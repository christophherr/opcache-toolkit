# OPcache Toolkit How-To Guides

Practical guides and instructions for common tasks using OPcache Toolkit.

## 1. How to Manually Preload PHP Files

Preloading ensures that your code is compiled into the OPcache memory before it's actually requested, improving initial page load times.

### Via WordPress Admin
1. Navigate to **Settings > OPcache Toolkit** (or **Network Admin > OPcache Toolkit**).
2. Click on the **Preload** tab.
3. Click the **Run Preload Now** button.
4. The plugin will recursively compile all `.php` files in the WordPress core, plugins, and themes directories.

### Via WP-CLI
To preload a specific directory (e.g., a specific plugin):
```bash
wp opcache-toolkit preload /path/to/wordpress/wp-content/plugins/your-plugin
```

## 2. How to Reset the OPcache

Use this if you have manually changed PHP files and the changes are not showing up, or if the cache hit rate drops significantly.

### Via WordPress Admin
1. Navigate to **Settings > OPcache Toolkit**.
2. Go to the **Advanced** tab.
3. Click **Reset OPcache Now**.

### Via WP-CLI
```bash
wp opcache-toolkit reset
```

## 3. How to Use WP-CLI for Monitoring

OPcache Toolkit provides several commands for command-line monitoring.

- **Check General Status**:
  ```bash
  wp opcache-toolkit info
  ```
- **Run Health Diagnostics**:
  ```bash
  wp opcache-toolkit doctor
  ```
- **Export Data as JSON**:
  ```bash
  wp opcache-toolkit info --json
  ```

## 4. How to Analyze Plugin Logs

OPcache Toolkit maintains structured logs for troubleshooting.

### Log Location
Logs are stored in the WordPress uploads directory:
`wp-content/uploads/opcache-toolkit-logs/plugin.log`

### Understanding Log Entries
Each entry is formatted as JSON-like structure with metadata:
- **Timestamp**: When the event occurred.
- **Level**: The severity (INFO, WARNING, ERROR, DEBUG).
- **Source**: Whether the log came from PHP or JavaScript (REST API).
- **Context**: Additional data (e.g., file paths, memory usage, user ID).

### Enabling Debug Mode
If you need more detailed logs (including stack traces):
1. Navigate to the **Settings** page.
2. In the **General** tab, scroll to the bottom.
3. Enable **Debug Mode**.
4. Remember to disable it once troubleshooting is complete to save disk space.

## 5. Troubleshooting Common Issues

### "OPcache is not loaded or enabled"
- **Cause**: The PHP `opcache` extension is missing or disabled in `php.ini`.
- **Solution**: Ensure `zend_extension=opcache` is present in your PHP configuration and `opcache.enable=1` is set.

### "Hit rate is consistently low"
- **Cause**: The `opcache.memory_consumption` limit may be too low for your site's size.
- **Solution**: Check the **Advanced > Debug Information** section for your current limit and increase it in `php.ini` if necessary.

### "Charts are not appearing"
- **Cause**: JavaScript files might be blocked, or the REST API is restricted.
- **Solution**: Check the browser console for errors and ensure that the `opcache-toolkit/v1` REST namespace is accessible.
