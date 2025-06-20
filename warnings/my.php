<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المعلم
if (!isLoggedIn() || !isTeacher()) {
    redirect('../login.php');
}

// الحصول على بيانات المعلم الحالي
$currentTeacher = null;
try {
    $stmt = $db->query("SELECT * FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
    $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentTeacher) {
        showMessage('لم يتم العثور على بيانات المعلم', 'error');
        redirect('../index.php');
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلم', 'error');
    redirect('../index.php');
}

// معالجة تحديث حالة القراءة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $warningId = (int)($_POST['warning_id'] ?? 0);
    try {
        $stmt = $db->query("UPDATE warnings SET is_read = 1 WHERE id = ? AND teacher_id = ?", 
                          [$warningId, $currentTeacher['id']]);
        showMessage('تم تحديث حالة القراءة', 'success');
    } catch (Exception $e) {
        showMessage('خطأ في تحديث حالة القراءة', 'error');
    }
}

// معالجة إضافة رد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_response'])) {
    $warningId = (int)($_POST['warning_id'] ?? 0);
    $response = sanitize($_POST['response'] ?? '');
    
    if (!empty($response)) {
        try {
            $stmt = $db->query("UPDATE warnings SET response = ?, response_date = CURRENT_DATE WHERE id = ? AND teacher_id = ?", 
                              [$response, $warningId, $currentTeacher['id']]);
            showMessage('تم إضافة الرد بنجاح', 'success');
        } catch (Exception $e) {
            showMessage('خطأ في إضافة الرد', 'error');
        }
    }
}

// معالجة البحث والتصفية
$search = sanitize($_GET['search'] ?? '');
$type_filter = sanitize($_GET['type'] ?? '');
$read_filter = sanitize($_GET['read'] ?? '');

// بناء استعلام البحث
$sql = "SELECT w.*, u.username as issued_by_username 
        FROM warnings w 
        LEFT JOIN users u ON w.issued_by = u.id 
        WHERE w.teacher_id = ?";
$params = [$currentTeacher['id']];

if (!empty($search)) {
    $sql .= " AND (w.title LIKE ? OR w.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($type_filter)) {
    $sql .= " AND w.warning_type = ?";
    $params[] = $type_filter;
}

if ($read_filter !== '') {
    $sql .= " AND w.is_read = ?";
    $params[] = (int)$read_filter;
}

$sql .= " ORDER BY w.issue_date DESC, w.created_at DESC";

try {
    $stmt = $db->query($sql, $params);
    $warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات التنويهات', 'error');
    $warnings = [];
}

