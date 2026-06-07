<?php
/**
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all database tables and plugin options.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
XZP_Database::uninstall();
