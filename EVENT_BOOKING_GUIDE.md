# Event-Based Booking Form - Usage Guide

## Overview

The updated booking form now includes dynamic event selection. Users can:
1. Select an event from a dropdown
2. View event details (capacity, duration, fee)
3. Select a booking date
4. View available time slots based on event settings
5. Enter personal information
6. Complete the booking

## Shortcode Usage

### Option 1: Event Selector (Recommended)
```
[trm_booking_form]
```
This displays a dropdown to select from all available events.

**Use Case:** Single page where users can book any available event

### Option 2: Specific Event
```
[trm_booking_form event_id="123"]
```
Pre-selects a specific event (no dropdown shown).

**Use Case:** Individual event pages with dedicated booking form

## How It Works

### Step 1: Event Selection
- Dropdown shows all published events
- When user selects an event, displays:
  - Event type (Counseling, Webinar, etc.)
  - Capacity (Max bookings per slot)
  - Session duration (in minutes)
  - Booking fee (if applicable)
- "Next" button enables after selection

### Step 2: Date Selection
- User picks a date
- Form automatically loads available time slots
- Time slots respect event's:
  - Capacity settings
  - Single/multiple booking rules
  - Session duration + buffer time

### Step 3: Time Slot Selection
- Available slots displayed as buttons
- Slots automatically hide if fully booked
- User clicks to select a slot

### Step 4: Personal Information
- Full name (required)
- Phone number (required)
- Email address (optional)

### Step 5: Membership Status
- "Are you a member?" question
- Affects session duration and pricing

### Step 6: Donations/Additional Fees
- Members: Optional donation options
- Non-members: "Plant a seed" donation options
- Custom amount option available

### Step 7: Confirmation
- Review booking details
- Confirm booking
- Receive confirmation message with booking ID

## Frontend Flow

```
User visits page with shortcode
    ↓
Form loads with event dropdown
    ↓
User selects event
    ↓
Event details displayed
    ↓
User clicks "Next"
    ↓
User selects date
    ↓
AJAX loads available slots
    ↓
User selects time
    ↓
User enters personal info
    ↓
User selects membership status
    ↓
User confirms booking
    ↓
Booking created
    ↓
Confirmation email sent
    ↓
Success message shown
```

## JavaScript Features

The booking form uses AJAX for dynamic content:

### AJAX Calls Made:
1. `trm_get_events` - Load all active events on page load
2. `trm_get_available_slots` - Load slots when date selected
3. `trm_create_booking` - Submit booking data

### Dynamic Updates:
- Event dropdown auto-populated
- Time slots update when date changes
- Capacity restrictions enforced client-side
- Form validation on submission

## Technical Details

### JavaScript File
- Location: `assets/js/booking-form.js` (308 lines)
- jQuery-based
- Uses WordPress AJAX
- Handles all form logic and transitions

### CSS File
- Location: `assets/css/booking-form.css` (366 lines)
- Responsive design (mobile-friendly)
- Green color scheme (#4CAF50)
- Accessible focus states

### Data Passed to AJAX

**Event Selection AJAX:**
```javascript
{
    action: 'trm_get_events',
    nonce: nonce_value
}
```

**Available Slots AJAX:**
```javascript
{
    action: 'trm_get_available_slots',
    date: 'YYYY-MM-DD',
    event_id: event_id,
    nonce: nonce_value
}
```

**Create Booking AJAX:**
```javascript
{
    action: 'trm_create_booking',
    event_id: event_id,
    full_name: 'Name',
    phone: 'Phone',
    email: 'email@example.com',
    booking_date: 'YYYY-MM-DD',
    booking_time: 'HH:MM',
    is_member: 0 or 1,
    donation_amount: 0.00,
    payment_method: 'offline',
    nonce: nonce_value
}
```

## Event Capacity Logic

### Single Booking Mode (capacity=1, allow_multiple='no')
- Only 1 person can book each slot
- Slot shows as unavailable if already booked

### Multiple Booking Mode (capacity=5, allow_multiple='yes')
- Up to 5 people can book same slot
- Slot shows as unavailable when 5 bookings exist
- Count updates in real-time via AJAX

## Styling Classes

### Main Container
- `.trm-booking-wrapper` - Outer wrapper
- `.trm-booking-container` - Inner container

### Form Elements
- `.trm-form-group` - Form field group
- `.trm-step` - Form step/section
- `.trm-step.active` - Visible step

### Buttons
- `.trm-btn` - Generic button
- `.trm-btn-primary` - Green action button
- `.trm-btn-secondary` - Gray action button
- `.trm-btn-next` - Next button
- `.trm-btn-back` - Back button
- `.trm-time-slot` - Time slot button
- `.trm-time-slot.selected` - Selected time slot

### Messages
- `.trm-note` - Info message (gray)
- `.trm-error` - Error message (red)
- `.trm-warning-message` - Warning (yellow)
- `.trm-success-message` - Success (green)

## Usage Examples

### Example 1: Single Event Page
**Scenario:** You have a Webinar event (ID: 42)

1. Edit page
2. Add shortcode: `[trm_booking_form event_id="42"]`
3. Save
4. Users see booking form with dates and slots for that webinar

### Example 2: Multi-Event Booking Page
**Scenario:** You want users to book any event

1. Edit page
2. Add shortcode: `[trm_booking_form]`
3. Save
4. Users see dropdown with all events
5. Select event, then see availability

### Example 3: Multiple Booking Page
Place both:
```
<h2>Book a Counseling Session</h2>
[trm_booking_form]
```

### Example 4: Archive-Style
Create separate pages for each event:
- `/counseling/daily-session/` → `[trm_booking_form event_id="1"]`
- `/counseling/webinar/` → `[trm_booking_form event_id="2"]`
- `/counseling/workshop/` → `[trm_booking_form event_id="3"]`

## Responsive Design

The form is mobile-responsive:
- Mobile: Full-width, single column
- Tablet: Optimized spacing
- Desktop: Centered 600px width

## Troubleshooting

### Events Not Showing in Dropdown
1. Verify events are published in WordPress
2. Check browser console for AJAX errors
3. Ensure nonce is valid
4. Clear browser cache

### Time Slots Not Appearing
1. Verify event has date set
2. Check if capacity is full for that date/time
3. Verify working hours are set correctly
4. Check browser console for AJAX errors

### Booking Not Submitting
1. Verify all required fields are filled
2. Check browser console for JavaScript errors
3. Verify database tables exist
4. Check server error logs

### Styling Issues
1. Verify CSS file is enqueued
2. Check for CSS conflicts with theme
3. Try disabling other plugins
4. Clear WordPress cache

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Notes

- AJAX requests cached where possible
- Events loaded once on page load
- Time slots loaded on-demand per date
- Minimal database queries
- No page reloads required

## Security

- All user input sanitized
- CSRF protection via nonces
- SQL injection prevention
- XSS prevention
- Server-side validation

## Future Enhancements

Potential improvements:
- Calendar widget for date selection
- Real-time availability indicators
- SMS notifications option
- Payment gateway selection on form
- Booking history page
- Cancellation/rescheduling

---

## Quick Reference

| Task | Shortcode |
|------|-----------|
| Show all events | `[trm_booking_form]` |
| Show specific event | `[trm_booking_form event_id="123"]` |

| Setting | Effect |
|---------|--------|
| Event Capacity | Max bookings per slot |
| Allow Multiple Bookings | Yes = multiple per slot, No = 1 per slot |
| Session Duration | Time per session |
| Buffer Time | Break between sessions |
| Booking Fee | Charge per booking |
| Require Payment | Block booking until paid |