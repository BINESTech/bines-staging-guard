<?php
/**
 * Pest bootstrap: Brain\Monkey gives every test stubbed WordPress functions.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

use Brain\Monkey;

pest()->beforeEach( fn() => Monkey\setUp() )->afterEach( fn() => Monkey\tearDown() )->in( 'Unit' );
