<?php
/**
 * Plugin Name:       xZeroProtect
 * Plugin URI:        https://github.com/webrium/xzeroprotect-wp
 * Description:       Lightweight firewall for WordPress — blocks bots, scanners, and common attacks with zero external dependencies. Tracks real visitor analytics filtered from bot traffic.
 * Version:           1.1.3
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Webrium
 * Author URI:        https://github.com/webrium
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       xzeroprotect
 */

declare(strict_types=1);

defined("ABSPATH") || exit();

define("XZEROP_VERSION", "1.1.3");
define("XZEROP_FILE", __FILE__);
define("XZEROP_DIR", plugin_dir_path(__FILE__));
define("XZEROP_URL", plugin_dir_url(__FILE__));
define("XZEROP_SLUG", "xzeroprotect");
define("XZEROP_DB_TABLE", "xzerop_visits");

// ── Autoload ──────────────────────────────────────────────────────────────────
// Try Composer autoloader first (dev/manual install), then bundled fallback
$xzp_composer = XZEROP_DIR . "vendor/autoload.php";
if (file_exists($xzp_composer)) {
    require_once $xzp_composer;
} else {
    wp_die(
        wp_kses(
            __(
                "xZeroProtect requires Composer dependencies. Please run <code>composer install</code> inside the plugin directory.",
                "xzeroprotect",
            ),
            ["code" => []],
        ),
        esc_html__("xZeroProtect — Missing Dependencies", "xzeroprotect"),
    );
}

// ── Core includes ─────────────────────────────────────────────────────────────
require_once XZEROP_DIR . "includes/class-database.php";
require_once XZEROP_DIR . "includes/class-firewall.php";
require_once XZEROP_DIR . "includes/class-settings.php";
require_once XZEROP_DIR . "includes/class-admin.php";

// ── Activation / Deactivation ─────────────────────────────────────────────────
register_activation_hook(__FILE__, ["XZEROP_Database", "install"]);
register_deactivation_hook(__FILE__, ["XZEROP_Database", "deactivate"]);

// ── Boot ──────────────────────────────────────────────────────────────────────

// Firewall runs as early as possible — before WordPress processes the request
add_action("init", ["XZEROP_Firewall", "run"], 1);

// Admin UI
if (is_admin()) {
    new XZEROP_Admin();
}