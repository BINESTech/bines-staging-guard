<?php
/**
 * Decides whether the staging guard is active for this request.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

/**
 * Activation decision: guard everything that is not production.
 */
final class Environment {

	/**
	 * Pure decision used by tests and by current(). Thin wrapper around
	 * is_active_for() with no WP_ENV override.
	 *
	 * @param string  $environment_type Value of wp_get_environment_type().
	 * @param boolean $disabled         True when BINES_GUARD_DISABLE is truthy.
	 * @return boolean True when the guard should run.
	 */
	public static function is_active( string $environment_type, bool $disabled ): bool {
		return self::is_active_for( $environment_type, null, $disabled );
	}

	/**
	 * Pure decision including the raw WP_ENV override.
	 *
	 * Bedrock's environment bootstrap only maps a handful of known WP_ENV
	 * values (production, staging, development, local) onto
	 * WP_ENVIRONMENT_TYPE; anything else, such as a typo like
	 * "WP_ENV=stage", falls through and wp_get_environment_type() quietly
	 * reads back as "production". That would fail open, so whenever
	 * WP_ENV is set and is not literally "production" we treat the request
	 * as non-production regardless of what wp_get_environment_type() says.
	 *
	 * @param string      $environment_type Value of wp_get_environment_type().
	 * @param null|string $wp_env           Raw WP_ENV constant value, or null when undefined.
	 * @param boolean     $disabled         True when BINES_GUARD_DISABLE is truthy.
	 * @return boolean True when the guard should run.
	 */
	public static function is_active_for( string $environment_type, ?string $wp_env, bool $disabled ): bool {
		if ( $disabled ) {
			return false;
		}
		if ( null !== $wp_env && 'production' !== strtolower( $wp_env ) ) {
			return true;
		}
		return 'production' !== $environment_type;
	}

	/**
	 * Read the live environment, the raw WP_ENV constant and the disable
	 * constant.
	 *
	 * @return boolean True when the guard should run on this site.
	 */
	public static function current(): bool {
		$disabled = defined( 'BINES_GUARD_DISABLE' ) && (bool) constant( 'BINES_GUARD_DISABLE' );
		$wp_env   = defined( 'WP_ENV' ) ? (string) constant( 'WP_ENV' ) : null;
		return self::is_active_for( wp_get_environment_type(), $wp_env, $disabled );
	}
}
