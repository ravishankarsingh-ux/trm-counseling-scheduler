<?php
/**
 * TRM Events Handler
 * Manages events/webinars custom post type and booking capacity
 */

class TRM_Events {

    public function __construct() {
        add_action('init', array($this, 'register_event_post_type'));
        add_action('add_meta_boxes', array($this, 'add_event_meta_boxes'));
        add_action('save_post', array($this, 'save_event_meta'));
        add_filter('manage_trm_event_posts_columns', array($this, 'add_event_columns'));
        add_action('manage_trm_event_posts_custom_column', array($this, 'event_column_content'), 10, 2);
    }

    /**
     * Register Events custom post type
     */
    public function register_event_post_type() {
        $labels = array(
            'name' => 'Events/Webinars',
            'singular_name' => 'Event',
            'menu_name' => 'Events/Webinars',
            'add_new' => 'Add New Event',
            'add_new_item' => 'Add New Event',
            'edit_item' => 'Edit Event',
            'new_item' => 'New Event',
            'view_item' => 'View Event',
            'search_items' => 'Search Events',
            'not_found' => 'No events found',
            'not_found_in_trash' => 'No events found in trash'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'trm-counseling',
            'query_var' => true,
            'rewrite' => array('slug' => 'events'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'menu_icon' => 'dashicons-calendar-alt'
        );

        register_post_type('trm_event', $args);
    }

    /**
     * Add meta boxes for event settings
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'trm-event-booking-settings',
            'Booking Settings',
            array($this, 'event_booking_settings_metabox'),
            'trm_event',
            'normal',
            'high'
        );

        add_meta_box(
            'trm-event-schedule',
            'Event Schedule',
            array($this, 'event_schedule_metabox'),
            'trm_event',
            'normal',
            'high'
        );
    }

    /**
     * Event booking settings metabox
     */
    public function event_booking_settings_metabox($post) {
        wp_nonce_field('trm_event_meta', 'trm_event_meta_nonce');
        
        $event_type = get_post_meta($post->ID, '_trm_event_type', true) ?: 'counseling';
        $capacity = get_post_meta($post->ID, '_trm_capacity', true) ?: 1;
        $allow_multiple = get_post_meta($post->ID, '_trm_allow_multiple_bookings', true) ?: 'no';
        $session_duration = get_post_meta($post->ID, '_trm_session_duration', true) ?: 30;
        $buffer_time = get_post_meta($post->ID, '_trm_buffer_time', true) ?: 15;
        $require_payment = get_post_meta($post->ID, '_trm_require_payment', true) ?: 'no';
        $booking_fee = get_post_meta($post->ID, '_trm_booking_fee', true) ?: 0;
        ?>
        <table class="form-table">
            <tr>
                <th><label for="trm_event_type">Event Type</label></th>
                <td>
                    <select name="trm_event_type" id="trm_event_type">
                        <option value="counseling" <?php selected($event_type, 'counseling'); ?>>Counseling Session</option>
                        <option value="webinar" <?php selected($event_type, 'webinar'); ?>>Webinar</option>
                        <option value="workshop" <?php selected($event_type, 'workshop'); ?>>Workshop</option>
                        <option value="meeting" <?php selected($event_type, 'meeting'); ?>>Meeting</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="trm_capacity">Maximum Capacity</label></th>
                <td>
                    <input type="number" name="trm_capacity" id="trm_capacity" value="<?php echo esc_attr($capacity); ?>" min="1" />
                    <p class="description">Maximum number of people who can book for each time slot</p>
                </td>
            </tr>
            <tr>
                <th><label for="trm_allow_multiple_bookings">Allow Multiple Bookings Per Slot</label></th>
                <td>
                    <select name="trm_allow_multiple_bookings" id="trm_allow_multiple_bookings">
                        <option value="no" <?php selected($allow_multiple, 'no'); ?>>No - Only one booking per slot</option>
                        <option value="yes" <?php selected($allow_multiple, 'yes'); ?>>Yes - Allow up to capacity limit</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="trm_session_duration">Session Duration (minutes)</label></th>
                <td>
                    <input type="number" name="trm_session_duration" id="trm_session_duration" value="<?php echo esc_attr($session_duration); ?>" min="15" step="15" />
                </td>
            </tr>
            <tr>
                <th><label for="trm_buffer_time">Buffer Time (minutes)</label></th>
                <td>
                    <input type="number" name="trm_buffer_time" id="trm_buffer_time" value="<?php echo esc_attr($buffer_time); ?>" min="0" step="5" />
                    <p class="description">Time between bookings</p>
                </td>
            </tr>
            <tr>
                <th><label for="trm_require_payment">Require Payment</label></th>
                <td>
                    <select name="trm_require_payment" id="trm_require_payment">
                        <option value="no" <?php selected($require_payment, 'no'); ?>>No - Free booking</option>
                        <option value="yes" <?php selected($require_payment, 'yes'); ?>>Yes - Payment required</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="trm_booking_fee">Booking Fee ($)</label></th>
                <td>
                    <input type="number" name="trm_booking_fee" id="trm_booking_fee" value="<?php echo esc_attr($booking_fee); ?>" min="0" step="0.01" />
                    <p class="description">Required fee for booking (if payment required)</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Event schedule metabox
     */
    public function event_schedule_metabox($post) {
        $schedule_type = get_post_meta($post->ID, '_trm_schedule_type', true) ?: 'single';
        $event_date = get_post_meta($post->ID, '_trm_event_date', true);
        $start_time = get_post_meta($post->ID, '_trm_start_time', true) ?: '09:00';
        $end_time = get_post_meta($post->ID, '_trm_end_time', true) ?: '17:00';
        $recurring_days = get_post_meta($post->ID, '_trm_recurring_days', true) ?: array();
        $recurring_start_date = get_post_meta($post->ID, '_trm_recurring_start_date', true);
        $recurring_end_date = get_post_meta($post->ID, '_trm_recurring_end_date', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="trm_schedule_type">Schedule Type</label></th>
                <td>
                    <select name="trm_schedule_type" id="trm_schedule_type">
                        <option value="single" <?php selected($schedule_type, 'single'); ?>>Single Date</option>
                        <option value="recurring" <?php selected($schedule_type, 'recurring'); ?>>Recurring Weekly</option>
                    </select>
                </td>
            </tr>
            <tr id="single_date_row">
                <th><label for="trm_event_date">Event Date</label></th>
                <td>
                    <input type="date" name="trm_event_date" id="trm_event_date" value="<?php echo esc_attr($event_date); ?>" />
                </td>
            </tr>
            <tr id="recurring_start_row" style="display:none;">
                <th><label for="trm_recurring_start_date">Start Date</label></th>
                <td>
                    <input type="date" name="trm_recurring_start_date" id="trm_recurring_start_date" value="<?php echo esc_attr($recurring_start_date); ?>" />
                </td>
            </tr>
            <tr id="recurring_end_row" style="display:none;">
                <th><label for="trm_recurring_end_date">End Date</label></th>
                <td>
                    <input type="date" name="trm_recurring_end_date" id="trm_recurring_end_date" value="<?php echo esc_attr($recurring_end_date); ?>" />
                </td>
            </tr>
            <tr id="recurring_days_row" style="display:none;">
                <th><label>Recurring Days</label></th>
                <td>
                    <?php
                    $days = array(
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday'
                    );
                    foreach ($days as $key => $label) {
                        $checked = in_array($key, (array)$recurring_days) ? 'checked' : '';
                        echo "<label><input type='checkbox' name='trm_recurring_days[]' value='{$key}' {$checked}> {$label}</label><br>";
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="trm_start_time">Start Time</label></th>
                <td>
                    <input type="time" name="trm_start_time" id="trm_start_time" value="<?php echo esc_attr($start_time); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="trm_end_time">End Time</label></th>
                <td>
                    <input type="time" name="trm_end_time" id="trm_end_time" value="<?php echo esc_attr($end_time); ?>" />
                </td>
            </tr>
        </table>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scheduleType = document.getElementById('trm_schedule_type');
            const singleDateRow = document.getElementById('single_date_row');
            const recurringStartRow = document.getElementById('recurring_start_row');
            const recurringEndRow = document.getElementById('recurring_end_row');
            const recurringDaysRow = document.getElementById('recurring_days_row');
            
            function toggleScheduleFields() {
                if (scheduleType.value === 'single') {
                    singleDateRow.style.display = 'table-row';
                    recurringStartRow.style.display = 'none';
                    recurringEndRow.style.display = 'none';
                    recurringDaysRow.style.display = 'none';
                } else {
                    singleDateRow.style.display = 'none';
                    recurringStartRow.style.display = 'table-row';
                    recurringEndRow.style.display = 'table-row';
                    recurringDaysRow.style.display = 'table-row';
                }
            }
            
            scheduleType.addEventListener('change', toggleScheduleFields);
            toggleScheduleFields();
        });
        </script>
        <?php
    }

    /**
     * Save event meta data
     */
    public function save_event_meta($post_id) {
        if (!isset($_POST['trm_event_meta_nonce']) || !wp_verify_nonce($_POST['trm_event_meta_nonce'], 'trm_event_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $meta_fields = array(
            'trm_event_type',
            'trm_capacity',
            'trm_allow_multiple_bookings',
            'trm_session_duration',
            'trm_buffer_time',
            'trm_require_payment',
            'trm_booking_fee',
            'trm_schedule_type',
            'trm_event_date',
            'trm_start_time',
            'trm_end_time',
            'trm_recurring_start_date',
            'trm_recurring_end_date'
        );

        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, "_{$field}", sanitize_text_field($_POST[$field]));
            }
        }

        // Handle recurring days array
        if (isset($_POST['trm_recurring_days'])) {
            update_post_meta($post_id, '_trm_recurring_days', array_map('sanitize_text_field', $_POST['trm_recurring_days']));
        } else {
            delete_post_meta($post_id, '_trm_recurring_days');
        }
    }

