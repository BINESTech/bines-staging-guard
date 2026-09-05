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

test( 'is_active_for treats an unrecognised WP_ENV as non-production even if wp_get_environment_type() says production', function () {
	expect( Environment::is_active_for( 'production', 'stage', false ) )->toBeTrue();
} );

test( 'is_active_for is inert when WP_ENV literally matches production', function () {
	expect( Environment::is_active_for( 'production', 'production', false ) )->toBeFalse();
} );

test( 'is_active_for falls back to the environment type when WP_ENV is undefined', function () {
	expect( Environment::is_active_for( 'production', null, false ) )->toBeFalse();
} );

test( 'is_active_for still honours the disable constant even with a non-production WP_ENV', function () {
	expect( Environment::is_active_for( 'staging', 'production', true ) )->toBeFalse();
} );
