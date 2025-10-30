---
title: Creating Custom SMS Providers
order: 6
category: Development
screens: []
---

# Creating Custom SMS Providers

Guide to extending the SMS Commands system with custom provider implementations.

## Provider Architecture

The SMS Commands plugin uses a provider pattern to support multiple SMS services. Each provider implements a common interface, making it easy to add new services.

### Provider Interface

All providers must implement the `SMS_Provider` interface:

**Location:** `/includes/providers/interface-sms-provider.php`

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

## Creating a Provider

### Step 1: Create Provider Class

Create a new file in `/includes/providers/`:

**Example:** `/includes/providers/class-my-provider.php`

```php
<?php
namespace SierraSMSCommands\Providers;

class My_Provider implements SMS_Provider {

    /**
     * Get provider display name
     */
    public function get_name() {
        return __('My SMS Provider', 'sierra-sms-commands');
    }

    /**
     * Get provider slug (used in settings)
     */
    public function get_slug() {
        return 'my-provider';
    }

    /**
     * Get configuration fields for settings page
     */
    public function get_config_fields() {
        return [
            [
                'id' => 'my_provider_api_key',
                'label' => __('API Key', 'sierra-sms-commands'),
                'type' => 'text',
                'description' => __('Enter your API key from My Provider dashboard', 'sierra-sms-commands'),
                'required' => true
            ],
            [
                'id' => 'my_provider_phone_number',
                'label' => __('Phone Number', 'sierra-sms-commands'),
                'type' => 'text',
                'description' => __('Your provider phone number in E.164 format', 'sierra-sms-commands'),
                'placeholder' => '+14155551234',
                'required' => true
            ]
        ];
    }

    /**
     * Send SMS message
     *
     * @param string $to Recipient phone number (E.164 format)
     * @param string $message Message text
     * @return bool True on success, false on failure
     */
    public function send_sms($to, $message) {
        $api_key = get_option('sierra_sms_my_provider_api_key');
        $from = get_option('sierra_sms_my_provider_phone_number');

        // Call provider API
        $response = wp_remote_post('https://api.myprovider.com/sms', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'to' => $to,
                'from' => $from,
                'message' => $message
            ]),
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            error_log('My Provider send failed: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        return $code >= 200 && $code < 300;
    }

    /**
     * Validate inbound webhook request
     *
     * @param WP_REST_Request $request
     * @return bool True if valid, false otherwise
     */
    public function validate_webhook($request) {
        // Get signature from header
        $signature = $request->get_header('X-My-Provider-Signature');
        if (!$signature) {
            return false;
        }

        // Get API key
        $api_key = get_option('sierra_sms_my_provider_api_key');

        // Compute expected signature
        $body = $request->get_body();
        $expected = hash_hmac('sha256', $body, $api_key);

        // Compare
        return hash_equals($expected, $signature);
    }

    /**
     * Parse inbound SMS message from webhook
     *
     * @param WP_REST_Request $request
     * @return array|false Array with 'from' and 'body' keys, or false on failure
     */
    public function parse_inbound_message($request) {
        $body = json_decode($request->get_body(), true);

        if (!isset($body['from']) || !isset($body['text'])) {
            return false;
        }

        return [
            'from' => $body['from'],
            'body' => $body['text']
        ];
    }
}
```

### Step 2: Register Provider

Register your provider with the Provider Manager:

**Location:** Add to plugin initialization in `sierra-sms-commands.php`

```php
add_action('plugins_loaded', function() {
    // Get provider manager instance
    $manager = \SierraSMSCommands\Provider_Manager::get_instance();

    // Register your provider
    $manager->register_provider(new \SierraSMSCommands\Providers\My_Provider());
});
```

### Step 3: Test Provider

1. Activate plugin
2. Go to Settings → SMS Commands
3. Select your provider from dropdown
4. Configure API credentials
5. Save settings
6. Test with inbound SMS

## Method Details

### `get_name()`

**Purpose:** Display name shown in settings dropdown

**Returns:** `string` - Translated provider name

**Example:**
```php
public function get_name() {
    return __('Twilio SMS', 'sierra-sms-commands');
}
```

### `get_slug()`

**Purpose:** Unique identifier for provider (used in options, logs)

**Returns:** `string` - Lowercase slug with no spaces

**Example:**
```php
public function get_slug() {
    return 'twilio';
}
```

**Note:** Slug appears in audit logs as `sms:{slug}` (e.g., `sms:twilio`)

### `get_config_fields()`

**Purpose:** Define settings fields for admin configuration page

**Returns:** `array` - Array of field definitions

