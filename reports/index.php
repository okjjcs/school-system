<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// جلب إحصائيات عامة
try {
    // إحصائيات المعلمين
    $stmt = $db->query("SELECT COUNT(*) as total FROM teachers");
    $totalTeachers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // إحصائيات التحضيرات
    $stmt = $db->query("SELECT COUNT(*) as total FROM daily_preparations");
    $totalPreparations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // إحصائيات الأنشطة
    $stmt = $db->query("SELECT COUNT(*) as total FROM activities");
    $totalActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // إحصائيات الملفات
    $stmt = $db->query("SELECT COUNT(*) as total FROM files");
    $totalFiles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // إحصائيات هذا الشهر
    $stmt = $db->query("SELECT COUNT(*) as total FROM daily_preparations WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')");
    $monthlyPreparations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM activities WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')");
    $monthlyActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // أكثر المعلمين نشاطاً
    $stmt = $db->query("
        SELECT t.first_name, t.last_name, t.subject,
               COUNT(dp.id) as preparations_count,
               COUNT(a.id) as activities_count
        FROM teachers t
        LEFT JOIN daily_preparations dp ON t.id = dp.teacher_id
        LEFT JOIN activities a ON t.id = a.teacher_id
        GROUP BY t.id
        ORDER BY (COUNT(dp.id) + COUNT(a.id)) DESC
        LIMIT 5
    ");
    $topTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إحصائيات الاختصاصات
    $stmt = $db->query("
        SELECT t.subject, COUNT(*) as count
        FROM teachers t
        GROUP BY t.subject
        ORDER BY count DESC
    ");
    $subjectStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $totalTeachers = $totalPreparations = $totalActivities = $totalFiles = 0;
    $monthlyPreparations = $monthlyActivities = 0;
    $topTeachers = $subjectStats = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - التقارير والإحصائيات</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                <a class="nav-link" href="../teachers/list.php">
                    <i class="fas fa-users me-1"></i>
                    المعلمون
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
        <!-- Page Header -->
        <div class="page-header text-center">
            <h1 class="display-5 mb-3">
                <i class="fas fa-chart-bar me-3"></i>
                التقارير والإحصائيات
            </h1>
            <p class="lead mb-0">نظرة شاملة على أداء النظام والمعلمين</p>
        </div>

        <!-- إحصائيات عامة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center bg-primary text-white">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h2><?php echo $totalTeachers; ?></h2>
                        <p class="mb-0">إجمالي المعلمين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center bg-success text-white">
                    <div class="card-body">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <h2><?php echo $totalPreparations; ?></h2>
                        <p class="mb-0">إجمالي التحضيرات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center bg-warning text-white">
                    <div class="card-body">
                        <i class="fas fa-trophy fa-3x mb-3"></i>
                        <h2><?php echo $totalActivities; ?></h2>
                        <p class="mb-0">إجمالي الأنشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center bg-info text-white">
                    <div class="card-body">
                        <i class="fas fa-file fa-3x mb-3"></i>
                        <h2><?php echo $totalFiles; ?></h2>
                        <p class="mb-0">إجمالي الملفات</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات شهرية -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card stats-card text-center bg-gradient" style="background: linear-gradient(45deg, #28a745, #20c997);">
                    <div class="card-body text-white">
                        <i class="fas fa-calendar-month fa-2x mb-2"></i>
                        <h3><?php echo $monthlyPreparations; ?></h3>
                        <p class="mb-0">تحضيرات هذا الشهر</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card stats-card text-center bg-gradient" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                    <div class="card-body text-white">
                        <i class="fas fa-calendar-star fa-2x mb-2"></i>
                        <h3><?php echo $monthlyActivities; ?></h3>
                        <p class="mb-0">أنشطة هذا الشهر</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- أكثر المعلمين نشاطاً -->
            <div class="col-lg-6 mb-4">
                <div class="card stats-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-star me-2"></i>
                            أكثر المعلمين نشاطاً
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topTeachers)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topTeachers as $index => $teacher): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($teacher['subject']); ?></small>
                                </div>
                                <div class="text-center">
                                    <span class="badge bg-success me-1"><?php echo $teacher['preparations_count']; ?> تحضير</span>
                                    <span class="badge bg-warning"><?php echo $teacher['activities_count']; ?> نشاط</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد بيانات متاحة</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الاختصاصات -->
            <div class="col-lg-6 mb-4">
                <div class="card stats-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            توزيع المعلمين حسب الاختصاص
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="subjectsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تقارير سريعة -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-download me-2"></i>
                            تقارير سريعة
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="teachers_report.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-users me-2"></i>
                                    تقرير المعلمين
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="preparations_report.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-book me-2"></i>
                                    تقرير التحضيرات
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="activities_report.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-trophy me-2"></i>
                                    تقرير الأنشطة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // رسم بياني للاختصاصات
    <?php if (!empty($subjectStats)): ?>
    const ctx = document.getElementById('subjectsChart').getContext('2d');
    const subjectsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo "'" . implode("', '", array_column($subjectStats, 'subject')) . "'"; ?>],
            datasets: [{
                data: [<?php echo implode(', ', array_column($subjectStats, 'count')); ?>],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#C9CBCF',
                    '#4BC0C0',
                    '#FF6384'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>
