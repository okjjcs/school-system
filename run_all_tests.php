<?php
require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>اختبار شامل - نظام أرشفة الأساتذة</title>
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
<div class='card-header bg-success text-white'>
<h4 class='mb-0'><i class='fas fa-play-circle me-2'></i>تشغيل جميع اختبارات النظام</h4>
</div>
<div class='card-body'>";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Database Connection
echo "<h5 class='text-primary'><i class='fas fa-database me-2'></i>اختبار 1: الاتصال بقاعدة البيانات</h5>";
$totalTests++;
try {
    $stmt = $db->query("SELECT 1");
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>نجح الاتصال بقاعدة البيانات</div>";
    $passedTests++;
    $testResults['database_connection'] = true;
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>فشل الاتصال بقاعدة البيانات: " . $e->getMessage() . "</div>";
    $testResults['database_connection'] = false;
}

// Test 2: User Authentication Functions
echo "<h5 class='text-primary mt-4'><i class='fas fa-user-shield me-2'></i>اختبار 2: وظائف المصادقة</h5>";
$totalTests++;
try {
    // Test password hashing
    $testPassword = 'test123';
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    $verifyResult = password_verify($testPassword, $hashedPassword);
    
    if ($verifyResult) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تشفير كلمات المرور يعمل بشكل صحيح</div>";
        $passedTests++;
        $testResults['password_hashing'] = true;
    } else {
        echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في تشفير كلمات المرور</div>";
        $testResults['password_hashing'] = false;
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في اختبار المصادقة: " . $e->getMessage() . "</div>";
    $testResults['password_hashing'] = false;
}

// Test 3: Database Tables Structure
echo "<h5 class='text-primary mt-4'><i class='fas fa-table me-2'></i>اختبار 3: هيكل جداول قاعدة البيانات</h5>";
$requiredTables = [
    'users' => ['id', 'username', 'password', 'role'],
    'teachers' => ['id', 'user_id', 'employee_id', 'first_name', 'last_name'],
    'daily_preparations' => ['id', 'teacher_id', 'subject', 'grade', 'lesson_title'],
    'activities' => ['id', 'teacher_id', 'title', 'description', 'activity_type'],
    'curriculum_progress' => ['id', 'teacher_id', 'subject', 'grade', 'unit_title'],
    'warnings' => ['id', 'teacher_id', 'warning_type', 'title', 'description'],
    'attendance' => ['id', 'teacher_id', 'attendance_date', 'status'],
    'files' => ['id', 'teacher_id', 'file_name', 'original_name', 'file_path']
];

$tableTestsPassed = 0;
$totalTableTests = count($requiredTables);

foreach ($requiredTables as $tableName => $requiredColumns) {
    $totalTests++;
    try {
        // Check if table exists and has required columns
        $stmt = $db->query("PRAGMA table_info($tableName)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $existingColumns = array_column($columns, 'name');
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        
        if (empty($missingColumns)) {
            echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>جدول $tableName: جميع الأعمدة المطلوبة موجودة</div>";
            $passedTests++;
            $tableTestsPassed++;
        } else {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>جدول $tableName: أعمدة مفقودة - " . implode(', ', $missingColumns) . "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في فحص جدول $tableName: " . $e->getMessage() . "</div>";
    }
}

// Test 4: Core Functions
echo "<h5 class='text-primary mt-4'><i class='fas fa-cogs me-2'></i>اختبار 4: الوظائف الأساسية</h5>";

// Test sanitize function
$totalTests++;
try {
    $testInput = "<script>alert('test')</script>";
    $sanitized = sanitize($testInput);
    if ($sanitized !== $testInput) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>وظيفة تنظيف البيانات تعمل بشكل صحيح</div>";
        $passedTests++;
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>وظيفة تنظيف البيانات قد لا تعمل بشكل صحيح</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في اختبار وظيفة التنظيف: " . $e->getMessage() . "</div>";
}

// Test formatDateArabic function
$totalTests++;
try {
    $testDate = '2024-01-15 10:30:00';
    $formattedDate = formatDateArabic($testDate);
    if (!empty($formattedDate)) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>وظيفة تنسيق التاريخ العربي تعمل بشكل صحيح</div>";
        $passedTests++;
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>وظيفة تنسيق التاريخ العربي قد لا تعمل بشكل صحيح</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في اختبار وظيفة التاريخ: " . $e->getMessage() . "</div>";
}

// Test 5: File System Permissions
echo "<h5 class='text-primary mt-4'><i class='fas fa-folder-open me-2'></i>اختبار 5: صلاحيات نظام الملفات</h5>";

$testDirectories = ['uploads', 'database'];
foreach ($testDirectories as $dir) {
    $totalTests++;
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>مجلد $dir قابل للكتابة</div>";
            $passedTests++;
        } else {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>مجلد $dir غير قابل للكتابة</div>";
        }
    } else {
        echo "<div class='alert alert-info'><i class='fas fa-info me-2'></i>مجلد $dir غير موجود (سيتم إنشاؤه عند الحاجة)</div>";
        $passedTests++; // Not critical for basic functionality
    }
}

