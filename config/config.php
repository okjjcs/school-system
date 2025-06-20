<?php
// إعدادات النظام العامة
define('APP_NAME', 'نظام أرشفة الأساتذة');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// إعدادات قاعدة البيانات - المسار الصحيح
define('DB_PATH', 'C:\xampp\htdocs\school-system\database\school_archive.db');

// إعدادات الجلسات
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// إعدادات الملفات
define('UPLOAD_PATH', 'C:\xampp\htdocs\school-system\uploads\\');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 ميجابايت
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif']);

// إعدادات الأمان
define('HASH_ALGO', PASSWORD_DEFAULT);
define('CSRF_TOKEN_LENGTH', 32);

// إعدادات التاريخ والوقت
date_default_timezone_set('Asia/Baghdad');

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/database.php';

// تضمين فئة رفع الملفات
require_once __DIR__ . '/FileUpload.php';

// دالة للتحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// دالة للتحقق من صلاحيات المدير
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// دالة للتحقق من صلاحيات المعلم
function isTeacher() {
    return isLoggedIn() && $_SESSION['role'] === 'teacher';
}

// دالة لإعادة التوجيه
function redirect($url) {
    header("Location: $url");
    exit();
}

// دالة لعرض الرسائل
function showMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// دالة لعرض الرسائل المحفوظة
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        
        $alertClass = '';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            default:
                $alertClass = 'alert-info';
        }
        
        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// دالة لتنظيف البيانات
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// دالة لتوليد رمز CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

// دالة للتحقق من رمز CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// دالة لتنسيق التاريخ
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// دالة لتنسيق التاريخ بالعربية
function formatDateArabic($date) {
    if (empty($date)) return '';
    
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

// دالة لرفع الملفات
function uploadFile($file, $category = 'other') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        throw new Exception('لم يتم اختيار ملف');
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileError = $file['error'];
    
    if ($fileError !== UPLOAD_ERR_OK) {
        throw new Exception('خطأ في رفع الملف');
    }
    
    if ($fileSize > MAX_FILE_SIZE) {
        throw new Exception('حجم الملف كبير جداً');
    }
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        throw new Exception('نوع الملف غير مسموح');
    }
    
    $newFileName = uniqid() . '.' . $fileExt;
    $uploadDir = UPLOAD_PATH . $category . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $newFileName;
    
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        throw new Exception('فشل في رفع الملف');
    }
    
    return [
        'file_name' => $newFileName,
        'original_name' => $fileName,
        'file_path' => $uploadPath,
        'file_size' => $fileSize,
        'file_type' => $fileExt
    ];
}

// إنشاء اتصال قاعدة البيانات
try {
    $db = new Database(DB_PATH);
} catch (Exception $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// دوال مساعدة للصفوف والاختصاصات
function getActiveSubjects($db) {
    try {
        $stmt = $db->query("SELECT * FROM subjects WHERE is_active = 1 ORDER BY sort_order, name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getActiveGrades($db) {
    try {
        $stmt = $db->query("SELECT * FROM grades WHERE is_active = 1 ORDER BY level, sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getSubjectById($db, $id) {
    try {
        $stmt = $db->query("SELECT * FROM subjects WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function getGradeById($db, $id) {
    try {
        $stmt = $db->query("SELECT * FROM grades WHERE id = ?", [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function getSubjectByName($db, $name) {
    try {
        $stmt = $db->query("SELECT * FROM subjects WHERE name = ? AND is_active = 1", [$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function getGradeByName($db, $name) {
    try {
        $stmt = $db->query("SELECT * FROM grades WHERE name = ? AND is_active = 1", [$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}
?>
