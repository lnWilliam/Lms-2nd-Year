<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Upload;
use PDO;

/**
 * Handles database operations for classes, posts, activities, submissions, and attachments. This model keeps LMS data access in one place so pages and controllers do not directly manage SQL.
 *
 * @package App\Models
 * @author Charlo Marco
 * @since 2026-05-17
 */
class ClassModel
{
    private \PDO $conn;

    /**
     * Initializes the object with the dependencies it needs to perform its responsibility.
     *
     * @param mixed $database Database helper used to obtain the PDO connection.
     * @return void No value is returned.
     *
     */
    public function __construct($database)
    {
        $this->conn = $database->getConnection();
    }

    /**
     * Creates a class and links the creator as teacher inside one transaction.
     *
     * @param mixed $user_id User identifier involved in the operation.
     * @param array $data Associative array of values required by the operation.
     * @return mixed Operation result used by the caller.
     *
     */
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
            $sql2 = 'INSERT INTO Class_User(class_id, user_id, role, status) VALUES
        (:class_id, :user_id, :role, :status)';

            $stmt2 = $this->conn->prepare($sql2);

            $stmt2->execute([
                ':class_id' => $last_id,
                ':user_id' => $user_id,
                ':role'  => "teacher",
                ':status' => "Active"
            ]);

            $this->conn->commit();
            return true;
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("User insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks whether a generated class code is unused before class creation.
     *
     * @param mixed $classCode Class code value to check for uniqueness.
     * @return mixed Operation result used by the caller.
     *
     */
    public function checkClassCodeAvailability($classCode)
    {
        $sql = "SELECT class_id FROM Classes WHERE class_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$classCode]);
        return $stmt->rowCount() === 0;
    }

    /**
     * Retrieves active classes joined by a user so the dashboard can show their courses.
     *
     * @param mixed $user_id User identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getClassesByUser(int|string $user_id): array
    {
        try {
            $sql = "SELECT
            c.class_id,
            c.class_name,
            c.class_desc,
            c.class_code,
            c.created_by,
            c.status,
            cu.role,
            cu.status AS membership_status
        FROM Classes c
        JOIN Class_User cu ON c.class_id = cu.class_id
        WHERE cu.user_id = ?
        AND c.status = 'Active'
        AND cu.status = 'Active'";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$user_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get classes by user error: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Retrieves student members of a class so the teacher can view enrollment.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getStudents($class_id)
    {
        try {
            $sql = "SELECT
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    a.email,
                    cu.role,
                    cu.status AS membership_status
                FROM Users u
                JOIN Class_User cu ON cu.user_id = u.user_id
                JOIN Account a ON a.account_id = u.account_id
                WHERE cu.class_id = :class_id
                AND cu.role = 'student'
                AND cu.status = 'Active'
                ORDER BY u.last_name ASC, u.first_name ASC";

            $statement = $this->conn->prepare($sql);
            $statement->execute([
                ':class_id' => $class_id
            ]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get students error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Removes a student membership from a class when a teacher manages enrollment.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $student_id Student user identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function removeStudentFromClass(int|string $class_id, int|string $student_id): bool
    {
        try {
            $sql = "UPDATE Class_User
                    SET status = 'Inactive'
                    WHERE class_id = :class_id
                    AND user_id = :user_id
                    AND role = 'student'";

            $statement = $this->conn->prepare($sql);
            $statement->execute([
                ':class_id' => $class_id,
                ':user_id' => $student_id
            ]);

            return $statement->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Remove student error: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Adds a student to a class by code while preventing invalid or duplicate membership.
     *
     * @param mixed $user_id User identifier involved in the operation.
     * @param mixed $class_code Class code entered by the user.
     * @return mixed Operation result used by the caller.
     *
     */
    public function joinClassByCode(int|string $user_id, string $class_code): array
    {
        try {
            $this->conn->beginTransaction();

            $sql = "SELECT
                        c.class_id,
                        cu.class_user_id,
                        cu.user_id,
                        cu.role,
                        cu.status AS membership_status
                    FROM Classes c
                    LEFT JOIN Class_User cu
                        ON c.class_id = cu.class_id
                        AND cu.user_id = :user_id
                    WHERE c.class_code = :class_code
                    AND c.status = 'Active'
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':class_code' => $class_code,
                ':user_id' => $user_id
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                $this->conn->rollBack();
                return ["success" => false, "message" => "Invalid class code"];
            }

            $class_id = $result['class_id'];

            if (!empty($result['class_user_id']) && $result['membership_status'] === 'Active') {
                $this->conn->rollBack();
                return [
                    "success" => false,
                    "message" => "Already joined this class"
                ];
            }

            if (!empty($result['class_user_id']) && $result['membership_status'] === 'Inactive') {
                $sql = "UPDATE Class_User
                        SET status = 'Active',
                            join_at = CURRENT_TIMESTAMP
                        WHERE class_user_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$result['class_user_id']]);

                $this->conn->commit();
                return ["success" => true, "message" => "Successfully rejoined class"];
            }

