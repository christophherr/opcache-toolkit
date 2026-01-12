# OPcache Toolkit - Implementation Roadmap

**Status:** Draft
**Last Updated:** 2026-01-12

This roadmap outlines the implementation plan for migrating OPcache Toolkit to PSR-4 architecture based on ADR-001 through ADR-004.

---

## Overview

### Current State
- 22 procedural PHP files
- ~43 global functions
- ~26 hook registrations
- No unit tests
- Some SQL queries without `$wpdb->prepare()`

### Target State
- ~8 PSR-4 class files (testable core logic)
- ~8 procedural files (WordPress hooks/templates)
- 80%+ test coverage for business logic
- All SQL queries use `$wpdb->prepare()`
- Performance: 95% cache hit rate on chart data

---

## Phase 1: Foundation & Quick Wins
**Timeline:** Week 1
**Priority:** HIGH

### 1.1 Infrastructure Setup
- [x] Create ADR documentation
- [ ] Add `composer.json` with PSR-4 autoloading
- [ ] Set up PHPUnit with Brain\Monkey for mocking WordPress
- [ ] Create `tests/` directory structure

**Files to create:**
```
composer.json
phpunit.xml.dist
tests/
├── bootstrap.php
├── Unit/
│   └── BaseTestCase.php
└── Integration/
```

**Acceptance Criteria:**
- `composer dump-autoload` runs successfully
- `vendor/bin/phpunit` executes (even with no tests)

### 1.2 Fix Missing Widget Enqueue ✅
**Status:** COMPLETED (mentioned by user)

- [x] Add `opcache-toolkit-widgets.js` enqueue in `admin-dashboard.php`

### 1.3 SQL Query Hardening
**Priority:** HIGH (Security)

Convert all `$wpdb->query()` and `$wpdb->get_results()` to use `$wpdb->prepare()` with placeholders.

**Files to update:**
1. `includes/system/rest.php:324` - Chart data query
2. `includes/core/db.php:152` - TRUNCATE (use `%i` for table name)
3. `includes/core/db.php:164` - SELECT query
4. `includes/system/wp-cli.php:389` - TRUNCATE
5. `includes/system/wp-cli.php:419` - SELECT query

**Example:**
```php
// Before
$wpdb->query("TRUNCATE TABLE {$table}");

// After (WordPress 6.2+)
$wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $table));
```

**Acceptance Criteria:**
- All SQL queries use `$wpdb->prepare()`
- Use `%i` for identifiers (table/column names)
- Use `%d` for integers, `%s` for strings, `%f` for floats

### 1.4 Delete Backup Files
**Priority:** LOW (Cleanup)

Remove leftover files:
- `includes/admin/admin-dashboard copy.php`
- `assets/js/opcache-toolkit-widgets copy.js`
- `assets/css/opcache-toolkit-dashboard copy.css`

---

## Phase 2: Core Services
**Timeline:** Week 2
**Priority:** HIGH (Unblocks testing)

### 2.1 Create OPcacheService Wrapper
**Justification:** Enables mocking of `opcache_*()` functions for unit tests

**File:** `includes/OPcacheToolkit/Services/OPcacheService.php`

**Implementation:**
```php
namespace OPcacheToolkit\Services;

class OPcacheService {
    public function isEnabled(): bool {
        return function_exists('opcache_get_status')
            && opcache_get_status() !== false;
    }

    public function getStatus(bool $scripts = false): ?array {
        if (!function_exists('opcache_get_status')) {
            return null;
        }

        $status = opcache_get_status($scripts);
        return $status !== false ? $status : null;
    }

    public function getConfiguration(): ?array {
        $config = ini_get_all('opcache');
        return is_array($config) ? $config : null;
    }

    public function reset(): bool {
        return function_exists('opcache_reset') && opcache_reset();
    }

    public function compileFile(string $path): bool {
        if (!function_exists('opcache_compile_file')) {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        try {
            return opcache_compile_file($path);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('OPcache compile error: ' . $e->getMessage());
            }
            return false;
        }
    }

    public function getHitRate(): float {
        $status = $this->getStatus();
        return $status['opcache_statistics']['opcache_hit_rate'] ?? 0.0;
    }

    public function getMemoryUsage(): array {
        $status = $this->getStatus();
        return $status['memory_usage'] ?? [];
    }
}
```

