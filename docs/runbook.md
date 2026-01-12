# Runbook

This runbook provides guidance for common operational tasks, maintenance, and troubleshooting for the OPcache Toolkit plugin.

## Common Operational Tasks

### Manual OPcache Reset
You can reset the OPcache manually using any of the following methods:
- **Admin UI**: Go to **OPcache Toolkit > Settings > Advanced** and click **Reset OPcache Now**.
- **WP-CLI**: Run the following command:
  ```bash
  wp opcache-toolkit reset
  ```
- **REST API**: Send a POST request to `/wp-json/opcache-toolkit/v1/reset` (requires authentication and valid nonce).

### Triggering Cache Preload
To manually trigger the preloading of PHP files:
- **Admin UI**: Go to **OPcache Toolkit > Settings > Preload** and click **Run Preload Now**.
- **WP-CLI**: Run the following command, specifying the directories to preload:
  ```bash
  wp opcache-toolkit preload /path/to/directory
  ```

### Inspecting Logs
Structured logs are stored in the WordPress uploads directory:
- **Path**: `wp-content/uploads/opcache-toolkit-logs/`
- **Files**:
    - `plugin.log`: PHP-side events, errors, and profiling data.
    - `js.log`: JavaScript-side events and errors.
- **Rotation**: Logs are automatically rotated when they exceed 5MB.

## Maintenance

### Clearing Statistics
If you need to clear the historical performance data:
- **Admin UI**: Go to **OPcache Toolkit > Settings > Advanced** and click **Clear Statistics**.
- **WP-CLI**: (Currently handled via Admin UI, CLI command planned).

### Log Cleanup
Logs are automatically cleaned up based on the **Retention Days** setting (default 90 days). A daily cron job (`opcache_toolkit_daily_stats_cleanup`) handles this.

## Troubleshooting

### OPcache is not enabled
- **Symptom**: Admin dashboard shows "OPcache is not loaded or enabled".
- **Check**: Run `php -i | grep opcache` on the server or check `phpinfo()`.
- **Solution**: Ensure `zend_extension=opcache` is present in your `php.ini` and `opcache.enable=1` (and `opcache.enable_cli=1` for CLI tasks).

### REST API Errors
- **Symptom**: Charts are not loading or buttons in the UI don't respond.
- **Check**: Open browser developer tools and check for 401, 403, or 500 errors on REST API requests.
- **Solution**: Ensure your WordPress site has Permalinks enabled (required for REST API). Check if a security plugin is blocking REST API access.

### Missing Performance Data
- **Symptom**: Graphs show no data points.
- **Check**: Ensure the daily cron job `opcache_toolkit_daily_log` is running. You can use a plugin like WP Crontrol to verify.
- **Solution**: Manually trigger the cron job to see if data starts appearing. Verify that the database table `{prefix}opcache_toolkit_stats` exists.

## WP-CLI Reference

| Command | Description |
|---------|-------------|
| `wp opcache-toolkit info` | Display general OPcache status and memory usage. |
| `wp opcache-toolkit reset` | Clear all entries from OPcache. |
| `wp opcache-toolkit preload <dirs>` | Preload PHP files from specified directories. |
| `wp opcache-toolkit doctor` | Run system diagnostics and health checks. |