            $sql = "INSERT INTO Class_User (class_id, user_id, role, status)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id, $user_id, "student", "Active"]);

            $this->conn->commit();

            return ["success" => true, "message" => "Successfully joined class"];
        } catch (\PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            error_log("Join class error: " . $e->getMessage());
            return ["success" => false, "message" => "Something went wrong"];
        }
    }

    /**
     * Removes a user from a class membership record.
     *
     * @param mixed $user_id User identifier involved in the operation.
     * @param mixed $class_id Class identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function leaveClass(int|string $user_id, int|string $class_id): bool
    {
        try {
            $sql = "UPDATE Class_User
                    SET status = 'Inactive'
                    WHERE class_id = ?
                    AND user_id = ?
                    AND role = 'student'";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $class_id,
                $user_id
            ]);

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Leave class error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates the shared post record used by announcements and activities.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $postedBy User identifier of the post author.
     * @param mixed $type Post type such as announcement or activity.
     * @param mixed $title Post or class title value.
     * @param mixed $description Text description value.
     * @return mixed Operation result used by the caller.
     *
     */
    public function createPost($class_id, $postedBy, $type, $title, $description)
    {
        try {
            $sql = "INSERT INTO Post 
                    (class_id, postedBy, type, title, description)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $class_id,
                $postedBy,
                $type,
                $title,
                $description
            ]);

            return $this->conn->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Create post error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates an announcement post and optional attachment records as one workflow.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $postedBy User identifier of the post author.
     * @param mixed $title Post or class title value.
     * @param mixed $description Text description value.
     * @param array $files Uploaded files array from the request.
     * @return mixed Operation result used by the caller.
     *
     */
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

    /**
     * Creates an activity post and its activity settings for grading.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $postedBy User identifier of the post author.
     * @param mixed $title Post or class title value.
     * @param mixed $description Text description value.
     * @param mixed $due_date Optional due date for an activity.
     * @param mixed $max_score Maximum score allowed for the activity.
     * @param mixed $allow_late Whether late submissions are allowed.
     * @return mixed Operation result used by the caller.
     *
     */
    public function createActivity($class_id, $postedBy, $title, $description, $due_date, $max_score = 100, $allow_late = false)
    {
        try {
            $post_id = $this->createPost(
                $class_id,
                $postedBy,
                'activity',
                $title,
                $description
            );

            if (!$post_id) {
                return false;
            }

            $sql = "INSERT INTO Activity
                (post_id, due_date, max_score, allow_late)
                VALUES (?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $post_id,
                $due_date,
                $max_score,
                $allow_late
            ]);

            return $post_id;
        } catch (\PDOException $e) {
            error_log("Create activity error: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Creates a material post and its material record for class resources.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $postedBy User identifier of the post author.
     * @param mixed $title Post or class title value.
     * @param mixed $description Text description value.
     * @return mixed Operation result used by the caller.
     *
     */
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
    /**
     * Retrieves the teacher assigned to a class for display in the student list.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getTeacher($class_id)
    {
        try {
            $sql = "SELECT
            u.first_name,
            u.last_name,
            a.email
        FROM Class_User cu
        JOIN Users u ON cu.user_id = u.user_id
        JOIN Account a ON a.account_id = u.account_id
        WHERE cu.class_id = ?
        AND cu.role = 'teacher'
        LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get teacher error: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Retrieves posts and attachment summaries for a class stream.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getClassPosts($class_id)
    {
        try {

            $sql = "
        SELECT
            p.post_id,
            p.type,
            p.title,
            p.description,
            act.due_date,
            p.created_at,
            p.postedBy,
            u.first_name,
            u.last_name,
            act.max_score,

            GROUP_CONCAT(a.file_path SEPARATOR '||') AS file_paths,
            GROUP_CONCAT(a.file_name SEPARATOR '||') AS file_names,
            GROUP_CONCAT(a.attachment_type SEPARATOR '||') AS attachment_types

        FROM Post p

        JOIN Users u ON p.postedBy = u.user_id

        LEFT JOIN Activity act ON act.post_id = p.post_id

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

    /**
     * Deletes a post, related records, and physical attachments to keep storage consistent.
     *
     * @param mixed $post_id Post identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
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

            $this->conn->prepare("DELETE FROM Post WHERE post_id = ?")->execute([$post_id]);

            $this->conn->commit();
            return true;
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Delete post error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stores an attachment record for a post after the file is moved to storage.
     *
     * @param mixed $post_id Post identifier involved in the operation.
     * @param mixed $attachment_type Attachment type or extension to store.
     * @param mixed $file_path Stored file path for the attachment.
     * @param mixed $file_name Original file name for display.
     * @return mixed Operation result used by the caller.
     *
     */
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

    /**
     * Retrieves ownership and type information so pages can authorize edits or deletes.
     *
     * @param mixed $post_id Post identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getPostOwner($post_id)
    {
        try {
            $stmt = $this->conn->prepare("
            SELECT post_id, postedBy, type
            FROM Post
            WHERE post_id = ?
        ");

            $stmt->execute([$post_id]);

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get post owner error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates post content and activity settings while preserving post type rules.
     *
     * @param mixed $post_id Post identifier involved in the operation.
     * @param mixed $title Post or class title value.
     * @param mixed $description Text description value.
     * @param mixed $due_date Optional due date for an activity.
     * @param mixed $max_score Maximum score allowed for the activity.
     * @return mixed Operation result used by the caller.
     *
     */
    public function updatePost($post_id, $title, $description, $due_date = null, $max_score = null)
    {
        try {
            $this->conn->beginTransaction();

            $post = $this->getPostOwner($post_id);

            if (!$post) {
                $this->conn->rollBack();
                return false;
            }

            if ($post['type'] === 'activity') {

                $sql = "UPDATE Post
                    SET title = ?,
                        description = ?
                    WHERE post_id = ?";

                $stmt = $this->conn->prepare($sql);

                $stmt->execute([
                    $title,
                    $description,
                    $post_id
                ]);

                $sql = "UPDATE Activity
                    SET due_date = ?,
                        max_score = ?
                    WHERE post_id = ?";

                $stmt = $this->conn->prepare($sql);

                $stmt->execute([
                    $due_date,
                    $max_score,
                    $post_id
                ]);
            } else {

                $sql = "UPDATE Post
                    SET title = ?,
                        description = ?
                    WHERE post_id = ?";

                $stmt = $this->conn->prepare($sql);

                $stmt->execute([
                    $title,
                    $description,
                    $post_id
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Update post error: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Retrieves activity details by post ID so the activity page can load the activity.
     *
     * @param mixed $post_id Post identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getActivityByPostId($post_id)
    {
        try {
            $sql = "SELECT
                    p.post_id,
                    p.class_id,
                    p.postedBy,
                    p.title,
                    p.description,
                    act.due_date,
                    p.created_at,
                    act.activity_id,
                    act.max_score,
                    act.allow_late
                FROM Post p
                JOIN Activity act ON act.post_id = p.post_id
                WHERE p.post_id = ?
                AND p.type = 'activity'
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$post_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Get activity error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves attachments for a post so pages can display linked files.
     *
     * @param mixed $post_id Post identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getPostAttachments($post_id)
    {
        try {
            $sql = "SELECT attachment_id, attachment_type, file_name, file_path
                FROM Attachment
                WHERE post_id = ?
                ORDER BY attachment_id ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$post_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Get attachments error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves students, submissions, file counts, and grades for teacher grading view.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $activity_id Activity activity identifier.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getActivityGrades(int|string $class_id, int|string $activity_id): array
    {
        try {
            $sql = "SELECT
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    a.email,
                    s.submission_id,
                    s.grade,
                    s.submitted_at,
                    s.status,
                    COUNT(sa.submission_attachment_id) AS submitted_file_count
                FROM Class_User cu
                JOIN Users u ON u.user_id = cu.user_id
                JOIN Account a ON a.account_id = u.account_id
                LEFT JOIN Submission s
                    ON s.user_id = u.user_id
                    AND s.activity_id = :activity_id
                LEFT JOIN Submission_Attachment sa
                    ON sa.submission_id = s.submission_id
                WHERE cu.class_id = :class_id
                AND cu.role = 'student'
                AND cu.status = 'Active'
                GROUP BY u.user_id, u.first_name, u.last_name, a.email, s.submission_id, s.grade, s.submitted_at, s.status
                ORDER BY u.last_name ASC, u.first_name ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':class_id' => $class_id,
                ':activity_id' => $activity_id
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Get activity grades error: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Creates or updates a submission grade for a student in an activity.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $student_id Student user identifier involved in the operation.
     * @param mixed $activity_id Activity activity identifier.
     * @param mixed $grade Grade value to save.
     * @return mixed Operation result used by the caller.
     *
     */
    public function saveStudentGrade(int|string $class_id, int|string $student_id, int|string $activity_id, mixed $grade): bool
    {
        try {
            $sql = "SELECT user_id
                FROM Class_User
                WHERE class_id = ?
                AND user_id = ?
                AND role = 'student'
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id, $student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                return false;
            }

            $sql = "INSERT INTO Submission (user_id, activity_id, grade)
                VALUES (:user_id, :activity_id, :grade)
                ON DUPLICATE KEY UPDATE
                    grade = VALUES(grade)";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':user_id' => $student_id,
                ':activity_id' => $activity_id,
                ':grade' => $grade
            ]);
        } catch (\PDOException $e) {
            error_log('Save grade error: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Retrieves a student submission row for activity status and grade display.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $student_id Student user identifier involved in the operation.
     * @param mixed $activity_id Activity activity identifier.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getStudentSubmission(int|string $class_id, int|string $student_id, int|string $activity_id): array|false
    {
        try {
            $sql = "SELECT
                    s.submission_id,
                    s.grade,
                    s.submitted_at,
                    s.status,
                    u.user_id
                FROM Users u
                JOIN Class_User cu
                    ON cu.user_id = u.user_id
                    AND cu.class_id = :class_id
                    AND cu.role = 'student'
                LEFT JOIN Submission s
                    ON s.user_id = u.user_id
                    AND s.activity_id = :activity_id
                WHERE u.user_id = :student_id
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':class_id' => $class_id,
                ':student_id' => $student_id,
                ':activity_id' => $activity_id
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Get student submission error: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Creates or updates a submission and stores uploaded file records for student work.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $student_id Student user identifier involved in the operation.
     * @param mixed $activity_id Activity activity identifier.
     * @param array $files Uploaded files array from the request.
     * @return mixed Operation result used by the caller.
     *
     */
    public function submitActivityFiles(int|string $class_id, int|string $student_id, int|string $activity_id, array $files): array
    {
        try {
            $uploader = new Upload();

            if (!$uploader->hasValidFiles($files)) {
                return [
                    'success' => false,
                    'message' => 'Please choose at least one valid file.'
                ];
            }

            $sql = "SELECT user_id
                FROM Class_User
                WHERE class_id = ?
                AND user_id = ?
                AND role = 'student'
                AND status = 'Active'
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $class_id,
                $student_id
            ]);

            $classUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$classUser) {
                return [
                    'success' => false,
                    'message' => 'Student is not part of this class.'
                ];
            }

            $this->conn->beginTransaction();

            $sql = "INSERT INTO Submission
                    (user_id, activity_id, submitted_at, status)
                VALUES
                    (:user_id, :activity_id, CURRENT_TIMESTAMP, 'submitted')
                ON DUPLICATE KEY UPDATE
                    submitted_at = CURRENT_TIMESTAMP,
                    status = 'submitted'";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':user_id' => $student_id,
                ':activity_id' => $activity_id
            ]);

            $sql = "SELECT submission_id
                FROM Submission
                WHERE user_id = ?
                AND activity_id = ?
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $student_id,
                $activity_id
            ]);

            $submission = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$submission) {
                if ($this->conn->inTransaction()) {
                    $this->conn->rollBack();
                }

                return [
                    'success' => false,
                    'message' => 'Unable to create submission.'
                ];
            }

            $submission_id = $submission['submission_id'];

            $uploadedFiles = $uploader->uploadSubmissionFiles(
                $files,
                $submission_id
            );

            if (empty($uploadedFiles)) {
                if ($this->conn->inTransaction()) {
                    $this->conn->rollBack();
                }

                return [
                    'success' => false,
                    'message' => 'No valid files were uploaded.'
                ];
            }

            $sql = "INSERT INTO Submission_Attachment
                    (submission_id, file_name, file_path, file_type)
                VALUES
                    (?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            foreach ($uploadedFiles as $file) {
                $stmt->execute([
                    $submission_id,
                    $file['file_name'],
                    $file['file_path'],
                    $file['file_type']
                ]);
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Activity turned in successfully.'
            ];
        } catch (\PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            error_log('Submit activity error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to submit activity.'
            ];
        }
    }


    /**
     * Retrieves uploaded files for a submission so users can view submitted work.
     *
     * @param mixed $submission_id Submission identifier used to find files.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getSubmissionFiles($submission_id)
    {
        try {
            if (!$submission_id) {
                return [];
            }

            $sql = "SELECT submission_attachment_id, file_name, file_path, file_type, uploaded_at
                FROM Submission_Attachment
                WHERE submission_id = ?
                ORDER BY uploaded_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submission_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Get submission files error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Finds a student submission and returns its uploaded files for teacher review.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $student_id Student user identifier involved in the operation.
     * @param mixed $activity_id Activity activity identifier.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getSubmissionFilesByStudent($class_id, $student_id, $activity_id)
    {
        try {
            $submission = $this->getStudentSubmission($class_id, $student_id, $activity_id);

            if (!$submission || empty($submission['submission_id'])) {
                return [];
            }

            return $this->getSubmissionFiles($submission['submission_id']);
        } catch (\PDOException $e) {
            error_log('Get submission files by student error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a class only when the user is its teacher for authorization-sensitive actions.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $user_id User identifier involved in the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function getClassForTeacher($class_id, $user_id)
    {
        try {
            $sql = "SELECT
                    c.class_id,
                    c.class_name,
                    c.class_desc,
                    c.class_code,
                    c.created_by,
                    c.status,
                    cu.role
                FROM Classes c
                JOIN Class_User cu ON cu.class_id = c.class_id
                WHERE c.class_id = ?
                AND cu.user_id = ?
                AND cu.role = 'teacher'
                AND c.status = 'Active'
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $class_id,
                $user_id
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get teacher class error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates class details after confirming the user is authorized as teacher.
     *
     * @param mixed $class_id Class identifier involved in the operation.
     * @param mixed $user_id User identifier involved in the operation.
     * @param array $data Associative array of values required by the operation.
     * @return mixed Operation result used by the caller.
     *
     */
    public function updateClass($class_id, $user_id, $data)
    {
        try {
            $class = $this->getClassForTeacher($class_id, $user_id);

            if (!$class) {
                return [
                    "success" => false,
                    "message" => "Unauthorized."
                ];
            }

            $checkSql = "SELECT class_id
                     FROM Classes
                     WHERE class_code = ?
                     AND class_id != ?
                     LIMIT 1";

            $checkStmt = $this->conn->prepare($checkSql);

            $checkStmt->execute([
                $data['class_code'],
                $class_id
            ]);

            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    "success" => false,
                    "message" => "Class code is already taken."
                ];
            }

            $sql = "UPDATE Classes
                SET class_name = ?,
                    class_desc = ?,
                    class_code = ?
                WHERE class_id = ?";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $data['class_name'],
                $data['class_desc'],
                $data['class_code'],
                $class_id
            ]);

            return [
                "success" => true,
                "message" => "Class updated successfully."
            ];
        } catch (\PDOException $e) {
            error_log("Update class error: " . $e->getMessage());

            return [
                "success" => false,
                "message" => "Something went wrong while updating the class."
            ];
        }
    }

    /**
     * Archives a class so it is hidden without permanently deleting its records.
     *
     * This method first confirms that the current user is a teacher of the class,
     * then marks the class as Inactive. It does not delete the class row, so the
     * class can still appear on the archived classes page.
     *
     * @param int|string $class_id The class ID to archive.
     * @param int|string $user_id The teacher user ID requesting the archive action.
     * @return bool True if the class status was changed to Inactive, false otherwise.
     */
    public function deleteClass(int|string $class_id, int|string $user_id): bool
    {
        try {
            $class = $this->getClassForTeacher($class_id, $user_id);

            if (!$class) {
                return false;
            }

            $sql = "UPDATE Classes
                    SET status = 'Inactive'
                    WHERE class_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$class_id]);

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Archive class error: " . $e->getMessage());
            return false;
        }
    }

    public function unsubmitActivity(int|string $class_id, int|string $student_id, int|string $activity_id): array
    {
        try {
            $this->conn->beginTransaction();

            $sql = "SELECT 
                    s.submission_id
                FROM Submission s
                JOIN Activity act ON act.activity_id = s.activity_id
                JOIN Post p ON p.post_id = act.post_id
                JOIN Class_User cu
                    ON cu.class_id = p.class_id
                    AND cu.user_id = s.user_id
                    AND cu.role = 'student'
                WHERE p.class_id = ?
                AND s.user_id = ?
                AND s.activity_id = ?
                LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $class_id,
                $student_id,
                $activity_id
            ]);

            $submission = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$submission) {
                if ($this->conn->inTransaction()) {
                    $this->conn->rollBack();
                }

                return [
                    'success' => false,
                    'message' => 'No submission found.'
                ];
            }

            $submission_id = $submission['submission_id'];

            $sql = "SELECT file_path
                FROM Submission_Attachment
                WHERE submission_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submission_id]);

            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $uploader = new Upload();

            foreach ($files as $file) {
                if (!empty($file['file_path'])) {
                    $uploader->deleteFile($file['file_path']);
                }
            }

            $sql = "DELETE FROM Submission_Attachment
                WHERE submission_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submission_id]);

            $sql = "UPDATE Submission
                SET status = 'unsubmitted',
                    submitted_at = NULL
                WHERE submission_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submission_id]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Activity unsubmitted successfully.'
            ];
        } catch (\PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            error_log('Unsubmit activity error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to unsubmit activity.'
            ];
        }
    }


    /**
     * Retrieves archived classes joined or created by a user.
     *
     * This method is used by the archive classes page to display classes that
     * are no longer active but still stored in the database for record keeping.
     *
     * @param int|string $user_id The user ID from the Users table.
     * @return array Archived class records connected to the user.
     */
    public function getArchivedClassesByUser(int|string $user_id): array
    {
        try {
            $sql = "SELECT DISTINCT
                        c.class_id,
                        c.class_name,
                        c.class_desc,
                        c.class_code,
                        c.created_by,
                        c.created_at,
                        c.status,
                        COALESCE(cu.role, 'teacher') AS role
                    FROM Classes c
                    LEFT JOIN Class_User cu
                        ON c.class_id = cu.class_id
                        AND cu.user_id = ?
                    WHERE c.status = 'Inactive'
                    AND (
                        cu.user_id = ?
                        OR c.created_by = ?
                    )
                    ORDER BY c.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $user_id,
                $user_id,
                $user_id
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get archived classes error: " . $e->getMessage());
            return [];
        }
    }
}
