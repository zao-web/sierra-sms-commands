<?php
/**
 * Command Parser
 *
 * Parses natural language SMS commands into structured data
 * Supports fuzzy matching for trail/lift/gate names
 *
 * @package SierraSMSCommands
 */

namespace SierraSMSCommands;

/**
 * Command_Parser Class
 */
class Command_Parser {
	/**
	 * Parse SMS command
	 *
	 * @param string $message SMS message text
	 * @return array {
	 *     @type string $action Action (open, close, status, help, undo, confirm)
	 *     @type string $type Post type (sierra_lift, sierra_trail, sierra_gate, etc.)
	 *     @type string $name Item name
	 *     @type int    $post_id Matched post ID (if found)
	 *     @type array  $matches Multiple matches (if ambiguous)
	 *     @type string $error Error message (if invalid)
	 * }
	 */
	public static function parse( $message ) {
		$message = trim( strtolower( $message ) );

		// Special commands
		if ( in_array( $message, [ 'status', 'stat', 'info' ], true ) ) {
			return [ 'action' => 'status' ];
		}

		if ( in_array( $message, [ 'help', '?', 'commands' ], true ) ) {
			return [ 'action' => 'help' ];
		}

		if ( in_array( $message, [ 'undo', 'cancel', 'revert' ], true ) ) {
			return [ 'action' => 'undo' ];
		}

		if ( in_array( $message, [ 'yes', 'y', 'confirm', 'ok' ], true ) ) {
			return [ 'action' => 'confirm' ];
		}

		if ( in_array( $message, [ 'no', 'n', 'cancel' ], true ) ) {
			return [ 'action' => 'cancel' ];
		}

		// Parse action + type + name
		// Examples: "open lift grandview", "close broadway", "open gate 5"
		$pattern = '/^(open|close|reopen)\s+(?:(lift|trail|gate|park|feature)\s+)?(.+)$/i';

		if ( ! preg_match( $pattern, $message, $matches ) ) {
			return [
				'error' => __( 'Invalid command format. Try "open lift name", "close trail name", "status", or "help".', 'sierra-sms-commands' ),
			];
		}

		$action = strtolower( $matches[1] );
		$type_hint = ! empty( $matches[2] ) ? strtolower( $matches[2] ) : null;
		$name = trim( $matches[3] );

		// Map type hints to post types
		$type_map = [
			'lift'    => 'sierra_lift',
			'trail'   => 'sierra_trail',
			'gate'    => 'sierra_gate',
			'park'    => 'sierra_park_feature',
			'feature' => 'sierra_park_feature',
		];

		// Determine search types
		$search_types = [];
		if ( $type_hint && isset( $type_map[ $type_hint ] ) ) {
			$search_types = [ $type_map[ $type_hint ] ];
		} else {
			// Search all types if no hint provided
			$search_types = array_values( $type_map );
		}

		// Find matching posts
		$matches = self::find_matches( $name, $search_types );

		if ( empty( $matches ) ) {
			$type_str = $type_hint ? " $type_hint" : '';
			return [
				'error' => sprintf(
					__( 'No%s found matching "%s". Please check the name and try again.', 'sierra-sms-commands' ),
					$type_str,
					$name
				),
			];
		}

		if ( count( $matches ) > 1 ) {
			return [
				'action'  => $action,
				'matches' => $matches,
				'error'   => __( 'Multiple matches found. Please be more specific.', 'sierra-sms-commands' ),
			];
		}

		$match = $matches[0];

		return [
			'action'  => $action,
			'type'    => $match['post_type'],
			'name'    => $match['post_title'],
			'post_id' => $match['ID'],
		];
	}

