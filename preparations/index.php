<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// تحديد التاريخ المطلوب
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// جلب التحضيرات حسب الصلاحيات
try {
    if (isAdmin()) {
        // المدير يرى جميع التحضيرات
        $stmt = $db->query("SELECT dp.*, t.first_name, t.last_name, t.employee_id 
                           FROM daily_preparations dp 
                           JOIN teachers t ON dp.teacher_id = t.id 
                           WHERE dp.preparation_date = ?
                           ORDER BY t.first_name, dp.subject", [$selectedDate]);
    } else {
        // المعلم يرى تحضيراته فقط
        $teacherId = getCurrentTeacherId();
        if (!$teacherId) {
            showMessage('خطأ في تحديد هوية المعلم', 'error');
            redirect('../index.php');
        }
        
        $stmt = $db->query("SELECT dp.*, t.first_name, t.last_name, t.employee_id 
                           FROM daily_preparations dp 
                           JOIN teachers t ON dp.teacher_id = t.id 
                           WHERE dp.teacher_id = ? AND dp.preparation_date = ?
                           ORDER BY dp.subject", [$teacherId, $selectedDate]);
    }
    
    $preparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $preparations = [];
    showMessage('خطأ في جلب التحضيرات: ' . $e->getMessage(), 'error');
}

// إحصائيات التحضيرات
try {
    if (isAdmin()) {
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_today,
                           COUNT(DISTINCT teacher_id) as teachers_prepared,
                           COUNT(*) as total_week
                           FROM daily_preparations 
                           WHERE preparation_date = ?", [$selectedDate]);
        
        $weekStats = $db->query("SELECT COUNT(*) as total_week 
                                FROM daily_preparations 
                                WHERE preparation_date >= date(?, '-6 days') AND preparation_date <= ?", 
                               [$selectedDate, $selectedDate]);
        $weekCount = $weekStats->fetch(PDO::FETCH_ASSOC)['total_week'];
    } else {
        $teacherId = getCurrentTeacherId();
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_today,
                           1 as teachers_prepared,
                           COUNT(*) as total_week
                           FROM daily_preparations 
                           WHERE teacher_id = ? AND preparation_date = ?", [$teacherId, $selectedDate]);
        
        $weekStats = $db->query("SELECT COUNT(*) as total_week 
                                FROM daily_preparations 
                                WHERE teacher_id = ? AND preparation_date >= date(?, '-6 days') AND preparation_date <= ?", 
                               [$teacherId, $selectedDate, $selectedDate]);
        $weekCount = $weekStats->fetch(PDO::FETCH_ASSOC)['total_week'];
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_week'] = $weekCount;
    
} catch (Exception $e) {
    $stats = ['total_today' => 0, 'teachers_prepared' => 0, 'total_week' => 0];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحضيرات اليومية - <?php echo APP_NAME; ?></title>
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
        .preparation-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .preparation-card:hover {
            transform: translateY(-3px);
        }
        .lesson-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin: 0.5rem 0;
        }
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
                        <a class="nav-link active" href="index.php">التحضيرات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../activities/index.php">الأنشطة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../curriculum/index.php">متابعة المنهج</a>
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
                <i class="fas fa-book me-2"></i>التحضيرات اليومية
            </h2>
            
            <div class="d-flex gap-2">
                <!-- فلتر التاريخ -->
                <form method="GET" class="d-flex gap-2">
                    <input type="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <a href="add.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>إضافة تحضير
                </a>
            </div>
        </div>
        
        <!-- عرض التاريخ المحدد -->
        <div class="alert alert-info">
            <i class="fas fa-calendar me-2"></i>
            عرض تحضيرات يوم <strong><?php echo formatDateArabic($selectedDate); ?></strong>
            <?php if ($selectedDate === date('Y-m-d')): ?>
            <span class="badge bg-success ms-2">اليوم</span>
            <?php endif; ?>
        </div>
        
        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-book-open fa-2x mb-2"></i>
                    <h3><?php echo $stats['total_today']; ?></h3>
                    <p class="mb-0">تحضيرات اليوم</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?php echo $stats['teachers_prepared']; ?></h3>
                    <p class="mb-0">معلمون حضروا</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="fas fa-calendar-week fa-2x mb-2"></i>
                    <h3><?php echo $stats['total_week']; ?></h3>
                    <p class="mb-0">تحضيرات الأسبوع</p>
                </div>
            </div>
        </div>
        
        <!-- قائمة التحضيرات -->
        <?php if (empty($preparations)): ?>
        <div class="text-center py-5">
            <i class="fas fa-book-open fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">لا توجد تحضيرات</h4>
            <p class="text-muted">لا توجد تحضيرات لهذا اليوم</p>
            <a href="add.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>إضافة تحضير جديد
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($preparations as $prep): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card preparation-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($prep['subject']); ?></h6>
                        <span class="badge bg-info"><?php echo htmlspecialchars($prep['grade_level']); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($prep['lesson_title']); ?></h5>
                        
                        <?php if ($prep['lesson_objectives']): ?>
                        <div class="lesson-content">
                            <h6 class="text-primary">
                                <i class="fas fa-bullseye me-1"></i>الأهداف:
                            </h6>
                            <p class="small mb-0">
                                <?php echo htmlspecialchars(substr($prep['lesson_objectives'], 0, 100)); ?>
                                <?php if (strlen($prep['lesson_objectives']) > 100): ?>...<?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($prep['lesson_content']): ?>
                        <div class="lesson-content">
                            <h6 class="text-success">
                                <i class="fas fa-book-open me-1"></i>المحتوى:
                            </h6>
                            <p class="small mb-0">
                                <?php echo htmlspecialchars(substr($prep['lesson_content'], 0, 100)); ?>
                                <?php if (strlen($prep['lesson_content']) > 100): ?>...<?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($prep['teaching_methods']): ?>
                        <div class="mb-2">
                            <i class="fas fa-chalkboard-teacher text-warning me-2"></i>
                            <small class="text-muted">طرق التدريس: <?php echo htmlspecialchars(substr($prep['teaching_methods'], 0, 50)); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($prep['homework']): ?>
                        <div class="mb-2">
                            <i class="fas fa-home text-info me-2"></i>
                            <small class="text-muted">الواجب: <?php echo htmlspecialchars(substr($prep['homework'], 0, 50)); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isAdmin()): ?>
                        <div class="mb-2">
                            <i class="fas fa-user text-primary me-2"></i>
                            <small><?php echo htmlspecialchars($prep['first_name'] . ' ' . $prep['last_name']); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <i class="fas fa-clock text-secondary me-2"></i>
                            <small class="text-muted">
                                أضيف في: <?php echo formatDateArabic($prep['created_at']); ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2 d-md-flex">
                            <a href="view.php?id=<?php echo $prep['id']; ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fas fa-eye me-1"></i>عرض
                            </a>
                            <?php if (isAdmin() || getCurrentTeacherId() == $prep['teacher_id']): ?>
                            <a href="edit.php?id=<?php echo $prep['id']; ?>" class="btn btn-outline-warning btn-sm flex-fill">
                                <i class="fas fa-edit me-1"></i>تعديل
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- روابط سريعة للأيام -->
        <div class="mt-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>انتقال سريع
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        $tomorrow = date('Y-m-d', strtotime('+1 day'));
                        ?>
                        
                        <a href="?date=<?php echo $yesterday; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-right me-1"></i>أمس
                        </a>
                        
                        <a href="?date=<?php echo $today; ?>" class="btn btn-<?php echo $selectedDate === $today ? 'primary' : 'outline-primary'; ?> btn-sm">
                            <i class="fas fa-calendar-day me-1"></i>اليوم
                        </a>
                        
                        <a href="?date=<?php echo $tomorrow; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>غداً
                        </a>
                        
                        <!-- أيام الأسبوع -->
                        <?php for ($i = 0; $i < 7; $i++): ?>
                        <?php 
                        $weekDay = date('Y-m-d', strtotime("monday this week +{$i} days"));
                        $dayName = ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'][$i];
                        ?>
                        <a href="?date=<?php echo $weekDay; ?>" class="btn btn-<?php echo $selectedDate === $weekDay ? 'success' : 'outline-success'; ?> btn-sm">
                            <?php echo $dayName; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
