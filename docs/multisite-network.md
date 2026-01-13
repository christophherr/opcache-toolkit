# OPcache Toolkit in WordPress Multisite

OPcache Toolkit is fully compatible with WordPress Multisite (Network) environments. This document explains how the plugin behaves when activated across a network.

## Network Activation vs. Single Site Activation

While the PHP OPcache itself is shared across the entire server (and thus all sites in the network), OPcache Toolkit allows for centralized management via the Network Admin.

### Network Activation (Recommended)
When the plugin is Network Activated:
- A new **OPcache Toolkit** menu appears in the **Network Admin**.
- Configuration settings are stored globally as **Site Options**.
- Only Network Administrators (`manage_network` capability) can access the dashboard and change settings.
- The dashboard shows statistics for the entire server's OPcache, which includes data from all sites in the network.

### Single Site Activation
If the plugin is activated on a single site within a multisite network:
- The menu appears under **Settings > OPcache Toolkit** for that specific site.
- Settings are stored as local **Options** for that site only.
- Local Site Administrators (`manage_options` capability) can access the plugin.
- **Note**: Since OPcache is server-wide, the "Reset OPcache" action will clear the cache for the entire server, affecting all other sites in the network.

## Shared Resource Management

Because PHP OPcache is a shared resource, certain actions have network-wide implications:

- **Resetting the Cache**: Clearing the OPcache via the dashboard or REST API will clear it for all sites sharing that PHP instance.
- **Preloading**: Preloading compiles files into the shared cache. It is recommended to perform preloading from the Network Admin to ensure all relevant core and plugin files are covered.
- **Statistics**: Performance metrics (hit rate, memory usage) are reported at the PHP process level. In most Multisite configurations, this means the dashboard displays the aggregate performance of the entire network.

## Configuration in Multisite

In a Network-Activated setup, the following settings are managed centrally:

- **Email Alerts**: Alert emails are sent to the network administrator's address by default.
- **Auto-Reset**: When enabled, the OPcache is reset when any plugin or theme is updated across the network.
- **Data Retention**: The historical data for the entire network's performance is stored in a single set of tables (using the base network prefix).

## Permissions and Security

- **Access Control**: The plugin strictly enforces the `manage_network` capability in Multisite mode to prevent unauthorized cache manipulation by individual site owners.
- **REST API**: API endpoints in a network context require the same elevated permissions.
