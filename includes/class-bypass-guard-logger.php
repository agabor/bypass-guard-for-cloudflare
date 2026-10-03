<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bypass_Guard_Logger {

	const CACHE_GROUP = 'bypass_guard_log';

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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
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

		self::flush_cache();

		self::trim_log();
	}

	public static function get_entries( int $limit = 500 ): array {
		global $wpdb;

		$cache_key = 'entries_' . $limit;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY logged_at DESC, id DESC LIMIT %d',
				$table_name,
				$limit
			),
			ARRAY_A
		);

		$results = is_array( $results ) ? $results : array();

		wp_cache_set( $cache_key, $results, self::CACHE_GROUP );

		return $results;
	}

	public static function clear_log(): void {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare( 'TRUNCATE TABLE %i', $table_name )
		);

		self::flush_cache();
	}

	private static function trim_log(): void {
		global $wpdb;

		$table_name = self::get_table_name();

		$cache_key = 'count';
		$count     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $count ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table_name )
			);

			wp_cache_set( $cache_key, $count, self::CACHE_GROUP );
		}

		if ( $count > BYPASS_GUARD_LOG_LIMIT ) {
			$delete_count = $count - BYPASS_GUARD_LOG_LIMIT;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM %i ORDER BY id ASC LIMIT %d',
					$table_name,
					$delete_count
				)
			);

			self::flush_cache();
		}
	}

	private static function flush_cache(): void {
		wp_cache_delete( 'count', self::CACHE_GROUP );
		wp_cache_flush_group( self::CACHE_GROUP );
	}
}