---
title: SMS Commands Reference
order: 1
category: SMS Commands
screens: [resort-data_page_sierra-sms-command-log, dashboard]
---

# SMS Commands Reference

Control resort data via text message using simple natural language commands.

## Available Commands

### Open Items

Open lifts, trails, gates, or park features.

**Syntax:**
- `open [type] [name]`
- `open [name]` (auto-detects type)

**Examples:**
- `open lift grandview`
- `open broadway` (if unique)
- `open gate 2`
- `open park rail slide`

**Response:** Confirmation message with item name and new status.

### Close Items

Close lifts, trails, gates, or park features.

**Syntax:**
- `close [type] [name]`
- `close [name]` (auto-detects type)

**Examples:**
- `close lift tahoe king`
- `close east face`
- `close gate 1`

**Response:** Confirmation message with item name and new status.

### Groom Trails

Mark trails as freshly groomed. **Only works for trails.**

**Syntax:**
- `groom [trail name]`
- `groomed [trail name]`

**Examples:**
- `groom broadway`
- `groomed sugar n spice`
- `groom trail east face`

**Response:** "Trail [name] marked as groomed successfully"

**Note:** If you try to groom a non-trail item, you'll receive an error: "Only trails can be groomed"

### Resort Status

Get current counts of open/closed items.

**Syntax:**
- `status`
- `stat`
- `info`

**Response:** Summary showing:
```
Resort Status:
Lifts: 12/14 open
Trails: 25/40 open
Gates: 1/1 open
```

### Help

Get command help.

**Syntax:**
- `help`
- `?`
- `commands`

**Response:** List of available commands and examples.

### Undo

Undo your last change (within 2 minutes).

**Syntax:**
- `undo`
- `cancel`
- `revert`

**Response:** Reverts the last status change and confirms.

**Time Limit:** Must be used within 2 minutes of the original command.

## Item Type Hints

Specify the type of item for faster matching:

- `lift` - Chairlifts and surface lifts
- `trail` - Ski runs and trails
- `gate` - Access gates to terrain areas
- `park` / `feature` - Terrain park features

**Examples:**
- `open lift tahoe king` (explicit)
- `close trail broadway` (explicit)
- `groom broadway` (trail assumed for grooming)

## Name Matching

The system uses fuzzy matching to find items:

### Exact Matches
Best match, instant recognition:
- `open grandview express` → Grandview Express

### Partial Matches
Matches parts of names:
- `open grandview` → Grandview Express
- `close broadway` → Upper Broadway

### Fuzzy Matches
Handles typos and variations:
- `open tahoe qeen` → Tahoe Queen (spelling error)
- `close east fce` → East Face (missing letters)

### Ambiguous Names
If multiple items match, you'll get options:
```
Multiple matches found:
1. Upper Broadway (Trail)
2. Lower Broadway (Trail)

Reply with the number to select.
```

Reply with `1` or `2` to choose.

## Command Examples

### Morning Opening
```
open grandview express
open tahoe king
open broadway
open east face
status
```

### Trail Grooming Report
```
groomed broadway
groomed sugar n spice
groomed main street
groom lower main
```

### Closing Terrain
```
close east face
close gate 2
status
```

### Fix a Mistake
```
close tahoe king
(oops, meant to open it)
undo
open tahoe king
```

## Response Messages

### Success
- "Lift [name] opened successfully. Reply UNDO within 2 min to reverse."
- "Trail [name] closed successfully. Reply UNDO within 2 min to reverse."
- "Trail [name] marked as groomed successfully"

### Already in State
- "Lift [name] is already open"
- "Trail [name] is already closed"
- "Trail [name] is already marked as groomed"

### Not Found
- "No lift found matching '[name]'. Please check the name and try again."
- "No trail found matching '[name]'. Please check the name and try again."

### Disambiguation Required
- "Multiple matches found: [list]. Reply with the number to select."

### Errors
- "Only trails can be groomed"
- "Invalid command format. Try 'open lift name', 'close trail name', 'groom trail name', 'status', or 'help'."
- "You do not have permission to update resort data."

## Tips for Success

1. **Be Specific**: Use type hints when names might be ambiguous
2. **Check Status**: Use `status` command regularly to verify changes
3. **Use UNDO**: Made a mistake? Use `undo` within 2 minutes
4. **Spell Approximately**: Fuzzy matching handles minor typos
5. **Watch Confirmations**: Always wait for confirmation before sending another command

## Permissions

Only authorized users can send commands. Contact your administrator to:
- Add your phone number to the allowed list
- Assign the "Snow Reporter" role
- Grant resort data update permissions

## Audit Trail

All SMS commands are logged in the audit system:
- Who sent the command
- What was changed
- When it happened
- Which SMS provider was used
- IP address (for webhook requests)

View SMS command history at **Resort Data → SMS Command Log**.

## Supported SMS Providers

- **Twilio**: Primary SMS provider
- **Telnyx**: Alternative provider
- **Textbelt**: Development/testing provider

The provider is automatically detected and logged.

## Technical Details

- Commands are processed via webhook endpoints
- Natural language parsing with fuzzy matching
- Response time: < 1 second typical
- Character limit: 160 characters recommended
- Encoding: UTF-8 supported
