---
title: Hooks & Filters Reference
order: 7
category: Development
screens: []
---

# Hooks & Filters Reference

Complete reference for WordPress hooks and filters available in the Sierra SMS Commands plugin.

## Actions

Actions allow you to execute custom code at specific points in the SMS command lifecycle.

### `sierra_sms_before_command_parse`

Fires before an SMS command is parsed.

**Parameters:**
- `$message` (string) - Raw SMS message text
- `$phone_number` (string) - Sender's phone number (E.164)
- `$provider` (string) - Provider slug (telnyx, twilio, textbelt)

**Example:**
```php
add_action('sierra_sms_before_command_parse', function($message, $phone_number, $provider) {
    // Log all incoming SMS commands
    error_log("SMS from {$phone_number} via {$provider}: {$message}");
}, 10, 3);
```

### `sierra_sms_after_command_parse`

Fires after command is successfully parsed but before execution.

**Parameters:**
- `$command` (array) - Parsed command structure
- `$message` (string) - Original message text
- `$phone_number` (string) - Sender's phone number
- `$provider` (string) - Provider slug

**Command array structure:**
```php
[
    'action' => 'open',          // open, close, status, help, undo
    'type' => 'lift',            // lift, trail, gate, park_feature (optional)
    'name' => 'grandview',       // Search term for item
    'raw' => 'open lift grandview' // Original message
]
```

**Example:**
```php
add_action('sierra_sms_after_command_parse', function($command, $message, $phone, $provider) {
    // Send notification for gate commands
    if (isset($command['type']) && $command['type'] === 'gate') {
        wp_mail(
            'security@resort.com',
            "Gate Command: {$command['action']} {$command['name']}",
            "User: {$phone}\nProvider: {$provider}"
        );
    }
}, 10, 4);
```

### `sierra_sms_before_command_execute`

Fires before command is executed (after parsing and user validation).

**Parameters:**
- `$command` (array) - Parsed command
- `$user_id` (int) - WordPress user ID executing command
- `$provider` (string) - Provider slug

**Example:**
```php
add_action('sierra_sms_before_command_execute', function($command, $user_id, $provider) {
    // Rate limiting: Check if user sent too many commands recently
    $recent_count = get_transient("sms_command_count_user_{$user_id}");

    if ($recent_count && $recent_count > 20) {
        // Log and potentially block
        error_log("User {$user_id} exceeded rate limit");
    }

    // Increment counter
    $new_count = $recent_count ? $recent_count + 1 : 1;
    set_transient("sms_command_count_user_{$user_id}", $new_count, HOUR_IN_SECONDS);
}, 10, 3);
```

### `sierra_sms_after_command_execute`

Fires after command is successfully executed.

**Parameters:**
- `$command` (array) - Parsed command
- `$result` (array) - Execution result
- `$user_id` (int) - WordPress user ID
- `$provider` (string) - Provider slug

**Result array structure:**
```php
[
    'success' => true,
    'message' => 'Grandview Express opened successfully',
    'post_id' => 123,
    'post_title' => 'Grandview Express',
    'post_type' => 'sierra_lift',
    'field_changed' => 'status',
    'old_value' => 'closed',
    'new_value' => 'open'
]
```

**Example:**
```php
add_action('sierra_sms_after_command_execute', function($command, $result, $user_id, $provider) {
    // Send to analytics
    if ($result['success']) {
        track_event('sms_command_executed', [
            'action' => $command['action'],
            'type' => $result['post_type'],
            'provider' => $provider,
            'user_id' => $user_id
        ]);
    }
}, 10, 4);
```

### `sierra_sms_command_failed`

Fires when command execution fails.

**Parameters:**
- `$command` (array) - Parsed command
- `$error` (string) - Error message
- `$user_id` (int) - WordPress user ID
- `$provider` (string) - Provider slug

**Example:**
```php
add_action('sierra_sms_command_failed', function($command, $error, $user_id, $provider) {
    // Alert on repeated failures
    $failure_key = "sms_failures_user_{$user_id}";
    $failures = get_transient($failure_key) ?: 0;
    $failures++;

    set_transient($failure_key, $failures, HOUR_IN_SECONDS);

    if ($failures >= 5) {
        wp_mail(
            'admin@resort.com',
            "SMS Command Failures: User {$user_id}",
            "User has {$failures} failed commands in the last hour.\nLast error: {$error}"
        );
    }
}, 10, 4);
```

### `sierra_sms_before_send`

Fires before sending outbound SMS response.

**Parameters:**
- `$to` (string) - Recipient phone number
- `$message` (string) - Message to send
- `$provider` (string) - Provider slug

