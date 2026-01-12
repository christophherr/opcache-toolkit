# OPcache Toolkit REST API Documentation

The OPcache Toolkit provides a structured REST API for managing and monitoring the PHP OPcache. All endpoints are registered under the `opcache-toolkit/v1` namespace.

## Base URL

`https://your-site.com/wp-json/opcache-toolkit/v1`

## Authentication

All endpoints require the user to have the `manage_options` capability (or `manage_network` in Multisite). Additionally, some endpoints require a valid WordPress nonce for security.

- **Nonce Header**: `X-WP-Nonce`

## Endpoints

### 1. Get Status
Retrieves real-time OPcache status and statistics.

- **URL**: `/status`
- **Method**: `GET`
- **Success Response**:
    - **Code**: 200
    - **Content**:
      ```json
      {
        "success": true,
        "data": {
          "opcache_enabled": true,
          "cache_full": false,
        "memory_usage": {
          "used_memory": 64000000,
          "free_memory": 64000000,
          "wasted_memory": 0,
          "current_wasted_percentage": 0
        },
        "opcache_statistics": {
          "num_cached_scripts": 150,
          "num_cached_keys": 200,
          "max_cached_keys": 16229,
          "hits": 1200,
          "start_time": 1700000000,
          "last_restart_time": 0,
          "oom_restarts": 0,
          "hash_restarts": 0,
          "manual_restarts": 0,
          "misses": 5,
          "blacklist_misses": 0,
          "blacklist_miss_ratio": 0,
          "opcache_hit_rate": 99.5
        }
        }
      }
      ```

### 2. Reset Cache
Clears the entire OPcache.

- **URL**: `/reset`
- **Method**: `POST`
- **Required Header**: `X-WP-Nonce`
- **Success Response**:
    - **Code**: 200
    - **Content**:
      ```json
      {
        "success": true,
        "data": {
          "message": "OPcache has been successfully reset."
        }
      }
      ```

### 3. Preload Directories
Recursively compiles PHP files in the specified directories into the OPcache.

- **URL**: `/preload`
- **Method**: `POST`
- **Required Header**: `X-WP-Nonce`
- **Body Parameters**:
    - `directories` (array): List of absolute paths to directories to preload.
- **Success Response**:
    - **Code**: 200
    - **Content**:
      ```json
      {
        "success": true,
        "data": {
          "message": "Successfully preloaded 150 files into OPcache.",
          "data": {
            "compiled_count": 150,
            "failed_files": []
          }
        }
      }
      ```

### 4. Get Chart Data
Retrieves historical performance data for dashboard charts.

- **URL**: `/chart-data`
- **Method**: `GET`
- **Query Parameters**:
    - `limit` (integer, default: 180): Number of data points to retrieve.
- **Success Response**:
    - **Code**: 200
    - **Content**:
      ```json
      {
        "success": true,
        "data": [
          {
            "time": "2026-01-12 12:00:00",
            "hits": 1200,
            "misses": 5
          }
        ]
      }
      ```

### 5. Get Analytics
Provides advanced analytics, including memory predictions and ghost script detection.

- **URL**: `/analytics`
- **Method**: `GET`
- **Success Response**:
    - **Code**: 200
    - **Content**:
      ```json
      {
        "success": true,
        "prediction": {
          "trend": "stable",
          "days_until_full": 45
        },
        "ghosts": [
          "/path/to/deleted/file.php"
        ],
        "groups": {
          "wordpress_core": 450,
          "plugins": 230,
          "themes": 15
        }
      }
      ```

### 6. Client Logging
Receives log entries from the JavaScript frontend and mirrors them to the plugin's structured logs.

- **URL**: `/log`
- **Method**: `POST`
- **Required Header**: `X-WP-Nonce`
- **Body Parameters**:
    - `logs` (array): Array of log objects. Each object should have:
        - `level` (string): Log level (info, error, warning, debug).
        - `message` (string): The message to log.
        - `context` (object): Optional additional data.
- **Success Response**:
    - **Code**: 200
    - **Content**:
      ```json
      {
        "success": true
      }
      ```

## Error Handling

Standard WordPress REST API error responses are used.

- **Example Error Response**:
  ```json
  {
    "code": "opcache_disabled",
    "message": "OPcache is not loaded or enabled on this server.",
    "data": {
      "status": 503
    }
  }
  ```
