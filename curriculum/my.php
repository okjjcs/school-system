<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المعلم
if (!isLoggedIn() || !isTeacher()) {
    redirect('../login.php');
}

// الحصول على بيانات المعلم الحالي
$currentTeacher = null;
try {
    $stmt = $db->query("SELECT * FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
    $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentTeacher) {
        showMessage('لم يتم العثور على بيانات المعلم', 'error');
        redirect('../index.php');
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلم', 'error');
    redirect('../index.php');
}

// معالجة البحث والتصفية
$search = sanitize($_GET['search'] ?? '');
$subject_filter = sanitize($_GET['subject'] ?? '');
$grade_filter = sanitize($_GET['grade'] ?? '');

// بناء استعلام البحث
$sql = "SELECT * FROM curriculum_progress WHERE teacher_id = ?";
$params = [$currentTeacher['id']];

if (!empty($search)) {
    $sql .= " AND (unit_title LIKE ? OR notes LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($subject_filter)) {
    $sql .= " AND subject = ?";
    $params[] = $subject_filter;
}

if (!empty($grade_filter)) {
    $sql .= " AND grade = ?";
    $params[] = $grade_filter;
}

$sql .= " ORDER BY subject, grade, unit_number";

try {
    $stmt = $db->query($sql, $params);
    $curriculumProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب قائمة المواد والصفوف للتصفية
    $stmt = $db->query("SELECT DISTINCT subject FROM curriculum_progress WHERE teacher_id = ? ORDER BY subject", [$currentTeacher['id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->query("SELECT DISTINCT grade FROM curriculum_progress WHERE teacher_id = ? ORDER BY grade", [$currentTeacher['id']]);
    $grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المنهج', 'error');
    $curriculumProgress = [];
    $subjects = [];
    $grades = [];
}

// حساب الإحصائيات
$totalUnits = count($curriculumProgress);
$completedUnits = array_filter($curriculumProgress, function($unit) { 
    return $unit['progress_percentage'] >= 100; 
});
$inProgressUnits = array_filter($curriculumProgress, function($unit) { 
    return $unit['progress_percentage'] > 0 && $unit['progress_percentage'] < 100; 
});
$notStartedUnits = array_filter($curriculumProgress, function($unit) { 
    return $unit['progress_percentage'] == 0; 
});

$averageProgress = $totalUnits > 0 ? array_sum(array_column($curriculumProgress, 'progress_percentage')) / $totalUnits : 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - متابعة المنهج</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }
        
        .unit-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        
        .unit-card.completed {
            border-left-color: #28a745;
        }
        
        .unit-card.in-progress {
            border-left-color: #ffc107;
        }
        
        .unit-card.not-started {
            border-left-color: #6c757d;
        }
        
        .unit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
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
                            <i class="fas fa-tasks me-3"></i>
                            متابعة المنهج
                        </h1>
                        <p class="page-subtitle">متابعة تقدمي في المنهج المقرر لكل مادة وصف</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-list fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo $totalUnits; ?></h4>
                        <p class="text-muted mb-0">إجمالي الوحدات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success"><?php echo count($completedUnits); ?></h4>
                        <p class="text-muted mb-0">وحدات مكتملة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-play-circle fa-3x text-warning mb-3"></i>
                        <h4 class="text-warning"><?php echo count($inProgressUnits); ?></h4>
                        <p class="text-muted mb-0">قيد التنفيذ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-percentage fa-3x text-info mb-3"></i>
                        <h4 class="text-info"><?php echo number_format($averageProgress, 1); ?>%</h4>
                        <p class="text-muted mb-0">متوسط التقدم</p>
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
                    <div class="col-md-4">
                        <label for="search" class="form-label">البحث</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="عنوان الوحدة أو الملاحظات">
                    </div>
                    <div class="col-md-4">
                        <label for="subject_filter" class="form-label">المادة</label>
                        <select class="form-select" id="subject_filter" name="subject">
                            <option value="">جميع المواد</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject); ?>" 
                                    <?php echo $subject_filter === $subject ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            بحث
                        </button>
                        <a href="my.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>
                            إعادة تعيين
                        </a>
                        <a href="add.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>
                            إضافة وحدة جديدة
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- قائمة الوحدات -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    وحدات المنهج (<?php echo count($curriculumProgress); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($curriculumProgress)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد وحدات</h5>
                    <p class="text-muted">لم يتم العثور على وحدات تطابق معايير البحث</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        إضافة وحدة جديدة
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($curriculumProgress as $unit): ?>
                    <?php
                    $progressClass = '';
                    $statusText = '';
                    if ($unit['progress_percentage'] >= 100) {
                        $progressClass = 'completed';
                        $statusText = 'مكتملة';
                        $progressColor = 'bg-success';
                    } elseif ($unit['progress_percentage'] > 0) {
                        $progressClass = 'in-progress';
                        $statusText = 'قيد التنفيذ';
                        $progressColor = 'bg-warning';
                    } else {
                        $progressClass = 'not-started';
                        $statusText = 'لم تبدأ';
                        $progressColor = 'bg-secondary';
                    }
                    ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card unit-card <?php echo $progressClass; ?> h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">الوحدة <?php echo $unit['unit_number']; ?>: <?php echo htmlspecialchars($unit['unit_title']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-book me-1"></i>
                                        <?php echo htmlspecialchars($unit['subject']); ?>
                                        <i class="fas fa-users me-1 ms-2"></i>
                                        <?php echo htmlspecialchars($unit['grade']); ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="view.php?id=<?php echo $unit['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>عرض
                                        </a></li>
                                        <li><a class="dropdown-item" href="edit.php?id=<?php echo $unit['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>تعديل
                                        </a></li>
                                        <li><a class="dropdown-item" href="update_progress.php?id=<?php echo $unit['id']; ?>">
                                            <i class="fas fa-chart-line me-2"></i>تحديث التقدم
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger btn-delete" href="#" 
                                               data-unit-id="<?php echo $unit['id']; ?>"
                                               data-item-name="<?php echo htmlspecialchars($unit['unit_title']); ?>">
                                            <i class="fas fa-trash me-2"></i>حذف
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center mb-3">
                                    <div class="col-8">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small">التقدم</span>
                                            <span class="small font-weight-bold"><?php echo $unit['progress_percentage']; ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar <?php echo $progressColor; ?>" 
                                                 style="width: <?php echo $unit['progress_percentage']; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-4 text-center">
                                        <div class="progress-circle <?php echo $progressColor; ?>">
                                            <?php echo round($unit['progress_percentage']); ?>%
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <small class="text-muted d-block">إجمالي الدروس</small>
                                        <strong class="text-primary"><?php echo $unit['total_lessons']; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">الدروس المكتملة</small>
                                        <strong class="text-success"><?php echo $unit['completed_lessons']; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">المتبقية</small>
                                        <strong class="text-warning"><?php echo $unit['total_lessons'] - $unit['completed_lessons']; ?></strong>
                                    </div>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">تاريخ البداية</small>
                                        <strong><?php echo $unit['start_date'] ? date('m/d', strtotime($unit['start_date'])) : '-'; ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">التاريخ المتوقع</small>
                                        <strong><?php echo $unit['expected_end_date'] ? date('m/d', strtotime($unit['expected_end_date'])) : '-'; ?></strong>
                                    </div>
                                </div>
                                
                                <?php if (!empty($unit['notes'])): ?>
                                <div class="mb-3">
                                    <strong class="text-info">ملاحظات:</strong>
                                    <p class="small mb-0"><?php echo nl2br(htmlspecialchars(substr($unit['notes'], 0, 100))); ?>
                                    <?php if (strlen($unit['notes']) > 100): ?>...<?php endif; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        آخر تحديث: <?php echo formatDateArabic($unit['updated_at']); ?>
                                    </small>
                                    <span class="badge <?php echo $progressColor; ?>"><?php echo $statusText; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
        // معالجة حذف الوحدة
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete')) {
                e.preventDefault();
                const button = e.target.closest('.btn-delete');
                const unitId = button.dataset.unitId;
                const itemName = button.dataset.itemName;
                
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: `هل أنت متأكد من حذف الوحدة "${itemName}"؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `delete.php?id=${unitId}`;
                    }
                });
            }
        });
    </script>
</body>
</html>
