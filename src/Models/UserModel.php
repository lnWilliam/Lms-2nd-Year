<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.


namespace App\Models;

use PDO;

class UserModel
{
    private $conn;

    public function __construct($database)
    {
        $this->conn = $database->getConnection();
    }

    public function selectAll()
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

    public function getUser($id)
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

    public function getUserByUsername($username)
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

    public function updateUser($data)
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

    public function insert($data)
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

    public function deleteUser($id)
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

    public function checkUsernameAvailability($username)
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

    public function checkEmailAvailability($email)
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
