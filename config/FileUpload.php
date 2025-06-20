<?php

class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $db;
    
    public function __construct($db, $uploadDir = 'uploads/') {
        $this->db = $db;
        $this->uploadDir = $uploadDir;
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->allowedTypes = [
            // Images
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            // Documents
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv',
            // Archives
            'application/zip', 'application/x-rar-compressed'
        ];
        
        // إنشاء مجلد الرفع إذا لم يكن موجوداً
        $this->createUploadDirectory();
    }
    
    private function createUploadDirectory() {
        $dirs = [
            $this->uploadDir,
            $this->uploadDir . 'preparations/',
            $this->uploadDir . 'activities/',
            $this->uploadDir . 'documents/',
            $this->uploadDir . 'images/'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    public function uploadFile($file, $category = 'document', $teacherId = null, $relatedId = null, $relatedType = null) {
        // التحقق من وجود الملف
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('لم يتم رفع الملف أو حدث خطأ في الرفع');
        }
        
        // التحقق من حجم الملف
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت');
        }
        
        // التحقق من نوع الملف
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('نوع الملف غير مسموح');
        }
        
        // إنشاء اسم فريد للملف
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        
        // تحديد المجلد حسب النوع
        $subDir = $this->getSubDirectory($category, $relatedType);
        $filePath = $this->uploadDir . $subDir . $fileName;
        
        // رفع الملف
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('فشل في رفع الملف');
        }
        
        // حفظ معلومات الملف في قاعدة البيانات
        $fileId = $this->saveFileInfo([
            'teacher_id' => $teacherId,
            'file_name' => $fileName,
            'original_name' => $file['name'],
            'file_path' => $filePath,
            'file_type' => $mimeType,
            'file_size' => $file['size'],
            'category' => $category,
            'related_id' => $relatedId,
            'related_type' => $relatedType
        ]);
        
        return [
            'id' => $fileId,
            'file_name' => $fileName,
            'original_name' => $file['name'],
            'file_path' => $filePath,
            'file_size' => $file['size'],
            'file_type' => $mimeType
        ];
    }
    
    private function getSubDirectory($category, $relatedType) {
        if ($relatedType === 'preparation') {
            return 'preparations/';
        } elseif ($relatedType === 'activity') {
            return 'activities/';
        } elseif (strpos($category, 'image') !== false) {
            return 'images/';
        } else {
            return 'documents/';
        }
    }
    
    private function saveFileInfo($fileData) {
        try {
            $stmt = $this->db->query(
                "INSERT INTO files (teacher_id, file_name, original_name, file_path, file_type, file_size, category, uploaded_by, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
                [
                    $fileData['teacher_id'],
                    $fileData['file_name'],
                    $fileData['original_name'],
                    $fileData['file_path'],
                    $fileData['file_type'],
                    $fileData['file_size'],
                    $fileData['category'],
                    $_SESSION['user_id'] ?? $fileData['teacher_id']
                ]
            );
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception('فشل في حفظ معلومات الملف: ' . $e->getMessage());
        }
    }
    
    public function getFilesByRelated($relatedId, $relatedType) {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM files WHERE teacher_id IN (
                    SELECT teacher_id FROM " . ($relatedType === 'preparation' ? 'daily_preparations' : 'activities') . " 
                    WHERE id = ?
                ) ORDER BY created_at DESC",
                [$relatedId]
            );
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function deleteFile($fileId, $teacherId = null) {
        try {
            // جلب معلومات الملف
            $stmt = $this->db->query("SELECT * FROM files WHERE id = ?", [$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                throw new Exception('الملف غير موجود');
            }
            
            // التحقق من الصلاحية
            if ($teacherId && $file['teacher_id'] != $teacherId) {
                throw new Exception('ليس لديك صلاحية لحذف هذا الملف');
            }
            
            // حذف الملف من النظام
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            // حذف السجل من قاعدة البيانات
            $this->db->query("DELETE FROM files WHERE id = ?", [$fileId]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception('فشل في حذف الملف: ' . $e->getMessage());
        }
    }
    
    public function getFileUrl($filePath) {
        return str_replace('\\', '/', $filePath);
    }
    
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    public function getFileIcon($mimeType) {
        if (strpos($mimeType, 'image/') === 0) {
            return 'fas fa-image text-success';
        } elseif (strpos($mimeType, 'pdf') !== false) {
            return 'fas fa-file-pdf text-danger';
        } elseif (strpos($mimeType, 'word') !== false || strpos($mimeType, 'document') !== false) {
            return 'fas fa-file-word text-primary';
        } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'sheet') !== false) {
            return 'fas fa-file-excel text-success';
        } elseif (strpos($mimeType, 'powerpoint') !== false || strpos($mimeType, 'presentation') !== false) {
            return 'fas fa-file-powerpoint text-warning';
        } elseif (strpos($mimeType, 'zip') !== false || strpos($mimeType, 'rar') !== false) {
            return 'fas fa-file-archive text-secondary';
        } else {
            return 'fas fa-file text-muted';
        }
    }
    
    public function isImage($mimeType) {
        return strpos($mimeType, 'image/') === 0;
    }
}
