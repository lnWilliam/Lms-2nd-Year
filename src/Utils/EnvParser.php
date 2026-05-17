<?php
declare(strict_types=1);
// EnvParser.php
namespace App\Utils;

class EnvParser
{
    private $variables = [];
    
    /**
     * Load .env file and parse its contents
     * 
     * @param string $path Path to .env file
     * @throws Exception If file not found
     */
    public function load($path)
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
    private function parseLine($line)
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
    private function sanitizeValue($value)
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
    public function get($key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }
    
    /**
     * Get all variables
     * 
     * @return array
     */
    public function all()
    {
        return $this->variables;
    }
}