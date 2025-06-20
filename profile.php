<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

// الحصول على بيانات المستخدم الحالي
$currentUser = null;
$currentTeacher = null;
$qualifications = [];
$experiences = [];

try {
    $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentUser['role'] === 'teacher') {
        $stmt = $db->query("SELECT * FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentTeacher) {
            // جلب المؤهلات
            $stmt = $db->query("SELECT * FROM qualifications WHERE teacher_id = ? ORDER BY graduation_year DESC", [$currentTeacher['id']]);
            $qualifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // جلب الخبرات
            $stmt = $db->query("SELECT * FROM experiences WHERE teacher_id = ? ORDER BY start_date DESC", [$currentTeacher['id']]);
            $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المستخدم', 'error');
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        try {
            if ($currentUser['role'] === 'teacher' && $currentTeacher) {
                $firstName = sanitize($_POST['first_name']);
                $lastName = sanitize($_POST['last_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);
                
                $db->query("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
                          [$firstName, $lastName, $email, $phone, $address, $currentTeacher['id']]);
                
                showMessage('تم تحديث البيانات بنجاح', 'success');
                redirect('profile.php');
            }
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
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-school me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    العودة للرئيسية
                </a>
                <a class="nav-link" href="logout.php">
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
            <!-- معلومات المستخدم -->
            <div class="col-lg-4 mb-4">
                <div class="card profile-card">
                    <div class="card-body text-center">
                        <div class="profile-avatar bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3">
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
                            <span class="badge bg-primary">
                                <?php echo $currentUser['role'] === 'admin' ? 'مدير' : 'معلم'; ?>
                            </span>
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
                                تاريخ التوظيف: <?php echo formatDateArabic($currentTeacher['hire_date']); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- إحصائيات سريعة للمعلم -->
                <?php if ($currentTeacher): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            إحصائيات سريعة
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // جلب الإحصائيات
                        $stmt = $db->query("SELECT COUNT(*) as count FROM daily_preparations WHERE teacher_id = ?", [$currentTeacher['id']]);
                        $preparationsCount = $stmt->fetchColumn();
                        
                        $stmt = $db->query("SELECT COUNT(*) as count FROM activities WHERE teacher_id = ?", [$currentTeacher['id']]);
                        $activitiesCount = $stmt->fetchColumn();
                        
                        $stmt = $db->query("SELECT COUNT(*) as count FROM warnings WHERE teacher_id = ? AND is_read = 0", [$currentTeacher['id']]);
                        $unreadWarnings = $stmt->fetchColumn();
                        ?>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <h4 class="text-primary"><?php echo $preparationsCount; ?></h4>
                                <small class="text-muted">تحضير</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-success"><?php echo $activitiesCount; ?></h4>
                                <small class="text-muted">نشاط</small>
                            </div>
                            <div class="col-4">
                                <h4 class="text-warning"><?php echo $unreadWarnings; ?></h4>
                                <small class="text-muted">تنويه</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
                            <?php if ($currentTeacher): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qualifications-tab" data-bs-toggle="tab" data-bs-target="#qualifications" type="button" role="tab">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    المؤهلات
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="experiences-tab" data-bs-toggle="tab" data-bs-target="#experiences" type="button" role="tab">
                                    <i class="fas fa-briefcase me-1"></i>
                                    الخبرات
                                </button>
                            </li>
                            <?php endif; ?>
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
                                    <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                                    <h5>حساب المدير</h5>
                                    <p class="text-muted">يمكنك تغيير كلمة المرور من تبويب الأمان</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- المؤهلات -->
                            <?php if ($currentTeacher): ?>
                            <div class="tab-pane fade" id="qualifications" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>المؤهلات العلمية</h6>
                                    <a href="qualifications/add.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i>
                                        إضافة مؤهل
                                    </a>
                                </div>
                                
                                <?php if (empty($qualifications)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد مؤهلات مضافة بعد</p>
                                </div>
                                <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($qualifications as $qualification): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($qualification['degree_type']); ?></h6>
                                                <p class="text-muted mb-1"><?php echo htmlspecialchars($qualification['institution']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo $qualification['graduation_year']; ?>
                                                    <?php if ($qualification['grade']): ?>
                                                    | <i class="fas fa-star me-1"></i>
                                                    <?php echo htmlspecialchars($qualification['grade']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="qualifications/edit.php?id=<?php echo $qualification['id']; ?>">تعديل</a></li>
                                                    <li><a class="dropdown-item text-danger" href="qualifications/delete.php?id=<?php echo $qualification['id']; ?>">حذف</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- الخبرات -->
                            <div class="tab-pane fade" id="experiences" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>الخبرات العملية</h6>
                                    <a href="experiences/add.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i>
                                        إضافة خبرة
                                    </a>
                                </div>
                                
                                <?php if (empty($experiences)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد خبرات مضافة بعد</p>
                                </div>
                                <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($experiences as $experience): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($experience['position']); ?></h6>
                                                <p class="text-muted mb-1"><?php echo htmlspecialchars($experience['institution']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo formatDateArabic($experience['start_date']); ?>
                                                    -
                                                    <?php echo $experience['end_date'] ? formatDateArabic($experience['end_date']) : 'حتى الآن'; ?>
                                                </small>
                                                <?php if ($experience['description']): ?>
                                                <p class="mt-2 small"><?php echo htmlspecialchars($experience['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="experiences/edit.php?id=<?php echo $experience['id']; ?>">تعديل</a></li>
                                                    <li><a class="dropdown-item text-danger" href="experiences/delete.php?id=<?php echo $experience['id']; ?>">حذف</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
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
                                            <li><small class="text-muted">وقت تسجيل الدخول:</small> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></li>
                                            <li><small class="text-muted">عنوان IP:</small> <?php echo $_SERVER['REMOTE_ADDR']; ?></li>
                                            <li><small class="text-muted">المتصفح:</small> <?php echo substr($_SERVER['HTTP_USER_AGENT'], 0, 50) . '...'; ?></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>إعدادات الأمان</h6>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="twoFactorAuth" disabled>
                                            <label class="form-check-label" for="twoFactorAuth">
                                                المصادقة الثنائية (قريباً)
                                            </label>
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
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
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
