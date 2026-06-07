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
            'manage_options', 'xzp-visitors', [$this, 'renderVisitors']);

        add_submenu_page('xzeroprotect',
            __('Blocked', 'xzeroprotect'),
            __('Blocked Requests', 'xzeroprotect'),
            'manage_options', 'xzp-blocked', [$this, 'renderBlocked']);

        add_submenu_page('xzeroprotect',
            __('Settings', 'xzeroprotect'),
            __('Settings', 'xzeroprotect'),
            'manage_options', 'xzp-settings', [$this, 'renderSettings']);
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public function enqueueAssets(string $hook): void
    {
        $xzp_pages = [
            'toplevel_page_xzeroprotect',
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

        wp_enqueue_script(
            'xzp-admin',
            XZPWP_URL . 'assets/js/admin.js',
            ['jquery'],
            XZPWP_VERSION,
            true
        );

        wp_localize_script('xzp-admin', 'xzpData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('xzp_ajax'),
            'i18n'    => [
                'visits'  => __('Visits', 'xzeroprotect'),
                'unique'  => __('Unique', 'xzeroprotect'),
                'blocked' => __('Blocked', 'xzeroprotect'),
            ],
        ]);
    }

    // ── Page renderers ────────────────────────────────────────────────────────

    public function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        $stats = XZP_Database::getVisitStats(30);
        $chart = XZP_Database::getVisitsChart(14);
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
        wp_redirect(add_query_arg(['page' => 'xzp-settings', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public function handleClearData(): void
    {
        if (!current_user_can('manage_options')) wp_die();
        check_admin_referer('xzp_clear_data');
        $type = sanitize_text_field($_POST['clear_type'] ?? 'all');

        global $wpdb;
        if (in_array($type, ['visits', 'all'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}xzp_visits");
        }
        if (in_array($type, ['blocks', 'all'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}xzp_blocks");
        }
        wp_redirect(add_query_arg(['page' => 'xzp-settings', 'cleared' => '1'], admin_url('admin.php')));
        exit;
    }

    // ── AJAX ──────────────────────────────────────────────────────────────────

    public function ajaxGetChart(): void
    {
        check_ajax_referer('xzp_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $days  = (int) ($_POST['days'] ?? 14);
        $chart = XZP_Database::getVisitsChart(max(7, min(90, $days)));
        wp_send_json_success($chart);
    }

    public function ajaxGetStats(): void
    {
        check_ajax_referer('xzp_ajax', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $days  = (int) ($_POST['days'] ?? 30);
        $stats = XZP_Database::getVisitStats(max(1, min(365, $days)));
        wp_send_json_success($stats);
    }
}
