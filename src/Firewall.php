<?php
/**
 * Outbound HTTP firewall for non-production sites.
 *
 * WordPress routes every wp_remote_* call through the pre_http_request
 * filter, so this one hook is the choke point for webhooks, mail APIs,
 * analytics pushes and booking systems. Anything not allowlisted is refused.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

use WP_Error;

/**
 * Allowlist decision and the pre_http_request callback.
 */
final class Firewall {

	public const LOG_OPTION = 'bines_guard_http_log';
	public const LOG_CAP    = 200;

	/**
	 * Hosts that must keep working on staging: core/plugin updates and
	 * read-only booking availability. POST to the booking provider is
	 * deliberately absent so staging can never create a real appointment.
	 *
	 * @return array<int, array{host:string, methods:string[]}>
	 */
	public static function default_allowlist(): array {
		return array(
			array( 'host' => 'api.wordpress.org', 'methods' => array( '*' ) ),
			array( 'host' => 'downloads.wordpress.org', 'methods' => array( '*' ) ),
			array( 'host' => '*.wordpress.org', 'methods' => array( '*' ) ),
			array( 'host' => '*.w.org', 'methods' => array( '*' ) ),
			array( 'host' => 'api.github.com', 'methods' => array( 'GET' ) ),
			array( 'host' => '*.cliniko.com', 'methods' => array( 'GET' ) ),
		);
	}

	/**
	 * Pure allowlist check.
	 *
	 * @param string $url       Full request URL.
	 * @param string $method    HTTP method in any case.
	 * @param array  $allowlist Entries of host + methods (see default_allowlist()).
	 * @return boolean True when the request may leave the server.
	 */
	public static function allows( string $url, string $method, array $allowlist ): bool {
		$host = strtolower( (string) host_of( $url ) );
		if ( '' === $host ) {
			return false;
		}
		$method = strtoupper( $method );

		foreach ( $allowlist as $entry ) {
			if ( ! self::host_matches( $host, strtolower( $entry['host'] ) ) ) {
				continue;
			}
			$methods = array_map( 'strtoupper', $entry['methods'] );
			if ( in_array( '*', $methods, true ) || in_array( $method, $methods, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Match a host against an exact host or a "*.example.com" pattern.
	 * The wildcard requires at least one label before the suffix, so
	 * "*.cliniko.com" does not match the bare "cliniko.com".
	 *
	 * @param string $host    Lower-case request host.
	 * @param string $pattern Lower-case allowlist pattern.
	 * @return boolean
	 */
	private static function host_matches( string $host, string $pattern ): bool {
		if ( str_starts_with( $pattern, '*.' ) ) {
			$suffix = substr( $pattern, 1 ); // ".cliniko.com".
			return strlen( $host ) > strlen( $suffix ) && str_ends_with( $host, $suffix );
		}
		return $host === $pattern;
	}

	/**
	 * pre_http_request callback. Returns a WP_Error for blocked calls so the
	 * caller sees a normal failed request rather than a hang or a fatal.
	 *
	 * @param false|array|WP_Error $preempt Value from an earlier filter; non-false means already handled.
	 * @param array                $args    Request arguments; 'method' is read.
	 * @param string               $url     Request URL.
	 * @return false|array|WP_Error
	 */
	public static function intercept( $preempt, array $args, string $url ) {
		if ( false !== $preempt ) {
			return $preempt;
		}
		$method    = (string) ( $args['method'] ?? 'GET' );
		$allowlist = (array) apply_filters( 'bines_guard_allowlist', self::default_allowlist() );

		if ( self::allows( $url, $method, $allowlist ) ) {
			return false;
		}

		self::log( (string) host_of( $url ), strtoupper( $method ) );

		return new WP_Error(
			'bines_guard_blocked',
			__( 'Blocked by BINES Staging Guard: outbound calls are disabled on non-production sites.', 'bines-staging-guard' )
		);
	}

	/**
	 * Append host + method to the capped log. No URL path, no body.
	 *
	 * @param string $host   Request host.
	 * @param string $method Upper-case HTTP method.
	 * @return void
	 */
	private static function log( string $host, string $method ): void {
		$log   = (array) get_option( self::LOG_OPTION, array() );
		$log[] = array( 'host' => $host, 'method' => $method, 'time' => time() );
		$log   = array_slice( $log, -self::LOG_CAP );
		update_option( self::LOG_OPTION, $log, false );
	}

	/**
	 * Hook into WordPress. Called by Guard::boot() only when active.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'pre_http_request', array( self::class, 'intercept' ), 5, 3 );
	}
}

/**
 * Host extraction that does not depend on WordPress being loaded, so the
 * pure decision stays testable. Uses PHP's parse_url directly.
 *
 * @param string $url URL to inspect.
 * @return string Host or empty string.
 */
function host_of( string $url ): string {
	$host = parse_url( $url, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	return is_string( $host ) ? $host : '';
}
