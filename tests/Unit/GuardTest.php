<?php
/**
 * Tests for the guard bootstrap.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\Guard;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

test( 'boot wires nothing on production', function () {
	Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
	Guard::boot();
	expect( has_filter( 'pre_http_request' ) )->toBeFalse()
		->and( has_filter( 'pre_wp_mail' ) )->toBeFalse();
} );

test( 'boot wires every module on staging', function () {
	Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
	Guard::boot();
	expect( has_filter( 'pre_http_request', 'BinesGuard\Firewall::intercept', 5 ) )->toBeTrue()
		->and( has_filter( 'pre_wp_mail', 'BinesGuard\MailCatcher::catch_mail', 5 ) )->toBeTrue()
		->and( has_action( 'admin_bar_menu', 'BinesGuard\Badge::admin_bar', 1 ) )->toBeTrue()
		->and( has_action( 'send_headers', 'BinesGuard\NoIndex::send_header', 10 ) )->toBeTrue()
		->and( has_action( 'admin_menu', 'BinesGuard\LogPage::menu', 10 ) )->toBeTrue();
} );
