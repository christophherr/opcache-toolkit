# OPcache Toolkit: PHPUnit Testing Guide

## 🎯 Testing Principles

### 1. Use Brain\Monkey for WordPress Mocking
- ✅ All WordPress function mocks use `Brain\Monkey\Functions\when()` or `expect()`.
- ❌ NEVER use custom `function_exists()` checks in production code solely to make tests pass.
- ✅ Use `Mockery` for class and object mocks.

### 2. Minimal BaseTestCase
- ✅ `OPcacheToolkit\Tests\Unit\BaseTestCase` only stubs essential WordPress functions used by nearly all tests (e.g., `__`, `esc_html`, `get_option`).
- ✅ Individual tests should explicitly declare any specific WordPress functions they need to mock.

### 3. Namespace Interception for PHP Functions
- ✅ We use `php-mock-phpunit` to mock global PHP functions (like `opcache_reset`, `time`, `ini_get`).
- ✅ Call these functions without a leading backslash (e.g., `opcache_reset()`, not `\opcache_reset()`) in namespaced code to allow interception.

### 4. Test Isolation
- ✅ Each test must be independent.
- ✅ No shared state between tests.
- ✅ `setUp()` and `tearDown()` must properly clean up mocks via `Monkey\tearDown()` and `Mockery::close()`.

---

## 📁 Folder Structure

```
tests/phpunit/
├── unit/
│   ├── bootstrap-unit.php
│   ├── BaseTestCase.php          # Minimal base, uses Brain\Monkey
│   ├── Services/                 # Tests for OPcacheToolkit\Services
│   ├── Database/                 # Tests for OPcacheToolkit\Database
│   ├── Commands/                 # Tests for OPcacheToolkit\Commands
│   └── CLI/                      # Tests for OPcacheToolkit\CLI
└── integration/
    ├── bootstrap-integration.php
    └── ...                       # Integration tests
```

---

## 📝 Test Patterns

### 1. Service Wrapper Test (with php-mock)

When testing low-level services that wrap global PHP functions:

```php
namespace OPcacheToolkit\Tests\Unit\Services;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Services\OPcacheService;
use phpmock\phpunit\PHPMock;

class OPcacheServiceTest extends BaseTestCase {
    use PHPMock;

    public function test_reset_calls_native_function() {
        $service = new OPcacheService();
        
        // Mock the global opcache_reset function in the service's namespace
        $reset = $this->getFunctionMock('OPcacheToolkit\Services', 'opcache_reset');
        $reset->expects($this->once())->willReturn(true);

        $this->assertTrue($service->reset());
    }
}
```

### 2. Logic Test with WordPress Mocking (Brain\Monkey)

```php
namespace OPcacheToolkit\Tests\Unit\Commands;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Commands\ResetCommand;
use Brain\Monkey\Functions;

class ResetCommandTest extends BaseTestCase {
    public function test_execute_failure_message() {
        $opcache = \Mockery::mock('OPcacheToolkit\Services\OPcacheService');
        $opcache->shouldReceive('is_enabled')->andReturn(true);
        $opcache->shouldReceive('reset')->andReturn(false);

        // Mock WordPress translation function
        Functions\expect('__')
            ->with('Failed to reset OPcache.', 'opcache-toolkit')
            ->andReturn('Mocked Failure');

        $command = new ResetCommand($opcache);
        $result = $command->execute();

        $this->assertFalse($result->success);
        $this->assertEquals('Mocked Failure', $result->message);
    }
}
```

### 3. Database Repository Test (Mocking $wpdb)

`BaseTestCase` automatically sets up a `$wpdb` mock.

```php
namespace OPcacheToolkit\Tests\Unit\Database;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Database\StatsRepository;

class StatsRepositoryTest extends BaseTestCase {
    public function test_truncate_calls_wpdb() {
        $repository = new StatsRepository($this->wpdb);
        
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->with('TRUNCATE TABLE %i', 'wp_opcache_toolkit_stats')
            ->andReturn('TRUNCATE TABLE `wp_opcache_toolkit_stats`');

        $this->wpdb->shouldReceive('query')
            ->once()
            ->with('TRUNCATE TABLE `wp_opcache_toolkit_stats`')
            ->andReturn(true);

        $this->assertTrue($repository->truncate());
    }
}
```

---

## ✅ PHPUnit Checklist

- [ ] Does the test extend `BaseTestCase`?
- [ ] Are all external dependencies (Services, Repositories) mocked using `Mockery`?
- [ ] Are WordPress functions mocked using `Brain\Monkey`?
- [ ] If mocking a native PHP function, is the `PHPMock` trait used and is the backslash omitted in the source code?
- [ ] Does the test run successfully with `composer test:unit`?
- [ ] Is the code style consistent with WordPress Coding Standards?
