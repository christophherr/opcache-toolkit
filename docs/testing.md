# Testing Patterns

This document outlines the testing strategy and patterns used in OPcache Toolkit.

## Overview

OPcache Toolkit uses a multi-layered testing approach to ensure reliability and maintainability:

1.  **Unit Tests (PHP)**: Isolated logic testing using PHPUnit and Brain\Monkey.
2.  **Integration Tests (PHP)**: WordPress-dependent testing for database and REST API interactions.
3.  **Frontend Tests (JS)**: Service and utility testing using Jest.

## PHP Unit Testing

Unit tests are located in `tests/phpunit/unit`. They should extend `OPcacheToolkit\Tests\Unit\BaseTestCase`.

### Key Principles

-   **Isolate Logic**: Mock all external dependencies, including WordPress functions and other plugin services.
-   **No Database**: Unit tests should never touch the database. Use mocks for `$wpdb` or Repositories.
-   **Brain\Monkey**: Use `Monkey\Functions\when()` or `expect()` to mock global WordPress functions.

### Example: Mocking a WordPress Function

```php
use Brain\Monkey;

public function test_something() {
    Monkey\Functions\expect( 'get_option' )
        ->with( 'my_option' )
        ->andReturn( 'mock_value' );

    $result = my_function_that_calls_get_option();
    $this->assertEquals( 'mock_value', $result );
}
```

### Example: Mocking a Service

```php
$opcache = Mockery::mock( OPcacheService::class );
$opcache->shouldReceive( 'is_enabled' )->andReturn( true );

$command = new ResetCommand( $opcache );
```

## PHP Integration Testing

Integration tests are located in `tests/phpunit/integration`. They extend `WP_UnitTestCase`.

### Key Principles

-   **WordPress Environment**: These tests run with a full WordPress environment and a temporary database.
-   **REST API Testing**: Use `WP_REST_Request` and `rest_get_server()->dispatch()` to test endpoints.
-   **Database Verification**: Use `$wpdb` or plugin repositories to verify data persistence.

### Testing REST Endpoints

When testing REST endpoints that depend on services, you may need to mock the service within the plugin container.

```php
public function set_up() {
    parent::set_up();

    // Mock OPcache and re-register endpoints.
    $opcache = $this->createMock( \OPcacheToolkit\Services\OPcacheService::class );
    $opcache->method( 'is_enabled' )->willReturn( true );
    \OPcacheToolkit\Plugin::set_opcache( $opcache );

    add_action( 'rest_api_init', [ \OPcacheToolkit\Plugin::instance(), 'register_rest_endpoints' ], 5 );
    do_action( 'rest_api_init' );
}
```

## JavaScript Testing

Frontend tests are located in `tests/jest`. They use Jest as the test runner.

### Mocking API Calls

Use `jest.fn()` to mock API modules or services.

```javascript
import { getStatus } from '../../src/js/api/status';

jest.mock('../../src/js/api/status');

test('fetches status', async () => {
    getStatus.mockResolvedValue({ success: true, data: { opcache_enabled: true } });
    // ... test logic
});
```

## Code Coverage

Coverage reporting is enabled for both Unit and Integration tests.

-   **Run Unit Coverage**: `composer test:coverage`
-   **Outputs**:
    -   `coverage-unit.xml` (Clover)
    -   `coverage-unit-html/` (HTML Report)
    -   `coverage-integration.xml`
    -   `coverage-integration-html/`

All new code should aim for at least 80% coverage of business logic.
