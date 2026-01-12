
# **OPcache Toolkit Dashboard**
A modern, real‑time, WordPress‑native dashboard for monitoring and managing PHP OPcache.

---

## **📊 Features Overview**

### **1. Real‑Time OPcache Status**
- Live polling of OPcache metrics
- Hit rate, memory usage, wasted memory, cached scripts
- Auto‑refresh with pause/resume
- “Last updated” timestamp + live indicator
- Lightweight REST API endpoints for fast updates

---

## **📈 Performance Charts**
- Chart.js‑powered visualizations
- Hit rate over time
- Memory usage and fragmentation
- Cached scripts count
- Wasted memory tracking
- Zoom & pan support (Chart.js Zoom plugin)
- Manual refresh button
- Auto‑refresh toggle

---

## **📦 Preload Progress**
- Real‑time preload status
- Progress bar with smooth animation
- Total scripts, completed scripts, and percentage
- REST‑driven updates

---

## **❤️ System Health Checks**
- OPcache configuration validation
- Memory thresholds
- Restart recommendations
- Color‑coded status indicators
- Expandable/collapsible meta box

---

## **🧱 WordPress‑Native Meta Box Layout**
- Uses WP’s built‑in `postbox` + `meta-box-sortables`
- Drag‑and‑drop widget ordering
- Collapsible sections
- Per‑user persistence
- No custom drag logic required
- Fully compatible with WP Admin UI

---

## **🧭 Sidebar Navigation**
- Custom left‑side navigation panel
- Smooth scrolling to each meta box
- Active‑section highlighting
- Sticky sidebar (when layout allows)
- Fully theme‑aware (uses plugin CSS variables)

---

## **🎨 Custom UI Theme**
- Unified color palette
- Card‑style components
- Shadows, borders, spacing tuned for WP Admin
- Responsive layout
- Mobile‑friendly sidebar collapse behavior

---

## **🔌 REST API Endpoints**
The plugin exposes several endpoints under:

```
/wp-json/opcache-toolkit/v1/
```

Including:

- `/status` — live OPcache metrics
- `/health` — system health checks
- `/preload-progress` — preload status
- `/chart-data` — historical chart data

All endpoints are nonce‑protected and optimized for low overhead.

---

## **⚙️ Script Architecture**
- `opcache-toolkit-live.js`
  - Handles polling, live updates, preload, health, and status cards

- `opcache-toolkit-charts.js`
  - Chart.js initialization
  - Zoom/pan
  - Auto‑refresh logic

  - Sidebar scroll‑spy
  - Highlight bar
  - Smooth scrolling

---

## **🧩 Templates**
Modular template structure:

```
includes/templates/
    dashboard-cards.php
    dashboard-charts.php
    dashboard-health.php
    dashboard-preload.php
    dashboard-export-buttons.php
```

Each template is self‑contained and easy to override.

---

## **🛠 CSS Architecture**
- `opcache-toolkit-theme.css`
  - Global variables
  - Colors, spacing, typography

- `opcache-toolkit-dashboard.css`
  - Sidebar
  - Cards
  - Charts
  - Preload bar
  - Health list
  - Layout overrides

---

## **🔒 Permissions**
- Only users with OPcache management capability can access the dashboard
- Capability is filterable for custom roles

---

## **📦 Requirements**
- PHP 7.4+
- WordPress 6.0+
- OPcache enabled

---

## **📘 License**
GPL‑compatible (same as WordPress)


## WP‑CLI Commands

OPcache Toolkit includes a full WP‑CLI interface for managing, inspecting, and maintaining OPcache from the command line.

All commands follow the format:

`wp opcache-toolkit <command> [options]`


### Available Commands

| Command | Description |
|--------|-------------|
| `wp opcache-toolkit info` | Raw OPcache information from `opcache_get_status()` |
| `wp opcache-toolkit status` | Summary of memory, strings, and statistics |
| `wp opcache-toolkit health` | Health indicators (hit rate, memory usage, wasted memory) |
| `wp opcache-toolkit reset` | Reset OPcache immediately |
| `wp opcache-toolkit preload` | Preload OPcache (sync or async) |
| `wp opcache-toolkit preload report` | Show last preload results |
| `wp opcache-toolkit warmup` | Compile only uncached PHP files |
| `wp opcache-toolkit stats clear` | Clear the OPcache statistics table |
| `wp opcache-toolkit stats export` | Export stats as JSON |
| `wp opcache-toolkit log` | Run the daily log job manually |
| `wp opcache-toolkit cleanup` | Run retention cleanup manually |
| `wp opcache-toolkit config` | Show OPcache ini configuration |

### JSON Output

All commands support a `--json` flag:
`wp opcache-toolkit status --json`


This is ideal for automation, monitoring, and scripting.

### Examples

Reset OPcache:
`wp opcache-toolkit reset`

Preload OPcache asynchronously:
`wp opcache-toolkit preload --async`

Export statistics:
`wp opcache-toolkit stats export --json > stats.json`

Check OPcache health:
`wp opcache-toolkit health`


OPCACHE(1)                 User Commands                OPCACHE(1)

NAME
    opcache – WP‑CLI interface for OPcache Toolkit

SYNOPSIS
    wp opcache-toolkit <command> [options]

DESCRIPTION
    OPcache Toolkit provides a complete WP‑CLI interface for inspecting,
    resetting, preloading, warming, and maintaining OPcache.

COMMANDS
    info
        Display raw OPcache information from opcache_get_status().

    status
        Show summarized OPcache status (memory, strings, statistics).

    health
        Display OPcache health indicators including hit rate and memory usage.

    reset
        Reset OPcache immediately.

    preload [--async]
        Preload OPcache by compiling all PHP files in plugins and themes.
        Use --async to queue the job via Action Scheduler.

    preload report
        Show the last preload report (files compiled, timestamp).

    warmup
        Compile only uncached PHP files.

    stats clear
        Clear the OPcache statistics database table.

    stats export [--json]
        Export OPcache statistics as JSON.

    log
        Run the daily OPcache logging job immediately.

    cleanup
        Run the OPcache retention cleanup job immediately.

    config
        Display OPcache ini configuration.

OPTIONS
    --json
        Output machine‑readable JSON instead of human‑readable text.

EXAMPLES
    wp opcache-toolkit reset
    wp opcache-toolkit preload --async
    wp opcache-toolkit status --json
    wp opcache-toolkit stats export --json > stats.json

AUTHOR
    OPcache Toolkit Plugin

COPYRIGHT
    This is free software; see the source for copying conditions.
