---
title: Troubleshooting SMS Commands
order: 5
category: Support
screens: [settings_page_sierra-sms-commands, sierra-resort-audit-logs]
---

# Troubleshooting SMS Commands

Common issues and solutions for SMS command system.

## Quick Diagnostic Checklist

Before diving into specific issues, run through this checklist:

- [ ] SMS provider configured in Settings → SMS Commands
- [ ] Provider has active account with credits/balance
- [ ] Webhook URL is correct and publicly accessible
- [ ] User has Snow Reporter role
- [ ] Phone number assigned in user profile
- [ ] Phone number format is E.164: `+14155551234`
- [ ] No duplicate phone numbers in system
- [ ] WordPress and plugins up to date

## Issue: No Response to SMS Commands

**Symptom:** Snow reporter sends command, gets no reply

### Check 1: Phone Number Configuration

1. Go to **Users → All Users**
2. Find the user
3. Click **Edit**
4. Scroll to **Snow Reporter Information**
5. Verify phone number matches their actual phone
6. Verify format: `+14155551234` (no spaces, dashes, parentheses)

**Fix if incorrect:**
- Correct format
- Click **Update User**
- Have them retry command

### Check 2: SMS Provider Status

**Telnyx:**
1. Log into Telnyx dashboard
2. Go to Messaging → Message History
3. Look for recent inbound messages
4. Check if webhook is being called

**Textbelt:**
1. Check API key is valid
2. Verify account has credits
3. Check if US number (required for inbound)

**Twilio:**
1. Log into Twilio console
2. Go to Messaging → Logs
3. Check for inbound messages
4. Look for webhook errors

### Check 3: Webhook Configuration

**Verify webhook URL format:**
```
https://yoursite.com/wp-json/sierra/v1/sms/webhook
```

**Requirements:**
- Must be HTTPS (not HTTP)
- No trailing slash
- Publicly accessible (not localhost or behind firewall)
- WordPress REST API enabled

**Test webhook manually:**
```bash
curl -X POST https://yoursite.com/wp-json/sierra/v1/sms/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

Should return JSON response, not 404.

### Check 4: Provider-Specific Issues

**Telnyx webhook not triggering:**
- Verify messaging profile has webhook URL set
- Check webhook is enabled
- Verify phone number assigned to profile
- Check webhook API version (should be v2)

**Textbelt inbound not working:**
- Only US numbers support inbound webhooks
- Verify webhook configured in Textbelt dashboard
- May need to upgrade plan for webhook support

**Twilio webhook not calling:**
- Go to Phone Numbers → Active Numbers
- Click your number
- Under Messaging, verify webhook URL
- Verify HTTP POST is selected
- Check "Webhooks, TwiML Bins, Functions" option selected

### Check 5: WordPress Permissions

1. Go to **Settings → Permalinks**
2. Click **Save Changes** (regenerates rewrite rules)
3. Test REST API:
```bash
curl https://yoursite.com/wp-json/sierra/v1/sms/webhook
```

Should not return 404 or 403.

## Issue: Permission Denied Errors

**Symptom:** User receives "You do not have permission" message

### Verify User Role

1. Go to **Users → All Users**
2. Find the user
3. Check **Role** column shows "Snow Reporter"
4. If not, click **Edit**
5. Change **Role** to **Snow Reporter**
6. Click **Update User**

### Verify Phone Number Ownership

System matches inbound phone number to user account.

**Check:**
1. Verify phone number in user profile
2. Ensure no duplicate phone numbers
3. Search all users for the same number

**Fix duplicates:**
1. Decide which user should have the number
2. Remove from other users
3. Have correct user retry

### Verify Capabilities

Snow Reporter role should have:
- `edit_sierra_lifts`
- `edit_sierra_trails`
- `edit_sierra_gates`
- `edit_sierra_park_features`
- `edit_sierra_snow_reports`

**If missing capabilities:**
1. Go to Settings → SMS Commands
2. Click "Reinstall Snow Reporter Role"
3. Or deactivate/reactivate sierra-resort-data plugin

## Issue: "Multiple Matches Found"

**Symptom:** User gets list of possible matches, command not executed

**This is correct behavior** when name is ambiguous.

### Example

Command: `close main`

Response:
```
Multiple matches found:
• Main Street Trail
• Main Park Jump
• West Main Gate

