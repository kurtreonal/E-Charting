<?php
/**
 * Configuration Loader
 * Loads environment variables from .env file
 * Use this instead of directly accessing $_ENV
 */

class Config {
    private static $config = [];
    private static $loaded = false;

    /**
     * Load configuration from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = dirname(dirname(__FILE__)) . '/.env';
        }

        if (!file_exists($path)) {
            throw new Exception("Configuration file not found: $path");
        }

        // Parse .env file
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos($line, '#') === 0) {
                continue;
            }

            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                self::$config[$key] = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        self::load(); // Auto-load if not already loaded

        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }

        return $default;
    }

    /**
     * Get all configuration
     * @return array Configuration array
     */
    public static function all() {
        self::load();
        return self::$config;
    }

    /**
     * Check if configuration key exists
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public static function has($key) {
        self::load();
        return isset(self::$config[$key]);
    }

    /**
     * Set configuration value (runtime only, not persisted to .env)
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
    }
}

// Load configuration on include
Config::load();
?>
