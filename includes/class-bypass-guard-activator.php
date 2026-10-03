<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bypass_Guard_Activator {

	public static function activate(): void {
		add_option( 'bypass_guard_token', self::generate_token() );
		add_option( 'bypass_guard_header_detected', false );
		add_option( 'bypass_guard_filter_enabled', false );

		update_option( 'bypass_guard_filter_enabled', false );

		Bypass_Guard_Logger::create_table();
	}

	public static function deactivate(): void {
		update_option( 'bypass_guard_filter_enabled', false );
	}

	public static function generate_token(): string {
		return bin2hex( random_bytes( 12 ) );
	}
}