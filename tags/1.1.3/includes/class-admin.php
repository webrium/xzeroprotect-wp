<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Registers all WordPress admin UI for xZeroProtect.
 */
class XZEROP_Admin
{
    public function __construct()
    {
        add_action('admin_menu',            [$this, 'registerMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_xzerop_save_settings', [$this, 'handleSaveSettings']);
        add_action('admin_post_xzerop_clear_data',    [$this, 'handleClearData']);
        add_action('wp_ajax_xzerop_get_chart',        [$this, 'ajaxGetChart']);
        add_action('wp_ajax_xzerop_get_stats',        [$this, 'ajaxGetStats']);
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    public function registerMenus(): void
    {
        add_menu_page(
            __('xZeroProtect', 'xzeroprotect'),
            __('xZeroProtect', 'xzeroprotect'),
            'manage_options',
            'xzeroprotect',
            [$this, 'renderDashboard'],
            'dashicons-shield',
            80
        );

        add_submenu_page('xzeroprotect',
            __('Dashboard', 'xzeroprotect'),
            __('Dashboard', 'xzeroprotect'),
            'manage_options', 'xzeroprotect', [$this, 'renderDashboard']);

        add_submenu_page('xzeroprotect',
            __('Visitors', 'xzeroprotect'),
            __('Real Visitors', 'xzeroprotect'),
            'manage_options', 'xzerop-visitors', [$this, 'renderVisitors']);

        add_submenu_page('xzeroprotect',
            __('Blocked', 'xzeroprotect'),
            __('Blocked Requests', 'xzeroprotect'),
            'manage_options', 'xzerop-blocked', [$this, 'renderBlocked']);

        add_submenu_page('xzeroprotect',
            __('Settings', 'xzeroprotect'),
            __('Settings', 'xzeroprotect'),
            'manage_options', 'xzerop-settings', [$this, 'renderSettings']);
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public function enqueueAssets(string $hook): void
    {
        // WordPress hook suffix = sanitize_title(menu_title) + '_page_' + page_slug
        // sanitize_title('xZeroProtect') → 'xzeroprotect'  (NOT the menu slug)
        $xzp_pages = [
            'toplevel_page_xzeroprotect',
            'xzeroprotect_page_xzerop-visitors',
            'xzeroprotect_page_xzerop-blocked',
            'xzeroprotect_page_xzerop-settings',
        ];

        if (!in_array($hook, $xzp_pages, true)) {
            return;
        }

        wp_enqueue_style(
            'xzerop-admin',
            XZEROP_URL . 'assets/css/admin.css',
            [],
            XZEROP_VERSION
        );

        // Chart.js — loaded locally (WordPress.org guideline #8: no external CDNs)
        wp_enqueue_script(
            'xzerop-chartjs',
            XZEROP_URL . 'assets/js/chart.umd.min.js',
            [],
            '4.5.1',
            true
        );

        wp_enqueue_script(
            'xzerop-admin',
            XZEROP_URL . 'assets/js/admin.js',
            ['jquery', 'xzerop-chartjs'],
            XZEROP_VERSION,
            true
        );

        wp_localize_script('xzerop-admin', 'xzeropData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('xzerop_ajax'),
            'i18n'    => [
                'visits'  => __('Visits', 'xzeroprotect'),
                'unique'  => __('Unique', 'xzeroprotect'),
                'blocked' => __('Blocked', 'xzeroprotect'),
            ],
        ]);

        // Initial chart data for the dashboard page (avoids inline <script> tags).
        if ($hook === 'toplevel_page_xzeroprotect') {
            $xzp_chart = XZEROP_Database::getVisitsChart(14);
            $xzp_chart_data = array_values(array_map(function ($day, $data) {
                return [
                    'date'   => $day,
                    'visits' => $data['visits'],
                    'unique' => $data['unique'],
                    'blocks' => $data['blocks'],
                ];
            }, array_keys($xzp_chart), $xzp_chart));

            wp_add_inline_script(
                'xzerop-admin',
                'window.xzeropChartData = ' . wp_json_encode($xzp_chart_data) . ';',
                'before'
            );
        }
    }

    // ── Page renderers ────────────────────────────────────────────────────────

    public function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $stats = XZEROP_Database::getVisitStats(30);
        $pages = XZEROP_Database::getTopPages(8, 30);
        $types = XZEROP_Database::getTopBlockTypes(30);
        $devs  = XZEROP_Database::getDeviceBreakdown(30);
        $mode  = XZEROP_Settings::get('mode');
        include XZEROP_DIR . 'admin/views/dashboard.php';
    }

    public function renderVisitors(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $visits = XZEROP_Database::getRecentVisits(100);
        include XZEROP_DIR . 'admin/views/visitors.php';
    }

    public function renderBlocked(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $blocks = XZEROP_Database::getRecentBlocks(100);
        include XZEROP_DIR . 'admin/views/blocked.php';
    }

    public function renderSettings(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $settings = XZEROP_Settings::all();
        $saved    = isset($_GET['saved']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UI flag, no data processed; actual action is nonce-protected in handleSaveSettings()
        include XZEROP_DIR . 'admin/views/settings.php';
    }

    // ── Form handlers ─────────────────────────────────────────────────────────

    public function handleSaveSettings(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        check_admin_referer('xzerop_save_settings');
        XZEROP_Settings::save($_POST);
        wp_safe_redirect(add_query_arg(['page' => 'xzerop-settings', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public function handleClearData(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        check_admin_referer('xzerop_clear_data');
        $type = sanitize_text_field(wp_unslash($_POST['clear_type'] ?? 'all'));

        global $wpdb;
        if (in_array($type, ['visits', 'all'], true)) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}xzerop_visits"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        if (in_array($type, ['blocks', 'all'], true)) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}xzerop_blocks"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        wp_safe_redirect(add_query_arg(['page' => 'xzerop-settings', 'cleared' => '1'], admin_url('admin.php')));
        exit;
    }

    // ── AJAX ──────────────────────────────────────────────────────────────────

    public function ajaxGetChart(): void
    {
        check_ajax_referer('xzerop_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $days  = (int) sanitize_text_field(wp_unslash($_POST['days'] ?? 14));
        $chart = XZEROP_Database::getVisitsChart(max(7, min(90, $days)));
        wp_send_json_success($chart);
    }

    public function ajaxGetStats(): void
    {
        check_ajax_referer('xzerop_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $days  = (int) sanitize_text_field(wp_unslash($_POST['days'] ?? 30));
        $stats = XZEROP_Database::getVisitStats(max(1, min(365, $days)));
        wp_send_json_success($stats);
    }
}