Please be more specific.
```

### Solution: Be More Specific

**Add type:**
- `close trail main` (narrows to trails only)
- `close gate main` (narrows to gates only)

**Add more name detail:**
- `close main street` (full name)
- `close west main gate` (full name)

**Use unique portion:**
- `close west main` (only one item has "west main")

### For Administrators

If users frequently hit this issue with specific items:

**Option 1: Rename items**
- Make names more distinctive
- Avoid common words like "main", "park", "central"

**Option 2: Train users**
- Provide cheat sheet of ambiguous names
- Show recommended commands for common items

## Issue: "No Trail Found Matching..."

**Symptom:** User gets "not found" error for valid item

### Check Item Exists

1. Go to **Resort Data → Trails** (or Lifts, Gates, etc.)
2. Search for the item name
3. Verify it exists and is published

**If not published:**
- Edit item
- Change Status to "Publish"
- Click **Update**

### Check Name Matching

System uses fuzzy matching but has limits.

**Too different:**
- `broadway` → Matches "Broadway Express Trail" ✅
- `brdwy` → Too different, won't match ❌

**Solution:**
- Train users on correct names
- Provide reference card with actual names
- Use enough of the name to match

### Check Type Filter

If user specified type, must be correct.

**Example:**
- `close lift broadway` → Fails if Broadway is a trail
- `close trail broadway` → Works

**Solution:**
- Omit type for unique names: `close broadway`
- Or correct type: `close trail broadway`

## Issue: Undo Not Working

**Symptom:** User texts `undo`, gets error or no effect

### Verify Confirmation Mode

Undo only works in **Immediate + Undo** mode.

**Check mode:**
1. Go to **Settings → SMS Commands**
2. Check **Confirmation Mode**
3. Should be "Immediate with Undo Window"

**If Two-Step Confirmation:**
- Undo not available (commands require confirmation before execution)
- User must manually reverse with opposite command

### Verify Undo Window

Default: 2 minutes (120 seconds)

**Check window:**
1. Go to **Settings → SMS Commands**
2. Check **Undo Window Duration**
3. Verify reasonable (30-600 seconds)

**Common issues:**
- Too short: User can't undo in time
- Expired: More than 2 minutes since command

### Verify Command Was Successful

Undo only works if previous command succeeded.

**Check audit log:**
1. Go to **Resort Data → Audit Logs**
2. Filter by user
3. Verify last command shows in log
4. If not logged, original command failed

### Multiple Commands Issue

Only the **last command** can be undone.

**Example:**
1. User: `open gate 1` (can undo)
2. User: `close broadway` (can undo this; gate 1 now locked in)
3. User: `undo` (only reverts broadway, not gate 1)

**Solution:**
- Undo immediately after command if needed
- Or manually reverse older commands

## Issue: Wrong Item Was Updated

**Symptom:** User intended to update Item A, but Item B was changed

### Verify Command

**Check audit log:**
1. Go to **Resort Data → Audit Logs**
2. Filter by user and recent time
3. See exactly what was matched and changed

### Common Causes

**Ambiguous partial match:**
- `close main` matched "West Main Gate" instead of "Main Street Trail"
- Solution: Be more specific

**Typo created different match:**
- `close grandview` (intended: Grandview Express)
- Matched: "Grand View Lodge Gate" (closer fuzzy match)
- Solution: Use more of actual name

**Type mismatch:**
- User thought item was a trail, it's actually a lift
- Solution: Training on correct types

### Prevention

**Best practices:**
- Use `status` command first to verify current state
- Use enough of name to be unambiguous
- Check confirmation message before proceeding (two-step mode)
- Use undo immediately if wrong (immediate mode)

## Issue: Commands Working Intermittently

**Symptom:** Sometimes works, sometimes doesn't

### Check Provider Credits/Balance

**Telnyx:**
- Log into dashboard
- Check account balance
- Verify payment method active

**Textbelt:**
- Check remaining credits
- Top up if needed

**Twilio:**
- Check account balance
- Verify no service interruptions

### Check Rate Limiting

Some providers limit message rates.

**Check provider dashboard** for:
- Rate limit warnings
- Throttling messages
- Queue delays

### Check Webhook Timeouts

WordPress has 30-second timeout on REST requests.

**If commands take too long:**
- Check database performance
- Verify no hanging processes
- Check hosting server load

### Check Cell Coverage

Snow reporters may have intermittent cell service on mountain.

**Solutions:**
- Move to area with better coverage
- Retry command
- Use radio to request web-based update

## Issue: Confirmation Taking Too Long

**Symptom:** Delay between sending command and receiving confirmation

### Normal Delays

**Expected timing:**
- SMS delivery: 1-3 seconds
- Webhook processing: 1-2 seconds
- Response SMS: 1-3 seconds
- **Total: 3-8 seconds typical**

### Excessive Delays (>15 seconds)

**Check provider status:**
- Provider dashboard for service issues
- Network status pages
- Queued message indicators

**Check WordPress performance:**
- Server load (hosting dashboard)
- Database query speed
- Plugin conflicts

**Check webhook queue:**
- Some providers queue webhooks during high load
- Check provider documentation for queue limits

### Improvement Options

**Optimize WordPress:**
- Enable object caching (Redis, Memcached)
- Optimize database queries
- Reduce plugin load

**Upgrade provider plan:**
- Higher throughput limits
- Priority message routing
- Dedicated infrastructure

## Issue: CSV Export Not Working

**Symptom:** Export button doesn't work or file is empty

### Verify Permissions

Only administrators (manage_options capability) can export.

**Check:**
1. User is logged in as Administrator
2. Not Snow Reporter or other limited role

### Check Filters

Export includes only **filtered results**.

**If export is empty:**
1. Remove all filters
2. Clear date range
3. Click **Export to CSV** again

### Check Browser

**Try different browser:**
- Chrome, Firefox, Safari
- Disable browser extensions
- Allow pop-ups from your site

### Check File Size

Very large exports (100,000+ rows) may timeout.

**Solution:**
- Export smaller date ranges
- Filter to specific post types
- Use API endpoint with streaming

### Manual Export via Database

**Last resort:**
```sql
SELECT * FROM wp_sierra_audit_logs
WHERE created_at >= '2025-01-01'
ORDER BY created_at DESC
INTO OUTFILE '/tmp/audit_export.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

