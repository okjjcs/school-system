<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// معالجة البحث والتصفية
$search = sanitize($_GET['search'] ?? '');
$subject_filter = sanitize($_GET['subject'] ?? '');
$grade_filter = sanitize($_GET['grade'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

// بناء استعلام البحث
$sql = "SELECT t.*, u.username, u.is_active 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (t.first_name LIKE ? OR t.last_name LIKE ? OR t.employee_id LIKE ? OR t.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($subject_filter)) {
    $sql .= " AND t.subject = ?";
    $params[] = $subject_filter;
}

if (!empty($grade_filter)) {
    $sql .= " AND t.grade_level = ?";
    $params[] = $grade_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'present') {
        $sql .= " AND t.is_present = 1";
    } elseif ($status_filter === 'absent') {
        $sql .= " AND t.is_present = 0";
    } elseif ($status_filter === 'active') {
        $sql .= " AND u.is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $sql .= " AND u.is_active = 0";
    }
}

$sql .= " ORDER BY t.first_name, t.last_name";

try {
    $stmt = $db->query($sql, $params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب قائمة التخصصات والصفوف للتصفية
    $stmt = $db->query("SELECT DISTINCT subject FROM teachers WHERE subject IS NOT NULL ORDER BY subject");
    $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->query("SELECT DISTINCT grade_level FROM teachers WHERE grade_level IS NOT NULL ORDER BY grade_level");
    $grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلمين', 'error');
    $teachers = [];
    $subjects = [];
    $grades = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - قائمة المعلمين</title>
    
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
                    الرئيسية
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
                            <i class="fas fa-users me-3"></i>
                            قائمة المعلمين
                        </h1>
                        <p class="page-subtitle">إدارة ومتابعة جميع المعلمين في المدرسة</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo count($teachers); ?></h4>
                        <p class="text-muted mb-0">إجمالي المعلمين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                        <?php
                        $presentCount = array_filter($teachers, function($t) { return $t['is_present'] == 1; });
                        ?>
                        <h4 class="text-success"><?php echo count($presentCount); ?></h4>
                        <p class="text-muted mb-0">حاضرون اليوم</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-times fa-3x text-warning mb-3"></i>
                        <?php
                        $absentCount = array_filter($teachers, function($t) { return $t['is_present'] == 0; });
                        ?>
                        <h4 class="text-warning"><?php echo count($absentCount); ?></h4>
                        <p class="text-muted mb-0">غائبون اليوم</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-3x text-info mb-3"></i>
                        <?php
                        $activeCount = array_filter($teachers, function($t) { return $t['is_active'] == 1; });
                        ?>
                        <h4 class="text-info"><?php echo count($activeCount); ?></h4>
                        <p class="text-muted mb-0">نشطون</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- أدوات البحث والتصفية -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-search me-2"></i>
                    البحث والتصفية
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">البحث</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="اسم، رقم موظف، أو بريد إلكتروني">
                    </div>
                    <div class="col-md-3">
                        <label for="subject_filter" class="form-label">التخصص</label>
                        <select class="form-select" id="subject_filter" name="subject">
                            <option value="">جميع التخصصات</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject); ?>" 
                                    <?php echo $subject_filter === $subject ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="grade_filter" class="form-label">الصف</label>
                        <select class="form-select" id="grade_filter" name="grade">
                            <option value="">جميع الصفوف</option>
                            <?php foreach ($grades as $grade): ?>
                            <option value="<?php echo htmlspecialchars($grade); ?>" 
                                    <?php echo $grade_filter === $grade ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grade); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">الحالة</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="present" <?php echo $status_filter === 'present' ? 'selected' : ''; ?>>حاضر</option>
                            <option value="absent" <?php echo $status_filter === 'absent' ? 'selected' : ''; ?>>غائب</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>نشط</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            بحث
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>
                            إعادة تعيين
                        </a>
                        <a href="add.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>
                            إضافة معلم جديد
                        </a>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-2"></i>
                                تصدير
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?format=excel">Excel</a></li>
                                <li><a class="dropdown-item" href="export.php?format=pdf">PDF</a></li>
                                <li><a class="dropdown-item" href="export.php?format=csv">CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- جدول المعلمين -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة المعلمين (<?php echo count($teachers); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($teachers)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد نتائج</h5>
                    <p class="text-muted">لم يتم العثور على معلمين يطابقون معايير البحث</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        إضافة معلم جديد
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-numbered">
                        <thead>
                            <tr>
                                <th class="sortable" data-column="0">#</th>
                                <th class="sortable" data-column="1">رقم الموظف</th>
                                <th class="sortable" data-column="2">الاسم</th>
                                <th class="sortable" data-column="3">التخصص</th>
                                <th class="sortable" data-column="4">الصف</th>
                                <th class="sortable" data-column="5">الهاتف</th>
                                <th>الحضور</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $index => $teacher): ?>
                            <tr>
                                <td class="row-number"><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($teacher['employee_id']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($teacher['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($teacher['subject']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($teacher['grade_level']); ?></td>
                                <td>
                                    <?php if ($teacher['phone']): ?>
                                    <a href="tel:<?php echo $teacher['phone']; ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($teacher['phone']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">غير محدد</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input attendance-toggle" 
                                               type="checkbox" 
                                               data-teacher-id="<?php echo $teacher['id']; ?>"
                                               <?php echo $teacher['is_present'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            <?php echo $teacher['is_present'] ? 'حاضر' : 'غائب'; ?>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($teacher['is_active']): ?>
                                    <span class="badge bg-success">نشط</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">غير نشط</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $teacher['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           data-bs-toggle="tooltip" 
                                           title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $teacher['id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" 
                                           data-bs-toggle="tooltip" 
                                           title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $teacher['id']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('هل أنت متأكد من حذف المعلم <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>؟')"
                                           data-bs-toggle="tooltip"
                                           title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>

        // تحديث حالة الحضور
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('attendance-toggle')) {
                const teacherId = e.target.dataset.teacherId;
                const isPresent = e.target.checked;
                
                fetch('../ajax/update_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        teacher_id: teacherId,
                        is_present: isPresent
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const label = e.target.nextElementSibling;
                        label.textContent = isPresent ? 'حاضر' : 'غائب';
                        
                        showToast('تم تحديث حالة الحضور بنجاح', 'success');
                    } else {
                        showToast('خطأ في تحديث حالة الحضور', 'error');
                        e.target.checked = !isPresent;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('خطأ في الاتصال', 'error');
                    e.target.checked = !isPresent;
                });
            }
        });
    </script>
    
    <style>
        .avatar-sm {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .sortable {
            cursor: pointer;
            user-select: none;
        }
        
        .sortable:hover {
            background-color: rgba(0,0,0,0.05);
        }
        
        .sortable.sort-asc::after {
            content: ' ↑';
            color: #007bff;
        }
        
        .sortable.sort-desc::after {
            content: ' ↓';
            color: #007bff;
        }
    </style>
</body>
</html>