**Field structure:**
```php
[
    'id' => 'setting_key',           // Option key (will be prefixed with sierra_sms_)
    'label' => 'Field Label',        // Translated label
    'type' => 'text|password|url',   // Input type
    'description' => 'Help text',    // Translated description
    'placeholder' => 'Example',      // Placeholder text
    'required' => true|false         // Is field required
]
```

**Example:**
```php
public function get_config_fields() {
    return [
        [
            'id' => 'twilio_account_sid',
            'label' => __('Account SID', 'sierra-sms-commands'),
            'type' => 'text',
            'description' => __('Found in Twilio Console dashboard', 'sierra-sms-commands'),
            'placeholder' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'required' => true
        ],
        [
            'id' => 'twilio_auth_token',
            'label' => __('Auth Token', 'sierra-sms-commands'),
            'type' => 'password',
            'description' => __('Primary auth token from Twilio Console', 'sierra-sms-commands'),
            'required' => true
        ],
        [
            'id' => 'twilio_phone_number',
            'label' => __('Phone Number', 'sierra-sms-commands'),
            'type' => 'text',
            'description' => __('Your Twilio phone number in E.164 format', 'sierra-sms-commands'),
            'placeholder' => '+14155551234',
            'required' => true
        ]
    ];
}
```

**Accessing values:**
```php
$account_sid = get_option('sierra_sms_twilio_account_sid');
$auth_token = get_option('sierra_sms_twilio_auth_token');
$phone = get_option('sierra_sms_twilio_phone_number');
```

### `send_sms($to, $message)`

**Purpose:** Send outbound SMS message

**Parameters:**
- `$to` (string) - Recipient phone number in E.164 format
- `$message` (string) - Message text (max 160 characters for standard SMS)

**Returns:** `bool` - True if sent successfully, false on failure

**Implementation tips:**
- Use `wp_remote_post()` for API calls
- Set reasonable timeout (10 seconds)
- Log errors for debugging
- Return boolean success status
- Handle rate limiting gracefully

**Example:**
```php
public function send_sms($to, $message) {
    $api_key = get_option('sierra_sms_my_provider_api_key');

    $response = wp_remote_post('https://api.provider.com/v1/sms', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'to' => $to,
            'text' => $message
        ]),
        'timeout' => 10
    ]);

    // Handle errors
    if (is_wp_error($response)) {
        error_log('SMS send failed: ' . $response->get_error_message());
        return false;
    }

    // Check status code
    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        $body = wp_remote_retrieve_body($response);
        error_log("SMS send failed with code $code: $body");
        return false;
    }

    return true;
}
```

### `validate_webhook($request)`

**Purpose:** Validate inbound webhook requests are authentic

**Parameters:**
- `$request` (WP_REST_Request) - WordPress REST request object

**Returns:** `bool` - True if request is valid, false otherwise

**Security methods:**

#### HMAC Signature Validation (Recommended)

```php
public function validate_webhook($request) {
    // Get signature from header
    $signature = $request->get_header('X-Provider-Signature');
    if (!$signature) {
        return false;
    }

    // Get shared secret
    $secret = get_option('sierra_sms_my_provider_secret_key');

    // Compute expected signature from body
    $body = $request->get_body();
    $expected = hash_hmac('sha256', $body, $secret);

    // Constant-time comparison
    return hash_equals($expected, $signature);
}
```

#### OAuth/Bearer Token

```php
public function validate_webhook($request) {
    $auth = $request->get_header('Authorization');
    if (!$auth) {
        return false;
    }

    // Extract token
    if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
        $token = $matches[1];
        $expected_token = get_option('sierra_sms_my_provider_webhook_token');

        return hash_equals($expected_token, $token);
    }

    return false;
}
```

#### IP Whitelist (Least secure)

```php
public function validate_webhook($request) {
    $allowed_ips = [
        '192.0.2.1',
        '198.51.100.0/24',
        '203.0.113.0'
    ];

    $remote_ip = $_SERVER['REMOTE_ADDR'];

    foreach ($allowed_ips as $allowed) {
        if (strpos($allowed, '/') !== false) {
            // CIDR notation
            if ($this->ip_in_range($remote_ip, $allowed)) {
                return true;
            }
        } else {
            // Exact match
            if ($remote_ip === $allowed) {
                return true;
            }
        }
    }

    return false;
}
```

**Note:** Always validate webhooks in production. Return `false` for invalid requests.

### `parse_inbound_message($request)`

**Purpose:** Extract phone number and message text from webhook

**Parameters:**
- `$request` (WP_REST_Request) - WordPress REST request object

**Returns:** `array|false` - Array with keys or false on failure:
```php
[
    'from' => '+14155551234',  // Sender phone number (E.164)
    'body' => 'open gate 5'    // Message text
]
```

