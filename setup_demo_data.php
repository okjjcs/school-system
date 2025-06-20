<?php
require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد البيانات التجريبية</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class='bg-light'>
<div class='container mt-5'>
<div class='row justify-content-center'>
<div class='col-md-8'>
<div class='card'>
<div class='card-header bg-primary text-white'>
<h4 class='mb-0'>إعداد البيانات التجريبية</h4>
</div>
<div class='card-body'>";

try {
    // حذف قاعدة البيانات القديمة إذا كانت موجودة
    $dbPath = __DIR__ . '/database/school_archive.db';
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "<div class='alert alert-info'>تم حذف قاعدة البيانات القديمة</div>";
    }

    // إنشاء قاعدة بيانات جديدة
    $db = new Database();
    echo "<div class='alert alert-success'>تم إنشاء قاعدة البيانات الجديدة بنجاح</div>";

    // إنشاء مستخدم معلم تجريبي
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = 'teacher1'");
    $teacherExists = $stmt->fetchColumn();
    
    if ($teacherExists == 0) {
        // إضافة مستخدم معلم
        $stmt = $db->query("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", 
                          ['teacher1', password_hash('teacher123', PASSWORD_DEFAULT), 'teacher']);
        $teacherUserId = $db->lastInsertId();
        
        // إضافة بيانات المعلم
        $stmt = $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, phone, subject, grade_level, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                          [$teacherUserId, 'T001', 'أحمد', 'محمد', 'ahmed@school.edu', '07901234567', 'الرياضيات', 'الصف السادس', '2020-09-01']);
        $teacherId = $db->lastInsertId();
        
        echo "<div class='alert alert-success'>✓ تم إنشاء المعلم التجريبي: teacher1 / teacher123</div>";
        
        // إضافة مؤهلات للمعلم
        $db->query("INSERT INTO qualifications (teacher_id, degree_type, institution, major, graduation_year, grade) VALUES (?, ?, ?, ?, ?, ?)", 
                  [$teacherId, 'بكالوريوس', 'جامعة بغداد', 'الرياضيات', 2018, 'جيد جداً']);
        
        // إضافة خبرة للمعلم
        $db->query("INSERT INTO experiences (teacher_id, position, institution, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?)", 
                  [$teacherId, 'معلم رياضيات', 'مدرسة النور الابتدائية', '2018-09-01', '2020-08-31', 'تدريس الرياضيات للصفوف الابتدائية']);
        
        echo "<div class='alert alert-info'>✓ تم إضافة المؤهلات والخبرات للمعلم</div>";
        
        // إضافة تحضير يومي تجريبي
        $db->query("INSERT INTO daily_preparations (teacher_id, subject, grade, lesson_title, lesson_objectives, lesson_content, teaching_methods, preparation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                  [$teacherId, 'الرياضيات', 'السادس', 'الكسور العادية', 'فهم مفهوم الكسور وطرق التعامل معها', 'شرح الكسور العادية وأنواعها', 'الشرح والأمثلة التطبيقية', date('Y-m-d')]);
        
        echo "<div class='alert alert-info'>✓ تم إضافة تحضير يومي تجريبي</div>";
        
        // إضافة نشاط تجريبي
        $db->query("INSERT INTO activities (teacher_id, title, description, activity_type, target_grade, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                  [$teacherId, 'مسابقة الرياضيات الذهنية', 'مسابقة في الحساب الذهني للطلاب', 'competition', 'السادس', date('Y-m-d'), 'planned']);
        
        echo "<div class='alert alert-info'>✓ تم إضافة نشاط تجريبي</div>";
        
        // إضافة متابعة منهج
        $db->query("INSERT INTO curriculum_progress (teacher_id, subject, grade, unit_number, unit_title, total_lessons, completed_lessons, start_date, progress_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                  [$teacherId, 'الرياضيات', 'السادس', 1, 'الأعداد والعمليات', 20, 5, date('Y-m-d'), 25.0]);
        
        echo "<div class='alert alert-info'>✓ تم إضافة متابعة منهج تجريبية</div>";
        
    } else {
        echo "<div class='alert alert-warning'>المعلم التجريبي موجود بالفعل</div>";
    }
    
    // إضافة معلمين إضافيين
    $teachers = [
        ['teacher2', 'فاطمة', 'علي', 'اللغة العربية', 'الصف الخامس'],
        ['teacher3', 'محمد', 'حسن', 'العلوم', 'الصف الرابع'],
        ['teacher4', 'زينب', 'أحمد', 'الاجتماعيات', 'الصف الثالث']
    ];
    
    foreach ($teachers as $index => $teacher) {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = ?", [$teacher[0]]);
        $exists = $stmt->fetchColumn();
        
        if ($exists == 0) {
            $stmt = $db->query("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", 
                              [$teacher[0], password_hash('teacher123', PASSWORD_DEFAULT), 'teacher']);
            $userId = $db->lastInsertId();
            
            $employeeId = 'T' . str_pad($index + 2, 3, '0', STR_PAD_LEFT);
            $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, phone, subject, grade_level, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                      [$userId, $employeeId, $teacher[1], $teacher[2], $teacher[0] . '@school.edu', '0790123456' . ($index + 1), $teacher[3], $teacher[4], '2020-09-01']);
            
            echo "<div class='alert alert-success'>✓ تم إنشاء المعلم: {$teacher[0]} / teacher123</div>";
        }
    }
    
    // إضافة بعض التنويهات التجريبية
    $stmt = $db->query("SELECT id FROM teachers LIMIT 1");
    $firstTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($firstTeacher) {
        $stmt = $db->query("SELECT COUNT(*) FROM warnings WHERE teacher_id = ?", [$firstTeacher['id']]);
        $warningExists = $stmt->fetchColumn();
        
        if ($warningExists == 0) {
            $stmt = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                $db->query("INSERT INTO warnings (teacher_id, warning_type, title, description, issued_by, issue_date) VALUES (?, ?, ?, ?, ?, ?)", 
                          [$firstTeacher['id'], 'notice', 'تذكير بموعد الاجتماع', 'يرجى حضور اجتماع المعلمين يوم الأحد الساعة 10 صباحاً', $admin['id'], date('Y-m-d')]);
                
                echo "<div class='alert alert-info'>✓ تم إضافة تنويه تجريبي</div>";
            }
        }
    }
    
    echo "<div class='alert alert-success mt-4'>
            <h5>تم إعداد البيانات التجريبية بنجاح!</h5>
            <hr>
            <h6>بيانات تسجيل الدخول:</h6>
            <ul>
                <li><strong>المدير:</strong> admin / admin123</li>
                <li><strong>المعلم 1:</strong> teacher1 / teacher123</li>
                <li><strong>المعلم 2:</strong> teacher2 / teacher123</li>
                <li><strong>المعلم 3:</strong> teacher3 / teacher123</li>
                <li><strong>المعلم 4:</strong> teacher4 / teacher123</li>
            </ul>
          </div>";
    
    echo "<div class='text-center mt-4'>
            <a href='login.php' class='btn btn-primary btn-lg'>
                <i class='fas fa-sign-in-alt me-2'></i>
                الذهاب لتسجيل الدخول
            </a>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>خطأ: " . $e->getMessage() . "</div>";
}

echo "</div>
</div>
</div>
</div>
</div>
<script src='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js'></script>
</body>
</html>";
?>
