<?php
/**
 * TRM AJAX Handler
 * Handles all AJAX requests
 */

class TRM_Ajax {

    public function __construct() {
        // Public AJAX actions
        add_action('wp_ajax_trm_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_nopriv_trm_get_available_slots', array($this, 'get_available_slots'));
        
        add_action('wp_ajax_trm_get_events', array($this, 'get_events'));
        add_action('wp_ajax_nopriv_trm_get_events', array($this, 'get_events'));

        add_action('wp_ajax_trm_create_booking', array($this, 'create_booking'));
        add_action('wp_ajax_nopriv_trm_create_booking', array($this, 'create_booking'));

        add_action('wp_ajax_trm_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_trm_process_payment', array($this, 'process_payment'));
        
        add_action('wp_ajax_trm_get_payment_methods', array($this, 'get_payment_methods'));
        add_action('wp_ajax_nopriv_trm_get_payment_methods', array($this, 'get_payment_methods'));

        add_action('wp_ajax_trm_extend_session', array($this, 'extend_session'));
        add_action('wp_ajax_nopriv_trm_extend_session', array($this, 'extend_session'));
        
        add_action('wp_ajax_trm_get_donation_options', array($this, 'get_donation_options'));
        add_action('wp_ajax_nopriv_trm_get_donation_options', array($this, 'get_donation_options'));
        
        add_action('wp_ajax_trm_create_stripe_checkout', array($this, 'create_stripe_checkout'));
        add_action('wp_ajax_nopriv_trm_create_stripe_checkout', array($this, 'create_stripe_checkout'));
    }

    public function get_available_slots() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');

        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

        if (empty($date)) {
            wp_send_json_error(array('message' => 'Date is required'));
        }

        $slots = TRM_Booking::get_available_slots($date, $event_id);

        wp_send_json_success(array('slots' => $slots));
    }
    
    public function get_events() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');
        
        $events = TRM_Events::get_available_events();
        wp_send_json_success(array('events' => $events));
    }

    public function create_booking() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');

        error_log('TRM AJAX create_booking called. POST data: ' . print_r($_POST, true));

        $data = array(
            'event_id' => isset($_POST['event_id']) ? intval($_POST['event_id']) : 0,
            'full_name' => isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'booking_date' => isset($_POST['booking_date']) ? sanitize_text_field($_POST['booking_date']) : '',
            'booking_time' => isset($_POST['booking_time']) ? sanitize_text_field($_POST['booking_time']) : '',
            'is_member' => isset($_POST['is_member']) ? intval($_POST['is_member']) : 0,
            'donation_amount' => isset($_POST['donation_amount']) ? floatval($_POST['donation_amount']) : 0,
            'payment_method' => isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : ''
        );

        error_log('TRM AJAX Processed data: ' . print_r($data, true));

        $result = TRM_Booking::create_booking($data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function process_payment() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

        // Process payment through selected gateway
        $result = TRM_Payment::process_payment($booking_id, $amount, $payment_method, $_POST);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    public function get_payment_methods() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');
        
        $payment_methods = TRM_Payment::get_enabled_payment_methods();
        
        // Convert to array of objects for JavaScript
        $methods = array();
        foreach ($payment_methods as $id => $title) {
            $methods[] = array(
                'id' => $id,
                'title' => $title
            );
        }
        
        wp_send_json_success(array('methods' => $methods));
    }

    public function get_donation_options() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');
        
        $options = array(5, 10, 25, 50, 100);
        wp_send_json_success(array('options' => $options));
    }
    
    public function extend_session() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $donation_amount = isset($_POST['donation_amount']) ? floatval($_POST['donation_amount']) : 0;

        $result = TRM_Booking::extend_session($booking_id, $donation_amount);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function create_stripe_checkout() {
        check_ajax_referer('trm-counseling-nonce', 'nonce');

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

        if (!$booking_id || !$amount) {
            wp_send_json_error(array('message' => 'Missing booking ID or amount'));
        }

        // Get Stripe settings
        $stripe_secret_key = TRM_Database::get_setting('stripe_secret_key', '');
        
        if (empty($stripe_secret_key)) {
            wp_send_json_error(array('message' => 'Stripe secret key not configured'));
        }

        // Get booking details
        $booking = TRM_Booking::get_booking($booking_id);
        if (!$booking) {
            wp_send_json_error(array('message' => 'Booking not found'));
        }

        // Get event details
        $event = TRM_Events::get_event($booking->event_id);
        $event_title = $event ? $event['title'] : 'Counseling Session';

        // Create Stripe Checkout session using direct API call
        $success_url = home_url('/?trm_payment=success&booking_id=' . $booking_id);
        $cancel_url = home_url('/?trm_payment=cancelled&booking_id=' . $booking_id);

        $checkout_data = array(
            'payment_method_types' => array('card'),
            'line_items' => array(
                array(
                    'price_data' => array(
                        'currency' => 'usd',
                        'product_data' => array(
                            'name' => $event_title,
                            'description' => 'Trinity Revival Ministry - Counseling Session'
                        ),
                        'unit_amount' => intval($amount * 100) // Convert to cents
                    ),
                    'quantity' => 1
                )
            ),
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => array(
                'booking_id' => $booking_id
            )
        );

        // Make API call to Stripe
        $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $stripe_secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($checkout_data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to connect to Stripe: ' . $response->get_error_message()));
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);
        $data = json_decode($body, true);

        if ($status !== 200 || !isset($data['url'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Stripe API error';
            wp_send_json_error(array('message' => 'Stripe error: ' . $error_message));
        }

        // Return the Stripe Checkout URL for redirect
        wp_send_json_success(array(
            'checkout_url' => $data['url'],
            'session_id' => $data['id']
        ));
    }
}