**Implementation:**

```php
public function parse_inbound_message($request) {
    // JSON body example
    $body = json_decode($request->get_body(), true);

    // Check required fields exist
    if (!isset($body['from']) || !isset($body['message'])) {
        return false;
    }

    // Normalize phone number to E.164
    $from = $this->normalize_phone($body['from']);

    return [
        'from' => $from,
        'body' => trim($body['message'])
    ];
}

/**
 * Normalize phone number to E.164 format
 */
private function normalize_phone($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Add + if missing
    if (substr($phone, 0, 1) !== '+') {
        $phone = '+' . $phone;
    }

    return $phone;
}
```

**Form-encoded body example:**
```php
public function parse_inbound_message($request) {
    // Get from form params
    $from = $request->get_param('From');
    $body = $request->get_param('Body');

    if (!$from || !$body) {
        return false;
    }

    return [
        'from' => $this->normalize_phone($from),
        'body' => trim($body)
    ];
}
```

## Real-World Examples

### Example: Vonage (Nexmo) Provider

```php
<?php
namespace SierraSMSCommands\Providers;

class Vonage_Provider implements SMS_Provider {

    public function get_name() {
        return __('Vonage (Nexmo)', 'sierra-sms-commands');
    }

    public function get_slug() {
        return 'vonage';
    }

    public function get_config_fields() {
        return [
            [
                'id' => 'vonage_api_key',
                'label' => __('API Key', 'sierra-sms-commands'),
                'type' => 'text',
                'required' => true
            ],
            [
                'id' => 'vonage_api_secret',
                'label' => __('API Secret', 'sierra-sms-commands'),
                'type' => 'password',
                'required' => true
            ],
            [
                'id' => 'vonage_phone_number',
                'label' => __('Phone Number', 'sierra-sms-commands'),
                'type' => 'text',
                'placeholder' => '+14155551234',
                'required' => true
            ]
        ];
    }

    public function send_sms($to, $message) {
        $api_key = get_option('sierra_sms_vonage_api_key');
        $api_secret = get_option('sierra_sms_vonage_api_secret');
        $from = get_option('sierra_sms_vonage_phone_number');

        $response = wp_remote_post('https://rest.nexmo.com/sms/json', [
            'body' => [
                'api_key' => $api_key,
                'api_secret' => $api_secret,
                'from' => $from,
                'to' => ltrim($to, '+'), // Vonage doesn't want + prefix
                'text' => $message
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Check if message sent successfully
        return isset($body['messages'][0]['status']) && $body['messages'][0]['status'] === '0';
    }

    public function validate_webhook($request) {
        // Vonage doesn't provide signature validation for inbound SMS
        // Use IP whitelist or custom token
        $token = $request->get_param('token');
        $expected = get_option('sierra_sms_vonage_webhook_token');

        return hash_equals($expected ?: '', $token ?: '');
    }

    public function parse_inbound_message($request) {
        // Vonage sends as query parameters
        $from = $request->get_param('msisdn');
        $body = $request->get_param('text');

        if (!$from || !$body) {
            return false;
        }

        return [
            'from' => '+' . $from, // Add + prefix
            'body' => $body
        ];
    }
}
```

### Example: Plivo Provider

```php
<?php
namespace SierraSMSCommands\Providers;

class Plivo_Provider implements SMS_Provider {

    public function get_name() {
        return __('Plivo', 'sierra-sms-commands');
    }

    public function get_slug() {
        return 'plivo';
    }

    public function get_config_fields() {
        return [
            [
                'id' => 'plivo_auth_id',
                'label' => __('Auth ID', 'sierra-sms-commands'),
                'type' => 'text',
                'required' => true
            ],
            [
                'id' => 'plivo_auth_token',
                'label' => __('Auth Token', 'sierra-sms-commands'),
                'type' => 'password',
                'required' => true
            ],
            [
                'id' => 'plivo_phone_number',
                'label' => __('Phone Number', 'sierra-sms-commands'),
                'type' => 'text',
                'placeholder' => '+14155551234',
                'required' => true
            ]
        ];
    }

    public function send_sms($to, $message) {
        $auth_id = get_option('sierra_sms_plivo_auth_id');
        $auth_token = get_option('sierra_sms_plivo_auth_token');
        $from = get_option('sierra_sms_plivo_phone_number');

        $url = "https://api.plivo.com/v1/Account/{$auth_id}/Message/";

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$auth_id}:{$auth_token}"),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'src' => $from,
                'dst' => $to,
                'text' => $message
            ]),
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        return $code >= 200 && $code < 300;
    }

    public function validate_webhook($request) {
        // Plivo signature validation
        $signature = $request->get_header('X-Plivo-Signature');
        if (!$signature) {
            return false;
        }

        $auth_token = get_option('sierra_sms_plivo_auth_token');
        $url = $request->get_param('_url'); // Full webhook URL
        $params = $request->get_params();

        // Sort parameters
        ksort($params);

        // Build string to sign
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        // Compute signature
        $expected = base64_encode(hash_hmac('sha1', $data, $auth_token, true));

        return hash_equals($expected, $signature);
    }

    public function parse_inbound_message($request) {
        $from = $request->get_param('From');
        $body = $request->get_param('Text');

        if (!$from || !$body) {
            return false;
        }

        return [
            'from' => $from,
            'body' => $body
        ];
    }
}
```

