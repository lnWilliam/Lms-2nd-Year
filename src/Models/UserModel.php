<?php

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
        $sql = "SELECT * FROM account ORDER BY id DESC";
        $result = $this->conn->query($sql);
        $users = [];

        if ($result && $result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $row;
            }
        }

        return $users;
    }

    public function getUser($id)
    {
        $sql = "SELECT * FROM account WHERE account_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

   public function getUserByUsername($username)
{
    $sql = "SELECT 
                a.account_id, 
                a.username, 
                a.email,
                a.password, 
                u.user_id, 
                u.first_name, 
                u.last_name
            FROM account a
            JOIN Users u ON a.account_id = u.account_id
            WHERE a.username = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function updateUser($data)
    {
        $sql = "UPDATE account SET username = ?, email = ? WHERE account_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['username'],
            $data['email'],
            $data['account_id']
        ]);
    }

    public function insert($data)
    {
        try {
            $this->conn->beginTransaction();

            // Insert into account
            $sql = "INSERT INTO account (username, password, email) 
                    VALUES (:username, :password, :email)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':username' => $data['username'],
                ':password' => $data['password'],
                ':email'    => $data['email']
            ]);

            $last_id = $this->conn->lastInsertId();

            // Insert into user
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
            $this->conn->rollBack();
            error_log("User insert error: " . $e->getMessage());
            return false;
        }
    }

    

    public function deleteUser($id)
    {
        $sql = "DELETE FROM Account WHERE account_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function checkUsernameAvailability($username)
    {
        $sql = "SELECT account_id FROM Account WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->rowCount() === 0;
    }

    public function checkEmailAvailability($email)
    {
        $sql = "SELECT account_id FROM Account WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() === 0;
    }
    
    
}