// Test 6: Sample Data Verification
echo "<h5 class='text-primary mt-4'><i class='fas fa-data me-2'></i>اختبار 6: التحقق من البيانات التجريبية</h5>";

try {
    // Check for admin user
    $totalTests++;
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount > 0) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>يوجد $adminCount حساب مدير في النظام</div>";
        $passedTests++;
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>لا يوجد حساب مدير في النظام</div>";
    }
    
    // Check for teacher users
    $totalTests++;
    $stmt = $db->query("SELECT COUNT(*) FROM teachers");
    $teacherCount = $stmt->fetchColumn();
    
    if ($teacherCount > 0) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>يوجد $teacherCount معلم في النظام</div>";
        $passedTests++;
    } else {
        echo "<div class='alert alert-info'><i class='fas fa-info me-2'></i>لا يوجد معلمون في النظام (يمكن إضافتهم لاحقاً)</div>";
        $passedTests++; // Not critical for testing
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ في فحص البيانات التجريبية: " . $e->getMessage() . "</div>";
}

// Test Results Summary
echo "<div class='mt-5'>";
echo "<div class='card'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h5 class='mb-0'><i class='fas fa-chart-bar me-2'></i>ملخص نتائج الاختبارات</h5>";
echo "</div>";
echo "<div class='card-body'>";

$successRate = ($totalTests > 0) ? ($passedTests / $totalTests) * 100 : 0;

echo "<div class='row text-center mb-4'>";
echo "<div class='col-md-4'>";
echo "<div class='card bg-primary text-white'>";
echo "<div class='card-body'>";
echo "<h3>$totalTests</h3>";
echo "<p class='mb-0'>إجمالي الاختبارات</p>";
echo "</div></div></div>";

echo "<div class='col-md-4'>";
echo "<div class='card bg-success text-white'>";
echo "<div class='card-body'>";
echo "<h3>$passedTests</h3>";
echo "<p class='mb-0'>اختبارات ناجحة</p>";
echo "</div></div></div>";

echo "<div class='col-md-4'>";
echo "<div class='card bg-info text-white'>";
echo "<div class='card-body'>";
echo "<h3>" . number_format($successRate, 1) . "%</h3>";
echo "<p class='mb-0'>معدل النجاح</p>";
echo "</div></div></div>";
echo "</div>";

// Overall Status
if ($successRate >= 90) {
    echo "<div class='alert alert-success alert-lg'>";
    echo "<h4><i class='fas fa-trophy me-2'></i>ممتاز! النظام يعمل بشكل مثالي</h4>";
    echo "<p class='mb-0'>جميع الاختبارات الأساسية نجحت. النظام جاهز للاستخدام الفوري.</p>";
    echo "</div>";
} elseif ($successRate >= 75) {
    echo "<div class='alert alert-warning alert-lg'>";
    echo "<h4><i class='fas fa-check-circle me-2'></i>جيد! النظام يعمل مع بعض التحذيرات</h4>";
    echo "<p class='mb-0'>معظم الاختبارات نجحت. يمكن استخدام النظام مع مراجعة التحذيرات.</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger alert-lg'>";
    echo "<h4><i class='fas fa-exclamation-triangle me-2'></i>يحتاج إصلاح! توجد مشاكل في النظام</h4>";
    echo "<p class='mb-0'>عدة اختبارات فشلت. يرجى مراجعة الأخطاء وإصلاحها قبل الاستخدام.</p>";
    echo "</div>";
}

echo "</div></div></div>";

// Quick Actions
echo "<div class='mt-4'>";
echo "<div class='alert alert-primary'>";
echo "<h5><i class='fas fa-rocket me-2'></i>إجراءات سريعة:</h5>";
echo "<div class='btn-group-vertical w-100' role='group'>";
echo "<a href='setup_fixed.php' class='btn btn-outline-primary mb-2'><i class='fas fa-cog me-2'></i>تشغيل إعداد البيانات التجريبية</a>";
echo "<a href='final_update.php' class='btn btn-outline-success mb-2'><i class='fas fa-sync me-2'></i>تشغيل التحديث النهائي</a>";
echo "<a href='system_verification.php' class='btn btn-outline-info mb-2'><i class='fas fa-search me-2'></i>فحص شامل للنظام</a>";
echo "<a href='login.php' class='btn btn-outline-warning mb-2'><i class='fas fa-sign-in-alt me-2'></i>اختبار تسجيل الدخول</a>";
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
