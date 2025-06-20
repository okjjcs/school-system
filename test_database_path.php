<?php
require_once 'config/config.php';

echo "<h2>اختبار مسار قاعدة البيانات</h2>";

echo "<h3>1. فحص الإعدادات:</h3>";
echo "<p><strong>DB_PATH المحدد:</strong> " . DB_PATH . "</p>";
echo "<p><strong>ملف قاعدة البيانات موجود:</strong> " . (file_exists(DB_PATH) ? '✅ نعم' : '❌ لا') . "</p>";

if (file_exists(DB_PATH)) {
    echo "<p><strong>حجم الملف:</strong> " . filesize(DB_PATH) . " بايت</p>";
    echo "<p><strong>تاريخ آخر تعديل:</strong> " . date('Y-m-d H:i:s', filemtime(DB_PATH)) . "</p>";
}

echo "<h3>2. فحص مسار قاعدة البيانات المستخدم:</h3>";
try {
    // إنشاء كائن قاعدة البيانات
    $testDb = new Database(DB_PATH);
    
    // الحصول على معلومات قاعدة البيانات
    $reflection = new ReflectionClass($testDb);
    $dbPathProperty = $reflection->getProperty('dbPath');
    $dbPathProperty->setAccessible(true);
    $actualDbPath = $dbPathProperty->getValue($testDb);
    
    echo "<p><strong>المسار المستخدم فعلياً:</strong> " . $actualDbPath . "</p>";
    echo "<p><strong>هل المسار صحيح:</strong> " . ($actualDbPath === DB_PATH ? '✅ نعم' : '❌ لا') . "</p>";
    
    // اختبار الاتصال
    $stmt = $testDb->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>عدد المستخدمين:</strong> " . $result['count'] . "</p>";
    
    $stmt = $testDb->query("SELECT COUNT(*) as count FROM teachers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>عدد المعلمين:</strong> " . $result['count'] . "</p>";
    
    echo "<p style='color: green;'>✅ الاتصال بقاعدة البيانات نجح</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ في الاتصال: " . $e->getMessage() . "</p>";
}

echo "<h3>3. فحص المجلدات الموجودة:</h3>";
$currentDir = __DIR__;
echo "<p><strong>المجلد الحالي:</strong> $currentDir</p>";

// فحص وجود مجلد database في المجلد الحالي
if (is_dir($currentDir . '/database')) {
    echo "<p style='color: orange;'>⚠️ يوجد مجلد database في المجلد الحالي</p>";
    $files = scandir($currentDir . '/database');
    echo "<p><strong>الملفات في المجلد:</strong> " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })) . "</p>";
} else {
    echo "<p>✅ لا يوجد مجلد database في المجلد الحالي</p>";
}

// فحص وجود مجلد database في مجلد teachers
$teachersDir = $currentDir . '/teachers';
if (is_dir($teachersDir . '/database')) {
    echo "<p style='color: red;'>❌ يوجد مجلد database في مجلد teachers (هذا خطأ!)</p>";
    $files = scandir($teachersDir . '/database');
    echo "<p><strong>الملفات في المجلد:</strong> " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })) . "</p>";
    
    echo "<p><strong>حل المشكلة:</strong> احذف مجلد teachers/database</p>";
} else {
    echo "<p>✅ لا يوجد مجلد database في مجلد teachers</p>";
}

echo "<h3>4. اختبار من مجلد teachers:</h3>";
echo "<p>سيتم اختبار الاتصال من مجلد teachers...</p>";

// محاكاة الاتصال من مجلد teachers
$teachersConfigPath = $currentDir . '/teachers/../config/config.php';
echo "<p><strong>مسار config من teachers:</strong> $teachersConfigPath</p>";
echo "<p><strong>مسار config صحيح:</strong> " . (file_exists($teachersConfigPath) ? '✅ نعم' : '❌ لا') . "</p>";

if ($_POST && isset($_POST['test_from_teachers'])) {
    echo "<hr>";
    echo "<h3>نتيجة الاختبار من مجلد teachers:</h3>";
    
    // تغيير المجلد الحالي لمحاكاة التشغيل من مجلد teachers
    $originalDir = getcwd();
    chdir($currentDir . '/teachers');
    
    try {
        // تحميل config من المسار النسبي
        require_once '../config/config.php';
        
        echo "<p>✅ تم تحميل config بنجاح من مجلد teachers</p>";
        echo "<p><strong>DB_PATH:</strong> " . DB_PATH . "</p>";
        
        // اختبار إنشاء قاعدة البيانات
        $teachersDb = new Database(DB_PATH);
        echo "<p>✅ تم إنشاء اتصال قاعدة البيانات بنجاح</p>";
        
        // التحقق من المسار المستخدم
        $reflection = new ReflectionClass($teachersDb);
        $dbPathProperty = $reflection->getProperty('dbPath');
        $dbPathProperty->setAccessible(true);
        $actualDbPath = $dbPathProperty->getValue($teachersDb);
        
        echo "<p><strong>المسار المستخدم:</strong> $actualDbPath</p>";
        echo "<p><strong>هل المسار صحيح:</strong> " . ($actualDbPath === DB_PATH ? '✅ نعم' : '❌ لا') . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
    } finally {
        // العودة للمجلد الأصلي
        chdir($originalDir);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار مسار قاعدة البيانات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <hr>
    <h3>اختبار من مجلد teachers:</h3>
    <form method="POST">
        <button type="submit" name="test_from_teachers">اختبار الاتصال من مجلد teachers</button>
    </form>
    
    <hr>
    <p><a href="teachers/add.php">الذهاب إلى صفحة إضافة المعلم</a></p>
    <p><a href="simple_add_teacher.php">الذهاب إلى الملف المبسط</a></p>
</body>
</html>