**Example:**
```php
add_action('sierra_sms_before_send', function($to, $message, $provider) {
    // Log all outbound messages
    error_log("Sending SMS to {$to} via {$provider}: {$message}");
}, 10, 3);
```

### `sierra_sms_after_send`

Fires after outbound SMS is sent (regardless of success/failure).

**Parameters:**
- `$to` (string) - Recipient phone number
- `$message` (string) - Message sent
- `$success` (bool) - Whether send was successful
- `$provider` (string) - Provider slug

**Example:**
```php
add_action('sierra_sms_after_send', function($to, $message, $success, $provider) {
    // Track SMS costs
    if ($success) {
        $cost = 0.0025; // Telnyx cost per message
        $total = get_option('sms_total_cost', 0);
        update_option('sms_total_cost', $total + $cost);
    }
}, 10, 4);
```

### `sierra_sms_undo_executed`

Fires when undo command is successfully executed.

**Parameters:**
- `$undo_data` (array) - Original command data that was undone
- `$user_id` (int) - WordPress user ID
- `$provider` (string) - Provider slug

**Undo data structure:**
```php
[
    'post_id' => 123,
    'post_type' => 'sierra_lift',
    'field' => 'status',
    'old_value' => 'open',        // Value before original command
    'command' => 'close lift grandview', // Original command
    'executed_at' => 1642345678   // Unix timestamp
]
```

**Example:**
```php
add_action('sierra_sms_undo_executed', function($undo_data, $user_id, $provider) {
    // Alert on frequent undo usage (may indicate training issue)
    $undo_count = get_transient("undo_count_user_{$user_id}") ?: 0;
    $undo_count++;

    set_transient("undo_count_user_{$user_id}", $undo_count, DAY_IN_SECONDS);

    if ($undo_count > 10) {
        error_log("User {$user_id} has used undo {$undo_count} times today");
    }
}, 10, 3);
```

### `sierra_sms_confirmation_requested`

Fires when two-step confirmation is requested.

**Parameters:**
- `$command` (array) - Command awaiting confirmation
- `$user_id` (int) - WordPress user ID
- `$phone_number` (string) - User's phone number
- `$provider` (string) - Provider slug

**Example:**
```php
add_action('sierra_sms_confirmation_requested', function($command, $user_id, $phone, $provider) {
    // Log confirmation requests for audit
    error_log("Confirmation requested: User {$user_id} - {$command['raw']}");
}, 10, 4);
```

### `sierra_sms_confirmation_timeout`

Fires when pending confirmation expires without response.

**Parameters:**
- `$command` (array) - Command that timed out
- `$user_id` (int) - WordPress user ID

**Example:**
```php
add_action('sierra_sms_confirmation_timeout', function($command, $user_id) {
    // Track timeout rate
    $timeout_count = get_user_meta($user_id, 'sms_timeout_count', true) ?: 0;
    update_user_meta($user_id, 'sms_timeout_count', $timeout_count + 1);
}, 10, 2);
```

## Filters

Filters allow you to modify data before it's used or displayed.

### `sierra_sms_parsed_command`

Modify parsed command before execution.

**Parameters:**
- `$command` (array) - Parsed command
- `$message` (string) - Original message text

**Returns:** `array` - Modified command

**Example:**
```php
add_filter('sierra_sms_parsed_command', function($command, $message) {
    // Add custom aliases
    if (isset($command['name']) && $command['name'] === 'gv') {
        $command['name'] = 'grandview';
    }

    return $command;
}, 10, 2);
```

### `sierra_sms_response_message`

Modify outbound SMS response before sending.

**Parameters:**
- `$message` (string) - Response message
- `$command` (array) - Original command
- `$result` (array) - Execution result

**Returns:** `string` - Modified message

**Example:**
```php
add_filter('sierra_sms_response_message', function($message, $command, $result) {
    // Add custom branding
    $message .= "\n\n- Sierra Operations Team";

    // Shorten for SMS length
    $message = str_replace('successfully', '', $message);

    return $message;
}, 10, 3);
```

### `sierra_sms_fuzzy_match_threshold`

Adjust fuzzy matching sensitivity.

**Parameters:**
- `$threshold` (float) - Default threshold (0.3 = 30% difference allowed)
- `$search_term` (string) - Term being searched

**Returns:** `float` - Modified threshold (0.0-1.0)

**Example:**
```php
add_filter('sierra_sms_fuzzy_match_threshold', function($threshold, $search_term) {
    // Be more strict for short terms
    if (strlen($search_term) < 5) {
        return 0.2; // Only 20% difference allowed
    }

    // Be more lenient for long terms
    if (strlen($search_term) > 10) {
        return 0.4; // 40% difference allowed
    }

    return $threshold;
}, 10, 2);
```

