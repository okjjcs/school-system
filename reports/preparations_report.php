<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// معالجة الفلاتر
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$subject = $_GET['subject'] ?? '';
$grade = $_GET['grade'] ?? '';

// بناء الاستعلام مع الفلاتر
$whereConditions = [];
$params = [];

if (!empty($dateFrom)) {
    $whereConditions[] = "dp.preparation_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "dp.preparation_date <= ?";
    $params[] = $dateTo;
}

if (!empty($subject)) {
    $whereConditions[] = "dp.subject = ?";
    $params[] = $subject;
}

if (!empty($grade)) {
    $whereConditions[] = "dp.grade_level = ?";
    $params[] = $grade;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// جلب بيانات التحضيرات
try {
    $stmt = $db->query("
        SELECT 
            dp.*,
            t.first_name,
            t.last_name,
            t.employee_id
        FROM daily_preparations dp
        JOIN teachers t ON dp.teacher_id = t.id
        $whereClause
        ORDER BY dp.preparation_date DESC, t.first_name
    ", $params);
    $preparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب قائمة الاختصاصات والصفوف للفلاتر
    $subjects = getActiveSubjects($db);
    $grades = getActiveGrades($db);
    
} catch (Exception $e) {
    $preparations = [];
    $subjects = [];
    $grades = [];
    showMessage('خطأ في جلب بيانات التحضيرات', 'error');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تقرير التحضيرات</title>
    
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
        @media print {
            .no-print { display: none !important; }
            .page-header { background: #28a745 !important; }
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
                <i class="fas fa-book me-3"></i>
                تقرير التحضيرات اليومية
            </h1>
            <p class="lead mb-0">تقرير شامل لجميع التحضيرات اليومية للمعلمين</p>
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
                        <label for="subject" class="form-label">الاختصاص</label>
                        <select class="form-select" id="subject" name="subject">
                            <option value="">جميع الاختصاصات</option>
                            <?php foreach ($subjects as $subj): ?>
                            <option value="<?php echo htmlspecialchars($subj['name']); ?>" 
                                    <?php echo $subject === $subj['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subj['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="grade" class="form-label">الصف</label>
                        <select class="form-select" id="grade" name="grade">
                            <option value="">جميع الصفوف</option>
                            <?php foreach ($grades as $gr): ?>
                            <option value="<?php echo htmlspecialchars($gr['name']); ?>" 
                                    <?php echo $grade === $gr['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($gr['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>بحث
                        </button>
                        <a href="preparations_report.php" class="btn btn-secondary">
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
            <div class="col-md-4">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h3><?php echo count($preparations); ?></h3>
                        <p class="mb-0">إجمالي التحضيرات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h3><?php echo count(array_unique(array_column($preparations, 'teacher_id'))); ?></h3>
                        <p class="mb-0">عدد المعلمين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body">
                        <h3><?php echo count(array_unique(array_column($preparations, 'subject'))); ?></h3>
                        <p class="mb-0">عدد الاختصاصات</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول التحضيرات -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    بيانات التحضيرات التفصيلية
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($preparations)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="preparationsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>المعلم</th>
                                <th>رقم الموظف</th>
                                <th>التاريخ</th>
                                <th>الاختصاص</th>
                                <th>الصف</th>
                                <th>عنوان الدرس</th>
                                <th>الأهداف</th>
                                <th>المدة</th>
                                <th>تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preparations as $index => $prep): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prep['first_name'] . ' ' . $prep['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($prep['employee_id']); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo formatDateArabic($prep['preparation_date']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($prep['subject']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($prep['grade_level']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($prep['lesson_title']); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($prep['objectives'], 0, 50)); ?><?php echo strlen($prep['objectives']) > 50 ? '...' : ''; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo $prep['duration']; ?> دقيقة</span>
                                </td>
                                <td><?php echo formatDateArabic($prep['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد تحضيرات تطابق المعايير المحددة</h5>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ملخص التقرير -->
        <?php if (!empty($preparations)): ?>
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
                        <h6>توزيع التحضيرات حسب الاختصاص:</h6>
                        <?php 
                        $subjectCounts = array_count_values(array_column($preparations, 'subject'));
                        foreach ($subjectCounts as $subj => $count):
                        ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo htmlspecialchars($subj); ?></span>
                            <span class="badge bg-primary"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>توزيع التحضيرات حسب الصف:</h6>
                        <?php 
                        $gradeCounts = array_count_values(array_column($preparations, 'grade_level'));
                        foreach ($gradeCounts as $gr => $count):
                        ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo htmlspecialchars($gr); ?></span>
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
        const table = document.getElementById('preparationsTable');
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
        link.setAttribute('download', 'preparations_report_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>
