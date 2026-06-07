<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

use Webrium\XZeroProtect\XZeroProtect;
use Webrium\XZeroProtect\VisitInfo;

/**
 * Bridges the xZeroProtect library with WordPress.
 *
 * Responsibilities:
 *  - Build the firewall config from plugin settings
 *  - Whitelist WordPress core paths/IPs automatically
 *  - Remove rules that conflict with WordPress (wp-admin, xmlrpc, etc.)
 *  - Hook visitor tracking into the database
 *  - Schedule periodic data pruning
 */
class XZP_Firewall
{
    public static function run(): void
    {
        $s = XZP_Settings::all();

        // Firewall is off
        if ($s['mode'] === 'off') {
            return;
        }

        // Skip firewall for logged-in admins on admin pages
        if (is_admin() && current_user_can('manage_options')) {
            return;
        }

        try {
            $firewall = XZeroProtect::init(self::buildConfig($s));

            self::applyWordPressRules($firewall, $s);

            if ($s['tracking_enabled']) {
                $firewall->enableTracking(function (VisitInfo $visit) use ($s) {
                    self::recordVisit($visit, $s);
                });
            }

            $firewall->run();

        } catch (\Throwable $e) {
            // Never crash WordPress — log silently
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[xZeroProtect] ' . $e->getMessage());
            }
        }
    }

    // ── Config builder ────────────────────────────────────────────────────────

    private static function buildConfig(array $s): array
    {
        return [
            'mode'         => $s['mode'],
            'storage_path' => WP_CONTENT_DIR . '/xzp-storage',

            'rate_limit' => [
                'enabled'      => $s['rate_limit_enabled'],
                'max_requests' => $s['rate_limit_max'],
                'per_seconds'  => $s['rate_limit_window'],
            ],

            'auto_ban' => [
                'enabled'              => $s['auto_ban_enabled'],
                'violations_threshold' => $s['auto_ban_threshold'],
                'ban_duration'         => $s['auto_ban_duration'],
                'permanent_after_bans' => $s['auto_ban_permanent'],
            ],

            'checks' => [
                'crawler_check' => $s['check_crawler'],
                'rate_limit'    => $s['check_rate_limit'],
                'blocked_path'  => $s['check_blocked_path'],
                'user_agent'    => $s['check_user_agent'],
                'payload'       => $s['check_payload'],
                'custom_rules'  => $s['check_custom_rules'],
            ],

            'apache_blocking' => $s['apache_blocking'],

            'whitelist' => [
                'ips'   => XZP_Settings::getWhitelistIps(),
                'paths' => XZP_Settings::getWhitelistPaths(),
            ],

            'block_response' => [
                'code'    => $s['block_code'],
                'message' => $s['block_message'],
            ],

            'log' => [
                'enabled'       => true,
                'max_file_size' => 10,
                'keep_days'     => $s['keep_days'],
            ],
        ];
    }

    // ── WordPress-specific rule adjustments ───────────────────────────────────

    private static function applyWordPressRules(XZeroProtect $fw, array $s): void
    {
        // Remove patterns that conflict with WordPress core
        $fw->patterns->removePath('wp-admin');
        $fw->patterns->removePath('wp-login');
        $fw->patterns->removePath('wp-config');
        $fw->patterns->removePath('xmlrpc');
        $fw->patterns->removePath('wordpress');
        $fw->patterns->removePath('administrator');

        // .php is only blocked if the user opted in
        if ($s['block_php_extension']) {
            $fw->patterns->addPath('.php');
        } else {
            $fw->patterns->removePath('.php');
        }

        // Optional low-level HTTP client blocking
        if ($s['block_curl'])   $fw->patterns->addAgent('curl/');
        if ($s['block_wget'])   $fw->patterns->addAgent('wget/');
        if ($s['block_python']) $fw->patterns->addAgent('python-requests');
        if ($s['block_go_http']) $fw->patterns->addAgent('go-http-client');

        // Whitelist current user's IP if logged-in admin
        $admin_ip = self::getClientIp();
        if ($admin_ip && current_user_can('manage_options')) {
            $fw->ip->whitelist($admin_ip);
        }
    }

    // ── Visitor tracking ──────────────────────────────────────────────────────

    private static function recordVisit(VisitInfo $visit, array $s): void
    {
        // Skip WordPress admin area visits
        if (str_starts_with($visit->path, '/wp-admin')) {
            return;
        }

        XZP_Database::insertVisit($visit->toArray());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // ── Scheduled cleanup ─────────────────────────────────────────────────────

    public static function schedulePruning(): void
    {
        if (!wp_next_scheduled('xzp_prune_data')) {
            wp_schedule_event(time(), 'daily', 'xzp_prune_data');
        }
    }

    public static function pruneData(): void
    {
        $days = (int) XZP_Settings::get('keep_days', 30);
        XZP_Database::pruneOldData($days);
    }
}

// Register scheduled pruning
add_action('xzp_prune_data', ['XZP_Firewall', 'pruneData']);
add_action('wp_loaded',      ['XZP_Firewall', 'schedulePruning']);
