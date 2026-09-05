<?php
/**
 * Tests for the log page rendering.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\LogPage;
use Brain\Monkey\Functions;

beforeEach( function () {
	Functions\when( 'esc_html' )->alias( fn( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
	Functions\when( 'wp_date' )->alias( fn( $f, $t ) => gmdate( 'Y-m-d H:i', $t ) );
	Functions\when( '__' )->returnArg();
} );

test( 'mail rows show newest first and escape the body', function () {
	$html = LogPage::render_mail_rows( array(
		array( 'to' => 'a@x', 'subject' => 'first', 'message' => '<b>old</b>', 'time' => 100 ),
		array( 'to' => 'b@x', 'subject' => 'second', 'message' => 'new', 'time' => 200 ),
	) );
	expect( strpos( $html, 'second' ) )->toBeLessThan( strpos( $html, 'first' ) )
		->and( $html )->toContain( '&lt;b&gt;old&lt;/b&gt;' )
		->and( $html )->not->toContain( '<b>old</b>' );
} );

test( 'http rows show host and method only', function () {
	$html = LogPage::render_http_rows( array( array( 'host' => 'hooks.zapier.com', 'method' => 'POST', 'time' => 100 ) ) );
	expect( $html )->toContain( 'hooks.zapier.com' )->toContain( 'POST' );
} );

test( 'empty logs render a friendly empty row', function () {
	expect( LogPage::render_mail_rows( array() ) )->toContain( 'Nothing caught yet' );
} );
