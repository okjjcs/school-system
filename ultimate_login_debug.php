<?php
require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>تشخيص شامل لمشكلة تسجيل الدخول</title>
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
<div class='card-header bg-danger text-white'>
<h4 class='mb-0'><i class='fas fa-search me-2'></i>تشخيص شامل لمشكلة تسجيل الدخول</h4>
</div>
<div class='card-body'>";

// إنشاء معلم تجريبي جديد مع تتبع كامل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_and_test'])) {
    $testUsername = trim($_POST['test_username']);
    $testPassword = trim($_POST['test_password']);
    
    echo "<h5 class='text-primary'><i class='fas fa-plus me-2'></i>إنشاء معلم تجريبي جديد:</h5>";
    
    echo "<div class='alert alert-info'>
    <strong>البيانات المدخلة:</strong><br>
    اسم المستخدم: '<span style='background: yellow;'>$testUsername</span>' (طول: " . strlen($testUsername) . ")<br>
    كلمة المرور: '<span style='background: yellow;'>$testPassword</span>' (طول: " . strlen($testPassword) . ")<br>
    </div>";
    
    try {
        // فحص وجود اسم المستخدم مسبقاً
        $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$testUsername]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            echo "<div class='alert alert-warning'>
            <h6>اسم المستخدم موجود بالفعل!</h6>
            <p>ID: {$existingUser['id']}<br>
            اسم المستخدم: '{$existingUser['username']}'<br>
            نشط: " . ($existingUser['is_active'] ? 'نعم' : 'لا') . "</p>
            </div>";
            
            // اختبار تسجيل الدخول مع المستخدم الموجود
            if (password_verify($testPassword, $existingUser['password'])) {
                echo "<div class='alert alert-success'>كلمة المرور صحيحة للمستخدم الموجود!</div>";
            } else {
                echo "<div class='alert alert-danger'>كلمة المرور خاطئة للمستخدم الموجود!</div>";
                echo "<div class='alert alert-info'>سيتم تحديث كلمة المرور...</div>";
                
                // تحديث كلمة المرور
                $newHashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
                $stmt = $db->query("UPDATE users SET password = ? WHERE id = ?", [$newHashedPassword, $existingUser['id']]);
                echo "<div class='alert alert-success'>تم تحديث كلمة المرور!</div>";
            }
        } else {
            // إنشاء مستخدم جديد
            $db->getConnection()->beginTransaction();
            
            $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "<div class='alert alert-secondary'>
            كلمة المرور الأصلية: '$testPassword'<br>
            كلمة المرور المشفرة: " . substr($hashedPassword, 0, 60) . "...
            </div>";
            
            $stmt = $db->query("INSERT INTO users (username, password, role, is_active, created_at) VALUES (?, ?, 'teacher', 1, datetime('now'))", 
                              [$testUsername, $hashedPassword]);
            $userId = $db->lastInsertId();
            
            echo "<div class='alert alert-success'>تم إنشاء المستخدم - ID: $userId</div>";
            
            // إنشاء ملف المعلم
            $stmt = $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, subject, grade_level, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                              [$userId, 'TEST' . time(), 'معلم', 'تجريبي', 'test@example.com', 'اختبار', 'الصف الأول', date('Y-m-d')]);
            
            echo "<div class='alert alert-success'>تم إنشاء ملف المعلم</div>";
            
            $db->getConnection()->commit();
        }
        
        // اختبار فوري للبحث
        echo "<h6 class='text-info mt-3'>اختبار البحث الفوري:</h6>";
        
        $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$testUsername]);
        $foundUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($foundUser) {
            echo "<div class='alert alert-success'>
            <h6>تم العثور على المستخدم!</h6>
            <table class='table table-sm'>
            <tr><td>ID:</td><td>{$foundUser['id']}</td></tr>
            <tr><td>اسم المستخدم:</td><td>'{$foundUser['username']}'</td></tr>
            <tr><td>الدور:</td><td>{$foundUser['role']}</td></tr>
            <tr><td>نشط:</td><td>" . ($foundUser['is_active'] ? 'نعم' : 'لا') . "</td></tr>
            <tr><td>تاريخ الإنشاء:</td><td>{$foundUser['created_at']}</td></tr>
            </table>
            </div>";
            
            // اختبار كلمة المرور
            echo "<h6 class='text-danger mt-3'>اختبار كلمة المرور:</h6>";
            
            if (password_verify($testPassword, $foundUser['password'])) {
                echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>كلمة المرور صحيحة!</div>";
                
                // محاكاة تسجيل الدخول الكامل
                echo "<h6 class='text-success mt-3'>محاكاة تسجيل الدخول:</h6>";
                
                if ($foundUser['is_active']) {
                    echo "<div class='alert alert-success'>
                    <i class='fas fa-check me-2'></i>تسجيل الدخول نجح!<br>
                    <strong>يمكنك الآن استخدام:</strong><br>
                    اسم المستخدم: <code>$testUsername</code><br>
                    كلمة المرور: <code>$testPassword</code>
                    </div>";
                    
                    // إنشاء نموذج تسجيل دخول مباشر
                    echo "<div class='card mt-3'>
                    <div class='card-header bg-success text-white'>
                    <h6 class='mb-0'>تسجيل دخول مباشر</h6>
                    </div>
                    <div class='card-body'>
                    <form method='POST' action='login.php'>
                    <input type='hidden' name='username' value='" . htmlspecialchars($testUsername) . "'>
                    <input type='hidden' name='password' value='" . htmlspecialchars($testPassword) . "'>
                    <p>سيتم تسجيل الدخول بالبيانات التالية:</p>
                    <p><strong>اسم المستخدم:</strong> $testUsername</p>
                    <p><strong>كلمة المرور:</strong> $testPassword</p>
                    <button type='submit' class='btn btn-success'>تسجيل الدخول الآن</button>
                    </form>
                    </div>
                    </div>";
                    
                } else {
                    echo "<div class='alert alert-danger'>الحساب غير نشط!</div>";
                }
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>كلمة المرور خاطئة!</div>";
                
                // اختبار كلمات مرور مختلفة
                $testPasswords = ['teacher123', 'test123', $testPassword, trim($testPassword)];
                echo "<div class='alert alert-warning'>اختبار كلمات مرور مختلفة:</div>";
                
                foreach ($testPasswords as $tryPassword) {
                    if (password_verify($tryPassword, $foundUser['password'])) {
                        echo "<div class='alert alert-success'>كلمة المرور الصحيحة هي: '$tryPassword'</div>";
                        break;
                    } else {
                        echo "<div class='alert alert-secondary'>ليست: '$tryPassword'</div>";
                    }
                }
            }
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>لم يتم العثور على المستخدم!</div>";
        }
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->getConnection()->rollBack();
        }
        echo "<div class='alert alert-danger'><i class='fas fa-times me-2'></i>خطأ: " . $e->getMessage() . "</div>";
    }
}

