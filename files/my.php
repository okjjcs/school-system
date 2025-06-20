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

// إنشاء مجلد الملفات إذا لم يكن موجوداً
$uploadsDir = '../uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$teacherUploadsDir = $uploadsDir . '/teacher_' . $currentTeacher['id'];
if (!is_dir($teacherUploadsDir)) {
    mkdir($teacherUploadsDir, 0755, true);
}

// معالجة رفع الملفات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $category = sanitize($_POST['category'] ?? 'other');
    $description = sanitize($_POST['description'] ?? '');
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $originalName = $file['name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $tmpName = $file['tmp_name'];
        
        // التحقق من نوع الملف
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];
        
        if (!in_array($fileType, $allowedTypes)) {
            showMessage('نوع الملف غير مدعوم', 'error');
        } elseif ($fileSize > 10 * 1024 * 1024) { // 10MB
            showMessage('حجم الملف كبير جداً (الحد الأقصى 10 ميجابايت)', 'error');
        } else {
            // إنشاء اسم فريد للملف
            $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $teacherUploadsDir . '/' . $fileName;
            
            if (move_uploaded_file($tmpName, $filePath)) {
                try {
                    // حفظ معلومات الملف في قاعدة البيانات
                    $stmt = $db->query("INSERT INTO files (teacher_id, file_name, original_name, file_path, file_type, file_size, category, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                                      [$currentTeacher['id'], $fileName, $originalName, $filePath, $fileType, $fileSize, $category, $description, $_SESSION['user_id']]);
                    
                    showMessage('تم رفع الملف بنجاح', 'success');
                } catch (Exception $e) {
                    // حذف الملف في حالة فشل حفظ البيانات
                    unlink($filePath);
                    showMessage('خطأ في حفظ معلومات الملف', 'error');
                }
            } else {
                showMessage('خطأ في رفع الملف', 'error');
            }
        }
    } else {
        showMessage('يرجى اختيار ملف للرفع', 'error');
    }
}

