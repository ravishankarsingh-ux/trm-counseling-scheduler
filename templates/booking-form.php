<?php
/**
 * Booking Form Template - Simplified Single Page
 * Shortcode: [trm_booking_form]
 */

if (!defined('ABSPATH')) exit;
?>

<div id="trm-booking-wrapper" class="trm-booking-wrapper">
    <div class="trm-booking-container">
        <h2>Schedule Your Counseling Session</h2>
        <p class="trm-description">Trinity Revival Ministry - Counseling & Scheduling Framework 2026</p>
        
        <form id="trm-booking-form" class="trm-booking-form">
            <!-- Step 0: Select Event -->
            <div class="trm-step trm-step-0 active" id="event-selection-step">
                <h3>Step 1: Select an Event</h3>
                <div class="trm-form-group">
                    <label for="event_id">Choose an Event:</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">-- Select an Event --</option>
                    </select>
                    <p class="trm-note" id="event-description"></p>
                </div>
                <button type="button" class="trm-btn trm-btn-next" id="event-next" disabled>Next</button>
            </div>
            
            <!-- Step 1: Select Date & Time -->
            <div class="trm-step trm-step-1" style="display: none;">
                <h3>Step 2: Select Date & Time</h3>
                <div class="trm-form-group">
                    <label for="booking_date">Select Date:</label>
                    <input type="date" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="trm-form-group">
                    <label>Available Time Slots:</label>
                    <div id="time-slots-container" class="time-slots-container">
                        <p class="trm-note">Please select a date to view available time slots</p>
                    </div>
                </div>
                <button type="button" class="trm-btn trm-btn-next" id="step1-next" disabled>Next</button>
            </div>
            
            <!-- Step 2: Enter Details -->
            <div class="trm-step trm-step-2">
                <h3>Step 2: Your Information</h3>
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
                <div class="trm-form-actions">
                    <button type="button" class="trm-btn trm-btn-back">Back</button>
                    <button type="button" class="trm-btn trm-btn-next" id="step2-next">Next</button>
                </div>
            </div>
            
            <!-- Step 3: Membership Confirmation -->
            <div class="trm-step trm-step-3">
                <h3>Step 3: Membership Status</h3>
                <div class="trm-membership-question">
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
                <div class="trm-form-actions">
                    <button type="button" class="trm-btn trm-btn-back">Back</button>
                </div>
            </div>
            
            <!-- Step 4: Member Donation (if member) -->
            <div class="trm-step trm-step-4-member">
                <h3>Support Our Ministry</h3>
                <div class="trm-donation-message">
                    <p>Trinity Revival Ministry is in need of financial support. Please consider making a small donation to help the ministry.</p>
                </div>
                <div class="trm-donation-options">
                    <?php
                    $donation_options = TRM_Payment::get_donation_options();
                    foreach ($donation_options as $amount) {
                        echo '<button type="button" class="trm-donation-btn" data-amount="' . $amount . '">$' . $amount . '</button>';
                    }
                    ?>
                    <button type="button" class="trm-donation-btn" data-amount="custom">Custom Amount</button>
                </div>
                <div class="trm-custom-amount-wrapper" style="display: none;">
                    <input type="number" id="custom_donation" placeholder="Enter amount" min="1" step="0.01">
                </div>
                <input type="hidden" id="donation_amount" name="donation_amount" value="0">
                <div class="trm-form-actions">
                    <button type="button" class="trm-btn trm-btn-secondary" id="skip-donation-member">No Thanks</button>
                    <button type="button" class="trm-btn trm-btn-primary" id="continue-donation-member" disabled>Continue</button>
                </div>
            </div>
            
            <!-- Step 5: Non-Member Donation -->
            <div class="trm-step trm-step-4-nonmember">
                <h3>Prophetic Consultative Services</h3>
                <div class="trm-donation-message">
                    <p>We are pleased to offer you our prophetic consultative services. Please plant a seed to assist our ministry's growth.</p>
                </div>
                <div class="trm-donation-options">
                    <?php
                    foreach ($donation_options as $amount) {
                        echo '<button type="button" class="trm-donation-btn-nm" data-amount="' . $amount . '">$' . $amount . '</button>';
                    }
                    ?>
                    <button type="button" class="trm-donation-btn-nm" data-amount="custom">Custom Amount</button>
                </div>
                <div class="trm-custom-amount-wrapper-nm" style="display: none;">
                    <input type="number" id="custom_donation_nm" placeholder="Enter amount" min="1" step="0.01">
                </div>
                <div class="trm-form-actions">
                    <button type="button" class="trm-btn trm-btn-secondary" id="skip-donation-nonmember">No Thanks</button>
                    <button type="button" class="trm-btn trm-btn-primary" id="continue-donation-nonmember" disabled>Yes, Plant a Seed</button>
                </div>
            </div>
            
            <!-- Step 6: 15-Minute Warning (Non-member, no donation) -->
            <div class="trm-step trm-step-5-limited">
                <h3>Session Duration Notice</h3>
                <div class="trm-warning-message">
                    <p>You will be scheduled for a <strong>15-minute session</strong>. After 15 minutes, your session will end.</p>
                    <p>If you wish for additional time, please plant a seed to support the ministry's work.</p>
                </div>
                <div class="trm-form-actions">
                    <button type="button" class="trm-btn trm-btn-primary" id="add-seed-later">Add a Seed</button>
                    <button type="button" class="trm-btn trm-btn-secondary" id="skip-seed-final">Skip - Book 15 Minutes</button>
                </div>
            </div>
            
            <!-- Step 7: Confirmation -->
            <div class="trm-step trm-step-final">
                <h3>Confirm Your Booking</h3>
                <div id="booking-summary" class="trm-booking-summary">
                    <!-- Summary will be populated by JavaScript -->
                </div>
                <div class="trm-form-actions">
                    <button type="button" class="trm-btn trm-btn-back">Back</button>
                    <button type="submit" class="trm-btn trm-btn-primary" id="confirm-booking">Confirm Booking</button>
                </div>
            </div>
            
            <!-- Success Message -->
            <div class="trm-step trm-step-success">
                <div class="trm-success-message">
                    <h3>✓ Booking Confirmed!</h3>
                    <p>Your counseling session has been successfully scheduled.</p>
                    <div id="confirmation-details"></div>
                    <p class="trm-confidentiality">All counseling sessions are private and pastoral in nature. Information shared will be treated with discretion and used only for spiritual guidance.</p>
                </div>
            </div>
        </form>
        
        <div id="trm-loading" class="trm-loading" style="display: none;">
            <div class="trm-spinner"></div>
        </div>
    </div>
</div>
