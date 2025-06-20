<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// جلب الأنشطة حسب الصلاحيات
try {
    if (isAdmin()) {
        // المدير يرى جميع الأنشطة
        $stmt = $db->query("SELECT a.*, t.first_name, t.last_name, t.employee_id 
                           FROM activities a 
                           JOIN teachers t ON a.teacher_id = t.id 
                           ORDER BY a.activity_date DESC, a.created_at DESC");
    } else {
        // المعلم يرى أنشطته فقط
        $teacherId = getCurrentTeacherId();
        if (!$teacherId) {
            showMessage('خطأ في تحديد هوية المعلم', 'error');
            redirect('../index.php');
        }
        
        $stmt = $db->query("SELECT a.*, t.first_name, t.last_name, t.employee_id 
                           FROM activities a 
                           JOIN teachers t ON a.teacher_id = t.id 
                           WHERE a.teacher_id = ? 
                           ORDER BY a.activity_date DESC, a.created_at DESC", [$teacherId]);
    }
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $activities = [];
    showMessage('خطأ في جلب الأنشطة: ' . $e->getMessage(), 'error');
}

// إحصائيات الأنشطة
try {
    if (isAdmin()) {
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           COUNT(CASE WHEN status = 'planned' THEN 1 END) as planned,
                           COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing,
                           COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                           FROM activities");
    } else {
        $teacherId = getCurrentTeacherId();
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           COUNT(CASE WHEN status = 'planned' THEN 1 END) as planned,
                           COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing,
                           COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                           FROM activities WHERE teacher_id = ?", [$teacherId]);
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = ['total' => 0, 'planned' => 0, 'ongoing' => 0, 'completed' => 0];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأنشطة والمسابقات - <?php echo APP_NAME; ?></title>
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
        .activity-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .activity-card:hover {
            transform: translateY(-3px);
        }
        .status-badge {
            font-size: 0.8rem;
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
                        <a class="nav-link active" href="index.php">الأنشطة</a>
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
        
        <!-- العنوان والإجراءات -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-trophy me-2"></i>الأنشطة والمسابقات
            </h2>
            <a href="add.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>إضافة نشاط جديد
            </a>
        </div>
        
        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-list fa-2x mb-2"></i>
                    <h3><?php echo $stats['total']; ?></h3>
                    <p class="mb-0">إجمالي الأنشطة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3><?php echo $stats['planned']; ?></h3>
                    <p class="mb-0">مخطط لها</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-play fa-2x mb-2"></i>
                    <h3><?php echo $stats['ongoing']; ?></h3>
                    <p class="mb-0">جارية</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-check fa-2x mb-2"></i>
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p class="mb-0">مكتملة</p>
                </div>
            </div>
        </div>
        
        <!-- قائمة الأنشطة -->
        <?php if (empty($activities)): ?>
        <div class="text-center py-5">
            <i class="fas fa-trophy fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">لا توجد أنشطة</h4>
            <p class="text-muted">لم يتم إضافة أي أنشطة أو مسابقات بعد</p>
            <a href="add.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>إضافة أول نشاط
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($activities as $activity): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card activity-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($activity['activity_type'] ?: 'نشاط'); ?></h6>
                        <?php
                        $statusClass = '';
                        $statusText = '';
                        switch ($activity['status']) {
                            case 'planned':
                                $statusClass = 'bg-warning';
                                $statusText = 'مخطط';
                                break;
                            case 'ongoing':
                                $statusClass = 'bg-info';
                                $statusText = 'جاري';
                                break;
                            case 'completed':
                                $statusClass = 'bg-success';
                                $statusText = 'مكتمل';
                                break;
                            default:
                                $statusClass = 'bg-secondary';
                                $statusText = 'غير محدد';
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?> status-badge"><?php echo $statusText; ?></span>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h5>
                        
                        <?php if (!empty($activity['description'])): ?>
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($activity['description'], 0, 100)); ?>
                            <?php if (strlen($activity['description']) > 100): ?>...<?php endif; ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="row text-center mb-3">
                            <?php if ($activity['participants_count']): ?>
                            <div class="col-6">
                                <small class="text-muted">المشاركون</small>
                                <div class="fw-bold"><?php echo $activity['participants_count']; ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($activity['duration']): ?>
                            <div class="col-6">
                                <small class="text-muted">المدة (دقيقة)</small>
                                <div class="fw-bold"><?php echo $activity['duration']; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($activity['activity_date']): ?>
                        <div class="mb-2">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            <small><?php echo formatDateArabic($activity['activity_date']); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($activity['location']): ?>
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt text-success me-2"></i>
                            <small><?php echo htmlspecialchars($activity['location']); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isAdmin()): ?>
                        <div class="mb-2">
                            <i class="fas fa-user text-info me-2"></i>
                            <small><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2 d-md-flex">
                            <a href="view.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fas fa-eye me-1"></i>عرض
                            </a>
                            <?php if (isAdmin() || getCurrentTeacherId() == $activity['teacher_id']): ?>
                            <a href="edit.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-warning btn-sm flex-fill">
                                <i class="fas fa-edit me-1"></i>تعديل
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
