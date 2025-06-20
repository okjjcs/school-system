<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// جلب بيانات المعلمين مع الإحصائيات
try {
    $stmt = $db->query("
        SELECT 
            t.*,
            COUNT(DISTINCT dp.id) as preparations_count,
            COUNT(DISTINCT a.id) as activities_count,
            COUNT(DISTINCT f.id) as files_count
        FROM teachers t
        LEFT JOIN daily_preparations dp ON t.id = dp.teacher_id
        LEFT JOIN activities a ON t.id = a.teacher_id
        LEFT JOIN files f ON t.id = f.teacher_id
        GROUP BY t.id
        ORDER BY t.first_name, t.last_name
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $teachers = [];
    showMessage('خطأ في جلب بيانات المعلمين', 'error');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تقرير المعلمين</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        @media print {
            .no-print { display: none !important; }
            .page-header { background: #007bff !important; }
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
                <i class="fas fa-users me-3"></i>
                تقرير المعلمين الشامل
            </h1>
            <p class="lead mb-0">قائمة شاملة بجميع المعلمين مع إحصائياتهم</p>
            <small class="text-light">تاريخ التقرير: <?php echo date('Y-m-d H:i'); ?></small>
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
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <h3><?php echo count($teachers); ?></h3>
                        <p class="mb-0">إجمالي المعلمين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h3><?php echo array_sum(array_column($teachers, 'preparations_count')); ?></h3>
                        <p class="mb-0">إجمالي التحضيرات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body">
                        <h3><?php echo array_sum(array_column($teachers, 'activities_count')); ?></h3>
                        <p class="mb-0">إجمالي الأنشطة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h3><?php echo array_sum(array_column($teachers, 'files_count')); ?></h3>
                        <p class="mb-0">إجمالي الملفات</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول المعلمين -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    بيانات المعلمين التفصيلية
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($teachers)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="teachersTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>الاسم الكامل</th>
                                <th>رقم الموظف</th>
                                <th>التخصص</th>
                                <th>الصف المدرس</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>تاريخ التوظيف</th>
                                <th>التحضيرات</th>
                                <th>الأنشطة</th>
                                <th>الملفات</th>
                                <th>النشاط العام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $index => $teacher): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($teacher['employee_id']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($teacher['subject']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($teacher['grade_level']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                <td><?php echo formatDateArabic($teacher['hire_date']); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo $teacher['preparations_count']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-warning"><?php echo $teacher['activities_count']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $teacher['files_count']; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $totalActivity = $teacher['preparations_count'] + $teacher['activities_count'] + $teacher['files_count'];
                                    if ($totalActivity >= 10) {
                                        echo '<span class="badge bg-success">عالي</span>';
                                    } elseif ($totalActivity >= 5) {
                                        echo '<span class="badge bg-warning">متوسط</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">منخفض</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد بيانات معلمين</h5>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ملخص التقرير -->
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
                        <h6>إحصائيات الأداء:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>معلمون نشطون (10+ أنشطة): 
                                <?php echo count(array_filter($teachers, function($t) { return ($t['preparations_count'] + $t['activities_count'] + $t['files_count']) >= 10; })); ?>
                            </li>
                            <li><i class="fas fa-minus text-warning me-2"></i>معلمون متوسطو النشاط (5-9 أنشطة): 
                                <?php echo count(array_filter($teachers, function($t) { $total = $t['preparations_count'] + $t['activities_count'] + $t['files_count']; return $total >= 5 && $total < 10; })); ?>
                            </li>
                            <li><i class="fas fa-times text-danger me-2"></i>معلمون قليلو النشاط (أقل من 5): 
                                <?php echo count(array_filter($teachers, function($t) { return ($t['preparations_count'] + $t['activities_count'] + $t['files_count']) < 5; })); ?>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>توزيع الاختصاصات:</h6>
                        <?php 
                        $subjectCounts = array_count_values(array_column($teachers, 'subject'));
                        foreach ($subjectCounts as $subject => $count):
                        ?>
                        <div class="d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($subject); ?></span>
                            <span class="badge bg-primary"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function exportToExcel() {
        // تحويل الجدول إلى CSV
        const table = document.getElementById('teachersTable');
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
                // إزالة HTML tags والحصول على النص فقط
                let text = td.textContent.trim();
                // إضافة علامات اقتباس للنصوص التي تحتوي على فواصل
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
        link.setAttribute('download', 'teachers_report_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>
