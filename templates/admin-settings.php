<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Trinity Revival Ministry - Settings</h1>
    
    <div style="margin-bottom: 20px;">
        <form method="post" action="" style="display: inline;">
            <?php wp_nonce_field('trm_update_check_nonce'); ?>
            <button type="submit" name="trm_check_updates" class="button button-secondary" style="margin-right: 10px;">
                🔄 Check for Updates
            </button>
            <span style="color: #666; font-size: 12px;">Current version: <?php echo TRM_COUNSELING_VERSION; ?></span>
        </form>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('trm_settings_nonce'); ?>
        
        <h2>Session Duration Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="session_duration_member">Member Session Duration (minutes)</label></th>
                <td>
                    <input type="number" id="session_duration_member" name="session_duration_member" 
                           value="<?php echo TRM_Database::get_setting('session_duration_member', '30'); ?>" min="15" max="60">
                </td>
            </tr>
            <tr>
                <th><label for="session_duration_nonmember_paid">Non-Member (With Donation) Duration (minutes)</label></th>
                <td>
                    <input type="number" id="session_duration_nonmember_paid" name="session_duration_nonmember_paid" 
                           value="<?php echo TRM_Database::get_setting('session_duration_nonmember_paid', '30'); ?>" min="15" max="60">
                </td>
            </tr>
            <tr>
                <th><label for="session_duration_nonmember_free">Non-Member (Without Donation) Duration (minutes)</label></th>
                <td>
                    <input type="number" id="session_duration_nonmember_free" name="session_duration_nonmember_free" 
                           value="<?php echo TRM_Database::get_setting('session_duration_nonmember_free', '15'); ?>" min="15" max="60">
                </td>
            </tr>
            <tr>
                <th><label for="buffer_time">Buffer Time Between Sessions (minutes)</label></th>
                <td>
                    <input type="number" id="buffer_time" name="buffer_time" 
                           value="<?php echo TRM_Database::get_setting('buffer_time', '15'); ?>" min="5" max="30">
                </td>
            </tr>
        </table>
        
        <h2>Working Hours</h2>
        <table class="form-table">
            <tr>
                <th><label for="working_hours_start">Start Time</label></th>
                <td>
                    <input type="time" id="working_hours_start" name="working_hours_start" 
                           value="<?php echo TRM_Database::get_setting('working_hours_start', '09:00'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="working_hours_end">End Time</label></th>
                <td>
                    <input type="time" id="working_hours_end" name="working_hours_end" 
                           value="<?php echo TRM_Database::get_setting('working_hours_end', '17:00'); ?>">
                </td>
            </tr>
        </table>
        
        <h2>Donation Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="donation_amounts">Donation Amount Options (comma-separated)</label></th>
                <td>
                    <?php
                    $amounts = json_decode(TRM_Database::get_setting('donation_amounts', '[30,50,100]'), true);
                    ?>
                    <input type="text" id="donation_amounts" name="donation_amounts" 
                           value="<?php echo implode(',', $amounts); ?>" class="regular-text">
                    <p class="description">Enter amounts separated by commas (e.g., 30,50,100)</p>
                </td>
            </tr>
        </table>
        
        <h2>Payment Gateway Settings</h2>
        <table class="form-table">
            <tr>
                <th colspan="2">Enable Payment Methods</th>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="enable_stripe" value="1" <?php checked(TRM_Database::get_setting('enable_stripe', '1'), '1'); ?>>
                        Enable Stripe (Credit Card)
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="enable_paypal" value="1" <?php checked(TRM_Database::get_setting('enable_paypal', '1'), '1'); ?>>
                        Enable PayPal
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="enable_offline" value="1" <?php checked(TRM_Database::get_setting('enable_offline', '1'), '1'); ?>>
                        Enable Offline Payment (Cash/Check/Bank Transfer)
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="payment_gateway">Default Payment Gateway</label></th>
                <td>
                    <select id="payment_gateway" name="payment_gateway">
                        <option value="stripe" <?php selected(TRM_Database::get_setting('payment_gateway'), 'stripe'); ?>>Stripe</option>
                        <option value="paypal" <?php selected(TRM_Database::get_setting('payment_gateway'), 'paypal'); ?>>PayPal</option>
                        <option value="offline" <?php selected(TRM_Database::get_setting('payment_gateway'), 'offline'); ?>>Offline</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th colspan="2">Stripe Settings</th>
            </tr>
            <tr>
                <th><label for="stripe_public_key">Stripe Public Key</label></th>
                <td>
                    <input type="text" id="stripe_public_key" name="stripe_public_key" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('stripe_public_key', '')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="stripe_secret_key">Stripe Secret Key</label></th>
                <td>
                    <input type="password" id="stripe_secret_key" name="stripe_secret_key" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('stripe_secret_key', '')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th colspan="2">PayPal Settings</th>
            </tr>
            <tr>
                <th><label for="paypal_client_id">PayPal Client ID</label></th>
                <td>
                    <input type="text" id="paypal_client_id" name="paypal_client_id" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('paypal_client_id', '')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="paypal_secret">PayPal Secret</label></th>
                <td>
                    <input type="password" id="paypal_secret" name="paypal_secret" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('paypal_secret', '')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th colspan="2">Offline Payment Settings</th>
            </tr>
            <tr>
                <th><label for="offline_payment_instructions">Payment Instructions</label></th>
                <td>
                    <textarea id="offline_payment_instructions" name="offline_payment_instructions" class="regular-text" rows="4"><?php echo esc_textarea(TRM_Database::get_setting('offline_payment_instructions', 'Please send payment details for verification.')); ?></textarea>
                    <p class="description">Instructions to be displayed to users for offline payments</p>
                </td>
            </tr>
        </table>
        
        <h2>Notification Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="notification_email">Notification Email</label></th>
                <td>
                    <input type="email" id="notification_email" name="notification_email" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('notification_email', get_option('admin_email'))); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <h2>Booking Form - Color Customization</h2>
        <p>Customize the colors of available and selected time slots in the booking form.</p>
        <table class="form-table">
            <tr>
                <th><label for="trm_slot_bg_color">Available Slot Background Color</label></th>
                <td>
                    <input type="color" id="trm_slot_bg_color" name="trm_slot_bg_color" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('trm_slot_bg_color', '#f5f5f5')); ?>">
                    <p class="description">Color of available time slot buttons</p>
                </td>
            </tr>
            <tr>
                <th><label for="trm_slot_text_color">Available Slot Text Color</label></th>
                <td>
                    <input type="color" id="trm_slot_text_color" name="trm_slot_text_color" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('trm_slot_text_color', '#333333')); ?>">
                    <p class="description">Text color of available time slot buttons</p>
                </td>
            </tr>
            <tr>
                <th><label for="trm_slot_selected_bg_color">Selected Slot Background Color</label></th>
                <td>
                    <input type="color" id="trm_slot_selected_bg_color" name="trm_slot_selected_bg_color" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('trm_slot_selected_bg_color', '#28a745')); ?>">
                    <p class="description">Color of selected time slot button</p>
                </td>
            </tr>
            <tr>
                <th><label for="trm_slot_selected_text_color">Selected Slot Text Color</label></th>
                <td>
                    <input type="color" id="trm_slot_selected_text_color" name="trm_slot_selected_text_color" 
                           value="<?php echo esc_attr(TRM_Database::get_setting('trm_slot_selected_text_color', '#ffffff')); ?>">
                    <p class="description">Text color of selected time slot button</p>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
            <h3>Color Preview</h3>
            <div style="margin-bottom: 20px;">
                <p><strong>Available Slots:</strong></p>
                <button style="background-color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_bg_color', '#f5f5f5')); ?>; color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_text_color', '#333333')); ?>; padding: 8px 12px; border: 1px solid #ccc; margin: 5px; cursor: pointer; border-radius: 4px;">09:00</button>
                <button style="background-color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_bg_color', '#f5f5f5')); ?>; color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_text_color', '#333333')); ?>; padding: 8px 12px; border: 1px solid #ccc; margin: 5px; cursor: pointer; border-radius: 4px;">10:00</button>
                <button style="background-color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_bg_color', '#f5f5f5')); ?>; color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_text_color', '#333333')); ?>; padding: 8px 12px; border: 1px solid #ccc; margin: 5px; cursor: pointer; border-radius: 4px;">11:00</button>
            </div>
            <div>
                <p><strong>Selected Slot:</strong></p>
                <button style="background-color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_selected_bg_color', '#28a745')); ?>; color: <?php echo esc_attr(TRM_Database::get_setting('trm_slot_selected_text_color', '#ffffff')); ?>; padding: 8px 12px; border: none; margin: 5px; cursor: pointer; border-radius: 4px;">10:30 (Selected)</button>
            </div>
        </div>
        
        <?php submit_button('Save Settings', 'primary', 'trm_save_settings'); ?>
    </form>
</div>
