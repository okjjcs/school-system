<?php
require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>فحص النظام - نظام أرشفة الأساتذة</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class='bg-light'>
<div class='container mt-5'>
<div class='row justify-content-center'>
<div class='col-md-12'>
<div class='card'>
<div class='card-header bg-primary text-white'>
<h4 class='mb-0'><i class='fas fa-check-circle me-2'></i>فحص شامل لنظام أرشفة الأساتذة</h4>
</div>
<div class='card-body'>";

$verificationResults = [];
$overallStatus = true;

// 1. فحص قاعدة البيانات والجداول
echo "<h5 class='text-primary'><i class='fas fa-database me-2'></i>فحص قاعدة البيانات</h5>";

try {
    $requiredTables = [
        'users', 'teachers', 'daily_preparations', 'activities', 
        'curriculum_progress', 'warnings', 'attendance', 'files'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>جدول $table: $count سجل</div>";
            $verificationResults[$table] = ['status' => 'success', 'count' => $count];
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في جدول $table: " . $e->getMessage() . "</div>";
            $verificationResults[$table] = ['status' => 'error', 'message' => $e->getMessage()];
            $overallStatus = false;
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</div>";
    $overallStatus = false;
}

// 2. فحص الملفات الأساسية
echo "<h5 class='text-primary mt-4'><i class='fas fa-file-code me-2'></i>فحص الملفات الأساسية</h5>";

$requiredFiles = [
    'index.php' => 'الصفحة الرئيسية',
    'login.php' => 'صفحة تسجيل الدخول',
    'logout.php' => 'صفحة تسجيل الخروج',
    'profile.php' => 'الملف الشخصي',
    'config/config.php' => 'ملف الإعدادات',
    'config/database.php' => 'إعدادات قاعدة البيانات',
    'assets/css/style.css' => 'ملف الأنماط',
    'assets/js/main.js' => 'ملف JavaScript'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>$description ($file): " . number_format($size) . " بايت</div>";
    } else {
        echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>ملف مفقود: $description ($file)</div>";
        $overallStatus = false;
    }
}

// 3. فحص مجلدات النظام
echo "<h5 class='text-primary mt-4'><i class='fas fa-folder me-2'></i>فحص المجلدات</h5>";

$requiredDirectories = [
    'teachers' => 'إدارة المعلمين',
    'preparations' => 'التحضير اليومي',
    'activities' => 'الأنشطة والمسابقات',
    'curriculum' => 'متابعة المنهج',
    'warnings' => 'التنويهات والإنذارات',
    'attendance' => 'الحضور والغياب',
    'files' => 'إدارة الملفات',
    'uploads' => 'الملفات المرفوعة',
    'assets' => 'الموارد',
    'config' => 'الإعدادات'
];

foreach ($requiredDirectories as $dir => $description) {
    if (is_dir($dir)) {
        $fileCount = count(glob($dir . '/*.php'));
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>$description ($dir): $fileCount ملف PHP</div>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>مجلد مفقود: $description ($dir)</div>";
    }
}

// 4. فحص صفحات النظام الرئيسية
echo "<h5 class='text-primary mt-4'><i class='fas fa-globe me-2'></i>فحص صفحات النظام</h5>";

$systemPages = [
    'teachers/list.php' => 'قائمة المعلمين',
    'teachers/add.php' => 'إضافة معلم',
    'preparations/my.php' => 'التحضير اليومي',
    'activities/my.php' => 'الأنشطة والمسابقات',
    'curriculum/my.php' => 'متابعة المنهج',
    'warnings/my.php' => 'التنويهات والإنذارات',
    'attendance/today.php' => 'حضور اليوم',
    'files/my.php' => 'إدارة الملفات'
];

foreach ($systemPages as $page => $description) {
    if (file_exists($page)) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>$description ($page): متوفر</div>";
    } else {
        echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>صفحة مفقودة: $description ($page)</div>";
        $overallStatus = false;
    }
}

// 5. فحص البيانات التجريبية
echo "<h5 class='text-primary mt-4'><i class='fas fa-data me-2'></i>فحص البيانات التجريبية</h5>";

try {
    // فحص المستخدمين
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
    $teacherUserCount = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM teachers");
    $teacherCount = $stmt->fetchColumn();
    
    echo "<div class='alert alert-info'>";
    echo "<h6><i class='fas fa-users me-2'></i>إحصائيات المستخدمين:</h6>";
    echo "<ul class='mb-0'>";
    echo "<li>المديرون: $adminCount</li>";
    echo "<li>حسابات المعلمين: $teacherUserCount</li>";
    echo "<li>ملفات المعلمين: $teacherCount</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($adminCount == 0) {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>لا يوجد حساب مدير في النظام</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في فحص البيانات: " . $e->getMessage() . "</div>";
}

// 6. فحص الصلاحيات
echo "<h5 class='text-primary mt-4'><i class='fas fa-shield-alt me-2'></i>فحص الصلاحيات</h5>";

$uploadDir = 'uploads';
if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>مجلد الرفع قابل للكتابة</div>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>مجلد الرفع غير قابل للكتابة</div>";
    }
} else {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>مجلد الرفع غير موجود</div>";
}

// 7. النتيجة النهائية
echo "<div class='mt-4'>";
if ($overallStatus) {
    echo "<div class='alert alert-success alert-lg'>";
    echo "<h4><i class='fas fa-check-circle me-2'></i>تم فحص النظام بنجاح!</h4>";
    echo "<hr>";
    echo "<p class='mb-0'>جميع المكونات الأساسية للنظام تعمل بشكل صحيح. النظام جاهز للاستخدام.</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning alert-lg'>";
    echo "<h4><i class='fas fa-exclamation-triangle me-2'></i>تم العثور على بعض المشاكل</h4>";
    echo "<hr>";
    echo "<p class='mb-0'>يرجى مراجعة الأخطاء المذكورة أعلاه وإصلاحها قبل استخدام النظام.</p>";
    echo "</div>";
}

// 8. روابط سريعة للاختبار
echo "<div class='alert alert-primary'>";
echo "<h5><i class='fas fa-link me-2'></i>روابط سريعة للاختبار:</h5>";
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<ul class='list-unstyled'>";
echo "<li><a href='login.php' class='btn btn-sm btn-outline-primary me-2 mb-2'><i class='fas fa-sign-in-alt me-1'></i>تسجيل الدخول</a></li>";
echo "<li><a href='index.php' class='btn btn-sm btn-outline-success me-2 mb-2'><i class='fas fa-home me-1'></i>الصفحة الرئيسية</a></li>";
echo "<li><a href='teachers/list.php' class='btn btn-sm btn-outline-info me-2 mb-2'><i class='fas fa-users me-1'></i>قائمة المعلمين</a></li>";
echo "</ul>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<ul class='list-unstyled'>";
echo "<li><a href='setup_fixed.php' class='btn btn-sm btn-outline-warning me-2 mb-2'><i class='fas fa-cog me-1'></i>إعداد البيانات</a></li>";
echo "<li><a href='final_update.php' class='btn btn-sm btn-outline-secondary me-2 mb-2'><i class='fas fa-sync me-1'></i>التحديث النهائي</a></li>";
echo "</ul>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

echo "</div>
</div>
</div>
</div>
</div>
</body>
</html>";
?>
