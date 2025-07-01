<?php
/**
 * Plugin Name: CI vs RM Display Plugin
 * Plugin URI: https://sterlingdigital.com
 * Description: Displays Capital Improvement vs Repair and Maintenance items with advanced search functionality via shortcode and page templates.
 * Version: 2.2.0
 * Author: Sterling Digital
 * Author URI: https://sterlingdigital.com
 * License: GPL v2 or later
 * Text Domain: ci-vs-rm
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CIVSRM_PLUGIN_FILE', __FILE__);
define('CIVSRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CIVSRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CIVSRM_PLUGIN_VERSION', '2.2.0');
define('CIVSRM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once CIVSRM_PLUGIN_PATH . 'includes/class-civsrm-plugin.php';

// Initialize the plugin
function civsrm_init() {
    new CIVSRM_Plugin();
}
add_action('plugins_loaded', 'civsrm_init');

// Activation hook
register_activation_hook(__FILE__, 'civsrm_activate');
function civsrm_activate() {
    // Flush rewrite rules on activation
    flush_rewrite_rules();
    
    // Set default options if needed
    if (!get_option('civsrm_version')) {
        add_option('civsrm_version', CIVSRM_PLUGIN_VERSION);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'civsrm_deactivate');
function civsrm_deactivate() {
    // Clean up transients
    delete_transient('civsrm_shortcode_data');
    delete_transient('civsrm_optimized_data');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'civsrm_uninstall');
function civsrm_uninstall() {
    // Clean up options
    delete_option('civsrm_version');
    
    // Clean up any remaining transients
    delete_transient('civsrm_shortcode_data');
    delete_transient('civsrm_optimized_data');
}