// اختبار تسجيل دخول مباشر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_login_test'])) {
    $loginUsername = trim($_POST['login_username']);
    $loginPassword = trim($_POST['login_password']);
    
    echo "<h5 class='text-primary'><i class='fas fa-sign-in-alt me-2'></i>اختبار تسجيل الدخول المباشر:</h5>";
    
    echo "<div class='alert alert-info'>
    <strong>بيانات تسجيل الدخول:</strong><br>
    اسم المستخدم: '<span style='background: yellow;'>$loginUsername</span>'<br>
    كلمة المرور: '<span style='background: yellow;'>$loginPassword</span>'
    </div>";
    
    try {
        // نفس منطق صفحة login.php
        $stmt = $db->query("SELECT * FROM users WHERE username = ? AND is_active = 1", [$loginUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div class='alert alert-success'>تم العثور على المستخدم النشط!</div>";
            
            if (password_verify($loginPassword, $user['password'])) {
                echo "<div class='alert alert-success'>
                <i class='fas fa-check me-2'></i>تسجيل الدخول نجح!<br>
                <a href='login.php' class='btn btn-success mt-2'>اذهب لصفحة تسجيل الدخول</a>
                </div>";
            } else {
                echo "<div class='alert alert-danger'>كلمة المرور خاطئة!</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>المستخدم غير موجود أو غير نشط!</div>";
            
            // فحص بدون شرط is_active
            $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$loginUsername]);
            $userAny = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userAny) {
                echo "<div class='alert alert-warning'>المستخدم موجود لكنه غير نشط! سيتم تفعيله...</div>";
                $stmt = $db->query("UPDATE users SET is_active = 1 WHERE username = ?", [$loginUsername]);
                echo "<div class='alert alert-success'>تم تفعيل المستخدم!</div>";
            } else {
                echo "<div class='alert alert-danger'>المستخدم غير موجود نهائياً!</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>خطأ: " . $e->getMessage() . "</div>";
    }
}

// نموذج إنشاء واختبار
echo "<div class='row'>
<div class='col-md-6'>
<div class='card'>
<div class='card-header bg-primary text-white'>
<h6 class='mb-0'>إنشاء معلم تجريبي</h6>
</div>
<div class='card-body'>
<form method='POST'>
<div class='mb-3'>
<label for='test_username' class='form-label'>اسم المستخدم:</label>
<input type='text' class='form-control' id='test_username' name='test_username' 
       value='testuser" . time() . "' required>