## Provider-Specific Issues

### Telnyx: Webhook Signature Validation Failing

**Error:** "Invalid webhook signature"

**Causes:**
- Telnyx public key not configured
- Webhook payload modified in transit
- Proxy/CDN altering headers

**Fix:**
1. Get public key from Telnyx dashboard
2. Go to Settings → SMS Commands
3. Re-enter Telnyx configuration
4. Save settings
5. Test with new message

### Textbelt: US-Only Limitation

**Error:** "Inbound SMS not supported for this number"

**Cause:** Textbelt only supports inbound webhooks for US numbers

**Fix:**
- Use Telnyx or Twilio for international
- Or use US phone number with Textbelt

### Twilio: Signature Validation Failing

**Error:** "Invalid request signature"

**Causes:**
- Auth token incorrect
- Webhook URL changed
- Proxy/load balancer altering request

**Fix:**
1. Verify Auth Token in Settings → SMS Commands
2. Check webhook URL exactly matches Twilio console
3. If using proxy, configure to preserve headers

### Twilio: Geographic Permissions

**Error:** Messages not sending to certain countries

**Cause:** Twilio requires geographic permissions enabled

**Fix:**
1. Log into Twilio console
2. Go to Messaging → Settings → Geo Permissions
3. Enable countries where reporters are located
4. Save changes

