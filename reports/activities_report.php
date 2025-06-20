<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// معالجة الفلاتر
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$activityType = $_GET['activity_type'] ?? '';
$status = $_GET['status'] ?? '';

// بناء الاستعلام مع الفلاتر
$whereConditions = [];
$params = [];

if (!empty($dateFrom)) {
    $whereConditions[] = "a.start_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "a.end_date <= ?";
    $params[] = $dateTo;
}

if (!empty($activityType)) {
    $whereConditions[] = "a.activity_type = ?";
    $params[] = $activityType;
}

if (!empty($status)) {
    $whereConditions[] = "a.status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// جلب بيانات الأنشطة
try {
    $stmt = $db->query("
        SELECT 
            a.*,
            t.first_name,
            t.last_name,
            t.employee_id,
            t.subject
        FROM activities a
        JOIN teachers t ON a.teacher_id = t.id
        $whereClause
        ORDER BY a.start_date DESC, t.first_name
    ", $params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $activities = [];
    showMessage('خطأ في جلب بيانات الأنشطة', 'error');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تقرير الأنشطة</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        @media print {
            .no-print { display: none !important; }
            .page-header { background: #ffc107 !important; }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-chart-bar me-1"></i>
                    التقارير
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
        <div class="page-header text-center">
            <h1 class="display-5 mb-3">
                <i class="fas fa-trophy me-3"></i>
                تقرير الأنشطة والمسابقات
            </h1>
            <p class="lead mb-0">تقرير شامل لجميع الأنشطة والمسابقات المدرسية</p>
            <small class="text-light">تاريخ التقرير: <?php echo date('Y-m-d H:i'); ?></small>
        </div>

        <!-- فلاتر البحث -->
        <div class="card mb-4 no-print">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    فلاتر البحث
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="activity_type" class="form-label">نوع النشاط</label>
                        <select class="form-select" id="activity_type" name="activity_type">
                            <option value="">جميع الأنواع</option>
                            <option value="activity" <?php echo $activityType === 'activity' ? 'selected' : ''; ?>>نشاط</option>
                            <option value="competition" <?php echo $activityType === 'competition' ? 'selected' : ''; ?>>مسابقة</option>
                            <option value="project" <?php echo $activityType === 'project' ? 'selected' : ''; ?>>مشروع</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">الحالة</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="planned" <?php echo $status === 'planned' ? 'selected' : ''; ?>>مخطط</option>
                            <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>جاري</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>بحث
                        </button>
                        <a href="activities_report.php" class="btn btn-secondary">
                            <i class="fas fa-refresh me-2"></i>إعادة تعيين
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- أزرار الإجراءات -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button onclick="window.print()" class="btn btn-primary me-2">
                            <i class="fas fa-print me-2"></i>طباعة التقرير
                        </button>
                        <button onclick="exportToExcel()" class="btn btn-success me-2">
                            <i class="fas fa-file-excel me-2"></i>تصدير إلى Excel
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>العودة للتقارير
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body">
                        <h3><?php echo count($activities); ?></h3>
                        <p class="mb-0">إجمالي الأنشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h3><?php echo count(array_filter($activities, function($a) { return $a['status'] === 'completed'; })); ?></h3>
                        <p class="mb-0">أنشطة مكتملة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h3><?php echo count(array_filter($activities, function($a) { return $a['status'] === 'ongoing'; })); ?></h3>
                        <p class="mb-0">أنشطة جارية</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <h3><?php echo array_sum(array_column($activities, 'participants_count')); ?></h3>
                        <p class="mb-0">إجمالي المشاركين</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول الأنشطة -->
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    بيانات الأنشطة التفصيلية
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($activities)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="activitiesTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>المعلم</th>
                                <th>الاختصاص</th>
                                <th>عنوان النشاط</th>
                                <th>النوع</th>
                                <th>الصف المستهدف</th>
                                <th>تاريخ البداية</th>
                                <th>تاريخ النهاية</th>
                                <th>عدد المشاركين</th>
                                <th>الحالة</th>
                                <th>تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $index => $activity): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($activity['employee_id']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($activity['subject']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                <td>
                                    <?php
                                    $typeClass = '';
                                    $typeName = '';
                                    switch($activity['activity_type']) {
                                        case 'competition':
                                            $typeClass = 'bg-danger';
                                            $typeName = 'مسابقة';
                                            break;
                                        case 'project':
                                            $typeClass = 'bg-primary';
                                            $typeName = 'مشروع';
                                            break;
                                        default:
                                            $typeClass = 'bg-warning';
                                            $typeName = 'نشاط';
                                    }
                                    ?>
                                    <span class="badge <?php echo $typeClass; ?>"><?php echo $typeName; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($activity['target_grade']); ?></span>
                                </td>
                                <td><?php echo formatDateArabic($activity['start_date']); ?></td>
                                <td><?php echo formatDateArabic($activity['end_date']); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $activity['participants_count']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusName = '';
                                    switch($activity['status']) {
                                        case 'completed':
                                            $statusClass = 'bg-success';
                                            $statusName = 'مكتمل';
                                            break;
                                        case 'ongoing':
                                            $statusClass = 'bg-info';
                                            $statusName = 'جاري';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'bg-danger';
                                            $statusName = 'ملغي';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                            $statusName = 'مخطط';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusName; ?></span>
                                </td>
                                <td><?php echo formatDateArabic($activity['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-trophy fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد أنشطة تطابق المعايير المحددة</h5>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ملخص التقرير -->
        <?php if (!empty($activities)): ?>
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    ملخص التقرير
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>توزيع الأنشطة حسب النوع:</h6>
                        <?php 
                        $typeCounts = array_count_values(array_column($activities, 'activity_type'));
                        $typeNames = ['activity' => 'نشاط', 'competition' => 'مسابقة', 'project' => 'مشروع'];
                        foreach ($typeCounts as $type => $count):
                        ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo $typeNames[$type] ?? $type; ?></span>
                            <span class="badge bg-primary"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>توزيع الأنشطة حسب الحالة:</h6>
                        <?php 
                        $statusCounts = array_count_values(array_column($activities, 'status'));
                        $statusNames = ['planned' => 'مخطط', 'ongoing' => 'جاري', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
                        foreach ($statusCounts as $stat => $count):
                        ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo $statusNames[$stat] ?? $stat; ?></span>
                            <span class="badge bg-success"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function exportToExcel() {
        const table = document.getElementById('activitiesTable');
        let csv = [];
        
        // إضافة العناوين
        const headers = [];
        table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv.push(headers.join(','));
        
        // إضافة البيانات
        table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => {
                let text = td.textContent.trim();
                if (text.includes(',')) {
                    text = '"' + text + '"';
                }
                row.push(text);
            });
            csv.push(row.join(','));
        });
        
        // تحميل الملف
        const csvContent = csv.join('\n');
        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'activities_report_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>
