<?php
/**
 * Visible "you are on staging" signal: admin bar node + front-end strip.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

use WP_Admin_Bar;

/**
 * Renders the red environment badge.
 */
final class Badge {

	/**
	 * Human label, e.g. "STAGING — mail and outbound calls blocked".
	 *
	 * @return string
	 */
	public static function label(): string {
		$env = strtoupper( (string) wp_get_environment_type() );
		/* translators: %s: environment name in upper case */
		return sprintf( __( '%s — mail and outbound calls blocked', 'bines-staging-guard' ), $env );
	}

	/**
	 * Add the node to the admin bar.
	 *
	 * @param WP_Admin_Bar $bar Admin bar instance.
	 * @return void
	 */
	public static function admin_bar( WP_Admin_Bar $bar ): void {
		$bar->add_node(
			array(
				'id'    => 'bines-guard',
				'title' => '<span class="bines-guard-badge">' . esc_html( self::label() ) . '</span>',
				'href'  => false,
				'meta'  => array( 'class' => 'bines-guard-node' ),
			)
		);
	}

	/**
	 * Print a thin strip at the top of the front end for logged-in users.
	 *
	 * @return void
	 */
	public static function front_strip(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		echo '<div class="bines-guard-strip" role="status">' . esc_html( self::label() ) . '</div>';
	}

	/**
	 * Inline CSS for both surfaces. Printed in admin and front head, but
	 * skipped on the front end for logged-out visitors since front_strip()
	 * never renders for them and the CSS would otherwise leak on every
	 * public page for no reason.
	 *
	 * @return void
	 */
	public static function styles(): void {
		if ( ! is_admin() && ! is_user_logged_in() ) {
			return;
		}
		echo '<style id="bines-guard-css">'
			. '#wpadminbar .bines-guard-node{background:#b32d2e!important}'
			. '#wpadminbar .bines-guard-badge{color:#fff;font-weight:700}'
			. '.bines-guard-strip{position:sticky;top:0;z-index:99999;background:#b32d2e;color:#fff;'
			. 'font:700 12px/1.6 system-ui,sans-serif;text-align:center;padding:2px 8px}'
			. '</style>';
	}

	/**
	 * Hook into WordPress. Called by Guard::boot() only when active.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_bar_menu', array( self::class, 'admin_bar' ), 1 );
		add_action( 'wp_body_open', array( self::class, 'front_strip' ) );
		add_action( 'wp_head', array( self::class, 'styles' ) );
		add_action( 'admin_head', array( self::class, 'styles' ) );
	}
}
