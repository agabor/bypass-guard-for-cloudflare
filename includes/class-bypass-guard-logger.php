<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bypass_Guard_Logger {

	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'bypass_guard_log';
	}

	public static function create_table(): void {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			logged_at datetime NOT NULL,
			ip_address varchar(45) NOT NULL,
			request_method varchar(10) NOT NULL,
			request_url text NOT NULL,
			user_agent text NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function log_blocked_request( string $ip, string $method, string $url, string $user_agent ): void {
		global $wpdb;

		$table_name = self::get_table_name();

		$wpdb->insert(
			$table_name,
			array(
				'logged_at'      => current_time( 'mysql' ),
				'ip_address'     => $ip,
				'request_method' => $method,
				'request_url'    => $url,
				'user_agent'     => $user_agent,
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		self::trim_log();
	}

	public static function get_entries( int $limit = 500 ): array {
		global $wpdb;

		$table_name = self::get_table_name();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY logged_at DESC, id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	public static function clear_log(): void {
		global $wpdb;

		$table_name = self::get_table_name();

		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	private static function trim_log(): void {
		global $wpdb;

		$table_name = self::get_table_name();

		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		if ( $count > BYPASS_GUARD_LOG_LIMIT ) {
			$delete_count = $count - BYPASS_GUARD_LOG_LIMIT;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_name ORDER BY id ASC LIMIT %d",
					$delete_count
				)
			);
		}
	}
}