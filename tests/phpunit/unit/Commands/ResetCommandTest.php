<?php
/**
 * Unit tests for ResetCommand
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Commands;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Commands\ResetCommand;
use OPcacheToolkit\Services\OPcacheService;
use Mockery;
use Brain\Monkey;

/**
 * Class ResetCommandTest
 */
class ResetCommandTest extends BaseTestCase {

	/**
	 * @var ResetCommand
	 */
	private $command;

	/**
	 * @var OPcacheService|Mockery\MockInterface
	 */
	private $opcache;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->opcache = Mockery::mock( OPcacheService::class );
		$this->command = new ResetCommand( $this->opcache );
	}

	/**
	 * Test execute returns failure if OPcache is disabled.
	 */
	public function test_execute_returns_failure_if_disabled(): void {
		$this->opcache->shouldReceive( 'is_enabled' )->once()->andReturn( false );

		$result = $this->command->execute();

		$this->assertFalse( $result->success );
		$this->assertStringContainsString( 'not enabled', $result->message );
	}

	/**
	 * Test execute returns success if reset succeeds.
	 */
	public function test_execute_returns_success_on_success(): void {
		$this->opcache->shouldReceive( 'is_enabled' )->once()->andReturn( true );
		$this->opcache->shouldReceive( 'reset' )->once()->andReturn( true );

		$result = $this->command->execute();

		$this->assertTrue( $result->success );
		$this->assertStringContainsString( 'successfully reset', $result->message );
	}

	/**
	 * Test execute returns failure if reset fails.
	 */
	public function test_execute_returns_failure_on_failure(): void {
		$this->opcache->shouldReceive( 'is_enabled' )->once()->andReturn( true );
		$this->opcache->shouldReceive( 'reset' )->once()->andReturn( false );

		$result = $this->command->execute();

		$this->assertFalse( $result->success );
		$this->assertStringContainsString( 'Failed to reset', $result->message );
	}
}
