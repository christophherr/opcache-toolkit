# OPcache Toolkit – WP‑CLI Cheat Sheet

### 🔍 Inspect OPcache
| Command | Description |
|--------|-------------|
| `wp opcache-toolkit info` | Raw OPcache info |
| `wp opcache-toolkit status` | Summary (memory, strings, stats) |
| `wp opcache-toolkit health` | Health indicators |
| `wp opcache-toolkit config` | OPcache ini settings |

### ⚡ Actions
| Command | Description |
|--------|-------------|
| `wp opcache-toolkit reset` | Reset OPcache |
| `wp opcache-toolkit preload` | Preload OPcache (sync) |
| `wp opcache-toolkit preload --async` | Queue async preload |
| `wp opcache-toolkit warmup` | Compile uncached files |

### 📊 Statistics
| Command | Description |
|--------|-------------|
| `wp opcache-toolkit stats clear` | Clear stats table |
| `wp opcache-toolkit stats export --json` | Export stats |

### 🛠 Maintenance
| Command | Description |
|--------|-------------|
| `wp opcache-toolkit log` | Run daily log now |
| `wp opcache-toolkit cleanup` | Run retention cleanup |

### 🧪 JSON Output
Add `--json` to any command:
`wp opcache-toolkit status --json`

### 🧩 Examples
```
wp opcache-toolkit reset
wp opcache-toolkit preload --async
wp opcache-toolkit warmup
wp opcache-toolkit stats export --json > stats.json
wp opcache-toolkit health
```

