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

// الحصول على معرف النشاط
$activityId = (int)($_GET['id'] ?? 0);
if ($activityId <= 0) {
    showMessage('معرف النشاط غير صحيح', 'error');
    redirect('my.php');
}

// جلب بيانات النشاط
$activity = null;
try {
    $stmt = $db->query("SELECT * FROM activities WHERE id = ? AND teacher_id = ?", [$activityId, $currentTeacher['id']]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        showMessage('النشاط غير موجود أو ليس لديك صلاحية لتعديله', 'error');
        redirect('my.php');
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات النشاط', 'error');
    redirect('my.php');
}

$errors = [];

// معالجة تحديث النشاط
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $activityType = sanitize($_POST['activity_type'] ?? '');
    $targetGrade = sanitize($_POST['target_grade'] ?? '');
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');
    $participantsCount = (int)($_POST['participants_count'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'planned');
    $results = sanitize($_POST['results'] ?? '');
    
    // التحقق من صحة البيانات
    if (empty($title)) {
        $errors[] = 'عنوان النشاط مطلوب';
    }
    
    if (empty($description)) {
        $errors[] = 'وصف النشاط مطلوب';
    }
    
    if (empty($activityType)) {
        $errors[] = 'نوع النشاط مطلوب';
    }
    
    if (empty($startDate)) {
        $errors[] = 'تاريخ البداية مطلوب';
    }
    
    if (!empty($endDate) && !empty($startDate) && $endDate < $startDate) {
        $errors[] = 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية';
    }
    
    // تحديث النشاط إذا لم توجد أخطاء
    if (empty($errors)) {
        try {
            $stmt = $db->query("UPDATE activities SET title = ?, description = ?, activity_type = ?, target_grade = ?, start_date = ?, end_date = ?, participants_count = ?, status = ?, results = ? WHERE id = ? AND teacher_id = ?", 
                              [$title, $description, $activityType, $targetGrade, $startDate, $endDate ?: null, $participantsCount, $status, $results, $activityId, $currentTeacher['id']]);
            
            showMessage('تم تحديث النشاط بنجاح', 'success');
            redirect('view.php?id=' . $activityId);
            
        } catch (Exception $e) {
            $errors[] = 'خطأ في تحديث النشاط: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تعديل النشاط</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
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
                <a class="nav-link" href="view.php?id=<?php echo $activityId; ?>">
                    <i class="fas fa-arrow-right me-1"></i>
                    العودة للنشاط
                </a>
                <a class="nav-link" href="my.php">
                    <i class="fas fa-list me-1"></i>
                    قائمة الأنشطة
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <div class="container">
                        <h1 class="page-title">
                            <i class="fas fa-edit me-3"></i>
                            تعديل النشاط
                        </h1>
                        <p class="page-subtitle"><?php echo htmlspecialchars($activity['title']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض الأخطاء -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>يرجى تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- نموذج تعديل النشاط -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            تعديل بيانات النشاط
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- معلومات أساسية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        المعلومات الأساسية
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="title" class="form-label">عنوان النشاط *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? $activity['title']); ?>" 
                                           required placeholder="مثال: مسابقة الرياضيات الذهنية">
                                    <div class="invalid-feedback">يرجى إدخال عنوان النشاط</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="activity_type" class="form-label">نوع النشاط *</label>
                                    <select class="form-select" id="activity_type" name="activity_type" required>
                                        <option value="">اختر نوع النشاط</option>
                                        <option value="competition" <?php echo ($_POST['activity_type'] ?? $activity['activity_type']) === 'competition' ? 'selected' : ''; ?>>مسابقة</option>
                                        <option value="activity" <?php echo ($_POST['activity_type'] ?? $activity['activity_type']) === 'activity' ? 'selected' : ''; ?>>نشاط</option>
                                        <option value="project" <?php echo ($_POST['activity_type'] ?? $activity['activity_type']) === 'project' ? 'selected' : ''; ?>>مشروع</option>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار نوع النشاط</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="target_grade" class="form-label">الصف المستهدف</label>
                                    <select class="form-select" id="target_grade" name="target_grade">
                                        <option value="">جميع الصفوف</option>
                                        <option value="الصف الأول" <?php echo ($_POST['target_grade'] ?? $activity['target_grade']) === 'الصف الأول' ? 'selected' : ''; ?>>الصف الأول</option>
                                        <option value="الصف الثاني" <?php echo ($_POST['target_grade'] ?? $activity['target_grade']) === 'الصف الثاني' ? 'selected' : ''; ?>>الصف الثاني</option>
                                        <option value="الصف الثالث" <?php echo ($_POST['target_grade'] ?? $activity['target_grade']) === 'الصف الثالث' ? 'selected' : ''; ?>>الصف الثالث</option>
                                        <option value="الصف الرابع" <?php echo ($_POST['target_grade'] ?? $activity['target_grade']) === 'الصف الرابع' ? 'selected' : ''; ?>>الصف الرابع</option>
                                        <option value="الصف الخامس" <?php echo ($_POST['target_grade'] ?? $activity['target_grade']) === 'الصف الخامس' ? 'selected' : ''; ?>>الصف الخامس</option>
                                        <option value="الصف السادس" <?php echo ($_POST['target_grade'] ?? $activity['target_grade']) === 'الصف السادس' ? 'selected' : ''; ?>>الصف السادس</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">حالة النشاط</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="planned" <?php echo ($_POST['status'] ?? $activity['status']) === 'planned' ? 'selected' : ''; ?>>مخطط</option>
                                        <option value="ongoing" <?php echo ($_POST['status'] ?? $activity['status']) === 'ongoing' ? 'selected' : ''; ?>>جاري</option>
                                        <option value="completed" <?php echo ($_POST['status'] ?? $activity['status']) === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                                        <option value="cancelled" <?php echo ($_POST['status'] ?? $activity['status']) === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                    </select>
                                </div>
                            </div>

                            <!-- وصف النشاط -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-align-left me-2"></i>
                                        وصف النشاط
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">وصف النشاط *</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="6" required placeholder="اكتب وصفاً مفصلاً للنشاط، أهدافه، وكيفية تنفيذه..."><?php echo htmlspecialchars($_POST['description'] ?? $activity['description']); ?></textarea>
                                    <div class="form-text">اشرح النشاط بالتفصيل، أهدافه، والفائدة المرجوة منه</div>
                                    <div class="invalid-feedback">يرجى إدخال وصف النشاط</div>
                                </div>
                            </div>

                            <!-- التواريخ والمشاركون -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-calendar me-2"></i>
                                        التواريخ والمشاركون
                                    </h6>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">تاريخ البداية *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? $activity['start_date']); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ البداية</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">تاريخ النهاية</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo htmlspecialchars($_POST['end_date'] ?? $activity['end_date']); ?>">
                                    <div class="form-text">اختياري - اتركه فارغاً إذا كان النشاط ليوم واحد</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="participants_count" class="form-label">عدد المشاركين</label>
                                    <input type="number" class="form-control" id="participants_count" name="participants_count" 
                                           value="<?php echo htmlspecialchars($_POST['participants_count'] ?? $activity['participants_count']); ?>" 
                                           min="0" placeholder="0">
                                    <div class="form-text">العدد الفعلي للمشاركين</div>
                                </div>
                            </div>

                            <!-- النتائج -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-award me-2"></i>
                                        النتائج والملاحظات
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="results" class="form-label">النتائج والملاحظات</label>
                                    <textarea class="form-control" id="results" name="results" 
                                              rows="4" placeholder="اكتب النتائج، الفائزين، الملاحظات، أو أي معلومات إضافية..."><?php echo htmlspecialchars($_POST['results'] ?? $activity['results']); ?></textarea>
                                    <div class="form-text">النتائج النهائية والملاحظات حول النشاط</div>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="row">
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>
                                                حفظ التعديلات
                                            </button>
                                        </div>
                                        <div>
                                            <a href="view.php?id=<?php echo $activityId; ?>" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-times me-2"></i>
                                                إلغاء
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // التحقق من التواريخ
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = this.value;
            
            if (startDate && endDate && endDate < startDate) {
                this.setCustomValidity('تاريخ النهاية يجب أن يكون بعد تاريخ البداية');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
