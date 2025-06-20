<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $name = sanitize($_POST['name']);
        $nameEn = sanitize($_POST['name_en']);
        $description = sanitize($_POST['description']);
        
        if (!empty($name)) {
            try {
                $stmt = $db->query("INSERT INTO subjects (name, name_en, description) VALUES (?, ?, ?)", 
                                  [$name, $nameEn, $description]);
                showMessage('تم إضافة الاختصاص بنجاح', 'success');
            } catch (Exception $e) {
                showMessage('خطأ في إضافة الاختصاص: ' . $e->getMessage(), 'error');
            }
        } else {
            showMessage('اسم الاختصاص مطلوب', 'error');
        }
    }
    
    if (isset($_POST['update_subject'])) {
        $id = (int)$_POST['subject_id'];
        $name = sanitize($_POST['name']);
        $nameEn = sanitize($_POST['name_en']);
        $description = sanitize($_POST['description']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $db->query("UPDATE subjects SET name = ?, name_en = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
                              [$name, $nameEn, $description, $isActive, $id]);
            showMessage('تم تحديث الاختصاص بنجاح', 'success');
        } catch (Exception $e) {
            showMessage('خطأ في تحديث الاختصاص: ' . $e->getMessage(), 'error');
        }
    }
    
    if (isset($_POST['delete_subject'])) {
        $id = (int)$_POST['subject_id'];
        
        try {
            // التحقق من عدم وجود معلمين مرتبطين بهذا الاختصاص
            $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE subject = (SELECT name FROM subjects WHERE id = ?)", [$id]);
            $teacherCount = $stmt->fetchColumn();
            
            if ($teacherCount > 0) {
                showMessage("لا يمكن حذف هذا الاختصاص لأنه مرتبط بـ $teacherCount معلم", 'error');
            } else {
                $stmt = $db->query("DELETE FROM subjects WHERE id = ?", [$id]);
                showMessage('تم حذف الاختصاص بنجاح', 'success');
            }
        } catch (Exception $e) {
            showMessage('خطأ في حذف الاختصاص: ' . $e->getMessage(), 'error');
        }
    }
}

// جلب جميع الاختصاصات
try {
    $stmt = $db->query("SELECT * FROM subjects ORDER BY sort_order, name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $subjects = [];
    showMessage('خطأ في جلب الاختصاصات', 'error');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - إدارة الاختصاصات</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .subject-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .subject-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>
                    الرئيسية
                </a>
                <a class="nav-link" href="grades.php">
                    <i class="fas fa-layer-group me-1"></i>
                    إدارة الصفوف
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
        <div class="page-header text-center">
            <h1 class="display-5 mb-3">
                <i class="fas fa-book me-3"></i>
                إدارة الاختصاصات
            </h1>
            <p class="lead mb-0">إضافة وتعديل وحذف اختصاصات المعلمين</p>
        </div>

        <div class="row">
            <!-- إضافة اختصاص جديد -->
            <div class="col-lg-4 mb-4">
                <div class="card subject-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            إضافة اختصاص جديد
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الاختصاص *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name_en" class="form-label">الاسم بالإنجليزية</label>
                                <input type="text" class="form-control" id="name_en" name="name_en">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" name="add_subject" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>
                                إضافة الاختصاص
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- قائمة الاختصاصات -->
            <div class="col-lg-8">
                <div class="card subject-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الاختصاصات (<?php echo count($subjects); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($subjects)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>الاختصاص</th>
                                        <th>الاسم بالإنجليزية</th>
                                        <th>الوصف</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($subject['name_en']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($subject['description'], 0, 50)); ?><?php echo strlen($subject['description']) > 50 ? '...' : ''; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($subject['is_active']): ?>
                                                <span class="badge bg-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $subject['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="deleteSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal تعديل الاختصاص -->
                                    <div class="modal fade" id="editModal<?php echo $subject['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">تعديل الاختصاص</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">اسم الاختصاص *</label>
                                                            <input type="text" class="form-control" name="name" 
                                                                   value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">الاسم بالإنجليزية</label>
                                                            <input type="text" class="form-control" name="name_en" 
                                                                   value="<?php echo htmlspecialchars($subject['name_en']); ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">الوصف</label>
                                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="is_active" 
                                                                   <?php echo $subject['is_active'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label">نشط</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" name="update_subject" class="btn btn-primary">حفظ التغييرات</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد اختصاصات</h5>
                            <p class="text-muted">ابدأ بإضافة اختصاص جديد</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function deleteSubject(id, name) {
        if (confirm('هل أنت متأكد من حذف الاختصاص "' + name + '"؟\nهذا الإجراء لا يمكن التراجع عنه.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="subject_id" value="${id}">
                <input type="hidden" name="delete_subject" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
