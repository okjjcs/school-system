<?php
require_once 'config/config.php';

// إعادة توجيه المستخدم المسجل دخوله بالفعل
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ''); // لا نستخدم sanitize على اسم المستخدم
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        try {
            $stmt = $db->query("SELECT * FROM users WHERE username = ? AND is_active = 1", [$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // تشخيص مفصل لمشكلة تسجيل الدخول
            if ($user) {
                error_log("محاولة تسجيل دخول - المستخدم موجود: " . $user['username']);
                error_log("كلمة المرور المحفوظة: " . substr($user['password'], 0, 20) . "...");
                error_log("كلمة المرور المدخلة: " . $password);

                // التحقق من كلمة المرور
                $passwordValid = password_verify($password, $user['password']);
                error_log("نتيجة التحقق من كلمة المرور: " . ($passwordValid ? 'صحيحة' : 'خاطئة'));

                // إذا فشل التحقق، جرب مقارنة مباشرة (للكلمات غير المشفرة)
                if (!$passwordValid && $password === $user['password']) {
                    error_log("كلمة المرور غير مشفرة - سيتم إصلاحها");

                    // إعادة تشفير كلمة المرور
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $user['id']]);

                    $passwordValid = true;
                    error_log("تم إصلاح كلمة المرور وإعادة تشفيرها");
                }
            } else {
                error_log("محاولة تسجيل دخول فاشلة - المستخدم غير موجود: " . $username);
            }

            if ($user && $passwordValid) {
                // تسجيل الدخول بنجاح
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // تحديث وقت آخر دخول
                $db->query("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$user['id']]);
                
                // إذا كان المستخدم معلماً، تحديث حالة الحضور
                if ($user['role'] === 'teacher') {
                    $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$user['id']]);
                    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($teacher) {
                        $_SESSION['teacher_id'] = $teacher['id'];
                        
                        // تسجيل الحضور
                        $today = date('Y-m-d');
                        $currentTime = date('H:i:s');
                        
                        $stmt = $db->query("SELECT id FROM attendance WHERE teacher_id = ? AND attendance_date = ?", 
                                         [$teacher['id'], $today]);
                        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$attendance) {
                            $db->query("INSERT INTO attendance (teacher_id, attendance_date, check_in_time, status) VALUES (?, ?, ?, 'present')", 
                                     [$teacher['id'], $today, $currentTime]);
                        }
                        
                        // تحديث حالة الحضور في جدول المعلمين
                        $db->query("UPDATE teachers SET is_present = 1 WHERE id = ?", [$teacher['id']]);
                    }
                }
                
                // تذكر المستخدم إذا طلب ذلك
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 يوم
                    // يمكن حفظ الرمز في قاعدة البيانات للتحقق لاحقاً
                }
                
                showMessage('مرحباً بك، تم تسجيل الدخول بنجاح', 'success');
                redirect('index.php');
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } catch (Exception $e) {
            $error = 'خطأ في النظام، يرجى المحاولة لاحقاً';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تسجيل الدخول</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 40px;
        }
        
        .login-form {
            padding: 40px;
        }
        
        .login-logo {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
        }
        
        .input-group .form-control {
            border-right: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .login-image {
                display: none;
            }
            
            .login-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="login-container">
                    <div class="row g-0">
                        <!-- صورة تسجيل الدخول -->
                        <div class="col-lg-6">
                            <div class="login-image h-100">
                                <div>
                                    <div class="login-logo">
                                        <i class="fas fa-school"></i>
                                    </div>
                                    <h2 class="mb-3"><?php echo APP_NAME; ?></h2>
                                    <p class="lead">
                                        نظام شامل لإدارة ومتابعة أعمال الأساتذة في المدرسة
                                    </p>
                                    <div class="mt-4">
                                        <i class="fas fa-users fa-2x mb-3"></i>
                                        <p>إدارة المعلمين والأنشطة بكفاءة عالية</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- نموذج تسجيل الدخول -->
                        <div class="col-lg-6">
                            <div class="login-form">
                                <div class="text-center mb-4">
                                    <h3 class="text-gradient">تسجيل الدخول</h3>
                                    <p class="text-muted">أدخل بياناتك للوصول إلى النظام</p>
                                </div>
                                
                                <?php if (!empty($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user me-1"></i>
                                            اسم المستخدم
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                                   required 
                                                   autocomplete="username"
                                                   placeholder="أدخل اسم المستخدم">
                                            <div class="invalid-feedback">
                                                يرجى إدخال اسم المستخدم
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>
                                            كلمة المرور
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password" 
                                                   required 
                                                   autocomplete="current-password"
                                                   placeholder="أدخل كلمة المرور">
                                            <button class="btn btn-outline-secondary" 
                                                    type="button" 
                                                    id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="invalid-feedback">
                                                يرجى إدخال كلمة المرور
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="remember" 
                                                   name="remember">
                                            <label class="form-check-label" for="remember">
                                                تذكرني لمدة 30 يوماً
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-login">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            تسجيل الدخول
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="text-center mt-4">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        نظام آمن ومحمي
                                    </small>
                                </div>
                                
                                <!-- معلومات تسجيل الدخول التجريبي -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="text-center mb-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        بيانات تجريبية
                                    </h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted d-block">المدير:</small>
                                            <small><strong>admin</strong></small><br>
                                            <small><strong>admin123</strong></small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">معلم:</small>
                                            <small><strong>teacher1</strong></small><br>
                                            <small><strong>teacher123</strong></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
        
        // التحقق من صحة النموذج
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // تركيز تلقائي على حقل اسم المستخدم
        document.getElementById('username').focus();
    </script>
</body>
</html>
