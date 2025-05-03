<?php
class FileUploader {
    private $conn;
    private $uploadDir;
    private $maxFileSize;
    private $allowedTypes;
    private $userId;
    private $errors = [];

    public function __construct($conn, $userId) {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->uploadDir = __DIR__ . '/../uploads/';
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->allowedTypes = ['csv', 'xlsx', 'xls'];
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }
    }

    public function upload($file, $month = null) {
        try {
            // Validate file
            $this->validateFile($file);
            
            if (!empty($this->errors)) {
                return [
                    'success' => false,
                    'errors' => $this->errors
                ];
            }

            // Generate unique filename
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uniqueFilename = uniqid('upload_', true) . '.' . $ext;
            $targetPath = $this->uploadDir . $uniqueFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Failed to move uploaded file");
            }

            // Verify file is readable
            if (!is_readable($targetPath)) {
                throw new Exception("Cannot read uploaded file");
            }

            // Record the upload in database
            $stmt = $this->conn->prepare("
                INSERT INTO file_uploads (
                    user_id, 
                    file_name, 
                    file_path, 
                    file_type, 
                    file_size, 
                    status
                ) VALUES (?, ?, ?, ?, ?, 'pending')
            ");

            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $stmt->bind_param(
                'isssi',
                $this->userId,
                $file['name'],
                $uniqueFilename,
                $ext,
                $file['size']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to record file upload: " . $stmt->error);
            }

            $uploadId = $this->conn->insert_id;

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'upload_id' => $uploadId,
                'file_path' => $targetPath
            ];

        } catch (Exception $e) {
            // Clean up the uploaded file if it exists
            if (isset($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $this->errors[] = $upload_errors[$file['error']] ?? 'Unknown upload error';
            return;
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = "File size exceeds the maximum limit of " . ($this->maxFileSize / 1024 / 1024) . "MB";
        }

        // Check file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedTypes)) {
            $this->errors[] = "Invalid file type. Allowed types: " . implode(', ', $this->allowedTypes);
        }
    }

    public function getErrors() {
        return $this->errors;
    }
} 