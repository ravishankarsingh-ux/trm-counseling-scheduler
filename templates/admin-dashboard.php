<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Trinity Revival Ministry - Counseling Bookings</h1>
    
    <!-- Filters -->
    <div class="trm-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="trm-counseling">
            
            <label>Status:</label>
            <select name="status">
                <option value="all" <?php selected($status_filter, 'all'); ?>>All</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>Confirmed</option>
                <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
            </select>
            
            <label>Date:</label>
            <input type="date" name="date" value="<?php echo esc_attr($date_filter); ?>">
            
            <button type="submit" class="button">Filter</button>
        </form>
    </div>
    
    <!-- Bookings Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Event</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Date</th>
                <th>Time</th>
                <th>Member</th>
                <th>Duration</th>
                <th>Booking Fee</th>
                <th>Donation</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="14">No bookings found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <?php $event = TRM_Events::get_event($booking->event_id); ?>
                    <tr>
                        <td><?php echo $booking->id; ?></td>
                        <td><?php echo esc_html($booking->full_name); ?></td>
                        <td><?php echo $event ? esc_html($event['title']) : 'Unknown'; ?></td>
                        <td><?php echo esc_html($booking->phone); ?></td>
                        <td><?php echo esc_html($booking->email); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></td>
                        <td><?php echo date('g:i A', strtotime($booking->booking_time)); ?></td>
                        <td><?php echo $booking->is_member ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $booking->session_duration; ?> min</td>
                        <td>$<?php echo number_format($booking->booking_fee, 2); ?></td>
                        <td>$<?php echo number_format($booking->donation_amount, 2); ?></td>
                        <td><?php echo esc_html($booking->payment_method ?: 'None'); ?></td>
                        <td>
                            <span class="trm-status trm-status-<?php echo $booking->status; ?>">
                                <?php echo ucfirst($booking->status); ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('trm_update_status'); ?>
                                <input type="hidden" name="booking_id" value="<?php echo $booking->id; ?>">
                                <select name="new_status">
                                    <option value="pending" <?php selected($booking->status, 'pending'); ?>>Pending</option>
                                    <option value="confirmed" <?php selected($booking->status, 'confirmed'); ?>>Confirmed</option>
                                    <option value="completed" <?php selected($booking->status, 'completed'); ?>>Completed</option>
                                    <option value="cancelled" <?php selected($booking->status, 'cancelled'); ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="button button-small">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
