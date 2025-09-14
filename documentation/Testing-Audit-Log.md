# Testing the Security Audit Log Feature

This document provides instructions for testing the Security Audit Log functionality to ensure it's working correctly.

## Prerequisites

1. A WordPress installation with the Kura AI plugin activated
2. Administrator access to the WordPress dashboard
3. Access to the WordPress database (for verification)

## Test Cases

### 1. User Events

#### Test User Creation
1. Navigate to Users > Add New
2. Create a new user with any role
3. Go to KuraAI Security > Logs > Security Audit Log
4. Verify a log entry appears with event type "user" and action "created"

#### Test Role Change
1. Navigate to Users > All Users
2. Edit an existing user and change their role
3. Check the Security Audit Log for an entry with event type "user" and action "role_changed"

### 2. Authentication Events

#### Test Login Success
1. Log out of WordPress
2. Log back in with valid credentials
3. Check the Security Audit Log for an entry with event type "auth" and action "login_success"

#### Test Login Failure
1. Log out of WordPress
2. Attempt to log in with incorrect credentials
3. Check the Security Audit Log for an entry with event type "auth" and action "login_failed"

### 3. Plugin/Theme Events

#### Test Plugin Activation
1. Navigate to Plugins > Installed Plugins
2. Deactivate a plugin, then activate it again
3. Check the Security Audit Log for entries with event type "plugin" and actions "deactivated" and "activated"

#### Test Theme Switch
1. Navigate to Appearance > Themes
2. Switch to a different theme
3. Check the Security Audit Log for an entry with event type "theme" and action "switched"

### 4. Content Events

#### Test Post Creation
1. Navigate to Posts > Add New
2. Create and publish a new post
3. Check the Security Audit Log for an entry with event type "content" and action "created"

#### Test Post Update
1. Edit an existing post and update it
2. Check the Security Audit Log for an entry with event type "content" and action "updated"

#### Test Post Deletion
1. Move a post to trash
2. Check the Security Audit Log for an entry with event type "content" and action "trashed"

### 5. Site Configuration

#### Test Option Update
1. Navigate to Settings > General
2. Change a setting (e.g., Site Title)
3. Check the Security Audit Log for an entry with event type "option" and action "updated"

### 6. UI Features

#### Test Filtering
1. Go to KuraAI Security > Logs > Security Audit Log
2. Test each filter option (Event Type, Severity, Date Range)
3. Verify results are filtered correctly

#### Test Search
1. Enter a search term in the search box
2. Verify results match the search term

#### Test View Details
1. Click "View Details" on any log entry
2. Verify the modal shows complete log information

#### Test Export
1. Click "Export to CSV"
2. Verify the downloaded file contains all log entries
3. Repeat for "Export to JSON"

### 7. Settings

#### Test Logging Scope
1. Click "Show Settings"
2. Change the logging scope
3. Perform actions and verify logging behavior changes accordingly

#### Test Email Reporting
1. Configure email reporting (e.g., daily)
2. Use WordPress cron testing plugin or wait for the scheduled time
3. Check the configured email address for the report

## Database Verification

To verify logs are being stored correctly in the database:

1. Access your WordPress database using phpMyAdmin or similar tool
2. Locate the `{prefix}_kura_ai_audit_logs` table
3. Verify log entries contain all required fields:
   - id
   - timestamp
   - user_id
   - username
   - ip_address
   - event_type
   - action
   - summary
   - details (JSON)
   - severity

## Troubleshooting

If tests fail, check the following:

1. Ensure the plugin is properly activated
2. Check WordPress error logs for PHP errors
3. Verify database table was created successfully
4. Check user permissions (must have appropriate capabilities)
5. Ensure WordPress hooks are firing correctly

## Performance Testing

To test performance impact:

1. Use a tool like Query Monitor to measure page load times before and after enabling audit logging
2. Test with different logging scopes to measure impact
3. Generate a large number of logs (>1000) and test UI responsiveness

## Security Testing

1. Verify non-admin users cannot access audit logs
2. Attempt to modify log entries directly in the database to test tamper resistance
3. Test that clearing logs creates a "logs_cleared" event