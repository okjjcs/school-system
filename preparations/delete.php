<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المعلم
if (!isLoggedIn() || !isTeacher()) {
    redirect('../login.php');
}

// الحصول على معرف التحضير
$prepId = (int)($_GET['id'] ?? 0);
if ($prepId <= 0) {
    showMessage('معرف التحضير غير صحيح', 'error');
    redirect('my.php');
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

// التحقق من وجود التحضير وأنه يخص المعلم الحالي
try {
    $stmt = $db->query("SELECT * FROM daily_preparations WHERE id = ? AND teacher_id = ?", 
                      [$prepId, $currentTeacher['id']]);
    $preparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$preparation) {
        showMessage('لم يتم العثور على التحضير المطلوب أو أنك غير مخول لحذفه', 'error');
        redirect('my.php');
    }
    
    // حذف التحضير
    $stmt = $db->query("DELETE FROM daily_preparations WHERE id = ? AND teacher_id = ?", 
                      [$prepId, $currentTeacher['id']]);
    
    if ($stmt->rowCount() > 0) {
        showMessage('تم حذف التحضير بنجاح', 'success');
    } else {
        showMessage('فشل في حذف التحضير', 'error');
    }
    
} catch (Exception $e) {
    showMessage('خطأ في حذف التحضير: ' . $e->getMessage(), 'error');
}

redirect('my.php');
?>