## Advanced Diagnostics

### Enable Debug Logging

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `/wp-content/debug.log` for errors.

### Test Webhook Directly

**Send test POST request:**
```bash
curl -X POST https://yoursite.com/wp-json/sierra/v1/sms/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "from": "+14155551234",
    "body": "status",
    "provider": "test"
  }'
```

Should return JSON response with success status.

### Check Database Tables

**Verify audit log table exists:**
```sql
SHOW TABLES LIKE 'wp_sierra_audit_logs';
```

**Check recent logs:**
```sql
SELECT * FROM wp_sierra_audit_logs
WHERE change_source LIKE 'sms:%'
ORDER BY created_at DESC
LIMIT 10;
```

### Check REST API Status

**Test REST API is enabled:**
```bash
curl https://yoursite.com/wp-json/
```

Should return JSON with namespace info.

**If disabled:**
1. Check if REST API disabled by security plugin
2. Check `.htaccess` rules blocking wp-json
3. Check Cloudflare/firewall not blocking

## Getting Additional Help

### Before Contacting Support

Gather this information:

1. **WordPress version:** Dashboard → Updates
2. **Plugin versions:** Plugins page
3. **Provider:** Telnyx, Textbelt, or Twilio
4. **Exact error message:** Screenshot if possible
5. **Recent changes:** New plugins, hosting changes, etc.
6. **Webhook URL:** Settings → SMS Commands
7. **Provider logs:** Screenshot from provider dashboard

### Testing Checklist

Run through these tests:

- [ ] Text "help" command - receives response?
- [ ] Text "status" command - receives correct data?
- [ ] Text "close [test item]" - executes and logs?
- [ ] Check audit log - shows SMS source?
- [ ] Test from different phone - works?
- [ ] Test webhook with curl - responds?
- [ ] Check provider dashboard - messages showing?

### Support Resources

- **Plugin Documentation:** All files in `/docs` folder
- **Provider Support:**
  - Telnyx: https://telnyx.com/support
  - Textbelt: https://textbelt.com
  - Twilio: https://www.twilio.com/help/support
- **WordPress.org Forums:** For WordPress-related issues
- **Audit Logs:** Resort Data → Audit Logs for command history

## Maintenance & Prevention

### Regular Checks

**Weekly:**
- Review audit log for SMS errors
- Check provider balance/credits
- Verify all snow reporters can still send commands

**Monthly:**
- Test webhook with curl
- Review error patterns
- Update documentation if needed

**Seasonally:**
- Review provider costs
- Test with all snow reporters
- Update phone numbers as needed
- Refresh training materials

### System Health Indicators

**Healthy system:**
- Commands respond within 3-8 seconds
- Less than 5% undo rate
- No permission errors
- All reporters actively using

**Warning signs:**
- Increasing delays (>15 seconds)
- High undo rate (>20%)
- Permission errors appearing
- Reporters calling instead of texting

### Backup Plans

**If SMS system goes down:**

1. **Immediate:** Radio/phone to admin for manual updates
2. **Short-term:** Direct WordPress admin access for reporters
3. **Long-term:** Switch to backup provider

**Always maintain:**
- List of all reporter phone numbers
- Alternative contact methods
- Manual update procedures
- Provider backup account

## Common Training Issues

### "I keep making typos"

**Solution:**
- Train on autocorrect awareness
- Emphasize fuzzy matching handles small typos
- Use shortcuts (unique parts of names)
- Practice with non-critical items

### "I forgot the provider phone number"

**Solution:**
- Add to contacts: "Resort SMS Commands"
- Print on reference card
- Post in operations office
- Include in training materials

### "Undo window too short"

**Solution:**
- Increase undo window in Settings → SMS Commands
- Default 2 minutes, can extend to 10 minutes
- Or switch to two-step confirmation mode

### "I prefer confirming before execution"

**Solution:**
- Change user's confirmation mode preference
- Edit user → SMS Confirmation Mode → Two-Step
- Or change global default for all users
