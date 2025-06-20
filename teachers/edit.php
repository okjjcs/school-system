<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// الحصول على معرف المعلم
$teacherId = (int)($_GET['id'] ?? 0);
if ($teacherId <= 0) {
    showMessage('معرف المعلم غير صحيح', 'error');
    redirect('list.php');
}

// جلب بيانات المعلم
try {
    $stmt = $db->query("SELECT t.*, u.username, u.is_active 
                       FROM teachers t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?", [$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        showMessage('لم يتم العثور على المعلم المطلوب', 'error');
        redirect('list.php');
    }
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات المعلم', 'error');
    redirect('list.php');
}

$errors = [];

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = sanitize($_POST['employee_id'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $gradeLevel = sanitize($_POST['grade_level'] ?? '');
    $hireDate = sanitize($_POST['hire_date'] ?? '');
    $birthDate = sanitize($_POST['birth_date'] ?? '');
    $nationalId = sanitize($_POST['national_id'] ?? '');

    
    // التحقق من صحة البيانات
    if (empty($employeeId)) {
        $errors[] = 'رقم الموظف مطلوب';
    }
    
    if (empty($firstName)) {
        $errors[] = 'الاسم الأول مطلوب';
    }
    
    if (empty($lastName)) {
        $errors[] = 'اسم العائلة مطلوب';
    }
    
    if (empty($subject)) {
        $errors[] = 'التخصص مطلوب';
    }
    
    if (empty($gradeLevel)) {
        $errors[] = 'الصف المدرس مطلوب';
    }
    
    if (empty($hireDate)) {
        $errors[] = 'تاريخ التوظيف مطلوب';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    // التحقق من عدم تكرار رقم الموظف
    if (empty($errors)) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM teachers WHERE employee_id = ? AND id != ?", [$employeeId, $teacherId]);
            $exists = $stmt->fetchColumn();
            
            if ($exists > 0) {
                $errors[] = 'رقم الموظف موجود بالفعل';
            }
        } catch (Exception $e) {
            $errors[] = 'خطأ في التحقق من رقم الموظف';
        }
    }
    
    // تحديث البيانات إذا لم توجد أخطاء
    if (empty($errors)) {
        try {
            $stmt = $db->query("UPDATE teachers SET
                               employee_id = ?, first_name = ?, last_name = ?, email = ?,
                               phone = ?, address = ?, subject = ?, grade_level = ?,
                               hire_date = ?, birth_date = ?, national_id = ?
                               WHERE id = ?",
                              [$employeeId, $firstName, $lastName, $email, $phone, $address,
                               $subject, $gradeLevel, $hireDate, $birthDate ?: null, $nationalId, $teacherId]);
            
            showMessage('تم تحديث بيانات المعلم بنجاح', 'success');
            redirect('view.php?id=' . $teacherId);
            
        } catch (Exception $e) {
            $errors[] = 'خطأ في تحديث البيانات: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - تعديل بيانات المعلم</title>
    
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
                <a class="nav-link" href="view.php?id=<?php echo $teacherId; ?>">
                    <i class="fas fa-arrow-right me-1"></i>
                    العودة للتفاصيل
                </a>
                <a class="nav-link" href="list.php">
                    <i class="fas fa-list me-1"></i>
                    قائمة المعلمين
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
                            <i class="fas fa-user-edit me-3"></i>
                            تعديل بيانات المعلم
                        </h1>
                        <p class="page-subtitle"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
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

        <!-- نموذج تعديل المعلم -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            تعديل بيانات المعلم
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- البيانات الأساسية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-user me-2"></i>
                                        البيانات الأساسية
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="employee_id" class="form-label">رقم الموظف *</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                           value="<?php echo htmlspecialchars($_POST['employee_id'] ?? $teacher['employee_id']); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال رقم الموظف</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="national_id" class="form-label">رقم الهوية</label>
                                    <input type="text" class="form-control" id="national_id" name="national_id" 
                                           value="<?php echo htmlspecialchars($_POST['national_id'] ?? $teacher['national_id']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">الاسم الأول *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? $teacher['first_name']); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال الاسم الأول</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">اسم العائلة *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? $teacher['last_name']); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال اسم العائلة</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label">تاريخ الميلاد</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                           value="<?php echo htmlspecialchars($_POST['birth_date'] ?? $teacher['birth_date']); ?>">
                                </div>
                            </div>

                            <!-- بيانات الاتصال -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-address-book me-2"></i>
                                        بيانات الاتصال
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? $teacher['email']); ?>">
                                    <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? $teacher['phone']); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">العنوان</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $teacher['address']); ?></textarea>
                                </div>
                            </div>

                            <!-- البيانات الوظيفية -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-briefcase me-2"></i>
                                        البيانات الوظيفية
                                    </h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">التخصص *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">اختر التخصص</option>
                                        <option value="الرياضيات" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'الرياضيات' ? 'selected' : ''; ?>>الرياضيات</option>
                                        <option value="اللغة العربية" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'اللغة العربية' ? 'selected' : ''; ?>>اللغة العربية</option>
                                        <option value="اللغة الإنجليزية" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'اللغة الإنجليزية' ? 'selected' : ''; ?>>اللغة الإنجليزية</option>
                                        <option value="العلوم" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'العلوم' ? 'selected' : ''; ?>>العلوم</option>
                                        <option value="الاجتماعيات" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'الاجتماعيات' ? 'selected' : ''; ?>>الاجتماعيات</option>
                                        <option value="التربية الإسلامية" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'التربية الإسلامية' ? 'selected' : ''; ?>>التربية الإسلامية</option>
                                        <option value="التربية الفنية" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'التربية الفنية' ? 'selected' : ''; ?>>التربية الفنية</option>
                                        <option value="التربية الرياضية" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'التربية الرياضية' ? 'selected' : ''; ?>>التربية الرياضية</option>
                                        <option value="الحاسوب" <?php echo ($_POST['subject'] ?? $teacher['subject']) === 'الحاسوب' ? 'selected' : ''; ?>>الحاسوب</option>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار التخصص</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="grade_level" class="form-label">الصف المدرس *</label>
                                    <select class="form-select" id="grade_level" name="grade_level" required>
                                        <option value="">اختر الصف</option>
                                        <option value="الصف الأول" <?php echo ($_POST['grade_level'] ?? $teacher['grade_level']) === 'الصف الأول' ? 'selected' : ''; ?>>الصف الأول</option>
                                        <option value="الصف الثاني" <?php echo ($_POST['grade_level'] ?? $teacher['grade_level']) === 'الصف الثاني' ? 'selected' : ''; ?>>الصف الثاني</option>
                                        <option value="الصف الثالث" <?php echo ($_POST['grade_level'] ?? $teacher['grade_level']) === 'الصف الثالث' ? 'selected' : ''; ?>>الصف الثالث</option>
                                        <option value="الصف الرابع" <?php echo ($_POST['grade_level'] ?? $teacher['grade_level']) === 'الصف الرابع' ? 'selected' : ''; ?>>الصف الرابع</option>
                                        <option value="الصف الخامس" <?php echo ($_POST['grade_level'] ?? $teacher['grade_level']) === 'الصف الخامس' ? 'selected' : ''; ?>>الصف الخامس</option>
                                        <option value="الصف السادس" <?php echo ($_POST['grade_level'] ?? $teacher['grade_level']) === 'الصف السادس' ? 'selected' : ''; ?>>الصف السادس</option>
                                    </select>
                                    <div class="invalid-feedback">يرجى اختيار الصف المدرس</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label">تاريخ التوظيف *</label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date" 
                                           value="<?php echo htmlspecialchars($_POST['hire_date'] ?? $teacher['hire_date']); ?>" 
                                           required>
                                    <div class="invalid-feedback">يرجى إدخال تاريخ التوظيف</div>
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
                                            <a href="view.php?id=<?php echo $teacherId; ?>" class="btn btn-secondary btn-lg">
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
</body>
</html>
