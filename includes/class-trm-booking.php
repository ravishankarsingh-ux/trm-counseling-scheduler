<?php
/**
 * TRM Booking Handler
 * Manages booking operations and availability
 */

class TRM_Booking {
    
    public function __construct() {
        // Constructor can be used for initialization if needed
    }
    
    /**
     * Get available time slots for a given event and date
     */
    public static function get_available_slots($date, $event_id = null) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        
        // Get event details if provided
        if ($event_id) {
            $event = TRM_Events::get_event($event_id);
            if (!$event) {
                return array('error' => 'Event not found');
            }
            $start_time = $event['start_time'];
            $end_time = $event['end_time'];
            $buffer_time = intval($event['buffer_time']);
            $session_duration = intval($event['session_duration']);
            $allow_multiple = $event['allow_multiple_bookings'];
            $capacity = intval($event['capacity']);
        } else {
            // Fallback to global settings
            $start_time = TRM_Database::get_setting('working_hours_start', '09:00');
            $end_time = TRM_Database::get_setting('working_hours_end', '17:00');
            $buffer_time = intval(TRM_Database::get_setting('buffer_time', '15'));
            $session_duration = 30;
            $allow_multiple = 'no';
            $capacity = 1;
        }
        
        // Generate all possible slots
        $slots = array();
        $current = strtotime($start_time);
        $end = strtotime($end_time);
        $slot_duration = $session_duration + $buffer_time;
        
        while ($current < $end) {
            $slots[] = date('H:i', $current);
            $current = strtotime("+{$slot_duration} minutes", $current);
        }
        
        // Get booked slots and their counts
        $where = "WHERE booking_date = %s AND status IN ('confirmed', 'pending')";
        $params = array($date);
        
        if ($event_id) {
            $where .= " AND event_id = %d";
            $params[] = $event_id;
        }
        