**Tests:** `tests/Unit/Services/OPcacheServiceTest.php`

**Acceptance Criteria:**
- Can instantiate service
- Returns null when OPcache not available
- Handles missing functions gracefully
- Unit tests pass with mocked PHP functions

### 2.2 Create Plugin Registry
**Justification:** Provides shared service instances with full IDE support

**File:** `includes/OPcacheToolkit/Plugin.php`

**Implementation:**
```php
namespace OPcacheToolkit;

class Plugin {
    private static ?self $instance = null;
    private static ?Services\OPcacheService $opcache = null;
    private static ?Database\StatsRepository $stats = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Get OPcache service instance.
     */
    public static function opcache(): Services\OPcacheService {
        return self::$opcache ??= new Services\OPcacheService();
    }

    /**
     * Get stats repository instance.
     */
    public static function stats(): Database\StatsRepository {
        global $wpdb;
        return self::$stats ??= new Database\StatsRepository($wpdb);
    }

    /**
     * Bootstrap the plugin.
     */
    public static function boot(): void {
        // Load procedural files
        self::loadFiles();

        // Register REST endpoints
        add_action('rest_api_init', [self::class, 'registerRestEndpoints']);

        // Register WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('opcache-toolkit', new CLI\Commands(
                self::opcache(),
                self::stats()
            ));
        }
    }

    private static function loadFiles(): void {
        $files = [
            'admin/admin-menu.php',
            'admin/admin-settings.php',
            'admin/admin-dashboard.php',
            'admin/admin-bar.php',
            'admin/dashboard-widget.php',
            'core/cron.php',
            'core/db.php',
            'system/notices.php',
        ];

        foreach ($files as $file) {
            require_once OPCACHE_TOOLKIT_PATH . 'includes/' . $file;
        }
    }

    private static function registerRestEndpoints(): void {
        (new REST\StatusEndpoint(self::opcache()))->register();
        (new REST\PreloadEndpoint(self::opcache()))->register();
        (new REST\ResetEndpoint(self::opcache()))->register();
        (new REST\ChartDataEndpoint(self::stats()))->register();
        (new REST\HealthEndpoint(self::opcache()))->register();
        (new REST\PreloadProgressEndpoint())->register();
    }
}
```

**Acceptance Criteria:**
- IDE autocomplete works: `Plugin::opcache()->getStatus()`
- Services instantiated only once
- Can still pass mocks in tests

---

## Phase 3: Database Layer
**Timeline:** Week 3
**Priority:** HIGH (Performance gains)

### 3.1 Create StatsRepository
**Justification:** Centralizes queries, adds caching (95% performance improvement)

**File:** `includes/OPcacheToolkit/Database/StatsRepository.php`