// حساب الإحصائيات
$totalWarnings = count($warnings);
$unreadWarnings = array_filter($warnings, function($w) { return $w['is_read'] == 0; });
$notices = array_filter($warnings, function($w) { return $w['warning_type'] == 'notice'; });
$actualWarnings = array_filter($warnings, function($w) { return $w['warning_type'] == 'warning'; });
$finalWarnings = array_filter($warnings, function($w) { return $w['warning_type'] == 'final_warning'; });
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - التنويهات والإنذارات</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .warning-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        
        .warning-card.notice {
            border-left-color: #17a2b8;
        }
        
        .warning-card.warning {
            border-left-color: #ffc107;
        }
        
        .warning-card.final_warning {
            border-left-color: #dc3545;
        }
        
        .warning-card.unread {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .warning-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .response-form {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
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
                            <i class="fas fa-bell me-3"></i>
                            التنويهات والإنذارات
                        </h1>
                        <p class="page-subtitle">متابعة التنويهات والإنذارات الواردة من الإدارة</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-bell fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo $totalWarnings; ?></h4>
                        <p class="text-muted mb-0">إجمالي التنويهات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-envelope fa-3x text-danger mb-3"></i>
                        <h4 class="text-danger"><?php echo count($unreadWarnings); ?></h4>
                        <p class="text-muted mb-0">غير مقروءة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                        <h4 class="text-info"><?php echo count($notices); ?></h4>
                        <p class="text-muted mb-0">تنويهات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4 class="text-warning"><?php echo count($actualWarnings) + count($finalWarnings); ?></h4>
                        <p class="text-muted mb-0">إنذارات</p>
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
                    <div class="col-md-4">
                        <label for="search" class="form-label">البحث</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="عنوان التنويه أو المحتوى">
                    </div>
                    <div class="col-md-4">
                        <label for="type_filter" class="form-label">نوع التنويه</label>
                        <select class="form-select" id="type_filter" name="type">
                            <option value="">جميع الأنواع</option>
                            <option value="notice" <?php echo $type_filter === 'notice' ? 'selected' : ''; ?>>تنويه</option>
                            <option value="warning" <?php echo $type_filter === 'warning' ? 'selected' : ''; ?>>إنذار</option>
                            <option value="final_warning" <?php echo $type_filter === 'final_warning' ? 'selected' : ''; ?>>إنذار نهائي</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="read_filter" class="form-label">حالة القراءة</label>
                        <select class="form-select" id="read_filter" name="read">
                            <option value="">جميع الحالات</option>
                            <option value="0" <?php echo $read_filter === '0' ? 'selected' : ''; ?>>غير مقروء</option>
                            <option value="1" <?php echo $read_filter === '1' ? 'selected' : ''; ?>>مقروء</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            بحث
                        </button>
                        <a href="my.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>
                            إعادة تعيين
                        </a>
                        <button type="button" class="btn btn-success" onclick="markAllAsRead()">
                            <i class="fas fa-check-double me-2"></i>
                            تحديد الكل كمقروء
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- قائمة التنويهات -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة التنويهات (<?php echo count($warnings); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($warnings)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد تنويهات</h5>
                    <p class="text-muted">لم يتم العثور على تنويهات تطابق معايير البحث</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($warnings as $warning): ?>
                    <?php
                    $typeIcons = [
                        'notice' => 'fas fa-info-circle text-info',
                        'warning' => 'fas fa-exclamation-triangle text-warning',
                        'final_warning' => 'fas fa-exclamation-circle text-danger'
                    ];
                    $typeNames = [
                        'notice' => 'تنويه',
                        'warning' => 'إنذار',
                        'final_warning' => 'إنذار نهائي'
                    ];
                    $typeColors = [
                        'notice' => 'info',
                        'warning' => 'warning',
                        'final_warning' => 'danger'
                    ];
                    ?>
                    <div class="col-12 mb-4">
                        <div class="card warning-card <?php echo $warning['warning_type']; ?> <?php echo $warning['is_read'] ? '' : 'unread'; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">
                                        <i class="<?php echo $typeIcons[$warning['warning_type']]; ?> me-2"></i>
                                        <?php echo htmlspecialchars($warning['title']); ?>
                                        <?php if (!$warning['is_read']): ?>
                                        <span class="badge bg-danger ms-2">جديد</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        من: <?php echo htmlspecialchars($warning['issued_by_username'] ?? 'الإدارة'); ?>
                                        <i class="fas fa-calendar me-1 ms-2"></i>
                                        <?php echo formatDateArabic($warning['issue_date']); ?>
                                    </small>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $typeColors[$warning['warning_type']]; ?>">
                                        <?php echo $typeNames[$warning['warning_type']]; ?>
                                    </span>
                                    <?php if (!$warning['is_read']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="warning_id" value="<?php echo $warning['id']; ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-check me-1"></i>
                                            تحديد كمقروء
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($warning['description'])); ?></p>
                                </div>
                                
                                <?php if (!empty($warning['response'])): ?>
                                <div class="alert alert-light">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-reply me-2"></i>
                                        ردي على التنويه:
                                    </h6>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($warning['response'])); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        تاريخ الرد: <?php echo formatDateArabic($warning['response_date']); ?>
                                    </small>
                                </div>
                                <?php elseif ($warning['warning_type'] !== 'notice'): ?>
                                <div class="response-form">
                                    <h6 class="mb-3">
                                        <i class="fas fa-reply me-2"></i>
                                        إضافة رد على الإنذار:
                                    </h6>
                                    <form method="POST">
                                        <input type="hidden" name="warning_id" value="<?php echo $warning['id']; ?>">
                                        <div class="mb-3">
                                            <textarea class="form-control" name="response" rows="3" 
                                                      placeholder="اكتب ردك على الإنذار..." required></textarea>
                                        </div>
                                        <button type="submit" name="add_response" class="btn btn-primary btn-sm">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            إرسال الرد
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        تاريخ الإنشاء: <?php echo formatDateArabic($warning['created_at']); ?>
                                    </small>
                                    <div>
                                        <?php if ($warning['is_read']): ?>
                                        <span class="badge bg-success">مقروء</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">غير مقروء</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // تحديد جميع التنويهات كمقروءة
        function markAllAsRead() {
            Swal.fire({
                title: 'تأكيد العملية',
                text: 'هل تريد تحديد جميع التنويهات كمقروءة؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، حدد الكل',
                cancelButtonText: 'إلغاء',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('mark_all_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('تم تحديد جميع التنويهات كمقروءة', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showToast('خطأ في العملية', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('خطأ في الاتصال', 'error');
                    });
                }
            });
        }
        
        // تحديث تلقائي للتنويهات الجديدة كل دقيقة
        setInterval(function() {
            fetch('check_new_warnings.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_count > 0) {
                    showToast(`لديك ${data.new_count} تنويه جديد`, 'info');
                }
            })
            .catch(error => {
                console.error('Error checking new warnings:', error);
            });
        }, 60000); // كل دقيقة
    </script>
</body>
</html>
