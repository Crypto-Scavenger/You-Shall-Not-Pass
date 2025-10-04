# Add Some Solt

A comprehensive WordPress SALT key management plugin that automates the rotation of WordPress authentication and security keys with scheduling, notifications, and audit logging.

**Version:** 1.0.0  
**Requires:** WordPress 6.8+  
**Requires PHP:** 7.4+  
**License:** GPL v2 or later

---

## Description

Add Some Solt provides complete management of your WordPress SALT keys with features including:

- View current SALT keys from wp-config.php
- Generate and replace keys with one click
- Schedule automatic key rotation (daily, weekly, monthly, quarterly, biannually)
- Set specific day and time for scheduled changes
- Email notifications when keys are changed
- Email reminders before scheduled changes
- Complete audit log of all key changes
- Test functionality to verify scheduling system
- Secure wp-config.php backup before changes

## What Are SALT Keys?

SALT keys are cryptographic constants in WordPress that secure authentication cookies and password hashes. They add extra security by making it impossible to crack passwords or hijack user sessions even if cookies are intercepted.

WordPress uses 8 keys:
1. AUTH_KEY
2. SECURE_AUTH_KEY
3. LOGGED_IN_KEY
4. NONCE_KEY
5. AUTH_SALT
6. SECURE_AUTH_SALT
7. LOGGED_IN_SALT
8. NONCE_SALT

**Important:** Changing SALT keys logs out all users immediately, requiring them to log back in.

---

## File Structure

```
add-some-solt/
├── add-some-solt.php           # Main plugin file
├── README.md                    # This file
├── uninstall.php               # Cleanup on uninstall
├── index.php                   # Security stub
├── assets/
│   ├── admin.css               # Admin styling
│   ├── admin.js                # Admin JavaScript
│   └── index.php               # Security stub
└── includes/
    ├── class-database.php      # Database operations
    ├── class-salt-manager.php  # SALT key management
    ├── class-scheduler.php     # WordPress Cron scheduling
    ├── class-core.php          # Core functionality
    ├── class-admin.php         # Admin interface
    └── index.php               # Security stub
```

---

## Installation

### Method 1: WordPress Admin

1. Download the plugin as a ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

### Method 2: Manual Installation

1. Download and extract the plugin
2. Upload the `add-some-solt` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "Add Some Solt" and click "Activate"

### Method 3: FTP Upload

1. Extract the plugin ZIP file
2. Upload the `add-some-solt` folder to `/wp-content/plugins/` via FTP
3. Activate the plugin through the WordPress Admin Plugins page

---

## Usage Guide

### Accessing the Plugin

1. After activation, go to **Tools → SALT Keys** in WordPress Admin
2. You'll see the main management interface

### View Current Keys

1. Click the "Show Keys" button to reveal your current SALT keys
2. Keys are hidden by default for security
3. Click "Hide Keys" to conceal them again

### Generate New Keys Manually

1. Click "Generate & Replace Keys Now" button
2. Confirm the action (all users will be logged out)
3. New keys are fetched from WordPress API
4. wp-config.php is backed up automatically
5. Keys are updated and old backup is removed

### Schedule Automatic Key Changes

1. Check "Enable Scheduled Changes"
2. Select frequency:
   - **Daily:** Changes every day at specified time
   - **Weekly:** Changes on specific day of week
   - **Monthly:** Changes on specific day of month
   - **Quarterly:** Changes on specified day in Jan, Apr, Jul, Oct
   - **Biannually:** Changes on specified day in Jan and Jul
3. Set the day (if applicable)
4. Set the time (24-hour format in server timezone)
5. Click "Save Settings"

### Email Notifications

1. Check "Enable Notifications" to receive emails when keys change
2. Enter email address (defaults to admin email)
3. Check "Enable Reminders" for advance warning emails
4. Set reminder days before change (1-30 days)
5. Click "Save Settings"

### Test the Scheduling System

1. Scroll to "Test Scheduled Change" section
2. Click "Test Schedule (Changes Keys in 1 Minute)"
3. Confirm the action
4. Wait 1 minute and check the Change History log
5. Verify the scheduled change executed correctly

### View Change History

The Change History table shows:
- Date and time of each change
- Type of change (manual, scheduled, test)
- User who initiated the change
- Additional notes

---

## Features in Detail

### Automated Scheduling

The plugin uses WordPress Cron to schedule key changes:
- Calculates next run time based on frequency
- Handles edge cases (month-end dates, leap years)
- Automatically reschedules after each change
- Clears old schedules when settings change

### Email System

Two types of emails:
1. **Change Notifications:** Sent immediately after keys change
2. **Reminder Emails:** Sent X days before scheduled change

Both include:
- Timestamp of change/scheduled change
- Link to admin page
- Information about user logout requirement

### Security Features

- Nonce verification on all forms
- Capability checks (requires `manage_options`)
- wp-config.php backup before changes
- Automatic restoration if update fails
- Keys hidden by default in admin
- All database queries use prepared statements

### Database Architecture

Two custom tables created:

**{prefix}_ass_settings**
- Stores all plugin settings
- Uses unique keys for fast lookups
- Cached with WordPress Transients (12-hour expiration)

