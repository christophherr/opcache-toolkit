<?php
/**
 * Integration tests for Redirection Logic
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Integration;

use WP_UnitTestCase;

/**
 * Class RedirectionTest
 */
class RedirectionTest extends WP_UnitTestCase {

	/**
	 * Setup the test case.
	 */
	public function set_up(): void {
		parent::set_up();

		// Ensure we are on an admin screen
		set_current_screen( 'dashboard' );

		// Ensure user has necessary capabilities
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		// Prevent exit from terminating the test runner
		add_filter( 'opcache_toolkit_skip_exit', '__return_true' );
	}

	/**
	 * Test that redirection happens when show_wizard is true and not completed.
	 */
	public function test_redirection_to_wizard() {
		// Mock the options
		update_option( 'opcache_toolkit_show_wizard', true );
		update_option( 'opcache_toolkit_setup_completed', false );

		$redirected = false;
		add_filter( 'wp_redirect', function( $location ) use ( &$redirected ) {
			$this->assertStringContainsString( 'page=opcache-toolkit-wizard', $location );
			$redirected = true;
			return false; // Cancel redirect
		} );

		// Trigger the action
		$output = '';
		if ( ob_get_level() > 0 ) {
			// If we're already buffering, we need to be careful
		}

		ob_start();
		opcache_toolkit_maybe_redirect_to_wizard();
		$output = ob_get_clean();

		if ( ! $redirected ) {
			$this->assertStringContainsString( 'http-equiv="refresh"', $output );
			$this->assertStringContainsString( 'page=opcache-toolkit-wizard', $output );
		}

		// Verify show_wizard was cleared
		$this->assertFalse( (bool) get_option( 'opcache_toolkit_show_wizard' ) );
	}

	/**
	 * Test that redirection is skipped if setup is already completed.
	 */
	public function test_no_redirection_if_completed() {
		update_option( 'opcache_toolkit_show_wizard', true );
		update_option( 'opcache_toolkit_setup_completed', true );

		add_filter( 'wp_redirect', function( $location ) {
			$this->fail( 'Redirect should not have happened: ' . $location );
		} );

		ob_start();
		opcache_toolkit_maybe_redirect_to_wizard();
		$output = ob_get_clean();

		$this->assertEmpty( $output );

		// Verify show_wizard was cleared anyway
		$this->assertFalse( (bool) get_option( 'opcache_toolkit_show_wizard' ) );
	}

	/**
	 * Test that redirection is skipped if activate-multi is set.
	 */
	public function test_no_redirection_if_activate_multi() {
		update_option( 'opcache_toolkit_show_wizard', true );
		update_option( 'opcache_toolkit_setup_completed', false );
		$_GET['activate-multi'] = '1';

		add_filter( 'wp_redirect', function( $location ) {
			$this->fail( 'Redirect should not have happened: ' . $location );
		} );

		ob_start();
		opcache_toolkit_maybe_redirect_to_wizard();
		$output = ob_get_clean();

		$this->assertEmpty( $output );

		// Verify show_wizard was cleared anyway
		$this->assertFalse( (bool) get_option( 'opcache_toolkit_show_wizard' ) );

		unset( $_GET['activate-multi'] );
	}

	/**
	 * Test wizard completion redirection.
	 */
	public function test_wizard_completion_redirect() {
		$_GET['page'] = 'opcache-toolkit-wizard';
		$_POST['opcache_toolkit_setup_complete'] = '1';
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'opcache_toolkit_setup' );

		$redirected = false;
		add_filter( 'wp_redirect', function( $location ) use ( &$redirected ) {
			$this->assertStringContainsString( 'page=opcache-toolkit', $location );
			$this->assertStringNotContainsString( 'page=opcache-toolkit-wizard', $location );
			$redirected = true;
			return false;
		} );

		// Load the wizard file to ensure the function exists
		if ( ! function_exists( 'opcache_toolkit_render_wizard_page' ) ) {
			require_once dirname( __DIR__, 3 ) . '/includes/admin/admin-wizard.php';
		}

		ob_start();
		opcache_toolkit_render_wizard_page();
		$output = ob_get_clean();

		if ( ! $redirected ) {
			$this->assertStringContainsString( 'http-equiv="refresh"', $output );
			$this->assertStringContainsString( 'page=opcache-toolkit', $output );
		}

		$this->assertTrue( (bool) get_option( 'opcache_toolkit_setup_completed' ) );
		$this->assertFalse( (bool) get_option( 'opcache_toolkit_show_wizard' ) );

		unset( $_GET['page'] );
		unset( $_POST['opcache_toolkit_setup_complete'] );
		unset( $_REQUEST['_wpnonce'] );
	}
}
