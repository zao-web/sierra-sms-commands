---
title: Mobiniti SMS Provider Setup
order: 3
category: Configuration
screens: [settings_page_sierra-sms-commands]
---

# Mobiniti SMS Provider Setup

This guide walks through configuring the Mobiniti SMS provider for Sierra SMS Commands.

## Prerequisites

Before starting, ensure you have:

1. **Active Mobiniti Account** with messaging capabilities
2. **Short Code** provisioned in your Mobiniti account
3. **Access Token** (Personal Access Token or OAuth token)
4. **Webhook Source IPs** from Mobiniti support

## Step 1: Get Your Access Token

1. Log into your Mobiniti account
2. Navigate to **Account Settings** or **API Settings**
3. Generate a **Personal Access Token**
   - Tokens are valid for 10 years
   - Required scope: `messages.create`
4. Copy the token securely (shown only once)

## Step 2: Identify Your Short Code

Your Mobiniti short code is the number customers text to reach you (e.g., `64600`).

1. Find this in your Mobiniti dashboard under **Phone Numbers** or **Short Codes**
2. Note the short code for plugin configuration

## Step 3: Get Webhook Source IPs

For security, the plugin validates webhooks using IP allowlisting.

**Contact Mobiniti Support:**
- Email or chat support through your Mobiniti account
- Request: "What IP addresses do webhooks originate from?"
- You need a list of static IP addresses

**Example IPs** (contact Mobiniti for actual IPs):
```
192.0.2.1
192.0.2.2
203.0.113.10
```

## Step 4: Configure Plugin

1. Navigate to **SMS Commands → Settings** in WordPress admin
2. Select **Mobiniti** as your SMS provider
3. Enter configuration:

   **Access Token**
   - Paste your Personal Access Token
   - Keep secure - never share publicly

   **Short Code** (optional)
   - Enter your short code for reference
   - This is informational only

   **Allowed Webhook IPs** (required)
   - Paste IP addresses from Mobiniti support
   - One per line or comma-separated
   - Example:
     ```
     192.0.2.1
     192.0.2.2
     203.0.113.10
     ```

   **Webhook URL** (read-only)
   - Copy this URL for next step
   - Example: `https://yoursite.com/wp-json/sierra/v1/sms/webhook`

4. Click **Save Changes**

## Step 5: Configure Mobiniti Webhook

1. Log into your Mobiniti account
2. Navigate to **Message Settings** → **Webhooks**
3. Click **Add Webhook** or **Configure Webhook**
4. Configure webhook:
   - **URL**: Paste the webhook URL from plugin settings
   - **Event**: Select **Inbound Messages**
   - **Method**: POST
   - **Format**: JSON
5. Save webhook configuration

## Step 6: Test the Integration

### Test Inbound Command

1. From an authorized phone number, text a command to your short code:
   ```
   GROOMING OPEN
   ```

2. Check **SMS Commands → Command Log** to verify:
   - Message received
   - Command parsed correctly
   - Response sent

### Verify in Mobiniti Dashboard

1. Check Mobiniti **Message History**
2. Confirm outbound response was sent
3. Verify delivery status

## How Mobiniti Works

### Contact Management

Mobiniti uses a contact-based system:

1. **First Message**: When someone texts your short code, a contact is auto-created
2. **Contact ID**: Plugin automatically manages contact IDs
3. **Caching**: Contact IDs cached to reduce API calls

### Message Flow

```
[User texts short code]
       ↓
[Mobiniti receives SMS]
       ↓
[Webhook POST to WordPress]
       ↓
[Plugin validates IP]
       ↓
[Plugin processes command]
       ↓
[Plugin looks up/creates contact]
       ↓
[Plugin sends response via API]
       ↓
[User receives confirmation]
```

## Important Differences from Twilio

### Rate Limits
- **Mobiniti**: 1 request per second per route
- **Impact**: Multiple simultaneous commands may be queued
- **429 Response**: Rate limit exceeded error logged

### Authentication
- **Mobiniti**: OAuth 2.0 Bearer tokens
- **Twilio**: HTTP Basic Auth
- **Token Validity**: 10 years (Mobiniti)