**Implementation:**
```php
namespace OPcacheToolkit\Database;

class StatsRepository {
    private \wpdb $wpdb;
    private string $table;

    private const CACHE_KEY = 'opcache_toolkit_chart_data';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(\wpdb $wpdb) {
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'opcache_toolkit_stats';
    }

    /**
     * Get chart data with caching.
     */
    public function getChartData(int $limit = 180): array {
        $cache_key = self::CACHE_KEY . "_{$limit}";
        $cached = get_transient($cache_key);

        if (false !== $cached) {
            return $cached;
        }

        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT recorded_at, hit_rate, cached_scripts, wasted_memory
             FROM %i
             ORDER BY recorded_at ASC
             LIMIT %d",
            $this->table,
            $limit
        ));

        $data = $this->formatChartData($rows);

        set_transient($cache_key, $data, self::CACHE_TTL);

        return $data;
    }

    /**
     * Insert stats row and invalidate cache.
     */
    public function insert(array $data): bool {
        $result = $this->wpdb->insert(
            $this->table,
            [
                'recorded_at' => $data['recorded_at'],
                'hit_rate' => (float) $data['hit_rate'],
                'cached_scripts' => (int) $data['cached_scripts'],
                'wasted_memory' => (int) $data['wasted_memory'],
            ],
            ['%s', '%f', '%d', '%d']
        );

        // Invalidate all chart data caches
        delete_transient(self::CACHE_KEY . '_180');
        delete_transient(self::CACHE_KEY . '_30');

        return false !== $result;
    }

    /**
     * Delete stats older than specified days.
     */
    public function deleteOlderThan(int $days): int {
        return (int) $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM %i WHERE recorded_at < (NOW() - INTERVAL %d DAY)",
            $this->table,
            $days
        ));
    }

    /**
     * Truncate entire table.
     */
    public function truncate(): bool {
        $result = $this->wpdb->query($this->wpdb->prepare("TRUNCATE TABLE %i", $this->table));

        // Clear all caches
        delete_transient(self::CACHE_KEY . '_180');
        delete_transient(self::CACHE_KEY . '_30');

        return false !== $result;
    }

    /**
     * Get all rows (for export).
     */
    public function getAll(): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM %i ORDER BY recorded_at ASC",
                $this->table
            ),
            ARRAY_A
        );
    }

    private function formatChartData(array $rows): array {
        $data = [
            'labels' => [],
            'hitRate' => [],
            'cached' => [],
            'wasted' => [],
        ];

        foreach ($rows as $row) {
            $data['labels'][] = $row->recorded_at;
            $data['hitRate'][] = (float) $row->hit_rate;
            $data['cached'][] = (int) $row->cached_scripts;
            $data['wasted'][] = (int) $row->wasted_memory;
        }

        return $data;
    }
}
```

**Update consumers:**
- `includes/system/rest.php` - Use repository instead of direct queries
- `includes/core/cron.php` - Use repository for inserts
- `includes/core/db.php` - Keep schema functions, use repository for queries

**Tests:** `tests/Unit/Database/StatsRepositoryTest.php`

**Acceptance Criteria:**
- All queries use `$wpdb->prepare()` with `%i` for table name
- First request queries DB, subsequent requests served from cache
- Cache invalidates on insert
- Unit tests with mocked `$wpdb`

---

## Phase 4: Commands
**Timeline:** Week 4
**Priority:** MEDIUM (Enables reuse & testing)

### 4.1 Create PreloadCommand
**File:** `includes/OPcacheToolkit/Commands/PreloadCommand.php`

**Features:**
- Accepts `OPcacheService` dependency
- Accepts custom paths (for testing)
- Returns `CommandResult` with success/failure/data
- Async support via Action Scheduler

**Tests:** `tests/Unit/Commands/PreloadCommandTest.php`

### 4.2 Create ResetCommand
**File:** `includes/OPcacheToolkit/Commands/ResetCommand.php`

### 4.3 Create WarmupCommand
**File:** `includes/OPcacheToolkit/Commands/WarmupCommand.php`

### 4.4 Create CommandResult Value Object
**File:** `includes/OPcacheToolkit/Commands/CommandResult.php`

**Acceptance Criteria:**
- Commands reusable from REST/CLI/Admin
- Consistent result format
- Unit testable with mocks

---

## Phase 5: REST API Refactor
**Timeline:** Week 5
**Priority:** MEDIUM (Maintainability)

### 5.1 Split REST Endpoints

Convert `includes/system/rest.php` (466 lines) into separate classes:

**Files to create:**
- `includes/OPcacheToolkit/REST/StatusEndpoint.php`
- `includes/OPcacheToolkit/REST/PreloadEndpoint.php`
- `includes/OPcacheToolkit/REST/ResetEndpoint.php`
- `includes/OPcacheToolkit/REST/ChartDataEndpoint.php`
- `includes/OPcacheToolkit/REST/HealthEndpoint.php`
- `includes/OPcacheToolkit/REST/PreloadProgressEndpoint.php`

