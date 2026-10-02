<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bypass_Guard_Settings_Page {

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$token           = get_option( 'bypass_guard_token', '' );
		$header_detected = (bool) get_option( 'bypass_guard_header_detected', false );
		$filter_enabled  = (bool) get_option( 'bypass_guard_filter_enabled', false );
		$entries         = Bypass_Guard_Logger::get_entries( BYPASS_GUARD_LOG_LIMIT );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Bypass Guard for Cloudflare', 'bypass-guard-for-cloudflare' ) . '</h1>';

		if ( isset( $_GET['bypass_guard_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_GET['bypass_guard_status'] ) );
			self::render_status_notice( $status );
		}

		self::render_token_section( $token, $header_detected );
		self::render_filter_section( $filter_enabled, $header_detected );
		self::render_log_section( $entries );

		echo '</div>';
	}

	private static function render_status_notice( string $status ): void {
		$messages = array(
			'enabled'  => array( __( 'The filter has been enabled.', 'bypass-guard-for-cloudflare' ), 'success' ),
			'disabled' => array( __( 'The filter has been disabled.', 'bypass-guard-for-cloudflare' ), 'success' ),
			'cleared'  => array( __( 'The log has been cleared.', 'bypass-guard-for-cloudflare' ), 'success' ),
			'error'    => array( __( 'The filter cannot be enabled until the header has been detected.', 'bypass-guard-for-cloudflare' ), 'error' ),
		);

		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		list( $message, $type ) = $messages[ $status ];

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	private static function render_token_section( string $token, bool $detected ): void {
		echo '<h2>' . esc_html__( 'Token', 'bypass-guard-for-cloudflare' ) . '</h2>';
		echo '<table class="form-table"><tbody>';

		echo '<tr><th scope="row">' . esc_html__( 'Secret Token', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '<td><code>' . esc_html( $token ) . '</code></td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Header Status', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '<td>';

		if ( $detected ) {
			echo '<span style="color:green;font-weight:bold;">' . esc_html__( 'Detected', 'bypass-guard-for-cloudflare' ) . '</span>';
		} else {
			echo '<span style="color:#b32d2e;font-weight:bold;">' . esc_html__( 'Not detected', 'bypass-guard-for-cloudflare' ) . '</span>';
		}

		echo '</td></tr>';
		echo '</tbody></table>';
	}

	private static function render_filter_section( bool $filter_enabled, bool $header_detected ): void {
		echo '<h2>' . esc_html__( 'Filter', 'bypass-guard-for-cloudflare' ) . '</h2>';
		echo '<p>';

		if ( $filter_enabled ) {
			esc_html_e( 'The filter is currently enabled.', 'bypass-guard-for-cloudflare' );
		} else {
			esc_html_e( 'The filter is currently disabled.', 'bypass-guard-for-cloudflare' );
		}

		echo '</p>';

		if ( $filter_enabled ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'bypass_guard_action', 'bypass_guard_nonce' );
			echo '<input type="hidden" name="action" value="bypass_guard_disable" />';
			submit_button( __( 'Disable Filter', 'bypass-guard-for-cloudflare' ), 'secondary' );
			echo '</form>';
		} else {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'bypass_guard_action', 'bypass_guard_nonce' );
			echo '<input type="hidden" name="action" value="bypass_guard_enable" />';

			$other_attributes = $header_detected ? array() : array( 'disabled' => 'disabled' );
			submit_button( __( 'Enable Filter', 'bypass-guard-for-cloudflare' ), 'primary', 'submit', true, $other_attributes );

			echo '</form>';

			if ( ! $header_detected ) {
				echo '<p class="description">' . esc_html__( 'The filter cannot be enabled until the header has been detected.', 'bypass-guard-for-cloudflare' ) . '</p>';
			}
		}
	}

	private static function render_log_section( array $entries ): void {
		echo '<h2>' . esc_html__( 'Blocked Requests Log', 'bypass-guard-for-cloudflare' ) . '</h2>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom: 1em;">';
		wp_nonce_field( 'bypass_guard_action', 'bypass_guard_nonce' );
		echo '<input type="hidden" name="action" value="bypass_guard_clear_log" />';
		submit_button( __( 'Clear Log', 'bypass-guard-for-cloudflare' ), 'delete', 'submit', false );
		echo '</form>';

		if ( empty( $entries ) ) {
			echo '<p>' . esc_html__( 'No blocked requests have been logged yet.', 'bypass-guard-for-cloudflare' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Date/Time', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '<th>' . esc_html__( 'IP Address', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '<th>' . esc_html__( 'Method', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '<th>' . esc_html__( 'URL', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '<th>' . esc_html__( 'User Agent', 'bypass-guard-for-cloudflare' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $entries as $entry ) {
			echo '<tr>';
			echo '<td>' . esc_html( $entry['logged_at'] ) . '</td>';
			echo '<td>' . esc_html( $entry['ip_address'] ) . '</td>';
			echo '<td>' . esc_html( $entry['request_method'] ) . '</td>';
			echo '<td>' . esc_html( $entry['request_url'] ) . '</td>';
			echo '<td>' . esc_html( $entry['user_agent'] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}