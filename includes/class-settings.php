<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Manages xZeroProtect plugin settings stored in wp_options.
 */
class XZP_Settings
{
    private const OPTION_KEY = 'xzp_settings';

    private static array $defaults = [
        // Firewall mode
        'mode'                  => 'production',   // production | learning | off

        // Rate limiting
        'rate_limit_enabled'    => true,
        'rate_limit_max'        => 60,
        'rate_limit_window'     => 60,

        // Auto-ban
        'auto_ban_enabled'      => true,
        'auto_ban_threshold'    => 10,
        'auto_ban_duration'     => 86400,
        'auto_ban_permanent'    => 3,

        // Detection checks
        'check_crawler'         => true,
        'check_rate_limit'      => true,
        'check_blocked_path'    => true,
        'check_user_agent'      => true,
        'check_payload'         => true,
        'check_custom_rules'    => true,

        // Extra path/agent blocks
        'block_php_extension'   => false,
        'block_curl'            => false,
        'block_wget'            => false,
        'block_python'          => false,
        'block_go_http'         => false,

        // Whitelist
        'whitelist_ips'         => '',   // newline-separated
        'whitelist_paths'       => '',

        // Visitor tracking
        'tracking_enabled'      => true,

        // Apache blocking
        'apache_blocking'       => false,

        // Block response
        'block_code'            => 403,
        'block_message'         => 'Access Denied',

        // Data retention
        'keep_days'             => 30,

        // WordPress-specific safe paths (always whitelisted)
        'wp_safe_paths'         => true,
    ];

    // ── Read ──────────────────────────────────────────────────────────────────

    public static function all(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        return array_merge(self::$defaults, is_array($saved) ? $saved : []);
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        $settings = self::all();
        return $settings[$key] ?? $fallback ?? self::$defaults[$key] ?? null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public static function save(array $input): bool
    {
        $clean = self::sanitize($input);
        return update_option(self::OPTION_KEY, $clean);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function getWhitelistIps(): array
    {
        $raw = self::get('whitelist_ips', '');
        return array_filter(array_map('trim', explode("\n", $raw)));
    }

    public static function getWhitelistPaths(): array
    {
        $raw = self::get('whitelist_paths', '');
        $paths = array_filter(array_map('trim', explode("\n", $raw)));

        // Always include WordPress core paths
        if (self::get('wp_safe_paths')) {
            $paths = array_merge($paths, self::wpCorePaths());
        }
        return array_values(array_unique($paths));
    }

    public static function wpCorePaths(): array
    {
        return [
            '/wp-admin',
            '/wp-login.php',
            '/wp-cron.php',
            '/wp-json',
            '/xmlrpc.php',   // only if the site uses it
        ];
    }

    // ── Sanitize ──────────────────────────────────────────────────────────────

    private static function sanitize(array $input): array
    {
        $d = self::$defaults;
        return [
            'mode'                 => in_array($input['mode'] ?? '', ['production','learning','off'])
                                        ? $input['mode'] : $d['mode'],

            'rate_limit_enabled'   => !empty($input['rate_limit_enabled']),
            'rate_limit_max'       => max(1, (int)($input['rate_limit_max'] ?? $d['rate_limit_max'])),
            'rate_limit_window'    => max(1, (int)($input['rate_limit_window'] ?? $d['rate_limit_window'])),

            'auto_ban_enabled'     => !empty($input['auto_ban_enabled']),
            'auto_ban_threshold'   => max(1, (int)($input['auto_ban_threshold'] ?? $d['auto_ban_threshold'])),
            'auto_ban_duration'    => max(60, (int)($input['auto_ban_duration'] ?? $d['auto_ban_duration'])),
            'auto_ban_permanent'   => max(1, (int)($input['auto_ban_permanent'] ?? $d['auto_ban_permanent'])),

            'check_crawler'        => !empty($input['check_crawler']),
            'check_rate_limit'     => !empty($input['check_rate_limit']),
            'check_blocked_path'   => !empty($input['check_blocked_path']),
            'check_user_agent'     => !empty($input['check_user_agent']),
            'check_payload'        => !empty($input['check_payload']),
            'check_custom_rules'   => !empty($input['check_custom_rules']),

            'block_php_extension'  => !empty($input['block_php_extension']),
            'block_curl'           => !empty($input['block_curl']),
            'block_wget'           => !empty($input['block_wget']),
            'block_python'         => !empty($input['block_python']),
            'block_go_http'        => !empty($input['block_go_http']),

            'whitelist_ips'        => sanitize_textarea_field($input['whitelist_ips'] ?? ''),
            'whitelist_paths'      => sanitize_textarea_field($input['whitelist_paths'] ?? ''),

            'tracking_enabled'     => !empty($input['tracking_enabled']),
            'apache_blocking'      => !empty($input['apache_blocking']),

            'block_code'           => in_array((int)($input['block_code'] ?? 403), [403,429,503])
                                        ? (int)$input['block_code'] : 403,
            'block_message'        => sanitize_text_field($input['block_message'] ?? $d['block_message']),

            'keep_days'            => max(1, min(365, (int)($input['keep_days'] ?? $d['keep_days']))),
            'wp_safe_paths'        => !empty($input['wp_safe_paths']),
        ];
    }

    public static function defaults(): array
    {
        return self::$defaults;
    }
}