**{prefix}_ass_change_log**
- Audit log of all key changes
- Stores user ID, timestamp, type, and notes
- Indexed by date for fast queries

### File Permission Handling

The plugin:
- Checks if wp-config.php is writable
- Shows clear status messages
- Provides file path for permission adjustments
- Creates timestamped backups
- Restores backup if write fails

---

## Requirements

### Server Requirements

- **WordPress:** 6.8 or higher
- **PHP:** 7.4 or higher
- **File Permissions:** wp-config.php must be writable during key changes

### Recommended Settings

- WordPress Cron enabled (not disabled)
- Server timezone properly configured
- Email functionality working (wp_mail)
- Adequate file permissions on wp-config.php

---

## Troubleshooting

### wp-config.php Not Writable

**Problem:** Error message about file permissions

**Solution:**
1. Temporarily set wp-config.php to 644 or 664
2. Run the key change
3. Return permissions to 444 or 440 for security
4. Some servers require 644 permanently

### Scheduled Changes Not Running

**Problem:** Keys don't change at scheduled time

**Solution:**
1. Verify WordPress Cron is not disabled
2. Check if site receives regular traffic (triggers Cron)
3. Consider using a server cron job to trigger wp-cron.php
4. Use the test function to verify scheduling works

### Email Notifications Not Received

**Problem:** No emails arriving

**Solution:**
1. Verify email address is correct
2. Check spam/junk folders
3. Test WordPress email with another plugin
4. Configure SMTP if wp_mail() is not working
5. Check server email logs

### Keys Not Found in wp-config.php

**Problem:** Plugin shows empty keys

**Solution:**
1. Verify SALT keys are defined in wp-config.php
2. Check if wp-config.php exists in standard location
3. Ensure constants use correct names
4. Check file read permissions

---

## FAQ

**Q: Will changing SALT keys break my site?**  
A: No, but it will log out all users. They'll need to log back in.

**Q: How often should I change SALT keys?**  
A: Quarterly or biannually is recommended. After security breaches, change immediately.

**Q: Can I change keys during high traffic?**  
A: Yes, but users will be logged out. Schedule during low-traffic periods.

**Q: What happens if the key change fails?**  
A: The backup is automatically restored and an error is logged.

**Q: Are the keys stored in the database?**  
A: No, they're only stored in wp-config.php. The plugin reads them when needed.

**Q: Can I disable automatic changes temporarily?**  
A: Yes, uncheck "Enable Scheduled Changes" and save settings.

**Q: Does this work with multisite?**  
A: Yes, but it affects all sites in the network (shared wp-config.php).

**Q: Will this conflict with other plugins?**  
A: No, it only modifies wp-config.php and uses custom database tables.

---

## Technical Details

### Cron Implementation

The plugin uses single events rather than recurring schedules:
- Calculates next run time dynamically
- Reschedules itself after execution
- Clears old schedules when settings change
- Supports complex scheduling (quarterly, biannually)

### Performance Optimization

- Settings cached with WordPress Transients (12-hour TTL)
- Lazy loading of settings (only when needed)
- Defensive table existence checks
- Minimal database queries
- Admin assets only load on plugin page

### Security Measures

- All user input sanitized
- All output escaped
- Prepared statements for database queries
- Capability checks in both render and save methods
- Nonce verification on all forms
- No external service dependencies

### WordPress Coding Standards

This plugin follows:
- WordPress PHP Coding Standards
- WordPress JavaScript Coding Standards
- Security best practices from WordPress Security Team
- Database optimization guidelines
- Proper use of WordPress APIs

---

## Uninstallation

### Standard Uninstall

1. Deactivate the plugin
2. Delete the plugin
3. If "Cleanup on Uninstall" is enabled, all data is removed

### Data Removed on Uninstall (if enabled)

- Custom database tables
- All settings
- Change history logs
- Scheduled cron events
- Cached data

### Data Retained (if cleanup disabled)

- Database tables and settings remain
- Can reinstall plugin without losing history

---

## Privacy & GDPR Compliance

### Data Collection

The plugin stores:
- Plugin settings (schedule, email preferences)
- Change history (timestamps, user IDs)
- No personal data beyond admin user IDs
- No external service connections

### Data Storage

- All data stored locally in WordPress database
- Custom tables (not wp_options)
- No third-party data transmission
- No tracking or analytics

### User Rights

- Admins can export change logs manually
- All data deletable via uninstall
- No personally identifiable information collected

---

## Changelog

### Version 1.0.0
- Initial release
- View current SALT keys
- Generate and replace keys manually
- Schedule automatic key changes (5 frequencies)
- Set specific day and time for changes
- Email notifications on key changes
- Email reminders before scheduled changes
- Complete audit log with change history
- Test functionality for scheduling
- Secure wp-config.php backup system
- Custom database tables
- WordPress Cron integration
- Full security implementation
- Comprehensive error handling

---

## Support

For issues or questions:
1. Check the Troubleshooting section
2. Review the FAQ
3. Verify Requirements are met
4. Test on staging site first

---

## License

This plugin is licensed under the GPL v2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