// معالجة حذف الملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $fileId = (int)($_POST['file_id'] ?? 0);
    
    try {
        // جلب معلومات الملف
        $stmt = $db->query("SELECT * FROM files WHERE id = ? AND teacher_id = ?", [$fileId, $currentTeacher['id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            // حذف الملف من النظام
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            // حذف السجل من قاعدة البيانات
            $db->query("DELETE FROM files WHERE id = ?", [$fileId]);
            
            showMessage('تم حذف الملف بنجاح', 'success');
        } else {
            showMessage('الملف غير موجود', 'error');
        }
    } catch (Exception $e) {
        showMessage('خطأ في حذف الملف', 'error');
    }
}

// معالجة البحث والتصفية
$search = sanitize($_GET['search'] ?? '');
$category_filter = sanitize($_GET['category'] ?? '');

// بناء استعلام البحث
$sql = "SELECT * FROM files WHERE teacher_id = ?";
$params = [$currentTeacher['id']];

if (!empty($search)) {
    $sql .= " AND (original_name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $db->query($sql, $params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات الملفات', 'error');
    $files = [];
}

// حساب الإحصائيات
$totalFiles = count($files);
$totalSize = array_sum(array_column($files, 'file_size'));
$categories = array_count_values(array_column($files, 'category'));
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
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .file-card {
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
        }
        
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .file-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .file-size {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .upload-area.dragover {
            border-color: #28a745;
            background-color: #f0fff4;
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
                            <i class="fas fa-folder me-3"></i>
                            ملفاتي
                        </h1>
                        <p class="page-subtitle">إدارة ورفع الملفات والوثائق الخاصة بي</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-file fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo $totalFiles; ?></h4>
                        <p class="text-muted mb-0">إجمالي الملفات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-hdd fa-3x text-info mb-3"></i>
                        <h4 class="text-info"><?php echo formatFileSize($totalSize); ?></h4>
                        <p class="text-muted mb-0">المساحة المستخدمة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                        <h4 class="text-success"><?php echo $categories['certificate'] ?? 0; ?></h4>
                        <p class="text-muted mb-0">الشهادات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-file-alt fa-3x text-warning mb-3"></i>
                        <h4 class="text-warning"><?php echo $categories['document'] ?? 0; ?></h4>
                        <p class="text-muted mb-0">الوثائق</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- منطقة رفع الملفات -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cloud-upload-alt me-2"></i>
                    رفع ملف جديد
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>اسحب الملف هنا أو اضغط للاختيار</h5>
                                <p class="text-muted">الحد الأقصى: 10 ميجابايت | الأنواع المدعومة: PDF, Word, Excel, الصور</p>
                                <input type="file" id="fileInput" name="file" style="display: none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt" required>
                                <div id="selectedFile" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category" class="form-label">فئة الملف</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">اختر الفئة</option>
                                    <option value="certificate">شهادة</option>
                                    <option value="document">وثيقة</option>
                                    <option value="photo">صورة</option>
                                    <option value="report">تقرير</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">وصف الملف</label>
                                <textarea class="form-control" name="description" id="description" rows="3" placeholder="وصف مختصر للملف..."></textarea>
                            </div>
                            <button type="submit" name="upload_file" class="btn btn-primary w-100">
                                <i class="fas fa-upload me-2"></i>
                                رفع الملف
                            </button>
                        </div>
                    </div>
                </form>
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
                    <div class="col-md-6">
                        <label for="search" class="form-label">البحث</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="اسم الملف أو الوصف">
                    </div>
                    <div class="col-md-6">
                        <label for="category_filter" class="form-label">الفئة</label>
                        <select class="form-select" id="category_filter" name="category">
                            <option value="">جميع الفئات</option>
                            <option value="certificate" <?php echo $category_filter === 'certificate' ? 'selected' : ''; ?>>شهادة</option>
                            <option value="document" <?php echo $category_filter === 'document' ? 'selected' : ''; ?>>وثيقة</option>
                            <option value="photo" <?php echo $category_filter === 'photo' ? 'selected' : ''; ?>>صورة</option>
                            <option value="report" <?php echo $category_filter === 'report' ? 'selected' : ''; ?>>تقرير</option>
                            <option value="other" <?php echo $category_filter === 'other' ? 'selected' : ''; ?>>أخرى</option>
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
                    </div>
                </form>
            </div>
        </div>

        <!-- قائمة الملفات -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    ملفاتي (<?php echo count($files); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($files)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد ملفات</h5>
                    <p class="text-muted">لم يتم العثور على ملفات تطابق معايير البحث</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($files as $file): ?>
                    <?php
                    $fileIcon = '';
                    $iconColor = '';
                    
                    if (strpos($file['file_type'], 'image/') === 0) {
                        $fileIcon = 'fas fa-image';
                        $iconColor = 'text-success';
                    } elseif ($file['file_type'] === 'application/pdf') {
                        $fileIcon = 'fas fa-file-pdf';
                        $iconColor = 'text-danger';
                    } elseif (strpos($file['file_type'], 'word') !== false) {
                        $fileIcon = 'fas fa-file-word';
                        $iconColor = 'text-primary';
                    } elseif (strpos($file['file_type'], 'excel') !== false || strpos($file['file_type'], 'sheet') !== false) {
                        $fileIcon = 'fas fa-file-excel';
                        $iconColor = 'text-success';
                    } else {
                        $fileIcon = 'fas fa-file';
                        $iconColor = 'text-secondary';
                    }
                    
                    $categoryNames = [
                        'certificate' => 'شهادة',
                        'document' => 'وثيقة',
                        'photo' => 'صورة',
                        'report' => 'تقرير',
                        'other' => 'أخرى'
                    ];
                    
                    $categoryColors = [
                        'certificate' => 'success',
                        'document' => 'primary',
                        'photo' => 'info',
                        'report' => 'warning',
                        'other' => 'secondary'
                    ];
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card file-card h-100">
                            <div class="card-body text-center">
                                <i class="<?php echo $fileIcon; ?> file-icon <?php echo $iconColor; ?>"></i>
                                
                                <h6 class="card-title" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                    <?php echo htmlspecialchars(strlen($file['original_name']) > 20 ? substr($file['original_name'], 0, 20) . '...' : $file['original_name']); ?>
                                </h6>
                                
                                <div class="mb-3">
                                    <span class="badge bg-<?php echo $categoryColors[$file['category']] ?? 'secondary'; ?>">
                                        <?php echo $categoryNames[$file['category']] ?? $file['category']; ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted file-size">
                                        <i class="fas fa-hdd me-1"></i>
                                        <?php echo formatFileSize($file['file_size']); ?>
                                    </small>
                                </div>
                                
                                <?php if (!empty($file['description'])): ?>
                                <p class="small text-muted mb-3" title="<?php echo htmlspecialchars($file['description']); ?>">
                                    <?php echo htmlspecialchars(strlen($file['description']) > 50 ? substr($file['description'], 0, 50) . '...' : $file['description']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="btn-group w-100" role="group">
                                    <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="view.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete" 
                                            data-file-id="<?php echo $file['id']; ?>"
                                            data-file-name="<?php echo htmlspecialchars($file['original_name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo formatDateArabic($file['created_at']); ?>
                                </small>
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
        // معالجة اختيار الملف
        document.getElementById('fileInput').addEventListener('change', function() {
            const file = this.files[0];
            const selectedFileDiv = document.getElementById('selectedFile');
            
            if (file) {
                selectedFileDiv.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file me-2"></i>
                        <strong>${file.name}</strong> (${formatFileSize(file.size)})
                    </div>
                `;
            } else {
                selectedFileDiv.innerHTML = '';
            }
        });
        
        // معالجة السحب والإفلات
        const uploadArea = document.querySelector('.upload-area');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('fileInput').files = files;
                document.getElementById('fileInput').dispatchEvent(new Event('change'));
            }
        });
        
        // معالجة حذف الملف
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete')) {
                e.preventDefault();
                const button = e.target.closest('.btn-delete');
                const fileId = button.dataset.fileId;
                const fileName = button.dataset.fileName;
                
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: `هل أنت متأكد من حذف الملف "${fileName}"؟`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="file_id" value="${fileId}">
                            <input type="hidden" name="delete_file" value="1">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
        
        // دالة تنسيق حجم الملف
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>

<?php
// دالة تنسيق حجم الملف
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
}
?>
