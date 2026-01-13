<?php
/**
 * Base Test Case for OPcache Toolkit
 *
 * @package OPcacheToolkit\Tests
 */

namespace OPcacheToolkit\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use Brain\Monkey;
	use Mockery;

	if ( ! defined( 'ARRAY_A' ) ) {
		define( 'ARRAY_A', 'ARRAY_A' );
	}
	if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
		define( 'MINUTE_IN_SECONDS', 60 );
	}
	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}
	if ( ! defined( 'DB_NAME' ) ) {
		define( 'DB_NAME', 'test_db' );
	}

	/**
	 * Base Test Case class
	 */
	abstract class BaseTestCase extends TestCase {

		protected $options = array();
		protected $cache   = array();
		protected $wpdb;
		protected $wpdb_mock;
		protected $db;
		protected $logger;

		protected function setUp(): void {
			parent::setUp();
			Monkey\setUp();

			$this->setupWpdb();
			$this->setupFilesystem();
			$this->setupEssentials();
		}

		protected function tearDown(): void {
			global $wp_filesystem;
			$wp_filesystem = null;

			Monkey\tearDown();
			Mockery::close();
			parent::tearDown();
		}

		/**
		 * Setup filesystem mock
		 */
		private function setupFilesystem(): void {
			global $wp_filesystem;
			$wp_filesystem = Mockery::mock( 'WP_Filesystem_Direct' )->makePartial();
			$wp_filesystem->shouldReceive( 'is_dir' )->andReturn( true )->byDefault();
			$wp_filesystem->shouldReceive( 'mkdir' )->andReturn( true )->byDefault();
			$wp_filesystem->shouldReceive( 'exists' )->andReturn( true )->byDefault();
			$wp_filesystem->shouldReceive( 'put_contents' )->andReturn( true )->byDefault();
			$wp_filesystem->shouldReceive( 'get_contents' )->andReturn( '' )->byDefault();
			$wp_filesystem->shouldReceive( 'size' )->andReturn( 0 )->byDefault();
			$wp_filesystem->shouldReceive( 'move' )->andReturn( true )->byDefault();
			$wp_filesystem->shouldReceive( 'delete' )->andReturn( true )->byDefault();
			$wp_filesystem->shouldReceive( 'dirlist' )->andReturn( array() )->byDefault();
		}

		/**
		 * Setup wpdb mock
		 */
		private function setupWpdb(): void {
			$this->wpdb                = Mockery::mock( 'wpdb' );
			$this->wpdb->prefix        = 'wp_';
			$this->wpdb->users         = 'wp_users';
			$this->wpdb->posts         = 'wp_posts';
			$this->wpdb->last_error    = '';
			$this->wpdb->insert_id     = 0;
			$this->wpdb->rows_affected = 0;

			$this->wpdb_mock = $this->wpdb;

			$this->wpdb->shouldReceive( 'prepare' )->andReturnUsing(
				function ( $query, ...$args ) {
					if ( count( $args ) === 1 && is_array( $args[0] ) ) {
						$args = $args[0];
					}
					return $this->mockPrepare( (string) $query, (array) $args );
				}
			)->byDefault();

			$this->wpdb->shouldReceive( 'get_charset_collate' )->andReturn( 'utf8mb4_unicode_ci' )->byDefault();
			$this->wpdb->shouldReceive( 'query' )->andReturn( true )->byDefault();
			$this->wpdb->shouldReceive( 'get_var' )->andReturn( null )->byDefault();
			$this->wpdb->shouldReceive( 'get_results' )->andReturn( array() )->byDefault();
			$this->wpdb->shouldReceive( 'get_row' )->andReturn( null )->byDefault();
			$this->wpdb->shouldReceive( 'update' )->andReturn( true )->byDefault();
			$this->wpdb->shouldReceive( 'delete' )->andReturn( true )->byDefault();
			$this->wpdb->shouldReceive( 'insert' )->andReturn( true )->byDefault();

			global $wpdb;
			$wpdb = $this->wpdb;
		}

		/**
		 * Setup essential WP functions
		 */
		private function setupEssentials(): void {
			$options = &$this->options;
			$cache   = &$this->cache;

			Monkey\Functions\when( 'is_wp_error' )->alias(
				function ( $v ) {
					return $v instanceof \WP_Error;
				}
			);
			Monkey\Functions\when( '__' )->alias(
				function ( $v ) {
					return $v;
				}
			);
			Monkey\Functions\when( 'esc_html' )->alias(
				function ( $v ) {
					return $v;
				}
			);
			Monkey\Functions\when( 'esc_html__' )->alias(
				function ( $v ) {
					return $v;
				}
			);
			Monkey\Functions\when( 'wp_json_encode' )->alias(
				function ( $v ) {
					return json_encode( $v );
				}
			);
			Monkey\Functions\when( 'get_option' )->alias(
				function ( $n, $d = false ) use ( &$options ) {
					return $options[ $n ] ?? $d;
				}
			);
			Monkey\Functions\when( 'update_option' )->alias(
				function ( $n, $v ) use ( &$options ) {
					$options[ $n ] = $v;
					return true;
				}
			);
			Monkey\Functions\when( 'apply_filters' )->alias(
				function ( $t, $v ) {
					return $v;
				}
			);
			Monkey\Functions\when( 'do_action' )->alias( function () {} );
			Monkey\Functions\when( 'WP_Filesystem' )->alias(
				function () {
					// Global $wp_filesystem is already initialized in setupFilesystem()
					return true;
				}
			);
			Monkey\Functions\when( 'current_time' )->justReturn( date( 'Y-m-d H:i:s' ) );
			Monkey\Functions\when( 'wp_upload_dir' )->alias(
				function () {
					return [
						'basedir' => '/tmp',
						'baseurl' => 'http://example.com/wp-content/uploads',
					];
				}
			);
			Monkey\Functions\when( 'size_format' )->alias(
				function ( $v ) {
					return (string) $v . ' B';
				}
			);
			Monkey\Functions\when( 'wp_cache_get' )->alias(
				function ( $k, $g = '' ) use ( &$cache ) {
					return $cache[ $g ][ $k ] ?? false;
				}
			);
			Monkey\Functions\when( 'wp_cache_set' )->alias(
				function ( $k, $v, $g = '' ) use ( &$cache ) {
					$cache[ $g ][ $k ] = $v;
					return true;
				}
			);
			Monkey\Functions\when( 'get_current_user_id' )->justReturn( 1 );
			Monkey\Functions\when( 'current_user_can' )->justReturn( true );
			Monkey\Functions\when( 'wp_die' )->alias( function ( $m ) {
				throw new \Exception( $m );
			} );
			Monkey\Functions\when( 'admin_url' )->alias( function ( $p = '' ) {
				return 'http://example.com/wp-admin/' . $p;
			} );
			Monkey\Functions\when( 'wp_nonce_field' )->justReturn( '' );
			Monkey\Functions\when( 'wp_create_nonce' )->justReturn( 'mock-nonce' );
			Monkey\Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
			Monkey\Functions\when( 'check_ajax_referer' )->justReturn( true );
			Monkey\Functions\when( 'wp_send_json_success' )->alias( function ( $d = null ) {
				echo json_encode( [ 'success' => true, 'data' => $d ] );
			} );
			Monkey\Functions\when( 'wp_send_json_error' )->alias( function ( $d = null ) {
				echo json_encode( [ 'success' => false, 'data' => $d ] );
			} );
			Monkey\Functions\when( 'register_rest_route' )->alias( function () {} );
			Monkey\Functions\when( 'esc_url_raw' )->alias( function ( $v ) {
				return $v;
			} );
		}

		/**
		 * Mock prepare()
		 */
		private function mockPrepare( string $query, array $args ): string {
			$arg_index = 0;
			return preg_replace_callback(
				'/%([isd])/',
				function ( $matches ) use ( $args, &$arg_index ) {
					if ( ! isset( $args[ $arg_index ] ) ) {
						return $matches[0];
					}
					$arg = $args[ $arg_index++ ];
					switch ( $matches[1] ) {
						case 'i':
							return '`' . (string) $arg . '`';
						case 'd':
							return (string) (int) $arg;
						case 's':
							return "'" . (string) $arg . "'";
					}
					return $matches[0];
				},
				$query
			);
		}

	}
}