</div>
<div class='mb-3'>
<label for='test_password' class='form-label'>كلمة المرور:</label>
<input type='text' class='form-control' id='test_password' name='test_password' 
       value='mypassword123' required>
</div>
<button type='submit' name='create_and_test' class='btn btn-primary w-100'>
<i class='fas fa-plus me-2'></i>إنشاء واختبار
</button>
</form>
</div>
</div>
</div>

<div class='col-md-6'>
<div class='card'>
<div class='card-header bg-success text-white'>
<h6 class='mb-0'>اختبار تسجيل دخول</h6>
</div>
<div class='card-body'>
<form method='POST'>
<div class='mb-3'>
<label for='login_username' class='form-label'>اسم المستخدم:</label>
<input type='text' class='form-control' id='login_username' name='login_username' 
       placeholder='أدخل اسم المستخدم' required>
</div>
<div class='mb-3'>
<label for='login_password' class='form-label'>كلمة المرور:</label>
<input type='text' class='form-control' id='login_password' name='login_password' 
       placeholder='أدخل كلمة المرور' required>
</div>
<button type='submit' name='direct_login_test' class='btn btn-success w-100'>
<i class='fas fa-sign-in-alt me-2'></i>اختبار تسجيل الدخول
</button>
</form>
</div>
</div>
</div>
</div>";

// عرض آخر المستخدمين
echo "<h5 class='text-primary mt-4'><i class='fas fa-users me-2'></i>آخر المستخدمين المنشأين:</h5>";

try {
    $stmt = $db->query("SELECT u.*, t.first_name, t.last_name 
                       FROM users u 
                       LEFT JOIN teachers t ON u.id = t.user_id 
                       WHERE u.role = 'teacher' 
                       ORDER BY u.created_at DESC 
                       LIMIT 5");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recentUsers)) {
        echo "<div class='table-responsive'>
        <table class='table table-striped'>
        <thead class='table-dark'>
        <tr>
        <th>ID</th>
        <th>اسم المستخدم</th>
        <th>اسم المعلم</th>
        <th>نشط</th>
        <th>تاريخ الإنشاء</th>
        <th>اختبار سريع</th>
        </tr>
        </thead>
        <tbody>";
        
        foreach ($recentUsers as $user) {
            $teacherName = $user['first_name'] ? $user['first_name'] . ' ' . $user['last_name'] : 'غير محدد';
            $isActive = $user['is_active'] ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">غير نشط</span>';
            $createdAt = date('Y-m-d H:i', strtotime($user['created_at']));
            
            echo "<tr>
            <td>{$user['id']}</td>
            <td><code>{$user['username']}</code></td>
            <td>" . htmlspecialchars($teacherName) . "</td>
            <td>$isActive</td>
            <td>$createdAt</td>
            <td>
                <button class='btn btn-sm btn-outline-primary' onclick='quickTest(\"{$user['username']}\")'>
                    اختبار
                </button>
            </td>
            </tr>";
        }
        
        echo "</tbody></table></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>خطأ في جلب المستخدمين: " . $e->getMessage() . "</div>";
}

// روابط مفيدة
echo "<div class='alert alert-primary mt-4'>
<h5><i class='fas fa-link me-2'></i>روابط مفيدة:</h5>
<div class='row'>
<div class='col-md-6'>
<ul class='list-unstyled'>
<li><a href='teachers/add.php' class='btn btn-sm btn-outline-success me-2 mb-2'><i class='fas fa-user-plus me-1'></i>إضافة معلم جديد</a></li>
<li><a href='login.php' class='btn btn-sm btn-outline-primary me-2 mb-2'><i class='fas fa-sign-in-alt me-1'></i>صفحة تسجيل الدخول</a></li>
</ul>
</div>
<div class='col-md-6'>
<ul class='list-unstyled'>
<li><a href='fix_username_problem.php' class='btn btn-sm btn-outline-warning me-2 mb-2'><i class='fas fa-wrench me-1'></i>إصلاح اسم المستخدم</a></li>
<li><a href='index.php' class='btn btn-sm btn-outline-secondary me-2 mb-2'><i class='fas fa-home me-1'></i>الصفحة الرئيسية</a></li>
</ul>
</div>
</div>
</div>";

echo "</div>
</div>
</div>
</div>
</div>

<script>
function quickTest(username) {
    const password = prompt('أدخل كلمة المرور لاختبار المستخدم: ' + username + '\\n\\nجرب: teacher123 أو test123 أو mypassword123');
    if (password) {
        document.getElementById('login_username').value = username;
        document.getElementById('login_password').value = password;
        document.querySelector('button[name=\"direct_login_test\"]').click();
    }
}
</script>

</body>
</html>";
?>
