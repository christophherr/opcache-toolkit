<?php
/**
 * Reset Command.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Commands;

use OPcacheToolkit\Services\OPcacheService;
use OPcacheToolkit\Services\Profiler;

/**
 * Class ResetCommand.
 *
 * Handles the logic for resetting the OPcache.
 */
class ResetCommand {

	/**
	 * OPcache service instance.
	 *
	 * @var OPcacheService
	 */
	private OPcacheService $opcache;

	/**
	 * ResetCommand constructor.
	 *
	 * @param OPcacheService $opcache OPcache service instance.
	 */
	public function __construct( OPcacheService $opcache ) {
		$this->opcache = $opcache;
	}

	/**
	 * Execute the reset command.
	 *
	 * @return CommandResult
	 */
	public function execute(): CommandResult {
		if ( ! $this->opcache->is_enabled() ) {
			return CommandResult::failure(
				__( 'OPcache is not enabled or available on this server.', 'opcache-toolkit' )
			);
		}

		return Profiler::measure(
			'OPcache Reset',
			function () {
				$success = $this->opcache->reset();

				if ( $success ) {
					return CommandResult::success(
						__( 'OPcache has been successfully reset.', 'opcache-toolkit' )
					);
				}

				return CommandResult::failure(
					__( 'Failed to reset OPcache. This may be due to restrictive server settings.', 'opcache-toolkit' )
				);
			},
			[ 'user_id' => get_current_user_id() ]
		);
	}
}
