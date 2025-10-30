<?php
/**
 * Plugin Name: Sierra SMS Commands
 * Plugin URI: https://sierraattahoe.com
 * Description: Multi-provider SMS integration for snow reporters to update resort data via text message
 * Version: 1.0.0
 * Author: Sierra at Tahoe
 * Author URI: https://sierraattahoe.com
 * License: GPL v2 or later
 * Text Domain: sierra-sms-commands
 * Domain Path: /languages
 * Requires Plugins: sierra-resort-data
 */

namespace SierraSMSCommands;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'SIERRA_SMS_VERSION', '1.0.0' );
define( 'SIERRA_SMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIERRA_SMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Plugin {
	/**
	 * Instance of this class
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		// Providers
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/providers/interface-sms-provider.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/providers/class-telnyx-provider.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/providers/class-textbelt-provider.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/providers/class-twilio-provider.php';

		// Core classes
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/class-provider-manager.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/class-command-parser.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/class-command-executor.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/class-confirmation-manager.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'includes/class-webhook-handler.php';

		// Admin
		require_once SIERRA_SMS_PLUGIN_DIR . 'admin/class-admin-settings.php';
		require_once SIERRA_SMS_PLUGIN_DIR . 'admin/class-command-log.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		$webhook_handler = new Webhook_Handler();
		$webhook_handler->register_routes();
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu() {
		Admin\Admin_Settings::register();
		Admin\Command_Log::register();
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		Admin\Admin_Settings::register_settings();
	}
}

// Initialize plugin
function sierra_sms_commands() {
	return Plugin::get_instance();
}

// Start the plugin
sierra_sms_commands();

// Activation hook
register_activation_hook( __FILE__, function() {
	// Check if sierra-resort-data is active
	if ( ! is_plugin_active( 'sierra-resort-data/sierra-resort-data.php' ) ) {
		wp_die(
			__( 'Sierra SMS Commands requires the Sierra Resort Data Manager plugin to be installed and activated.', 'sierra-sms-commands' ),
			__( 'Plugin Dependency Error', 'sierra-sms-commands' ),
			[ 'back_link' => true ]
		);
	}

	// Set default options
	add_option( 'sierra_sms_provider', 'telnyx' );
	add_option( 'sierra_sms_confirmation_mode', 'immediate_undo' );
	add_option( 'sierra_sms_undo_window', 120 ); // 2 minutes in seconds

	flush_rewrite_rules();
} );

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
	flush_rewrite_rules();
} );
