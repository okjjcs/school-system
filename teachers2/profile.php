<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// التحقق من أن المستخدم معلم
if (!isTeacher()) {
    redirect('../index.php');
}

// الحصول على بيانات المعلم الحالي
$currentUser = null;
$currentTeacher = null;

try {
    $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentUser) {
        $stmt = $db->query("SELECT * FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المستخدم', 'error');
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile']) && $currentTeacher) {
        try {
            $firstName = sanitize($_POST['first_name']);
            $lastName = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $address = sanitize($_POST['address']);
            
            $db->query("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
                      [$firstName, $lastName, $email, $phone, $address, $currentTeacher['id']]);
            
            showMessage('تم تحديث البيانات بنجاح', 'success');
            redirect('profile.php');
        } catch (Exception $e) {
            showMessage('خطأ في تحديث البيانات', 'error');
        }
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            showMessage('يرجى ملء جميع الحقول', 'error');
        } elseif ($newPassword !== $confirmPassword) {
            showMessage('كلمات المرور الجديدة غير متطابقة', 'error');
        } elseif (strlen($newPassword) < 6) {
            showMessage('كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'error');
        } else {
            try {
                if (password_verify($currentPassword, $currentUser['password'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
                              [$hashedPassword, $_SESSION['user_id']]);
                    
                    showMessage('تم تغيير كلمة المرور بنجاح', 'success');
                    redirect('profile.php');
                } else {
                    showMessage('كلمة المرور الحالية غير صحيحة', 'error');
                }
            } catch (Exception $e) {
                showMessage('خطأ في تغيير كلمة المرور', 'error');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - الملف الشخصي</title>
    
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
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>
                    العودة للرئيسية
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php displayMessage(); ?>
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <div class="container">
                        <h1 class="page-title">
                            <i class="fas fa-user-circle me-3"></i>
                            الملف الشخصي
                        </h1>
                        <p class="page-subtitle">إدارة بياناتك الشخصية وإعدادات الحساب</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- معلومات المعلم -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-avatar bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%;">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        
                        <h4 class="profile-name">
                            <?php 
                            if ($currentTeacher) {
                                echo htmlspecialchars($currentTeacher['first_name'] . ' ' . $currentTeacher['last_name']);
                            } else {
                                echo htmlspecialchars($currentUser['username']);
                            }
                            ?>
                        </h4>
                        
                        <p class="profile-role">
                            <span class="badge bg-primary">معلم</span>
                        </p>
                        
                        <?php if ($currentTeacher): ?>
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-primary"><?php echo htmlspecialchars($currentTeacher['employee_id']); ?></h5>
                                    <small class="text-muted">رقم الموظف</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success"><?php echo htmlspecialchars($currentTeacher['subject']); ?></h5>
                                <small class="text-muted">التخصص</small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                تاريخ التوظيف: <?php echo $currentTeacher['hire_date']; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- تبويبات البيانات -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                                    <i class="fas fa-user me-1"></i>
                                    البيانات الشخصية
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                    <i class="fas fa-lock me-1"></i>
                                    الأمان
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- البيانات الشخصية -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                <?php if ($currentTeacher): ?>
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">الاسم الأول</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($currentTeacher['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">اسم العائلة</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($currentTeacher['last_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">البريد الإلكتروني</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($currentTeacher['email']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">رقم الهاتف</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($currentTeacher['phone']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">العنوان</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($currentTeacher['address']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">التخصص</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentTeacher['subject']); ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الصف المدرس</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentTeacher['grade_level']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ التغييرات
                                    </button>
                                </form>
                                <?php else: ?>
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                    <h5>لا توجد بيانات معلم</h5>
                                    <p class="text-muted">يرجى التواصل مع المدير لإضافة بياناتك</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- الأمان -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <h6 class="mb-3">تغيير كلمة المرور</h6>
                                
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                        <div class="form-text">يجب أن تكون 6 أحرف على الأقل</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>
                                        تغيير كلمة المرور
                                    </button>
                                </form>
                                
                                <hr class="my-4">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>معلومات الجلسة</h6>
                                        <ul class="list-unstyled">
                                            <li><small class="text-muted">اسم المستخدم:</small> <?php echo htmlspecialchars($currentUser['username']); ?></li>
                                            <li><small class="text-muted">وقت تسجيل الدخول:</small> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></li>
                                            <li><small class="text-muted">عنوان IP:</small> <?php echo $_SERVER['REMOTE_ADDR']; ?></li>
                                        </ul>
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
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // التحقق من تطابق كلمات المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword !== newPassword) {
                this.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
