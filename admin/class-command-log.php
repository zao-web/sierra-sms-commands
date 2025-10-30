<?php
/**
 * Command Log
 *
 * Displays history of SMS commands
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands\Admin;

/**
 * Command_Log Class
 */
class Command_Log {
	/**
	 * Register admin menu
	 */
	public static function register() {
		add_submenu_page(
			'sierra-resort-data',
			__( 'SMS Command Log', 'sierra-sms-commands' ),
			__( 'SMS Commands', 'sierra-sms-commands' ),
			'manage_options',
			'sierra-sms-command-log',
			[ self::class, 'render_log_page' ]
		);
	}

	/**
	 * Render command log page
	 */
	public static function render_log_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'SMS Command Log', 'sierra-sms-commands' ); ?></h1>
			<p class="description"><?php _e( 'View SMS commands from the audit log (filtered by SMS sources).', 'sierra-sms-commands' ); ?></p>

			<p>
				<strong><?php _e( 'Tip:', 'sierra-sms-commands' ); ?></strong>
				<?php _e( 'SMS commands are logged in the main Audit Logs with source tracking (e.g., "sms:telnyx", "sms:twilio").', 'sierra-sms-commands' ); ?>
				<a href="<?php echo admin_url( 'admin.php?page=sierra-resort-audit-logs' ); ?>"><?php _e( 'View Full Audit Log →', 'sierra-sms-commands' ); ?></a>
			</p>

			<div id="sierra-sms-command-log-root"></div>
		</div>
		<?php
	}
}
