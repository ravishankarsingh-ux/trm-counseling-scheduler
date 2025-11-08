<?php
/**
 * TRM Shortcodes
 * Handles all shortcode rendering
 */

class TRM_Shortcodes {

    public function __construct() {
        add_shortcode('trm_booking_form', array($this, 'render_booking_form'));
        add_shortcode('trm_my_bookings', array($this, 'render_my_bookings'));
    }

    /**
     * Render the main booking form (simplified single-page)
     * Usage: [trm_booking_form event_id="123"]
     */
    public function render_booking_form($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0
        ), $atts, 'trm_booking_form');
        
        $event_id = intval($atts['event_id']);
        $event = null;
        
        if ($event_id) {
            $event = TRM_Events::get_event($event_id);
        }
        
        ob_start();
        include TRM_COUNSELING_PLUGIN_DIR . 'templates/booking-form-simple.php';
        return ob_get_clean();
    }

    /**
     * Render user's bookings
     * Usage: [trm_my_bookings]
     */
    public function render_my_bookings($atts) {
        ob_start();
        include TRM_COUNSELING_PLUGIN_DIR . 'templates/my-bookings.php';
        return ob_get_clean();
    }
}
