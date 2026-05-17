<?php
declare(strict_types=1);
namespace App\Helpers;

use RuntimeException;
use App\Utils\EnvParser;

$env = new EnvParser();
$env->load(__DIR__ . '/../../.env');
/**
 * Creates and manages the shared PDO database connection for the application. The class centralizes environment-based configuration and transaction helpers for models.
 *
 * @package App\Helpers
 * @author Charlo Marco
 * @since 2026-05-17
 */
class Database {

    private $conn;
    private $config;
    private static ?Database $instance = null;
    
    /**
     * Initializes the object with the dependencies it needs to perform its responsibility.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }
    
        // Clone prevention
    /**
     * Blocks cloning so the singleton connection manager cannot be duplicated unexpectedly.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    private function __clone() {}
    
    // Wakeup prevention (for unserialization)
    /**
     * Blocks unserialization so the singleton connection manager cannot be duplicated unexpectedly.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function __wakeup() {
        throw new RuntimeException("Cannot unserialize singleton");
    }
    /**
     * Load database configuration from environment
     */
    /**
     * Loads database settings from environment variables so credentials are not hard-coded.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
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
    /**
     * Creates the PDO connection used by the application models.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
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
    /**
     * Returns the shared Database instance so the application uses one connection manager.
     *
     * @return Database Database singleton instance.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    
    /**
     * Returns the active PDO connection for model queries.
     *
     * @return \PDO PDO connection object.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function getConnection(){
        return $this->conn;
    }
    
    /**
     * Returns the last inserted database identifier for follow-up insert operations.
     *
     * @return string Last insert ID as a string.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function lastInsertId(): string {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Starts a database transaction so related queries can succeed or fail together.
     *
     * @return bool True when the transaction starts successfully.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function beginTransaction(): bool {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commits the active database transaction after all related queries succeed.
     *
     * @return bool True when the transaction is committed successfully.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function commit(): bool {
        return $this->conn->commit();
    }
    
    /**
     * Rolls back the active transaction to avoid partial database changes after a failure.
     *
     * @return bool True when the transaction is rolled back successfully.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function rollBack(): bool {
        return $this->conn->rollBack();
    }
}

?>
