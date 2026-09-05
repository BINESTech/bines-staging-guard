<?php
/**
 * Tests for forced noindex.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use BinesGuard\NoIndex;
use Brain\Monkey\Functions;

test( 'blog_public is forced to 0 regardless of stored value', function () {
	expect( NoIndex::force_private( '1' ) )->toBe( '0' )
		->and( NoIndex::force_private( '0' ) )->toBe( '0' );
} );

test( 'send_header emits X-Robots-Tag noindex', function () {
	Functions\when( 'headers_sent' )->justReturn( false );
	$sent = array();
	// header() cannot be stubbed; NoIndex uses a static sender we can swap.
	NoIndex::$sender = function ( string $h ) use ( &$sent ) { $sent[] = $h; };
	NoIndex::send_header();
	expect( $sent )->toBe( array( 'X-Robots-Tag: noindex, nofollow' ) );
	NoIndex::$sender = null;
} );
