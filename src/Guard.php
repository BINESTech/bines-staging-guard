<?php
/**
 * Wires every guard module when the site is not production.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

/**
 * Single entry point called from the plugin bootstrap.
 */
final class Guard {

	/**
	 * Register all hooks, or none on production.
	 *
	 * @return void
	 */
	public static function boot(): void {
		if ( ! Environment::current() ) {
			return;
		}
		Firewall::register();
		MailCatcher::register();
		Badge::register();
		NoIndex::register();
		LogPage::register();
	}
}
