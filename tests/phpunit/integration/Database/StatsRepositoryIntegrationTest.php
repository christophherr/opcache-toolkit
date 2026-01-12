<?php
/**
 * Integration tests for StatsRepository.
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Integration\Database;

use OPcacheToolkit\Database\StatsRepository;
use WP_UnitTestCase;

/**
 * Class StatsRepositoryIntegrationTest.
 */
class StatsRepositoryIntegrationTest extends WP_UnitTestCase {

	/**
	 * @var StatsRepository
	 */
	private $repository;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();
		global $wpdb;
		$this->repository = new StatsRepository( $wpdb );

		// Ensure table exists (usually handled by bootstrap but being safe).
		if ( function_exists( 'opcache_toolkit_install_schema' ) ) {
			opcache_toolkit_install_schema();
		}
	}

	/**
	 * Test that insert() actually saves data to the database.
	 */
	public function test_insert_saves_to_db() {
		$data = [
			'recorded_at'    => current_time( 'mysql' ),
			'hit_rate'       => 99.5,
			'cached_scripts' => 123,
			'wasted_memory'  => 1024,
		];

		$result = $this->repository->insert( $data );
		$this->assertTrue( $result );

		$all = $this->repository->get_all();
		$this->assertCount( 1, $all );
		$this->assertEquals( 99.5, (float) $all[0]['hit_rate'] );
		$this->assertEquals( 123, (int) $all[0]['cached_scripts'] );
	}

	/**
	 * Test that get_chart_data() returns correct structure and respects limits.
	 */
	public function test_get_chart_data_format() {
		// Insert 3 rows.
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->repository->insert(
				[
					'recorded_at'    => gmdate( 'Y-m-d H:i:s', time() - ( $i * 3600 ) ),
					'hit_rate'       => 90 + $i,
					'cached_scripts' => 100 * $i,
					'wasted_memory'  => 0,
				]
			);
		}

		$data = $this->repository->get_chart_data( 2 );

		$this->assertArrayHasKey( 'labels', $data );
		$this->assertArrayHasKey( 'hitRate', $data );
		$this->assertCount( 2, $data['labels'] );
	}

	/**
	 * Test that delete_older_than() removes old data.
	 */
	public function test_delete_older_than() {
		// Insert one very old row.
		$this->repository->insert(
			[
				'recorded_at'    => gmdate( 'Y-m-d H:i:s', time() - ( 100 * DAY_IN_SECONDS ) ),
				'hit_rate'       => 50,
				'cached_scripts' => 10,
				'wasted_memory'  => 0,
			]
		);

		// Insert one recent row.
		$this->repository->insert(
			[
				'recorded_at'    => current_time( 'mysql' ),
				'hit_rate'       => 99,
				'cached_scripts' => 100,
				'wasted_memory'  => 0,
			]
		);

		$deleted = $this->repository->delete_older_than( 30 );
		$this->assertEquals( 1, $deleted );

		$all = $this->repository->get_all();
		$this->assertCount( 1, $all );
		$this->assertEquals( 99, (float) $all[0]['hit_rate'] );
	}
}