namespace {
	if ( ! class_exists( 'WP_Error' ) ) {
		class WP_Error {
			protected $message;
			public function __construct( $c = '', $m = '', $d = null ) {
				$this->message = $m;
			}
			public function get_error_message() {
				return $this->message;
			}
		}
	}

	if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
		abstract class WP_Filesystem_Base {
			abstract public function get_contents( $file );
			abstract public function put_contents( $file, $contents, $mode = false );
			abstract public function exists( $file );
			abstract public function is_dir( $path );
			abstract public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false );
			abstract public function delete( $path, $recursive = false, $type = false );
			abstract public function move( $source, $destination, $overwrite = false );
			abstract public function size( $file );
			abstract public function dirlist( $path = '.', $include_hidden = true, $recursive = false );
		}
	}

	if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
		class WP_Filesystem_Direct extends WP_Filesystem_Base {
			public function get_contents( $file ) {
				return '';
			}
			public function put_contents( $file, $contents, $mode = false ) {
				return true;
			}
			public function exists( $file ) {
				return true;
			}
			public function is_dir( $path ) {
				return true;
			}
			public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {
				return true;
			}
			public function delete( $path, $recursive = false, $type = false ) {
				return true;
			}
			public function move( $source, $destination, $overwrite = false ) {
				return true;
			}
			public function size( $file ) {
				return 0;
			}
			public function dirlist( $path = '.', $include_hidden = true, $recursive = false ) {
				return [];
			}
		}
	}
}
