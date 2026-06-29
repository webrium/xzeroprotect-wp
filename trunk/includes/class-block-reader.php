<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Reads blocked / suspicious requests directly from the xZeroProtect
 * library's file-based log (storage/logs/attacks.log).
 *
 * The library is file-based by design — it does not write blocks to the
 * database. This class bridges that gap for the WordPress admin UI by
 * parsing the on-disk log into structured arrays that match the schema
 * the views expect (blocked_at, ip, uri, method, block_type, reason,
 * user_agent).
 *
 * Log line format (produced by Webrium\XZeroProtect\Storage::appendLog):
 *   2024-11-15 14:32:01 | ip=1.2.3.4 | type=rate_limit | uri=/foo | reason=... | ua=...
 */
class XZEROP_BlockReader
{
    /**
     * Absolute path to attacks.log (mirrors XZEROP_Firewall::getStoragePath()).
     */
    private static function logFile(): string
    {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']) . 'xzeroprotect/logs/attacks.log';
    }

    /**
     * Parse one raw log line into the array shape expected by the views.
     * Returns null if the line is malformed.
     */
    private static function parseLine(string $line): ?array
    {
        // Expect: "YYYY-MM-DD HH:MM:SS | key=value | key=value | ..."
        if (!preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s*\|\s*(.+)$/', $line, $m)) {
            return null;
        }

        $entry = [
            'blocked_at' => $m[1],
            'ip'         => '',
            'uri'        => '',
            'method'     => 'GET',
            'block_type' => 'unknown',
            'reason'     => '',
            'user_agent' => '',
        ];

        foreach (explode(' | ', $m[2]) as $pair) {
            $pos = strpos($pair, '=');
            if ($pos === false) {
                continue;
            }
            $key = substr($pair, 0, $pos);
            $val = substr($pair, $pos + 1);

            switch ($key) {
                case 'ip':     $entry['ip']         = $val; break;
                case 'type':   $entry['block_type'] = $val ?: 'unknown'; break;
                case 'uri':    $entry['uri']        = $val; break;
                case 'reason': $entry['reason']     = $val; break;
                case 'ua':     $entry['user_agent'] = $val; break;
                case 'method': $entry['method']     = $val ?: 'GET'; break;
            }
        }

        return $entry;
    }

    /**
     * Read the most recent log lines (newest first).
     * Reads only the tail of the file for efficiency.
     */
    public static function recent(int $limit = 100): array
    {
        $file = self::logFile();
        if (!is_readable($file)) {
            return [];
        }

        $lines = self::tail($file, max(1, $limit) * 2);
        if (!$lines) {
            return [];
        }

        // Newest first
        $lines = array_reverse($lines);

        $out = [];
        foreach ($lines as $line) {
            $parsed = self::parseLine($line);
            if ($parsed === null) {
                continue;
            }
            $out[] = $parsed;
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    /**
     * Count blocks since a given MySQL-formatted datetime ("Y-m-d H:i:s").
     */
    public static function countSince(string $sinceDateTime): int
    {
        $file = self::logFile();
        if (!is_readable($file)) {
            return 0;
        }

        $fp = @fopen($file, 'r');
        if (!$fp) {
            return 0;
        }

        $count = 0;
        while (($line = fgets($fp)) !== false) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $m)) {
                if ($m[1] >= $sinceDateTime) {
                    $count++;
                }
            }
        }
        fclose($fp);
        return $count;
    }

    /**
     * Group by block_type for the last $days days.
     * Returns rows shaped like the old DB query: [['block_type' => ..., 'total' => N], ...]
     */
    public static function topTypes(int $days = 30, int $limit = 10): array
    {
        $since = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        $file  = self::logFile();
        if (!is_readable($file)) {
            return [];
        }

        $fp = @fopen($file, 'r');
        if (!$fp) {
            return [];
        }

        $counts = [];
        while (($line = fgets($fp)) !== false) {
            $entry = self::parseLine(rtrim($line, "\r\n"));
            if ($entry === null || $entry['blocked_at'] < $since) {
                continue;
            }
            $type = $entry['block_type'] !== '' ? $entry['block_type'] : 'unknown';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        fclose($fp);

        arsort($counts);
        $out = [];
        foreach (array_slice($counts, 0, $limit, true) as $type => $total) {
            $out[] = ['block_type' => $type, 'total' => $total];
        }
        return $out;
    }

    /**
     * Blocks-per-day for the chart. Returns [ 'Y-m-d' => count, ... ].
     */
    public static function blocksPerDay(int $days = 14): array
    {
        $startDate = gmdate('Y-m-d', strtotime('-' . max(0, $days - 1) . ' days'));
        $file      = self::logFile();
        $counts    = [];
        if (!is_readable($file)) {
            return $counts;
        }

        $fp = @fopen($file, 'r');
        if (!$fp) {
            return $counts;
        }

        while (($line = fgets($fp)) !== false) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2}) /', $line, $m)) {
                $day = $m[1];
                if ($day < $startDate) {
                    continue;
                }
                $counts[$day] = ($counts[$day] ?? 0) + 1;
            }
        }
        fclose($fp);

        return $counts;
    }

    /**
     * Delete the log file (used by the "Clear data" admin action).
     */
    public static function clear(): void
    {
        $file = self::logFile();
        if (file_exists($file)) {
            @unlink($file);
        }
        // Remove rotated backups as well
        foreach (glob(dirname($file) . '/attacks.log.*.bak') ?: [] as $bak) {
            @unlink($bak);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Efficiently read the last $lines lines of a file without slurping it whole.
     */
    private static function tail(string $file, int $lines): array
    {
        $fp = @fopen($file, 'r');
        if (!$fp) {
            return [];
        }

        fseek($fp, 0, SEEK_END);
        $pos    = ftell($fp);
        $buffer = '';
        $found  = 0;
        $chunk  = 4096;

        while ($pos > 0 && $found <= $lines) {
            $read = min($chunk, $pos);
            $pos -= $read;
            fseek($fp, $pos, SEEK_SET);
            $buffer = fread($fp, $read) . $buffer;
            $found  = substr_count($buffer, "\n");
        }
        fclose($fp);

        $all  = preg_split('/\r?\n/', $buffer) ?: [];
        $all  = array_values(array_filter($all, fn($l) => $l !== ''));
        return array_slice($all, -$lines);
    }
}
