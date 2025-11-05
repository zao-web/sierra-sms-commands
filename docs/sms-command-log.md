---
title: SMS Command Log
order: 2
category: SMS Commands
screens: [resort-data_page_sierra-sms-command-log]
---

# SMS Command Log

View a complete history of all changes made via SMS text message commands.

## What This Shows

The SMS Command Log is a filtered view of the main Audit Logs showing only changes made via text message. It includes:

- Trail status changes (open/closed)
- Lift status changes (open/closed)
- Gate status changes (open/closed)
- Park feature status changes
- Trail grooming updates

## Log Information

Each entry shows:

- **Date/Time**: When the command was sent
- **Item**: Name of the trail, lift, gate, etc.
- **Type**: Category (Lift, Trail, Gate, Park Feature)
- **Field**: What was changed (status, groomed)
- **From**: Previous value
- **To**: New value
- **User**: Who sent the SMS command
- **Provider**: SMS service used (Twilio, Telnyx, Textbelt)
- **IP Address**: Origin of the webhook request (hidden by default)

## Filtering the Log

Click the filter icon to narrow down entries:

### By Type
Show only specific categories:
- Lifts
- Trails
- Gates
- Park Features

### By Provider
Filter by SMS service:
- Twilio
- Telnyx
- Textbelt

### By User
Show commands from specific snow reporters or staff members.

## Searching

Use the search box to find:
- Specific trail or lift names
- User names
- Date/time ranges
- Status values

## Exporting SMS Commands

Click **Export to CSV** to download the SMS command history.

The export includes all columns plus IP addresses for security review.

### CSV Contents
- Date/Time
- Item name
- Type (Trail, Lift, Gate, etc.)
- Field changed
- Old value
- New value
- User who sent command
- SMS provider
- IP address

## Why Track SMS Commands?

### Operational Benefits
1. **Accountability**: Know who made each change
2. **Timing**: See when terrain was opened or closed
3. **Patterns**: Identify frequently updated areas
4. **Training**: Review new staff member's command usage
5. **Audit Trail**: Legal compliance for resort operations

### Security Benefits
1. **Unauthorized Access**: Detect suspicious commands
2. **IP Verification**: Validate webhook sources
3. **Provider Monitoring**: Ensure commands come from approved services
4. **Usage Analysis**: Track command frequency by user

## Common Use Cases

### Morning Operations Review
Check what was opened/groomed at the start of the day:
1. Filter by Date/Time to today
2. Search for "open" or "groomed"
3. Review all changes made during morning ops

### Staff Performance
Monitor a specific snow reporter:
1. Filter by User name
2. Review their command accuracy
3. Export for training or performance reviews

### Security Audit
Verify all SMS commands are legitimate:
1. Enable IP Address column
2. Check for unusual IP addresses
3. Filter by Provider to ensure approved services only

### Grooming Report
Generate daily grooming log:
1. Filter by Field = "groomed"
2. Filter by Date to today
3. Export CSV for operations records

## Related Pages

- **Audit Logs**: View ALL changes (not just SMS)
- **SMS Commands Reference**: Learn available commands
- **Trails Manager**: Manually update trail status and grooming

## Technical Details

- Data source: Same audit logs table, filtered by `change_source LIKE 'sms:%'`
- Real-time: Commands appear immediately after execution
- Retention: Logs stored indefinitely
- Performance: Indexed for fast filtering and searching
