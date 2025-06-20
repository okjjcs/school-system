<?php
require_once '../config/config.php';

// التحقق من صلاحيات المدير
if (!isAdmin()) {
    redirect('../index.php');
}

$error = '';
$success = false;
$teacherId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$teacher = null;

// جلب بيانات المعلم
if ($teacherId > 0) {
    try {
        $stmt = $db->query("SELECT t.*, u.username FROM teachers t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = ?", [$teacherId]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            showMessage('المعلم غير موجود', 'error');
            redirect('list.php');
        }
    } catch (Exception $e) {
        showMessage('خطأ في جلب بيانات المعلم: ' . $e->getMessage(), 'error');
        redirect('list.php');
    }
}

// معالجة حذف المعلم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $deleteType = $_POST['delete_type'] ?? 'deactivate';
    
    try {
        $db->getConnection()->beginTransaction();
        
        if ($deleteType === 'permanent') {
            // حذف نهائي - حذف جميع البيانات المرتبطة
            $db->query("DELETE FROM files WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM attendance WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM warnings WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM reports WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM curriculum_progress WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM activities WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM daily_preparations WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM experiences WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM qualifications WHERE teacher_id = ?", [$teacherId]);
            $db->query("DELETE FROM teachers WHERE id = ?", [$teacherId]);
            $db->query("DELETE FROM users WHERE id = ?", [$teacher['user_id']]);
            
            $message = 'تم حذف المعلم نهائياً من النظام';
        } else {
            // إلغاء تفعيل فقط
            $db->query("UPDATE users SET is_active = 0 WHERE id = ?", [$teacher['user_id']]);
            $db->query("UPDATE teachers SET is_present = 0 WHERE id = ?", [$teacherId]);
            
            $message = 'تم إلغاء تفعيل المعلم بنجاح';
        }
        
        $db->getConnection()->commit();
        $success = true;
        showMessage($message, 'success');
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $error = 'خطأ في حذف المعلم: ' . $e->getMessage();
    }
}

// إذا لم يتم تحديد معلم، عرض قائمة المعلمين للاختيار
if (!$teacher && !$success) {
    try {
        $stmt = $db->query("SELECT t.*, u.username, u.is_active FROM teachers t 
                           JOIN users u ON t.user_id = u.id 
                           ORDER BY t.first_name, t.last_name");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $teachers = [];
        $error = 'خطأ في جلب قائمة المعلمين: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف معلم - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            background: #fff5f5;
        }
        .teacher-info {
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body class="bg-light">
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">الرئيسية</a>
                <a class="nav-link" href="list.php">قائمة المعلمين</a>
                <a class="nav-link" href="add.php">إضافة معلم</a>
                <a class="nav-link" href="../logout.php">تسجيل الخروج</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php displayMessage(); ?>
        
        <?php if ($success): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h4>تمت العملية بنجاح</h4>
                        <p class="text-muted">تم تنفيذ العملية المطلوبة بنجاح</p>
                        <div class="d-grid gap-2">
                            <a href="list.php" class="btn btn-primary">عودة لقائمة المعلمين</a>
                            <a href="../index.php" class="btn btn-outline-secondary">الصفحة الرئيسية</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($teacher): ?>
        <!-- تأكيد حذف معلم محدد -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-times me-2"></i>حذف معلم
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- معلومات المعلم -->
                        <div class="teacher-info p-4 mb-4">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>معلومات المعلم
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>الاسم:</strong> <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
                                    <p><strong>رقم الموظف:</strong> <?php echo htmlspecialchars($teacher['employee_id']); ?></p>
                                    <p><strong>اسم المستخدم:</strong> <?php echo htmlspecialchars($teacher['username']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>التخصص:</strong> <?php echo htmlspecialchars($teacher['subject']); ?></p>
                                    <p><strong>الصف:</strong> <?php echo htmlspecialchars($teacher['grade_level']); ?></p>
                                    <p><strong>تاريخ التوظيف:</strong> <?php echo formatDateArabic($teacher['hire_date']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- منطقة الخطر -->
                        <div class="danger-zone p-4">
                            <h5 class="text-danger mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>تحذير: عملية حذف
                            </h5>
                            
                            <form method="POST" id="deleteForm">
                                <div class="mb-4">
                                    <label class="form-label"><strong>نوع الحذف:</strong></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="delete_type" id="deactivate" value="deactivate" checked>
                                        <label class="form-check-label" for="deactivate">
                                            <strong>إلغاء التفعيل</strong> - إيقاف الحساب مع الاحتفاظ بجميع البيانات
                                        </label>
                                        <small class="text-muted d-block">يمكن إعادة تفعيل الحساب لاحقاً</small>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="delete_type" id="permanent" value="permanent">
                                        <label class="form-check-label" for="permanent">
                                            <strong class="text-danger">حذف نهائي</strong> - حذف جميع البيانات نهائياً
                                        </label>
                                        <small class="text-danger d-block">تحذير: لا يمكن التراجع عن هذه العملية</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                                        <label class="form-check-label" for="confirmDelete">
                                            أؤكد أنني أريد تنفيذ هذه العملية وأتحمل المسؤولية الكاملة
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="submit" name="confirm_delete" class="btn btn-danger" id="deleteBtn" disabled>
                                            <i class="fas fa-trash me-2"></i>تأكيد الحذف
                                        </button>
                                    </div>
                                    <div>
                                        <a href="list.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>إلغاء
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- اختيار معلم للحذف -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="fas fa-user-times me-2"></i>اختيار معلم للحذف
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($teachers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">لا يوجد معلمون</h4>
                            <p class="text-muted">لا يوجد معلمون مسجلون في النظام</p>
                            <a href="add.php" class="btn btn-primary">إضافة معلم جديد</a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            اختر المعلم الذي تريد حذفه من القائمة أدناه
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>الاسم</th>
                                        <th>رقم الموظف</th>
                                        <th>اسم المستخدم</th>
                                        <th>التخصص</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($t['employee_id']); ?></td>
                                        <td><?php echo htmlspecialchars($t['username']); ?></td>
                                        <td><?php echo htmlspecialchars($t['subject']); ?></td>
                                        <td>
                                            <?php if ($t['is_active']): ?>
                                            <span class="badge bg-success">نشط</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="remove.php?id=<?php echo $t['id']; ?>" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash me-1"></i>حذف
                                            </a>
                                            <a href="profile.php?id=<?php echo $t['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye me-1"></i>عرض
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تفعيل/إلغاء تفعيل زر الحذف حسب التأكيد
        document.getElementById('confirmDelete')?.addEventListener('change', function() {
            document.getElementById('deleteBtn').disabled = !this.checked;
        });
        
        // تأكيد إضافي للحذف النهائي
        document.getElementById('deleteForm')?.addEventListener('submit', function(e) {
            const deleteType = document.querySelector('input[name="delete_type"]:checked').value;
            
            if (deleteType === 'permanent') {
                if (!confirm('تحذير: هذا حذف نهائي ولا يمكن التراجع عنه. هل أنت متأكد؟')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
