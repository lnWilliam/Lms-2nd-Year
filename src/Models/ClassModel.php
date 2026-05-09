<?php

namespace App\Models;

use App\Utils\Upload;
use PDO;

class ClassModel
{
    private \PDO $conn;

    public function __construct($database)
    {
        $this->conn = $database->getConnection();
    }

    public function createClass($user_id, $data)
    {
        try {
            $this->conn->beginTransaction();
            $sql = 'INSERT INTO Classes (class_name,class_desc, class_code, created_by)
        VALUES(:class_name,:class_desc,:class_code, :created_by)';
            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':class_name' => $data['class_name'],
                ':class_desc' => $data['class_desc'],
                ':class_code' => $data['class_code'],
                ':created_by'  => $user_id
            ]);
            $last_id = $this->conn->lastInsertId();
            $sql2 = 'INSERT INTO Class_User(class_id,user_id, role) VALUES
        (:class_id, :user_id,:role)';

            $stmt2 = $this->conn->prepare($sql2);

            $stmt2->execute([
                ':class_id' => $last_id,
                ':user_id' => $user_id,
                ':role'  => "teacher"
            ]);

            $this->conn->commit();
            return true;
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("User insert error: " . $e->getMessage());
            return false;
        }
    }

    public function checkClassCodeAvailability($classCode)
    {
        $sql = "SELECT class_id FROM Classes WHERE class_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$classCode]);
        return $stmt->rowCount() === 0;
    }

    public function getClassesByUser($user_id)
    {
        $sql = "SELECT
            c.class_id,
            c.class_name,
            c.class_desc,
            c.class_code,
            c.created_by,
            cu.role
        FROM Classes c
        JOIN Class_User cu ON c.class_id = cu.class_id
        WHERE cu.user_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function joinClassByCode($user_id, $class_code)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Check class + membership in one query
            $sql = "SELECT c.class_id, cu.user_id 
                FROM Classes c
                LEFT JOIN Class_User cu 
                    ON c.class_id = cu.class_id 
                    AND cu.user_id = :user_id
                WHERE c.class_code = :class_code";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':class_code' => $class_code,
                ':user_id' => $user_id
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Class does not exist
            if (!$result) {
                $this->conn->rollBack();
                return ["success" => false, "message" => "Invalid class code"];
            }

            $class_id = $result['class_id'];

            // 3. Already joined
            if ($result['user_id']) {
                $this->conn->rollBack();
                return [
                    "success" => false,
                    "message" => "Already joined this class"
                ];
            }

            // 4. Insert
            $sql = "INSERT INTO Class_User (class_id, user_id, role)
                VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id, $user_id, "student"]);

            $this->conn->commit();

            return ["success" => true, "message" => "Successfully joined class"];
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Join class error: " . $e->getMessage());
            return ["success" => false, "message" => "Something went wrong"];
        }
    }

    public function leaveClass($user_id, $class_id)
    {
        try {
            $sql = "DELETE FROM Class_User WHERE class_id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id, $user_id]);
            return true;
        } catch (\PDOException $e) {
            error_log("Leave class error: " . $e->getMessage());
            return false;
        }
    }

    public function createPost($class_id, $postedBy, $type, $title, $description, $due_date = null)
    {
        try {
            $sql = "INSERT INTO Post 
                    (class_id, postedBy, type, title, description, due_date)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $class_id,
                $postedBy,
                $type,
                $title,
                $description,
                $due_date
            ]);

            return $this->conn->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Create post error: " . $e->getMessage());
            return false;
        }
    }

    public function createAnnouncement($class_id, $postedBy, $title, $description, $files = [])
    {
        try {

            $this->conn->beginTransaction();

            // 1. Create Post first
            $post_id = $this->createPost(
                $class_id,
                $postedBy,
                'announcement',
                $title,
                $description
            );

            if (!$post_id) {
                $this->conn->rollBack();
                return false;
            }

            // 2. Insert Announcement row
            $stmt = $this->conn->prepare("
            INSERT INTO Announcement (post_id)
            VALUES (?)
        ");
            $stmt->execute([$post_id]);

            // 3. Handle attachments
            if (!empty($files['name'][0])) {

                $uploadDir = "documents/";

                foreach ($files['name'] as $i => $fileName) {

                    $tmp = $files['tmp_name'][$i];
                    $error = $files['error'][$i];

                    if ($error !== UPLOAD_ERR_OK) continue;

                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);

                    $newFileName =
                        $post_id . '_' . uniqid() . '_' . $safeName;

                    $targetPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmp, $targetPath)) {

                        $stmt = $this->conn->prepare("
                        INSERT INTO Attachment
                        (post_id, attachment_type, file_path, file_name)
                        VALUES (?, ?, ?, ?)
                    ");

                        $stmt->execute([
                            $post_id,
                            $ext,
                            $targetPath,
                            $fileName
                        ]);
                    }
                }
            }

            $this->conn->commit();
            return $post_id;
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Announcement error: " . $e->getMessage());
            return false;
        }
    }

    public function createAssignment($class_id, $postedBy, $title, $description, $due_date, $max_score = 100, $allow_late = false)
    {
        try {
            $post_id = $this->createPost(
                $class_id,
                $postedBy,
                'assignment',
                $title,
                $description,
                $due_date
            );

            if (!$post_id) {
                return false;
            }

            $sql = "INSERT INTO Activity
                    (post_id, max_score, allow_late)
                    VALUES (?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $post_id,
                $max_score,
                $allow_late
            ]);

            return true;
        } catch (\PDOException $e) {
            error_log("Create assignment error: " . $e->getMessage());
            return false;
        }
    }
    public function createMaterial($class_id, $postedBy, $title, $description)
    {
        try {

            $post_id = $this->createPost(
                $class_id,
                $postedBy,
                'material',
                $title,
                $description
            );

            if (!$post_id) {
                return false;
            }

            $sql = "INSERT INTO Material (post_id)
                    VALUES (?)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([$post_id]);

            return $post_id;
        } catch (\PDOException $e) {

            error_log(
                "Create material error: "
                    . $e->getMessage()
            );

            return false;
        }
    }

    public function getClassPosts($class_id)
    {
        try {

            $sql = "
            SELECT
                p.post_id,
                p.type,
                p.title,
                p.description,
                p.due_date,
                p.created_at,
                p.postedBy,
                u.first_name,
                u.last_name,

                GROUP_CONCAT(a.file_path SEPARATOR '||') AS file_paths,
                GROUP_CONCAT(a.file_name SEPARATOR '||') AS file_names,
                GROUP_CONCAT(a.attachment_type SEPARATOR '||') AS attachment_types

            FROM Post p

            JOIN Users u ON p.postedBy = u.user_id

            LEFT JOIN Attachment a ON p.post_id = a.post_id

            WHERE p.class_id = ?

            GROUP BY p.post_id

            ORDER BY p.created_at DESC
        ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function deletePost($post_id)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Get attachments
            $stmt = $this->conn->prepare("SELECT file_path FROM Attachment WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $uploader = new Upload();

            // 2. Delete physical files
            foreach ($files as $file) {
                $uploader->deleteFile($file['file_path']);
            }

            // 3. Delete DB attachments
            $this->conn->prepare("DELETE FROM Attachment WHERE post_id = ?")->execute([$post_id]);

            // 4. Delete related tables
            $this->conn->prepare("DELETE FROM Material WHERE post_id = ?")->execute([$post_id]);

            $this->conn->prepare("DELETE FROM Announcement WHERE post_id = ?")->execute([$post_id]);

            $this->conn->prepare("DELETE FROM Activity WHERE post_id = ?")->execute([$post_id]);

            // 5. Delete post
            $this->conn->prepare("DELETE FROM Post WHERE post_id = ?")->execute([$post_id]);

            $this->conn->commit();
            return true;
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Delete post error: " . $e->getMessage());
            return false;
        }
    }

    public function addAttachment($post_id, $attachment_type, $file_path, $file_name)
    {
        $sql = "INSERT INTO Attachment
                (post_id, attachment_type, file_path, file_name)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $post_id,
            $attachment_type,
            $file_path,
            $file_name
        ]);
    }

    public function getPostOwner($post_id)
    {
        $stmt = $this->conn->prepare("
        SELECT post_id, postedBy
        FROM Post
        WHERE post_id = ?
    ");

        $stmt->execute([$post_id]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
