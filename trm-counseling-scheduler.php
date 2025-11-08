<?php
/**
 * Plugin Name: TRM Counseling Session Scheduler
 * Plugin URI: https://trinityrevivalministry.org
 * Description: A comprehensive counseling session booking system with member/non-member differentiation, donation integration, and admin dashboard for Trinity Revival Ministry.
 * Version: 1.0.0
 * Author: Ebenezer Caurie
 * Author URI: https://trinityrevivalministry.org
 * License: GPL v2 or later
 * Text Domain: trm-counseling
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TRM_COUNSELING_VERSION', '1.0.0');
define('TRM_COUNSELING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TRM_COUNSELING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-database.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-events.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-booking.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-admin.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-shortcodes.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-ajax.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-payment.php';
require_once TRM_COUNSELING_PLUGIN_DIR . 'includes/class-trm-updater.php';

// Activation hook
register_activation_hook(__FILE__, 'trm_counseling_activate');
function trm_counseling_activate() {
    TRM_Database::create_tables();
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'trm_counseling_deactivate');
function trm_counseling_deactivate() {
    flush_rewrite_rules();
}

// Initialize plugin
add_action('plugins_loaded', 'trm_counseling_init');
function trm_counseling_init() {
    // Load text domain for translations
    load_plugin_textdomain('trm-counseling', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Ensure database tables are up to date
    if (is_admin()) {
        TRM_Database::create_tables();
    }
    
    // Initialize classes
    new TRM_Events();
    new TRM_Booking();
    new TRM_Admin();
    new TRM_Shortcodes();
    new TRM_Ajax();
    new TRM_Payment();
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'trm_counseling_enqueue_scripts');
function trm_counseling_enqueue_scripts() {
    wp_enqueue_style('trm-counseling-style', TRM_COUNSELING_PLUGIN_URL . 'assets/css/trm-style.css', array(), TRM_COUNSELING_VERSION);
    wp_enqueue_style('trm-booking-form-style', TRM_COUNSELING_PLUGIN_URL . 'assets/css/booking-form.css', array(), TRM_COUNSELING_VERSION);
    wp_enqueue_style('trm-booking-form-simple-style', TRM_COUNSELING_PLUGIN_URL . 'assets/css/booking-form-simple.css', array(), TRM_COUNSELING_VERSION);
    wp_enqueue_script('trm-counseling-script', TRM_COUNSELING_PLUGIN_URL . 'assets/js/trm-script.js', array('jquery'), TRM_COUNSELING_VERSION, true);
    wp_enqueue_script('trm-booking-form-simple', TRM_COUNSELING_PLUGIN_URL . 'assets/js/booking-form-simple.js', array('jquery'), TRM_COUNSELING_VERSION, true);
    
    wp_localize_script('trm-counseling-script', 'trmCounseling', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('trm-counseling-nonce')
    ));
    
    wp_localize_script('trm-booking-form-simple', 'trmCounseling', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('trm-counseling-nonce')
    ));
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'trm_counseling_admin_scripts');
function trm_counseling_admin_scripts($hook) {
    if (strpos($hook, 'trm-counseling') !== false) {
        wp_enqueue_style('trm-admin-style', TRM_COUNSELING_PLUGIN_URL . 'assets/css/trm-admin.css', array(), TRM_COUNSELING_VERSION);
        wp_enqueue_script('trm-admin-script', TRM_COUNSELING_PLUGIN_URL . 'assets/js/trm-admin.js', array('jquery'), TRM_COUNSELING_VERSION, true);
    }
}
