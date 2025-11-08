<?php
/**
 * Simplified Booking Form Template - Single Page with Dividers
 * Shortcode: [trm_booking_form]
 */

if (!defined('ABSPATH')) exit;
?>

<div id="trm-booking-wrapper" class="trm-booking-wrapper-simple">
    <div class="trm-booking-container-simple">
        <h2>Schedule Your Counseling Session</h2>
        <p class="trm-description">Trinity Revival Ministry - Counseling & Scheduling Framework 2026</p>
        
        <form id="trm-booking-form" class="trm-booking-form-simple">
            
            <!-- Section 1: Select Event -->
            <div class="trm-form-section">
                <h3 class="trm-section-title">1. Select an Event</h3>
                
                <div class="trm-form-group">
                    <label for="event_id">Choose an Event:</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">-- Select an Event --</option>
                        <?php
                        // Server-side fallback: preload events so dropdown is never empty
                        if (!isset($event) || !$event) {
                            $events = TRM_Events::get_available_events();
                        } else {
                            $events = array($event);
                        }
                        if (!empty($events)) {
                            foreach ($events as $e) {
                                $id = is_array($e) ? $e['id'] : (isset($e->ID) ? $e->ID : 0);
                                $title = is_array($e) ? $e['title'] : (isset($e->post_title) ? $e->post_title : '');
                                if ($id) {
                                    echo '<option value="' . esc_attr($id) . '">' . esc_html($title) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div id="event-details" class="trm-event-details" style="display: none;">
                    <p id="event-description"></p>
                </div>
            </div>
            
            <hr class="trm-divider">
            
            <!-- Section 2: Select Date & Time -->
            <div class="trm-form-section">
                <h3 class="trm-section-title">2. Select Date & Time</h3>
                
                <div class="trm-form-group">
                    <label for="booking_date">Select Date:</label>
                    <input type="date" id="booking_date" name="booking_date" required>
                </div>
                
                <div class="trm-form-group">
                    <label>Available Time Slots:</label>
                    <div id="time-slots-container" class="time-slots-container">
                        <p class="trm-note">Please select an event and date to view available time slots</p>
                    </div>
                    <input type="hidden" id="booking_time" name="booking_time" value="">
                </div>
            </div>
            
            <hr class="trm-divider">
            
            <!-- Section 3: Your Information -->
            <div class="trm-form-section">
                <h3 class="trm-section-title">3. Your Information</h3>
                
                <div class="trm-form-group">
                    <label for="full_name">Full Name: *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="trm-form-group">
                    <label for="phone">Phone Number: *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="trm-form-group">
                    <label for="email">Email Address: (optional)</label>
                    <input type="email" id="email" name="email">
                </div>
            </div>
            
            <hr class="trm-divider">
            
            <!-- Section 4: Membership Status -->
            <div class="trm-form-section">
                <h3 class="trm-section-title">4. Membership Status</h3>
                
                <p>Are you a member of Trinity Revival Ministry?</p>
                <div class="trm-radio-group">
                    <label class="trm-radio-label">
                        <input type="radio" name="is_member" value="1"> Yes
                    </label>
                    <label class="trm-radio-label">
                        <input type="radio" name="is_member" value="0"> No
                    </label>
                </div>
            </div>
            
            <hr class="trm-divider">
            
            <!-- Section 5: Donation (Conditional) -->
            <div class="trm-form-section" id="donation-section" style="display: none;">
                <h3 class="trm-section-title" id="donation-title">5. Support Our Ministry</h3>
                <p id="donation-message"></p>
                
                <div class="trm-donation-options" id="donation-options">
                    <!-- Populated by JavaScript -->
                </div>
                
                <div class="trm-custom-amount-wrapper" id="custom-amount-wrapper" style="display: none;">
                    <label for="custom_donation">Enter Custom Amount ($):</label>
                    <input type="number" id="custom_donation" placeholder="Enter amount" min="0.01" step="0.01">
                </div>
                
                <input type="hidden" id="donation_amount" name="donation_amount" value="0">
                <input type="hidden" id="payment_method" name="payment_method" value="offline">
            </div>
            
            <hr class="trm-divider">
            
            <!-- Section 6: Payment Method -->
            <div class="trm-form-section" id="payment-section" style="display: none;">
                <h3 class="trm-section-title">6. Payment Method</h3>
                <p>Select how you'd like to pay for your booking fee and donation (if any):</p>
                
                <div class="trm-radio-group" id="payment-methods">
                    <!-- Payment methods will be populated by JavaScript -->
                </div>
                
                <div id="payment-total" class="trm-payment-total" style="display: none;">
                    <p><strong>Total Amount Due: $<span id="total-amount">0.00</span></strong></p>
                </div>
            </div>
            
            <hr class="trm-divider">
            
            <!-- Section 7: Submit -->
            <div class="trm-form-section">
                <div class="trm-form-actions-simple">
                    <button type="submit" class="trm-btn trm-btn-primary" id="submit-booking">Complete Booking</button>
                </div>
            </div>
            
        </form>
        
        <!-- Loading Indicator -->
        <div id="trm-loading" class="trm-loading" style="display: none;">
            <div class="trm-spinner"></div>
            <p>Processing your booking...</p>
        </div>
        
        <!-- Success Message -->
        <div id="success-message" class="trm-success-message-simple" style="display: none;">
            <h3>✓ Booking Confirmed!</h3>
            <p>Your counseling session has been successfully scheduled.</p>
            <div id="confirmation-details"></div>
            <p class="trm-confidentiality">All counseling sessions are private and pastoral in nature. Information shared will be treated with discretion and used only for spiritual guidance.</p>
        </div>
    </div>
</div>
