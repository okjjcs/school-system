<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// الحصول على معرف النشاط
$activityId = (int)($_GET['id'] ?? 0);
if ($activityId <= 0) {
    showMessage('معرف النشاط غير صحيح', 'error');
    redirect('list.php');
}

// جلب بيانات النشاط
try {
    $sql = "SELECT a.*, t.first_name, t.last_name, t.employee_id, t.subject 
            FROM activities a 
            JOIN teachers t ON a.teacher_id = t.id 
            WHERE a.id = ?";
    
    // إذا كان المستخدم معلماً، تأكد من أن النشاط خاص به
    if (isTeacher()) {
        $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentTeacher) {
            $sql .= " AND a.teacher_id = ?";
            $stmt = $db->query($sql, [$activityId, $currentTeacher['id']]);
        } else {
            throw new Exception('لم يتم العثور على بيانات المعلم');
        }
    } else {
        $stmt = $db->query($sql, [$activityId]);
    }
    
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        showMessage('لم يتم العثور على النشاط المطلوب', 'error');
        redirect(isTeacher() ? 'my.php' : 'list.php');
    }
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات النشاط', 'error');
    redirect(isTeacher() ? 'my.php' : 'list.php');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تفاصيل النشاط</title>
    
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
                <a class="nav-link" href="<?php echo isTeacher() ? 'my.php' : 'list.php'; ?>">
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
                            <i class="fas fa-trophy me-3"></i>
                            تفاصيل النشاط
                        </h1>
                        <p class="page-subtitle"><?php echo htmlspecialchars($activity['title']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- تفاصيل النشاط -->
        <div class="row">
            <div class="col-lg-8">
                <!-- المعلومات الأساسية -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            المعلومات الأساسية
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>عنوان النشاط:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($activity['title']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>نوع النشاط:</strong>
                                <p class="mb-0">
                                    <?php
                                    $typeIcons = [
                                        'competition' => 'fas fa-trophy text-warning',
                                        'activity' => 'fas fa-star text-info',
                                        'project' => 'fas fa-project-diagram text-success'
                                    ];
                                    $typeNames = [
                                        'competition' => 'مسابقة',
                                        'activity' => 'نشاط',
                                        'project' => 'مشروع'
                                    ];
                                    ?>
                                    <i class="<?php echo $typeIcons[$activity['activity_type']] ?? 'fas fa-circle'; ?> me-1"></i>
                                    <?php echo $typeNames[$activity['activity_type']] ?? $activity['activity_type']; ?>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>الصف المستهدف:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($activity['target_grade'] ?: 'جميع الصفوف'); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>الحالة:</strong>
                                <p class="mb-0">
                                    <?php
                                    $statusClasses = [
                                        'planned' => 'badge bg-secondary',
                                        'ongoing' => 'badge bg-warning',
                                        'completed' => 'badge bg-success',
                                        'cancelled' => 'badge bg-danger'
                                    ];
                                    $statusNames = [
                                        'planned' => 'مخطط',
                                        'ongoing' => 'جاري',
                                        'completed' => 'مكتمل',
                                        'cancelled' => 'ملغي'
                                    ];
                                    ?>
                                    <span class="<?php echo $statusClasses[$activity['status']] ?? 'badge bg-secondary'; ?>">
                                        <?php echo $statusNames[$activity['status']] ?? $activity['status']; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- وصف النشاط -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-align-left me-2"></i>
                            وصف النشاط
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($activity['description'])); ?></p>
                    </div>
                </div>

                <!-- النتائج -->
                <?php if (!empty($activity['results'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-award me-2"></i>
                            النتائج والملاحظات
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($activity['results'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- معلومات المعلم -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            معلومات المعلم
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            <?php echo strtoupper(substr($activity['first_name'], 0, 1)); ?>
                        </div>
                        <h6><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></h6>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($activity['employee_id']); ?></p>
                        <p class="text-muted"><?php echo htmlspecialchars($activity['subject']); ?></p>
                        
                        <?php if (isAdmin()): ?>
                        <a href="../teachers/view.php?id=<?php echo $activity['teacher_id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user me-1"></i>
                            ملف المعلم
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- التواريخ والإحصائيات -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar me-2"></i>
                            التواريخ والإحصائيات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>تاريخ البداية:</strong>
                            <p class="mb-0"><?php echo $activity['start_date'] ? formatDateArabic($activity['start_date']) : 'غير محدد'; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>تاريخ النهاية:</strong>
                            <p class="mb-0"><?php echo $activity['end_date'] ? formatDateArabic($activity['end_date']) : 'غير محدد'; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong>عدد المشاركين:</strong>
                            <p class="mb-0">
                                <span class="badge bg-primary fs-6"><?php echo $activity['participants_count']; ?></span>
                                مشارك
                            </p>
                        </div>
                        
                        <div class="mb-0">
                            <strong>تاريخ الإنشاء:</strong>
                            <p class="mb-0"><?php echo formatDateArabic($activity['created_at']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- الإجراءات -->
                <?php if (isTeacher()): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            الإجراءات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="edit.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>
                                تعديل النشاط
                            </a>
                            
                            <button type="button" class="btn btn-danger btn-delete" 
                                    data-activity-id="<?php echo $activity['id']; ?>"
                                    data-item-name="<?php echo htmlspecialchars($activity['title']); ?>">
                                <i class="fas fa-trash me-2"></i>
                                حذف النشاط
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
        // معالجة حذف النشاط
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete')) {
                e.preventDefault();
                const button = e.target.closest('.btn-delete');
                const activityId = button.dataset.activityId;
                const itemName = button.dataset.itemName;
                
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: `هل أنت متأكد من حذف النشاط "${itemName}"؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `delete.php?id=${activityId}`;
                    }
                });
            }
        });
    </script>
</body>
</html>
