<?php
declare(strict_types=1); // ADDED: PHP strict types must be the first PHP statement.


namespace App\Utils;

class Upload
{
    private $uploadDir;
    private $maxFileSize;
    private $allowedExts;

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

    public function deleteFile($filePath)
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}