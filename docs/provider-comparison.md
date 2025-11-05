---
title: SMS Provider Comparison
order: 10
category: Configuration
screens: [settings_page_sierra-sms-commands]
---

# SMS Provider Comparison

Quick reference comparing supported SMS providers for Sierra SMS Commands.

## Provider Overview

| Feature | Mobiniti | Twilio | Telnyx | TextBelt |
|---------|----------|--------|---------|----------|
| **Setup Complexity** | Medium | Easy | Easy | Easiest |
| **Security** | IP Allowlist | Signature | Signature | API Key |
| **Rate Limits** | 1/sec | None | 50/sec | Varies |
| **Contact Management** | Required | None | None | None |
| **Token Validity** | 10 years | Permanent | Permanent | Per-use |
| **Best For** | Existing customers | Most users | High volume | Quick testing |

## Mobiniti

**Pros:**
- Long-lived tokens (10 years)
- Contact/campaign management built-in
- OAuth 2.0 authentication
- Good for marketing + transactional use

**Cons:**
- Contact creation overhead
- Rate limited (1 req/sec)
- IP allowlist setup required
- Less common than Twilio

**Ideal For:**
- Organizations already using Mobiniti
- Vendor consolidation needs
- Combined marketing/command use

**Setup Requirements:**
1. Mobiniti account with short code
2. Personal Access Token
3. Webhook source IP addresses
4. Webhook configuration in dashboard

## Twilio

**Pros:**
- Industry standard, most reliable
- Excellent documentation
- Strong signature validation
- No rate limits
- Direct phone-to-phone (no contacts)

**Cons:**
- Slightly higher cost
- Requires phone number provisioning

**Ideal For:**
- New implementations
- Mission-critical applications
- Need for reliability

**Setup Requirements:**
1. Twilio account
2. Provisioned phone number
3. Account SID and Auth Token
4. Webhook configuration in phone settings

## Quick Setup Guide

### Mobiniti Setup
```
1. Get Access Token from account
2. Contact support for webhook IPs
3. Configure in plugin settings
4. Set webhook URL in Mobiniti dashboard
5. Test with sample command
```

### Twilio Setup
```
1. Get Account SID and Auth Token
2. Provision phone number
3. Configure in plugin settings
4. Set webhook URL in phone number settings
5. Test with sample command
```

## Security Comparison

### Mobiniti
- **Method**: IP Allowlist
- **Strength**: Medium (IP spoofing possible)
- **Setup**: Must get IPs from support
- **Bypass**: Development filter available

### Twilio
- **Method**: HMAC-SHA1 signature
- **Strength**: High (cryptographic validation)
- **Setup**: Automatic with auth token
- **Bypass**: Not recommended

## Cost Comparison

Approximate costs (verify with providers):

| Provider | Cost per SMS | Monthly Fee | Notes |
|----------|--------------|-------------|-------|
| Mobiniti | Varies | Varies | Contact for pricing |
| Twilio | $0.0079 | $1.50/number | US rates |
| Telnyx | $0.0055 | $1.00/number | Competitive |
| TextBelt | $0.0075 | None | Pay-per-use |

**Example: 6,000 SMS/month**
- Twilio: ~$49/month
- Check with Mobiniti for comparable pricing

## Switching Providers

The plugin supports easy provider switching:

1. **Configure New Provider**
   - Add credentials in settings
   - Don't change active provider yet

2. **Test New Provider**
   - Use development site if possible
   - Verify webhook reception
   - Test command execution

3. **Switch Active Provider**
   - Select from dropdown
   - Save changes
   - Monitor command log

4. **Verify Transition**
   - Test all command types
   - Check disambiguation flow
   - Verify undo functionality

**No data migration needed** - all command history preserved.

## Technical Differences

### Webhook Payloads

**Mobiniti:**
```json
{
  "id": "uuid",
  "phone_number": "+19999999999",
  "message": "GROOMING OPEN",
  "short_code": "64600",
  "received_at": "2025-01-01T12:00:00Z"
}
```

**Twilio:**
```
From=+19999999999
Body=GROOMING%20OPEN
MessageSid=SM123abc
```

### API Authentication

**Mobiniti:**
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Twilio:**
```http
Authorization: Basic <base64(sid:token)>
Content-Type: application/x-www-form-urlencoded
```

## Recommendation

**Choose Mobiniti if:**
- Already have Mobiniti account
- Want single vendor for marketing + commands
- Long-term token management preferred
- Rate limits acceptable for your volume

**Choose Twilio if:**
- Starting fresh
- Need maximum reliability
- High command volume (>1/sec)
- Want cryptographic webhook security

**Both work perfectly** with Sierra SMS Commands - select based on your operational needs.

## Related Documentation

- [Mobiniti Setup Guide](mobiniti-setup.md)
- [Twilio Setup Guide](twilio-setup.md) (if exists)
- [SMS Commands Overview](sms-commands.md)
