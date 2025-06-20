<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_grade'])) {
        $name = sanitize($_POST['name']);
        $nameEn = sanitize($_POST['name_en']);
        $level = (int)$_POST['level'];
        $description = sanitize($_POST['description']);
        
        if (!empty($name) && $level > 0) {
            try {
                $stmt = $db->query("INSERT INTO grades (name, name_en, level, description) VALUES (?, ?, ?, ?)", 
                                  [$name, $nameEn, $level, $description]);
                showMessage('تم إضافة الصف بنجاح', 'success');
            } catch (Exception $e) {
                showMessage('خطأ في إضافة الصف: ' . $e->getMessage(), 'error');
            }
        } else {
            showMessage('اسم الصف والمستوى مطلوبان', 'error');
        }
    }
    
    if (isset($_POST['update_grade'])) {
        $id = (int)$_POST['grade_id'];
        $name = sanitize($_POST['name']);
        $nameEn = sanitize($_POST['name_en']);
        $level = (int)$_POST['level'];
        $description = sanitize($_POST['description']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $db->query("UPDATE grades SET name = ?, name_en = ?, level = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
                              [$name, $nameEn, $level, $description, $isActive, $id]);
            showMessage('تم تحديث الصف بنجاح', 'success');
        } catch (Exception $e) {
            showMessage('خطأ في تحديث الصف: ' . $e->getMessage(), 'error');
        }
    }
    
    if (isset($_POST['delete_grade'])) {
        $id = (int)$_POST['grade_id'];
        
        try {
            // التحقق من عدم وجود معلمين مرتبطين بهذا الصف
            $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE grade_level = (SELECT name FROM grades WHERE id = ?)", [$id]);
            $teacherCount = $stmt->fetchColumn();
            
            if ($teacherCount > 0) {
                showMessage("لا يمكن حذف هذا الصف لأنه مرتبط بـ $teacherCount معلم", 'error');
            } else {
                $stmt = $db->query("DELETE FROM grades WHERE id = ?", [$id]);
                showMessage('تم حذف الصف بنجاح', 'success');
            }
        } catch (Exception $e) {
            showMessage('خطأ في حذف الصف: ' . $e->getMessage(), 'error');
        }
    }
}

// جلب جميع الصفوف
try {
    $stmt = $db->query("SELECT * FROM grades ORDER BY level, sort_order");
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $grades = [];
    showMessage('خطأ في جلب الصفوف', 'error');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - إدارة الصفوف</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .grade-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .grade-card:hover {
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
                <a class="nav-link" href="subjects.php">
                    <i class="fas fa-book me-1"></i>
                    إدارة الاختصاصات
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
                <i class="fas fa-layer-group me-3"></i>
                إدارة الصفوف الدراسية
            </h1>
            <p class="lead mb-0">إضافة وتعديل وحذف الصفوف الدراسية</p>
        </div>

        <div class="row">
            <!-- إضافة صف جديد -->
            <div class="col-lg-4 mb-4">
                <div class="card grade-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            إضافة صف جديد
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الصف *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="مثال: الصف الأول" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name_en" class="form-label">الاسم بالإنجليزية</label>
                                <input type="text" class="form-control" id="name_en" name="name_en" 
                                       placeholder="مثال: Grade 1">
                            </div>
                            
                            <div class="mb-3">
                                <label for="level" class="form-label">المستوى *</label>
                                <input type="number" class="form-control" id="level" name="level" 
                                       min="1" max="12" placeholder="1-12" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="وصف الصف الدراسي..."></textarea>
                            </div>
                            
                            <button type="submit" name="add_grade" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>
                                إضافة الصف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- قائمة الصفوف -->
            <div class="col-lg-8">
                <div class="card grade-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الصفوف الدراسية (<?php echo count($grades); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($grades)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>الصف</th>
                                        <th>المستوى</th>
                                        <th>الاسم بالإنجليزية</th>
                                        <th>الوصف</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($grade['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $grade['level']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($grade['name_en']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($grade['description'], 0, 30)); ?><?php echo strlen($grade['description']) > 30 ? '...' : ''; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($grade['is_active']): ?>
                                                <span class="badge bg-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $grade['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="deleteGrade(<?php echo $grade['id']; ?>, '<?php echo htmlspecialchars($grade['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal تعديل الصف -->
                                    <div class="modal fade" id="editModal<?php echo $grade['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">تعديل الصف</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="grade_id" value="<?php echo $grade['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">اسم الصف *</label>
                                                            <input type="text" class="form-control" name="name" 
                                                                   value="<?php echo htmlspecialchars($grade['name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">الاسم بالإنجليزية</label>
                                                            <input type="text" class="form-control" name="name_en" 
                                                                   value="<?php echo htmlspecialchars($grade['name_en']); ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">المستوى *</label>
                                                            <input type="number" class="form-control" name="level" 
                                                                   value="<?php echo $grade['level']; ?>" min="1" max="12" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">الوصف</label>
                                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($grade['description']); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="is_active" 
                                                                   <?php echo $grade['is_active'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label">نشط</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" name="update_grade" class="btn btn-primary">حفظ التغييرات</button>
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
                            <i class="fas fa-layer-group fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد صفوف</h5>
                            <p class="text-muted">ابدأ بإضافة صف جديد</p>
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
    function deleteGrade(id, name) {
        if (confirm('هل أنت متأكد من حذف الصف "' + name + '"؟\nهذا الإجراء لا يمكن التراجع عنه.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="grade_id" value="${id}">
                <input type="hidden" name="delete_grade" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
