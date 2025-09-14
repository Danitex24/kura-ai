# Security Audit Log

The Security Audit Log module provides comprehensive monitoring and logging of security-related events in your WordPress site. This feature helps you maintain a tamper-resistant record of all security-relevant activities.

## Features

### Event Monitoring

The audit log captures events in the following categories:

- **User Events**: User creation, role changes, profile edits
- **Authentication Events**: Login attempts (successful and failed), logouts
- **Plugin/Theme Events**: Installation, activation, deactivation, updates
- **WordPress Core**: Updates and modifications
- **Content Events**: Post/page creation, edits, deletions
- **Site Configuration**: Settings changes and option updates

### Log Details

Each log entry includes:

- Timestamp (UTC)
- User ID and username (or system)
- Event type and action
- Brief summary
- Severity level
- IP address
- Full details (JSON)

### Tamper Resistance

The audit log is designed with tamper resistance in mind:

- Append-only logging (no silent deletion)
- Deletion events are themselves recorded
- Optional remote email reporting for off-site backup

## Configuration

Access audit log settings from the Logs page by clicking the "Security Audit Log" tab and then the "Show Settings" button.

### Available Settings

- **Logging Scope**: Choose between logging all events or only significant events
- **Email Reporting**: Configure frequency (daily, weekly, monthly, off) and recipients
- **Retention Policy**: Set how long logs should be kept
- **Append-Only Mode**: Enable/disable append-only enforcement

## Viewing Logs

1. Navigate to **KuraAI Security > Logs**
2. Click the "Security Audit Log" tab
3. Use filters to narrow down results:
   - Event Type
   - Severity
   - Date Range
   - Search
4. Click "View Details" to see complete information for any log entry

## Exporting Logs

Export options are available for compliance and record-keeping:

- **CSV Export**: Click "Export to CSV" for a spreadsheet-compatible format
- **JSON Export**: Click "Export to JSON" for programmatic processing

## Developer Hooks

The audit log module provides hooks for developers to extend functionality:

### Actions

```php
// Log a custom security event
do_action('kura_ai_audit_log_event', $event_type, $action, $summary, $details, $severity, $user_id);

// Triggered after logs are cleared
do_action('kura_ai_audit_logs_cleared');
```

### Filters

```php
// Filter log data before saving
add_filter('kura_ai_audit_log_data', function($log_data) {
    // Modify log data
    return $log_data;
});

// Filter whether an event should be logged
add_filter('kura_ai_should_log_event', function($should_log, $event_type, $action, $severity) {
    // Custom logic to determine if event should be logged
    return $should_log;
}, 10, 3);

// Filter email report content
add_filter('kura_ai_audit_email_content', function($content, $logs, $period) {
    // Customize email report content
    return $content;
}, 10, 3);
```

## Best Practices

1. **Regular Review**: Check audit logs regularly for suspicious activity
2. **Export Archives**: Maintain exported copies for compliance requirements
3. **Configure Email Reports**: Set up email reporting for critical events
4. **Retention Balance**: Find a balance between storage constraints and compliance needs
5. **Investigate Anomalies**: Follow up on unusual patterns or unexpected events

## Troubleshooting

- **Missing Events**: Check logging scope settings
- **Performance Issues**: Adjust retention policy if database grows too large
- **Email Reports Not Arriving**: Verify email settings and check spam folders