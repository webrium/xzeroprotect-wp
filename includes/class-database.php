<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Handles all database operations for xZeroProtect.
 *
 * Tables:
 *   {prefix}xzp_visits  — verified real visitors (passed all firewall checks)
 *   {prefix}xzp_blocks  — blocked requests (bot/attack/suspicious)
 */
class XZP_Database
{
    // ── Schema version — bump when altering table structure ──────────────────
    private const SCHEMA_VERSION = '1';

    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $visits  = $wpdb->prefix . 'xzp_visits';
        $blocks  = $wpdb->prefix . 'xzp_blocks';

        // Real visitor log
        dbDelta("CREATE TABLE {$visits} (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ip            VARCHAR(45)     NOT NULL,
            path          VARCHAR(2048)   NOT NULL,
            method        VARCHAR(10)     NOT NULL DEFAULT 'GET',
            referer       VARCHAR(2048)   NOT NULL DEFAULT '',
            user_agent    TEXT            NOT NULL,
            browser       VARCHAR(80)     NOT NULL DEFAULT '',
            browser_ver   VARCHAR(30)     NOT NULL DEFAULT '',
            os            VARCHAR(80)     NOT NULL DEFAULT '',
            os_ver        VARCHAR(30)     NOT NULL DEFAULT '',
            device_type   VARCHAR(10)     NOT NULL DEFAULT 'desktop',
            is_mobile     TINYINT(1)      NOT NULL DEFAULT 0,
            fingerprint   CHAR(64)        NOT NULL,
            visited_at    DATETIME        NOT NULL,
            PRIMARY KEY   (id),
            KEY idx_fingerprint (fingerprint),
            KEY idx_path        (path(191)),
            KEY idx_visited_at  (visited_at),
            KEY idx_device_type (device_type)
        ) {$charset};");

        // Blocked request log
        dbDelta("CREATE TABLE {$blocks} (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ip            VARCHAR(45)     NOT NULL,
            uri           VARCHAR(2048)   NOT NULL,
            method        VARCHAR(10)     NOT NULL DEFAULT 'GET',
            user_agent    TEXT            NOT NULL,
            block_type    VARCHAR(60)     NOT NULL,
            reason        VARCHAR(500)    NOT NULL DEFAULT '',
            blocked_at    DATETIME        NOT NULL,
            PRIMARY KEY   (id),
            KEY idx_ip         (ip),
            KEY idx_block_type (block_type),
            KEY idx_blocked_at (blocked_at)
        ) {$charset};");

