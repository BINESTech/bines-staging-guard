<?php
/**
 * Tests for the staging mail catcher.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\MailCatcher;
use Brain\Monkey\Functions;

test( 'entry flattens array recipients and keeps subject and message', function () {
	$entry = MailCatcher::entry_from_atts( array(
		'to'      => array( 'a@example.com', 'b@example.com' ),
		'subject' => 'Hello',
		'message' => 'Body <a href="https://x/reset">reset</a>',
	) );
	expect( $entry['to'] )->toBe( 'a@example.com, b@example.com' )
		->and( $entry['subject'] )->toBe( 'Hello' )
		->and( $entry['message'] )->toContain( 'reset' )
		->and( $entry['time'] )->toBeInt();
} );

test( 'catch_mail logs and returns true so wp_mail reports success without sending', function () {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'update_option' )->once()->with(
		'bines_guard_mail_log',
		Mockery::on( fn( $log ) => 1 === count( $log ) && 'Hello' === $log[0]['subject'] ),
		false
	);

	$result = MailCatcher::catch_mail( null, array( 'to' => 'a@example.com', 'subject' => 'Hello', 'message' => 'Hi' ) );

	expect( $result )->toBeTrue();
} );

test( 'catch_mail respects an earlier short-circuit', function () {
	expect( MailCatcher::catch_mail( false, array() ) )->toBeFalse();
} );

test( 'log is capped at 200 entries, oldest dropped', function () {
	$existing = array_fill( 0, 200, array( 'to' => 'x', 'subject' => 'old', 'message' => '', 'time' => 1 ) );
	Functions\when( 'get_option' )->justReturn( $existing );

	$captured = null;
	Functions\expect( 'update_option' )->once()->andReturnUsing(
		function ( string $option, array $log, bool $autoload ) use ( &$captured ) {
			$captured = $log;
			return true;
		}
	);

	MailCatcher::catch_mail( null, array( 'to' => 'a@example.com', 'subject' => 'new', 'message' => '' ) );

	expect( $captured )->toHaveCount( 200 )
		->and( end( $captured )['subject'] )->toBe( 'new' );
} );
