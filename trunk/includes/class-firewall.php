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
class XZEROP_Firewall
{
    public static function run(): void
    {
        $s = XZEROP_Settings::all();

        // Firewall is off
        if ($s['mode'] === 'off') {
            return;
        }

        // Skip firewall for logged-in admins on admin pages
        if (is_admin() && current_user_can('manage_options')) {
            return;
        }

        // Skip firewall entirely for static asset requests.
        //
        // On many WordPress hosts these requests are served by the web server
        // and never reach PHP — but if a static file doesn't exist on disk
        // (e.g. /favicon.ico on a site that has no physical favicon),
        // WordPress's rewrite rules route the request to index.php. Counting
        // those toward the rate limit makes a single page view consume
        // several "requests" and triggers premature bans (issue #7).
        if (self::isStaticAssetRequest()) {
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
            // Never crash WordPress — log silently in debug mode only
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[xZeroProtect] ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }

    // ── Config builder ────────────────────────────────────────────────────────

    /**
     * Returns (and ensures) the directory where the firewall stores its
     * runtime data, inside the WordPress uploads directory.
     */
    private static function getStoragePath(): string
    {
        $upload_dir = wp_upload_dir();
        $path = trailingslashit($upload_dir['basedir']) . 'xzeroprotect';

        if (!file_exists($path)) {
            wp_mkdir_p($path);
        }

        // Prevent directory listing / direct access of stored data.
        $htaccess = $path . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
        $index = $path . '/index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, "<?php\n// Silence is golden.\n");
        }

        return $path;
    }

    private static function buildConfig(array $s): array
    {
        return [
            'mode'         => $s['mode'],
            'storage_path' => self::getStoragePath(),

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
                'ips'   => XZEROP_Settings::getWhitelistIps(),
                'paths' => XZEROP_Settings::getWhitelistPaths(),
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

        XZEROP_Database::insertVisit($visit->toArray());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function getClientIp(): string
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    }

    /**
     * Whether the current request targets a static asset that should bypass
     * the firewall entirely. Covers favicon, robots.txt, and common static
     * file extensions that browsers/crawlers fetch alongside page views.
     *
     * Skipping these prevents a single page view (HTML + favicon + a few
     * assets) from inflating the rate-limit counter into multiple "requests".
     */
    private static function isStaticAssetRequest(): bool
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($uri === '') {
            return false;
        }

        // Strip query string
        $path = strtolower((string) parse_url($uri, PHP_URL_PATH));
        if ($path === '') {
            return false;
        }

        // Exact-match static paths that have no extension
        static $exact = ['/favicon.ico', '/robots.txt', '/sitemap.xml', '/apple-touch-icon.png', '/apple-touch-icon-precomposed.png'];
        if (in_array($path, $exact, true)) {
            return true;
        }

        // Extension-based match — only assets that are part of normal page
        // loads. Extensions like .txt, .xml, .pdf, .zip are NOT skipped
        // because scanners actively probe them (/backup.zip, /db.txt, etc.).
        static $exts = [
            '.ico', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.bmp', '.avif',
            '.css', '.js', '.mjs', '.map',
            '.woff', '.woff2', '.ttf', '.otf', '.eot',
            '.mp3', '.mp4', '.webm', '.ogg', '.wav',
        ];
        foreach ($exts as $ext) {
            if (str_ends_with($path, $ext)) {
                return true;
            }
        }

        return false;
    }

    // ── Scheduled cleanup ─────────────────────────────────────────────────────

    public static function schedulePruning(): void
    {
        if (!wp_next_scheduled('xzerop_prune_data')) {
            wp_schedule_event(time(), 'daily', 'xzerop_prune_data');
        }
    }

    public static function pruneData(): void
    {
        $days = (int) XZEROP_Settings::get('keep_days', 30);
        XZEROP_Database::pruneOldData($days);
    }
}

// Register scheduled pruning
add_action('xzerop_prune_data', ['XZEROP_Firewall', 'pruneData']);
add_action('wp_loaded',      ['XZEROP_Firewall', 'schedulePruning']);
