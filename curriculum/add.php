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

$errors = [];

// معالجة إضافة وحدة المنهج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject'] ?? '');
    $grade = sanitize($_POST['grade'] ?? '');
    $unitNumber = (int)($_POST['unit_number'] ?? 0);
    $unitTitle = sanitize($_POST['unit_title'] ?? '');
    $totalLessons = (int)($_POST['total_lessons'] ?? 0);
    $completedLessons = (int)($_POST['completed_lessons'] ?? 0);
    $startDate = sanitize($_POST['start_date'] ?? '');
    $expectedEndDate = sanitize($_POST['expected_end_date'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // التحقق من صحة البيانات
    if (empty($subject)) {
        $errors[] = 'المادة مطلوبة';
    }
    
    if (empty($grade)) {
        $errors[] = 'الصف مطلوب';
    }
    
    if ($unitNumber <= 0) {
        $errors[] = 'رقم الوحدة يجب أن يكون أكبر من صفر';
    }
    
    if (empty($unitTitle)) {
        $errors[] = 'عنوان الوحدة مطلوب';
    }
    
    if ($totalLessons <= 0) {
        $errors[] = 'عدد الدروس يجب أن يكون أكبر من صفر';
    }
    
    if ($completedLessons > $totalLessons) {
        $errors[] = 'عدد الدروس المكتملة لا يمكن أن يكون أكبر من إجمالي الدروس';
    }
    
    if (empty($startDate)) {
        $errors[] = 'تاريخ البداية مطلوب';
    }
    
    // التحقق من عدم تكرار الوحدة
    if (empty($errors)) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM curriculum_progress WHERE teacher_id = ? AND subject = ? AND grade = ? AND unit_number = ?", 
                              [$currentTeacher['id'], $subject, $grade, $unitNumber]);
            $exists = $stmt->fetchColumn();
            
            if ($exists > 0) {
                $errors[] = 'هذه الوحدة موجودة بالفعل لنفس المادة والصف';
            }
        } catch (Exception $e) {
            $errors[] = 'خطأ في التحقق من الوحدة';
        }
    }
    
    // إضافة الوحدة إذا لم توجد أخطاء
    if (empty($errors)) {
        try {
            $progressPercentage = ($totalLessons > 0) ? ($completedLessons / $totalLessons) * 100 : 0;
            
            $stmt = $db->query("INSERT INTO curriculum_progress (teacher_id, subject, grade, unit_number, unit_title, total_lessons, completed_lessons, start_date, expected_end_date, progress_percentage, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                              [$currentTeacher['id'], $subject, $grade, $unitNumber, $unitTitle, $totalLessons, $completedLessons, $startDate, $expectedEndDate ?: null, $progressPercentage, $notes]);
            
            showMessage('تم إضافة وحدة المنهج بنجاح', 'success');
            redirect('my.php');
            
        } catch (Exception $e) {
            $errors[] = 'خطأ في إضافة وحدة المنهج: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - إضافة وحدة منهج جديدة</title>
    
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <div class="container">
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle me-3"></i>
                            إضافة وحدة منهج جديدة
                        </h1>
                        <p class="page-subtitle">إضافة وحدة جديدة لمتابعة تقدم المنهج</p>
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

        <!-- نموذج إضافة وحدة المنهج -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            بيانات وحدة المنهج
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
                                <div class="col-md-4 mb-3">
                                    <label for="subject" class="form-label">المادة *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">اختر المادة</option>
                                        <option value="الرياضيات" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'الرياضيات' ? 'selected' : ''; ?>>الرياضيات</option>
                                        <option value="اللغة العربية" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'اللغة العربية' ? 'selected' : ''; ?>>اللغة العربية</option>
                                        <option value="اللغة الإنجليزية" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'اللغة الإنجليزية' ? 'selected' : ''; ?>>اللغة الإنجليزية</option>
                                        <option value="العلوم" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'العلوم' ? 'selected' : ''; ?>>العلوم</option>
                                        <option value="الاجتماعيات" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'الاجتماعيات' ? 'selected' : ''; ?>>الاجتماعيات</option>
                                        <option value="التربية الإسلامية" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'التربية الإسلامية' ? 'selected' : ''; ?>>التربية الإسلامية</option>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار المادة</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="grade" class="form-label">الصف *</label>
                                    <select class="form-select" id="grade" name="grade" required>
                                        <option value="">اختر الصف</option>
                                        <option value="الصف الأول" <?php echo ($_POST['grade'] ?? $currentTeacher['grade_level']) === 'الصف الأول' ? 'selected' : ''; ?>>الصف الأول</option>
                                        <option value="الصف الثاني" <?php echo ($_POST['grade'] ?? $currentTeacher['grade_level']) === 'الصف الثاني' ? 'selected' : ''; ?>>الصف الثاني</option>
                                        <option value="الصف الثالث" <?php echo ($_POST['grade'] ?? $currentTeacher['grade_level']) === 'الصف الثالث' ? 'selected' : ''; ?>>الصف الثالث</option>
                                        <option value="الصف الرابع" <?php echo ($_POST['grade'] ?? $currentTeacher['grade_level']) === 'الصف الرابع' ? 'selected' : ''; ?>>الصف الرابع</option>
                                        <option value="الصف الخامس" <?php echo ($_POST['grade'] ?? $currentTeacher['grade_level']) === 'الصف الخامس' ? 'selected' : ''; ?>>الصف الخامس</option>
                                        <option value="الصف السادس" <?php echo ($_POST['grade'] ?? $currentTeacher['grade_level']) === 'الصف السادس' ? 'selected' : ''; ?>>الصف السادس</option>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار الصف</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="unit_number" class="form-label">رقم الوحدة *</label>
                                    <input type="number" class="form-control" id="unit_number" name="unit_number" 
                                           value="<?php echo htmlspecialchars($_POST['unit_number'] ?? '1'); ?>" 
                                           min="1" required>
                                    <div class="invalid-feedback">يرجى إدخال رقم الوحدة</div>
                                </div>
                            </div>

                            <!-- تفاصيل الوحدة -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-book me-2"></i>
                                        تفاصيل الوحدة
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="unit_title" class="form-label">عنوان الوحدة *</label>
                                    <input type="text" class="form-control" id="unit_title" name="unit_title" 
                                           value="<?php echo htmlspecialchars($_POST['unit_title'] ?? ''); ?>" 
                                           required placeholder="مثال: الأعداد والعمليات">
                                    <div class="invalid-feedback">يرجى إدخال عنوان الوحدة</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="total_lessons" class="form-label">إجمالي عدد الدروس *</label>
                                    <input type="number" class="form-control" id="total_lessons" name="total_lessons" 
                                           value="<?php echo htmlspecialchars($_POST['total_lessons'] ?? ''); ?>" 
                                           min="1" required>
                                    <div class="invalid-feedback">يرجى إدخال عدد الدروس</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="completed_lessons" class="form-label">الدروس المكتملة</label>
                                    <input type="number" class="form-control" id="completed_lessons" name="completed_lessons" 
                                           value="<?php echo htmlspecialchars($_POST['completed_lessons'] ?? '0'); ?>" 
                                           min="0">
                                    <div class="form-text">عدد الدروس التي تم تدريسها</div>
                                </div>
                            </div>

                            <!-- التواريخ -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-calendar me-2"></i>
                                        التواريخ
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">تاريخ البداية *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ البداية</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expected_end_date" class="form-label">التاريخ المتوقع للانتهاء</label>
                                    <input type="date" class="form-control" id="expected_end_date" name="expected_end_date" 
                                           value="<?php echo htmlspecialchars($_POST['expected_end_date'] ?? ''); ?>">
                                    <div class="form-text">التاريخ المتوقع لإنهاء الوحدة</div>
                                </div>
                            </div>

                            <!-- الملاحظات -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-sticky-note me-2"></i>
                                        الملاحظات
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label">ملاحظات</label>
                                    <textarea class="form-control" id="notes" name="notes" 
                                              rows="4" placeholder="أي ملاحظات أو تعليقات حول الوحدة..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
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
                                                حفظ الوحدة
                                            </button>
                                        </div>
                                        <div>
                                            <button type="reset" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-undo me-2"></i>
                                                إعادة تعيين
                                            </button>
                                            <a href="my.php" class="btn btn-outline-secondary btn-lg">
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
        // تحديث نسبة التقدم تلقائياً
        function updateProgress() {
            const totalLessons = parseInt(document.getElementById('total_lessons').value) || 0;
            const completedLessons = parseInt(document.getElementById('completed_lessons').value) || 0;
            
            if (totalLessons > 0) {
                const progress = Math.round((completedLessons / totalLessons) * 100);
                console.log(`التقدم: ${progress}%`);
            }
        }
        
        document.getElementById('total_lessons').addEventListener('input', updateProgress);
        document.getElementById('completed_lessons').addEventListener('input', updateProgress);
        
        // التحقق من أن الدروس المكتملة لا تتجاوز الإجمالي
        document.getElementById('completed_lessons').addEventListener('input', function() {
            const totalLessons = parseInt(document.getElementById('total_lessons').value) || 0;
            const completedLessons = parseInt(this.value) || 0;
            
            if (completedLessons > totalLessons) {
                this.setCustomValidity('عدد الدروس المكتملة لا يمكن أن يكون أكبر من إجمالي الدروس');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // ملء تلقائي للمادة والصف من بيانات المعلم
        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.getElementById('subject');
            const gradeSelect = document.getElementById('grade');
            
            // إذا لم يتم اختيار مادة، استخدم مادة المعلم
            if (!subjectSelect.value) {
                subjectSelect.value = '<?php echo $currentTeacher['subject']; ?>';
            }
            
            // إذا لم يتم اختيار صف، استخدم صف المعلم
            if (!gradeSelect.value) {
                gradeSelect.value = '<?php echo $currentTeacher['grade_level']; ?>';
            }
        });
    </script>
</body>
</html>
