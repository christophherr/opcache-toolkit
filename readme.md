# OPcache Toolkit

**The modern OPcache dashboard for WordPress**

Real-time visibility, visual analytics, and developer-grade automation for PHP OPcache — all in a safe, WordPress-native interface.

---

## 🚀 Features

### Real-Time Monitoring

Watch OPcache metrics update live without refreshing the page:

- **Live polling** via REST API with pause/resume control
- **Hit rate, memory usage, fragmentation, cached scripts**
- **Auto-refresh** with configurable intervals
- **"Last updated"** timestamp with live indicator
- **Lightweight endpoints** optimized for minimal overhead

### Visual Analytics

Interactive Chart.js-powered visualizations:

- **Hit rate over time** — understand cache effectiveness trends
- **Memory usage & fragmentation** — spot memory pressure early
- **Cached scripts count** — track bytecode cache growth
- **Wasted memory tracking** — identify invalidation patterns
- **Zoom & pan support** — explore historical data
- **Manual refresh** — update charts on demand

### Preload Insights

Real-time PHP 7.4+ preload progress visualization:

- **Progress bar** with smooth animation
- **Script counts** (total, completed, percentage)
- **REST-driven updates** for live preload tracking
- **Preload report** showing last compilation results

### System Health

Proactive configuration checks and recommendations:

- **OPcache configuration validation**
- **Memory threshold warnings**
- **Restart recommendations**
- **Color-coded status indicators** (✅ Good, ⚠️ Warning, ❌ Critical)
- **Expandable/collapsible** health panel

### WP-CLI Automation

Full command-line interface for developers and DevOps teams:

```shell script
# Check OPcache status
wp opcache-toolkit status

# Reset OPcache (careful!)
wp opcache-toolkit reset

# Preload OPcache asynchronously
wp opcache-toolkit preload --async

# Check system health
wp opcache-toolkit health

# Export statistics as JSON
wp opcache-toolkit stats export --json > stats.json

# Get raw OPcache info
wp opcache-toolkit info

# Run warmup (compile uncached files only)
wp opcache-toolkit warmup

# Show preload report
wp opcache-toolkit preload report

# View configuration
wp opcache-toolkit config
```


**All commands support `--json` flag** for automation, monitoring, and CI/CD pipelines.

### WordPress-Native UI

Built with WordPress's native meta box system:

- **Drag-and-drop widget ordering**
- **Collapsible sections** with per-user persistence
- **Responsive layout** for mobile and tablet
- **Sidebar navigation** with smooth scrolling
- **Custom color palette** tuned for WP Admin
- **Card-style components** with shadows and spacing

---

## 📦 Installation

### From WordPress.org (recommended)

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **"OPcache Toolkit"**
3. Click **Install Now**, then **Activate**

### Manual Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/opcache-toolkit/`
3. Activate via **Plugins** menu

### Requirements

- **PHP 8.0**
- **WordPress 6.9+**
- **OPcache enabled** in PHP configuration
- **`manage_options` capability** (Administrator by default)

---

## 🎯 Who Is This For?

### WordPress Developers
Get real-time visibility into how OPcache is performing on your development and staging environments.

### Performance Engineers
Monitor hit rates, memory pressure, and preload effectiveness with interactive charts and health checks.

### Agencies
Safely manage OPcache across multiple client sites without exposing dangerous reset operations in the UI.

### DevOps Teams
Automate OPcache management in CI/CD pipelines with the full WP-CLI suite.

### Technical Site Owners
Understand OPcache performance without needing to SSH into your server or run command-line tools.

### Hosting Providers
Provide a safe, per-site OPcache dashboard for shared hosting and multisite environments.

---

## 🆚 How Does It Compare?

**OPcache Toolkit is the only WordPress plugin that combines:**

- ✅ Real-time monitoring
- ✅ Interactive charts
- ✅ Preload progress visualization
- ✅ System health checks
- ✅ Full WP-CLI suite
- ✅ Safe for shared hosting and multisite

### vs Other OPcache Plugins

**Reset buttons** (Flush OPcache, Clear OPcache)
→ One button. No monitoring. No analytics.

**Info panels** (WP OPcache, OPCache Scripts, Atec Cache Info)
→ Basic OPcache info. No charts. No live data.

**Object caching plugins** (Docket Cache)
→ Not OPcache. Different layer entirely.

**OPcache Manager**
→ Powerful control features (invalidation, warmup, scheduling)
→ But no real-time charts, preload progress, or modern dashboard UI

**OPcache Toolkit focuses on visibility and safety.**
**OPcache Manager focuses on control and invalidation.**

Most developers will want insights first, control second — that's where OPcache Toolkit excels.

---

## 🔌 REST API Endpoints

All endpoints are under `/wp-json/opcache-toolkit/v1/` and are **nonce-protected**.

| Endpoint | Description |
|---------|-------------|
| `/status` | Live OPcache metrics (hit rate, memory, scripts) |
| `/health` | System health checks and recommendations |
| `/preload-progress` | Preload status (total, completed, percentage) |
| `/chart-data` | Historical time-series data for charts |

