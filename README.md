# TRM Counseling Session Scheduler

A comprehensive WordPress plugin for managing event-based counseling sessions and webinars with advanced capacity management, multiple payment gateways, flexible booking rules, and customizable time slot colors.

**Version:** 1.1.0  
**Author:** Ebenezer Caurie  
**Organization:** Trinity Revival Ministry  
**License:** GPL v2 or later

---

## 🎯 Features

### Events/Webinars Management
- Custom post type for creating and managing events
- Support for single-date and recurring (weekly) events
- Event-specific configuration:
  - Maximum capacity per time slot
  - Single or multiple bookings per slot
  - Customizable session duration
  - Buffer time between bookings
  - Payment requirements and fees

### Intelligent Capacity Management
**Single Booking Mode (1 person per slot)**
- Only one person can book a specific time slot
- Perfect for one-on-one counseling sessions
- Error message when slot is taken: "This time slot is already booked"

**Multiple Booking Mode (Up to N people per slot)**
- Multiple people can book the same time slot
- Configurable capacity (1-100+ people)
- Perfect for webinars and group sessions
- Shows "fully booked" when capacity reached

### Payment Gateway Integration
Three payment methods available:

1. **Stripe (Credit Card)**
   - Real-time payment processing
   - Secure PCI-compliant handling
   - Configurable via API keys

2. **PayPal**
   - PayPal account integration
   - Sandbox and live mode support
   - Buyer protection included

3. **Offline/Cash**
   - Manual payment verification
   - Customizable payment instructions
   - Payment reference tracking
   - Admin approval workflow

### Advanced Booking Features
- AJAX-based slot availability checking
- Respects event capacity limits in real-time
- Email notifications (user + admin)
- Donation support for non-members
- Booking status tracking (pending → confirmed → completed)
- Payment status tracking (unpaid → pending → completed → verified)

### Admin Dashboard
- Comprehensive bookings management
- Filter by event, date, status, payment method
- Bulk actions for booking management
- Payment method breakdown
- Donation tracking
- Email notification configuration

---

## 📋 Installation

### Requirements
- WordPress 5.0+
- PHP 7.2+
- MySQL 5.7+

### Steps
1. Download/Upload plugin to `/wp-content/plugins/trm-counseling-scheduler/`
2. Activate plugin in WordPress Admin → Plugins
3. Database tables created automatically on activation
4. Go to TRM Counseling → Settings to configure

---

## 🚀 Quick Start

### 1. Configure Payment Gateways
**Dashboard:** TRM Counseling → Settings

```
☑ Enable Stripe (if using credit cards)
☑ Enable PayPal (if using PayPal)
☑ Enable Offline Payment (for cash/check)

Add API credentials:
- Stripe Public Key: pk_test_xxxxx
- Stripe Secret Key: sk_test_xxxxx
- PayPal Client ID: xxxxx
- Offline Instructions: "Send payment to..."
```

### 2. Create an Event
**Dashboard:** TRM Counseling → Events/Webinars → Add New

```
Title: Daily Counseling Session

Booking Settings:
- Type: Counseling Session
- Capacity: 1
- Allow Multiple Bookings: No
- Session Duration: 30 minutes
- Buffer Time: 15 minutes
- Require Payment: No

Event Schedule:
- Type: Single Date
- Date: Tomorrow
- Start: 09:00 | End: 17:00

Publish
```

### 3. Add Booking Form to Page
**Edit Page:** Add shortcode

```html
[trm_booking_form event_id="123"]
```

Replace `123` with your event ID (visible in Events list).

---

## 🔧 Architecture

### Database Schema

#### wp_trm_bookings
```sql
id (INT) - Primary key
event_id (INT) - Link to event
full_name (VARCHAR) - Booking user name
phone (VARCHAR) - Contact phone
email (VARCHAR) - Contact email
booking_date (DATE) - Booking date
booking_time (TIME) - Booking time
is_member (TINYINT) - Member flag
donation_amount (DECIMAL) - Donation amount
booking_fee (DECIMAL) - Event fee
session_duration (INT) - Minutes
status (VARCHAR) - pending|confirmed|cancelled
payment_status (VARCHAR) - unpaid|pending|completed|pending_offline
payment_method (VARCHAR) - stripe|paypal|offline
payment_id (VARCHAR) - Transaction reference
```

