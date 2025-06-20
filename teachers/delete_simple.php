<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    showMessage('يجب تسجيل الدخول كمدير', 'error');
    redirect('../login.php');
}

// الحصول على معرف المعلم
$teacherId = (int)($_GET['id'] ?? 0);
if ($teacherId <= 0) {
    showMessage('معرف المعلم غير صحيح', 'error');
    redirect('list.php');
}

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>حذف المعلم - اختبار</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class='bg-light'>
<div class='container mt-5'>
<div class='row justify-content-center'>
<div class='col-md-8'>
<div class='card'>
<div class='card-header bg-danger text-white'>
<h4 class='mb-0'>اختبار حذف المعلم</h4>
</div>
<div class='card-body'>";

echo "<div class='alert alert-info'>
<strong>معرف المعلم المراد حذفه:</strong> $teacherId
</div>";

try {
    // جلب بيانات المعلم
    $stmt = $db->query("SELECT t.*, u.id as user_id, u.username 
                       FROM teachers t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?", [$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        echo "<div class='alert alert-danger'>المعلم غير موجود!</div>";
        echo "<a href='list.php' class='btn btn-secondary'>العودة للقائمة</a>";
    } else {
        $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];
        
        echo "<div class='alert alert-warning'>
        <h5>بيانات المعلم:</h5>
        <ul>
        <li><strong>الاسم:</strong> $teacherName</li>
        <li><strong>رقم الموظف:</strong> {$teacher['employee_id']}</li>
        <li><strong>المادة:</strong> {$teacher['subject']}</li>
        <li><strong>حساب المستخدم:</strong> " . ($teacher['user_id'] ? "موجود (ID: {$teacher['user_id']})" : "غير موجود") . "</li>
        </ul>
        </div>";
        
        // فحص البيانات المرتبطة
        echo "<h6>البيانات المرتبطة:</h6>";
        $relatedTables = [
            'daily_preparations' => 'التحضيرات اليومية',
            'activities' => 'الأنشطة والمسابقات',
            'curriculum_progress' => 'تقدم المنهج',
            'files' => 'الملفات',
            'warnings' => 'التنويهات',
            'qualifications' => 'المؤهلات',
            'experiences' => 'الخبرات'
        ];
        
        $relatedData = [];
        foreach ($relatedTables as $table => $description) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM $table WHERE teacher_id = ?", [$teacherId]);
                $count = $stmt->fetchColumn();
                if ($count > 0) {
                    $relatedData[] = "$count $description";
                    echo "<div class='alert alert-warning alert-sm'>$description: $count سجل</div>";
                } else {
                    echo "<div class='alert alert-success alert-sm'>$description: لا توجد بيانات</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-danger alert-sm'>$description: خطأ - " . $e->getMessage() . "</div>";
            }
        }
        
        // معالجة الحذف
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
            echo "<h6 class='text-danger mt-3'>تنفيذ الحذف:</h6>";
            
            try {
                $db->getConnection()->beginTransaction();
                
                $deletedCounts = [];
                
                // حذف البيانات المرتبطة
                foreach (array_keys($relatedTables) as $table) {
                    try {
                        $stmt = $db->query("DELETE FROM $table WHERE teacher_id = ?", [$teacherId]);
                        $deletedRows = $stmt->rowCount();
                        if ($deletedRows > 0) {
                            $deletedCounts[] = "$deletedRows من $table";
                            echo "<div class='alert alert-info alert-sm'>تم حذف $deletedRows سجل من جدول $table</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='alert alert-warning alert-sm'>تعذر حذف من جدول $table: " . $e->getMessage() . "</div>";
                    }
                }
                
                // حذف المعلم
                $stmt = $db->query("DELETE FROM teachers WHERE id = ?", [$teacherId]);
                $deletedTeacher = $stmt->rowCount();
                echo "<div class='alert alert-info alert-sm'>تم حذف $deletedTeacher معلم من جدول teachers</div>";
                
                // حذف حساب المستخدم
                if ($teacher['user_id']) {
                    $stmt = $db->query("DELETE FROM users WHERE id = ?", [$teacher['user_id']]);
                    $deletedUser = $stmt->rowCount();
                    echo "<div class='alert alert-info alert-sm'>تم حذف $deletedUser مستخدم من جدول users</div>";
                }
                
                $db->getConnection()->commit();
                
                echo "<div class='alert alert-success'>
                <h5><i class='fas fa-check'></i> تم حذف المعلم بنجاح!</h5>
                <p>تم حذف المعلم \"$teacherName\" وجميع البيانات المرتبطة به.</p>";
                
                if (!empty($deletedCounts)) {
                    echo "<p><strong>البيانات المحذوفة:</strong> " . implode(', ', $deletedCounts) . "</p>";
                }
                
                echo "<a href='list.php' class='btn btn-primary'>العودة لقائمة المعلمين</a>
                </div>";
                
            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                echo "<div class='alert alert-danger'>
                <h5>فشل في حذف المعلم!</h5>
                <p>خطأ: " . $e->getMessage() . "</p>
                <a href='list.php' class='btn btn-secondary'>العودة للقائمة</a>
                </div>";
            }
        } else {
            // عرض نموذج التأكيد
            echo "<form method='POST' class='mt-4'>
            <div class='alert alert-danger'>
            <h5>تحذير!</h5>
            <p>هل أنت متأكد من حذف المعلم \"$teacherName\"؟</p>
            <p>سيتم حذف جميع البيانات المرتبطة به نهائياً.</p>";
            
            if (!empty($relatedData)) {
                echo "<p><strong>البيانات التي ستحذف:</strong> " . implode(', ', $relatedData) . "</p>";
            }
            
            echo "</div>
            
            <div class='d-flex justify-content-between'>
            <button type='submit' name='confirm_delete' value='1' class='btn btn-danger btn-lg'>
                نعم، احذف المعلم نهائياً
            </button>
            <a href='list.php' class='btn btn-secondary btn-lg'>إلغاء</a>
            </div>
            </form>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
    <h5>خطأ في الوصول لبيانات المعلم!</h5>
    <p>" . $e->getMessage() . "</p>
    <a href='list.php' class='btn btn-secondary'>العودة للقائمة</a>
    </div>";
}

echo "</div>
</div>
</div>
</div>
</div>
</body>
</html>";
?>