	/**
	 * Find matching posts using fuzzy matching
	 *
	 * @param string $search_term Search term
	 * @param array  $post_types Post types to search
	 * @return array Array of matching posts
	 */
	private static function find_matches( $search_term, $post_types ) {
		global $wpdb;

		$search_term = strtolower( $search_term );

		// Exact match first
		$posts = get_posts( [
			'post_type'      => $post_types,
			'title'          => $search_term,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );

		if ( ! empty( $posts ) ) {
			return $posts;
		}

		// Partial match (contains)
		$posts = get_posts( [
			'post_type'      => $post_types,
			's'              => $search_term,
			'posts_per_page' => 10,
			'post_status'    => 'publish',
		] );

		if ( ! empty( $posts ) ) {
			// Filter to only posts that actually contain the search term
			$posts = array_filter( $posts, function( $post ) use ( $search_term ) {
				return stripos( $post->post_title, $search_term ) !== false;
			} );
		}

		// If still no matches, try fuzzy matching using Levenshtein distance
		if ( empty( $posts ) ) {
			$all_posts = get_posts( [
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			] );

			$fuzzy_matches = [];
			foreach ( $all_posts as $post ) {
				$distance = levenshtein(
					strtolower( $post->post_title ),
					$search_term
				);

				// Accept if distance is within 30% of title length
				$threshold = ceil( strlen( $post->post_title ) * 0.3 );
				if ( $distance <= $threshold && $distance <= 5 ) {
					$fuzzy_matches[] = [
						'post'     => $post,
						'distance' => $distance,
					];
				}
			}

			// Sort by distance (closest first)
			usort( $fuzzy_matches, function( $a, $b ) {
				return $a['distance'] - $b['distance'];
			} );

			// Return top 3 matches
			$posts = array_slice( array_column( $fuzzy_matches, 'post' ), 0, 3 );
		}

		return $posts;
	}

	/**
	 * Format command for display
	 *
	 * @param array $command Parsed command
	 * @return string Formatted command string
	 */
	public static function format_command( $command ) {
		if ( isset( $command['error'] ) ) {
			return $command['error'];
		}

		if ( $command['action'] === 'status' ) {
			return __( 'Get status report', 'sierra-sms-commands' );
		}

		if ( $command['action'] === 'help' ) {
			return __( 'Show help information', 'sierra-sms-commands' );
		}

		if ( $command['action'] === 'undo' ) {
			return __( 'Undo last command', 'sierra-sms-commands' );
		}

		if ( isset( $command['post_id'] ) ) {
			$action_labels = [
				'open'   => __( 'Open', 'sierra-sms-commands' ),
				'close'  => __( 'Close', 'sierra-sms-commands' ),
				'reopen' => __( 'Reopen', 'sierra-sms-commands' ),
			];

			$action_label = $action_labels[ $command['action'] ] ?? $command['action'];
			$type_label = self::get_type_label( $command['type'] );

			return sprintf(
				'%s %s: %s',
				$action_label,
				$type_label,
				$command['name']
			);
		}

		return __( 'Unknown command', 'sierra-sms-commands' );
	}

	/**
	 * Get human-readable label for post type
	 *
	 * @param string $post_type Post type slug
	 * @return string Label
	 */
	private static function get_type_label( $post_type ) {
		$labels = [
			'sierra_lift'         => __( 'Lift', 'sierra-sms-commands' ),
			'sierra_trail'        => __( 'Trail', 'sierra-sms-commands' ),
			'sierra_gate'         => __( 'Gate', 'sierra-sms-commands' ),
			'sierra_park_feature' => __( 'Park Feature', 'sierra-sms-commands' ),
		];

		return $labels[ $post_type ] ?? $post_type;
	}

	/**
	 * Generate help message
	 *
	 * @return string Help text
	 */
	public static function get_help_message() {
		return __(
			"SMS Commands:\n\n" .
			"• open [type] [name] - Open a lift/trail/gate\n" .
			"• close [type] [name] - Close a lift/trail/gate\n" .
			"• status - Get current status\n" .
			"• undo - Reverse last command\n" .
			"• help - Show this message\n\n" .
			"Examples:\n" .
			"• open lift grandview\n" .
			"• close broadway\n" .
			"• open gate 5",
			'sierra-sms-commands'
		);
	}
}
