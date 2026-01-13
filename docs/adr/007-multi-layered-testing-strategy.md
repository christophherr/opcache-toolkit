# ADR 007: Multi-Layered Testing Strategy

**Status:** Accepted
**Date:** 2026-01-12
**Deciders:** Junie, Developer

## Context
The OPcache Toolkit is transitioning to a PSR-4 architecture to improve maintainability and reliability. A robust testing strategy is required to verify the new architecture and prevent regressions. Testing in a WordPress environment presents challenges, particularly around mocking global functions and handling environment-dependent PHP extensions like Zend OPcache.

## Decision
We implement a multi-layered testing strategy that distinguishes between **Unit Tests** and **Integration Tests**, using a specific set of tools to handle different mocking requirements.

### Testing Layers
1.  **Unit Tests (Pure Logic)**: Test individual classes in isolation. No database, network, or actual WordPress installation required.
2.  **Integration Tests (WordPress-Dependent)**: Test components that interact with the WordPress core, database, or filesystem.

### Tooling
-   **PHPUnit**: The core testing framework.
-   **Mockery**: Used for mocking objects and service dependencies.
-   **Brain\Monkey**: Used for mocking WordPress functions, hooks, and filters.
-   **php-mock**: Specifically used to mock global PHP functions (e.g., `opcache_*`, `time`, `ini_get`) within namespaced code.

### Architectural Approach
-   **Service Wrappers**: Environment-dependent functions (like `opcache_reset`) are wrapped in service classes (e.g., `OPcacheService`).
-   **Dependency Injection**: Components receive their dependencies (Services, Repositories) via constructors, allowing easy injection of Mockery mocks in tests.
-   **Namespace Interception**: We utilize PHP's namespace fallback mechanism to intercept global function calls. By calling `opcache_reset()` instead of `\opcache_reset()` inside the `OPcacheToolkit\Services` namespace, `php-mock` can provide a mock implementation.

## Consequences

### Positive
-   **High Confidence**: Critical business logic is verified by 100% code coverage in unit tests.
-   **Speed**: Unit tests run extremely fast because they have no external dependencies.
-   **Isolation**: Failures in one component are easily traced because dependencies are mocked.
-   **Environment Independence**: Tests can run on any system, even if the Zend OPcache extension is not installed or enabled.

### Negative
-   **Mock Maintenance**: Extensive use of mocks requires keeping mock behavior in sync with real behavior.
-   **Setup Complexity**: Multiple mocking libraries (`Brain\Monkey`, `Mockery`, `php-mock`) increase the learning curve for new contributors.

### Neutral
-   **Strict Coding Style**: Developers must avoid leading backslashes on global function calls intended for mocking (e.g., use `time()` not `\time()`).

## Alternatives Considered

### Relying Solely on Integration Tests
-   **Rejected**: Too slow, requires a full WordPress environment (database, etc.), and makes it harder to test edge cases or error conditions from global PHP functions.

### Using Only Mockery
-   **Rejected**: Mockery cannot mock global PHP functions or WordPress's procedural hook system.

## Implementation Notes
-   **BaseTestCase**: A shared `OPcacheToolkit\Tests\Unit\BaseTestCase` provides common setup for `Brain\Monkey` and `wpdb` mocking.
-   **Namespace Requirement**: For `php-mock` to work, the function call must not be fully qualified (no leading `\`).

## References
-   [ADR-004: Design Patterns to Implement](./004-design-patterns-to-implement.md)
-   `dev/dev-diary.md`
