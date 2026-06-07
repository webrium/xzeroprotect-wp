<?php
/**
 * Plugin Name:       xZeroProtect
 * Plugin URI:        https://github.com/webrium/xzeroprotect
 * Description:       Lightweight firewall for WordPress — blocks bots, scanners, and common attacks with zero external dependencies. Tracks real visitor analytics filtered from bot traffic.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Webrium
 * Author URI:        https://github.com/webrium
 * License:           MIT
 * Text Domain:       xzeroprotect
 * Domain Path:       /languages
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('XZPWP_VERSION',   '1.0.0');
define('XZPWP_FILE',      __FILE__);
define('XZPWP_DIR',       plugin_dir_path(__FILE__));
define('XZPWP_URL',       plugin_dir_url(__FILE__));
define('XZPWP_SLUG',      'xzeroprotect');
define('XZPWP_DB_TABLE',  'xzp_visits');

// ── Autoload ──────────────────────────────────────────────────────────────────
// Try Composer autoloader first (dev/manual install), then bundled fallback
$composer = XZPWP_DIR . 'vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
} else {
    wp_die(
        __('xZeroProtect requires Composer dependencies. Please run <code>composer install</code> inside the plugin directory.', 'xzeroprotect'),
        __('xZeroProtect — Missing Dependencies', 'xzeroprotect')
    );
}

// ── Core includes ─────────────────────────────────────────────────────────────
require_once XZPWP_DIR . 'includes/class-database.php';
require_once XZPWP_DIR . 'includes/class-firewall.php';
require_once XZPWP_DIR . 'includes/class-settings.php';
require_once XZPWP_DIR . 'includes/class-admin.php';

// ── Activation / Deactivation ─────────────────────────────────────────────────
register_activation_hook(__FILE__,   ['XZP_Database', 'install']);
register_deactivation_hook(__FILE__, ['XZP_Database', 'deactivate']);

// ── Boot ──────────────────────────────────────────────────────────────────────
add_action('plugins_loaded', function () {
    load_plugin_textdomain('xzeroprotect', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Firewall runs as early as possible — before WordPress processes the request
add_action('init', ['XZP_Firewall', 'run'], 1);

// Admin UI
if (is_admin()) {
    new XZP_Admin();
}
