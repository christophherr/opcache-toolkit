<?php
/**
 * Unit tests for StatsRepository
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Database;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Database\StatsRepository;
use Brain\Monkey;
use Mockery;
use phpmock\phpunit\PHPMock;

/**
 * Class StatsRepositoryTest
 */
class StatsRepositoryTest extends BaseTestCase {
	use PHPMock;

	/**
	 * @var StatsRepository
	 */
	private $repository;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->repository = new StatsRepository( $this->wpdb );
	}

	/**
	 * Test get_chart_data returns cached data if available.
	 */
	public function test_get_chart_data_returns_cached_data(): void {
		$cached_data = [ 'labels' => [ '2026-01-12' ], 'hitRate' => [ 95.5 ] ];

		Monkey\Functions\expect( 'get_transient' )
			->once()
			->with( 'opcache_toolkit_chart_data_180' )
			->andReturn( $cached_data );

		$this->assertEquals( $cached_data, $this->repository->get_chart_data() );
	}

	/**
	 * Test get_chart_data queries DB and sets transient if not cached.
	 */
	public function test_get_chart_data_queries_db_if_not_cached(): void {
		Monkey\Functions\expect( 'get_transient' )
			->once()
			->andReturn( false );

		$rows = [
			(object) [
				'recorded_at'    => '2026-01-12 12:00:00',
				'hit_rate'       => 98.2,
				'cached_scripts' => 500,
				'wasted_memory'  => 1024,
			],
		];

		$this->wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::on( function( $sql ) {
				return str_contains( $sql, 'ORDER BY recorded_at DESC' );
			} ), Mockery::any(), 180 )
			->andReturn( 'MOCKED QUERY' );

		$this->wpdb->shouldReceive( 'get_results' )
			->once()
			->with( 'MOCKED QUERY' )
			->andReturn( $rows );

		Monkey\Functions\expect( 'set_transient' )
			->once()
			->with( 'opcache_toolkit_chart_data_180', Mockery::any(), 300 )
			->andReturn( true );

		$data = $this->repository->get_chart_data();

		$this->assertCount( 1, $data['labels'] );
		$this->assertEquals( '2026-01-12 12:00:00', $data['labels'][0] );
		$this->assertEquals( 98.2, $data['hitRate'][0] );
	}

	/**
	 * Test insert calls wpdb->insert and invalidates cache.
	 */
	public function test_insert_calls_wpdb_and_invalidates_cache(): void {
		$data = [
			'recorded_at'    => '2026-01-12 12:00:00',
			'hit_rate'       => 98.2,
			'cached_scripts' => 500,
			'wasted_memory'  => 1024,
		];

		$this->wpdb->shouldReceive( 'insert' )
			->once()
			->andReturn( 1 );

		Monkey\Functions\expect( 'delete_transient' )->twice();

		$this->assertTrue( $this->repository->insert( $data ) );
	}

	/**
	 * Test get_memory_prediction returns stable when no growth.
	 */
	public function test_get_memory_prediction_stable(): void {
		$chart_data = [
			'labels' => [ 'T1', 'T2' ],
			'wasted' => [ 1000, 1000 ],
		];

		// Mock get_chart_data internally or just mock the transient it calls
		Monkey\Functions\expect( 'get_transient' )
			->andReturn( $chart_data );

		$ini_get = $this->getFunctionMock( 'OPcacheToolkit\Database', 'ini_get' );
		$ini_get->expects( $this->any() )
			->with( 'opcache.memory_consumption' )
			->willReturn( '128' );

		// We need to mock Plugin::opcache()->get_status()
		// Since Plugin uses static accessors, we might need to mock the OPcacheService
		$opcache_mock = Mockery::mock( 'OPcacheToolkit\Services\OPcacheService' );
		$opcache_mock->shouldReceive( 'get_status' )->andReturn(
			[
				'memory_usage' => [
					'used_memory' => 64 * 1024 * 1024,
				],
			]
		);

		// We need to handle the Plugin singleton.
		// BaseTestCase might not handle it. Let's check how Plugin works.
		// It uses private static property.

		$reflection = new \ReflectionClass( 'OPcacheToolkit\Plugin' );
		$property   = $reflection->getProperty( 'opcache' );
		$property->setAccessible( true );
		$property->setValue( null, $opcache_mock );

		$prediction = $this->repository->get_memory_prediction();

		$this->assertEquals( 'stable', $prediction['status'] );
		$this->assertEquals( 0, $prediction['growth'] );
	}

	/**
	 * Test get_memory_prediction returns critical when memory is low and growth is high.
	 */
	public function test_get_memory_prediction_critical(): void {
		$chart_data = [
			'labels' => array_fill( 0, 30, 'T' ),
			'wasted' => range( 1000, 30000, 1000 ), // Growth of 1000 per point
		];

		Monkey\Functions\expect( 'get_transient' )->andReturn( $chart_data );

		$ini_get = $this->getFunctionMock( 'OPcacheToolkit\Database', 'ini_get' );
		$ini_get->expects( $this->any() )->willReturn( '128' );

		$opcache_mock = Mockery::mock( 'OPcacheToolkit\Services\OPcacheService' );
		$opcache_mock->shouldReceive( 'get_status' )->andReturn(
			[
				'memory_usage' => [
					'used_memory' => 134217000, // Almost 128MB (134217728)
				],
			]
		);

		$reflection = new \ReflectionClass( 'OPcacheToolkit\Plugin' );
		$property   = $reflection->getProperty( 'opcache' );
		$property->setAccessible( true );
		$property->setValue( null, $opcache_mock );

		$prediction = $this->repository->get_memory_prediction();

		$this->assertEquals( 'critical', $prediction['status'] );
		$this->assertLessThan( 7, $prediction['days_remaining'] );
	}

	/**
	 * Test get_chart_data retries on database failure.
	 */
	public function test_get_chart_data_retries_on_failure(): void {
		Monkey\Functions\expect( 'get_transient' )->andReturn( false );

		// Mock usleep to avoid waiting.
		$usleep = $this->getFunctionMock( 'OPcacheToolkit\Database', 'usleep' );
		$usleep->expects( $this->exactly( 2 ) );

		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'QUERY' );

		// First two calls fail, third succeeds.
		$this->wpdb->shouldReceive( 'get_results' )
			->times( 3 )
			->andReturnUsing( function() {
				static $count = 0;
				$count++;
				if ( $count < 3 ) {
					$this->wpdb->last_error = 'Transient error';
					return null;
				}
				return [ (object) [ 'recorded_at' => '2026-01-12', 'hit_rate' => 99, 'cached_scripts' => 10, 'wasted_memory' => 0 ] ];
			} );

		Monkey\Functions\expect( 'set_transient' )->once();

		$data = $this->repository->get_chart_data();
		$this->assertEquals( 99, $data['hitRate'][0] );
	}
}
