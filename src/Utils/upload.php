<?php
namespace App\Utils;

/**
 * Handles file upload validation, storage naming, and file deletion. This utility keeps filesystem operations reusable and separate from page logic.
 *
 * @package App\Utils
 * @author Charlo Marco
 * @since 2026-05-17
 */
class Upload
{
    private $uploadDir;
    private $maxFileSize;
    private $allowedExts;

    /**
     * Initializes the object with the dependencies it needs to perform its responsibility.
     *
     * @return void No value is returned.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function __construct()
    {
        $this->uploadDir = "documents/";
        $this->maxFileSize = 10 * 1024 * 1024;

        $this->allowedExts = [
            'pdf',
            'docx',
            'jpg',
            'jpeg',
            'png'
        ];

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Validates and moves multiple post attachment files into storage.
     *
     * @param array $files Uploaded files array from the request.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function multiUpload($files)
    {
        $uploadedFiles = [];

        if (!isset($files['name'])) {
            return [];
        }

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {

            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $originalName = basename($files['name'][$i]);

            $ext = strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            );

            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];

            // Validation

            if ($fileError !== UPLOAD_ERR_OK) {
                continue;
            }

            if (!in_array($ext, $this->allowedExts)) {
                continue;
            }

            if ($fileSize > $this->maxFileSize) {
                continue;
            }

            // Unique filename

            $newFileName =
                uniqid() . "_" .
                preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);

            $targetPath = $this->uploadDir . $newFileName;

            // Move file

            if (move_uploaded_file($tmpName, $targetPath)) {

                $uploadedFiles[] = [
                    'file_name' => $originalName,
                    'file_path' => $targetPath,
                    'attachment_type' => $this->detectType($ext)
                ];
            }
        }

        return $uploadedFiles;
    }

    /**
     * Maps a file extension to a general attachment type for display and storage.
     *
     * @param mixed $ext File extension to classify.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    private function detectType($ext)
    {
        switch ($ext) {

            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'image';

            case 'pdf':
                return 'pdf';

            case 'doc':
            case 'docx':
                return 'doc';

            default:
                return 'other';
        }
    }

    /**
     * Deletes a physical file from storage when a post or submission is removed.
     *
     * @param mixed $filePath Physical path of the file to delete.
     * @return mixed Operation result used by the caller.
     * @throws \Throwable If an unexpected runtime error occurs while the method is running.
     */
    public function deleteFile($filePath)
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}
