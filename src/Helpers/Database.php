<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.


namespace App\Helpers;

use RuntimeException;
use App\Utils\EnvParser;

$env = new EnvParser();
$env->load(__DIR__ . '/../../.env');
class Database {

    private $conn;
    private $config;
    private static ?Database $instance = null;
    
    public function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }
    
        // Clone prevention
    private function __clone() {}
    
    // Wakeup prevention (for unserialization)
    public function __wakeup() {
        throw new RuntimeException("Cannot unserialize singleton");
    }
    /**
     * Load database configuration from environment
     */
    private function loadConfig()
    {
        $this->config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
            'driver' => getenv('DB_DRIVER') ?: 'mysql'
        ];
        
        // Validate required fields
        if (!$this->config['name'] || !$this->config['user']) {
            throw new \Exception("Database name and user are required in .env file");
        }
    }
    private function connect()
    {
        try {
            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['driver'],
                $this->config['host'],
                $this->config['port'],
                $this->config['name'],
                $this->config['charset']
            );
            
            $this->conn = new \PDO(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Get the single instance
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    
    public function getConnection(){
        return $this->conn;
    }
    
    public function lastInsertId(): string {
        return $this->conn->lastInsertId();
    }
    
    public function beginTransaction(): bool {
        return $this->conn->beginTransaction();
    }
    
    public function commit(): bool {
        return $this->conn->commit();
    }
    
    public function rollBack(): bool {
        return $this->conn->rollBack();
    }
}

?>
