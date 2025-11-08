<?php
/**
 * TRM Database Handler
 * Creates and manages database tables for counseling sessions
 */

class TRM_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookings table
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $bookings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            full_name varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            email varchar(255) DEFAULT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            is_member tinyint(1) DEFAULT 0,
            donation_amount decimal(10,2) DEFAULT 0.00,
            session_duration int(11) DEFAULT 15,
            booking_fee decimal(10,2) DEFAULT 0.00,
            status varchar(50) DEFAULT 'pending',
            payment_status varchar(50) DEFAULT 'unpaid',
            payment_method varchar(50) DEFAULT NULL,
            payment_id varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY booking_date (booking_date),
            KEY booking_time (booking_time),
            KEY status (status)
        ) $charset_collate;";
        
        // Check and add missing columns for existing tables
        $missing_columns = array(
            'event_id' => "ALTER TABLE $bookings_table ADD COLUMN event_id bigint(20) NOT NULL DEFAULT 0 AFTER id",
            'booking_fee' => "ALTER TABLE $bookings_table ADD COLUMN booking_fee decimal(10,2) DEFAULT 0.00 AFTER donation_amount",
            'payment_status' => "ALTER TABLE $bookings_table ADD COLUMN payment_status varchar(50) DEFAULT 'unpaid' AFTER status",
            'payment_method' => "ALTER TABLE $bookings_table ADD COLUMN payment_method varchar(50) DEFAULT NULL AFTER payment_status",
            'payment_id' => "ALTER TABLE $bookings_table ADD COLUMN payment_id varchar(255) DEFAULT NULL AFTER payment_method"
        );
        
        foreach ($missing_columns as $column => $alter_sql) {
            $column_exists = $wpdb->query($wpdb->prepare("SHOW COLUMNS FROM $bookings_table LIKE %s", $column));
            if ($column_exists === 0) {
                $wpdb->query($alter_sql);
            }
        }
        
        // Sessions table (for tracking actual session conduct)
        $sessions_table = $wpdb->prefix . 'trm_sessions';
        $sql_sessions = "CREATE TABLE IF NOT EXISTS $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            session_start datetime DEFAULT NULL,
            session_end datetime DEFAULT NULL,
            actual_duration int(11) DEFAULT 0,
            counselor_notes text DEFAULT NULL,
            session_status varchar(50) DEFAULT 'scheduled',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id)
        ) $charset_collate;";
        
        // Donations table
        $donations_table = $wpdb->prefix . 'trm_donations';
        $sql_donations = "CREATE TABLE IF NOT EXISTS $donations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) DEFAULT NULL,
            donor_name varchar(255) NOT NULL,
            donor_email varchar(255) DEFAULT NULL,
            donor_phone varchar(50) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            donation_type varchar(50) DEFAULT 'session',
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Settings table
        $settings_table = $wpdb->prefix . 'trm_settings';
        $sql_settings = "CREATE TABLE IF NOT EXISTS $settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_bookings);
        dbDelta($sql_sessions);
        dbDelta($sql_donations);
        dbDelta($sql_settings);
        
        // Insert default settings
        self::insert_default_settings();
    }
    
    private static function insert_default_settings() {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'trm_settings';
        
        $default_settings = array(
            'session_duration_member' => '30',
            'session_duration_nonmember_paid' => '30',
            'session_duration_nonmember_free' => '15',
            'buffer_time' => '15',
            'donation_amounts' => json_encode(array(30, 50, 100)),
            'working_days' => json_encode(array(1, 2, 3, 4, 5, 6)), // Monday to Saturday
            'working_hours_start' => '09:00',
            'working_hours_end' => '17:00',
            'allow_weekend_booking' => '1',
            'payment_gateway' => 'stripe',
            'enable_stripe' => '1',
            'enable_paypal' => '1',
            'enable_offline' => '1',
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'paypal_client_id' => '',
            'paypal_secret' => '',
            'offline_payment_instructions' => 'Please send payment details for verification.',
            'notification_email' => get_option('admin_email'),
            'enable_email_notifications' => '1',
            'enable_sms_notifications' => '0'
        );
        
        foreach ($default_settings as $key => $value) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $settings_table WHERE setting_key = %s",
                $key
            ));
            
            if (!$existing) {
                $wpdb->insert($settings_table, array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ));
            }
        }
    }
    
    public static function get_setting($key, $default = '') {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'trm_settings';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
            $key
        ));
        
        return $value !== null ? $value : $default;
    }
    
    public static function update_setting($key, $value) {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'trm_settings';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $settings_table WHERE setting_key = %s",
            $key
        ));
        
        if ($existing) {
            $wpdb->update(
                $settings_table,
                array('setting_value' => $value),
                array('setting_key' => $key)
            );
        } else {
            $wpdb->insert($settings_table, array(
                'setting_key' => $key,
                'setting_value' => $value
            ));
        }
    }
}
