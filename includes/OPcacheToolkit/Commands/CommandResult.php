<?php
/**
 * Command Result Value Object.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Commands;

/**
 * Class CommandResult.
 *
 * Standardizes the output of internal commands.
 */
final class CommandResult {

	/**
	 * Whether the command was successful.
	 *
	 * @var bool
	 */
	public bool $success;

	/**
	 * Response message.
	 *
	 * @var string
	 */
	public string $message;

	/**
	 * Optional data payload.
	 *
	 * @var array
	 */
	public array $data;

	/**
	 * CommandResult constructor.
	 *
	 * @param bool   $success Whether the command was successful.
	 * @param string $message Response message.
	 * @param array  $data    Optional data payload.
	 */
	public function __construct( bool $success, string $message, array $data = [] ) {
		$this->success = $success;
		$this->message = $message;
		$this->data    = $data;
	}

	/**
	 * Create a success result.
	 *
	 * @param string $message Success message.
	 * @param array  $data    Optional data payload.
	 * @return self
	 */
	public static function success( string $message = '', array $data = [] ): self {
		return new self( true, $message, $data );
	}

	/**
	 * Create a failure result.
	 *
	 * @param string $message Failure message.
	 * @param array  $data    Optional data payload.
	 * @return self
	 */
	public static function failure( string $message = '', array $data = [] ): self {
		return new self( false, $message, $data );
	}
}
