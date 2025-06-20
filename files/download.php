<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// الحصول على معرف الملف
$fileId = (int)($_GET['id'] ?? 0);
if ($fileId <= 0) {
    showMessage('معرف الملف غير صحيح', 'error');
    redirect('my.php');
}

// جلب معلومات الملف
try {
    $sql = "SELECT f.*, t.first_name, t.last_name 
            FROM files f 
            JOIN teachers t ON f.teacher_id = t.id 
            WHERE f.id = ?";
    
    // إذا كان المستخدم معلماً، تأكد من أن الملف خاص به
    if (isTeacher()) {
        $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentTeacher) {
            $sql .= " AND f.teacher_id = ?";
            $stmt = $db->query($sql, [$fileId, $currentTeacher['id']]);
        } else {
            throw new Exception('لم يتم العثور على بيانات المعلم');
        }
    } else {
        $stmt = $db->query($sql, [$fileId]);
    }
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        showMessage('لم يتم العثور على الملف المطلوب', 'error');
        redirect('my.php');
    }
    
    // التحقق من وجود الملف في النظام
    if (!file_exists($file['file_path'])) {
        showMessage('الملف غير موجود في النظام', 'error');
        redirect('my.php');
    }
    
    // إعداد headers للتحميل
    header('Content-Type: ' . $file['file_type']);
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // قراءة وإرسال الملف
    readfile($file['file_path']);
    exit;
    
} catch (Exception $e) {
    showMessage('خطأ في تحميل الملف: ' . $e->getMessage(), 'error');
    redirect('my.php');
}
?>