        update_option('xzp_db_version', self::SCHEMA_VERSION);
    }

    public static function deactivate(): void
    {
        // Tables are kept on deactivation — only removed on uninstall
    }

    public static function uninstall(): void
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}xzp_visits");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}xzp_blocks");
        delete_option('xzp_db_version');
        delete_option('xzp_settings');
    }

    // ── Visits ────────────────────────────────────────────────────────────────

    public static function insertVisit(array $data): void
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'xzp_visits',
            [
                'ip'          => $data['ip']          ?? '',
                'path'        => substr($data['path'] ?? '', 0, 2048),
                'method'      => $data['method']       ?? 'GET',
                'referer'     => substr($data['referer'] ?? '', 0, 2048),
                'user_agent'  => $data['user_agent']  ?? '',
                'browser'     => $data['browser']     ?? '',
                'browser_ver' => $data['browser_ver'] ?? '',
                'os'          => $data['os']          ?? '',
                'os_ver'      => $data['os_ver']      ?? '',
                'device_type' => $data['device_type'] ?? 'desktop',
                'is_mobile'   => (int) ($data['is_mobile'] ?? 0),
                'fingerprint' => $data['fingerprint'] ?? '',
                'visited_at'  => current_time('mysql'),
            ],
            ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s']
        );
    }

    public static function insertBlock(array $data): void
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'xzp_blocks',
            [
                'ip'         => $data['ip']         ?? '',
                'uri'        => substr($data['uri'] ?? '', 0, 2048),
                'method'     => $data['method']      ?? 'GET',
                'user_agent' => $data['user_agent'] ?? '',
                'block_type' => $data['block_type'] ?? 'unknown',
                'reason'     => substr($data['reason'] ?? '', 0, 500),
                'blocked_at' => current_time('mysql'),
            ],
            ['%s','%s','%s','%s','%s','%s','%s']
        );
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public static function getVisitStats(int $days = 30): array
    {
        global $wpdb;
        $visits = $wpdb->prefix . 'xzp_visits';
        $blocks = $wpdb->prefix . 'xzp_blocks';
        $since  = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return [
            'total_visits'   => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$visits} WHERE visited_at >= %s", $since)),

            'unique_visitors' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT fingerprint) FROM {$visits} WHERE visited_at >= %s", $since)),

            'total_blocks'   => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$blocks} WHERE blocked_at >= %s", $since)),

            'blocked_today'  => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$blocks} WHERE blocked_at >= %s", date('Y-m-d 00:00:00'))),

            'visits_today'   => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$visits} WHERE visited_at >= %s", date('Y-m-d 00:00:00'))),

            'unique_today'   => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT fingerprint) FROM {$visits} WHERE visited_at >= %s", date('Y-m-d 00:00:00'))),
        ];
    }

    public static function getVisitsChart(int $days = 14): array
    {
        global $wpdb;
        $visits = $wpdb->prefix . 'xzp_visits';
        $blocks = $wpdb->prefix . 'xzp_blocks';
        $since  = date('Y-m-d', strtotime("-{$days} days"));

        $visitRows = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(visited_at) as day, COUNT(*) as total, COUNT(DISTINCT fingerprint) as unique_v
             FROM {$visits} WHERE visited_at >= %s GROUP BY day ORDER BY day ASC",
            $since
        ), ARRAY_A);

        $blockRows = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(blocked_at) as day, COUNT(*) as total
             FROM {$blocks} WHERE blocked_at >= %s GROUP BY day ORDER BY day ASC",
            $since
        ), ARRAY_A);

        // Merge into a single indexed structure
        $chart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $chart[date('Y-m-d', strtotime("-{$i} days"))] = ['visits' => 0, 'unique' => 0, 'blocks' => 0];
        }
        foreach ($visitRows as $r) {
            if (isset($chart[$r['day']])) {
                $chart[$r['day']]['visits'] = (int)$r['total'];
                $chart[$r['day']]['unique'] = (int)$r['unique_v'];
            }
        }
        foreach ($blockRows as $r) {
            if (isset($chart[$r['day']])) {
                $chart[$r['day']]['blocks'] = (int)$r['total'];
            }
        }
        return $chart;
    }

    public static function getTopPages(int $limit = 10, int $days = 30): array
    {
        global $wpdb;
        $visits = $wpdb->prefix . 'xzp_visits';
        $since  = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $wpdb->get_results($wpdb->prepare(
            "SELECT path, COUNT(*) as hits, COUNT(DISTINCT fingerprint) as unique_v
             FROM {$visits} WHERE visited_at >= %s
             GROUP BY path ORDER BY hits DESC LIMIT %d",
            $since, $limit
        ), ARRAY_A) ?: [];
    }

    public static function getTopBlockTypes(int $days = 30): array
    {
        global $wpdb;
        $blocks = $wpdb->prefix . 'xzp_blocks';
        $since  = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $wpdb->get_results($wpdb->prepare(
            "SELECT block_type, COUNT(*) as total
             FROM {$blocks} WHERE blocked_at >= %s
             GROUP BY block_type ORDER BY total DESC LIMIT 10",
            $since
        ), ARRAY_A) ?: [];
    }

    public static function getDeviceBreakdown(int $days = 30): array
    {
        global $wpdb;
        $visits = $wpdb->prefix . 'xzp_visits';
        $since  = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as total
             FROM {$visits} WHERE visited_at >= %s
             GROUP BY device_type",
            $since
        ), ARRAY_A) ?: [];
    }

    public static function getRecentBlocks(int $limit = 50): array
    {
        global $wpdb;
        $blocks = $wpdb->prefix . 'xzp_blocks';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$blocks} ORDER BY blocked_at DESC LIMIT %d", $limit
        ), ARRAY_A) ?: [];
    }

    public static function getRecentVisits(int $limit = 50): array
    {
        global $wpdb;
        $visits = $wpdb->prefix . 'xzp_visits';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$visits} ORDER BY visited_at DESC LIMIT %d", $limit
        ), ARRAY_A) ?: [];
    }

    public static function pruneOldData(int $keepDays): void
    {
        global $wpdb;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}xzp_visits WHERE visited_at < %s", $cutoff));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}xzp_blocks WHERE blocked_at < %s", $cutoff));
    }
}