    /**
     * Add custom columns to events list
     */
    public function add_event_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key == 'title') {
                $new_columns['event_type'] = 'Type';
                $new_columns['capacity'] = 'Capacity';
                $new_columns['event_date'] = 'Date/Schedule';
                $new_columns['bookings_count'] = 'Bookings';
            }
        }
        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public function event_column_content($column, $post_id) {
        switch ($column) {
            case 'event_type':
                echo ucfirst(get_post_meta($post_id, '_trm_event_type', true));
                break;
            case 'capacity':
                $capacity = get_post_meta($post_id, '_trm_capacity', true);
                $allow_multiple = get_post_meta($post_id, '_trm_allow_multiple_bookings', true);
                echo $capacity . ' (' . ($allow_multiple === 'yes' ? 'Multiple' : 'Single') . ')';
                break;
            case 'event_date':
                $schedule_type = get_post_meta($post_id, '_trm_schedule_type', true);
                if ($schedule_type === 'single') {
                    echo date('M j, Y', strtotime(get_post_meta($post_id, '_trm_event_date', true)));
                } else {
                    echo 'Recurring';
                }
                break;
            case 'bookings_count':
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}trm_bookings WHERE event_id = %d",
                    $post_id
                ));
                echo $count ?: 0;
                break;
        }
    }

    /**
     * Get event details
     */
    public static function get_event($event_id) {
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'trm_event') {
            return false;
        }

        return array(
            'id' => $event->ID,
            'title' => $event->post_title,
            'description' => $event->post_content,
            'type' => get_post_meta($event->ID, '_trm_event_type', true),
            'capacity' => get_post_meta($event->ID, '_trm_capacity', true),
            'allow_multiple_bookings' => get_post_meta($event->ID, '_trm_allow_multiple_bookings', true),
            'session_duration' => get_post_meta($event->ID, '_trm_session_duration', true),
            'buffer_time' => get_post_meta($event->ID, '_trm_buffer_time', true),
            'require_payment' => get_post_meta($event->ID, '_trm_require_payment', true),
            'booking_fee' => get_post_meta($event->ID, '_trm_booking_fee', true),
            'schedule_type' => get_post_meta($event->ID, '_trm_schedule_type', true),
            'event_date' => get_post_meta($event->ID, '_trm_event_date', true),
            'start_time' => get_post_meta($event->ID, '_trm_start_time', true),
            'end_time' => get_post_meta($event->ID, '_trm_end_time', true),
            'recurring_days' => get_post_meta($event->ID, '_trm_recurring_days', true),
            'recurring_start_date' => get_post_meta($event->ID, '_trm_recurring_start_date', true),
            'recurring_end_date' => get_post_meta($event->ID, '_trm_recurring_end_date', true)
        );
    }

    /**
     * Get available events
     */
    public static function get_available_events() {
        // Primary: upcoming single-date events and all recurring events
        $events = get_posts(array(
            'post_type' => 'trm_event',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_trm_event_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>='
                ),
                array(
                    'key' => '_trm_schedule_type',
                    'value' => 'recurring'
                )
            )
        ));

        // Fallback: if no events matched (e.g., missing meta), return all published events
        if (empty($events)) {
            $events = get_posts(array(
                'post_type' => 'trm_event',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
        }

        $available_events = array();
        foreach ($events as $event) {
            $available_events[] = self::get_event($event->ID);
        }

        return $available_events;
    }
}