#### wp_trm_events (Post Meta)
```
_trm_event_type (TEXT)
_trm_capacity (INT)
_trm_allow_multiple_bookings (TEXT) - yes|no
_trm_session_duration (INT)
_trm_buffer_time (INT)
_trm_require_payment (TEXT) - yes|no
_trm_booking_fee (DECIMAL)
_trm_schedule_type (TEXT) - single|recurring
_trm_event_date (DATE)
_trm_start_time (TIME)
_trm_end_time (TIME)
_trm_recurring_days (ARRAY)
_trm_recurring_start_date (DATE)
_trm_recurring_end_date (DATE)
```

### Core Classes

| Class | File | Purpose |
|-------|------|---------|
| `TRM_Events` | `class-trm-events.php` | Event management & custom post type |
| `TRM_Booking` | `class-trm-booking.php` | Booking logic & slot calculations |
| `TRM_Payment` | `class-trm-payment.php` | Payment processing & gateways |
| `TRM_Database` | `class-trm-database.php` | Database schema & settings |
| `TRM_Admin` | `class-trm-admin.php` | Admin dashboard & interface |
| `TRM_Ajax` | `class-trm-ajax.php` | AJAX endpoints for frontend |
| `TRM_Shortcodes` | `class-trm-shortcodes.php` | WordPress shortcodes |

---

## 📡 API Reference

### AJAX Endpoints

All AJAX endpoints require WordPress nonce: `trm-counseling-nonce`

#### Get Available Slots
```
Action: trm_get_available_slots
Method: POST
Parameters:
  - date (YYYY-MM-DD)
  - event_id (INT)
  - nonce (STRING)

Response:
{
  success: true,
  data: {
    slots: ["09:00", "09:45", "10:30", ...]
  }
}
```

#### Get Payment Methods
```
Action: trm_get_payment_methods
Method: POST
Parameters:
  - nonce (STRING)

Response:
{
  success: true,
  data: {
    methods: {
      "stripe": "Credit Card (Stripe)",
      "paypal": "PayPal",
      "offline": "Cash/Check/Bank Transfer"
    }
  }
}
```

#### Create Booking
```
Action: trm_create_booking
Method: POST
Parameters:
  - event_id (INT)
  - full_name (STRING)
  - phone (STRING)
  - email (STRING)
  - booking_date (YYYY-MM-DD)
  - booking_time (HH:MM)
  - is_member (0|1)
  - donation_amount (DECIMAL)
  - payment_method (stripe|paypal|offline)
  - nonce (STRING)

Response:
{
  success: true,
  data: {
    booking_id: 123,
    session_duration: 30,
    total_amount: 50.00,
    require_payment: true
  }
}
```

#### Process Payment
```
Action: trm_process_payment
Method: POST
Parameters:
  - booking_id (INT)
  - amount (DECIMAL)
  - payment_method (stripe|paypal|offline)
  - nonce (STRING)

Response:
{
  success: true,
  data: {
    message: "Payment processed successfully",
    transaction_id: "stripe_123456"
  }
}
```

### WordPress Shortcodes

#### Booking Form (with specific event)
```html
[trm_booking_form event_id="123"]
```

#### Booking Form (let user choose event)
```html
[trm_booking_form]
```

#### User's Bookings
```html
[trm_my_bookings]
```

---

## 🧪 Testing

See **TESTING_CHECKLIST.md** for comprehensive testing scenarios.

Quick test:
1. Create event with capacity=1
2. Book slot 1 time
3. Try booking same slot again
4. Verify error: "This time slot is already booked"

---

## ⚙️ Configuration

### Settings Page
**Dashboard:** TRM Counseling → Settings

**Session Duration:**
- Member duration (default: 30 min)
- Non-member with donation (default: 30 min)
- Non-member without donation (default: 15 min)

**Working Hours:**
- Start time (default: 09:00)
- End time (default: 17:00)

**Payment Methods:**
- Enable Stripe (checkbox)
- Enable PayPal (checkbox)
- Enable Offline (checkbox)
- Default gateway selection

**Payment Credentials:**
- Stripe keys (public & secret)
- PayPal credentials (client ID & secret)
- Offline instructions (text area)

---

## 🔐 Security

- **Input Sanitization:** All user input sanitized with WordPress functions
- **CSRF Protection:** WordPress nonces on all AJAX requests
- **SQL Injection Prevention:** Prepared statements with wpdb
- **XSS Prevention:** Output escaping with esc_* functions
- **Authorization:** Role-based access control

**Payment Security:**
- API keys stored in database settings (not in code)
- No credit card data stored locally
- Server-side payment verification
- PCI compliance for Stripe/PayPal integration

---

## 📧 Notifications

### User Receives:
- Booking confirmation with:
  - Event name
  - Date and time
  - Session duration
  - Booking fee (if applicable)
  - Payment method
  - Payment status
  - Payment instructions (if offline)

