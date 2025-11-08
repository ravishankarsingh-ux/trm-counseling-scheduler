# TRM Counseling Scheduler - Testing & Validation Checklist

## Core Functionality Tests

### ✅ Event Management
- [ ] Can create new event with custom post type
- [ ] Event saves with correct title
- [ ] Event settings (capacity, duration, buffer) save correctly
- [ ] Single date events save properly
- [ ] Recurring weekly events save with correct days
- [ ] Event meta fields display on edit screen
- [ ] Events appear in WP admin sidebar under TRM Counseling menu
- [ ] Events are searchable in admin

### ✅ Booking Capacity - Single Booking Mode
**Scenario**: Event with capacity=1, allow_multiple_bookings='no'

Test Case 1.1: First booking succeeds
- [ ] User can book time slot 10:00 AM
- [ ] Booking created with status "pending"
- [ ] Confirmation email sent

Test Case 1.2: Second booking for same slot fails
- [ ] User tries to book same slot (10:00 AM)
- [ ] System returns error: "This time slot is already booked"
- [ ] Booking NOT created
- [ ] Next available slot (10:45 AM) is available for booking

Test Case 1.3: Multiple different slots work
- [ ] 10:00 AM - Booked
- [ ] 10:45 AM - Available
- [ ] 11:30 AM - Available
- [ ] Can book multiple users at different times

### ✅ Booking Capacity - Multiple Booking Mode
**Scenario**: Event with capacity=3, allow_multiple_bookings='yes'

Test Case 2.1: Up to capacity bookings succeed
- [ ] Person A books 10:00 AM - Success
- [ ] Person B books 10:00 AM - Success
- [ ] Person C books 10:00 AM - Success
- [ ] All show in bookings list for same slot

Test Case 2.2: Over capacity fails
- [ ] Person D tries to book 10:00 AM
- [ ] System returns error: "This time slot is fully booked"
- [ ] Next slot (11:05 AM) is available

Test Case 2.3: Partial capacity usage
- [ ] Event capacity = 5
- [ ] 3 bookings for 10:00 AM
- [ ] Next person can still book 10:00 AM (space for 2 more)
- [ ] 6th person cannot book

### ✅ Payment Processing - Offline/Cash
Test Case 3.1: Offline payment selection
- [ ] User selects "Offline Payment" option
- [ ] Payment instructions displayed
- [ ] Booking created with payment_method='offline'
- [ ] Payment status = 'pending_offline'
- [ ] Payment reference generated (offline_timestamp_bookingid)

Test Case 3.2: Email notifications for offline
- [ ] User email contains payment instructions
- [ ] Admin email contains payment reference
- [ ] Admin can reference payment when updating status

Test Case 3.3: Admin approval workflow
- [ ] Go to TRM Counseling → All Bookings
- [ ] Find pending_offline booking
- [ ] Can update payment status to "completed"
- [ ] Booking status updates to "confirmed"

### ✅ Payment Processing - Stripe
Test Case 4.1: Stripe configuration
- [ ] Stripe keys saved in Settings
- [ ] Enable Stripe checkbox works
- [ ] Stripe option appears in payment methods

Test Case 4.2: Test payment processing
- [ ] Booking form shows Stripe option
- [ ] Can enter test card (4242 4242 4242 4242)
- [ ] Payment processes successfully
- [ ] Booking status = "confirmed"
- [ ] Payment status = "completed"
- [ ] Transaction ID recorded

Test Case 4.3: Email after successful payment
- [ ] User receives confirmation with transaction details
- [ ] Admin notified of completed payment

### ✅ Payment Processing - PayPal
Test Case 5.1: PayPal configuration
- [ ] PayPal credentials saved
- [ ] Enable PayPal checkbox works
- [ ] PayPal option appears in payment methods

Test Case 5.2: PayPal flow
- [ ] Can select PayPal payment method
- [ ] Redirects to PayPal (sandbox if configured)
- [ ] Returns after approval
- [ ] Booking confirmed after payment

