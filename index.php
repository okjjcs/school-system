<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

// جلب إحصائيات النظام
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
    $teachersCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM teachers WHERE is_present = 1");
    $presentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM daily_preparations WHERE preparation_date = date('now')");
    $todayPreparations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM activities WHERE status = 'ongoing'");
    $ongoingActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (Exception $e) {
    $teachersCount = 0;
    $presentCount = 0;
    $todayPreparations = 0;
    $ongoingActivities = 0;
}

// جلب آخر المعلمين المضافين
try {
    $stmt = $db->query("SELECT t.*, u.username FROM teachers t
                       JOIN users u ON t.user_id = u.id
                       ORDER BY t.created_at DESC LIMIT 5");
    $recentTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentTeachers = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
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
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .welcome-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
        }
        .action-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- شريط التنقل -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i><?php echo APP_NAME; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>الرئيسية
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>المعلمون
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="teachers/add.php">إضافة معلم</a></li>
                            <li><a class="dropdown-item" href="teachers/list.php">قائمة المعلمين</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if (isTeacher()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers/profile.php">
                            <i class="fas fa-user me-1"></i>ملفي الشخصي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teachers/files.php">
                            <i class="fas fa-folder me-1"></i>ملفاتي
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-book me-1"></i>التحضيرات
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="preparations/add.php">
                                <i class="fas fa-plus me-1"></i>إضافة تحضير جديد
                            </a></li>
                            <li><a class="dropdown-item" href="preparations/my.php">
                                <i class="fas fa-list me-1"></i>تحضيراتي
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-trophy me-1"></i>الأنشطة
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="activities/add.php">
                                <i class="fas fa-plus me-1"></i>إضافة نشاط جديد
                            </a></li>
                            <li><a class="dropdown-item" href="activities/my.php">
                                <i class="fas fa-list me-1"></i>أنشطتي
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>تسجيل الخروج
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php displayMessage(); ?>

        <!-- ترحيب -->
        <div class="card welcome-card mb-4">
            <div class="card-body text-center py-5">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-graduation-cap me-3"></i>
                    مرحباً بك في <?php echo APP_NAME; ?>
                </h1>
                <p class="lead">
                    أهلاً وسهلاً <?php echo $_SESSION['username']; ?> -
                    <?php echo $_SESSION['role'] === 'admin' ? 'مدير النظام' : 'معلم'; ?>
                </p>
                <p class="mb-0">نظام شامل لإدارة وأرشفة بيانات المعلمين والأنشطة التعليمية</p>
            </div>
        </div>

        <!-- الإحصائيات -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="mt-2"><?php echo $teachersCount; ?></h3>
                    <p class="mb-0">إجمالي المعلمين</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 class="mt-2"><?php echo $presentCount; ?></h3>
                    <p class="mb-0">المعلمون الحاضرون</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="mt-2"><?php echo $todayPreparations; ?></h3>
                    <p class="mb-0">تحضيرات اليوم</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="mt-2"><?php echo $ongoingActivities; ?></h3>
                    <p class="mb-0">الأنشطة الجارية</p>
                </div>
            </div>
        </div>

        <!-- إحصائيات المعلم الشخصية -->
        <?php if (isTeacher()): ?>
        <?php
        // جلب إحصائيات المعلم الحالي
        try {
            $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
            $currentTeacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($currentTeacher) {
                $teacherId = $currentTeacher['id'];

                // إحصائيات التحضيرات
                $stmt = $db->query("SELECT COUNT(*) as total FROM daily_preparations WHERE teacher_id = ?", [$teacherId]);
                $myPreparations = $stmt->fetchColumn() ?: 0;

                // إحصائيات الأنشطة
                $stmt = $db->query("SELECT COUNT(*) as total FROM activities WHERE teacher_id = ?", [$teacherId]);
                $myActivities = $stmt->fetchColumn() ?: 0;

                // تحضيرات هذا الشهر
                $stmt = $db->query("SELECT COUNT(*) as total FROM daily_preparations WHERE teacher_id = ? AND strftime('%Y-%m', preparation_date) = strftime('%Y-%m', 'now')", [$teacherId]);
                $thisMonthPreparations = $stmt->fetchColumn() ?: 0;

                // أنشطة هذا الشهر
                $stmt = $db->query("SELECT COUNT(*) as total FROM activities WHERE teacher_id = ? AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')", [$teacherId]);
                $thisMonthActivities = $stmt->fetchColumn() ?: 0;
            } else {
                $myPreparations = $myActivities = $thisMonthPreparations = $thisMonthActivities = 0;
            }
        } catch (Exception $e) {
            $myPreparations = $myActivities = $thisMonthPreparations = $thisMonthActivities = 0;
        }
        ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>إحصائياتي الشخصية
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-book text-success fa-2x mb-2"></i>
                                    <h4 class="text-success"><?php echo $myPreparations; ?></h4>
                                    <small class="text-muted">إجمالي تحضيراتي</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-trophy text-warning fa-2x mb-2"></i>
                                    <h4 class="text-warning"><?php echo $myActivities; ?></h4>
                                    <small class="text-muted">إجمالي أنشطتي</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-calendar-check text-primary fa-2x mb-2"></i>
                                    <h4 class="text-primary"><?php echo $thisMonthPreparations; ?></h4>
                                    <small class="text-muted">تحضيرات هذا الشهر</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-star text-danger fa-2x mb-2"></i>
                                    <h4 class="text-danger"><?php echo $thisMonthActivities; ?></h4>
                                    <small class="text-muted">أنشطة هذا الشهر</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- الإجراءات السريعة -->
            <div class="col-md-6">
                <div class="card action-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>الإجراءات السريعة
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isAdmin()): ?>
                        <div class="row g-2">
                            <div class="col-12">
                                <a href="teachers/add.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-user-plus me-2"></i>إضافة معلم جديد
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="teachers/list.php" class="btn btn-info w-100">
                                    <i class="fas fa-list me-1"></i>قائمة المعلمين
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="admin/subjects.php" class="btn btn-warning w-100">
                                    <i class="fas fa-book me-1"></i>إدارة الاختصاصات
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="admin/grades.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-layer-group me-1"></i>إدارة الصفوف
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="reports/" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-chart-bar me-1"></i>التقارير
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="row g-2">
                            <div class="col-12">
                                <a href="teachers/profile.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-user me-2"></i>ملفي الشخصي
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="preparations/add.php" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-1"></i>تحضير جديد
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="preparations/my.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-book me-1"></i>تحضيراتي
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="activities/add.php" class="btn btn-warning w-100">
                                    <i class="fas fa-plus me-1"></i>نشاط جديد
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="activities/my.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-trophy me-1"></i>أنشطتي
                                </a>
                            </div>
                            <div class="col-12 mt-2">
                                <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>رفع ملفات ومستندات
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- آخر المعلمين المضافين -->
            <div class="col-md-6">
                <div class="card action-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>آخر المعلمين المضافين
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentTeachers)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentTeachers as $teacher): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($teacher['username']); ?> |
                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($teacher['subject']); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo formatDateArabic($teacher['created_at']); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>لا يوجد معلمون مضافون بعد</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة رفع الملفات -->
    <?php if (isTeacher()): ?>
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        رفع ملفات ومستندات
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="upload_files.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            يمكنك رفع الملفات والمستندات التي تحتاجها في التحضيرات والأنشطة
                        </div>

                        <div class="mb-3">
                            <label for="file_category" class="form-label">نوع الملف</label>
                            <select class="form-select" id="file_category" name="file_category" required>
                                <option value="">اختر نوع الملف</option>
                                <option value="document">مستند</option>
                                <option value="photo">صورة</option>
                                <option value="certificate">شهادة</option>
                                <option value="report">تقرير</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="file_description" class="form-label">وصف الملف (اختياري)</label>
                            <textarea class="form-control" id="file_description" name="file_description" rows="2" placeholder="وصف مختصر للملف..."></textarea>
                        </div>

                        <!-- منطقة رفع الملفات -->
                        <div class="upload-area border-2 border-dashed border-info rounded p-4 text-center"
                             style="background-color: #f0f8ff;">
                            <div class="upload-icon mb-3">
                                <i class="fas fa-cloud-upload-alt fa-3x text-info"></i>
                            </div>
                            <h6 class="text-info">اسحب الملفات هنا أو اضغط للاختيار</h6>
                            <p class="text-muted mb-3">
                                يمكنك رفع عدة ملفات في نفس الوقت
                            </p>
                            <input type="file"
                                   name="files[]"
                                   id="modalFileInput"
                                   class="form-control d-none"
                                   multiple
                                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                            <button type="button" class="btn btn-info" onclick="document.getElementById('modalFileInput').click()">
                                <i class="fas fa-plus me-2"></i>
                                اختيار الملفات
                            </button>
                            <div class="mt-2">
                                <small class="text-muted">
                                    الحد الأقصى: 10 ميجابايت لكل ملف
                                </small>
                            </div>
                        </div>

                        <!-- معاينة الملفات المختارة -->
                        <div id="modalSelectedFiles" class="mt-3" style="display: none;">
                            <h6 class="text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                الملفات المختارة:
                            </h6>
                            <div id="filesList" class="list-group"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>إلغاء
                        </button>
                        <button type="submit" class="btn btn-info" id="uploadBtn" disabled>
                            <i class="fas fa-upload me-2"></i>رفع الملفات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- تذييل الصفحة -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - جميع الحقوق محفوظة
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // معالجة رفع الملفات في النافذة المنبثقة
    document.getElementById('modalFileInput').addEventListener('change', function(e) {
        const files = e.target.files;
        const selectedFilesDiv = document.getElementById('modalSelectedFiles');
        const filesList = document.getElementById('filesList');
        const uploadBtn = document.getElementById('uploadBtn');

        if (files.length > 0) {
            selectedFilesDiv.style.display = 'block';
            uploadBtn.disabled = false;

            let html = '';
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = formatFileSize(file.size);
                const fileIcon = getFileIconByName(file.name);

                html += `
                    <div class="list-group-item d-flex align-items-center">
                        <div class="file-icon me-3">
                            <i class="${fileIcon} fa-lg"></i>
                        </div>
                        <div class="file-info flex-grow-1">
                            <h6 class="mb-1">${file.name}</h6>
                            <small class="text-muted">${fileSize}</small>
                        </div>
                        <div class="file-status">
                            <span class="badge bg-primary">جاهز للرفع</span>
                        </div>
                    </div>
                `;
            }

            filesList.innerHTML = html;
        } else {
            selectedFilesDiv.style.display = 'none';
            uploadBtn.disabled = true;
        }
    });

    // السحب والإفلات للنافذة المنبثقة
    const modalUploadArea = document.querySelector('#uploadModal .upload-area');

    if (modalUploadArea) {
        modalUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#e6f3ff';
            this.style.borderColor = '#17a2b8';
        });

        modalUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f0f8ff';
            this.style.borderColor = '#17a2b8';
        });

        modalUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f0f8ff';
            this.style.borderColor = '#17a2b8';

            const files = e.dataTransfer.files;
            document.getElementById('modalFileInput').files = files;

            // تشغيل حدث التغيير
            const event = new Event('change', { bubbles: true });
            document.getElementById('modalFileInput').dispatchEvent(event);
        });

        modalUploadArea.addEventListener('click', function() {
            document.getElementById('modalFileInput').click();
        });
    }

    // دوال مساعدة
    function formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

    function getFileIconByName(fileName) {
        const extension = fileName.split('.').pop().toLowerCase();

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            return 'fas fa-image text-success';
        } else if (extension === 'pdf') {
            return 'fas fa-file-pdf text-danger';
        } else if (['doc', 'docx'].includes(extension)) {
            return 'fas fa-file-word text-primary';
        } else if (['xls', 'xlsx'].includes(extension)) {
            return 'fas fa-file-excel text-success';
        } else if (['ppt', 'pptx'].includes(extension)) {
            return 'fas fa-file-powerpoint text-warning';
        } else if (['zip', 'rar'].includes(extension)) {
            return 'fas fa-file-archive text-secondary';
        } else {
            return 'fas fa-file text-muted';
        }
    }
    </script>
</body>
</html>
