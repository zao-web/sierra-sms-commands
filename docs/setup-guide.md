---
title: SMS Commands Setup Guide
order: 1
category: Getting Started
screens: [settings_page_sierra-sms-commands, dashboard]
---

# SMS Commands Setup Guide

Complete guide to setting up SMS commands for snow reporters.

## Overview

The SMS Commands system allows snow reporters to update resort data by sending text messages. No app required - just standard SMS.

**Example:**
- Snow reporter texts: `open lift grandview`
- System updates database, logs change, replies with confirmation
- Website immediately shows Grandview lift as open

## Prerequisites

Before setup:
- ✅ Sierra Resort Data Manager plugin installed and active
- ✅ At least one Snow Reporter user created
- ✅ Phone numbers assigned to snow reporters
- ✅ SMS provider account (Telnyx, Textbelt, or Twilio)

## Step 1: Choose SMS Provider

### Telnyx (Recommended)
**Best for: Most users**

- **Pricing:** FREE inbound, $0.0025/outbound
- **Pros:** Lowest cost, reliable, good documentation
- **Cons:** Requires some technical setup
- **Setup time:** 15-20 minutes

[Get Started with Telnyx →](https://telnyx.com)

### Textbelt
**Best for: Simplicity**

- **Pricing:** Pay-as-you-go, no recurring fees
- **Pros:** Simplest setup, no account needed for testing
- **Cons:** US-only inbound, limited features
- **Setup time:** 5 minutes

[Get Started with Textbelt →](https://textbelt.com)

### Twilio
**Best for: Enterprise needs**

- **Pricing:** $0.0079/SMS + $1.50/month per number
- **Pros:** Most reliable, global coverage, enterprise support
- **Cons:** Higher cost, more complex
- **Setup time:** 20-30 minutes

[Get Started with Twilio →](https://twilio.com)

## Step 2: Configure Provider

### A. Telnyx Setup

1. **Create Telnyx Account**
   - Sign up at https://telnyx.com
   - Verify email and phone

2. **Purchase Phone Number**
   - Navigate to Numbers → Search & Buy
   - Select a local or toll-free number
   - Complete purchase (~$2-5/month)

3. **Create Messaging Profile**
   - Go to Messaging → Messaging Profiles
   - Click "Add Messaging Profile"
   - Name it "Sierra Resort SMS"

4. **Assign Number to Profile**
   - In profile settings, assign your purchased number

5. **Configure Webhook**
   - In profile, go to Inbound Settings
   - Set webhook URL: `https://yoursite.com/wp-json/sierra/v1/sms/webhook`
   - Set HTTP method: POST
   - Set webhook API version: v2
   - Save

6. **Get API Key**
   - Navigate to API Keys
   - Create new API key
   - Copy the key (starts with "KEY...")
   - Store securely

7. **Configure in WordPress**
   - Go to Settings → SMS Commands
   - Select "Telnyx" as provider
   - Enter API Key
   - Enter phone number (format: +14155551234)
   - Save Settings

### B. Textbelt Setup

1. **Get API Key**
   - Visit https://textbelt.com
   - Purchase credits or use free tier
   - Copy your API key

2. **Configure in WordPress**
   - Go to Settings → SMS Commands
   - Select "Textbelt" as provider
   - Enter API Key
   - Enable reply webhooks if using US number
   - Save Settings

3. **Configure Webhook (Optional)**
   - In Textbelt dashboard
   - Set webhook URL: `https://yoursite.com/wp-json/sierra/v1/sms/webhook`
   - US numbers only

### C. Twilio Setup

1. **Create Twilio Account**
   - Sign up at https://twilio.com
   - Verify identity (required for full account)

2. **Purchase Phone Number**
   - Navigate to Phone Numbers → Buy a Number
   - Select SMS-capable number
   - Complete purchase

3. **Get Credentials**
   - From dashboard, copy:
     - Account SID
     - Auth Token

4. **Configure Webhook**
   - Go to Phone Numbers → Active Numbers
   - Click your number
   - Under Messaging, set webhook URL:
     `https://yoursite.com/wp-json/sierra/v1/sms/webhook`
   - Set method: HTTP POST
   - Save

5. **Configure in WordPress**
   - Go to Settings → SMS Commands
   - Select "Twilio" as provider
   - Enter Account SID
   - Enter Auth Token
   - Enter phone number (format: +14155551234)
   - Save Settings

## Step 3: Configure Confirmation Mode

Choose how commands are confirmed:

### Immediate + Undo (Default)
Commands execute immediately, with option to undo:
- Reporter texts: `open lift grandview`
- System replies: `Grandview lift opened. Reply UNDO within 2 min to reverse.`
- Reporter can text `undo` to revert

**Best for:** Fast-paced operations, experienced reporters

### Two-Step Confirmation
Commands require explicit confirmation:
- Reporter texts: `open lift grandview`
- System replies: `Open Lift: Grandview? Reply YES to confirm or NO to cancel.`
- Reporter texts: `yes`
- System confirms: `Grandview lift opened.`

**Best for:** Critical changes, new reporters, high-risk situations

### Configuration

1. Go to Settings → SMS Commands
2. Under Command Settings, select mode
3. Set undo window (30-600 seconds for immediate mode)
4. Save Settings

**Per-User Override:**
Users can override in their profile (edit user → SMS Confirmation Mode)

## Step 4: Test the System

### Send Test Command

1. From a configured snow reporter's phone
2. Send SMS to your provider's phone number
3. Text: `help`
4. Should receive list of commands

### Test Basic Command

1. Text: `status`
2. Should receive current lift/trail counts
3. Verify response is accurate

### Test Update Command

1. Text: `close gate 1` (or any test item)
2. Verify confirmation received
3. Check WordPress admin - status should be updated
4. Check Audit Log - entry should exist with SMS source

### Test Undo (If Immediate Mode)

1. Text: `open gate 1`
2. Immediately text: `undo`
3. Verify status reverted
4. Check Audit Log - both entries logged

## Troubleshooting Setup

### Webhook Not Receiving Messages

**Check webhook URL:**
```
https://yoursite.com/wp-json/sierra/v1/sms/webhook
```

**Verify:**
- HTTPS (required for most providers)
- No trailing slash
- Site is publicly accessible (not localhost)

**Test webhook:**
```bash
curl -X POST https://yoursite.com/wp-json/sierra/v1/sms/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

### SMS Not Sending

1. Check API credentials are correct
2. Verify phone number format (+1...)
3. Check provider account has credits/balance
4. Review provider logs/dashboard for errors

### Permission Errors

1. Verify user has Snow Reporter role
2. Check phone number is assigned to user
3. Ensure phone number format is correct (+1...)
4. Verify no duplicate phone numbers

## Security Considerations

### Webhook Security

**Telnyx:** Validates Ed25519 signature
**Twilio:** Validates HMAC-SHA1 signature
**Textbelt:** Basic validation (header check)

All providers validated before processing commands.

### User Authentication

- Phone number → WordPress user mapping
- Capability checks before execution
- All commands logged with attribution

### Rate Limiting

Consider implementing rate limiting at:
- Provider level (Twilio/Telnyx dashboards)
- Server level (firewall rules)
- Application level (custom development)

## Cost Estimation

### Typical Usage Scenario
- 5 snow reporters
- 10 SMS commands per day average
- 150 days per season

**Telnyx:**
- Inbound: FREE
- Outbound (confirmations): 1,500 × $0.0025 = $3.75/season
- Phone number: $2/month × 5 months = $10/season
- **Total: ~$14/season**

**Textbelt:**
- ~$0.01 per SMS (bidirectional)
- 3,000 SMS × $0.01 = $30/season
- **Total: ~$30/season**

**Twilio:**
- 3,000 SMS × $0.0079 = $23.70/season
- Phone number: $1.50/month × 5 months = $7.50/season
- **Total: ~$31/season**

## Next Steps

- [Learn available SMS commands](./command-reference.md)
- [Review snow reporter onboarding](./snow-reporter-onboarding.md)
- [Check troubleshooting guide](./troubleshooting.md)