### ✅ Slot Availability Calculation
**Event**: 30-minute sessions + 15-minute buffer, working hours 09:00-17:00

- [ ] 09:00 slot exists
- [ ] 09:45 slot exists (09:00-09:30 + 09:30-09:45)
- [ ] 10:30 slot exists (09:45-10:15 + 10:15-10:30)
- [ ] 11:15 slot exists
- [ ] Slots end before 17:00 (last slot is 16:15)
- [ ] No slots after 17:00

**Event**: 60-minute sessions + 5-minute buffer, working hours 14:00-17:00

- [ ] 14:00 slot exists
- [ ] 15:05 slot exists (14:00-15:00 + 15:00-15:05)
- [ ] 16:10 slot exists (15:05-16:05 + 16:05-16:10)
- [ ] Only 3 slots fit in 3-hour window

### ✅ Email Notifications
Test Case 6.1: User booking confirmation
- [ ] Email sent to user's email address
- [ ] Subject line correct
- [ ] Contains event name
- [ ] Contains booking date
- [ ] Contains booking time
- [ ] Contains session duration
- [ ] Contains booking fee (if applicable)
- [ ] Contains donation amount (if applicable)
- [ ] Contains payment method
- [ ] Contains payment status

Test Case 6.2: Admin notification
- [ ] Email sent to notification email in settings
- [ ] Subject line mentions new booking
- [ ] Contains user name and contact info
- [ ] Contains event details
- [ ] Contains payment information

### ✅ Recurring Events
**Event**: Monday/Wednesday/Friday, 14:00-17:00, Jan 1 - Mar 31

Test Case 7.1: Slots available for each occurrence
- [ ] Monday Jan 1, 14:00 - Available
- [ ] Wednesday Jan 3, 14:00 - Available
- [ ] Friday Jan 5, 14:00 - Available
- [ ] Monday Jan 8, 14:00 - Available
- [ ] Sunday (not in recurring days) - No slots

Test Case 7.2: Separate capacity for each date
- [ ] Book Monday 14:00 (Person A)
- [ ] Book Wednesday 14:00 (Person B)
- [ ] Book Friday 14:00 (Person C)
- [ ] Each is separate booking
- [ ] Capacity not shared between dates

Test Case 7.3: Recurring end date
- [ ] Slots available until end date
- [ ] After end date, no slots shown
- [ ] Past recurrences hidden

### ✅ Shortcode Functionality
Test Case 8.1: Shortcode with event_id
- [ ] `[trm_booking_form event_id="123"]` displays form
- [ ] Form pre-populated with correct event
- [ ] User can submit booking

Test Case 8.2: Shortcode without event_id
- [ ] `[trm_booking_form]` displays form
- [ ] User can select from all events
- [ ] Event dropdown populated

### ✅ AJAX Operations
Test Case 9.1: Get available slots AJAX
- [ ] POST to wp_ajax with action "trm_get_available_slots"
- [ ] Passing event_id and date
- [ ] Returns correct slots array
- [ ] Respects capacity limits
- [ ] Respects allow_multiple_bookings setting

Test Case 9.2: Get payment methods AJAX
- [ ] POST to wp_ajax with action "trm_get_payment_methods"
- [ ] Returns only enabled methods
- [ ] Includes method labels

Test Case 9.3: Create booking AJAX
- [ ] POST with all required fields
- [ ] Returns success with booking_id
- [ ] Booking created in database
- [ ] Respects capacity limits

### ✅ Database Integrity
- [ ] event_id field exists in wp_trm_bookings
- [ ] booking_fee field exists
- [ ] payment_method field exists
- [ ] Foreign key relationship maintained
- [ ] Deleting event doesn't crash bookings
- [ ] Booking queries with event_id work correctly

