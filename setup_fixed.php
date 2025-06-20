<?php
require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد نظام أرشفة الأساتذة</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class='bg-light'>
<div class='container mt-5'>
<div class='row justify-content-center'>
<div class='col-md-8'>
<div class='card'>
<div class='card-header bg-primary text-white'>
<h4 class='mb-0'><i class='fas fa-database me-2'></i>إعداد نظام أرشفة الأساتذة</h4>
</div>
<div class='card-body'>";

try {
    // حذف قاعدة البيانات القديمة إذا كانت موجودة
    $dbPath = __DIR__ . '/database/school_archive.db';
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "<div class='alert alert-info'><i class='fas fa-trash me-2'></i>تم حذف قاعدة البيانات القديمة</div>";
    }
    
    // إنشاء مجلد قاعدة البيانات
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
        echo "<div class='alert alert-info'><i class='fas fa-folder me-2'></i>تم إنشاء مجلد قاعدة البيانات</div>";
    }
    
    // إنشاء قاعدة بيانات جديدة
    $db = new Database();
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم إنشاء قاعدة البيانات والجداول بنجاح</div>";
    
    // التحقق من وجود المدير
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        $stmt = $db->query("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", 
                          ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        echo "<div class='alert alert-success'><i class='fas fa-user-shield me-2'></i>تم إنشاء حساب المدير</div>";
    }
    
    // إضافة معلمين تجريبيين
    $teachers = [
        ['teacher1', 'أحمد', 'محمد', 'الرياضيات', 'الصف السادس', 'T001'],
        ['teacher2', 'فاطمة', 'علي', 'اللغة العربية', 'الصف الخامس', 'T002'],
        ['teacher3', 'محمد', 'حسن', 'العلوم', 'الصف الرابع', 'T003'],
        ['teacher4', 'زينب', 'أحمد', 'الاجتماعيات', 'الصف الثالث', 'T004']
    ];
    
    foreach ($teachers as $teacher) {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = ?", [$teacher[0]]);
        $exists = $stmt->fetchColumn();
        
        if ($exists == 0) {
            // إضافة المستخدم
            $stmt = $db->query("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", 
                              [$teacher[0], password_hash('teacher123', PASSWORD_DEFAULT), 'teacher']);
            $userId = $db->lastInsertId();
            
            // إضافة بيانات المعلم
            $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, subject, grade_level, hire_date, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                      [$userId, $teacher[5], $teacher[1], $teacher[2], $teacher[3], $teacher[4], '2020-09-01', $teacher[0] . '@school.edu', '07901234567']);
            
            echo "<div class='alert alert-success'><i class='fas fa-user-plus me-2'></i>تم إضافة المعلم: {$teacher[0]}</div>";
        }
    }
    
    // إضافة بيانات تجريبية للمعلم الأول
    $stmt = $db->query("SELECT t.id FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.username = 'teacher1'");
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        $teacherId = $teacher['id'];
        
        // إضافة مؤهل
        $stmt = $db->query("SELECT COUNT(*) FROM qualifications WHERE teacher_id = ?", [$teacherId]);
        if ($stmt->fetchColumn() == 0) {
            $db->query("INSERT INTO qualifications (teacher_id, degree_type, institution, major, graduation_year, grade) VALUES (?, ?, ?, ?, ?, ?)", 
                      [$teacherId, 'بكالوريوس', 'جامعة بغداد', 'الرياضيات', 2018, 'جيد جداً']);
            echo "<div class='alert alert-info'><i class='fas fa-graduation-cap me-2'></i>تم إضافة مؤهل تجريبي</div>";
        }
        
        // إضافة خبرة
        $stmt = $db->query("SELECT COUNT(*) FROM experiences WHERE teacher_id = ?", [$teacherId]);
        if ($stmt->fetchColumn() == 0) {
            $db->query("INSERT INTO experiences (teacher_id, position, institution, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?)", 
                      [$teacherId, 'معلم رياضيات', 'مدرسة النور الابتدائية', '2018-09-01', '2020-08-31', 'تدريس الرياضيات للصفوف الابتدائية']);
            echo "<div class='alert alert-info'><i class='fas fa-briefcase me-2'></i>تم إضافة خبرة تجريبية</div>";
        }
        
        // إضافة تحضير يومي
        $stmt = $db->query("SELECT COUNT(*) FROM daily_preparations WHERE teacher_id = ?", [$teacherId]);
        if ($stmt->fetchColumn() == 0) {
            $db->query("INSERT INTO daily_preparations (teacher_id, subject, grade, lesson_title, lesson_objectives, lesson_content, teaching_methods, preparation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                      [$teacherId, 'الرياضيات', 'السادس', 'الكسور العادية', 'فهم مفهوم الكسور وطرق التعامل معها', 'شرح الكسور العادية وأنواعها', 'الشرح والأمثلة التطبيقية', date('Y-m-d')]);
            echo "<div class='alert alert-info'><i class='fas fa-book me-2'></i>تم إضافة تحضير يومي تجريبي</div>";
        }
        
        // إضافة نشاط
        $stmt = $db->query("SELECT COUNT(*) FROM activities WHERE teacher_id = ?", [$teacherId]);
        if ($stmt->fetchColumn() == 0) {
            $db->query("INSERT INTO activities (teacher_id, title, description, activity_type, target_grade, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                      [$teacherId, 'مسابقة الرياضيات الذهنية', 'مسابقة في الحساب الذهني للطلاب', 'competition', 'السادس', date('Y-m-d'), 'planned']);
            echo "<div class='alert alert-info'><i class='fas fa-trophy me-2'></i>تم إضافة نشاط تجريبي</div>";
        }
        
        // إضافة متابعة منهج
        $stmt = $db->query("SELECT COUNT(*) FROM curriculum_progress WHERE teacher_id = ?", [$teacherId]);
        if ($stmt->fetchColumn() == 0) {
            $db->query("INSERT INTO curriculum_progress (teacher_id, subject, grade, unit_number, unit_title, total_lessons, completed_lessons, start_date, progress_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                      [$teacherId, 'الرياضيات', 'السادس', 1, 'الأعداد والعمليات', 20, 5, date('Y-m-d'), 25.0]);
            echo "<div class='alert alert-info'><i class='fas fa-tasks me-2'></i>تم إضافة متابعة منهج تجريبية</div>";
        }
    }
    
    echo "<div class='alert alert-success mt-4'>
            <h5><i class='fas fa-check-circle me-2'></i>تم إعداد النظام بنجاح!</h5>
            <hr>
            <h6>بيانات تسجيل الدخول:</h6>
            <div class='row'>
                <div class='col-md-6'>
                    <div class='card border-primary'>
                        <div class='card-header bg-primary text-white'>
                            <i class='fas fa-user-shield me-2'></i>المدير
                        </div>
                        <div class='card-body'>
                            <p class='mb-1'><strong>اسم المستخدم:</strong> admin</p>
                            <p class='mb-0'><strong>كلمة المرور:</strong> admin123</p>
                        </div>
                    </div>
                </div>
                <div class='col-md-6'>
                    <div class='card border-success'>
                        <div class='card-header bg-success text-white'>
                            <i class='fas fa-chalkboard-teacher me-2'></i>المعلم
                        </div>
                        <div class='card-body'>
                            <p class='mb-1'><strong>اسم المستخدم:</strong> teacher1</p>
                            <p class='mb-0'><strong>كلمة المرور:</strong> teacher123</p>
                        </div>
                    </div>
                </div>
            </div>
          </div>";
    
    echo "<div class='text-center mt-4'>
            <a href='login.php' class='btn btn-primary btn-lg me-2'>
                <i class='fas fa-sign-in-alt me-2'></i>
                تسجيل الدخول
            </a>
            <a href='index.php' class='btn btn-success btn-lg'>
                <i class='fas fa-home me-2'></i>
                الصفحة الرئيسية
            </a>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>خطأ: " . $e->getMessage() . "</div>";
    echo "<div class='alert alert-info'>
            <h6>تفاصيل الخطأ للمطور:</h6>
            <pre>" . $e->getTraceAsString() . "</pre>
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
