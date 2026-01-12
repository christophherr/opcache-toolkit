# OPcache Toolkit Architecture

This document describes the architectural design and system components of the OPcache Toolkit plugin.

## Overview

OPcache Toolkit is designed with a hybrid architecture that combines modern PSR-4 compliant object-oriented programming with traditional WordPress procedural patterns. This approach ensures high testability for core business logic while maintaining seamless integration with the WordPress ecosystem.

## Core Components

### 1. Plugin Registry (`OPcacheToolkit\Plugin`)
The `Plugin` class serves as the central registry and bootstrapper. It manages service instantiation using static accessor methods, ensuring shared instances across the application.

- **Bootstrapping**: Handles hook registrations and loads procedural files.
- **Service Management**: Provides access to `OPcacheService`, `Logger`, and `StatsRepository`.

### 2. Services
Services encapsulate specific business logic and external integrations.

- **`OPcacheService`**: A wrapper around PHP's `opcache_*` functions. This abstraction allows for easier testing and environment-independent logic.
- **`Logger`**: Handles structured, file-based logging. It supports different log levels and automatic rotation.
- **`Profiler`**: Provides lightweight performance measuring for operations, logging durations and memory usage.
- **`CircuitBreaker`**: Prevents cascading failures by stopping execution of failing operations after a threshold is reached.
- **`ErrorMonitor`**: Intercepts PHP errors and exceptions, mirroring them to the plugin's structured logs.

### 3. Commands
The Command pattern is used to encapsulate reusable operations.

- **`ResetCommand`**: Logic for clearing the OPcache.
- **`PreloadCommand`**: Logic for recursively compiling PHP files into the cache.
- **`WarmupCommand`**: Logic for warming up the cache by visiting site URLs.

### 4. Database Layer
- **`StatsRepository`**: Handles all database interactions related to performance statistics. It uses `$wpdb` and ensures all queries are prepared.

### 5. REST API
The REST API layer is split into individual endpoint classes extending a `BaseEndpoint`. This improves maintainability and allows for structured responses and consistent permission checks.

### 6. JavaScript Architecture
The frontend is built using a modular JavaScript architecture, bundled with Webpack. It mirrors many of the patterns used in the PHP layer to ensure consistency across the stack.

- **Services**: Client-side logic is encapsulated in services (e.g., `Logger.js`, `CircuitBreaker.js`) that mirror their PHP counterparts.
- **API Modules**: Dedicated modules in `src/js/api/` handle communication with the WordPress REST API endpoints.
- **Entry Points**: Located in `src/js/entries/`, these scripts initialize specific UI components and logic for different admin screens.
- **Build Process**: Uses Webpack and Babel to compile modern JavaScript into browser-compatible assets in the `assets/js/` directory.

## Data Flow

1. **Request Entry**: A request comes in via a WordPress hook, AJAX, REST API, or WP-CLI.
2. **Routing**: The request is routed to the appropriate controller (e.g., a REST endpoint class).
3. **Execution**: The controller invokes a Command or Service to perform the required logic.
4. **Processing**: Services interact with the environment (OPcache, Filesystem) or the Database.
5. **Response**: The result is returned to the user in a standardized format (JSON for REST/AJAX, console output for CLI).

## Directory Structure

- `includes/OPcacheToolkit/`: PSR-4 classes (Namespaced).
    - `Services/`: Business logic and wrappers.
    - `Commands/`: Encapsulated operations.
    - `REST/`: API endpoint handlers.
    - `Database/`: Data access objects.
    - `CLI/`: WP-CLI command registrations.
- `includes/core/`: Procedural core logic.
- `includes/admin/`: Admin UI and settings logic.
- `includes/system/`: Integration hooks and system utilities.
- `assets/`: CSS, JS, and image assets.
- `tests/`: Unit and integration test suites.

## Design Decisions

For detailed information on why specific architectural choices were made, please refer to the [Architecture Decision Records (ADRs)](./adr/README.md).
