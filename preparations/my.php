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
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');

// بناء استعلام البحث
$sql = "SELECT * FROM daily_preparations WHERE teacher_id = ?";
$params = [$currentTeacher['id']];

if (!empty($search)) {
    $sql .= " AND (lesson_title LIKE ? OR lesson_content LIKE ? OR lesson_objectives LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($subject_filter)) {
    $sql .= " AND subject = ?";
    $params[] = $subject_filter;
}

if (!empty($date_from)) {
    $sql .= " AND preparation_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND preparation_date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY preparation_date DESC, created_at DESC";

try {
    $stmt = $db->query($sql, $params);
    $preparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب قائمة المواد للتصفية
    $stmt = $db->query("SELECT DISTINCT subject FROM daily_preparations WHERE teacher_id = ? ORDER BY subject", [$currentTeacher['id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات التحضير', 'error');
    $preparations = [];
    $subjects = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تحضيري اليومي</title>
    
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
                            <i class="fas fa-book me-3"></i>
                            تحضيري اليومي
                        </h1>
                        <p class="page-subtitle">إدارة ومتابعة التحضير اليومي للدروس</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-book fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo count($preparations); ?></h4>
                        <p class="text-muted mb-0">إجمالي التحضيرات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-calendar-day fa-3x text-success mb-3"></i>
                        <?php
                        $todayPrep = array_filter($preparations, function($p) { 
                            return $p['preparation_date'] == date('Y-m-d'); 
                        });
                        ?>
                        <h4 class="text-success"><?php echo count($todayPrep); ?></h4>
                        <p class="text-muted mb-0">تحضير اليوم</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-calendar-week fa-3x text-warning mb-3"></i>
                        <?php
                        $weekStart = date('Y-m-d', strtotime('monday this week'));
                        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                        $weekPrep = array_filter($preparations, function($p) use ($weekStart, $weekEnd) { 
                            return $p['preparation_date'] >= $weekStart && $p['preparation_date'] <= $weekEnd; 
                        });
                        ?>
                        <h4 class="text-warning"><?php echo count($weekPrep); ?></h4>
                        <p class="text-muted mb-0">هذا الأسبوع</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-subjects fa-3x text-info mb-3"></i>
                        <h4 class="text-info"><?php echo count($subjects); ?></h4>
                        <p class="text-muted mb-0">المواد المدرسة</p>
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
                               placeholder="عنوان الدرس أو المحتوى">
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
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
                            إضافة تحضير جديد
                        </a>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-2"></i>
                                تصدير
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?format=pdf">PDF</a></li>
                                <li><a class="dropdown-item" href="export.php?format=word">Word</a></li>
                                <li><a class="dropdown-item" href="export.php?format=excel">Excel</a></li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- قائمة التحضيرات -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة التحضيرات (<?php echo count($preparations); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($preparations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد تحضيرات</h5>
                    <p class="text-muted">لم يتم العثور على تحضيرات تطابق معايير البحث</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        إضافة تحضير جديد
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($preparations as $prep): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 hover-shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($prep['lesson_title']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-book me-1"></i>
                                        <?php echo htmlspecialchars($prep['subject']); ?>
                                        <i class="fas fa-users me-1 ms-2"></i>
                                        <?php echo htmlspecialchars($prep['grade']); ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="view.php?id=<?php echo $prep['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>عرض
                                        </a></li>
                                        <li><a class="dropdown-item" href="edit.php?id=<?php echo $prep['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>تعديل
                                        </a></li>
                                        <li><a class="dropdown-item" href="duplicate.php?id=<?php echo $prep['id']; ?>">
                                            <i class="fas fa-copy me-2"></i>نسخ
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger btn-delete" href="#" 
                                               data-prep-id="<?php echo $prep['id']; ?>"
                                               data-item-name="<?php echo htmlspecialchars($prep['lesson_title']); ?>">
                                            <i class="fas fa-trash me-2"></i>حذف
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong class="text-primary">أهداف الدرس:</strong>
                                    <p class="small mb-2"><?php echo nl2br(htmlspecialchars(substr($prep['lesson_objectives'], 0, 150))); ?>
                                    <?php if (strlen($prep['lesson_objectives']) > 150): ?>...<?php endif; ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-success">محتوى الدرس:</strong>
                                    <p class="small mb-2"><?php echo nl2br(htmlspecialchars(substr($prep['lesson_content'], 0, 150))); ?>
                                    <?php if (strlen($prep['lesson_content']) > 150): ?>...<?php endif; ?></p>
                                </div>
                                
                                <?php if (!empty($prep['teaching_methods'])): ?>
                                <div class="mb-3">
                                    <strong class="text-warning">طرق التدريس:</strong>
                                    <p class="small mb-2"><?php echo nl2br(htmlspecialchars(substr($prep['teaching_methods'], 0, 100))); ?>
                                    <?php if (strlen($prep['teaching_methods']) > 100): ?>...<?php endif; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDateArabic($prep['preparation_date']); ?>
                                    </small>
                                    <div>
                                        <?php
                                        $today = date('Y-m-d');
                                        if ($prep['preparation_date'] == $today) {
                                            echo '<span class="badge bg-success">اليوم</span>';
                                        } elseif ($prep['preparation_date'] > $today) {
                                            echo '<span class="badge bg-info">مستقبلي</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">سابق</span>';
                                        }
                                        ?>
                                    </div>
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
        // معالجة حذف التحضير
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete')) {
                e.preventDefault();
                const button = e.target.closest('.btn-delete');
                const prepId = button.dataset.prepId;
                const itemName = button.dataset.itemName;
                
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: `هل أنت متأكد من حذف التحضير "${itemName}"؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `delete.php?id=${prepId}`;
                    }
                });
            }
        });
    </script>
</body>
</html>
