<?php

namespace Genome\Lib\Psr\Log;

class FileLogger implements LoggerInterface {
	private $pointer;

	/**
	 * @param string $path
	 */
	public function __construct( $path ) {
		$this->pointer = fopen( $path, 'ab+' );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function log( $level, $message, array $context = array() ) {
		$time = date( 'Y-m-d H:i:s' );
		fwrite( $this->pointer, "$time $level: $message\n" );
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function emergency( $message, array $context = array() ) {
		$this->log( 'EMER', $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function alert( $message, array $context = array() ) {
		$this->log( 'ALRT', $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function critical( $message, array $context = array() ) {
		$this->log( 'CRIT', $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function error( $message, array $context = array() ) {
		$this->log( 'ERRR', $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function warning( $message, array $context = array() ) {
		$this->log( 'WARN', $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function notice( $message, array $context = array() ) {
		$this->log( 'NOTC', $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function info( $message, array $context = array() ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function debug( $message, array $context = array() ) {
		$this->log( 'debu', $message, $context );
	}
}
