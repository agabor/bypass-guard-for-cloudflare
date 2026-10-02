<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bypass_Guard_Activator {

	public static function activate(): void {
		$token = bin2hex( random_bytes( 32 ) );

		add_option( 'bypass_guard_token', $token );
		add_option( 'bypass_guard_header_detected', false );
		add_option( 'bypass_guard_filter_enabled', false );

		Bypass_Guard_Logger::create_table();
	}
}