### `sierra_sms_undo_window`

Modify undo window duration per user.

**Parameters:**
- `$duration` (int) - Default duration in seconds
- `$user_id` (int) - WordPress user ID

**Returns:** `int` - Modified duration

**Example:**
```php
add_filter('sierra_sms_undo_window', function($duration, $user_id) {
    // Give senior staff longer undo window
    $user = get_userdata($user_id);

    if (in_array('administrator', $user->roles)) {
        return 600; // 10 minutes for admins
    }

    return $duration; // Default for others
}, 10, 2);
```

### `sierra_sms_confirmation_mode`

Override confirmation mode per command.

**Parameters:**
- `$mode` (string) - Default mode ('immediate' or 'two-step')
- `$command` (array) - Parsed command
- `$user_id` (int) - WordPress user ID

**Returns:** `string` - 'immediate' or 'two-step'

**Example:**
```php
add_filter('sierra_sms_confirmation_mode', function($mode, $command, $user_id) {
    // Always require confirmation for gate commands
    if (isset($command['type']) && $command['type'] === 'gate') {
        return 'two-step';
    }

    // Immediate mode for status/help
    if (in_array($command['action'], ['status', 'help'])) {
        return 'immediate';
    }

    return $mode;
}, 10, 3);
```

### `sierra_sms_search_post_types`

Modify which post types are searched for command matching.

**Parameters:**
- `$post_types` (array) - Default post types
- `$command` (array) - Parsed command

**Returns:** `array` - Modified post types

**Example:**
```php
add_filter('sierra_sms_search_post_types', function($post_types, $command) {
    // Add custom post type
    $post_types[] = 'custom_resort_feature';

    // Filter by command type
    if (isset($command['type']) && $command['type'] === 'lift') {
        // Only search lifts
        return ['sierra_lift'];
    }

    return $post_types;
}, 10, 2);
```

### `sierra_sms_user_lookup`

Override user lookup by phone number.

**Parameters:**
- `$user_id` (int|false) - Found user ID or false
- `$phone_number` (string) - Phone number to lookup

**Returns:** `int|false` - User ID or false

**Example:**
```php
add_filter('sierra_sms_user_lookup', function($user_id, $phone_number) {
    // Check custom user meta if standard lookup failed
    if (!$user_id) {
        $args = [
            'meta_key' => 'alternate_phone',
            'meta_value' => $phone_number,
            'number' => 1
        ];
        $users = get_users($args);

        if (!empty($users)) {
            return $users[0]->ID;
        }
    }

    return $user_id;
}, 10, 2);
```

### `sierra_sms_webhook_validation`

Override webhook validation result.

**Parameters:**
- `$is_valid` (bool) - Standard validation result
- `$request` (WP_REST_Request) - Webhook request
- `$provider` (string) - Provider slug

**Returns:** `bool` - Whether webhook is valid

**Example:**
```php
add_filter('sierra_sms_webhook_validation', function($is_valid, $request, $provider) {
    // Skip validation in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return true;
    }

    // Add custom validation
    $custom_token = $request->get_header('X-Custom-Token');
    if ($custom_token === get_option('custom_webhook_token')) {
        return true;
    }

    return $is_valid;
}, 10, 3);
```

### `sierra_sms_command_log_data`

Modify command log data before saving.

**Parameters:**
- `$log_data` (array) - Log entry data

**Returns:** `array` - Modified log data

**Example:**
```php
add_filter('sierra_sms_command_log_data', function($log_data) {
    // Add custom fields
    $log_data['app_version'] = get_option('app_version');
    $log_data['season'] = get_option('current_season');

    // Redact sensitive data
    if (isset($log_data['phone_number'])) {
        $log_data['phone_number'] = substr($log_data['phone_number'], 0, -4) . 'XXXX';
    }

    return $log_data;
});
```

### `sierra_sms_help_message`

Customize help command response.

**Parameters:**
- `$help_text` (string) - Default help message
- `$user_id` (int) - WordPress user ID

**Returns:** `string` - Modified help text

**Example:**
```php
add_filter('sierra_sms_help_message', function($help_text, $user_id) {
    // Add user-specific help
    $user = get_userdata($user_id);

    $help_text .= "\n\n";
    $help_text .= "You are: {$user->display_name}\n";
    $help_text .= "Support: (555) 123-4567";

    return $help_text;
}, 10, 2);
```

### `sierra_sms_status_message`

Customize status command response.

**Parameters:**
- `$status_text` (string) - Default status message
- `$user_id` (int) - WordPress user ID

**Returns:** `string` - Modified status text