### Webhook Security
- **Mobiniti**: IP allowlist validation
- **Twilio**: HMAC-SHA1 signature validation
- **Setup**: Must obtain IPs from Mobiniti support

### Contact Creation
- **Mobiniti**: Contacts auto-created on first message
- **Twilio**: No contact management needed
- **API Overhead**: Slight delay for first-time senders

## Troubleshooting

### Webhook Not Receiving Messages

**Check:**
1. Webhook URL configured correctly in Mobiniti
2. "Inbound Messages" event selected
3. WordPress REST API accessible (`/wp-json/` not blocked)

**Test:**
```bash
curl https://yoursite.com/wp-json/sierra/v1/sms/webhook
```
Should return: `{"code":"rest_no_route","message":"No route was found..."}`

### IP Validation Failing

**Symptoms:**
- Messages not processing
- Error log: "Webhook from unauthorized IP"

**Solutions:**
1. Verify IPs with Mobiniti support
2. Check server behind proxy (Cloudflare, etc.)
3. Temporarily bypass for testing (development only):
   ```php
   add_filter( 'sierra_sms_bypass_webhook_validation', '__return_true' );
   ```
   **WARNING**: Only use on development sites

### Rate Limit Errors

**Symptoms:**
- Error: "Rate limit exceeded. Mobiniti allows 1 request per second"
- HTTP 429 responses in logs

**Solutions:**
1. Reduce concurrent command volume
2. Contact Mobiniti about rate limit increases
3. Implement queuing for high-volume periods

### Contact Creation Failures

**Symptoms:**
- Error: "Failed to create contact"
- Messages not sending to new numbers

**Check:**
1. Access token has `messages.create` scope
2. Phone number in E.164 format (`+14155551234`)
3. Account has sufficient credits/quota

## Security Best Practices

1. **Protect Access Token**
   - Never commit to version control
   - Use environment variables for production
   - Rotate tokens annually

2. **Validate IP Allowlist**
   - Keep IPs up to date
   - Monitor webhook failures
   - Log unauthorized attempts

3. **Monitor Command Log**
   - Review regularly for unauthorized access
   - Check for unusual patterns
   - Alert on failed authentications

## Getting Help

**Mobiniti Support:**
- Support portal in your account dashboard
- Request webhook IP addresses
- API rate limit questions
- Contact creation issues

**Plugin Support:**
- Check **SMS Commands → Command Log** for errors
- Enable WordPress debug logging
- Review webhook validation logs

## Migration from Twilio

If migrating from Twilio to Mobiniti:

1. **Before Switching:**
   - Get Mobiniti webhook IPs
   - Configure webhook in Mobiniti
   - Test with development/staging site first

2. **Switch Provider:**
   - Select Mobiniti in SMS Commands settings
   - Enter all configuration
   - Save changes

3. **Update Phone Numbers:**
   - Update marketing materials if short code differs
   - Inform snow reporters of new number (if applicable)
   - Keep Twilio active during transition period

4. **Verify:**
   - Test all command types
   - Test disambiguation flow ("Press 1/2")
   - Test undo functionality
   - Confirm authorized users list

## Advanced Configuration

### Development Bypass Filter

For development sites where you can't get Mobiniti IPs:

```php
// In wp-config.php or custom plugin
add_filter( 'sierra_sms_bypass_webhook_validation', function( $bypass ) {
    // Only bypass on development sites
    return defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE === 'development';
} );
```

**Never use in production** - bypasses all webhook security.

### Custom Contact Caching

Contact IDs are cached in memory during request. For persistent caching:

```php
// Custom caching implementation (advanced)
add_filter( 'sierra_sms_mobiniti_contact_cache', function( $cache ) {
    // Implement custom cache (Redis, Memcached, etc.)
    return $your_persistent_cache;
}, 10, 1 );
```

## Related Documentation

- [SMS Commands Overview](sms-commands.md)
- [Command Log](sms-command-log.md)
- [Twilio Provider Setup](twilio-setup.md)
