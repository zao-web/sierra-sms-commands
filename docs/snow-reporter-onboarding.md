---
title: Snow Reporter Onboarding Guide
order: 3
category: Getting Started
screens: [user-edit-php, settings_page_sierra-sms-commands]
---

# Snow Reporter Onboarding Guide

Step-by-step guide for administrators to onboard new snow reporters and train them on SMS commands.

## Overview

This guide walks through the complete process of:
1. Creating a snow reporter user account
2. Assigning and configuring their phone number
3. Testing their SMS access
4. Training them on command usage
5. Monitoring their first shifts

## Prerequisites

Before onboarding snow reporters:
- ✅ SMS Commands plugin installed and configured
- ✅ SMS provider setup complete (Telnyx, Textbelt, or Twilio)
- ✅ Webhook tested and working
- ✅ Confirmation mode configured

## Step 1: Create User Account

### In WordPress Admin

1. Navigate to **Users → Add New**
2. Fill in required fields:
   - **Username**: First initial + last name (e.g., `jsmith`)
   - **Email**: Their work email address
   - **First Name**: Full first name
   - **Last Name**: Full last name
   - **Send User Notification**: Check this to email login credentials
3. **Role**: Select **Snow Reporter**
4. **Password**: Click "Show password" and use strong auto-generated password
5. Click **Add New User**

### Best Practices

- Use consistent username format across all reporters
- Always use work email addresses (not personal)
- Enable user notification so they receive credentials
- Document username/password in secure location (password manager)

## Step 2: Assign Phone Number

### Get Phone Number

1. Ask snow reporter for their mobile phone number
2. Verify it's a number they regularly check
3. Confirm they have reliable cell coverage on the mountain
4. Format in E.164 format: `+14155551234`
   - Include country code (+1 for US)
   - No spaces, dashes, parentheses, or other formatting

### Add to User Profile

1. Go to **Users → All Users**
2. Find the new snow reporter user
3. Click **Edit**
4. Scroll to **Snow Reporter Information** section
5. Enter phone number in **Phone Number** field
6. Verify format is correct: `+14155551234`
7. Click **Update User**

### Verification

System automatically checks:
- ✅ Phone number format is valid E.164
- ✅ No duplicate phone numbers exist
- ✅ Number starts with valid country code

If error appears, correct format and try again.

## Step 3: Test SMS Access

### Send Test Command

**Option A: Have Snow Reporter Test**

Send them this text message to forward to the provider number:
```
Text "help" to [your provider phone number]
```

They should receive:
```
SMS Commands:

• open [type] [name] - Open a lift/trail/gate
• close [type] [name] - Close a lift/trail/gate
• status - Get current status
• undo - Reverse last command
• help - Show this message

Examples:
• open lift grandview
• close broadway
• open gate 5
```

**Option B: Admin Test from Their Number**

If possible, send test from their phone:
1. Text: `status`
2. Should receive current lift/trail/gate counts
3. Text: `close gate 1` (or any test item)
4. Should receive confirmation
5. Check admin - verify status updated
6. Text: `undo` (if immediate mode)
7. Verify status reverted

### Troubleshooting Test Failures

**No response received:**
- Verify phone number format in user profile
- Check SMS provider dashboard for delivery status
- Ensure webhook URL is correct and accessible
- Test webhook manually with curl

**Permission denied:**
- Verify user has Snow Reporter role
- Check phone number is saved correctly
- Confirm no duplicate phone numbers

**Wrong number format error:**
- Must be E.164: `+14155551234`
- Remove all spaces, dashes, parentheses
- Include country code

## Step 4: Training Session

### Pre-Training Setup

**Prepare:**
- Print command reference sheet
- Have test phone ready
- Set aside 15-20 minutes
- Use test/training items if available

### Training Agenda

#### 1. Explain the System (2 minutes)

"You can now update lift and trail status by sending text messages. No app needed - just regular SMS to this number: [provider phone number]."

**Key points:**
- Updates website immediately
- All commands are logged for safety/legal
- Simple, natural language
- Works from anywhere with cell coverage