        $booked_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_time, COUNT(*) as count FROM $bookings_table 
            $where
            GROUP BY booking_time",
            $params
        ));
        
        $booked_times = array();
        foreach ($booked_slots as $slot) {
            $booked_times[$slot->booking_time] = $slot->count;
        }
        
        // Filter available slots based on capacity
        $available = array();
        foreach ($slots as $slot) {
            $booked_count = isset($booked_times[$slot]) ? $booked_times[$slot] : 0;
            
            // If single booking only, slot is fully booked if count >= 1
            // If multiple bookings allowed, slot available if count < capacity
            if ($allow_multiple === 'yes') {
                if ($booked_count < $capacity) {
                    $available[] = $slot;
                }
            } else {
                if ($booked_count === 0) {
                    $available[] = $slot;
                }
            }
        }
        
        return $available;
    }
    
    /**
     * Create a new booking
     */
    public static function create_booking($data) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        
        // Log incoming data for debugging
        error_log('TRM Booking Data Received: ' . print_r($data, true));
        
        // Event ID is required (check this FIRST)
        $event_id = isset($data['event_id']) ? intval($data['event_id']) : 0;
        if (!$event_id) {
            error_log('TRM Booking Error: Event ID missing or invalid. Data: ' . print_r($data, true));
            return array('success' => false, 'message' => 'Event is required');
        }
        
        // Validate required fields
        if (empty($data['full_name']) || empty($data['phone']) || 
            empty($data['booking_date']) || empty($data['booking_time'])) {
            error_log('TRM Booking Error: Missing required fields. Data: ' . print_r($data, true));
            return array('success' => false, 'message' => 'Missing required fields');
        }
        
        // Get event details
        $event = TRM_Events::get_event($event_id);
        if (!$event) {
            return array('success' => false, 'message' => 'Event not found');
        }
        
        // Check capacity and booking limits
        // Format booking_time for comparison (ensure HH:MM format for consistent comparison)
        $check_time = $data['booking_time'];
        if (strlen($check_time) == 5) { // HH:MM format
            $check_time .= ':00'; // Add seconds
        }
        
        $booked_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
            WHERE event_id = %d 
            AND booking_date = %s 
            AND booking_time LIKE %s 
            AND status IN ('confirmed', 'pending')",
            $event_id,
            $data['booking_date'],
            $check_time . '%'
        ));
        
        $capacity = intval($event['capacity']);
        $allow_multiple = $event['allow_multiple_bookings'];
        
        // Check availability based on settings
        if ($allow_multiple === 'yes') {
            if ($booked_count >= $capacity) {
                return array('success' => false, 'message' => 'This time slot is fully booked');
            }
        } else {
            if ($booked_count > 0) {
                return array('success' => false, 'message' => 'This time slot is already booked');
            }
        }
        
        // Determine session duration
        $is_member = isset($data['is_member']) ? intval($data['is_member']) : 0;
        $donation_amount = isset($data['donation_amount']) ? floatval($data['donation_amount']) : 0;
        $session_duration = intval($event['session_duration']);
        $booking_fee = isset($data['booking_fee']) ? floatval($data['booking_fee']) : floatval($event['booking_fee']);
        
        // For non-members: 15 minutes base, 30 minutes if they donate
        if (!$is_member) {
            if ($donation_amount > 0) {
                $session_duration = 30;  // Extend to 30 minutes with donation
            } else {
                $session_duration = 15;  // Base 15 minutes for non-members without donation
            }
        }
        
        // Determine payment status based on event settings
        $require_payment = $event['require_payment'];
        if ($require_payment === 'yes' && $booking_fee > 0) {
            $payment_status = 'pending';
        } elseif ($donation_amount > 0) {
            $payment_status = 'pending';
        } else {
            $payment_status = 'completed';
        }
        
        // Insert booking
        // Ensure booking_time has seconds if not present
        $booking_time = $data['booking_time'];
        if (strlen($booking_time) == 5) { // HH:MM format
            $booking_time .= ':00'; // Add seconds
        }
        
        $insert_data = array(
            'event_id' => $event_id,
            'full_name' => sanitize_text_field($data['full_name']),
            'phone' => sanitize_text_field($data['phone']),
            'email' => isset($data['email']) ? sanitize_email($data['email']) : null,
            'booking_date' => $data['booking_date'],
            'booking_time' => $booking_time,
            'is_member' => $is_member,
            'donation_amount' => $donation_amount,
            'booking_fee' => $booking_fee,
            'session_duration' => $session_duration,
            'status' => 'pending',
            'payment_status' => $payment_status,
            'payment_method' => isset($data['payment_method']) ? sanitize_text_field($data['payment_method']) : null
        );
        
        $inserted = $wpdb->insert($bookings_table, $insert_data);
        
        if (!$inserted) {
            error_log('TRM Booking Insert Error: ' . $wpdb->last_error);
            return array('success' => false, 'message' => 'Database error: ' . $wpdb->last_error);
        }
        
        if ($inserted) {
            $booking_id = $wpdb->insert_id;
            
            // If payment not required or no payment method selected, mark as confirmed
            if ($payment_status === 'completed') {
                $wpdb->update($bookings_table, array('status' => 'confirmed'), array('id' => $booking_id));
            }
            
            // Send confirmation email
            self::send_booking_confirmation($booking_id, $event);
            
            $total_amount = $booking_fee + $donation_amount;
            
            return array(
                'success' => true, 
                'message' => 'Booking created successfully',
                'booking_id' => $booking_id,
                'session_duration' => $session_duration,
                'total_amount' => $total_amount,
                'require_payment' => $payment_status === 'pending'
            );
        }
        
        return array('success' => false, 'message' => 'Failed to create booking');
    }
    
    /**
     * Record a donation
     */
    private static function record_donation($booking_id, $data) {
        global $wpdb;
        $donations_table = $wpdb->prefix . 'trm_donations';
        
        $wpdb->insert($donations_table, array(
            'booking_id' => $booking_id,
            'donor_name' => sanitize_text_field($data['full_name']),
            'donor_email' => isset($data['email']) ? sanitize_email($data['email']) : null,
            'donor_phone' => sanitize_text_field($data['phone']),
            'amount' => floatval($data['donation_amount']),
            'donation_type' => 'session',
            'status' => 'pending'
        ));
    }
    
    /**
     * Extend session duration (for non-members who donate later)
     */
    public static function extend_session($booking_id, $donation_amount) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            return array('success' => false, 'message' => 'Booking not found');
        }
        
        // Update booking
        $wpdb->update(
            $bookings_table,
            array(
                'donation_amount' => floatval($donation_amount),
                'session_duration' => 30,
                'status' => 'confirmed'
            ),
            array('id' => $booking_id)
        );
        
        // Record donation
        self::record_donation($booking_id, array(
            'full_name' => $booking->full_name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'donation_amount' => $donation_amount
        ));
        
        return array(
            'success' => true, 
            'message' => 'Session extended to 30 minutes'
        );
    }
    
    /**
     * Get booking by ID
     */
    public static function get_booking($booking_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'trm_bookings';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE id = %d",
            $booking_id
        ));
    }
    
    /**
     * Send booking confirmation email
     */
    private static function send_booking_confirmation($booking_id, $event = null) {
        $booking = self::get_booking($booking_id);
        
        if (!$booking || empty($booking->email)) {
            return;
        }
        
        // Get event if not provided
        if (!$event && isset($booking->event_id)) {
            $event = TRM_Events::get_event($booking->event_id);
        }
        
        $to = $booking->email;
        $event_title = $event ? $event['title'] : 'Session';
        $subject = 'Trinity Revival Ministry - ' . $event_title . ' Confirmation';
        
        $message = "Dear {$booking->full_name},\n\n";
        $message .= "Your booking for {$event_title} has been confirmed.\n\n";
        $message .= "Booking Details:\n";
        $message .= "Date: " . date('F j, Y', strtotime($booking->booking_date)) . "\n";
        $message .= "Time: " . date('g:i A', strtotime($booking->booking_time)) . "\n";
        $message .= "Duration: {$booking->session_duration} minutes\n";
        
        if ($booking->booking_fee > 0) {
            $message .= "Booking Fee: $" . number_format($booking->booking_fee, 2) . "\n";
        }
        
        if ($booking->donation_amount > 0) {
            $message .= "Donation Amount: $" . number_format($booking->donation_amount, 2) . "\n";
        }
        
        if ($booking->payment_status === 'pending') {
            $message .= "\nPayment Status: Pending\n";
            $message .= "Please complete your payment to confirm the booking.\n";
        }
        
        $message .= "\nAll sessions are private and pastoral in nature. Information shared will be treated with discretion.\n\n";
        $message .= "Trinity Revival Ministry - Counseling & Scheduling Framework 2026";
        
        wp_mail($to, $subject, $message);
        
        // Also notify admin
        $admin_email = TRM_Database::get_setting('notification_email', get_option('admin_email'));
        if ($admin_email) {
            $admin_subject = 'New Booking: ' . $event_title;
            $admin_message = "A new booking has been created.\n\n";
            $admin_message .= "Event: {$event_title}\n";
            $admin_message .= "Name: {$booking->full_name}\n";
            $admin_message .= "Phone: {$booking->phone}\n";
            $admin_message .= "Email: {$booking->email}\n";
            $admin_message .= "Date: " . date('F j, Y', strtotime($booking->booking_date)) . "\n";
            $admin_message .= "Time: " . date('g:i A', strtotime($booking->booking_time)) . "\n";
            $admin_message .= "Member: " . ($booking->is_member ? 'Yes' : 'No') . "\n";
            $admin_message .= "Duration: {$booking->session_duration} minutes\n";
            $admin_message .= "Payment Method: " . ($booking->payment_method ?: 'Not specified') . "\n";
            $admin_message .= "Payment Status: {$booking->payment_status}\n";
            
            wp_mail($admin_email, $admin_subject, $admin_message);
        }
    }
}
