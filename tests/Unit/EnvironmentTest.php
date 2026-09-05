<?php
/**
 * Tests for the guard activation decision.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\Environment;

test( 'inert on production', function () {
	expect( Environment::is_active( 'production', false ) )->toBeFalse();
} );

test( 'active on staging, development and local', function () {
	foreach ( array( 'staging', 'development', 'local' ) as $env ) {
		expect( Environment::is_active( $env, false ) )->toBeTrue();
	}
} );

test( 'disable constant wins on any environment', function () {
	expect( Environment::is_active( 'staging', true ) )->toBeFalse();
} );

test( 'unknown environment type is treated as non-production and guarded', function () {
	expect( Environment::is_active( 'weird', false ) )->toBeTrue();
} );
