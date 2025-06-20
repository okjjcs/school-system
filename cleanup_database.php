<?php
echo "<h2>تنظيف مجلدات قاعدة البيانات الخاطئة</h2>";

$currentDir = __DIR__;
$wrongDatabaseFolders = [];

// البحث عن مجلدات database في أماكن خاطئة
$foldersToCheck = [
    $currentDir . '/teachers/database',
    $currentDir . '/database' // إذا كان في المجلد الجذر بدلاً من المكان الصحيح
];

foreach ($foldersToCheck as $folder) {
    if (is_dir($folder)) {
        $wrongDatabaseFolders[] = $folder;
    }
}

if (empty($wrongDatabaseFolders)) {
    echo "<p style='color: green;'>✅ لا توجد مجلدات قاعدة بيانات في أماكن خاطئة</p>";
} else {
    echo "<p style='color: red;'>❌ تم العثور على مجلدات قاعدة بيانات في أماكن خاطئة:</p>";
    echo "<ul>";
    foreach ($wrongDatabaseFolders as $folder) {
        echo "<li>$folder</li>";
        
        // عرض محتويات المجلد
        if (is_dir($folder)) {
            $files = scandir($folder);
            $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
            if (!empty($files)) {
                echo "<ul>";
                foreach ($files as $file) {
                    $filePath = $folder . '/' . $file;
                    $size = is_file($filePath) ? filesize($filePath) : 0;
                    echo "<li>$file (" . $size . " بايت)</li>";
                }
                echo "</ul>";
            }
        }
    }
    echo "</ul>";
}

// التحقق من المسار الصحيح
$correctPath = 'C:\xampp\htdocs\school-system\database\school_archive.db';
echo "<h3>التحقق من المسار الصحيح:</h3>";
echo "<p><strong>المسار الصحيح:</strong> $correctPath</p>";
echo "<p><strong>الملف موجود:</strong> " . (file_exists($correctPath) ? '✅ نعم' : '❌ لا') . "</p>";

if (file_exists($correctPath)) {
    echo "<p><strong>حجم الملف:</strong> " . filesize($correctPath) . " بايت</p>";
    echo "<p><strong>تاريخ آخر تعديل:</strong> " . date('Y-m-d H:i:s', filemtime($correctPath)) . "</p>";
}

// عملية التنظيف
if ($_POST && isset($_POST['cleanup'])) {
    echo "<hr>";
    echo "<h3>تنظيف المجلدات الخاطئة:</h3>";
    
    foreach ($wrongDatabaseFolders as $folder) {
        try {
            if (is_dir($folder)) {
                // حذف جميع الملفات في المجلد
                $files = scandir($folder);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $folder . '/' . $file;
                        if (is_file($filePath)) {
                            unlink($filePath);
                            echo "<p>✅ تم حذف الملف: $filePath</p>";
                        }
                    }
                }
                
                // حذف المجلد
                rmdir($folder);
                echo "<p style='color: green;'>✅ تم حذف المجلد: $folder</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ خطأ في حذف $folder: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>🎉 تم تنظيف جميع المجلدات الخاطئة!</p>";
    echo "<p>الآن سيتم استخدام قاعدة البيانات الصحيحة فقط.</p>";
    
    // إعادة تحميل الصفحة لإعادة الفحص
    echo "<script>
    setTimeout(function() {
        window.location.reload();
    }, 2000);
    </script>";
}

// اختبار الاتصال بقاعدة البيانات الصحيحة
echo "<h3>اختبار الاتصال بقاعدة البيانات الصحيحة:</h3>";
try {
    require_once 'config/config.php';
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM teachers");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>✅ الاتصال بقاعدة البيانات نجح</p>";
    echo "<p><strong>عدد المستخدمين:</strong> $userCount</p>";
    echo "<p><strong>عدد المعلمين:</strong> $teacherCount</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظيف قاعدة البيانات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        button { padding: 10px 20px; background: #dc3545; color: white; border: none; cursor: pointer; }
        .safe-button { background: #28a745; }
    </style>
</head>
<body>
    <?php if (!empty($wrongDatabaseFolders)): ?>
    <hr>
    <h3>تنظيف المجلدات الخاطئة:</h3>
    <p style='color: red;'><strong>تحذير:</strong> سيتم حذف المجلدات والملفات التالية:</p>
    <ul>
        <?php foreach ($wrongDatabaseFolders as $folder): ?>
        <li><?php echo $folder; ?></li>
        <?php endforeach; ?>
    </ul>
    
    <form method="POST">
        <button type="submit" name="cleanup" onclick="return confirm('هل أنت متأكد من حذف هذه المجلدات؟')">
            حذف المجلدات الخاطئة
        </button>
    </form>
    <?php endif; ?>
    
    <hr>
    <p><a href="test_database_path.php">اختبار مسار قاعدة البيانات</a></p>
    <p><a href="teachers/add.php">الذهاب إلى صفحة إضافة المعلم</a></p>
</body>
</html>
