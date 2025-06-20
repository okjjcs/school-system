<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// الحصول على معرف المعلم
$teacherId = (int)($_GET['id'] ?? 0);
if ($teacherId <= 0) {
    showMessage('معرف المعلم غير صحيح', 'error');
    redirect('list.php');
}

// جلب بيانات المعلم للتأكد من وجوده
try {
    $stmt = $db->query("SELECT t.*, u.id as user_id, u.username 
                       FROM teachers t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?", [$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        showMessage('لم يتم العثور على المعلم المطلوب', 'error');
        redirect('list.php');
    }
    
    // التحقق من وجود بيانات مرتبطة بالمعلم
    $relatedData = [];

    // قائمة الجداول والأوصاف
    $tablesToCheck = [
        'daily_preparations' => 'تحضير يومي',
        'activities' => 'نشاط ومسابقة',
        'curriculum_progress' => 'وحدة منهج',
        'files' => 'ملف',
        'warnings' => 'تنويه',
        'qualifications' => 'مؤهل',
        'experiences' => 'خبرة'
    ];

    foreach ($tablesToCheck as $table => $description) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM $table WHERE teacher_id = ?", [$teacherId]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData[] = "$count $description";
            }
        } catch (Exception $e) {
            // تجاهل الأخطاء إذا كان الجدول غير موجود
            error_log("تعذر فحص جدول $table: " . $e->getMessage());
        }
    }
    
    // حذف المعلم والبيانات المرتبطة
    $db->getConnection()->beginTransaction();
    
    try {
        // قائمة الجداول المرتبطة للحذف
        $relatedTables = [
            'daily_preparations',
            'activities',
            'curriculum_progress',
            'files',
            'warnings',
            'qualifications',
            'experiences'
        ];

        // حذف البيانات المرتبطة أولاً
        foreach ($relatedTables as $table) {
            try {
                $stmt = $db->query("DELETE FROM $table WHERE teacher_id = ?", [$teacherId]);
                $deletedRows = $stmt->rowCount();
                if ($deletedRows > 0) {
                    error_log("تم حذف $deletedRows سجل من جدول $table للمعلم $teacherId");
                }
            } catch (Exception $e) {
                // تجاهل الأخطاء إذا كان الجدول غير موجود
                error_log("تعذر حذف من جدول $table: " . $e->getMessage());
            }
        }

        // حذف المعلم
        $stmt = $db->query("DELETE FROM teachers WHERE id = ?", [$teacherId]);
        $deletedTeacher = $stmt->rowCount();

        if ($deletedTeacher == 0) {
            throw new Exception("لم يتم حذف المعلم - قد يكون غير موجود");
        }

        // حذف حساب المستخدم إذا كان موجوداً
        if ($teacher['user_id']) {
            $stmt = $db->query("DELETE FROM users WHERE id = ?", [$teacher['user_id']]);
            $deletedUser = $stmt->rowCount();
            error_log("تم حذف $deletedUser مستخدم للمعلم $teacherId");
        }

        $db->getConnection()->commit();
        
        $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];
        $message = "تم حذف المعلم \"$teacherName\" بنجاح";
        
        if (!empty($relatedData)) {
            $message .= " مع البيانات المرتبطة: " . implode(', ', $relatedData);
        }
        
        showMessage($message, 'success');
        redirect('list.php');
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        showMessage('خطأ في حذف المعلم: ' . $e->getMessage(), 'error');
        redirect('list.php');
    }
    
} catch (Exception $e) {
    showMessage('خطأ في الوصول لبيانات المعلم: ' . $e->getMessage(), 'error');
    redirect('list.php');
}
?>
