<?php
/**
 * Forces search engines away from non-production sites.
 *
 * Bedrock already discourages indexing outside production; this adds the
 * X-Robots-Tag header and pins the option so a copied database cannot
 * re-enable indexing.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

/**
 * noindex header + forced blog_public = 0.
 */
final class NoIndex {

	/**
	 * Swappable header sender so tests can capture headers.
	 *
	 * @var null|callable(string):void
	 */
	public static $sender = null;

	/**
	 * Send the robots header once per request.
	 *
	 * @return void
	 */
	public static function send_header(): void {
		if ( headers_sent() ) {
			return;
		}
		$send = self::$sender ?? 'header';
		$send( 'X-Robots-Tag: noindex, nofollow' );
	}

	/**
	 * pre_option_blog_public / option_blog_public callback.
	 *
	 * @param boolean|integer|string|array|null $value Stored value (ignored).
	 * @return string Always '0'.
	 */
	public static function force_private( $value ): string {
		unset( $value );
		return '0';
	}

	/**
	 * Hook into WordPress. Called by Guard::boot() only when active.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'send_headers', array( self::class, 'send_header' ) );
		add_filter( 'pre_option_blog_public', array( self::class, 'force_private' ) );
	}
}
