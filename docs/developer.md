# Developer Guide

This document provides technical details for developers working on the OPcache Toolkit plugin, covering utility services, development environment setup, and coding patterns.

## Development Environment Setup

To set up the development environment, follow these steps:

1. **PHP Dependencies**: Install composer dependencies including development tools for testing and linting.
   ```bash
   composer install
   ```

2. **JavaScript Dependencies**: Install NPM packages for the build process and tests.
   ```bash
   npm install
   ```

3. **Build Assets**: Compile JavaScript and CSS assets.
   ```bash
   npm run build
   # Or for development with file watching:
   npm run dev
   ```

4. **Testing**: Run the test suites to ensure everything is working correctly.
   ```bash
   composer test:unit  # Run PHPUnit tests.
   npm test            # Run Jest tests.
   ```

## Utility Services (PHP)

The plugin provides several utility services to handle common tasks consistently.

### Logger (`OPcacheToolkit\Services\Logger`)
Handles structured, file-based logging. It is multisite-aware and handles log rotation automatically.

- **Usage**:
  ```php
  use OPcacheToolkit\Plugin;
  Plugin::logger()->log( 'Something happened', 'info', [ 'context' => 'data' ] );
  ```
- **Log Files**: Logs are stored in `wp-content/uploads/opcache-toolkit-logs/`.

### Profiler (`OPcacheToolkit\Services\Profiler`)
Measures operation duration and memory usage.

- **Usage**:
  ```php
  use OPcacheToolkit\Services\Profiler;
  $token = Profiler::start( 'My Operation' );
  // ... perform work ...
  Profiler::end( $token );
  ```

### Circuit Breaker (`OPcacheToolkit\Services\CircuitBreaker`)
Prevents cascading failures by stopping execution if a service is failing repeatedly.

- **Usage**:
  ```php
  $breaker = new CircuitBreaker( 'my-service', 5, 300 );
  $result = $breaker->execute( function() {
      // Potentially failing operation.
  } );
  ```

### Error Monitor (`OPcacheToolkit\Services\ErrorMonitor`)
Automatically captures PHP errors and exceptions and mirrors them to the plugin's structured logs. It is initialized during plugin bootstrap.

## Utility Services (JavaScript)

The JavaScript layer mirrors the utility patterns used in PHP.

### Logger (`src/js/services/Logger.js`)
Handles client-side logging. It can batch logs and send them to the server via the REST API.

- **Usage**:
  ```javascript
  import Logger from '../services/Logger';
  Logger.info( 'JS Event occurred', { detail: 'value' } );
  ```

### Circuit Breaker (`src/js/services/CircuitBreaker.js`)
Implementation of the Circuit Breaker pattern for client-side operations, particularly useful for API calls.

## Coding Standards

- **WPCS**: All PHP code must adhere to WordPress Coding Standards. Use `composer phpcs` to check and `composer phpcbf` to fix issues automatically.
- **Strict Types**: All PSR-4 classes must use `declare(strict_types=1);`.
- **Documentation**: All classes, methods, and functions must have proper DocBlocks. All comments must end with a full stop.
- **Security**: Always use `$wpdb->prepare()` for database queries and verify nonces/capabilities at all entry points.
