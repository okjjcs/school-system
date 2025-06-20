<?php
require_once 'config/config.php';

echo "<h2>اختبار إضافة معلم مبسط</h2>";

// التحقق من قاعدة البيانات
echo "<h3>1. فحص قاعدة البيانات:</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "<p>✅ الاتصال بقاعدة البيانات نجح - عدد المستخدمين: $userCount</p>";
    
    $stmt = $db->query("SELECT COUNT(*) FROM teachers");
    $teacherCount = $stmt->fetchColumn();
    echo "<p>✅ جدول المعلمين متاح - عدد المعلمين: $teacherCount</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
    exit;
}

// إضافة معلم تجريبي
if ($_POST && isset($_POST['add_teacher'])) {
    echo "<h3>2. إضافة معلم تجريبي:</h3>";
    
    $username = $_POST['username'] ?? 'teacher_' . time();
    $password = $_POST['password'] ?? '123456';
    $employeeId = $_POST['employee_id'] ?? 'EMP' . time();
    $firstName = $_POST['first_name'] ?? 'أحمد';
    $lastName = $_POST['last_name'] ?? 'محمد';
    $subject = $_POST['subject'] ?? 'الرياضيات';
    $gradeLevel = $_POST['grade_level'] ?? 'الصف الأول';
    $hireDate = $_POST['hire_date'] ?? date('Y-m-d');
    
    echo "<p><strong>البيانات:</strong></p>";
    echo "<ul>";
    echo "<li>اسم المستخدم: $username</li>";
    echo "<li>كلمة المرور: $password</li>";
    echo "<li>رقم الموظف: $employeeId</li>";
    echo "<li>الاسم: $firstName $lastName</li>";
    echo "<li>التخصص: $subject</li>";
    echo "<li>الصف: $gradeLevel</li>";
    echo "<li>تاريخ التوظيف: $hireDate</li>";
    echo "</ul>";
    
    try {
        // التحقق من عدم تكرار البيانات
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = ?", [$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("اسم المستخدم '$username' موجود بالفعل");
        }
        
        $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE employee_id = ?", [$employeeId]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("رقم الموظف '$employeeId' موجود بالفعل");
        }
        
        echo "<p>✅ التحقق من عدم تكرار البيانات نجح</p>";
        
        // بدء المعاملة
        $db->getConnection()->beginTransaction();
        echo "<p>✅ تم بدء المعاملة</p>";
        
        // تشفير كلمة المرور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        echo "<p>✅ تم تشفير كلمة المرور</p>";
        
        // إضافة المستخدم
        $stmt = $db->query("INSERT INTO users (username, password, role, is_active, created_at) VALUES (?, ?, 'teacher', 1, datetime('now'))",
                          [$username, $hashedPassword]);
        
        if (!$stmt) {
            throw new Exception('فشل في إدراج المستخدم');
        }
        
        $userId = $db->lastInsertId();
        if (!$userId) {
            throw new Exception('لم يتم الحصول على ID المستخدم');
        }
        
        echo "<p>✅ تم إضافة المستخدم - ID: $userId</p>";
        
        // إضافة المعلم
        $stmt = $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, subject, grade_level, hire_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))",
                          [$userId, $employeeId, $firstName, $lastName, $subject, $gradeLevel, $hireDate]);
        
        if (!$stmt) {
            throw new Exception('فشل في إدراج المعلم');
        }
        
        $teacherId = $db->lastInsertId();
        if (!$teacherId) {
            throw new Exception('لم يتم الحصول على ID المعلم');
        }
        
        echo "<p>✅ تم إضافة المعلم - ID: $teacherId</p>";
        
        // تأكيد المعاملة
        $db->getConnection()->commit();
        echo "<p style='color: green; font-weight: bold;'>🎉 تم إضافة المعلم بنجاح!</p>";
        
        // التحقق من الحفظ
        $stmt = $db->query("SELECT u.*, t.* FROM users u JOIN teachers t ON u.id = t.user_id WHERE u.id = ?", [$userId]);
        $savedData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($savedData) {
            echo "<p>✅ تم التحقق من حفظ البيانات</p>";
            echo "<p><strong>البيانات المحفوظة:</strong></p>";
            echo "<ul>";
            echo "<li>ID المستخدم: " . $savedData['id'] . "</li>";
            echo "<li>اسم المستخدم: " . $savedData['username'] . "</li>";
            echo "<li>ID المعلم: " . $savedData['0'] . "</li>";
            echo "<li>الاسم: " . $savedData['first_name'] . " " . $savedData['last_name'] . "</li>";
            echo "</ul>";
            
            // اختبار تسجيل الدخول
            $loginTest = password_verify($password, $savedData['password']);
            echo "<p>✅ اختبار تسجيل الدخول: " . ($loginTest ? 'نجح' : 'فشل') . "</p>";
        } else {
            echo "<p style='color: red;'>❌ البيانات غير موجودة بعد الحفظ!</p>";
        }
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
    }
}

// عرض البيانات الحالية
echo "<h3>3. البيانات الحالية:</h3>";
try {
    $stmt = $db->query("SELECT u.id, u.username, u.role, t.first_name, t.last_name, t.employee_id, t.subject FROM users u LEFT JOIN teachers t ON u.id = t.user_id ORDER BY u.created_at DESC LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>اسم المستخدم</th><th>الدور</th><th>الاسم</th><th>رقم الموظف</th><th>التخصص</th></tr>";
    
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) . "</td>";
        echo "<td>" . htmlspecialchars($row['employee_id'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['subject'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ في عرض البيانات: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار إضافة معلم مبسط</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        .form-group { margin: 10px 0; }
        input[type="text"], input[type="date"] { padding: 5px; width: 200px; }
        select { padding: 5px; width: 210px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <hr>
    <h3>إضافة معلم جديد:</h3>
    <form method="POST">
        <div class="form-group">
            <label>اسم المستخدم:</label>
            <input type="text" name="username" value="teacher_<?php echo time(); ?>" required>
        </div>
        <div class="form-group">
            <label>كلمة المرور:</label>
            <input type="text" name="password" value="123456" required>
        </div>
        <div class="form-group">
            <label>رقم الموظف:</label>
            <input type="text" name="employee_id" value="EMP<?php echo time(); ?>" required>
        </div>
        <div class="form-group">
            <label>الاسم الأول:</label>
            <input type="text" name="first_name" value="أحمد" required>
        </div>
        <div class="form-group">
            <label>اسم العائلة:</label>
            <input type="text" name="last_name" value="محمد" required>
        </div>
        <div class="form-group">
            <label>التخصص:</label>
            <select name="subject" required>
                <option value="الرياضيات">الرياضيات</option>
                <option value="اللغة العربية">اللغة العربية</option>
                <option value="العلوم">العلوم</option>
            </select>
        </div>
        <div class="form-group">
            <label>الصف:</label>
            <select name="grade_level" required>
                <option value="الصف الأول">الصف الأول</option>
                <option value="الصف الثاني">الصف الثاني</option>
                <option value="الصف الثالث">الصف الثالث</option>
            </select>
        </div>
        <div class="form-group">
            <label>تاريخ التوظيف:</label>
            <input type="date" name="hire_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <button type="submit" name="add_teacher">إضافة معلم</button>
    </form>
    
    <hr>
    <p><a href="teachers/add.php">الذهاب إلى صفحة إضافة المعلم الأصلية</a></p>
    <p><a href="login.php">الذهاب إلى صفحة تسجيل الدخول</a></p>
</body>
</html>
