<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// الحصول على معرف المعلم
$teacherId = (int)($_GET['id'] ?? 0);
if ($teacherId <= 0) {
    showMessage('معرف المعلم غير صحيح', 'error');
    redirect('list.php');
}

// جلب بيانات المعلم
try {
    $stmt = $db->query("SELECT t.*, u.username, u.is_active, u.created_at as user_created_at 
                       FROM teachers t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?", [$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        showMessage('لم يتم العثور على المعلم المطلوب', 'error');
        redirect('list.php');
    }
    
    // جلب إحصائيات المعلم
    $stats = [];
    
    // عدد التحضيرات
    $stmt = $db->query("SELECT COUNT(*) FROM daily_preparations WHERE teacher_id = ?", [$teacherId]);
    $stats['preparations'] = $stmt->fetchColumn();
    
    // عدد الأنشطة
    $stmt = $db->query("SELECT COUNT(*) FROM activities WHERE teacher_id = ?", [$teacherId]);
    $stats['activities'] = $stmt->fetchColumn();
    
    // عدد وحدات المنهج
    $stmt = $db->query("SELECT COUNT(*) FROM curriculum_progress WHERE teacher_id = ?", [$teacherId]);
    $stats['curriculum'] = $stmt->fetchColumn();
    
    // عدد الملفات
    $stmt = $db->query("SELECT COUNT(*) FROM files WHERE teacher_id = ?", [$teacherId]);
    $stats['files'] = $stmt->fetchColumn();
    
    // عدد التنويهات
    $stmt = $db->query("SELECT COUNT(*) FROM warnings WHERE teacher_id = ?", [$teacherId]);
    $stats['warnings'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلم', 'error');
    redirect('list.php');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تفاصيل المعلم</title>
    
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
                            <i class="fas fa-user me-3"></i>
                            تفاصيل المعلم
                        </h1>
                        <p class="page-subtitle"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- البيانات الشخصية -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-id-card me-2"></i>
                            البيانات الشخصية
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>الاسم الأول:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['first_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>الاسم الأخير:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['last_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>رقم الموظف:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['employee_id']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>البريد الإلكتروني:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['email']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>رقم الهاتف:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['phone']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>تاريخ الميلاد:</strong>
                                <p class="mb-0"><?php echo $teacher['birth_date'] ? formatDateArabic($teacher['birth_date']) : '-'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- البيانات المهنية -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-briefcase me-2"></i>
                            البيانات المهنية
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>المادة:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['subject']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>الصف:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['grade_level']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>تاريخ التوظيف:</strong>
                                <p class="mb-0"><?php echo $teacher['hire_date'] ? formatDateArabic($teacher['hire_date']) : '-'; ?></p>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- معلومات الحساب -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-cog me-2"></i>
                            معلومات الحساب
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($teacher['username']): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>اسم المستخدم:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($teacher['username']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>حالة الحساب:</strong>
                                <p class="mb-0">
                                    <?php if ($teacher['is_active']): ?>
                                    <span class="badge bg-success">نشط</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">غير نشط</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>تاريخ إنشاء الحساب:</strong>
                                <p class="mb-0"><?php echo formatDateArabic($teacher['user_created_at']); ?></p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            لا يوجد حساب مستخدم مرتبط بهذا المعلم
                            <a href="../create_user_account.php?teacher_id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-primary ms-2">
                                إنشاء حساب
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- الإحصائيات والإجراءات -->
            <div class="col-lg-4">
                <!-- الصورة الشخصية -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="avatar-xl bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($teacher['subject']); ?></p>
                        <p class="text-muted"><?php echo htmlspecialchars($teacher['employee_id']); ?></p>
                    </div>
                </div>

                <!-- الإحصائيات -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            الإحصائيات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="text-primary"><?php echo $stats['preparations']; ?></h4>
                                <small class="text-muted">التحضيرات</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-success"><?php echo $stats['activities']; ?></h4>
                                <small class="text-muted">الأنشطة</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-info"><?php echo $stats['curriculum']; ?></h4>
                                <small class="text-muted">وحدات المنهج</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-warning"><?php echo $stats['files']; ?></h4>
                                <small class="text-muted">الملفات</small>
                            </div>
                            <div class="col-12">
                                <h4 class="text-danger"><?php echo $stats['warnings']; ?></h4>
                                <small class="text-muted">التنويهات</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الإجراءات -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            الإجراءات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>
                                تعديل البيانات
                            </a>
                            
                            <?php if (!$teacher['username']): ?>
                            <a href="../create_user_account.php?teacher_id=<?php echo $teacher['id']; ?>" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i>
                                إنشاء حساب
                            </a>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-danger btn-delete" 
                                    data-teacher-id="<?php echo $teacher['id']; ?>"
                                    data-item-name="<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>">
                                <i class="fas fa-trash me-2"></i>
                                حذف المعلم
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // معالجة حذف المعلم
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete')) {
                e.preventDefault();
                const button = e.target.closest('.btn-delete');
                const teacherId = button.dataset.teacherId;
                const teacherName = button.dataset.itemName;
                
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: `هل أنت متأكد من حذف المعلم "${teacherName}"؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `delete.php?id=${teacherId}`;
                    }
                });
            }
        });
    </script>
</body>
</html>
