<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// التحقق من أن المستخدم معلم
if (!isTeacher()) {
    redirect('../index.php');
}

// الحصول على معرف المعلم
$teacherId = null;
try {
    $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($teacher) {
        $teacherId = $teacher['id'];
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلم', 'error');
}

// جلب الملفات
$files = [];
if ($teacherId) {
    try {
        $stmt = $db->query("SELECT * FROM files WHERE teacher_id = ? ORDER BY created_at DESC", [$teacherId]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        showMessage('خطأ في جلب الملفات', 'error');
    }
}

// إنشاء كائن رفع الملفات للدوال المساعدة
$fileUpload = new FileUpload($db);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - ملفاتي</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .file-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .file-card:hover {
            transform: translateY(-5px);
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .file-preview {
            max-width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
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
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user me-1"></i>
                    ملفي الشخصي
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
        <div class="page-header text-center">
            <h1 class="display-5 mb-3">
                <i class="fas fa-folder me-3"></i>
                ملفاتي ومستنداتي
            </h1>
            <p class="lead mb-0">إدارة جميع الملفات والمستندات المرفوعة</p>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <i class="fas fa-file fa-2x mb-2"></i>
                        <h3><?php echo count($files); ?></h3>
                        <p class="mb-0">إجمالي الملفات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <i class="fas fa-image fa-2x mb-2"></i>
                        <h3><?php echo count(array_filter($files, function($f) { return strpos($f['file_type'], 'image/') === 0; })); ?></h3>
                        <p class="mb-0">الصور</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <i class="fas fa-file-pdf fa-2x mb-2"></i>
                        <h3><?php echo count(array_filter($files, function($f) { return strpos($f['file_type'], 'pdf') !== false; })); ?></h3>
                        <p class="mb-0">ملفات PDF</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body">
                        <i class="fas fa-file-word fa-2x mb-2"></i>
                        <h3><?php echo count(array_filter($files, function($f) { return strpos($f['file_type'], 'word') !== false || strpos($f['file_type'], 'document') !== false; })); ?></h3>
                        <p class="mb-0">مستندات Word</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="button" class="btn btn-success btn-lg me-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-plus me-2"></i>
                            رفع ملفات جديدة
                        </button>
                        <a href="../preparations/my.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-book me-2"></i>
                            تحضيراتي
                        </a>
                        <a href="../activities/my.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-trophy me-2"></i>
                            أنشطتي
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Files List -->
        <div class="row">
            <?php if (!empty($files)): ?>
                <?php foreach ($files as $file): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card file-card h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <?php if ($fileUpload->isImage($file['file_type'])): ?>
                                    <img src="<?php echo $fileUpload->getFileUrl($file['file_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($file['original_name']); ?>"
                                         class="file-preview">
                                <?php else: ?>
                                    <i class="<?php echo $fileUpload->getFileIcon($file['file_type']); ?> fa-4x mb-3"></i>
                                <?php endif; ?>
                            </div>
                            
                            <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                <?php echo htmlspecialchars($file['original_name']); ?>
                            </h6>
                            
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <small class="text-muted">الحجم</small><br>
                                    <span class="badge bg-info"><?php echo $fileUpload->formatFileSize($file['file_size']); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">النوع</small><br>
                                    <span class="badge bg-secondary"><?php echo ucfirst($file['category']); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($file['description'])): ?>
                            <p class="card-text">
                                <small class="text-muted"><?php echo nl2br(htmlspecialchars($file['description'])); ?></small>
                            </p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo formatDateArabic($file['created_at']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="btn-group w-100">
                                <a href="<?php echo $fileUpload->getFileUrl($file['file_path']); ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
                                <a href="<?php echo $fileUpload->getFileUrl($file['file_path']); ?>" 
                                   download="<?php echo $file['original_name']; ?>"
                                   class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-download"></i> تحميل
                                </a>
                                <button type="button" 
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="deleteFile(<?php echo $file['id']; ?>)">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card file-card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">لا توجد ملفات بعد</h4>
                            <p class="text-muted mb-4">ابدأ برفع ملفاتك ومستنداتك</p>
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="fas fa-plus me-2"></i>
                                رفع ملفات جديدة
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="uploadModalLabel">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        رفع ملفات جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../upload_files.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
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
                            <textarea class="form-control" id="file_description" name="file_description" rows="2"></textarea>
                        </div>
                        
                        <div class="upload-area border-2 border-dashed border-success rounded p-4 text-center" 
                             style="background-color: #f0fff4;">
                            <i class="fas fa-cloud-upload-alt fa-3x text-success mb-3"></i>
                            <h6 class="text-success">اسحب الملفات هنا أو اضغط للاختيار</h6>
                            <input type="file" name="files[]" id="modalFileInput" class="form-control d-none" multiple>
                            <button type="button" class="btn btn-success" onclick="document.getElementById('modalFileInput').click()">
                                اختيار الملفات
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success">رفع الملفات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function deleteFile(fileId) {
        if (confirm('هل أنت متأكد من حذف هذا الملف؟')) {
            fetch('../delete_file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ file_id: fileId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('فشل في حذف الملف: ' + data.message);
                }
            })
            .catch(error => {
                alert('حدث خطأ في حذف الملف');
            });
        }
    }
    </script>
</body>
</html>
