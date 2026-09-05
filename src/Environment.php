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
	 * Pure decision used by tests and by current().
	 *
	 * @param string  $environment_type Value of wp_get_environment_type().
	 * @param boolean $disabled         True when BINES_GUARD_DISABLE is truthy.
	 * @return boolean True when the guard should run.
	 */
	public static function is_active( string $environment_type, bool $disabled ): bool {
		if ( $disabled ) {
			return false;
		}
		return 'production' !== $environment_type;
	}

	/**
	 * Read the live environment and the disable constant.
	 *
	 * @return boolean True when the guard should run on this site.
	 */
	public static function current(): bool {
		$disabled = defined( 'BINES_GUARD_DISABLE' ) && (bool) constant( 'BINES_GUARD_DISABLE' );
		return self::is_active( wp_get_environment_type(), $disabled );
	}
}
