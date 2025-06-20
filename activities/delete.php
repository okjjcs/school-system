<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المعلم
if (!isLoggedIn() || !isTeacher()) {
    redirect('../login.php');
}

// الحصول على بيانات المعلم الحالي
$currentTeacher = null;
try {
    $stmt = $db->query("SELECT * FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
    $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentTeacher) {
        showMessage('لم يتم العثور على بيانات المعلم', 'error');
        redirect('../index.php');
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلم', 'error');
    redirect('../index.php');
}

// الحصول على معرف النشاط
$activityId = (int)($_GET['id'] ?? 0);
if ($activityId <= 0) {
    showMessage('معرف النشاط غير صحيح', 'error');
    redirect('my.php');
}

// التحقق من وجود النشاط وأنه خاص بالمعلم الحالي
try {
    $stmt = $db->query("SELECT * FROM activities WHERE id = ? AND teacher_id = ?", [$activityId, $currentTeacher['id']]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        showMessage('النشاط غير موجود أو ليس لديك صلاحية لحذفه', 'error');
        redirect('my.php');
    }
    
    // حذف النشاط
    $stmt = $db->query("DELETE FROM activities WHERE id = ? AND teacher_id = ?", [$activityId, $currentTeacher['id']]);
    
    if ($stmt->rowCount() > 0) {
        showMessage('تم حذف النشاط بنجاح', 'success');
    } else {
        showMessage('لم يتم حذف النشاط', 'error');
    }
    
} catch (Exception $e) {
    showMessage('خطأ في حذف النشاط: ' . $e->getMessage(), 'error');
}

redirect('my.php');
?>
