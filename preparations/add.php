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

// معالجة إضافة التحضير
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject'] ?? '');
    $grade = sanitize($_POST['grade'] ?? '');
    $lessonTitle = sanitize($_POST['lesson_title'] ?? '');
    $lessonObjectives = sanitize($_POST['lesson_objectives'] ?? '');
    $lessonContent = sanitize($_POST['lesson_content'] ?? '');
    $teachingMethods = sanitize($_POST['teaching_methods'] ?? '');
    $resources = sanitize($_POST['resources'] ?? '');
    $evaluationMethods = sanitize($_POST['evaluation_methods'] ?? '');
    $homework = sanitize($_POST['homework'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $preparationDate = sanitize($_POST['preparation_date'] ?? '');
    
    // التحقق من صحة البيانات
    if (empty($subject)) {
        $errors[] = 'المادة مطلوبة';
    }
    
    if (empty($grade)) {
        $errors[] = 'الصف مطلوب';
    }
    
    if (empty($lessonTitle)) {
        $errors[] = 'عنوان الدرس مطلوب';
    }
    
    if (empty($lessonObjectives)) {
        $errors[] = 'أهداف الدرس مطلوبة';
    }
    
    if (empty($lessonContent)) {
        $errors[] = 'محتوى الدرس مطلوب';
    }
    
    if (empty($preparationDate)) {
        $errors[] = 'تاريخ التحضير مطلوب';
    }
    
    // إضافة التحضير إذا لم توجد أخطاء
    if (empty($errors)) {
        try {
            $stmt = $db->query("INSERT INTO daily_preparations (teacher_id, subject, grade, lesson_title, lesson_objectives, lesson_content, teaching_methods, resources, evaluation_methods, homework, notes, preparation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                              [$currentTeacher['id'], $subject, $grade, $lessonTitle, $lessonObjectives, $lessonContent, $teachingMethods, $resources, $evaluationMethods, $homework, $notes, $preparationDate]);
            
            showMessage('تم إضافة التحضير بنجاح', 'success');
            redirect('my.php');
            
        } catch (Exception $e) {
            $errors[] = 'خطأ في إضافة التحضير: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - إضافة تحضير جديد</title>
    
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
                            إضافة تحضير جديد
                        </h1>
                        <p class="page-subtitle">إضافة تحضير يومي جديد للدرس</p>
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

        <!-- نموذج إضافة التحضير -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            بيانات التحضير
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
                                        <option value="التربية الفنية" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'التربية الفنية' ? 'selected' : ''; ?>>التربية الفنية</option>
                                        <option value="التربية الرياضية" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'التربية الرياضية' ? 'selected' : ''; ?>>التربية الرياضية</option>
                                        <option value="الحاسوب" <?php echo ($_POST['subject'] ?? $currentTeacher['subject']) === 'الحاسوب' ? 'selected' : ''; ?>>الحاسوب</option>
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
                                    <label for="preparation_date" class="form-label">تاريخ التحضير *</label>
                                    <input type="date" class="form-control" id="preparation_date" name="preparation_date" 
                                           value="<?php echo htmlspecialchars($_POST['preparation_date'] ?? date('Y-m-d')); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ التحضير</div>
                                </div>
                            </div>

                            <!-- محتوى الدرس -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-book me-2"></i>
                                        محتوى الدرس
                                    </h6>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="lesson_title" class="form-label">عنوان الدرس *</label>
                                    <input type="text" class="form-control" id="lesson_title" name="lesson_title" 
                                           value="<?php echo htmlspecialchars($_POST['lesson_title'] ?? ''); ?>" 
                                           required placeholder="مثال: الكسور العادية">
                                    <div class="invalid-feedback">يرجى إدخال عنوان الدرس</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="lesson_objectives" class="form-label">أهداف الدرس *</label>
                                    <textarea class="form-control" id="lesson_objectives" name="lesson_objectives" 
                                              rows="4" required placeholder="اكتب أهداف الدرس التي تريد تحقيقها..."><?php echo htmlspecialchars($_POST['lesson_objectives'] ?? ''); ?></textarea>
                                    <div class="form-text">اكتب الأهداف التعليمية المحددة للدرس</div>
                                    <div class="invalid-feedback">يرجى إدخال أهداف الدرس</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="lesson_content" class="form-label">محتوى الدرس *</label>
                                    <textarea class="form-control" id="lesson_content" name="lesson_content" 
                                              rows="6" required placeholder="اكتب محتوى الدرس بالتفصيل..."><?php echo htmlspecialchars($_POST['lesson_content'] ?? ''); ?></textarea>
                                    <div class="form-text">اشرح محتوى الدرس والمفاهيم التي ستدرسها</div>
                                    <div class="invalid-feedback">يرجى إدخال محتوى الدرس</div>
                                </div>
                            </div>

                            <!-- طرق التدريس والتقييم -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>
                                        طرق التدريس والتقييم
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="teaching_methods" class="form-label">طرق التدريس</label>
                                    <textarea class="form-control" id="teaching_methods" name="teaching_methods" 
                                              rows="4" placeholder="مثال: الشرح، المناقشة، العصف الذهني..."><?php echo htmlspecialchars($_POST['teaching_methods'] ?? ''); ?></textarea>
                                    <div class="form-text">اذكر الطرق والاستراتيجيات التي ستستخدمها</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="evaluation_methods" class="form-label">طرق التقييم</label>
                                    <textarea class="form-control" id="evaluation_methods" name="evaluation_methods" 
                                              rows="4" placeholder="مثال: أسئلة شفهية، تمارين، اختبار قصير..."><?php echo htmlspecialchars($_POST['evaluation_methods'] ?? ''); ?></textarea>
                                    <div class="form-text">كيف ستقيم فهم الطلاب للدرس</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="resources" class="form-label">الوسائل والمصادر</label>
                                    <textarea class="form-control" id="resources" name="resources" 
                                              rows="3" placeholder="مثال: السبورة، الكتاب المدرسي، أوراق عمل، حاسوب..."><?php echo htmlspecialchars($_POST['resources'] ?? ''); ?></textarea>
                                    <div class="form-text">اذكر الوسائل التعليمية والمصادر المطلوبة</div>
                                </div>
                            </div>

                            <!-- الواجبات والملاحظات -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-clipboard-list me-2"></i>
                                        الواجبات والملاحظات
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="homework" class="form-label">الواجب المنزلي</label>
                                    <textarea class="form-control" id="homework" name="homework" 
                                              rows="4" placeholder="اكتب الواجب المنزلي المطلوب من الطلاب..."><?php echo htmlspecialchars($_POST['homework'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="notes" class="form-label">ملاحظات إضافية</label>
                                    <textarea class="form-control" id="notes" name="notes" 
                                              rows="4" placeholder="أي ملاحظات أو تعليقات إضافية..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
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
                                                حفظ التحضير
                                            </button>
                                            <button type="button" class="btn btn-success btn-lg" onclick="saveAndAddNew()">
                                                <i class="fas fa-plus me-2"></i>
                                                حفظ وإضافة آخر
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
        // حفظ وإضافة جديد
        function saveAndAddNew() {
            const form = document.querySelector('form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'save_and_new';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
        
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
        
        // حفظ تلقائي كل 5 دقائق
        setInterval(function() {
            const form = document.querySelector('form');
            const formData = new FormData(form);
            formData.append('auto_save', '1');
            
            fetch('auto_save.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('تم الحفظ التلقائي');
                }
            })
            .catch(error => {
                console.error('خطأ في الحفظ التلقائي:', error);
            });
        }, 300000); // 5 دقائق
    </script>
</body>
</html>
