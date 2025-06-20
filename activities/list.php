<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// معالجة البحث والتصفية
$search = sanitize($_GET['search'] ?? '');
$type_filter = sanitize($_GET['type'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$teacher_filter = sanitize($_GET['teacher'] ?? '');

// بناء استعلام البحث
$sql = "SELECT a.*, t.first_name, t.last_name, t.employee_id 
        FROM activities a 
        JOIN teachers t ON a.teacher_id = t.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($type_filter)) {
    $sql .= " AND a.activity_type = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

if (!empty($teacher_filter)) {
    $sql .= " AND a.teacher_id = ?";
    $params[] = $teacher_filter;
}

$sql .= " ORDER BY a.start_date DESC, a.created_at DESC";

try {
    $stmt = $db->query($sql, $params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب قائمة المعلمين للتصفية
    $stmt = $db->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name, last_name");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات الأنشطة', 'error');
    $activities = [];
    $teachers = [];
}

// حساب الإحصائيات
$totalActivities = count($activities);
$ongoingActivities = array_filter($activities, function($a) { return $a['status'] === 'ongoing'; });
$completedActivities = array_filter($activities, function($a) { return $a['status'] === 'completed'; });
$plannedActivities = array_filter($activities, function($a) { return $a['status'] === 'planned'; });
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - جميع الأنشطة والمسابقات</title>
    
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
                            جميع الأنشطة والمسابقات
                        </h1>
                        <p class="page-subtitle">عرض ومتابعة جميع الأنشطة والمسابقات في المدرسة</p>
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
                        <h4 class="text-primary"><?php echo $totalActivities; ?></h4>
                        <p class="text-muted mb-0">إجمالي الأنشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-play fa-3x text-warning mb-3"></i>
                        <h4 class="text-warning"><?php echo count($ongoingActivities); ?></h4>
                        <p class="text-muted mb-0">جارية حالياً</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success"><?php echo count($completedActivities); ?></h4>
                        <p class="text-muted mb-0">مكتملة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-clock fa-3x text-info mb-3"></i>
                        <h4 class="text-info"><?php echo count($plannedActivities); ?></h4>
                        <p class="text-muted mb-0">مخططة</p>
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
                    <div class="col-md-3">
                        <label for="teacher_filter" class="form-label">المعلم</label>
                        <select class="form-select" id="teacher_filter" name="teacher">
                            <option value="">جميع المعلمين</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" 
                                    <?php echo $teacher_filter == $teacher['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>
                                بحث
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>
                            إعادة تعيين
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportActivities()">
                            <i class="fas fa-download me-2"></i>
                            تصدير Excel
                        </button>
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
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>النشاط</th>
                                <th>المعلم</th>
                                <th>النوع</th>
                                <th>الحالة</th>
                                <th>تاريخ البداية</th>
                                <th>المشاركون</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                        <?php if (!empty($activity['target_grade'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($activity['target_grade']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($activity['employee_id']); ?></small>
                                    </div>
                                </td>
                                <td>
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
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <?php echo $activity['start_date'] ? formatDateArabic($activity['start_date']) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $activity['participants_count']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-outline-primary" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../teachers/view.php?id=<?php echo $activity['teacher_id']; ?>" class="btn btn-sm btn-outline-info" title="ملف المعلم">
                                            <i class="fas fa-user"></i>
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
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // تصدير الأنشطة
        function exportActivities() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.open('export.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>
