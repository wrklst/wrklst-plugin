<?php
/**
 * @package WrkLstPlugin
 */
/**
 * Plugin Name: WrkLst Plugin
 * Plugin URI: https://github.com/wrklst/wp-wrklst-plugin
 * Description: Integrate your WrkLst Database with your WordPress Website.
 * Version: 0.1
 * Author: Tobias Vielmetter-Diekmann
 * Author URI: https://wrklst.art/
 * License: GPLv2 or later
 * Text Domain: wrklst-plugin
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WRKLST_PLUGIN_VERSION', '0.1');
define('WRKLST_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WRKLST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WRKLST_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
if (file_exists(WRKLST_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WRKLST_PLUGIN_PATH . 'vendor/autoload.php';
}

// Plugin Activation
function activate_wrklst_plugin() {
    Inc\Base\Activate::exec();
}

// Plugin Deactivation
function deactivate_wrklst_plugin() {
    Inc\Base\Deactivate::exec();
}

// Register Hooks for Activation/Deactivation
register_activation_hook(__FILE__, 'activate_wrklst_plugin');
register_deactivation_hook(__FILE__, 'deactivate_wrklst_plugin');

// Init full plugin
if (class_exists('Inc\\Init')) {
    Inc\Init::register_services();
}