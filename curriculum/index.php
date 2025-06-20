<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// جلب متابعة المنهج حسب الصلاحيات
try {
    if (isAdmin()) {
        // المدير يرى متابعة جميع المعلمين
        $stmt = $db->query("SELECT cp.*, t.first_name, t.last_name, t.employee_id 
                           FROM curriculum_progress cp 
                           JOIN teachers t ON cp.teacher_id = t.id 
                           ORDER BY cp.subject, cp.grade_level, t.first_name");
    } else {
        // المعلم يرى متابعته فقط
        $teacherId = getCurrentTeacherId();
        if (!$teacherId) {
            showMessage('خطأ في تحديد هوية المعلم', 'error');
            redirect('../index.php');
        }
        
        $stmt = $db->query("SELECT cp.*, t.first_name, t.last_name, t.employee_id 
                           FROM curriculum_progress cp 
                           JOIN teachers t ON cp.teacher_id = t.id 
                           WHERE cp.teacher_id = ? 
                           ORDER BY cp.subject, cp.grade_level", [$teacherId]);
    }
    
    $curriculumProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $curriculumProgress = [];
    showMessage('خطأ في جلب متابعة المنهج: ' . $e->getMessage(), 'error');
}

// إحصائيات المنهج
try {
    if (isAdmin()) {
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_units,
                           AVG(completion_percentage) as avg_completion,
                           COUNT(CASE WHEN completion_percentage >= 100 THEN 1 END) as completed_units,
                           COUNT(CASE WHEN completion_percentage < 50 THEN 1 END) as behind_units
                           FROM curriculum_progress");
    } else {
        $teacherId = getCurrentTeacherId();
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_units,
                           AVG(completion_percentage) as avg_completion,
                           COUNT(CASE WHEN completion_percentage >= 100 THEN 1 END) as completed_units,
                           COUNT(CASE WHEN completion_percentage < 50 THEN 1 END) as behind_units
                           FROM curriculum_progress WHERE teacher_id = ?", [$teacherId]);
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = ['total_units' => 0, 'avg_completion' => 0, 'completed_units' => 0, 'behind_units' => 0];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متابعة المنهج - <?php echo APP_NAME; ?></title>
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
        .progress-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .progress-card:hover {
            transform: translateY(-3px);
        }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
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
                        <a class="nav-link active" href="index.php">متابعة المنهج</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../preparations/index.php">التحضيرات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../activities/index.php">الأنشطة</a>
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
        
        <!-- العنوان والإجراءات -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-book-open me-2"></i>متابعة المنهج
            </h2>
            <a href="add.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>إضافة وحدة جديدة
            </a>
        </div>
        
        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-book fa-2x mb-2"></i>
                    <h3><?php echo $stats['total_units']; ?></h3>
                    <p class="mb-0">إجمالي الوحدات</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-percentage fa-2x mb-2"></i>
                    <h3><?php echo round($stats['avg_completion'], 1); ?>%</h3>
                    <p class="mb-0">متوسط الإنجاز</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3><?php echo $stats['completed_units']; ?></h3>
                    <p class="mb-0">وحدات مكتملة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h3><?php echo $stats['behind_units']; ?></h3>
                    <p class="mb-0">وحدات متأخرة</p>
                </div>
            </div>
        </div>
        
        <!-- قائمة متابعة المنهج -->
        <?php if (empty($curriculumProgress)): ?>
        <div class="text-center py-5">
            <i class="fas fa-book-open fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">لا توجد وحدات منهج</h4>
            <p class="text-muted">لم يتم إضافة أي وحدات منهج للمتابعة بعد</p>
            <a href="add.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>إضافة أول وحدة
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($curriculumProgress as $progress): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card progress-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($progress['subject']); ?></h6>
                        <span class="badge bg-info"><?php echo htmlspecialchars($progress['grade_level']); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($progress['unit_name']); ?></h5>
                        
                        <!-- شريط التقدم -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">نسبة الإنجاز</small>
                                <small class="fw-bold"><?php echo round($progress['completion_percentage'], 1); ?>%</small>
                            </div>
                            <?php
                            $progressClass = '';
                            if ($progress['completion_percentage'] >= 100) {
                                $progressClass = 'bg-success';
                            } elseif ($progress['completion_percentage'] >= 75) {
                                $progressClass = 'bg-info';
                            } elseif ($progress['completion_percentage'] >= 50) {
                                $progressClass = 'bg-warning';
                            } else {
                                $progressClass = 'bg-danger';
                            }
                            ?>
                            <div class="progress progress-bar-custom">
                                <div class="progress-bar <?php echo $progressClass; ?>" 
                                     style="width: <?php echo min($progress['completion_percentage'], 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- تفاصيل الدروس -->
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <small class="text-muted">دروس مخططة</small>
                                <div class="fw-bold text-primary"><?php echo $progress['lessons_planned']; ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">دروس مكتملة</small>
                                <div class="fw-bold text-success"><?php echo $progress['lessons_completed']; ?></div>
                            </div>
                        </div>
                        
                        <?php if (isAdmin()): ?>
                        <div class="mb-2">
                            <i class="fas fa-user text-info me-2"></i>
                            <small><?php echo htmlspecialchars($progress['first_name'] . ' ' . $progress['last_name']); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($progress['notes']): ?>
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-sticky-note me-1"></i>
                                <?php echo htmlspecialchars(substr($progress['notes'], 0, 80)); ?>
                                <?php if (strlen($progress['notes']) > 80): ?>...<?php endif; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- حالة التقدم -->
                        <div class="mt-3">
                            <?php if ($progress['completion_percentage'] >= 100): ?>
                            <span class="badge bg-success w-100">مكتملة</span>
                            <?php elseif ($progress['completion_percentage'] >= 75): ?>
                            <span class="badge bg-info w-100">متقدمة</span>
                            <?php elseif ($progress['completion_percentage'] >= 50): ?>
                            <span class="badge bg-warning w-100">في المسار</span>
                            <?php else: ?>
                            <span class="badge bg-danger w-100">متأخرة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2 d-md-flex">
                            <a href="view.php?id=<?php echo $progress['id']; ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fas fa-eye me-1"></i>عرض
                            </a>
                            <?php if (isAdmin() || getCurrentTeacherId() == $progress['teacher_id']): ?>
                            <a href="edit.php?id=<?php echo $progress['id']; ?>" class="btn btn-outline-warning btn-sm flex-fill">
                                <i class="fas fa-edit me-1"></i>تحديث
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ملخص التقدم العام -->
        <?php if (isTeacher()): ?>
        <div class="mt-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>ملخص تقدمك في المنهج
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-primary"><?php echo $stats['total_units']; ?></h4>
                                <p class="text-muted mb-0">وحدات إجمالية</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-success"><?php echo $stats['completed_units']; ?></h4>
                                <p class="text-muted mb-0">وحدات مكتملة</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-danger"><?php echo $stats['behind_units']; ?></h4>
                                <p class="text-muted mb-0">وحدات متأخرة</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-info"><?php echo round($stats['avg_completion'], 1); ?>%</h4>
                            <p class="text-muted mb-0">متوسط الإنجاز</p>
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
