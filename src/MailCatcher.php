<?php
/**
 * Catches every wp_mail() on non-production sites into a rolling log.
 *
 * Password resets still work: the reset link is in the logged message body
 * and readable on the Tools → Staging Guard page.
 *
 * @package BinesGuard
 */

declare(strict_types=1);

namespace BinesGuard;

/**
 * pre_wp_mail short-circuit plus a capped option-backed log.
 */
final class MailCatcher {

	public const LOG_OPTION  = 'bines_guard_mail_log';
	public const LOG_CAP     = 200;
	public const MESSAGE_CAP = 20000;

	/**
	 * Normalise wp_mail() attributes into a log entry. The message is
	 * capped at MESSAGE_CAP characters so a runaway HTML email cannot
	 * bloat the option table.
	 *
	 * @param array $atts WP_mail attributes: to, subject, message, headers, attachments.
	 * @return array{to:string, subject:string, message:string, time:int}
	 */
	public static function entry_from_atts( array $atts ): array {
		$to = $atts['to'] ?? '';
		if ( is_array( $to ) ) {
			$to = implode( ', ', array_map( 'strval', $to ) );
		}
		$message = (string) ( $atts['message'] ?? '' );
		if ( mb_strlen( $message ) > self::MESSAGE_CAP ) {
			$message = mb_substr( $message, 0, self::MESSAGE_CAP ) . "\n… [truncated by BINES Staging Guard]";
		}
		return array(
			'to'      => (string) $to,
			'subject' => (string) ( $atts['subject'] ?? '' ),
			'message' => $message,
			'time'    => time(),
		);
	}

	/**
	 * pre_wp_mail callback: log the message and report success without sending.
	 *
	 * @param null|boolean $preempt Non-null means an earlier filter already handled it.
	 * @param array        $atts    WP_mail attributes.
	 * @return null|boolean True to short-circuit wp_mail as "sent".
	 */
	public static function catch_mail( $preempt, array $atts ) {
		if ( null !== $preempt ) {
			return $preempt;
		}
		$log   = (array) get_option( self::LOG_OPTION, array() );
		$log[] = self::entry_from_atts( $atts );
		$log   = array_slice( $log, -self::LOG_CAP );
		update_option( self::LOG_OPTION, $log, false );
		return true;
	}

	/**
	 * Hook into WordPress. Called by Guard::boot() only when active.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'pre_wp_mail', array( self::class, 'catch_mail' ), 5, 2 );
	}
}
