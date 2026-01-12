# OPcache Toolkit Settings Documentation

This document explains the configuration options available in the OPcache Toolkit plugin and their impact on system performance and monitoring.

## Accessing Settings

- **Single Site**: Navigate to **Settings > OPcache Toolkit**.
- **Multisite**: Navigate to **Network Admin > OPcache Toolkit**.

## General Settings

The General tab contains core configuration for alerts, automation, and data retention.

### Email Alerts

- **Hit Rate Threshold**: The percentage below which the OPcache hit rate must drop to trigger an alert. The default is 90%.
- **Alert Email**: The email address where notifications will be sent. Defaults to the site's administrator email.

### Automatic OPcache Reset

- **Auto Reset**: When enabled, the plugin will automatically clear the OPcache whenever a plugin or theme is updated. This ensures that the latest code changes are immediately reflected.

### Data Retention

- **Retention Days**: The number of days historical performance statistics are kept in the database. Older data is automatically purged to keep the database size manageable. The default is 90 days.

## Preload Settings

The Preload tab allows you to monitor and trigger manual preloading of PHP files.

- **Last Preload Run**: Displays the timestamp of the last time the preload process was executed.
- **Files Compiled**: The total number of files successfully compiled into the OPcache during the last run.
- **Run Preload Now**: Manually triggers the recursive compilation of the WordPress core, plugins, and themes directories.

## Advanced Tools

The Advanced tab provides powerful maintenance tools for troubleshooting and data management.

- **Reset OPcache Now**: Manually clears all cached scripts from memory. Use this if you notice inconsistent behavior after manual code changes.
- **Clear Statistics**: Deletes all historical performance data from the database. This action is irreversible.
- **Export OPcache Statistics**: Downloads a CSV file containing all stored performance data, useful for external analysis.
- **Debug Information**: Displays the current raw OPcache configuration from the server, including memory limits and directive status.
