# Database Fix Instructions

## Error: "Unknown column 'event_id' in 'field list'"

The `event_id` column may be missing from the `wp_trm_bookings` table if the plugin was updated without recreating the database.

## Quick Fix

Follow these steps to fix the issue:

### Option 1: Automatic Fix (Recommended)
1. Go to WordPress Admin Dashboard
2. The plugin will automatically check and update the database schema on the first admin page load
3. Try the booking form again

### Option 2: Manual Database Update via Admin
1. Go to WordPress Admin > Plugins
2. Deactivate the "TRM Counseling Session Scheduler" plugin
3. Reactivate the "TRM Counseling Session Scheduler" plugin
4. The plugin activation hook will recreate/update all tables
5. Try the booking form again

### Option 3: Direct SQL (Advanced Users Only)
If the columns still don't exist, run this SQL in your database:

```sql
-- Check if event_id column exists
ALTER TABLE `wp_trm_bookings` ADD COLUMN `event_id` bigint(20) NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `wp_trm_bookings` ADD KEY `event_id` (`event_id`);
```

## What Was Fixed

- Added automatic event_id column creation in database initialization
- Added admin hook to ensure database tables are up to date on every admin page load
- Improved time format handling (HH:MM vs HH:MM:SS) for consistent database queries
- Added error logging for troubleshooting

## Files Modified

1. `includes/class-trm-database.php` - Added column check and ALTER TABLE logic
2. `includes/class-trm-booking.php` - Improved time format handling and error logging
3. `trm-counseling-scheduler.php` - Added admin hook for database updates

## Testing the Fix

1. Clear browser cache
2. Go to the Booking Form page
3. Fill out the form completely:
   - Select Event
   - Select Date
   - Select Time
   - Enter Name
   - Enter Phone
   - Select Membership Status
   - Select/Skip Donation
4. Click "Complete Booking"
5. You should see the success message instead of the database error

## If Issues Persist

Check the WordPress error log at:
- `wp-content/debug.log` (if debug logging is enabled)

Look for errors containing "TRM Booking Insert Error" which will show the exact database issue.