### Admin Receives:
- New booking notification with:
  - All booking details
  - User contact information
  - Payment information
  - Payment reference

---

## 🐛 Troubleshooting

### Slots Not Showing
**Check:**
- Event is published
- Event date is today or future
- Working hours are configured
- Event schedule is active

**Fix:**
- Re-save event
- Clear browser cache
- Verify date/time settings

### Bookings Not Created
**Check:**
- Event capacity not full
- User filled all required fields
- No JavaScript errors in console

**Fix:**
- Check browser console for errors
- Verify event settings
- Test with different event

### Emails Not Sending
**Check:**
- WordPress email configuration
- Notification email in settings

**Fix:**
- Install WP Mail SMTP plugin
- Test email delivery
- Check spam folder

### Payment Not Processing
**Check:**
- Payment gateway enabled
- API keys configured correctly
- Payment method selected

**Fix:**
- Verify credentials in Settings
- Use payment gateway's test mode first
- Check browser console for errors

---

## 📝 Code Examples

### Get Available Slots (PHP)
```php
$slots = TRM_Booking::get_available_slots('2025-01-15', 123);
// Returns: ["09:00", "09:45", "10:30", ...]
```

### Create Booking (PHP)
```php
$data = array(
  'event_id' => 123,
  'full_name' => 'John Doe',
  'phone' => '555-1234',
  'email' => 'john@example.com',
  'booking_date' => '2025-01-15',
  'booking_time' => '10:00',
  'is_member' => 1,
  'donation_amount' => 25.00,
  'payment_method' => 'offline'
);
$result = TRM_Booking::create_booking($data);
// Returns: array(success => true, booking_id => 456, ...)
```

### Get Event (PHP)
```php
$event = TRM_Events::get_event(123);
// Returns: array with event details
```

### Process Payment (PHP)
```php
$result = TRM_Payment::process_payment(456, 50.00, 'stripe', $_POST);
// Returns: array(success => true, transaction_id => '...')
```

---

## 📚 Documentation

- **SETUP_GUIDE.md** - Complete setup and configuration instructions
- **TESTING_CHECKLIST.md** - Comprehensive testing scenarios
- **README.md** - This file

---

## 🔄 Workflow

```
User Books
  ↓
Select Event & Date
  ↓
Select Available Time Slot (respects capacity)
  ↓
Enter Personal Information
  ↓
Select Payment Method (if required)
  ↓
Process Payment (if required)
  ↓
Booking Confirmed
  ↓
Email Sent to User & Admin
  ↓
Booking Appears in Admin Dashboard
```

---

## 📊 Database Queries

### Get All Bookings for Event
```sql
SELECT * FROM wp_trm_bookings 
WHERE event_id = 123 
AND status IN ('confirmed', 'pending')
ORDER BY booking_date, booking_time;
```

### Get Revenue by Payment Method
```sql
SELECT 
  payment_method, 
  COUNT(*) as count,
  SUM(booking_fee + donation_amount) as total
FROM wp_trm_bookings
WHERE payment_status = 'completed'
GROUP BY payment_method;
```

### Get Fully Booked Slots
```sql
SELECT event_id, booking_date, booking_time, COUNT(*) as count
FROM wp_trm_bookings
WHERE status IN ('confirmed', 'pending')
GROUP BY event_id, booking_date, booking_time
HAVING count >= 1; -- or capacity value
```

---

## 🚀 Performance Tips

1. Use database indexes on `event_id` and `booking_date`
2. Archive old bookings periodically
3. Set reasonable capacity limits (avoid 1000+ per slot)
4. Enable WordPress object caching
5. Limit recurring event duration to avoid slot calculation slowdown

---

## 🔄 Version History

**1.1.0** (Color Customization & Manual Updates)
- Color customization for time slot selection (background and text colors)
- Color settings in admin dashboard with live preview
- Manual "Check for Updates" button in Settings
- Update check clears cache and forces GitHub version check
- Displays current version in Settings page

**1.0.0** (Initial Release)
- Event/Webinar management
- Single & multiple booking capacity
- Stripe, PayPal, Offline payment support
- Email notifications
- Admin dashboard
- Recurring events support
- Automatic update checking from GitHub

---

## 📞 Support

For issues or questions:
1. Check SETUP_GUIDE.md
2. Check TESTING_CHECKLIST.md
3. Enable WordPress debug logging
4. Contact development team

---

## 📄 License

GPL v2 or later - See LICENSE file for details

---

## 👤 Author

**Ebenezer Caurie**  
Trinity Revival Ministry

---

**Last Updated:** November 10, 2025