### ✅ Admin Dashboard
Test Case 10.1: Bookings list
- [ ] Can view all bookings
- [ ] Can filter by event
- [ ] Can filter by date
- [ ] Can filter by status
- [ ] Shows event name
- [ ] Shows payment method
- [ ] Shows payment status

Test Case 10.2: Bookings column display
- [ ] Event column shows event name
- [ ] Payment method column visible
- [ ] Payment status column visible
- [ ] Can sort by event

### ✅ Settings Configuration
Test Case 11.1: Payment method toggles
- [ ] Can enable/disable Stripe
- [ ] Can enable/disable PayPal
- [ ] Can enable/disable Offline
- [ ] Settings save correctly
- [ ] Payment form updates accordingly

Test Case 11.2: Payment credentials
- [ ] Stripe keys saveable
- [ ] PayPal credentials saveable
- [ ] Offline instructions saveable
- [ ] No sensitive data leaked in frontend

## Edge Cases & Error Handling

### ✅ Edge Case: Simultaneous Bookings
- [ ] Two users book same slot simultaneously
- [ ] Only one succeeds
- [ ] Other gets proper error message
- [ ] Database has no duplicate entries

### ✅ Edge Case: Booking After Capacity Reached
- [ ] Capacity full, slot locked
- [ ] User tries to book anyway
- [ ] Proper error message shown
- [ ] Booking NOT created

### ✅ Edge Case: Event Date in Past
- [ ] Cannot book past dates
- [ ] Date picker disabled for past dates
- [ ] Error if date tampered with in code

### ✅ Edge Case: Missing Event
- [ ] Booking form with non-existent event_id
- [ ] Graceful error handling
- [ ] No SQL errors
- [ ] User sees friendly message

### ✅ Edge Case: Payment Timeout
- [ ] Payment gateway timeout handled
- [ ] Booking status NOT confirmed
- [ ] User can retry
- [ ] No duplicate charges

### ✅ Edge Case: Buffer Time Overflow
- [ ] Last slot calculation correct
- [ ] No slot extends beyond end time
- [ ] Proper slot generation at boundary

## Performance Tests

### ✅ Slot Generation Performance
- [ ] Generating 100+ available slots loads quickly (<2s)
- [ ] Capacity checking doesn't cause N+1 queries
- [ ] Database indexes on event_id and booking_date

### ✅ Booking Creation Performance
- [ ] Creating booking <500ms
- [ ] Capacity check doesn't timeout
- [ ] Email sending non-blocking

## Security Tests

### ✅ Input Validation
- [ ] SQL injection attempts blocked
- [ ] XSS attempts sanitized
- [ ] CSRF protection (nonces) working
- [ ] Event_id validated (belongs to correct post type)

### ✅ Authorization
- [ ] Only admins can manage events
- [ ] Only admins can see bookings dashboard
- [ ] Users can only book (not modify)
- [ ] Users cannot see other users' data

### ✅ Payment Security
- [ ] API keys not exposed in frontend
- [ ] Test keys vs live keys handled
- [ ] Payment verification on server side
- [ ] No payment info stored locally

## Documentation Tests

- [ ] SETUP_GUIDE.md is complete and accurate
- [ ] All code is properly commented
- [ ] Database schema documented
- [ ] AJAX endpoints documented
- [ ] Setup instructions tested

## Deployment Checklist

Before going live:
- [ ] All tests passed
- [ ] Live payment gateway keys configured
- [ ] Email notifications working with real email
- [ ] Database backed up
- [ ] SSL certificate installed (for payments)
- [ ] Debug logging disabled in production
- [ ] Performance optimized (caching enabled)
- [ ] Error logs monitored
- [ ] User documentation created
- [ ] Admin staff trained

## Test Report Template

```
Test Suite: [Name]
Date: [Date]
Tester: [Name]

Passed: ___/__
Failed: ___/__
Blocked: ___/__

Failed Tests:
1. [Test Name] - [Issue] - [Steps to Reproduce]
2. ...

Notes:
[Any observations or concerns]
```