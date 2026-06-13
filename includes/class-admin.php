<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Registers all WordPress admin UI for xZeroProtect.
 */
class XZP_Admin
{
    public function __construct()
    {
        add_action('admin_menu',            [$this, 'registerMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_post_xzp_save_settings', [$this, 'handleSaveSettings']);
        add_action('admin_post_xzp_clear_data',    [$this, 'handleClearData']);
        add_action('wp_ajax_xzp_get_chart',        [$this, 'ajaxGetChart']);
        add_action('wp_ajax_xzp_get_stats',        [$this, 'ajaxGetStats']);
    }

    // ── Menus ─────────────────────────────────────────────────────────────────

    public function registerMenus(): void
    {
        add_menu_page(
            __('xZeroProtect', 'xzeroprotect-wp'),
            __('xZeroProtect', 'xzeroprotect-wp'),
            'manage_options',
            'xzeroprotect-wp',
            [$this, 'renderDashboard'],
            'dashicons-shield',
            80
        );

        add_submenu_page('xzeroprotect-wp',
            __('Dashboard', 'xzeroprotect-wp'),
            __('Dashboard', 'xzeroprotect-wp'),
            'manage_options', 'xzeroprotect-wp', [$this, 'renderDashboard']);

        add_submenu_page('xzeroprotect-wp',
            __('Visitors', 'xzeroprotect-wp'),
            __('Real Visitors', 'xzeroprotect-wp'),
            'manage_options', 'xzp-visitors', [$this, 'renderVisitors']);

        add_submenu_page('xzeroprotect-wp',
            __('Blocked', 'xzeroprotect-wp'),
            __('Blocked Requests', 'xzeroprotect-wp'),
            'manage_options', 'xzp-blocked', [$this, 'renderBlocked']);

        add_submenu_page('xzeroprotect-wp',
            __('Settings', 'xzeroprotect-wp'),
            __('Settings', 'xzeroprotect-wp'),
            'manage_options', 'xzp-settings', [$this, 'renderSettings']);
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public function enqueueAssets(string $hook): void
    {
        // WordPress hook suffix = sanitize_title(menu_title) + '_page_' + page_slug
        // sanitize_title('xZeroProtect') → 'xzeroprotect'  (NOT the menu slug)
        $xzp_pages = [
            'toplevel_page_xzeroprotect-wp',
            'xzeroprotect_page_xzp-visitors',
            'xzeroprotect_page_xzp-blocked',
            'xzeroprotect_page_xzp-settings',
        ];

        if (!in_array($hook, $xzp_pages, true)) {
            return;
        }

        wp_enqueue_style(
            'xzp-admin',
            XZPWP_URL . 'assets/css/admin.css',
            [],
            XZPWP_VERSION
        );

        // Chart.js — loaded locally (WordPress.org guideline #8: no external CDNs)
        wp_enqueue_script(
            'xzp-chartjs',
            XZPWP_URL . 'assets/js/chart.umd.min.js',
            [],
            '4.5.1',
            true
        );

        wp_enqueue_script(
            'xzp-admin',
            XZPWP_URL . 'assets/js/admin.js',
            ['jquery', 'xzp-chartjs'],
            XZPWP_VERSION,
            true
        );

        wp_localize_script('xzp-admin', 'xzpData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('xzp_ajax'),
            'i18n'    => [
                'visits'  => __('Visits', 'xzeroprotect-wp'),
                'unique'  => __('Unique', 'xzeroprotect-wp'),
                'blocked' => __('Blocked', 'xzeroprotect-wp'),
            ],
        ]);

        // Initial chart data for the dashboard page (avoids inline <script> tags).
        if ($hook === 'toplevel_page_xzeroprotect-wp') {
            $xzp_chart = XZP_Database::getVisitsChart(14);
            $xzp_chart_data = array_values(array_map(function ($day, $data) {
                return [
                    'date'   => $day,
                    'visits' => $data['visits'],
                    'unique' => $data['unique'],
                    'blocks' => $data['blocks'],
                ];
            }, array_keys($xzp_chart), $xzp_chart));

            wp_add_inline_script(
                'xzp-admin',
                'window.xzpChartData = ' . wp_json_encode($xzp_chart_data) . ';',
                'before'
            );
        }
    }

    // ── Page renderers ────────────────────────────────────────────────────────

    public function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $stats = XZP_Database::getVisitStats(30);
        $pages = XZP_Database::getTopPages(8, 30);
        $types = XZP_Database::getTopBlockTypes(30);
        $devs  = XZP_Database::getDeviceBreakdown(30);
        $mode  = XZP_Settings::get('mode');
        include XZPWP_DIR . 'admin/views/dashboard.php';
    }

    public function renderVisitors(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $visits = XZP_Database::getRecentVisits(100);
        include XZPWP_DIR . 'admin/views/visitors.php';
    }

    public function renderBlocked(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $blocks = XZP_Database::getRecentBlocks(100);
        include XZPWP_DIR . 'admin/views/blocked.php';
    }

    public function renderSettings(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $settings = XZP_Settings::all();
        $saved    = isset($_GET['saved']);
        include XZPWP_DIR . 'admin/views/settings.php';
    }

    // ── Form handlers ─────────────────────────────────────────────────────────

    public function handleSaveSettings(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        check_admin_referer('xzp_save_settings');
        XZP_Settings::save($_POST);
        wp_safe_redirect(add_query_arg(['page' => 'xzp-settings', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public function handleClearData(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        check_admin_referer('xzp_clear_data');
        $type = sanitize_text_field(wp_unslash($_POST['clear_type'] ?? 'all'));

        global $wpdb;
        if (in_array($type, ['visits', 'all'], true)) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}xzp_visits"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        if (in_array($type, ['blocks', 'all'], true)) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}xzp_blocks"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        wp_safe_redirect(add_query_arg(['page' => 'xzp-settings', 'cleared' => '1'], admin_url('admin.php')));
        exit;
    }

    // ── AJAX ──────────────────────────────────────────────────────────────────

    public function ajaxGetChart(): void
    {
        check_ajax_referer('xzp_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $days  = (int) wp_unslash($_POST['days'] ?? 14);
        $chart = XZP_Database::getVisitsChart(max(7, min(90, $days)));
        wp_send_json_success($chart);
    }

    public function ajaxGetStats(): void
    {
        check_ajax_referer('xzp_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $days  = (int) wp_unslash($_POST['days'] ?? 30);
        $stats = XZP_Database::getVisitStats(max(1, min(365, $days)));
        wp_send_json_success($stats);
    }
}