**Each endpoint:**
- Accepts service dependencies via constructor
- Has `register()` method
- Has `handle()` method for request processing
- Has `check_permission()` method

**Remove after migration:**
- `includes/system/rest.php` (move shared functions to endpoints)

**Tests:** `tests/Integration/REST/` - One test per endpoint

**Acceptance Criteria:**
- Each endpoint file < 100 lines
- Clear dependency injection
- All endpoints still functional

---

## Phase 6: WP-CLI Refactor
**Timeline:** Week 6
**Priority:** LOW (Nice to have)

### 6.1 Namespace WP-CLI Commands
**File:** `includes/OPcacheToolkit/CLI/Commands.php`

**Changes:**
- Add namespace: `namespace OPcacheToolkit\CLI;`
- Accept `OPcacheService` and `StatsRepository` via constructor
- Use commands from Phase 4 instead of direct logic

**Update registration:**
```php
// In Plugin::boot()
if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command(
        'opcache-toolkit',
        new CLI\Commands(Plugin::opcache(), Plugin::stats())
    );
}
```

**Remove after migration:**
- `includes/system/wp-cli.php` (replaced by class)

**Acceptance Criteria:**
- All WP-CLI commands still work
- Commands use shared `PreloadCommand`, `ResetCommand`, etc.

---

## Phase 7: Complete Features
**Timeline:** Week 7-8
**Priority:** MEDIUM

### 7.1 Implement Alerts System
**Current:** Stub exists in `includes/system/alerts.php`

**Implementation:**
```php
// Hook into daily cron
add_action('opcache_toolkit_daily_log', function() {
    $opcache = Plugin::opcache();
    $hit_rate = $opcache->getHitRate();

    $threshold = (float) opcache_toolkit_get_setting('opcache_toolkit_alert_threshold', 90);

    if ($hit_rate < $threshold) {
        $email = opcache_toolkit_get_setting(
            'opcache_toolkit_alert_email',
            get_option('admin_email')
        );

        wp_mail(
            $email,
            __('OPcache Hit Rate Alert', 'opcache-toolkit'),
            sprintf(
                __('Your OPcache hit rate has dropped to %.2f%%. Threshold: %.2f%%', 'opcache-toolkit'),
                $hit_rate,
                $threshold
            )
        );
    }
});
```

**Acceptance Criteria:**
- Email sent when hit rate drops below threshold
- Respects user settings
- Uses `wp_mail()` with filters

### 7.2 Implement Debug Logging
**Current:** Empty file `includes/debug/debug.php`

**Implementation:**
```php
function opcache_toolkit_log(string $message, string $level = 'info'): void {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    error_log(sprintf('[OPcache Toolkit] [%s] %s', strtoupper($level), $message));
}
```

**Add logging to:**
- OPcache reset operations
- Preload operations
- Failed API calls
- Alert triggers

---

## Phase 8: Testing & Quality
**Timeline:** Ongoing

### 8.1 Unit Tests
**Target:** 80%+ coverage for business logic

**Priority classes to test:**
- ✅ `OPcacheService` (mock PHP functions)
- ✅ `StatsRepository` (mock `$wpdb`)
- ✅ `PreloadCommand` (mock service + filesystem)
- ✅ `ResetCommand` (mock service)
- ✅ `CommandResult` (value object)

### 8.2 Integration Tests
**Target:** Critical paths work end-to-end

**Tests:**
- REST API endpoints return correct responses
- WP-CLI commands execute successfully
- Cron jobs run without errors

### 8.3 Code Quality
- Run `composer phpcs` - fix all warnings
- Run `composer phpcbf` - auto-fix style issues
- Add PHPDoc to all classes/methods
- Update README.md with new architecture

---

## Phase 9: Documentation
**Timeline:** Week 9
**Priority:** LOW

