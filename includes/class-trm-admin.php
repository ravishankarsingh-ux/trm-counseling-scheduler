<?php
/**
 * TRM Admin Dashboard
 * Handles admin interface and functionality
 */

class TRM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'TRM Counseling',
            'TRM Counseling',
            'manage_options',
            'trm-counseling',
            array($this, 'admin_dashboard'),
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'trm-counseling',
            'All Bookings',
            'All Bookings',
            'manage_options',
            'trm-counseling',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'trm-counseling',
            'Donations',
            'Donations',
            'manage_options',
            'trm-counseling-donations',
            array($this, 'donations_page')
        );
        
        add_submenu_page(
            'trm-counseling',
            'Settings',
            'Settings',
            'manage_options',
            'trm-counseling-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_dashboard() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        
        // Handle status updates
        if (isset($_POST['update_status']) && check_admin_referer('trm_update_status')) {
            $booking_id = intval($_POST['booking_id']);
            $new_status = sanitize_text_field($_POST['new_status']);
            
            $wpdb->update(
                $bookings_table,
                array('status' => $new_status),
                array('id' => $booking_id)
            );
            
            echo '<div class="notice notice-success"><p>Booking status updated successfully.</p></div>';
        }
        
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
        
        // Build query
        $where = "WHERE 1=1";
        if ($status_filter !== 'all') {
            $where .= $wpdb->prepare(" AND status = %s", $status_filter);
        }
        if ($date_filter) {
            $where .= $wpdb->prepare(" AND booking_date = %s", $date_filter);
        }
        
        $bookings = $wpdb->get_results("SELECT * FROM $bookings_table $where ORDER BY booking_date DESC, booking_time DESC");
        
        include TRM_COUNSELING_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    public function donations_page() {
        global $wpdb;
        $donations_table = $wpdb->prefix . 'trm_donations';
        
        $donations = $wpdb->get_results("SELECT * FROM $donations_table ORDER BY created_at DESC");
        
        include TRM_COUNSELING_PLUGIN_DIR . 'templates/admin-donations.php';
    }
    
    public function settings_page() {
        // Handle manual update check
        if (isset($_POST['trm_check_updates']) && check_admin_referer('trm_update_check_nonce')) {
            // Clear cache and force update check
            delete_transient('trm_update_check');
            delete_site_transient('update_plugins');
            
            // Force WordPress to check for updates
            wp_update_plugins();
            
            // Get the update transient to see if there's an update
            $update_plugins = get_site_transient('update_plugins');
            $plugin_file = 'trm-counseling-scheduler/trm-counseling-scheduler.php';
            
            if (isset($update_plugins->response[$plugin_file])) {
                $new_version = $update_plugins->response[$plugin_file]->new_version;
                echo '<div class="notice notice-warning"><p><strong>Update Available!</strong> Version ' . $new_version . ' is available. <a href="' . admin_url('plugins.php') . '">Go to Plugins page to update</a>.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p><strong>No updates available.</strong> You have the latest version (' . TRM_COUNSELING_VERSION . ').</p></div>';
            }
        }
        
        // Handle settings update
        if (isset($_POST['trm_save_settings']) && check_admin_referer('trm_settings_nonce')) {
            $settings = array(
                'session_duration_member',
                'session_duration_nonmember_paid',
                'session_duration_nonmember_free',
                'buffer_time',
                'working_hours_start',
                'working_hours_end',
                'notification_email',
                'payment_gateway',
                'stripe_public_key',
                'stripe_secret_key',
                'paypal_client_id',
                'paypal_secret',
                'offline_payment_instructions',
                'trm_slot_bg_color',
                'trm_slot_text_color',
                'trm_slot_selected_bg_color',
                'trm_slot_selected_text_color'
            );
            
            foreach ($settings as $setting) {
                if (isset($_POST[$setting])) {
                    TRM_Database::update_setting($setting, sanitize_text_field($_POST[$setting]));
                }
            }
            
            // Handle checkboxes for payment methods
            $checkbox_settings = array('enable_stripe', 'enable_paypal', 'enable_offline');
            foreach ($checkbox_settings as $setting) {
                $value = isset($_POST[$setting]) ? '1' : '0';
                TRM_Database::update_setting($setting, $value);
            }
            
            // Handle donation amounts (array)
            if (isset($_POST['donation_amounts'])) {
                $amounts = array_map('intval', explode(',', $_POST['donation_amounts']));
                TRM_Database::update_setting('donation_amounts', json_encode($amounts));
            }
            
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }
        
        include TRM_COUNSELING_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