**Example:**
```php
add_filter('sierra_sms_status_message', function($status_text, $user_id) {
    // Add weather info
    $temp = get_option('current_temp');
    $wind = get_option('current_wind');

    $status_text .= "\n\n";
    $status_text .= "Weather: {$temp}°F, Wind: {$wind}mph";

    return $status_text;
}, 10, 2);
```

## Complete Integration Example

Here's a complete example showing how to use hooks for a notification system:

```php
<?php
/**
 * SMS Command Notification System
 *
 * Sends email notifications for critical commands
 */

// Track all commands
add_action('sierra_sms_after_command_parse', function($command, $message, $phone, $provider) {
    // Store in custom log
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}sms_command_log", [
        'phone_number' => $phone,
        'command' => $message,
        'provider' => $provider,
        'parsed_action' => $command['action'],
        'created_at' => current_time('mysql')
    ]);
}, 10, 4);

// Alert on gate commands
add_action('sierra_sms_after_command_execute', function($command, $result, $user_id, $provider) {
    if (!$result['success'] || $result['post_type'] !== 'sierra_gate') {
        return;
    }

    $user = get_userdata($user_id);
    $action = $command['action'];
    $gate = $result['post_title'];

    $subject = "Gate {$action}: {$gate}";
    $message = "User: {$user->display_name}\n";
    $message .= "Action: {$action}\n";
    $message .= "Gate: {$gate}\n";
    $message .= "Provider: {$provider}\n";
    $message .= "Time: " . current_time('Y-m-d H:i:s');

    wp_mail('security@resort.com', $subject, $message);
}, 10, 4);

// Track undo frequency
add_action('sierra_sms_undo_executed', function($undo_data, $user_id, $provider) {
    $count = get_user_meta($user_id, 'undo_count_today', true) ?: 0;
    $count++;

    update_user_meta($user_id, 'undo_count_today', $count);

    // Alert if excessive
    if ($count >= 5) {
        $user = get_userdata($user_id);
        wp_mail(
            'training@resort.com',
            "Excessive Undo Usage: {$user->display_name}",
            "{$user->display_name} has used undo {$count} times today. Consider additional training."
        );
    }
}, 10, 3);

// Daily cleanup
add_action('wp_scheduled_delete', function() {
    global $wpdb;

    // Reset daily counters
    delete_metadata('user', null, 'undo_count_today', '', true);

    // Clean old command logs
    $wpdb->query("
        DELETE FROM {$wpdb->prefix}sms_command_log
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
});

// Customize responses
add_filter('sierra_sms_response_message', function($message, $command, $result) {
    // Add time to all responses
    $message .= "\n" . current_time('g:i A');

    return $message;
}, 10, 3);

// Strict matching for new users
add_filter('sierra_sms_fuzzy_match_threshold', function($threshold, $search_term) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    // New users (< 7 days) get stricter matching
    $registered = strtotime($user->user_registered);
    $days_active = (time() - $registered) / DAY_IN_SECONDS;

    if ($days_active < 7) {
        return 0.2; // Strict 20% threshold
    }

    return $threshold;
}, 10, 2);
```

## Debugging Hooks

Enable hook debugging by adding to `wp-config.php`:

```php
define('SIERRA_SMS_DEBUG', true);
```

This logs all hook executions to `wp-content/debug.log`:

```
[2025-01-15 10:30:45] sierra_sms_before_command_parse: +14155551234, "open gate 5", telnyx
[2025-01-15 10:30:45] sierra_sms_after_command_parse: action=open, type=gate, name=5
[2025-01-15 10:30:46] sierra_sms_before_command_execute: user_id=5
[2025-01-15 10:30:46] sierra_sms_after_command_execute: success, post_id=456
[2025-01-15 10:30:47] sierra_sms_before_send: +14155551234, "Gate 5 opened..."
[2025-01-15 10:30:47] sierra_sms_after_send: success
```

## Hook Priority Best Practices

**Default priority:** 10

**Use lower priority (5) for:**
- Security checks
- Validation
- Early modifications

**Use higher priority (20) for:**
- Logging
- Analytics
- Notifications

**Example:**
```php
// Security check first (priority 5)
add_filter('sierra_sms_parsed_command', 'validate_command', 5, 2);

// Logging last (priority 20)
add_action('sierra_sms_after_command_execute', 'log_to_analytics', 20, 4);
```

## Further Reading

- [WordPress Plugin API](https://developer.wordpress.org/plugins/hooks/)
- [Command Parser Source](../includes/class-command-parser.php)
- [Command Executor Source](../includes/class-command-executor.php)
- [Webhook Handler Source](../includes/class-webhook-handler.php)