#### 2. Basic Command Format (5 minutes)

Show them the pattern:
```
[action] [type] [name]
```

**Examples:**
- `open lift grandview`
- `close trail broadway`
- `open gate 5`

**Have them try:**
1. Text: `status` (see current state)
2. Text: `close gate 1` (or test item)
3. Verify confirmation received
4. Check website together to see update

#### 3. Shortcuts & Tips (3 minutes)

**Type is optional if name is unique:**
- `open grandview` (system knows it's a lift)
- `close broadway` (system knows it's a trail)

**Typos are okay:**
- `close brodway` → Matches "Broadway"
- `open grandvew` → Matches "Grandview"

**Ambiguous names require specificity:**
- `close main` → May match multiple items
- `close trail main street` → Specific

#### 4. Confirmation Mode (5 minutes)

Explain your configured mode:

**Immediate + Undo Mode:**
- Command executes right away
- Get confirmation with "Reply UNDO within 2 min to reverse"
- Text `undo` if you made a mistake
- Only last command can be undone

**Two-Step Mode:**
- System asks for confirmation
- Reply `yes` to confirm or `no` to cancel
- No undo needed (confirm before execution)

**Have them practice undo** (if immediate mode):
1. Text: `open gate 1`
2. Wait for confirmation
3. Text: `undo`
4. Verify reversion message
5. Check website to confirm

#### 5. Other Commands (2 minutes)

**Status command:**
```
status
```
Shows current lift/trail/gate counts. Use at start/end of shift.

**Help command:**
```
help
```
Shows command list. Use anytime as reminder.

### Training Checklist

Confirm they can:
- [ ] Send basic open/close commands
- [ ] Receive and understand confirmations
- [ ] Use undo (if immediate mode)
- [ ] Handle ambiguous match errors
- [ ] Access help command
- [ ] Know who to contact for issues

## Step 5: First Day Monitoring

### Before First Shift

**Send reminder text:**
```
Quick reminder: Text commands to [phone number]
• open/close lift/trail/gate [name]
• status - see current state
• help - see all commands
Contact [admin] if any issues!
```

### During First Shift

**Check-in points:**
1. **30 minutes in**: Text them asking how it's going
2. **Mid-shift**: Review audit log for their commands
3. **End of shift**: Follow up on any issues

**Look for in audit log:**
- Commands being received
- Successful executions
- Any permission errors
- Undo usage patterns

### After First Shift

**Debrief:**
- How did SMS commands work?
- Any confusion or errors?
- Suggestions for improvement?
- Additional training needed?

**Document:**
- Issues encountered
- Training gaps identified
- Follow-up actions needed

## Managing Multiple Snow Reporters

### Shift Handoffs

**Best practice for clear handoffs:**
1. Outgoing reporter texts: `status`
2. Shares current state with incoming reporter
3. Incoming reporter verifies they can access system
4. Both document handoff time

### Team Communication

**Set expectations:**
- All reporters can send commands simultaneously
- Last command wins if conflicting
- Coordinate major changes via radio/phone
- Use audit log to see who changed what

### Monitoring Team Activity

**Daily review:**
1. Go to Resort Data → Audit Logs
2. Filter by Source: `sms:*` (all SMS providers)
3. Filter by Date: Today
4. Review for:
   - Unusual patterns
   - Errors
   - High undo usage (may indicate training gap)

## Common First-Day Issues

### "I'm not getting responses"

**Check:**
1. Phone number in their profile matches their actual phone
2. Format is E.164: `+14155551234`
3. No typos in provider phone number
4. Phone has cell coverage

**Fix:**
- Verify phone number in Users → Edit User
- Re-save if needed
- Send test from admin panel

### "It says permission denied"

**Check:**
1. User has Snow Reporter role
2. Phone number is assigned
3. No duplicate phone numbers

**Fix:**
- Edit user, verify role
- Check phone number field is filled
- Search all users for duplicate number

### "I keep getting 'multiple matches found'"

**This is correct behavior** when name is ambiguous.

**Train them to be more specific:**
- Instead of: `close main`
- Use: `close trail main street`

### "Undo isn't working"

**Immediate mode only:**
- Undo window is 2 minutes (default)
- Only last command can be undone
- Check configuration in Settings → SMS Commands

**Two-step mode:**
- No undo available
- Must cancel before confirming
- Or send opposite command manually

## Offboarding Snow Reporters

### Temporary (End of Season)

1. Change their role to **Subscriber**
   - Keeps account active
   - Removes resort data access
   - Can be re-enabled next season

2. **Optional:** Remove phone number
   - Prevents accidental SMS commands
   - Easy to re-add next season

### Permanent (Termination/Departure)

1. **Immediate**: Change role to Subscriber
2. Remove phone number from profile
3. Document departure in notes
4. **Optional**: Delete user after retention period
   - Audit logs preserved even after deletion
   - Consider keeping account for historical reference

### Exit Interview

**Ask:**
- How was SMS system?
- What improvements would help?
- Any technical issues unresolved?
- Documentation clear enough?

**Document feedback** for next season improvements.

## Seasonal Preparation

### Pre-Season Checklist

**Two weeks before opening:**
- [ ] Review returning snow reporter list
- [ ] Verify phone numbers still accurate
- [ ] Re-activate seasonal accounts (change role back to Snow Reporter)
- [ ] Send welcome back message with provider phone number
- [ ] Test webhook and providers
- [ ] Review any mid-season provider changes

**One week before opening:**
- [ ] Onboard new snow reporters
- [ ] Training sessions scheduled
- [ ] Test commands from each reporter's phone
- [ ] Verify audit log working

**Opening day:**
- [ ] Monitor SMS activity closely
- [ ] Available for immediate support
- [ ] Review audit log at end of day

### Post-Season Review

**Analyze:**
- Total SMS commands sent
- Undo usage rate (high = training issue?)
- Error patterns
- Most active reporters
- Provider costs vs. alternatives

**Improve:**
- Update training materials
- Refine command aliases
- Adjust confirmation modes
- Document lessons learned

## Getting Help

### For Administrators

**Plugin issues:**
- Check Settings → SMS Commands for configuration
- Review webhook logs in provider dashboard
- Test webhook manually with curl
- Verify SMS provider has credits/active account

**User issues:**
- Check user's role and phone number
- Review audit log for their commands
- Test from their phone if possible

### For Snow Reporters

**Provide this contact info:**
```
SMS Commands Support

📱 Provider Number: [your phone number]
🆘 Support Contact: [admin name/number]
📖 Command Reference: [link to docs]

Quick Help:
• Text "help" for command list
• Text "status" to see current state
• Call [admin] if not working
```

## Resources

- [SMS Command Reference](./command-reference.md) - All available commands
- [Setup Guide](./setup-guide.md) - Provider configuration
- [Troubleshooting Guide](./troubleshooting.md) - Common issues
- [Snow Reporter Role Documentation](../../sierra-resort-data/docs/snow-reporter-role.md)

## Onboarding Template

**Copy/paste for new snow reporter setup:**

```
Snow Reporter Onboarding - [Name]

Date: _______________
Onboarded by: _______________

✅ Step 1: User Account Created
   - Username: _______________
   - Email: _______________
   - Password sent: Yes / No

✅ Step 2: Phone Number Assigned
   - Phone: _______________
   - Format verified: Yes / No

✅ Step 3: SMS Access Tested
   - Help command: Success / Failed
   - Status command: Success / Failed
   - Test update: Success / Failed
   - Undo test (if applicable): Success / Failed

✅ Step 4: Training Completed
   - Date: _______________
   - Duration: _____ minutes
   - Materials provided: Yes / No
   - Questions answered: Yes / No

✅ Step 5: First Shift Monitoring
   - First shift date: _______________
   - Mid-shift check-in: Complete / Issues
   - End-of-shift debrief: Complete / Issues

Notes:
_________________________________
_________________________________
_________________________________

Follow-up needed: Yes / No
If yes, describe: _________________________________
```
