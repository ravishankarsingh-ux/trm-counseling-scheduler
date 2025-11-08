# TRM Counseling Scheduler - Setup & Testing Guide

## Overview
Event-based booking system with capacity management, multiple payment gateways, and dynamic slot availability.

## Key Changes Made

### 1. Events/Webinars Custom Post Type
- Created `TRM_Events` class for managing events
- Each event has its own booking rules:
  - Capacity per time slot
  - Single vs multiple bookings per slot
  - Session duration
  - Buffer time between bookings
  - Payment requirements and fees

### 2. Updated Database Schema
- `wp_trm_bookings` table now includes:
  - `event_id` - Links booking to specific event
  - `booking_fee` - Event-specific booking fee
  - `payment_method` - Tracks payment method used (stripe, paypal, offline)

### 3. Payment Gateways
- **Stripe**: Credit card processing
- **PayPal**: PayPal integration
- **Offline/Cash**: Manual payment verification
- Each can be enabled/disabled independently

### 4. Capacity Management Logic
**Single Booking (allow_multiple_bookings = 'no')**
```
IF bookings for time slot >= 1 THEN slot is full
```

**Multiple Bookings (allow_multiple_bookings = 'yes')**
```
IF bookings for time slot >= capacity THEN slot is full
```

## Setup Steps

### Step 1: Activate Plugin
- Navigate to: WP Admin → Plugins
- Find "TRM Counseling Session Scheduler"
- Click "Activate"
- Tables created automatically on activation

### Step 2: Configure Settings
Go to: **TRM Counseling → Settings**

**Payment Methods Section:**
- ☑ Enable Stripe (if using credit cards)
- ☑ Enable PayPal (if using PayPal)
- ☑ Enable Offline Payment (for cash/check)

**Payment Credentials:**
- Stripe Public Key: pk_test_xxxxx
- Stripe Secret Key: sk_test_xxxxx
- PayPal Client ID: xxxxx
- PayPal Secret: xxxxx

**Offline Settings:**
- Payment Instructions: "Send check to address X" or equivalent

### Step 3: Create Test Events

#### Event 1: Single-Booking Counseling
1. Go to: **TRM Counseling → Events/Webinars**
2. Click: **Add New Event**
3. Title: "Daily Counseling Session"
4. Booking Settings:
   - Event Type: Counseling Session
   - Maximum Capacity: **1**
   - Allow Multiple Bookings: **No**
   - Session Duration: 30 minutes
   - Buffer Time: 15 minutes
   - Require Payment: No
5. Event Schedule:
   - Schedule Type: Single Date
   - Event Date: Tomorrow's date
   - Start Time: 09:00
   - End Time: 17:00
6. Publish

#### Event 2: Multiple-Booking Webinar
1. Title: "Financial Wisdom Webinar"
2. Booking Settings:
   - Event Type: Webinar
   - Maximum Capacity: **5**
   - Allow Multiple Bookings: **Yes**
   - Session Duration: 60 minutes
   - Buffer Time: 5 minutes
   - Require Payment: Yes
   - Booking Fee: $25
3. Event Schedule:
   - Schedule Type: Recurring Weekly
   - Start Date: 2025-01-01
   - End Date: 2025-12-31
   - Recurring Days: Monday, Wednesday, Friday
   - Start Time: 14:00
   - End Time: 17:00
4. Publish

#### Event 3: Offline Payment Counseling
1. Title: "Priority Consultation (Offline Payment)"
2. Booking Settings:
   - Event Type: Meeting
   - Maximum Capacity: 1
   - Allow Multiple Bookings: No
   - Session Duration: 45 minutes
   - Buffer Time: 15 minutes
   - Require Payment: Yes
   - Booking Fee: $50
3. Event Schedule:
   - Schedule Type: Single Date
   - Event Date: Tomorrow
   - Start Time: 10:00
   - End Time: 16:00
4. Publish

### Step 4: Add Booking Forms to Pages

1. Create/Edit a WordPress page
2. Add shortcode: `[trm_booking_form event_id="123"]`
   (Replace 123 with your event ID from Events list)
3. Publish

Get Event IDs:
- Go to TRM Counseling → Events/Webinars
- Hover over event title
- ID appears in URL as post=XXX

## Testing Scenarios

### Test 1: Single Booking Capacity
**Setup**: Use Event 1 (Daily Counseling Session)

**Test Steps**:
1. Fill booking form for tomorrow 10:00 AM
2. Submit booking
3. Observe: Booking created successfully
4. Try booking same time slot again
5. Observe: Error "This time slot is already booked"
6. Try booking 10:45 AM (next slot)
7. Observe: Should be available (30min + 15min buffer)

**Expected Result**: ✓ Only one booking per slot

---

### Test 2: Multiple Bookings Capacity
**Setup**: Use Event 2 (Financial Wisdom Webinar - capacity 5)

**Test Steps**:
1. Create 5 bookings for same time slot (e.g., Monday 14:00)
   - Booking 1: Person A - Slot 14:00 ✓
   - Booking 2: Person B - Slot 14:00 ✓
   - Booking 3: Person C - Slot 14:00 ✓
   - Booking 4: Person D - Slot 14:00 ✓
   - Booking 5: Person E - Slot 14:00 ✓
