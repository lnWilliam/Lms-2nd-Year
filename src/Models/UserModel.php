<?php
declare(strict_types=1);
namespace App\Models;

use App\Helpers\Database;
use PDO;

/**
 * Handles database operations for user account records and profile records. This model keeps account queries separate from controllers so registration and login use a consistent data layer.
 *
 * @package App\Models
 * @author Charlo Marco
 * @since 2026-05-17
 */
class UserModel
{
    private PDO $conn;

    /**
     * Initializes the object with the dependencies it needs to perform its responsibility.
     *
     * @param mixed $database Database helper used to obtain the PDO connection.
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    /**
     * Retrieves all account records for administrative listing or debugging use.
     *
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function selectAll(): array
    {
        try {
            $sql = "SELECT * FROM Account ORDER BY account_id DESC";
            $result = $this->conn->query($sql);

            $users = [];

            if ($result && $result->rowCount() > 0) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $users[] = $row;
                }
            }

            return $users;
        } catch (\PDOException $e) {
            error_log("Select all users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves one account by account ID so callers can load a specific user record.
     *
     * @param mixed $id Account identifier.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function getUser(int|string $id): array|false
    {
        try {
            $sql = "SELECT * FROM Account WHERE account_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves account and profile data by username for login verification.
     *
     * @param mixed $username Username value to check or validate.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function getUserByUsername(string $username): array|false
    {
        try {
            $sql = "SELECT
                        a.account_id,
                        a.username,
                        a.email,
                        a.password,
                        u.user_id,
                        u.first_name,
                        u.last_name
                    FROM Account a
                    JOIN Users u ON a.account_id = u.account_id
                    WHERE a.username = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get user by username error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates basic account fields so profile changes are saved consistently.
     *
     * @param array $data Associative array of values required by the operation.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function updateUser(array $data): bool
    {
        try {
            $sql = "UPDATE Account
                    SET username = ?, email = ?
                    WHERE account_id = ?";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                $data['username'],
                $data['email'],
                $data['account_id']
            ]);
        } catch (\PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates both the Account and Users records inside one transaction so registration stays consistent.
     *
     * @param array $data Associative array of values required by the operation.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function insert(array $data): bool
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO Account (username, password, email)
                    VALUES (:username, :password, :email)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':username' => $data['username'],
                ':password' => $data['password'],
                ':email'    => $data['email']
            ]);

            $last_id = $this->conn->lastInsertId();

            $sql = "INSERT INTO Users (account_id, first_name, last_name)
                    VALUES (:account_id, :first_name, :last_name)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':account_id' => $last_id,
                ':first_name' => $data['first_name'],
                ':last_name'  => $data['last_name']
            ]);

            $this->conn->commit();

            return true;
        } catch (\PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            error_log("User insert error: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Deletes an account record so related profile data can cascade according to the database schema.
     *
     * @param mixed $id Account identifier.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function deleteUser(int|string $id): bool
    {
        try {
            $sql = "DELETE FROM Account WHERE account_id = ?";
            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks whether a username is unused before registration accepts it.
     *
     * @param mixed $username Username value to check or validate.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function checkUsernameAvailability(string $username): bool
    {
        try {
            $sql = "SELECT account_id FROM Account WHERE username = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);

            return $stmt->rowCount() === 0;
        } catch (\PDOException $e) {
            error_log("Check username availability error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks whether an email address is unused before registration accepts it.
     *
     * @param mixed $email Email address value to check or validate.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function checkEmailAvailability(string $email): bool
    {
        try {
            $sql = "SELECT account_id FROM Account WHERE email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$email]);

            return $stmt->rowCount() === 0;
        } catch (\PDOException $e) {
            error_log("Check email availability error: " . $e->getMessage());
            return false;
        }
    }
}
