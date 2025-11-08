<?php
/**
 * Admin Donations Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Trinity Revival Ministry - Donations</h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Booking ID</th>
                <th>Donor Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($donations)): ?>
                <tr>
                    <td colspan="10">No donations found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($donations as $donation): ?>
                    <tr>
                        <td><?php echo $donation->id; ?></td>
                        <td><?php echo $donation->booking_id; ?></td>
                        <td><?php echo esc_html($donation->donor_name); ?></td>
                        <td><?php echo esc_html($donation->donor_email); ?></td>
                        <td><?php echo esc_html($donation->donor_phone); ?></td>
                        <td><strong>$<?php echo number_format($donation->amount, 2); ?></strong></td>
                        <td><?php echo esc_html($donation->payment_method); ?></td>
                        <td><?php echo esc_html($donation->transaction_id); ?></td>
                        <td>
                            <span class="trm-status trm-status-<?php echo $donation->status; ?>">
                                <?php echo ucfirst($donation->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($donation->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php
    // Calculate total donations
    $total = 0;
    foreach ($donations as $donation) {
        if ($donation->status === 'completed') {
            $total += $donation->amount;
        }
    }
    ?>
    
    <div class="trm-stats">
        <h3>Total Donations: $<?php echo number_format($total, 2); ?></h3>
    </div>
</div>
