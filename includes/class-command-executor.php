<?php
/**
 * Command Executor
 *
 * Executes parsed commands by updating resort data
 * Logs all changes to audit system
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands;

/**
 * Command_Executor Class
 */
class Command_Executor {
	/**
	 * Execute a command
	 *
	 * @param array $command Parsed command
	 * @param int   $user_id User ID executing command
	 * @param string $provider Provider slug (for audit trail)
	 * @return array {
	 *     @type bool   $success
	 *     @type string $message
	 *     @type array  $undo_data Data needed to undo this command
	 * }
	 */
	public static function execute( $command, $user_id, $provider = '' ) {
		if ( ! isset( $command['action'], $command['post_id'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Invalid command structure', 'sierra-sms-commands' ),
			];
		}

		// Set current user for audit trail
		wp_set_current_user( $user_id );

		// Handle grooming separately
		if ( $command['action'] === 'groom' ) {
			return self::execute_groom( $command, $provider );
		}

		// Get current status for undo
		$old_status = get_post_meta( $command['post_id'], 'status', true );

		// Determine new status
		$new_status = ( $command['action'] === 'close' ) ? 'closed' : 'open';

		// Update post meta directly
		$updated = update_post_meta( $command['post_id'], 'status', $new_status );

		if ( $updated === false && $old_status === $new_status ) {
			// Already in requested state
			$action_label = ( $command['action'] === 'close' ) ? __( 'closed', 'sierra-sms-commands' ) : __( 'opened', 'sierra-sms-commands' );
			return [
				'success' => true,
				'message' => sprintf(
					__( '%s %s is already %s', 'sierra-sms-commands' ),
					Command_Parser::get_type_label( $command['type'] ),
					$command['name'],
					$action_label
				),
			];
		}

		if ( $updated === false ) {
			return [
				'success' => false,
				'message' => __( 'Failed to update status', 'sierra-sms-commands' ),
			];
		}

		// Create audit log entry with SMS source
		if ( class_exists( '\SierraResortData\Audit_Logger' ) ) {
			\SierraResortData\Audit_Logger::log_change(
				$command['post_id'],
				$command['type'],
				'status',
				$old_status,
				$new_status,
				'sms:' . $provider
			);
		}

		$action_label = ( $command['action'] === 'close' ) ? __( 'closed', 'sierra-sms-commands' ) : __( 'opened', 'sierra-sms-commands' );

		return [
			'success'   => true,
			'message'   => sprintf(
				__( '%s %s %s successfully', 'sierra-sms-commands' ),
				Command_Parser::get_type_label( $command['type'] ),
				$command['name'],
				$action_label
			),
			'undo_data' => [
				'post_id'    => $command['post_id'],
				'post_type'  => $command['type'],
				'post_title' => $command['name'],
				'old_status' => $old_status,
				'new_status' => $new_status,
			],
		];
	}

	/**
	 * Execute groom command
	 *
	 * @param array $command Parsed command
	 * @param string $provider Provider slug
	 * @return array Result
	 */
	private static function execute_groom( $command, $provider ) {
		// Only trails can be groomed
		if ( $command['type'] !== 'sierra_trail' ) {
			return [
				'success' => false,
				'message' => __( 'Only trails can be groomed', 'sierra-sms-commands' ),
			];
		}

		// Get current groomed status
		$old_groomed = get_post_meta( $command['post_id'], 'groomed', true );
		$new_groomed = 'yes';

		// Update groomed status
		$updated = update_post_meta( $command['post_id'], 'groomed', $new_groomed );

		if ( $updated === false && $old_groomed === $new_groomed ) {
			return [
				'success' => true,
				'message' => sprintf(
					__( 'Trail %s is already marked as groomed', 'sierra-sms-commands' ),
					$command['name']
				),
			];
		}

		if ( $updated === false ) {
			return [
				'success' => false,
				'message' => __( 'Failed to update grooming status', 'sierra-sms-commands' ),
			];
		}

		// Create audit log entry
		if ( class_exists( '\SierraResortData\Audit_Logger' ) ) {
			\SierraResortData\Audit_Logger::log_change(
				$command['post_id'],
				$command['type'],
				'groomed',
				$old_groomed,
				$new_groomed,
				'sms:' . $provider
			);
		}

		return [
			'success'   => true,
			'message'   => sprintf(
				__( 'Trail %s marked as groomed successfully', 'sierra-sms-commands' ),
				$command['name']
			),
			'undo_data' => [
				'post_id'    => $command['post_id'],
				'post_type'  => $command['type'],
				'post_title' => $command['name'],
				'old_groomed' => $old_groomed,
				'new_groomed' => $new_groomed,
			],
		];
	}

	/**
	 * Get current resort status
	 *
	 * @return string Status message
	 */
	public static function get_status() {
		$lifts_open = self::count_open( 'sierra_lift' );
		$lifts_total = self::count_total( 'sierra_lift' );

		$trails_open = self::count_open( 'sierra_trail' );
		$trails_total = self::count_total( 'sierra_trail' );

		$gates_open = self::count_open( 'sierra_gate' );
		$gates_total = self::count_total( 'sierra_gate' );

		return sprintf(
			__( "Resort Status:\nLifts: %d/%d open\nTrails: %d/%d open\nGates: %d/%d open", 'sierra-sms-commands' ),
			$lifts_open,
			$lifts_total,
			$trails_open,
			$trails_total,
			$gates_open,
			$gates_total
		);
	}

	/**
	 * Count open items of a post type
	 */
	private static function count_open( $post_type ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE p.post_type = %s
			AND p.post_status = 'publish'
			AND pm.meta_key = 'status'
			AND pm.meta_value = 'open'",
			$post_type
		) );
	}

	/**
	 * Count total items of a post type
	 */
	private static function count_total( $post_type ) {
		return wp_count_posts( $post_type )->publish;
	}

	/**
	 * Get REST endpoint for post type
	 */
	private static function get_rest_endpoint( $post_type ) {
		$map = [
			'sierra_lift'         => 'lifts',
			'sierra_trail'        => 'trails',
			'sierra_gate'         => 'gates',
			'sierra_park_feature' => 'park-features',
		];

		return $map[ $post_type ] ?? 'lifts';
	}
}
