<?php
/**
 * Unit tests for WarmupCommand
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Commands;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Commands\WarmupCommand;
use Brain\Monkey;

/**
 * Class WarmupCommandTest
 */
class WarmupCommandTest extends BaseTestCase {

	/**
	 * @var WarmupCommand
	 */
	private $command;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->command = new WarmupCommand();
	}

	/**
	 * Test execute warms up URLs correctly.
	 */
	public function test_execute_warms_up_urls(): void {
		$urls = [ 'https://example.com', 'https://example.org' ];

		Monkey\Functions\expect( 'wp_remote_get' )
			->twice()
			->andReturn( [ 'response' => [ 'code' => 200 ] ] );

		Monkey\Functions\expect( 'wp_remote_retrieve_response_code' )
			->twice()
			->andReturn( 200 );

		$result = $this->command->execute( $urls );

		$this->assertTrue( $result->success );
		$this->assertEquals( 2, $result->data['success_count'] );
	}

	/**
	 * Test execute handles failures.
	 */
	public function test_execute_handles_failures(): void {
		$urls = [ 'https://example.com' ];

		Monkey\Functions\expect( 'wp_remote_get' )
			->once()
			->andReturn( [ 'response' => [ 'code' => 404 ] ] );

		Monkey\Functions\expect( 'wp_remote_retrieve_response_code' )
			->twice()
			->andReturn( 404 );

		$result = $this->command->execute( $urls );

		$this->assertTrue( $result->success );
		$this->assertEquals( 0, $result->data['success_count'] );
		$this->assertArrayHasKey( 'https://example.com', $result->data['errors'] );
	}
}
