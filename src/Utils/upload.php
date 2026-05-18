<?php
declare(strict_types=1);

namespace App\Utils;

class Upload
{
    private string $uploadDir;
    private int $maxFileSize;
    private array $allowedExts;

    public function __construct()
    {
        $this->uploadDir = "documents/";
        $this->maxFileSize = 10 * 1024 * 1024;

        $this->allowedExts = [
            'pdf',
            'doc',
            'docx',
            'ppt',
            'pptx',
            'xls',
            'xlsx',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'zip',
            'rar',
            'txt'
        ];

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function multiUpload(array $files): array
    {
        $uploadedFiles = [];

        if (!isset($files['name'])) {
            return [];
        }

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $originalName = basename($files['name'][$i]);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];

            if (!$this->isValidFile($fileError, $fileSize, $ext)) {
                continue;
            }

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
            $newFileName = uniqid() . "_" . $safeName;
            $targetPath = $this->uploadDir . $newFileName;

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

    public function hasValidFiles(array $files): bool
    {
        if (!isset($files['name']) || empty($files['name'][0])) {
            return false;
        }

        foreach ($files['name'] as $i => $fileName) {
            $fileError = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $fileSize = $files['size'][$i] ?? 0;
            $ext = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));

            if ($this->isValidFile($fileError, (int) $fileSize, $ext)) {
                return true;
            }
        }

        return false;
    }

    public function uploadSubmissionFiles(array $files, int|string $submission_id): array
    {
        $uploadDir = 'documents/submissions/';
        $publicDir = 'documents/submissions/';
        $uploadedFiles = [];

        if (!$this->hasValidFiles($files)) {
            return [];
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($files['name'] as $i => $fileName) {
            $fileError = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $fileSize = $files['size'][$i] ?? 0;
            $tmp = $files['tmp_name'][$i] ?? '';
            $ext = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));

            if (!$this->isValidFile($fileError, (int) $fileSize, $ext)) {
                continue;
            }

            $safeName = preg_replace(
                '/[^a-zA-Z0-9._-]/',
                '_',
                basename((string) $fileName)
            );

            $newFileName = $submission_id . '_' . time() . '_' . uniqid() . '_' . $safeName;
            $targetPath = $uploadDir . $newFileName;
            $dbPath = $publicDir . $newFileName;

            if (move_uploaded_file($tmp, $targetPath)) {
                $uploadedFiles[] = [
                    'file_name' => $fileName,
                    'file_path' => $dbPath,
                    'file_type' => $ext
                ];
            }
        }

        return $uploadedFiles;
    }

    private function isValidFile(int $fileError, int $fileSize, string $ext): bool
    {
        if ($fileError !== UPLOAD_ERR_OK) {
            return false;
        }

        if (!in_array($ext, $this->allowedExts, true)) {
            return false;
        }

        if ($fileSize > $this->maxFileSize) {
            return false;
        }

        return true;
    }

    private function detectType(string $ext): string
    {
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return 'image';

            case 'pdf':
                return 'pdf';

            case 'doc':
            case 'docx':
                return 'doc';

            case 'xls':
            case 'xlsx':
            case 'csv':
                return 'spreadsheet';

            case 'txt':
                return 'text';

            default:
                return 'other';
        }
    }

    public function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}