### 9.1 Update Documentation
- Update README.md with PSR-4 structure
- Document testing setup
- Add architecture diagram
- Update developer guide

### 9.2 Create Migration Guide
Document for other developers:
- How to use new services
- How to add new REST endpoints
- How to add new commands
- Testing best practices

---

## Future Enhancements

### Feature Ideas

#### 1. Multisite Network Dashboard
**Problem:** Dashboard only works on main site; no network-wide stats

**Solution:**
- Create network admin page that aggregates stats from all subsites
- Show per-site OPcache status
- Bulk operations (reset all, preload all)

**Effort:** Medium (2-3 days)

#### 2. Advanced Caching Strategies
**Problem:** Simple 5-minute cache; no cache warming

**Solution:**
- Cache warming on plugin/theme updates
- Predictive cache invalidation
- Configurable TTL per endpoint
- Redis/Memcached support

**Effort:** Medium (2-3 days)

#### 3. Historical Charts with Date Range
**Problem:** Fixed 180-day window; no date filtering

**Solution:**
- Add date range picker to dashboard
- Support custom time ranges (7d, 30d, 90d, custom)
- Export filtered data

**Effort:** Small (1 day)

#### 4. Real-Time Updates via WebSockets
**Problem:** Polling every 30-60s; not truly real-time

**Solution:**
- Replace polling with Server-Sent Events (SSE) or WebSockets
- Push updates only when data changes
- Reduce server load

**Effort:** Large (1 week)

#### 5. OPcache File Viewer
**Problem:** Can't see which files are cached

**Solution:**
- Add tab to dashboard showing cached files
- Show file size, hit count, last access
- Filter/search cached files
- Invalidate individual files

**Effort:** Medium (2-3 days)

#### 6. Automated Performance Reports
**Problem:** No scheduled reports; manual export only

**Solution:**
- Weekly/monthly email reports
- PDF generation with charts
- Configurable metrics
- Trend analysis (improving/degrading)

**Effort:** Large (1 week)

#### 7. Slack/Discord Webhook Alerts
**Problem:** Only email alerts

**Solution:**
- Support Slack/Discord/custom webhooks
- Configurable alert types (hit rate, memory, errors)
- Rich formatting with charts

**Effort:** Small (1 day)

#### 8. OPcache Configuration Recommendations
**Problem:** No guidance on optimal settings

**Solution:**
- Analyze current config vs workload
- Suggest memory_consumption adjustments
- Warn about dangerous settings
- One-click apply recommendations (via wp-config.php snippets)

**Effort:** Medium (3 days)

#### 9. A/B Testing for OPcache Settings
**Problem:** Hard to know if config changes improve performance

**Solution:**
- Test different settings automatically
- Measure hit rate/response time over time
- Recommend best configuration
- Rollback support

**Effort:** Large (1-2 weeks)

#### 10. Integration with APM Tools
**Problem:** OPcache stats isolated; not correlated with app performance

**Solution:**
- Export metrics to New Relic, Datadog, etc.
- Correlate OPcache hit rate with response times
- Push alerts to external monitoring

**Effort:** Medium (3-4 days)

#### 11. Preload Profile Management
**Problem:** Preloads everything; no selective preloading

**Solution:**
- Create preload profiles (only active plugins, only theme, custom paths)
- Schedule different profiles for different times
- Dry-run mode to preview what will be preloaded

**Effort:** Medium (2-3 days)

#### 12. WP-CLI Enhancements
**Existing:** Basic commands work

**Enhancements:**
- Progress bars for long operations (preload, warmup)
- `wp opcache-toolkit benchmark` - measure performance impact
- `wp opcache-toolkit diagnose` - troubleshoot issues
- `wp opcache-toolkit watch` - real-time status monitoring

**Effort:** Small-Medium (1-2 days per feature)

#### 13. REST API Enhancements
**Current:** Basic CRUD operations

**Enhancements:**
- Pagination for chart data
- Filtering by date range
- GraphQL endpoint support
- Rate limit configuration per-user

