<?php
/**
 * Tools → Staging Guard: shows caught mail and blocked outbound calls.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

/**
 * Admin page for the two logs, with a clear button.
 */
final class LogPage {

	public const SLUG = 'bines-staging-guard';

	/**
	 * Register the Tools submenu and the clear handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_bines_guard_clear', array( self::class, 'handle_clear' ) );
	}

	/**
	 * Add the submenu page under Tools.
	 *
	 * @return void
	 */
	public static function menu(): void {
		add_management_page(
			__( 'Staging Guard', 'bines-staging-guard' ),
			__( 'Staging Guard', 'bines-staging-guard' ),
			'manage_options',
			self::SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * Clear both logs, then redirect back.
	 *
	 * @return void
	 */
	public static function handle_clear(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'bines-staging-guard' ) );
		}
		check_admin_referer( 'bines_guard_clear' );
		delete_option( MailCatcher::LOG_OPTION );
		delete_option( Firewall::LOG_OPTION );
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::SLUG . '&cleared=1' ) );
		exit;
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render(): void {
		$mail = (array) get_option( MailCatcher::LOG_OPTION, array() );
		$http = (array) get_option( Firewall::LOG_OPTION, array() );
		echo '<div class="wrap"><h1>' . esc_html__( 'Staging Guard', 'bines-staging-guard' ) . '</h1>';
		echo '<p>' . esc_html( Badge::label() ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="bines_guard_clear">';
		wp_nonce_field( 'bines_guard_clear' );
		submit_button( __( 'Clear both logs', 'bines-staging-guard' ), 'secondary', 'submit', false );
		echo '</form>';
		echo '<h2>' . esc_html__( 'Caught mail', 'bines-staging-guard' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'When', 'bines-staging-guard' ) . '</th><th>' . esc_html__( 'To', 'bines-staging-guard' ) . '</th><th>' . esc_html__( 'Subject', 'bines-staging-guard' ) . '</th><th>' . esc_html__( 'Message', 'bines-staging-guard' ) . '</th></tr></thead><tbody>';
		echo self::render_mail_rows( $mail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows are escaped inside.
		echo '</tbody></table>';
		echo '<h2>' . esc_html__( 'Blocked outbound calls', 'bines-staging-guard' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'When', 'bines-staging-guard' ) . '</th><th>' . esc_html__( 'Host', 'bines-staging-guard' ) . '</th><th>' . esc_html__( 'Method', 'bines-staging-guard' ) . '</th></tr></thead><tbody>';
		echo self::render_http_rows( $http ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows are escaped inside.
		echo '</tbody></table></div>';
	}

	/**
	 * Table rows for caught mail, newest first, fully escaped.
	 *
	 * @param array $log Entries from MailCatcher.
	 * @return string HTML rows.
	 */
	public static function render_mail_rows( array $log ): string {
		if ( array() === $log ) {
			return '<tr><td colspan="4">' . esc_html( __( 'Nothing caught yet.', 'bines-staging-guard' ) ) . '</td></tr>';
		}
		$out = '';
		foreach ( array_reverse( $log ) as $e ) {
			$out .= '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $e['time'] ) ) . '</td>'
				. '<td>' . esc_html( $e['to'] ) . '</td>'
				. '<td>' . esc_html( $e['subject'] ) . '</td>'
				. '<td><pre style="white-space:pre-wrap;margin:0">' . esc_html( $e['message'] ) . '</pre></td></tr>';
		}
		return $out;
	}

	/**
	 * Table rows for blocked calls, newest first.
	 *
	 * @param array $log Entries from Firewall.
	 * @return string HTML rows.
	 */
	public static function render_http_rows( array $log ): string {
		if ( array() === $log ) {
			return '<tr><td colspan="3">' . esc_html( __( 'Nothing blocked yet.', 'bines-staging-guard' ) ) . '</td></tr>';
		}
		$out = '';
		foreach ( array_reverse( $log ) as $e ) {
			$out .= '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $e['time'] ) ) . '</td>'
				. '<td>' . esc_html( $e['host'] ) . '</td>'
				. '<td>' . esc_html( $e['method'] ) . '</td></tr>';
		}
		return $out;
	}
}