2. Try 6th booking for same slot
3. Observe: Error "This time slot is fully booked"
4. Try booking next slot (15:00)
5. Observe: Should be available

**Expected Result**: ✓ Up to 5 people per slot, then full

---

### Test 3: Offline Payment Processing
**Setup**: Use Event 3 (Priority Consultation)

**Test Steps**:
1. Fill booking form
2. Select offline payment method
3. Submit booking
4. Observe: Booking created with:
   - Status: pending
   - Payment Status: pending_offline
   - Payment Reference: offline_[timestamp]_[booking_id]
5. User receives email with payment instructions
6. Admin receives email notification
7. Go to TRM Counseling → All Bookings
8. Observe: Booking shows "Pending Offline" status
9. After user sends payment, admin manually updates status to "confirmed"

**Expected Result**: ✓ Offline payment tracked and awaiting verification

---

### Test 4: Stripe Payment Processing
**Setup**: Use Event 2 with Stripe configured

**Test Steps**:
1. Add booking with Stripe selected
2. Fill payment form with test card:
   - Card: 4242 4242 4242 4242
   - Expiry: 12/25
   - CVC: 123
3. Submit payment
4. Observe: 
   - Booking status: confirmed
   - Payment status: completed
   - Transaction ID: stripe_[id]
5. Check confirmation email sent to user
6. Verify in Stripe dashboard

**Expected Result**: ✓ Real-time payment processing

---

### Test 5: Event Slot Calculation
**Setup**: Event 1 (30min session + 15min buffer)

**Verify Slots**:
- 09:00 - 09:30 (session) + 09:30-09:45 (buffer) = Next: 09:45
- 09:45 - 10:15 (session) + 10:15-10:30 (buffer) = Next: 10:30
- 10:30 - 11:00 (session) + 11:00-11:15 (buffer) = Next: 11:15
- ... continues until 17:00

**Expected Result**: ✓ Correct slot intervals

---

### Test 6: Email Notifications
**Test Steps**:
1. Complete a booking
2. Check inbox for confirmation email
3. Verify email contains:
   - Event name
   - Date and time
   - Session duration
   - Booking fee (if applicable)
   - Payment status
   - Payment method
4. Check admin notification email
5. Verify all details present

**Expected Result**: ✓ Emails sent to user and admin

---

### Test 7: Recurring Event Availability
**Setup**: Event 2 (Recurring Wed-Fri-Mon)

**Test Steps**:
1. Select Wednesday slot
2. Observe: Slots available
3. Select Friday (same time)
4. Observe: Different event instance, slots available
5. Book both
6. Verify separate bookings created
7. Check availability updates correctly

**Expected Result**: ✓ Each recurring date treated separately

---

## Database Query Examples

### Check Bookings for Event
```sql
SELECT * FROM wp_trm_bookings 
WHERE event_id = 123 
AND booking_date = '2025-01-08'
ORDER BY booking_time;
```

### Check Payment Methods Used
```sql
SELECT payment_method, COUNT(*) as count, SUM(booking_fee + donation_amount) as total
FROM wp_trm_bookings
GROUP BY payment_method;
```

### Find Fully Booked Slots
```sql
SELECT 
  event_id, booking_date, booking_time, COUNT(*) as bookings
FROM wp_trm_bookings
WHERE status IN ('confirmed', 'pending')
GROUP BY event_id, booking_date, booking_time
HAVING COUNT(*) >= (SELECT _trm_capacity FROM wp_postmeta WHERE post_id = event_id);
```

## Troubleshooting

### Issue: Slots not appearing
**Check**:
1. Event is published
2. Event date is today or future
3. Working hours cover selected date
4. Time zone is correct

**Solution**: 
- Re-save event
- Clear browser cache
- Test with different date

### Issue: Payment not processing
**Check**:
1. Payment method is enabled in settings
2. API credentials are correct
3. Payment gateway is configured
4. Check browser console for errors

**Solution**:
- Verify credentials in Settings
- Test with provider's sandbox first
- Enable debug logging

### Issue: Booking showing wrong slot count
**Check**:
1. Event capacity setting
2. allow_multiple_bookings value
3. Only "pending" and "confirmed" count towards capacity

**Solution**:
- Verify event settings
- Check booking statuses
- Refresh page

## Files Modified/Created

### New Files
- `includes/class-trm-events.php` - Events custom post type

### Modified Files
- `trm-counseling-scheduler.php` - Added Events class
- `includes/class-trm-database.php` - Updated schema
- `includes/class-trm-booking.php` - Event-aware slot calculation
- `includes/class-trm-payment.php` - Offline payment support
- `includes/class-trm-ajax.php` - New AJAX actions
- `includes/class-trm-admin.php` - Settings for payment methods
- `includes/class-trm-shortcodes.php` - Event ID support
- `templates/admin-settings.php` - Payment gateway UI

## Quick Start Checklist

- [ ] Plugin activated
- [ ] Payment gateways configured in Settings
- [ ] Test event created (single booking)
- [ ] Test event created (multiple bookings)
- [ ] Booking form added to page with [trm_booking_form event_id="X"]
- [ ] Tested single booking restriction
- [ ] Tested multiple booking capacity
- [ ] Tested offline payment flow
- [ ] Tested email notifications
- [ ] Verified admin bookings dashboard