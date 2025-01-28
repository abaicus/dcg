<?php

/**
 * Plugin Name: Dummy Content Generator
 * Description: Generate dummy content for your WordPress site with customizable options.
 * Version: 1.0.0
 * Author: Your Name
 * License: GLWTPL
 * License URI: https://github.com/me-shaon/GLWTPL/blob/master/LICENSE
 */

if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('DCG_VERSION', '1.0.0');
define('DCG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCG_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once DCG_PLUGIN_DIR . 'includes/class-dcg-admin.php';

// Initialize the plugin
function dcg_init() {
  new DCG_Admin();
}
add_action('plugins_loaded', 'dcg_init');

// Register WP-CLI commands if available
if (defined('WP_CLI') && WP_CLI) {
  require_once DCG_PLUGIN_DIR . 'includes/class-dcg-cli.php';
  WP_CLI::add_command('dcg', 'DCG_CLI');
}

// Activation hook
function dcg_activate() {
  // Activation tasks if needed
}
register_activation_hook(__FILE__, 'dcg_activate');

// Deactivation hook
function dcg_deactivate() {
  // Cleanup tasks if needed
}
register_deactivation_hook(__FILE__, 'dcg_deactivate');