## Testing Your Provider

### Unit Testing

Create tests in `/tests/providers/`:

```php
class Test_My_Provider extends WP_UnitTestCase {

    private $provider;

    public function setUp() {
        parent::setUp();
        $this->provider = new \SierraSMSCommands\Providers\My_Provider();

        // Set test credentials
        update_option('sierra_sms_my_provider_api_key', 'test_key');
        update_option('sierra_sms_my_provider_phone_number', '+14155551234');
    }

    public function test_get_name() {
        $this->assertEquals('My SMS Provider', $this->provider->get_name());
    }

    public function test_get_slug() {
        $this->assertEquals('my-provider', $this->provider->get_slug());
    }

    public function test_config_fields() {
        $fields = $this->provider->get_config_fields();

        $this->assertIsArray($fields);
        $this->assertCount(2, $fields);
        $this->assertEquals('my_provider_api_key', $fields[0]['id']);
    }

    public function test_parse_inbound_message() {
        $request = new \WP_REST_Request('POST');
        $request->set_body(json_encode([
            'from' => '+14155551234',
            'text' => 'open gate 5'
        ]));

        $parsed = $this->provider->parse_inbound_message($request);

        $this->assertIsArray($parsed);
        $this->assertEquals('+14155551234', $parsed['from']);
        $this->assertEquals('open gate 5', $parsed['body']);
    }
}
```

### Manual Testing

**Test outbound SMS:**
```php
// In WordPress admin or wp-admin/admin-ajax.php
$provider = new \SierraSMSCommands\Providers\My_Provider();
$result = $provider->send_sms('+14155551234', 'Test message from WordPress');
var_dump($result); // Should be true
```

**Test webhook:**
```bash
curl -X POST https://yoursite.com/wp-json/sierra/v1/sms/webhook \
  -H "Content-Type: application/json" \
  -H "X-My-Provider-Signature: test_signature" \
  -d '{
    "from": "+14155551234",
    "text": "status"
  }'
```

## Best Practices

### Security

- **Always validate webhooks** - Never trust inbound requests without verification
- **Use HMAC signatures** when possible - More secure than IP whitelisting
- **Constant-time comparison** - Use `hash_equals()` to prevent timing attacks
- **Sanitize input** - Escape and validate all data from webhooks

### Error Handling

- **Log errors** - Use `error_log()` for debugging
- **Fail gracefully** - Return false on errors, don't throw exceptions
- **User-friendly messages** - Log technical details, show simple errors to users

### Performance

- **Set timeouts** - Use 10-second timeout for API calls
- **Don't retry in webhook** - If send fails, log and move on
- **Cache configuration** - Options are cached, safe to call `get_option()` multiple times

### Documentation

Create user-facing setup guide in `/docs/`:

**Example:** `/docs/my-provider-setup.md`

```markdown
---
title: My Provider Setup
order: 4
category: Setup
screens: [settings_page_sierra-sms-commands]
---

# My Provider Setup Guide

## Step 1: Create Account
...

## Step 2: Get API Credentials
...

## Step 3: Configure Webhook
Set webhook URL to:
`https://yoursite.com/wp-json/sierra/v1/sms/webhook`
...
```

## Submitting Your Provider

If you'd like to contribute your provider:

1. Fork the repository
2. Create provider class in `/includes/providers/`
3. Add registration in plugin main file
4. Write unit tests
5. Create setup documentation
6. Submit pull request

Include:
- Provider class file
- Unit tests
- Setup documentation
- Example webhook payloads
- Tested with real account

## Further Reading

- [Provider Manager source](../includes/class-provider-manager.php)
- [Telnyx implementation](../includes/providers/class-telnyx-provider.php)
- [Twilio implementation](../includes/providers/class-twilio-provider.php)
- [Textbelt implementation](../includes/providers/class-textbelt-provider.php)
- [Webhook Handler](../includes/class-webhook-handler.php)