**Effort:** Small (2-3 days total)

#### 14. Mobile-Friendly Dashboard
**Current:** Desktop-optimized

**Enhancements:**
- Responsive chart sizing
- Touch-friendly controls
- Mobile sidebar navigation
- Progressive Web App (PWA) support

**Effort:** Medium (3-4 days)

#### 15. Export Formats
**Current:** CSV only

**Enhancements:**
- JSON export
- Excel (XLSX) export
- PDF reports with charts
- Automated uploads to S3/FTP

**Effort:** Small-Medium (2-3 days)

---

## Success Metrics

### Performance
- Chart data endpoint: < 50ms average (from ~200ms)
- Cache hit rate: > 95% for chart data
- Database queries per request: < 10

### Testing
- Unit test coverage: > 80%
- Integration tests: All critical paths covered
- CI/CD: All tests pass on every commit

### Code Quality
- PHPCS: 0 warnings/errors
- All classes have PHPDoc
- All SQL queries use `$wpdb->prepare()`

### Maintainability
- REST endpoint files: < 100 lines each
- Clear dependency injection throughout
- IDE autocomplete works for all services

---

## Risk Mitigation

### Breaking Changes
- Keep procedural wrapper functions for backward compatibility
- Version bump to 2.0.0 signals major refactor
- Deprecation warnings before removing old functions

### Testing Gaps
- Test coverage may not reach 80% immediately
- Prioritize business logic over WordPress integration
- Add integration tests for critical user paths

### Performance Regressions
- Benchmark before/after each phase
- Monitor production sites after deployment
- Rollback plan if performance degrades

---

## Questions for Stakeholders

1. **Multisite Priority:** Should network dashboard be in Phase 7 or moved to Phase 10?
2. **Alert Channels:** Beyond email, which alert methods are most valuable (Slack, Discord, SMS)?
3. **Backwards Compatibility:** How long should deprecated functions remain (1 version, 6 months, 1 year)?
4. **Testing Requirements:** Is 80% coverage sufficient or aim for 90%+?
5. **Third-Party Dependencies:** Acceptable to add libraries (PHPStan, Psalm) or keep lean?

---

## Appendix: File Mapping

### Classes to Create (New Files)
```
includes/OPcacheToolkit/
├── Plugin.php
├── Services/
│   └── OPcacheService.php
├── Database/
│   └── StatsRepository.php
├── Commands/
│   ├── PreloadCommand.php
│   ├── ResetCommand.php
│   ├── WarmupCommand.php
│   └── CommandResult.php
├── REST/
│   ├── StatusEndpoint.php
│   ├── PreloadEndpoint.php
│   ├── ResetEndpoint.php
│   ├── ChartDataEndpoint.php
│   ├── HealthEndpoint.php
│   └── PreloadProgressEndpoint.php
└── CLI/
    └── Commands.php
```

### Procedural Files to Keep (Minimal Changes)
```
includes/
├── admin/
│   ├── admin-menu.php
│   ├── admin-settings.php
│   ├── admin-dashboard.php (add widget enqueue ✅)
│   ├── admin-bar.php
│   └── dashboard-widget.php
├── core/
│   ├── cron.php (update to use repository)
│   └── db.php (keep schema, delegate queries to repository)
├── system/
│   └── notices.php
└── templates/
    └── *.php (no changes)
```

### Files to Remove
```
includes/admin/admin-dashboard copy.php
includes/system/rest.php (replaced by REST classes)
includes/system/wp-cli.php (replaced by CLI class)
assets/js/opcache-toolkit-widgets copy.js
assets/css/opcache-toolkit-dashboard copy.css
```

---

## Next Steps

1. **Review & Approve** this roadmap
2. **Phase 1 Start:** Set up infrastructure (composer, phpunit)
3. **Weekly Sync:** Review progress, adjust priorities
4. **Iterate:** Implement → Test → Document → Deploy

---

**Questions? Concerns? Suggestions?**
Open an issue or discuss in #dev channel.
