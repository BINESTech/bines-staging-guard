<?php
/**
 * Tests for the staging badge.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\Badge;
use Brain\Monkey\Functions;

test( 'label names the environment and what is blocked', function () {
	Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
	Functions\when( '__' )->returnArg();
	expect( Badge::label() )->toBe( 'STAGING — mail and outbound calls blocked' );
} );

test( 'admin bar node is added with the guard id', function () {
	Functions\when( 'wp_get_environment_type' )->justReturn( 'development' );
	Functions\when( '__' )->returnArg();
	Functions\when( 'esc_html' )->returnArg();
	$bar = Mockery::mock( 'WP_Admin_Bar' );
	$captured = null;
	$bar->shouldReceive( 'add_node' )->once()->with( Mockery::on( fn( $n ) => 'bines-guard' === $n['id'] && str_contains( $n['title'], 'DEVELOPMENT' ) ) )->andReturnUsing( function ( $node ) use ( &$captured ) { $captured = $node; } );

	Badge::admin_bar( $bar );

	expect( $captured['id'] )->toBe( 'bines-guard' )->and( $captured['title'] )->toContain( 'DEVELOPMENT' );
} );

test( 'front strip prints nothing for logged-out visitors', function () {
	Functions\when( 'is_user_logged_in' )->justReturn( false );
	ob_start();
	Badge::front_strip();
	expect( ob_get_clean() )->toBe( '' );
} );

test( 'front strip prints the label for logged-in users', function () {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
	Functions\when( '__' )->returnArg();
	Functions\when( 'esc_html' )->returnArg();
	ob_start();
	Badge::front_strip();
	expect( ob_get_clean() )->toContain( 'STAGING' )->toContain( 'bines-guard-strip' );
} );

test( 'styles prints nothing for a logged-out front-end visitor', function () {
	Functions\when( 'is_admin' )->justReturn( false );
	Functions\when( 'is_user_logged_in' )->justReturn( false );
	ob_start();
	Badge::styles();
	expect( ob_get_clean() )->toBe( '' );
} );

test( 'styles prints the style tag in admin', function () {
	Functions\when( 'is_admin' )->justReturn( true );
	ob_start();
	Badge::styles();
	expect( ob_get_clean() )->toContain( 'bines-guard-css' );
} );
