# OPcache Toolkit: Jest Testing Guide

## 🧪 Required Tools

| Purpose | Package | Usage |
|---------|---------|-------|
| Test runner | `jest` | Core testing framework |
| DOM assertions | `jest-dom` | Extended matchers (toBeInTheDocument, etc) |
| Environment | `jsdom` | Browser-like environment for tests |
| Scripting | `@wordpress/scripts` | WordPress-optimized build and test tools |

## 🏗️ Established Patterns

### 1. Global Mocks
The `tests/jest/setup.js` file provides global mocks for all localized WordPress data used in the plugin. These include:
- `opcacheToolkitData`: REST URL and Nonce.
- `opcacheToolkitLive`: Status and health endpoints.
- `opcacheToolkitCharts`: Chart data and configuration.
- `opcacheToolkitWPAdminDashboard`: Dashboard-specific URLs and nonces.

### 2. WordPress Package Mocking
We mock `@wordpress` packages via `jest.mock()` in `setup.js` to ensure they are available even if not physically present in `node_modules`.

```javascript
jest.mock('@wordpress/i18n', () => ({
    __: (val) => val,
    _x: (val) => val,
}), { virtual: true });
```

### 3. API Fetch Mocking
`fetch` is mocked globally in `setup.js`. You can override it in individual tests to simulate different API responses.

```javascript
global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ success: true, data: { ... } }),
    }),
);
```

---

## 📝 Test Patterns

### 1. Testing Utility Functions

```javascript
// tests/jest/utils/format.test.js
import { formatMemory } from '../../../assets/js/utils/format';

describe('formatMemory', () => {
    it('formats bytes to MB correctly', () => {
        expect(formatMemory(1048576)).toBe('1.00 MB');
    });
});
```

### 2. Testing API Requests

```javascript
// tests/jest/api/status.test.js
describe('Status Polling', () => {
    it('calls the correct endpoint with nonce', async () => {
        await fetchStatus();
        
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('/opcache-toolkit/v1/status'),
            expect.objectContaining({
                headers: {
                    'X-WP-Nonce': opcacheToolkitLive.nonce
                }
            })
        );
    });
});
```

---

## 🎯 Testing Principles

1.  **Test User-Visible Behavior**: Avoid testing internal implementation details. Focus on what the user sees or what the system produces.
2.  **Mock External Dependencies**: Always mock API calls and global WordPress objects.
3.  **Clear Mocks Between Tests**: Use `beforeEach(() => jest.clearAllMocks())` to prevent test leakage.
4.  **No Real Timers**: Use `jest.useFakeTimers()` for testing polling logic or timeouts.

---

## ✅ Jest Testing Checklist

- [ ] Does the test file live in `tests/jest/`?
- [ ] Are all API calls mocked?
- [ ] Does the test use `screen` and `within` for DOM queries (if applicable)?
- [ ] Are mocks cleared in `beforeEach`?
- [ ] Does the test run successfully with `npm test`?