**Example**:
```shell script
curl -X GET "https://example.com/wp-json/opcache-toolkit/v1/status" \
  -H "X-WP-Nonce: YOUR_NONCE"
```


**Response**:
```json
{
  "enabled": true,
  "hit_rate": 98.5,
  "memory_usage": 67.2,
  "cached_scripts": 1247,
  "wasted_memory": 2.3,
  "last_restart_time": "2026-01-12 10:30:00"
}
```


---

## ⚙️ Architecture

### Script Structure

**`opcache-toolkit-live.js`**
Handles live polling, status cards, preload progress, and health panel updates.

**`opcache-toolkit-charts.js`**
Chart.js initialization, zoom/pan, auto-refresh logic, and chart data management.

**Sidebar navigation**
Scroll-spy, active section highlighting, smooth scrolling to meta boxes.

### Template Structure

Modular templates in `includes/templates/`:

```
dashboard-cards.php         — Status overview cards
dashboard-charts.php        — Chart.js visualizations
dashboard-health.php        — System health panel
dashboard-preload.php       — Preload progress bar
dashboard-export-buttons.php — Export utilities
```


Each template is self-contained and easy to override or extend.

### CSS Architecture

**`opcache-toolkit-theme.css`**
Global CSS variables (colors, spacing, typography, shadows)

**`opcache-toolkit-dashboard.css`**
Dashboard-specific styles (sidebar, cards, charts, preload bar, health list, layout overrides)

All styles are scoped to `.opcache-toolkit-*` classes to avoid conflicts.

---

## 🔒 Permissions

- Dashboard access requires **`manage_options`** capability by default
- Capability is **filterable** via `opcache_toolkit_capability` hook
- REST API endpoints are **nonce-protected**
- WP-CLI commands respect WordPress user context

---

## 📚 Documentation

### Available Documentation

- **[Plugin Architecture](docs/architecture.md)** - Plugin architecture
- **[ADR (Architecture Decision Records)](docs/adr/)** — Key architectural decisions
- **[Rest API](docs/rest-api.md)** - Rest API documentation
- **[How to](docs/how-to.md)** - How-To use the plugin
- **[Developer Documentation](docs/developer.md)** - Developer documentation
- **[Multisite Documentation](docs/multisite.md)** - Multisite support
- **[Setting Documentation](docs/settings.md)** - Plugin settings and options
- **[WP Cli Cheat Sheet](docs/wp-cli-cheatsheet.md)** - WP-CLI command reference
- **[Testing Documentation](docs/testing.md)** — PHPUnit and Jest testing guidelines
- **[Runbook](docs/runbook.md)** - What to check when thing go wrong...


### Getting Help

- **GitHub Issues**: Report bugs or request features
- **WordPress.org Support**: Community support forum

---

## 🛠 WP-CLI Commands

OPcache Toolkit includes a full WP-CLI interface for managing, inspecting, and maintaining OPcache from the command line.

All commands follow the format:

```shell script
wp opcache-toolkit <command> [options]
```


### Available Commands

| Command | Description |
|---------|-------------|
| `wp opcache-toolkit info` | Raw OPcache information from `opcache_get_status()` |
| `wp opcache-toolkit status` | Summary of memory, strings, and statistics |
| `wp opcache-toolkit health` | Health indicators (hit rate, memory usage, wasted memory) |
| `wp opcache-toolkit reset` | Reset OPcache immediately |
| `wp opcache-toolkit preload` | Preload OPcache (sync or async) |
| `wp opcache-toolkit preload report` | Show last preload results |
| `wp opcache-toolkit warmup` | Compile only uncached files |
| `wp opcache-toolkit stats clear` | Clear the OPcache statistics table |
| `wp opcache-toolkit stats export` | Export stats as JSON |
| `wp opcache-toolkit log` | Run the daily log job manually |
| `wp opcache-toolkit cleanup` | Run retention cleanup manually |
| `wp opcache-toolkit config` | Show OPcache ini configuration |

### JSON Output

All commands support a `--json` flag for automation, monitoring, and scripting:

```shell script
wp opcache-toolkit status --json
```


**Example JSON output**:
```json
{
  "enabled": true,
  "memory": {
    "used": 67.2,
    "free": 32.8,
    "wasted": 2.3
  },
  "statistics": {
    "hits": 123456,
    "misses": 789,
    "hit_rate": 99.36
  }
}
```


### Examples

**Reset OPcache**:
```shell script
wp opcache-toolkit reset
```


**Preload OPcache asynchronously**:
```shell script
wp opcache-toolkit preload --async
```


**Export statistics**:
```shell script
wp opcache-toolkit stats export --json > stats.json
```


**Check OPcache health**:
```shell script
wp opcache-toolkit health
```


**View configuration**:
```shell script
wp opcache-toolkit config
```


---

## 📘 License

GPL v3 or later

---


