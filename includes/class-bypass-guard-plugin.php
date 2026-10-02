<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bypass_Guard_Plugin {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'check_request' ), 0 );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_post_bypass_guard_enable', array( __CLASS__, 'handle_post_actions' ) );
		add_action( 'admin_post_bypass_guard_disable', array( __CLASS__, 'handle_post_actions' ) );
		add_action( 'admin_post_bypass_guard_clear_log', array( __CLASS__, 'handle_post_actions' ) );
	}

	public static function check_request(): void {
		$stored_token = get_option( 'bypass_guard_token' );

		if ( empty( $stored_token ) ) {
			return;
		}

		$request_token = self::get_request_token();

		if ( null !== $request_token && self::is_token_valid( $request_token ) ) {
			if ( ! get_option( 'bypass_guard_header_detected' ) ) {
				update_option( 'bypass_guard_header_detected', true );
			}

			return;
		}

		if ( get_option( 'bypass_guard_filter_enabled' ) ) {
			self::block_request();
		}
	}

	private static function get_request_token(): ?string {
		$server_key = 'HTTP_' . str_replace( '-', '_', strtoupper( BYPASS_GUARD_HEADER_NAME ) );

		if ( empty( $_SERVER[ $server_key ] ) ) {
			return null;
		}

		return sanitize_text_field( wp_unslash( $_SERVER[ $server_key ] ) );
	}

	private static function is_token_valid( string $token ): bool {
		$stored_token = get_option( 'bypass_guard_token' );

		if ( empty( $stored_token ) ) {
			return false;
		}

		return hash_equals( $stored_token, $token );
	}

	private static function get_client_ip(): string {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	private static function block_request(): void {
		$ip         = self::get_client_ip();
		$method     = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		$url        = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		Bypass_Guard_Logger::log_blocked_request( $ip, $method, $url, $user_agent );

		status_header( 403 );
		nocache_headers();
		exit( 'Forbidden' );
	}

	public static function register_admin_menu(): void {
		add_options_page(
			__( 'Bypass Guard for Cloudflare', 'bypass-guard-for-cloudflare' ),
			__( 'Bypass Guard', 'bypass-guard-for-cloudflare' ),
			'manage_options',
			'bypass-guard-for-cloudflare',
			array( 'Bypass_Guard_Settings_Page', 'render' )
		);
	}

	public static function handle_post_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'bypass-guard-for-cloudflare' ) );
		}

		check_admin_referer( 'bypass_guard_action', 'bypass_guard_nonce' );

		$hook   = current_filter();
		$status = '';

		switch ( $hook ) {
			case 'admin_post_bypass_guard_enable':
				if ( get_option( 'bypass_guard_header_detected' ) ) {
					update_option( 'bypass_guard_filter_enabled', true );
					$status = 'enabled';
				} else {
					$status = 'error';
				}
				break;

			case 'admin_post_bypass_guard_disable':
				update_option( 'bypass_guard_filter_enabled', false );
				$status = 'disabled';
				break;

			case 'admin_post_bypass_guard_clear_log':
				Bypass_Guard_Logger::clear_log();
				$status = 'cleared';
				break;
		}

		$redirect_url = add_query_arg(
			array(
				'page'                => 'bypass-guard-for-cloudflare',
				'bypass_guard_status' => $status,
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}