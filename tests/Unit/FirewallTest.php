<?php
/**
 * Tests for the outbound HTTP firewall decision and hook.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\Firewall;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

$allowlist = array(
	array( 'host' => 'api.wordpress.org', 'methods' => array( '*' ) ),
	array( 'host' => '*.cliniko.com', 'methods' => array( 'GET' ) ),
);

test( 'exact host on allowlist passes any method', function () use ( $allowlist ) {
	expect( Firewall::allows( 'https://api.wordpress.org/core/version-check/1.7/', 'POST', $allowlist ) )->toBeTrue();
} );

test( 'wildcard host passes only listed methods', function () use ( $allowlist ) {
	expect( Firewall::allows( 'https://api.au1.cliniko.com/v1/businesses', 'GET', $allowlist ) )->toBeTrue();
	expect( Firewall::allows( 'https://api.au1.cliniko.com/v1/patients', 'POST', $allowlist ) )->toBeFalse();
} );

test( 'unknown host is refused', function () use ( $allowlist ) {
	expect( Firewall::allows( 'https://hooks.zapier.com/x', 'POST', $allowlist ) )->toBeFalse();
	expect( Firewall::allows( 'https://hooks.zapier.com/x', 'GET', $allowlist ) )->toBeFalse();
} );

test( 'wildcard does not match the bare apex', function () use ( $allowlist ) {
	expect( Firewall::allows( 'https://cliniko.com/', 'GET', $allowlist ) )->toBeFalse();
} );

test( 'method comparison is case-insensitive', function () use ( $allowlist ) {
	expect( Firewall::allows( 'https://api.au1.cliniko.com/v1/businesses', 'get', $allowlist ) )->toBeTrue();
} );

test( 'intercept returns WP_Error and logs host and method for a blocked call', function () {
	Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
	Functions\when( '__' )->returnArg();
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'home_url' )->justReturn( 'https://example.test' );
	Functions\when( 'site_url' )->justReturn( 'https://example.test' );
	Functions\expect( 'update_option' )->once()->with(
		'bines_guard_http_log',
		Mockery::on( fn( $log ) => 1 === count( $log ) && 'hooks.zapier.com' === $log[0]['host'] && 'POST' === $log[0]['method'] ),
		false
	);
	Filters\expectApplied( 'bines_guard_allowlist' )->once()->andReturnFirstArg();
	if ( ! class_exists( 'WP_Error' ) ) {
		eval( 'class WP_Error { public function __construct( public string $code = "", public string $message = "" ) {} }' );
	}

	$result = Firewall::intercept( false, array( 'method' => 'POST' ), 'https://hooks.zapier.com/x' );

	expect( $result )->toBeInstanceOf( WP_Error::class );
} );

test( 'intercept passes an allowlisted call through untouched', function () {
	Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
	Functions\when( 'home_url' )->justReturn( 'https://example.test' );
	Functions\when( 'site_url' )->justReturn( 'https://example.test' );
	Filters\expectApplied( 'bines_guard_allowlist' )->once()->andReturnFirstArg();

	$result = Firewall::intercept( false, array( 'method' => 'GET' ), 'https://api.wordpress.org/x' );

	expect( $result )->toBeFalse();
} );

test( 'intercept respects an earlier preempt value', function () {
	$result = Firewall::intercept( array( 'body' => 'cached' ), array( 'method' => 'POST' ), 'https://hooks.zapier.com/x' );
	expect( $result )->toBe( array( 'body' => 'cached' ) );
} );

test( "intercept allows the site's own host so cron and loopback work", function () {
	Functions\when( 'home_url' )->justReturn( 'https://staging.example.test/wp' );
	Functions\when( 'site_url' )->justReturn( 'https://staging.example.test/wp' );
	Filters\expectApplied( 'bines_guard_allowlist' )->once()->andReturnFirstArg();
	Functions\expect( 'update_option' )->never();

	$result = Firewall::intercept( false, array( 'method' => 'POST' ), 'https://staging.example.test/wp/wp-cron.php?doing_wp_cron=1' );

	expect( $result )->toBeFalse();
} );

test( 'allows ignores malformed allowlist entries', function () {
	$allowlist = array(
		'junk',
		array( 'host' => '' ),
		array( 'host' => 'api.example.com' ),
	);
	expect( Firewall::allows( 'https://api.example.com/x', 'GET', $allowlist ) )->toBeFalse();

	$allowlist = array(
		array( 'host' => 'api.example.com', 'methods' => 'GET' ),
	);
	expect( Firewall::allows( 'https://api.example.com/x', 'GET', $allowlist ) )->toBeTrue();
} );

test( 'a filter extension is honoured by intercept', function () {
	Functions\when( 'home_url' )->justReturn( 'https://example.test' );
	Functions\when( 'site_url' )->justReturn( 'https://example.test' );
	Filters\expectApplied( 'bines_guard_allowlist' )->once()->andReturnUsing(
		fn( $list ) => array_merge( $list, array( array( 'host' => 'api.example.com', 'methods' => array( 'POST' ) ) ) )
	);

	$result = Firewall::intercept( false, array( 'method' => 'POST' ), 'https://api.example.com/hook' );

	expect( $result )->toBeFalse();
} );
