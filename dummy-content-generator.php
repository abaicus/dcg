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
  // Create a dummy user during plugin activation
  $dummy_user = get_user_by('login', 'dcg_dummy_user');
  if (!$dummy_user) {
    $dummy_user_id = wp_create_user('dcg_dummy_user', wp_generate_password(), 'dcg_dummy_user@example.com');
    wp_update_user(array(
      'ID' => $dummy_user_id,
      'role' => 'author',
      'display_name' => 'Danny Creed Genobli'
    ));
  }
}
register_activation_hook(__FILE__, 'dcg_activate');

// Deactivation hook
function dcg_deactivate() {
  // Remove the dummy user during plugin deactivation
  $dummy_user = get_user_by('login', 'dcg_dummy_user');
  if ($dummy_user) {
    wp_delete_user($dummy_user->ID);
  }
}
register_deactivation_hook(__FILE__, 'dcg_deactivate');
