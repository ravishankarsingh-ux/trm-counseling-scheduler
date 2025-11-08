<?php
/**
 * TRM Payment Handler
 * Processes payments through various gateways
 */

class TRM_Payment {

    /**
     * Process payment through selected gateway
     */
    public static function process_payment($booking_id, $amount, $payment_method, $payment_data) {
        global $wpdb;

        // Validate payment method is enabled
        switch ($payment_method) {
            case 'stripe':
                if (TRM_Database::get_setting('enable_stripe', '1') !== '1') {
                    return array('success' => false, 'message' => 'Stripe is not enabled');
                }
                return self::process_stripe_payment($booking_id, $amount, $payment_data);
            case 'paypal':
                if (TRM_Database::get_setting('enable_paypal', '1') !== '1') {
                    return array('success' => false, 'message' => 'PayPal is not enabled');
                }
                return self::process_paypal_payment($booking_id, $amount, $payment_data);
            case 'offline':
            case 'cash':
                if (TRM_Database::get_setting('enable_offline', '1') !== '1') {
                    return array('success' => false, 'message' => 'Offline payment is not enabled');
                }
                return self::process_offline_payment($booking_id, $amount, $payment_data);
            default:
                return array('success' => false, 'message' => 'Invalid payment method');
        }
    }

    /**
     * Process Stripe payment
     */
    private static function process_stripe_payment($booking_id, $amount, $payment_data) {
        $stripe_secret = TRM_Database::get_setting('stripe_secret_key', '');

        if (empty($stripe_secret)) {
            return array('success' => false, 'message' => 'Stripe is not configured');
        }

        // Include Stripe PHP library (you'll need to include this via Composer or manually)
        // For demonstration, this is a placeholder

        try {
            // Stripe::setApiKey($stripe_secret);
            // $charge = \Stripe\Charge::create([
            //     'amount' => $amount * 100, // Convert to cents
            //     'currency' => 'usd',
            //     'source' => $payment_data['stripe_token'],
            //     'description' => 'Trinity Revival Ministry - Counseling Session Donation'
            // ]);

            // Update booking payment status
            global $wpdb;
            $bookings_table = $wpdb->prefix . 'trm_bookings';

            $wpdb->update(
                $bookings_table,
                array(
                    'payment_status' => 'completed',
                    'payment_id' => 'stripe_' . time(), // Replace with actual charge ID
                    'status' => 'confirmed'
                ),
                array('id' => $booking_id)
            );

            // Update donation status
            $donations_table = $wpdb->prefix . 'trm_donations';
            $wpdb->update(
                $donations_table,
                array(
                    'status' => 'completed',
                    'transaction_id' => 'stripe_' . time(),
                    'payment_method' => 'stripe'
                ),
                array('booking_id' => $booking_id)
            );

            return array(
                'success' => true, 
                'message' => 'Payment processed successfully',
                'transaction_id' => 'stripe_' . time()
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Process PayPal payment
     */
    private static function process_paypal_payment($booking_id, $amount, $payment_data) {
        $paypal_client_id = TRM_Database::get_setting('paypal_client_id', '');

        if (empty($paypal_client_id)) {
            return array('success' => false, 'message' => 'PayPal is not configured');
        }

        // PayPal integration would go here
        // This is a placeholder for the actual PayPal SDK integration

        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';

        $wpdb->update(
            $bookings_table,
            array(
                'payment_status' => 'completed',
                'payment_id' => 'paypal_' . time(),
                'status' => 'confirmed'
            ),
            array('id' => $booking_id)
        );

        return array(
            'success' => true, 
            'message' => 'Payment processed successfully',
            'transaction_id' => 'paypal_' . time()
        );
    }

    /**
     * Process offline payment (cash/check/bank transfer)
     */
    private static function process_offline_payment($booking_id, $amount, $payment_data) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        $donations_table = $wpdb->prefix . 'trm_donations';

        // Get payment instructions
        $instructions = TRM_Database::get_setting('offline_payment_instructions', 'Please send payment details for verification.');
        
        // Generate a unique payment reference
        $payment_reference = 'offline_' . time() . '_' . $booking_id;

        // Update booking payment status to 'pending_offline'
        $wpdb->update(
            $bookings_table,
            array(
                'payment_status' => 'pending_offline',
                'payment_id' => $payment_reference,
                'payment_method' => 'offline'
            ),
            array('id' => $booking_id)
        );

        // Update or create donation record if applicable
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE id = %d",
            $booking_id
        ));
        
        if ($booking && $booking->donation_amount > 0) {
            $wpdb->update(
                $donations_table,
                array(
                    'status' => 'pending_offline',
                    'transaction_id' => $payment_reference,
                    'payment_method' => 'offline'
                ),
                array('booking_id' => $booking_id)
            );
        }

        return array(
            'success' => true,
            'message' => 'Offline payment initiated. Please follow the payment instructions.',
            'payment_reference' => $payment_reference,
            'instructions' => $instructions
        );
    }

    /**
     * Get enabled payment methods
     */
    public static function get_enabled_payment_methods() {
        $methods = array();
        
        if (TRM_Database::get_setting('enable_stripe', '1') === '1') {
            $methods['stripe'] = 'Credit Card (Stripe)';
        }
        
        if (TRM_Database::get_setting('enable_paypal', '1') === '1') {
            $methods['paypal'] = 'PayPal';
        }
        
        if (TRM_Database::get_setting('enable_offline', '1') === '1') {
            $methods['offline'] = 'Cash/Check/Bank Transfer';
        }
        
        return $methods;
    }

    /**
     * Get donation amount options
     */
    public static function get_donation_options() {
        $amounts = TRM_Database::get_setting('donation_amounts', json_encode(array(30, 50, 100)));
        return json_decode($amounts, true);
    }
}
