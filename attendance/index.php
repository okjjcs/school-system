<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// تحديد الشهر والسنة المطلوبين
$currentMonth = date('m');
$currentYear = date('Y');
$selectedMonth = $_GET['month'] ?? $currentMonth;
$selectedYear = $_GET['year'] ?? $currentYear;

// جلب سجلات الحضور
try {
    if (isAdmin()) {
        // المدير يرى حضور جميع المعلمين
        $stmt = $db->query("SELECT a.*, t.first_name, t.last_name, t.employee_id 
                           FROM attendance a 
                           JOIN teachers t ON a.teacher_id = t.id 
                           WHERE strftime('%m', a.attendance_date) = ? AND strftime('%Y', a.attendance_date) = ?
                           ORDER BY a.attendance_date DESC, t.first_name", 
                          [sprintf('%02d', $selectedMonth), $selectedYear]);
    } else {
        // المعلم يرى حضوره فقط
        $teacherId = getCurrentTeacherId();
        if (!$teacherId) {
            showMessage('خطأ في تحديد هوية المعلم', 'error');
            redirect('../index.php');
        }
        
        $stmt = $db->query("SELECT a.*, t.first_name, t.last_name, t.employee_id 
                           FROM attendance a 
                           JOIN teachers t ON a.teacher_id = t.id 
                           WHERE a.teacher_id = ? AND strftime('%m', a.attendance_date) = ? AND strftime('%Y', a.attendance_date) = ?
                           ORDER BY a.attendance_date DESC", 
                          [$teacherId, sprintf('%02d', $selectedMonth), $selectedYear]);
    }
    
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $attendanceRecords = [];
    showMessage('خطأ في جلب سجلات الحضور: ' . $e->getMessage(), 'error');
}

// إحصائيات الحضور للشهر المحدد
try {
    if (isAdmin()) {
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_days,
                           COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                           COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                           COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                           COUNT(DISTINCT teacher_id) as active_teachers
                           FROM attendance 
                           WHERE strftime('%m', attendance_date) = ? AND strftime('%Y', attendance_date) = ?", 
                          [sprintf('%02d', $selectedMonth), $selectedYear]);
    } else {
        $teacherId = getCurrentTeacherId();
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_days,
                           COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                           COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                           COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                           1 as active_teachers
                           FROM attendance 
                           WHERE teacher_id = ? AND strftime('%m', attendance_date) = ? AND strftime('%Y', attendance_date) = ?", 
                          [$teacherId, sprintf('%02d', $selectedMonth), $selectedYear]);
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = ['total_days' => 0, 'present_days' => 0, 'absent_days' => 0, 'late_days' => 0, 'active_teachers' => 0];
}

// أسماء الشهور بالعربية
$monthNames = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الحضور والغياب - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .attendance-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-present { color: #28a745; }
        .status-absent { color: #dc3545; }
        .status-late { color: #ffc107; }
    </style>
</head>
<body class="bg-light">
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">الحضور والغياب</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../activities/index.php">الأنشطة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../preparations/index.php">التحضيرات</a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../teachers/list.php">المعلمون</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">تسجيل الخروج</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php displayMessage(); ?>
        
        <!-- العنوان وفلتر التاريخ -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-calendar-check me-2"></i>الحضور والغياب
            </h2>
            
            <!-- فلتر الشهر والسنة -->
            <form method="GET" class="d-flex gap-2">
                <select name="month" class="form-select" style="width: auto;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $selectedMonth ? 'selected' : ''; ?>>
                        <?php echo $monthNames[$m]; ?>
                    </option>
                    <?php endfor; ?>
                </select>
                
                <select name="year" class="form-select" style="width: auto;">
                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                    <?php endfor; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <!-- عرض الشهر المحدد -->
        <div class="alert alert-info">
            <i class="fas fa-calendar me-2"></i>
            عرض سجلات <strong><?php echo $monthNames[(int)$selectedMonth] . ' ' . $selectedYear; ?></strong>
        </div>
        
        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-calendar-day fa-2x mb-2"></i>
                    <h3><?php echo $stats['total_days']; ?></h3>
                    <p class="mb-0">إجمالي الأيام</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check fa-2x mb-2"></i>
                    <h3><?php echo $stats['present_days']; ?></h3>
                    <p class="mb-0">أيام الحضور</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-times fa-2x mb-2"></i>
                    <h3><?php echo $stats['absent_days']; ?></h3>
                    <p class="mb-0">أيام الغياب</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3><?php echo $stats['late_days']; ?></h3>
                    <p class="mb-0">أيام التأخير</p>
                </div>
            </div>
        </div>
        
        <!-- جدول سجلات الحضور -->
        <?php if (empty($attendanceRecords)): ?>
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">لا توجد سجلات حضور</h4>
            <p class="text-muted">لا توجد سجلات حضور للشهر المحدد</p>
        </div>
        <?php else: ?>
        <div class="attendance-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>التاريخ</th>
                            <?php if (isAdmin()): ?>
                            <th>المعلم</th>
                            <?php endif; ?>
                            <th>الحالة</th>
                            <th>وقت الوصول</th>
                            <th>وقت المغادرة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendanceRecords as $record): ?>
                        <tr>
                            <td>
                                <strong><?php echo formatDateArabic($record['attendance_date']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo date('l', strtotime($record['attendance_date'])); ?></small>
                            </td>
                            
                            <?php if (isAdmin()): ?>
                            <td>
                                <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($record['employee_id']); ?></small>
                            </td>
                            <?php endif; ?>
                            
                            <td>
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                $statusText = '';
                                
                                switch ($record['status']) {
                                    case 'present':
                                        $statusClass = 'status-present';
                                        $statusIcon = 'fa-check-circle';
                                        $statusText = 'حاضر';
                                        break;
                                    case 'absent':
                                        $statusClass = 'status-absent';
                                        $statusIcon = 'fa-times-circle';
                                        $statusText = 'غائب';
                                        break;
                                    case 'late':
                                        $statusClass = 'status-late';
                                        $statusIcon = 'fa-clock';
                                        $statusText = 'متأخر';
                                        break;
                                    default:
                                        $statusClass = 'text-muted';
                                        $statusIcon = 'fa-question-circle';
                                        $statusText = 'غير محدد';
                                }
                                ?>
                                <span class="<?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if ($record['arrival_time']): ?>
                                <i class="fas fa-sign-in-alt text-success me-1"></i>
                                <?php echo date('H:i', strtotime($record['arrival_time'])); ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ($record['departure_time']): ?>
                                <i class="fas fa-sign-out-alt text-warning me-1"></i>
                                <?php echo date('H:i', strtotime($record['departure_time'])); ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ($record['notes']): ?>
                                <small><?php echo htmlspecialchars($record['notes']); ?></small>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ملخص الحضور -->
        <?php if (isTeacher()): ?>
        <div class="mt-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>ملخص حضورك لشهر <?php echo $monthNames[(int)$selectedMonth]; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-success"><?php echo $stats['present_days']; ?></h4>
                                <p class="text-muted mb-0">أيام حضور</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-danger"><?php echo $stats['absent_days']; ?></h4>
                                <p class="text-muted mb-0">أيام غياب</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-warning"><?php echo $stats['late_days']; ?></h4>
                                <p class="text-muted mb-0">أيام تأخير</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <?php 
                            $attendanceRate = $stats['total_days'] > 0 ? round(($stats['present_days'] / $stats['total_days']) * 100, 1) : 0;
                            ?>
                            <h4 class="text-primary"><?php echo $attendanceRate; ?>%</h4>
                            <p class="text-muted mb-0">نسبة الحضور</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
