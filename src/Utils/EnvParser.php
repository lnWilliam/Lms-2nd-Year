<?php
declare(strict_types=1);
// EnvParser.php
namespace App\Utils;

/**
 * Loads environment variables from the project .env file. This utility keeps database configuration outside source code while making values available to the application.
 *
 * @package App\Utils
 * @author Charlo Marco
 * @since 2026-05-17
 */
class EnvParser
{
    private array $variables = [];
    
    /**
     * Load .env file and parse its contents
     * 
     * @param string $path Path to .env file
     * @throws Exception If file not found
     */
    /**
     * Loads a .env file and registers its values for application configuration.
     *
     * @param mixed $path Path to the .env file.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function load(string $path): self
    {
        if (!file_exists($path)) {
            throw new \Exception(".env file not found at: " . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse variable
            $this->parseLine($line);
        }
        
        return $this;
    }
    
    /**
     * Parse a single line from .env file
     * 
     * @param string $line
     */
    /**
     * Parses one .env line so key/value pairs can be stored and exported.
     *
     * @param mixed $line Line from the .env file.
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    private function parseLine(string $line): void
    {
        // Find first equals sign
        $equalsPos = strpos($line, '=');
        if ($equalsPos === false) {
            return;
        }
        
        // Extract key and value
        $key = trim(substr($line, 0, $equalsPos));
        $value = trim(substr($line, $equalsPos + 1));
        
        // Remove quotes if present
        $value = $this->sanitizeValue($value);
        
        // Store variable
        $this->variables[$key] = $value;
        
        // Set as environment variable
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
    
    /**
     * Sanitize value by removing quotes and processing escape characters
     * 
     * @param string $value
     * @return string
     */
    /**
     * Normalizes an environment value by removing wrapping quotes and decoding escapes.
     *
     * @param mixed $value Environment value to sanitize.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    private function sanitizeValue(string $value): string
    {
        // Remove surrounding quotes
        if (strlen($value) > 1) {
            if (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                ($value[0] === "'" && $value[strlen($value) - 1] === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        
        // Handle escape sequences
        $value = str_replace('\\n', "\n", $value);
        $value = str_replace('\\r', "\r", $value);
        $value = str_replace('\\t', "\t", $value);
        
        return $value;
    }
    
    /**
     * Get a variable value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    /**
     * Reads one environment value with an optional fallback.
     *
     * @param mixed $key Environment variable key to read.
     * @param mixed $default Fallback value returned when the key is missing.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->variables[$key] ?? $default;
    }
    
    /**
     * Get all variables
     * 
     * @return array
     */
    /**
     * Returns all parsed environment variables for configuration inspection.
     *
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function all(): array
    {
        return $this->variables;
    }
}
