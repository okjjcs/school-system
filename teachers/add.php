<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// جلب الاختصاصات والصفوف من قاعدة البيانات
$subjects = getActiveSubjects($db);
$grades = getActiveGrades($db);

$errors = [];

// معالجة إضافة المعلم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من البيانات
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $employeeId = sanitize($_POST['employee_id'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $gradeLevel = sanitize($_POST['grade_level'] ?? '');
    $hireDate = sanitize($_POST['hire_date'] ?? '');
    $birthDate = sanitize($_POST['birth_date'] ?? '');
    $nationalId = sanitize($_POST['national_id'] ?? '');
    
    // التحقق من صحة البيانات
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
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
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
    
    // التحقق من عدم تكرار اسم المستخدم ورقم الموظف
    if (empty($errors)) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = ?", [$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'اسم المستخدم موجود بالفعل';
            }

            $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE employee_id = ?", [$employeeId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'رقم الموظف موجود بالفعل';
            }

            if (!empty($email)) {
                $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE email = ?", [$email]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'البريد الإلكتروني موجود بالفعل';
                }
            }

        } catch (Exception $e) {
            $errors[] = 'خطأ في التحقق من البيانات';
        }
    }
    
    // إضافة المعلم إذا لم توجد أخطاء
    if (empty($errors)) {
        try {
            // بدء المعاملة
            $db->getConnection()->beginTransaction();

            // تشفير كلمة المرور
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

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

            // إضافة المعلم
            $stmt = $db->query("INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, phone, address, subject, grade_level, hire_date, birth_date, national_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
                              [$userId, $employeeId, $firstName, $lastName, $email, $phone, $address, $subject, $gradeLevel, $hireDate, $birthDate, $nationalId]);

            if (!$stmt) {
                throw new Exception('فشل في إدراج المعلم');
            }

            $teacherId = $db->lastInsertId();
            if (!$teacherId) {
                throw new Exception('لم يتم الحصول على ID المعلم');
            }

            // تأكيد المعاملة
            $db->getConnection()->commit();

            // رسالة النجاح
            $successMessage = "تم إضافة المعلم بنجاح!<br>";
            $successMessage .= "اسم المستخدم: <strong>$username</strong><br>";
            $successMessage .= "كلمة المرور: <strong>$password</strong><br>";
            $successMessage .= "يمكنك الآن تسجيل الدخول بهذه البيانات.";

            showMessage($successMessage, 'success');

            // إعادة التوجيه بعد 3 ثوان
            echo "<script>
            setTimeout(function() {
                if (confirm('تم إنشاء المعلم بنجاح!\\nاسم المستخدم: $username\\nكلمة المرور: $password\\n\\nهل تريد الذهاب لقائمة المعلمين؟')) {
                    window.location.href = 'list.php';
                }
            }, 3000);
            </script>";

        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            $errors[] = 'خطأ في إضافة المعلم: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - إضافة معلم جديد</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-school me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="list.php">
                    <i class="fas fa-arrow-right me-1"></i>
                    العودة للقائمة
                </a>
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>
                    الرئيسية
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <div class="container">
                        <h1 class="page-title">
                            <i class="fas fa-user-plus me-3"></i>
                            إضافة معلم جديد
                        </h1>
                        <p class="page-subtitle">إضافة معلم جديد إلى النظام مع جميع البيانات المطلوبة</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض الأخطاء -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>يرجى تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- نموذج إضافة المعلم -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            بيانات المعلم
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- بيانات تسجيل الدخول -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-key me-2"></i>
                                        بيانات تسجيل الدخول
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">اسم المستخدم *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                           required minlength="3">
                                    <div class="form-text">سيستخدم للدخول إلى النظام</div>
                                    <div class="invalid-feedback">يرجى إدخال اسم مستخدم صحيح (3 أحرف على الأقل)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="employee_id" class="form-label">رقم الموظف *</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                           value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">رقم فريد للموظف</div>
                                    <div class="invalid-feedback">يرجى إدخال رقم الموظف</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">كلمة المرور *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">6 أحرف على الأقل</div>
                                    <div class="invalid-feedback">يرجى إدخال كلمة مرور (6 أحرف على الأقل)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           required>
                                    <div class="invalid-feedback">يرجى تأكيد كلمة المرور</div>
                                </div>
                            </div>

                            <!-- البيانات الشخصية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-user me-2"></i>
                                        البيانات الشخصية
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">الاسم الأول *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال الاسم الأول</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">اسم العائلة *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال اسم العائلة</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label">تاريخ الميلاد</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                           value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="national_id" class="form-label">رقم الهوية</label>
                                    <input type="text" class="form-control" id="national_id" name="national_id" 
                                           value="<?php echo htmlspecialchars($_POST['national_id'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- بيانات الاتصال -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-address-book me-2"></i>
                                        بيانات الاتصال
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">العنوان</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- البيانات الوظيفية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-briefcase me-2"></i>
                                        البيانات الوظيفية
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">التخصص *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">اختر التخصص</option>
                                        <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['name']); ?>"
                                                <?php echo ($_POST['subject'] ?? '') === $subject['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                            <?php if (!empty($subject['name_en'])): ?>
                                                (<?php echo htmlspecialchars($subject['name_en']); ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار التخصص</div>
                                    <div class="form-text">
                                        <a href="../admin/subjects.php" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-cog me-1"></i>إدارة الاختصاصات
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="grade_level" class="form-label">الصف المدرس *</label>
                                    <select class="form-select" id="grade_level" name="grade_level" required>
                                        <option value="">اختر الصف</option>
                                        <?php foreach ($grades as $grade): ?>
                                        <option value="<?php echo htmlspecialchars($grade['name']); ?>"
                                                <?php echo ($_POST['grade_level'] ?? '') === $grade['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grade['name']); ?>
                                            <?php if (!empty($grade['name_en'])): ?>
                                                (<?php echo htmlspecialchars($grade['name_en']); ?>)
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار الصف المدرس</div>
                                    <div class="form-text">
                                        <a href="../admin/grades.php" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-cog me-1"></i>إدارة الصفوف
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label">تاريخ التوظيف *</label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                           value="<?php echo htmlspecialchars($_POST['hire_date'] ?? ''); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ التوظيف</div>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="row">
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>
                                                حفظ المعلم
                                            </button>
                                            <button type="reset" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-undo me-2"></i>
                                                إعادة تعيين
                                            </button>
                                        </div>
                                        <div>
                                            <a href="list.php" class="btn btn-outline-secondary btn-lg">
                                                <i class="fas fa-times me-2"></i>
                                                إلغاء
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // تبديل إظهار/إخفاء كلمة المرور
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // التحقق من تطابق كلمات المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword !== password) {
                this.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // توليد اسم مستخدم تلقائي
        function generateUsername() {
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            const employeeId = document.getElementById('employee_id').value;
            
            if (firstName && lastName) {
                const username = firstName.toLowerCase() + '.' + lastName.toLowerCase();
                document.getElementById('username').value = username;
            } else if (employeeId) {
                document.getElementById('username').value = 'teacher' + employeeId;
            }
        }
        
        // ربط الأحداث
        document.getElementById('first_name').addEventListener('blur', generateUsername);
        document.getElementById('last_name').addEventListener('blur', generateUsername);
        document.getElementById('employee_id').addEventListener('blur', generateUsername);
    </script>
</body>
</html>
