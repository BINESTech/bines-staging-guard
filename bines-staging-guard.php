<?php
/**
 * Plugin Name: BINES Staging Guard
 * Description: On non-production sites: staging badge, mail catch, outbound HTTP firewall, noindex. Inert on production.
 * Version: 0.1.0
 * Requires PHP: 8.3
 * Author: BINESTech
 *
 * @package BinesGuard
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Environment.php';
require_once __DIR__ . '/src/Firewall.php';
require_once __DIR__ . '/src/MailCatcher.php';
require_once __DIR__ . '/src/Badge.php';
require_once __DIR__ . '/src/NoIndex.php';
require_once __DIR__ . '/src/LogPage.php';
require_once __DIR__ . '/src/Guard.php';

BinesGuard\Guard::boot();
