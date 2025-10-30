# Sierra SMS Commands

WordPress plugin that enables snow reporters to update resort data (lifts, trails, gates) via SMS text messages using natural language commands.

## Features

- 📱 **SMS Commands** - Text simple commands like "open lift grandview" or "close broadway trail"
- 🔄 **Multi-Provider Support** - Works with Telnyx, Textbelt, and Twilio
- 🤖 **Fuzzy Matching** - Handles typos and partial names intelligently
- ✅ **Configurable Confirmation** - Choose immediate+undo or two-step confirmation modes
- 🔐 **Secure** - Webhook signature validation and user capability checks
- 📊 **Audit Integration** - All SMS commands logged to audit system with full attribution
- 👥 **Per-User Settings** - Override confirmation mode for individual users

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- [Sierra Resort Data Manager](https://github.com/zao-web/sierra-resort-data) plugin (required dependency)
- SMS provider account (Telnyx, Textbelt, or Twilio)

## Installation

1. Install and activate Sierra Resort Data Manager plugin
2. Upload `sierra-sms-commands` folder to `/wp-content/plugins/`
3. Activate the plugin through WordPress admin
4. Configure SMS provider in Settings → SMS Commands
5. Set up webhook URL with your SMS provider

## Quick Start

### 1. Choose SMS Provider

**Telnyx (Recommended)**
- FREE inbound messages
- $0.0025 per outbound message
- Best value for most users

**Textbelt**
- Pay-as-you-go
- Simplest setup
- US-only inbound webhooks

**Twilio**
- $0.0079 per message
- Enterprise-grade reliability
- Global coverage

### 2. Configure Provider

1. Go to **Settings → SMS Commands**
2. Select your provider
3. Enter API credentials
4. Configure webhook URL: `https://yoursite.com/wp-json/sierra/v1/sms/webhook`
5. Choose confirmation mode
6. Save settings

### 3. Setup Snow Reporters

1. Create WordPress user with **Snow Reporter** role
2. Add phone number to user profile (E.164 format: `+14155551234`)
3. Test with `help` command

### 4. Send Commands

Text to your provider's phone number:

```
open lift grandview
close broadway trail
open gate 5
status
help
undo
```

## SMS Commands

### Basic Commands

- **Open** - `open lift grandview` - Open a lift, trail, or gate
- **Close** - `close trail broadway` - Close a lift, trail, or gate
- **Status** - `status` - Get current lift/trail/gate counts
- **Help** - `help` - Show available commands
- **Undo** - `undo` - Reverse last command (immediate mode only)

### Smart Matching

Type is optional if name is unique:
```
open grandview        → Matches "Grandview Express" lift
close broadway        → Matches "Broadway Express Trail"
```

Handles typos automatically:
```
close brodway         → Still matches "Broadway"
open grandvew         → Still matches "Grandview"
```

## Documentation

Complete documentation available in the `/docs` folder:

**User Guides:**
- [Setup Guide](docs/setup-guide.md) - Complete provider configuration
- [Command Reference](docs/command-reference.md) - All available commands
- [Snow Reporter Onboarding](docs/snow-reporter-onboarding.md) - Admin guide for onboarding users
- [Troubleshooting](docs/troubleshooting.md) - Common issues and solutions

**Developer Guides:**
- [Creating Custom Providers](docs/developer-providers.md) - Add new SMS services
- [Hooks & Filters Reference](docs/developer-hooks.md) - Extend functionality

## Configuration

### Confirmation Modes

**Immediate + Undo (Default)**
- Commands execute immediately
- Reply with `undo` within 2 minutes to reverse
- Best for: Fast-paced operations, experienced users

**Two-Step Confirmation**
- System asks for confirmation before executing
- Reply `yes` to confirm or `no` to cancel
- Best for: Critical changes, new users

Configure globally in Settings → SMS Commands or per-user in their profile.

### Undo Window

Adjust how long users have to undo commands (30-600 seconds):
1. Go to Settings → SMS Commands
2. Set Undo Window Duration
3. Default: 120 seconds (2 minutes)

## Architecture

### Provider System

Abstract provider interface allows easy addition of new SMS services:

```php
interface SMS_Provider {
    public function send_sms($to, $message);
    public function validate_webhook($request);
    public function parse_inbound_message($request);
    public function get_config_fields();
    public function get_name();
    public function get_slug();
}
```

### Command Flow

1. SMS received at provider
2. Webhook calls `/wp-json/sierra/v1/sms/webhook`
3. Provider validates signature
4. Command parsed with fuzzy matching
5. User authenticated by phone number
6. Capabilities checked
7. Command executed via REST API
8. Audit log updated with source `sms:provider`
9. Confirmation SMS sent

## Security

- **Webhook Validation** - All providers use signature validation (HMAC, Ed25519)
- **User Authentication** - Phone number mapped to WordPress user
- **Capability Checks** - Snow Reporter role required with appropriate capabilities
- **Audit Trail** - Every command logged with user, timestamp, IP, provider

## Integration

Integrates seamlessly with Sierra Resort Data Manager:
- Updates flow through existing REST API
- All changes logged to audit system
- Source tracked as `sms:telnyx`, `sms:twilio`, etc.
- Undo creates reverse command with `sms:provider:undo` source

## Development

### Directory Structure

```
sierra-sms-commands/
├── admin/                          # Admin settings UI
├── includes/
│   ├── providers/                  # SMS provider implementations
│   │   ├── interface-sms-provider.php
│   │   ├── class-telnyx-provider.php
│   │   ├── class-textbelt-provider.php
│   │   └── class-twilio-provider.php
│   ├── class-provider-manager.php  # Provider registration
│   ├── class-command-parser.php    # Natural language parsing
│   ├── class-command-executor.php  # Command execution
│   ├── class-confirmation-manager.php
│   └── class-webhook-handler.php   # REST endpoint
├── docs/                           # Complete documentation
└── sierra-sms-commands.php         # Main plugin file
```

### Hooks & Filters

**Actions:**
- `sierra_sms_before_command_parse` - Before parsing command
- `sierra_sms_after_command_execute` - After successful execution
- `sierra_sms_command_failed` - When command fails
- `sierra_sms_undo_executed` - After undo completes

**Filters:**
- `sierra_sms_parsed_command` - Modify parsed command
- `sierra_sms_response_message` - Customize SMS responses
- `sierra_sms_fuzzy_match_threshold` - Adjust matching sensitivity
- `sierra_sms_confirmation_mode` - Override mode per command

See [Hooks Reference](docs/developer-hooks.md) for complete list.

### Adding a Provider

See [Creating Custom Providers](docs/developer-providers.md) for step-by-step guide.

## Troubleshooting

### Commands Not Working

1. Verify phone number in user profile (E.164 format)
2. Check webhook URL configured in provider dashboard
3. Ensure user has Snow Reporter role
4. Test webhook manually with curl

### No Response

1. Check provider account has credits/balance
2. Verify webhook signature validation
3. Check WordPress debug log for errors
4. Test provider API credentials

See [Troubleshooting Guide](docs/troubleshooting.md) for complete solutions.

## Support

- **Documentation:** See `/docs` folder
- **Issues:** [GitHub Issues](https://github.com/zao-web/sierra-sms-commands/issues)

## Cost Estimation

**Typical Usage** (5 reporters, 10 commands/day, 150 days):

- **Telnyx:** ~$14/season
- **Textbelt:** ~$30/season
- **Twilio:** ~$31/season

See [Setup Guide](docs/setup-guide.md) for detailed cost breakdown.

## License

GPL v2 or later

## Credits

Built for ski resort operations teams who need fast, reliable updates from the field.

## Changelog

### 1.0.0 - 2025-01-30
- Initial release
- Multi-provider support (Telnyx, Textbelt, Twilio)
- Natural language command parsing with fuzzy matching
- Configurable confirmation modes (immediate+undo, two-step)
- Complete audit log integration
- Per-user confirmation preferences
- Comprehensive documentation
