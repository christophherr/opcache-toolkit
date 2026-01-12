# OPcache Toolkit: Testing Guide

## 🎯 Overview
The OPcache Toolkit follows a multi-layered testing strategy to ensure reliability, security, and performance. We use **PHPUnit** for server-side testing (PHP) and **Jest** for client-side testing (JavaScript).

## 🧪 Testing Strategy

### 1. PHPUnit (Server-Side)
We distinguish between two types of PHP tests:
- **Unit Tests**: Test isolated business logic in `includes/OPcacheToolkit/`. We use `Brain\Monkey` and `Mockery` to mock WordPress and dependencies.
- **Integration Tests**: Test interactions with a real WordPress environment, including database operations and REST API responses.

### 2. Jest (Client-Side)
We use Jest to test our JavaScript logic, specifically:
- **Utility Functions**: Helper functions for data formatting and calculations.
- **API Clients**: Verifying correct request construction and response handling.
- **Dashboard Logic**: Testing live polling, chart data processing, and user interactions.

## 📂 Folder Structure
```
tests/
├── phpunit/
│   ├── unit/            # PHP Unit tests
│   └── integration/     # PHP Integration tests
└── jest/                # JavaScript tests
    ├── mocks/           # JS mocks for styles/assets
    └── setup.js         # Jest environment setup
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- Composer
- Node.js & npm
- Local WordPress environment (for integration tests)

### Running Tests
| Command | Purpose |
|---------|---------|
| `composer test` | Run all PHP unit tests |
| `composer test:unit` | Run PHP unit tests only |
| `composer test:integration` | Run PHP integration tests (requires WP_TESTS_DIR) |
| `npm test` | Run all Jest tests |
| `npm run test:watch` | Run Jest tests in watch mode |

## 📚 Detailed Documentation
- [PHPUnit Testing Guide](./phpunit-testing.md)
- [Jest Testing Guide](./jest-testing.md)
- [ADR-007: Multi-Layered Testing Strategy](../adr/007-multi-layered-testing-strategy.md)
