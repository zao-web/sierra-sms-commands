---
title: SMS Command Reference
order: 2
category: Using SMS Commands
screens: [sierra-resort-audit-logs, dashboard]
---

# SMS Command Reference

Complete reference for all available SMS commands.

## Command Format

Basic syntax:
```
[action] [type] [name]
```

- **action**: What to do (open, close)
- **type**: Optional (lift, trail, gate)
- **name**: Item name

## Core Commands

### Open

Open a lift, trail, gate, or park feature.

**Format:**
```
open [type] [name]
```

**Examples:**
```
open lift grandview
open trail broadway
open gate 5
open grandview              (auto-detects type)
```

**Response:**
```
Lift Grandview opened successfully. Reply UNDO within 2 min to reverse.
```

### Close

Close a lift, trail, gate, or park feature.

**Format:**
```
close [type] [name]
```

**Examples:**
```
close lift grandview
close trail broadway
close gate 5
close grandview             (auto-detects type)
```

**Response:**
```
Lift Grandview closed successfully. Reply UNDO within 2 min to reverse.
```

### Status

Get current status of all resort features.

**Format:**
```
status
```

**Aliases:**
- `stat`
- `info`

**Response:**
```
Resort Status:
Lifts: 8/12 open
Trails: 42/87 open
Gates: 2/5 open
```

### Help

Show available commands.

**Format:**
```
help
```

**Aliases:**
- `?`
- `commands`

**Response:**
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

### Undo

Reverse the last command (immediate mode only).

**Format:**
```
undo
```

**Aliases:**
- `cancel`
- `revert`

**Response:**
```
Undone: Broadway Trail restored to open
```

**Note:** Only works within undo window (default 2 minutes)

### Confirm (Two-Step Mode)

Confirm a pending command.

**Format:**
```
yes
```

**Aliases:**
- `y`
- `confirm`
- `ok`

**Usage:**
Only after system requests confirmation.

### Cancel (Two-Step Mode)

Cancel a pending command.

**Format:**
```
no
```

**Aliases:**
- `n`
- `cancel`

## Type Keywords

### Lift
```
lift
```
Matches chairlifts, gondolas, surface lifts.

### Trail
```
trail
```
Matches all ski runs and trails.

### Gate
```
gate
```
Matches access gates.

### Park Feature
```
park
feature
```
Matches terrain park features (rails, jumps, boxes).

## Name Matching

The system uses intelligent name matching:

### Exact Match
```
close lift grandview express
```
Matches "Grandview Express" exactly

### Partial Match
```
close broadway
```
Matches "Broadway Express Trail"

### Fuzzy Match
```
close broaday           (typo)
```
Still matches "Broadway Express Trail"

### Ambiguous Match
```
close main
```
If multiple items contain "main":
```
Multiple matches found:
• Main Street Trail
• Main Park Jump
• West Main Gate

Please be more specific.
```

## Common Patterns

### Quick Updates
Type optional if name is unique:
```
open grandview
close broadway
```

### Explicit Types
Use when name could be ambiguous:
```
open trail main street
close lift main express
```

### Gates by Number
```
open gate 1
close gate 5
```

### Case Insensitive
All commands work regardless of case:
```
OPEN LIFT GRANDVIEW
open lift grandview
OpEn LiFt GrAnDvIeW
```
All equivalent.

## Response Types

### Success
```
Trail Broadway opened successfully. Reply UNDO within 2 min to reverse.
```

### Error - Not Found
```
No trail found matching "xyz". Please check the name and try again.
```

### Error - Ambiguous
```
Multiple matches found:
• Main Street Trail
• Main Park Jump

Please be more specific.
```

### Error - Permission
```
You do not have permission to update resort data.
```

### Error - Invalid Format
```
Invalid command format. Try "open lift name", "close trail name", "status", or "help".
```

### Error - Undo Expired
```
Nothing to undo. Undo window expired or no recent command.
```

## Advanced Usage

### Batch Updates
Send multiple commands:
```
close gate 1
close gate 2
close gate 3
```
Each confirmed separately.

### Checking Before Action
```
status                  (check current state)
close broadway         (make change)
status                  (verify change)
```

### Undo Chain
In immediate mode, only last command can be undone:
```
open gate 1            (can undo)
close broadway         (can undo this; gate 1 now locked in)
undo                   (reverts broadway only)
```

## Confirmation Modes

### Immediate + Undo (Default)

**Flow:**
1. Send command: `open gate 1`
2. Execute immediately
3. Reply: `Gate 1 opened. Reply UNDO within 2 min to reverse.`
4. Optionally undo: `undo`

**Best for:** Experienced users, fast operations

### Two-Step Confirmation

**Flow:**
1. Send command: `open gate 1`
2. System asks: `Open Gate: Gate 1? Reply YES to confirm or NO to cancel.`
3. Confirm: `yes`
4. System executes: `Gate 1 opened successfully.`

**Best for:** New users, critical changes

## Tips & Best Practices

### Be Specific
```
✅ close trail broadway express
❌ close trail                     (too vague)
```

### Use Status Frequently
```
status                  (start of shift)
[make updates]
status                  (end of shift)
```

### Keep Commands Short
```
✅ close broadway
✅ close trail broadway
❌ close the broadway express trail please
```

### Check for Typos
System handles most typos, but extreme errors may fail:
```
✅ brodway              (close enough)
❌ brdwy                (too different)
```

### Use Help When Needed
```
help                    (shows syntax)
```

### Test First
```
status                  (verify working)
close gate 1            (test command)
undo                    (test undo)
status                  (confirm reverted)
```

## Command Limits

### Rate Limiting
No built-in rate limiting, but reasonable use expected:
- Commands process in order received
- Expect 1-5 second response time
- Avoid flooding with rapid commands

### Message Length
SMS limit: 160 characters
- Commands typically 10-30 characters
- Responses fit within 160 characters
- Long item names truncated if needed

### Concurrent Commands
Multiple reporters can send commands simultaneously:
- Each processed independently
- Audit log records all
- Last command wins for conflicting changes

## Troubleshooting Commands

### Command Not Working

**Check:**
1. Phone number configured in profile
2. Correct provider phone number
3. Command syntax correct
4. Item name exists and is spelled correctly

**Try:**
```
help                    (verify connection)
status                  (verify system responding)
```

### No Response

**Wait:** 5-10 seconds for processing

**Retry:** Send command again

**Check:**
- Phone has signal
- SMS messages not blocked
- Provider account active

### Wrong Item Updated

**Verify:** Item name specificity
```
close trail main        (could match multiple)
close trail main street (more specific)
```

**Check audit log:** See what was actually changed

### Can't Undo

**Reasons:**
- Undo window expired (default 2 minutes)
- Another command sent after (only last can be undone)
- In two-step mode (no undo, must reverse manually)

**Solution:**
Send opposite command:
```
close broadway          (to reverse accidental open)
```

## Security Notes

### Authentication
- Commands validated by phone number
- Must be registered snow reporter
- Capabilities checked before execution

### Logging
All commands logged with:
- Who sent it (phone number/user)
- What was changed
- When (exact timestamp)
- Source (SMS provider)

### Audit Trail
Review your commands:
- Resort Data → Audit Logs
- Filter by your username
- Filter by source: SMS

## Getting Help

If commands aren't working:

1. **Try help command:**
   ```
   help
   ```

2. **Check with admin:**
   - Verify phone number configured
   - Confirm Snow Reporter role
   - Test from their phone

3. **Review audit log:**
   - See if commands are being received
   - Check for permission errors

4. **Test basic connectivity:**
   ```
   status                (should always work if connected)
   ```
