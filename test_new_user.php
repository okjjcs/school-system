<?php
require_once 'config/config.php';

echo "<h2>اختبار إنشاء مستخدم جديد وتسجيل الدخول</h2>";

// إنشاء مستخدم تجريبي
if ($_POST && isset($_POST['create_user'])) {
    $testUsername = $_POST['test_username'] ?? 'test_teacher_' . time();
    $testPassword = $_POST['test_password'] ?? '123456';
    
    echo "<h3>إنشاء مستخدم تجريبي:</h3>";
    echo "<p><strong>اسم المستخدم:</strong> $testUsername</p>";
    echo "<p><strong>كلمة المرور:</strong> $testPassword</p>";
    
    try {
        // تشفير كلمة المرور
        $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "<p><strong>كلمة المرور المشفرة:</strong> " . substr($hashedPassword, 0, 30) . "...</p>";
        
        // إدراج المستخدم
        $stmt = $db->query("INSERT INTO users (username, password, role, is_active, created_at) VALUES (?, ?, 'teacher', 1, datetime('now'))",
                          [$testUsername, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        if ($userId) {
            echo "<p style='color: green;'>✅ تم إنشاء المستخدم بنجاح - ID: $userId</p>";
            
            // التحقق من المستخدم المحفوظ
            $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
            $savedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($savedUser) {
                echo "<p><strong>المستخدم المحفوظ:</strong></p>";
                echo "<ul>";
                echo "<li>اسم المستخدم: " . $savedUser['username'] . "</li>";
                echo "<li>كلمة المرور المحفوظة: " . substr($savedUser['password'], 0, 30) . "...</li>";
                echo "<li>الدور: " . $savedUser['role'] . "</li>";
                echo "<li>نشط: " . ($savedUser['is_active'] ? 'نعم' : 'لا') . "</li>";
                echo "</ul>";
                
                // اختبار التحقق من كلمة المرور
                $verifyResult = password_verify($testPassword, $savedUser['password']);
                echo "<p><strong>اختبار التحقق من كلمة المرور:</strong> " . ($verifyResult ? '✅ نجح' : '❌ فشل') . "</p>";
                
                if ($verifyResult) {
                    echo "<p style='color: green; font-weight: bold;'>🎉 المستخدم جاهز لتسجيل الدخول!</p>";
                } else {
                    echo "<p style='color: red; font-weight: bold;'>⚠️ مشكلة في تشفير كلمة المرور!</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ فشل في إنشاء المستخدم</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
    }
}

// اختبار تسجيل الدخول
if ($_POST && isset($_POST['test_login'])) {
    $loginUsername = $_POST['login_username'] ?? '';
    $loginPassword = $_POST['login_password'] ?? '';
    
    echo "<hr>";
    echo "<h3>اختبار تسجيل الدخول:</h3>";
    echo "<p><strong>اسم المستخدم:</strong> $loginUsername</p>";
    echo "<p><strong>كلمة المرور:</strong> $loginPassword</p>";
    
    try {
        $stmt = $db->query("SELECT * FROM users WHERE username = ? AND is_active = 1", [$loginUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color: green;'>✅ المستخدم موجود</p>";
            echo "<p><strong>كلمة المرور المحفوظة:</strong> " . substr($user['password'], 0, 30) . "...</p>";
            
            // اختبار التحقق من كلمة المرور
            $passwordValid = password_verify($loginPassword, $user['password']);
            echo "<p><strong>نتيجة التحقق:</strong> " . ($passwordValid ? '✅ صحيحة' : '❌ خاطئة') . "</p>";
            
            if (!$passwordValid) {
                // اختبار مقارنة مباشرة
                if ($loginPassword === $user['password']) {
                    echo "<p style='color: orange;'>⚠️ كلمة المرور غير مشفرة - سيتم إصلاحها</p>";
                    
                    // إعادة تشفير
                    $newHash = password_hash($loginPassword, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password = ? WHERE id = ?", [$newHash, $user['id']]);
                    
                    echo "<p style='color: green;'>✅ تم إصلاح كلمة المرور</p>";
                    $passwordValid = true;
                }
            }
            
            if ($passwordValid) {
                echo "<p style='color: green; font-weight: bold;'>🎉 تسجيل الدخول نجح!</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ تسجيل الدخول فشل</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ المستخدم غير موجود أو غير نشط</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
    }
}

// عرض جميع المستخدمين
try {
    $stmt = $db->query("SELECT id, username, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<hr>";
    echo "<h3>آخر 10 مستخدمين:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>اسم المستخدم</th><th>الدور</th><th>نشط</th><th>تاريخ الإنشاء</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . ($user['is_active'] ? 'نعم' : 'لا') . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ في جلب المستخدمين: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار المستخدمين الجدد</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        input[type="text"], input[type="password"] { padding: 5px; width: 200px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; margin: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h3>إنشاء مستخدم تجريبي:</h3>
    <form method="POST">
        <div class="form-group">
            <label>اسم المستخدم:</label>
            <input type="text" name="test_username" value="test_teacher_<?php echo time(); ?>" required>
        </div>
        <div class="form-group">
            <label>كلمة المرور:</label>
            <input type="text" name="test_password" value="123456" required>
        </div>
        <button type="submit" name="create_user">إنشاء مستخدم</button>
    </form>
    
    <hr>
    
    <h3>اختبار تسجيل الدخول:</h3>
    <form method="POST">
        <div class="form-group">
            <label>اسم المستخدم:</label>
            <input type="text" name="login_username" required>
        </div>
        <div class="form-group">
            <label>كلمة المرور:</label>
            <input type="password" name="login_password" required>
        </div>
        <button type="submit" name="test_login">اختبار تسجيل الدخول</button>
    </form>
    
    <hr>
    <p><strong>ملاحظة:</strong> احذف هذا الملف بعد الانتهاء من الاختبار.</p>
    <p><a href="login.php">الذهاب إلى صفحة تسجيل الدخول</a></p>
</body>
</html>
