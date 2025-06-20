<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// الحصول على معرف التحضير
$prepId = (int)($_GET['id'] ?? 0);
if ($prepId <= 0) {
    showMessage('معرف التحضير غير صحيح', 'error');
    redirect('my.php');
}

// جلب بيانات التحضير
$preparation = null;
$teacher = null;

try {
    $sql = "SELECT dp.*, t.first_name, t.last_name, t.employee_id 
            FROM daily_preparations dp 
            JOIN teachers t ON dp.teacher_id = t.id 
            WHERE dp.id = ?";
    
    // إذا كان المستخدم معلماً، تأكد من أن التحضير خاص به
    if (isTeacher()) {
        $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentTeacher) {
            $sql .= " AND dp.teacher_id = ?";
            $stmt = $db->query($sql, [$prepId, $currentTeacher['id']]);
        } else {
            throw new Exception('لم يتم العثور على بيانات المعلم');
        }
    } else {
        $stmt = $db->query($sql, [$prepId]);
    }
    
    $preparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$preparation) {
        showMessage('لم يتم العثور على التحضير المطلوب', 'error');
        redirect('my.php');
    }
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات التحضير', 'error');
    redirect('my.php');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - عرض التحضير</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
            body { background: white !important; }
        }
        
        .preparation-section {
            border-right: 4px solid #007bff;
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .preparation-section h6 {
            color: #007bff;
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-school me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="my.php">
                    <i class="fas fa-arrow-right me-1"></i>
                    العودة للقائمة
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
        <?php displayMessage(); ?>
        
        <!-- أزرار الإجراءات -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="text-primary">
                        <i class="fas fa-eye me-2"></i>
                        عرض التحضير
                    </h2>
                    <div class="btn-group" role="group">
                        <?php if (isTeacher()): ?>
                        <a href="edit.php?id=<?php echo $preparation['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>
                            تعديل
                        </a>
                        <a href="duplicate.php?id=<?php echo $preparation['id']; ?>" class="btn btn-info">
                            <i class="fas fa-copy me-2"></i>
                            نسخ
                        </a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print me-2"></i>
                            طباعة
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-2"></i>
                                تصدير
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?id=<?php echo $preparation['id']; ?>&format=pdf">PDF</a></li>
                                <li><a class="dropdown-item" href="export.php?id=<?php echo $preparation['id']; ?>&format=word">Word</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقة التحضير -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-0">
                                    <i class="fas fa-book me-2"></i>
                                    <?php echo htmlspecialchars($preparation['lesson_title']); ?>
                                </h4>
                                <p class="mb-0 mt-2">
                                    <i class="fas fa-user me-2"></i>
                                    المعلم: <?php echo htmlspecialchars($preparation['first_name'] . ' ' . $preparation['last_name']); ?>
                                    <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($preparation['employee_id']); ?></span>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="text-white">
                                    <div><i class="fas fa-calendar me-1"></i> <?php echo formatDateArabic($preparation['preparation_date']); ?></div>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-primary"><?php echo htmlspecialchars($preparation['subject']); ?></span>
                                        <span class="badge bg-light text-success"><?php echo htmlspecialchars($preparation['grade']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- أهداف الدرس -->
                        <div class="preparation-section">
                            <h6><i class="fas fa-bullseye me-2"></i>أهداف الدرس</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['lesson_objectives'])); ?>
                            </div>
                        </div>

                        <!-- محتوى الدرس -->
                        <div class="preparation-section">
                            <h6><i class="fas fa-book-open me-2"></i>محتوى الدرس</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['lesson_content'])); ?>
                            </div>
                        </div>

                        <!-- طرق التدريس -->
                        <?php if (!empty($preparation['teaching_methods'])): ?>
                        <div class="preparation-section">
                            <h6><i class="fas fa-chalkboard-teacher me-2"></i>طرق التدريس</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['teaching_methods'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- الوسائل والمصادر -->
                        <?php if (!empty($preparation['resources'])): ?>
                        <div class="preparation-section">
                            <h6><i class="fas fa-tools me-2"></i>الوسائل والمصادر</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['resources'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- طرق التقييم -->
                        <?php if (!empty($preparation['evaluation_methods'])): ?>
                        <div class="preparation-section">
                            <h6><i class="fas fa-clipboard-check me-2"></i>طرق التقييم</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['evaluation_methods'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- الواجب المنزلي -->
                        <?php if (!empty($preparation['homework'])): ?>
                        <div class="preparation-section">
                            <h6><i class="fas fa-home me-2"></i>الواجب المنزلي</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['homework'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- ملاحظات إضافية -->
                        <?php if (!empty($preparation['notes'])): ?>
                        <div class="preparation-section">
                            <h6><i class="fas fa-sticky-note me-2"></i>ملاحظات إضافية</h6>
                            <div class="content">
                                <?php echo nl2br(htmlspecialchars($preparation['notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- معلومات إضافية -->
                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-plus-circle me-1"></i>
                                    تاريخ الإنشاء: <?php echo formatDateArabic($preparation['created_at']); ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <i class="fas fa-edit me-1"></i>
                                    آخر تحديث: <?php echo formatDateArabic($preparation['updated_at']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أزرار إضافية -->
        <div class="row mt-4 no-print">
            <div class="col-12 text-center">
                <div class="btn-group" role="group">
                    <a href="my.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>
                        جميع التحضيرات
                    </a>
                    <a href="add.php" class="btn btn-outline-success">
                        <i class="fas fa-plus me-2"></i>
                        إضافة تحضير جديد
                    </a>
                    <?php if (isTeacher()): ?>
                    <a href="edit.php?id=<?php echo $preparation['id']; ?>" class="btn btn-outline-warning">
                        <i class="fas fa-edit me-2"></i>
                        تعديل هذا التحضير
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // تحسين الطباعة
        window.addEventListener('beforeprint', function() {
            document.title = 'تحضير - <?php echo htmlspecialchars($preparation['lesson_title']); ?>';
        });
        
        // إضافة تاريخ الطباعة
        window.addEventListener('beforeprint', function() {
            const printDate = document.createElement('div');
            printDate.className = 'print-only text-center mt-3';
            printDate.innerHTML = '<small>تم الطباعة في: ' + new Date().toLocaleDateString('ar-SA') + '</small>';
            document.querySelector('.card-body').appendChild(printDate);
        });
    </script>
</body>
</html>
