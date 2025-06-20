<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

try {
    // إذا كان المستخدم معلماً، تحديث حالة الحضور
    if (isTeacher() && isset($_SESSION['teacher_id'])) {
        $teacherId = $_SESSION['teacher_id'];
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        // تحديث وقت الخروج في جدول الحضور
        $stmt = $db->query("SELECT id FROM attendance WHERE teacher_id = ? AND attendance_date = ?", 
                         [$teacherId, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($attendance) {
            $db->query("UPDATE attendance SET check_out_time = ? WHERE id = ?", 
                     [$currentTime, $attendance['id']]);
        }
        
        // تحديث حالة الحضور في جدول المعلمين
        $db->query("UPDATE teachers SET is_present = 0 WHERE id = ?", [$teacherId]);
    }
    
    // حذف ملف تعريف الارتباط للتذكر
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
} catch (Exception $e) {
    // في حالة حدوث خطأ، سجل الخطأ ولكن لا تمنع تسجيل الخروج
    error_log('Logout error: ' . $e->getMessage());
}

// تدمير الجلسة
session_destroy();

// إعادة بدء الجلسة لعرض رسالة الخروج
session_start();
showMessage('تم تسجيل الخروج بنجاح', 'success');

// إعادة التوجيه إلى صفحة تسجيل الدخول
redirect('login.php');
?>
