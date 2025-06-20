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
$type_filter = sanitize($_GET['type'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');

// بناء استعلام البحث
$sql = "SELECT * FROM activities WHERE teacher_id = ?";
$params = [$currentTeacher['id']];

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($type_filter)) {
    $sql .= " AND activity_type = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $sql .= " AND start_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND start_date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY start_date DESC, created_at DESC";

try {
    $stmt = $db->query($sql, $params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات الأنشطة', 'error');
    $activities = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - أنشطتي ومسابقاتي</title>
    
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
                            <i class="fas fa-trophy me-3"></i>
                            أنشطتي ومسابقاتي
                        </h1>
                        <p class="page-subtitle">إدارة ومتابعة الأنشطة والمسابقات التي أقوم بها</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-trophy fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo count($activities); ?></h4>
                        <p class="text-muted mb-0">إجمالي الأنشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-play fa-3x text-success mb-3"></i>
                        <?php
                        $ongoingActivities = array_filter($activities, function($a) { 
                            return $a['status'] == 'ongoing'; 
                        });
                        ?>
                        <h4 class="text-success"><?php echo count($ongoingActivities); ?></h4>
                        <p class="text-muted mb-0">جارية حالياً</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x text-info mb-3"></i>
                        <?php
                        $completedActivities = array_filter($activities, function($a) { 
                            return $a['status'] == 'completed'; 
                        });
                        ?>
                        <h4 class="text-info"><?php echo count($completedActivities); ?></h4>
                        <p class="text-muted mb-0">مكتملة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <?php
                        $totalParticipants = array_sum(array_column($activities, 'participants_count'));
                        ?>
                        <h4 class="text-warning"><?php echo $totalParticipants; ?></h4>
                        <p class="text-muted mb-0">إجمالي المشاركين</p>
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
                               placeholder="عنوان النشاط أو الوصف">
                    </div>
                    <div class="col-md-2">
                        <label for="type_filter" class="form-label">نوع النشاط</label>
                        <select class="form-select" id="type_filter" name="type">
                            <option value="">جميع الأنواع</option>
                            <option value="competition" <?php echo $type_filter === 'competition' ? 'selected' : ''; ?>>مسابقة</option>
                            <option value="activity" <?php echo $type_filter === 'activity' ? 'selected' : ''; ?>>نشاط</option>
                            <option value="project" <?php echo $type_filter === 'project' ? 'selected' : ''; ?>>مشروع</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status_filter" class="form-label">الحالة</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="planned" <?php echo $status_filter === 'planned' ? 'selected' : ''; ?>>مخطط</option>
                            <option value="ongoing" <?php echo $status_filter === 'ongoing' ? 'selected' : ''; ?>>جاري</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                        </select>
                    </div>
                    <div class="col-md-2">
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
                            إضافة نشاط جديد
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- قائمة الأنشطة -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة الأنشطة (<?php echo count($activities); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($activities)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد أنشطة</h5>
                    <p class="text-muted">لم يتم العثور على أنشطة تطابق معايير البحث</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        إضافة نشاط جديد
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($activities as $activity): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 hover-shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                    <small class="text-muted">
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
                                        <?php if (!empty($activity['target_grade'])): ?>
                                        <i class="fas fa-users me-1 ms-2"></i>
                                        <?php echo htmlspecialchars($activity['target_grade']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="view.php?id=<?php echo $activity['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>عرض
                                        </a></li>
                                        <li><a class="dropdown-item" href="edit.php?id=<?php echo $activity['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>تعديل
                                        </a></li>
                                        <li><a class="dropdown-item" href="participants.php?id=<?php echo $activity['id']; ?>">
                                            <i class="fas fa-users me-2"></i>المشاركون
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger btn-delete" href="#" 
                                               data-activity-id="<?php echo $activity['id']; ?>"
                                               data-item-name="<?php echo htmlspecialchars($activity['title']); ?>">
                                            <i class="fas fa-trash me-2"></i>حذف
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p class="small mb-2"><?php echo nl2br(htmlspecialchars(substr($activity['description'], 0, 150))); ?>
                                    <?php if (strlen($activity['description']) > 150): ?>...<?php endif; ?></p>
                                </div>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <small class="text-muted d-block">المشاركون</small>
                                        <strong class="text-primary"><?php echo $activity['participants_count']; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">تاريخ البداية</small>
                                        <strong class="text-success"><?php echo $activity['start_date'] ? date('m/d', strtotime($activity['start_date'])) : '-'; ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">تاريخ النهاية</small>
                                        <strong class="text-warning"><?php echo $activity['end_date'] ? date('m/d', strtotime($activity['end_date'])) : '-'; ?></strong>
                                    </div>
                                </div>
                                
                                <?php if (!empty($activity['results'])): ?>
                                <div class="mb-3">
                                    <strong class="text-info">النتائج:</strong>
                                    <p class="small mb-0"><?php echo nl2br(htmlspecialchars(substr($activity['results'], 0, 100))); ?>
                                    <?php if (strlen($activity['results']) > 100): ?>...<?php endif; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDateArabic($activity['created_at']); ?>
                                    </small>
                                    <div>
                                        <?php
                                        $statusClasses = [
                                            'planned' => 'badge bg-secondary',
                                            'ongoing' => 'badge bg-primary',
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
