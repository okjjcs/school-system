<?php
require_once '../config/config.php';

// تفعيل عرض الأخطاء للتشخيص
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$success = false;
$createdUsername = '';
$createdPassword = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h5>🔍 تتبع مفصل لعملية إنشاء المعلم:</h5>";
    
    // جمع البيانات
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $employeeId = trim($_POST['employee_id'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $subject = $_POST['subject'] ?? '';
    $gradeLevel = $_POST['grade_level'] ?? '';
    $hireDate = $_POST['hire_date'] ?? '';
    $birthDate = $_POST['birth_date'] ?? '';
    $nationalId = trim($_POST['national_id'] ?? '');
    
    echo "<p>✅ تم جمع البيانات من النموذج</p>";
    echo "<p><strong>اسم المستخدم:</strong> '$username' (طول: " . strlen($username) . ")</p>";
    echo "<p><strong>كلمة المرور:</strong> '$password' (طول: " . strlen($password) . ")</p>";
    
    // التحقق من البيانات
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    } elseif (strlen($username) < 3) {
        $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    } elseif (strlen($password) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'كلمات المرور غير متطابقة';
    }
    
    if (empty($employeeId)) {
        $errors[] = 'رقم الموظف مطلوب';
    }
    
    if (empty($firstName)) {
        $errors[] = 'الاسم الأول مطلوب';
    }
    
    if (empty($lastName)) {
        $errors[] = 'اسم العائلة مطلوب';
    }
    
    if (empty($subject)) {
        $errors[] = 'التخصص مطلوب';
    }
    
    if (empty($gradeLevel)) {
        $errors[] = 'الصف المدرس مطلوب';
    }
    
    if (empty($hireDate)) {
        $errors[] = 'تاريخ التوظيف مطلوب';
    }
    
    echo "<p>✅ تم التحقق من البيانات - عدد الأخطاء: " . count($errors) . "</p>";
    
    if (!empty($errors)) {
        echo "<div style='color: red;'>";
        echo "<p><strong>الأخطاء المكتشفة:</strong></p>";
        foreach ($errors as $error) {
            echo "<p>❌ $error</p>";
        }
        echo "</div>";
    }
    
    // فحص تكرار اسم المستخدم ورقم الموظف
    if (empty($errors)) {
        try {
            echo "<p>🔍 فحص تكرار اسم المستخدم...</p>";
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = ?", [$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'اسم المستخدم موجود بالفعل';
                echo "<p>❌ اسم المستخدم مكرر</p>";
            } else {
                echo "<p>✅ اسم المستخدم متاح</p>";
            }
            
            echo "<p>🔍 فحص تكرار رقم الموظف...</p>";
            $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE employee_id = ?", [$employeeId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'رقم الموظف موجود بالفعل';
                echo "<p>❌ رقم الموظف مكرر</p>";
            } else {
                echo "<p>✅ رقم الموظف متاح</p>";
            }
            
        } catch (Exception $e) {
            $errors[] = 'خطأ في فحص البيانات: ' . $e->getMessage();
            echo "<p>❌ خطأ في فحص التكرار: " . $e->getMessage() . "</p>";
        }
    }
    
    // إضافة المعلم إذا لم توجد أخطاء
    if (empty($errors)) {
        try {
            echo "<p>🔄 بدء عملية إنشاء المعلم...</p>";
            
            // بدء المعاملة
            echo "<p>📝 بدء المعاملة...</p>";
            $db->getConnection()->beginTransaction();
            
            // تشفير كلمة المرور
            echo "<p>🔐 تشفير كلمة المرور...</p>";
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            echo "<p>✅ تم التشفير: " . substr($hashedPassword, 0, 30) . "...</p>";
            
            // إضافة المستخدم
            echo "<p>👤 إدراج المستخدم في قاعدة البيانات...</p>";
            $stmt = $db->query("INSERT INTO users (username, password, role, is_active, created_at) VALUES (?, ?, 'teacher', 1, datetime('now'))", 
                              [$username, $hashedPassword]);
            $userId = $db->lastInsertId();
            
            if (!$userId) {
                throw new Exception('فشل في إنشاء المستخدم - لم يتم الحصول على ID');
            }
            
            echo "<p>✅ تم إنشاء المستخدم - ID: <strong>$userId</strong></p>";
            
            // إضافة بيانات المعلم
            echo "<p>📋 إدراج بيانات المعلم...</p>";
            $stmt = $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, phone, address, subject, grade_level, hire_date, birth_date, national_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                              [$userId, $employeeId, $firstName, $lastName, $email, $phone, $address, $subject, $gradeLevel, $hireDate, $birthDate, $nationalId]);
            
            $teacherId = $db->lastInsertId();
            if (!$teacherId) {
                throw new Exception('فشل في إنشاء ملف المعلم - لم يتم الحصول على ID');
            }
            
            echo "<p>✅ تم إنشاء ملف المعلم - ID: <strong>$teacherId</strong></p>";
            
            // تأكيد المعاملة
            echo "<p>✅ تأكيد المعاملة...</p>";
            $db->getConnection()->commit();
            
            // التحقق من وجود المستخدم بعد الحفظ
            echo "<p>🔍 التحقق من حفظ المستخدم...</p>";
            $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
            $savedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($savedUser) {
                echo "<p>✅ المستخدم محفوظ ويمكن العثور عليه!</p>";
                
                // اختبار كلمة المرور
                if (password_verify($password, $savedUser['password'])) {
                    echo "<p>✅ كلمة المرور تعمل بشكل صحيح!</p>";
                    
                    $success = true;
                    $createdUsername = $username;
                    $createdPassword = $password;
                    
                    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
                    echo "<h4>🎉 تم إنشاء المعلم بنجاح!</h4>";
                    echo "<div style='background: yellow; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
                    echo "<h5>بيانات تسجيل الدخول:</h5>";
                    echo "<p><strong>اسم المستخدم:</strong> <code style='font-size: 18px;'>$username</code></p>";
                    echo "<p><strong>كلمة المرور:</strong> <code style='font-size: 18px;'>$password</code></p>";
                    echo "</div>";
                    echo "<p>يمكنك الآن تسجيل الدخول بهذه البيانات.</p>";
                    echo "<a href='../login.php' class='btn btn-success' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>جرب تسجيل الدخول الآن</a>";
                    echo "</div>";
                    
                } else {
                    throw new Exception('كلمة المرور لا تعمل بعد الحفظ!');
                }
            } else {
                throw new Exception('المستخدم غير موجود بعد الحفظ!');
            }
            
        } catch (Exception $e) {
            // إلغاء المعاملة في حالة الخطأ
            $db->getConnection()->rollBack();
            $errors[] = 'خطأ في إضافة المعلم: ' . $e->getMessage();
            echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
            echo "<p style='color: red;'>📍 الملف: " . $e->getFile() . "</p>";
            echo "<p style='color: red;'>📍 السطر: " . $e->getLine() . "</p>";
        }
    }
    
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة معلم جديد - محسن</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            إضافة معلم جديد - محسن
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>يرجى تصحيح الأخطاء التالية:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle me-2"></i>تم إنشاء المعلم بنجاح!</h4>
                            <div class="bg-warning p-3 rounded my-3">
                                <h5>بيانات تسجيل الدخول:</h5>
                                <p><strong>اسم المستخدم:</strong> <code class="fs-5"><?php echo htmlspecialchars($createdUsername); ?></code></p>
                                <p><strong>كلمة المرور:</strong> <code class="fs-5"><?php echo htmlspecialchars($createdPassword); ?></code></p>
                            </div>
                            <a href="../login.php" class="btn btn-success btn-lg">جرب تسجيل الدخول الآن</a>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- بيانات تسجيل الدخول -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-key me-2"></i>بيانات تسجيل الدخول
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">اسم المستخدم *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                           required minlength="3">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="employee_id" class="form-label">رقم الموظف *</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                           value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">كلمة المرور *</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           required>
                                </div>
                            </div>

                            <!-- البيانات الشخصية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-user me-2"></i>البيانات الشخصية
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">الاسم الأول *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">اسم العائلة *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- البيانات الوظيفية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-briefcase me-2"></i>البيانات الوظيفية
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">التخصص *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">اختر التخصص</option>
                                        <option value="الرياضيات" <?php echo ($_POST['subject'] ?? '') === 'الرياضيات' ? 'selected' : ''; ?>>الرياضيات</option>
                                        <option value="اللغة العربية" <?php echo ($_POST['subject'] ?? '') === 'اللغة العربية' ? 'selected' : ''; ?>>اللغة العربية</option>
                                        <option value="اللغة الإنجليزية" <?php echo ($_POST['subject'] ?? '') === 'اللغة الإنجليزية' ? 'selected' : ''; ?>>اللغة الإنجليزية</option>
                                        <option value="العلوم" <?php echo ($_POST['subject'] ?? '') === 'العلوم' ? 'selected' : ''; ?>>العلوم</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="grade_level" class="form-label">الصف المدرس *</label>
                                    <select class="form-select" id="grade_level" name="grade_level" required>
                                        <option value="">اختر الصف</option>
                                        <option value="الصف الأول" <?php echo ($_POST['grade_level'] ?? '') === 'الصف الأول' ? 'selected' : ''; ?>>الصف الأول</option>
                                        <option value="الصف الثاني" <?php echo ($_POST['grade_level'] ?? '') === 'الصف الثاني' ? 'selected' : ''; ?>>الصف الثاني</option>
                                        <option value="الصف الثالث" <?php echo ($_POST['grade_level'] ?? '') === 'الصف الثالث' ? 'selected' : ''; ?>>الصف الثالث</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label">تاريخ التوظيف *</label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                           value="<?php echo htmlspecialchars($_POST['hire_date'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="row">
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>حفظ المعلم
                                            </button>
                                            <button type="reset" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-undo me-2"></i>إعادة تعيين
                                            </button>
                                        </div>
                                        <div>
                                            <a href="../index.php" class="btn btn-outline-secondary btn-lg">
                                                <i class="fas fa-home me-2"></i>الرئيسية
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
