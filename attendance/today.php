<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('../login.php');
}

// معالجة تحديث الحضور (للمديرين فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    if (isset($_POST['update_attendance'])) {
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        
        try {
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // التحقق من وجود سجل حضور لليوم
            $stmt = $db->query("SELECT id FROM attendance WHERE teacher_id = ? AND attendance_date = ?", 
                              [$teacherId, $today]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attendance) {
                // تحديث السجل الموجود
                $updateFields = ['status = ?', 'notes = ?'];
                $updateParams = [$status, $notes];
                
                if ($status === 'present' && empty($attendance['check_in_time'])) {
                    $updateFields[] = 'check_in_time = ?';
                    $updateParams[] = $currentTime;
                } elseif ($status === 'absent') {
                    $updateFields[] = 'check_out_time = ?';
                    $updateParams[] = $currentTime;
                }
                
                $updateParams[] = $attendance['id'];
                $sql = "UPDATE attendance SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $db->query($sql, $updateParams);
            } else {
                // إنشاء سجل جديد
                $checkInTime = ($status === 'present') ? $currentTime : null;
                $checkOutTime = ($status === 'absent') ? $currentTime : null;
                
                $db->query("INSERT INTO attendance (teacher_id, attendance_date, check_in_time, check_out_time, status, notes) VALUES (?, ?, ?, ?, ?, ?)", 
                          [$teacherId, $today, $checkInTime, $checkOutTime, $status, $notes]);
            }
            
            // تحديث حالة الحضور في جدول المعلمين
            $isPresent = ($status === 'present') ? 1 : 0;
            $db->query("UPDATE teachers SET is_present = ? WHERE id = ?", [$isPresent, $teacherId]);
            
            showMessage('تم تحديث حالة الحضور بنجاح', 'success');
            
        } catch (Exception $e) {
            showMessage('خطأ في تحديث الحضور: ' . $e->getMessage(), 'error');
        }
    }
}

// جلب بيانات الحضور لليوم الحالي
$today = date('Y-m-d');
$attendanceData = [];

try {
    $sql = "SELECT t.*, u.username, 
                   a.check_in_time, a.check_out_time, a.status as attendance_status, a.notes as attendance_notes
            FROM teachers t 
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN attendance a ON t.id = a.teacher_id AND a.attendance_date = ?
            WHERE u.is_active = 1
            ORDER BY t.first_name, t.last_name";
    
    $stmt = $db->query($sql, [$today]);
    $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    showMessage('خطأ في جلب بيانات الحضور', 'error');
    $attendanceData = [];
}

// حساب الإحصائيات
$totalTeachers = count($attendanceData);
$presentTeachers = array_filter($attendanceData, function($t) { 
    return $t['attendance_status'] === 'present' || $t['is_present'] == 1; 
});
$absentTeachers = array_filter($attendanceData, function($t) { 
    return $t['attendance_status'] === 'absent' || ($t['attendance_status'] === null && $t['is_present'] == 0); 
});
$lateTeachers = array_filter($attendanceData, function($t) { 
    return $t['attendance_status'] === 'late'; 
});
$earlyLeaveTeachers = array_filter($attendanceData, function($t) { 
    return $t['attendance_status'] === 'early_leave'; 
});

$attendanceRate = $totalTeachers > 0 ? (count($presentTeachers) / $totalTeachers) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - حضور اليوم</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .attendance-card {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        
        .attendance-card.present {
            border-left-color: #28a745;
            background-color: #f8fff9;
        }
        
        .attendance-card.absent {
            border-left-color: #dc3545;
            background-color: #fff8f8;
        }
        
        .attendance-card.late {
            border-left-color: #ffc107;
            background-color: #fffdf8;
        }
        
        .attendance-card.early_leave {
            border-left-color: #fd7e14;
            background-color: #fff9f5;
        }
        
        .time-badge {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .live-time {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title">
                                    <i class="fas fa-calendar-check me-3"></i>
                                    حضور اليوم
                                </h1>
                                <p class="page-subtitle">متابعة حضور وغياب المعلمين لليوم الحالي</p>
                            </div>
                            <div class="text-end">
                                <div class="live-time text-primary" id="currentTime"></div>
                                <small class="text-muted"><?php echo formatDateArabic($today); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h4 class="text-primary"><?php echo $totalTeachers; ?></h4>
                        <p class="text-muted mb-0">إجمالي المعلمين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                        <h4 class="text-success"><?php echo count($presentTeachers); ?></h4>
                        <p class="text-muted mb-0">حاضرون</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                        <h4 class="text-danger"><?php echo count($absentTeachers); ?></h4>
                        <p class="text-muted mb-0">غائبون</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-percentage fa-3x text-info mb-3"></i>
                        <h4 class="text-info"><?php echo number_format($attendanceRate, 1); ?>%</h4>
                        <p class="text-muted mb-0">نسبة الحضور</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- أدوات سريعة -->
        <?php if (isAdmin()): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tools me-2"></i>
                    أدوات سريعة
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="markAllPresent()">
                            <i class="fas fa-check-double me-2"></i>
                            تحديد الكل حاضر
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="exportAttendance()">
                            <i class="fas fa-download me-2"></i>
                            تصدير الحضور
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="reports.php" class="btn btn-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            تقارير الحضور
                        </a>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-secondary w-100" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>
                            طباعة
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- قائمة الحضور -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة حضور المعلمين
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($attendanceData)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد بيانات</h5>
                    <p class="text-muted">لم يتم العثور على بيانات المعلمين</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($attendanceData as $teacher): ?>
                    <?php
                    $status = $teacher['attendance_status'] ?? ($teacher['is_present'] ? 'present' : 'absent');
                    $statusClass = $status;
                    $statusIcon = '';
                    $statusText = '';
                    $statusColor = '';
                    
                    switch ($status) {
                        case 'present':
                            $statusIcon = 'fas fa-check-circle';
                            $statusText = 'حاضر';
                            $statusColor = 'success';
                            break;
                        case 'absent':
                            $statusIcon = 'fas fa-times-circle';
                            $statusText = 'غائب';
                            $statusColor = 'danger';
                            break;
                        case 'late':
                            $statusIcon = 'fas fa-clock';
                            $statusText = 'متأخر';
                            $statusColor = 'warning';
                            break;
                        case 'early_leave':
                            $statusIcon = 'fas fa-sign-out-alt';
                            $statusText = 'انصراف مبكر';
                            $statusColor = 'orange';
                            break;
                        default:
                            $statusIcon = 'fas fa-question-circle';
                            $statusText = 'غير محدد';
                            $statusColor = 'secondary';
                    }
                    ?>
                    <div class="col-lg-6 mb-3">
                        <div class="card attendance-card <?php echo $statusClass; ?> h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($teacher['employee_id']); ?> | 
                                                <?php echo htmlspecialchars($teacher['subject']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $statusColor; ?>">
                                            <i class="<?php echo $statusIcon; ?> me-1"></i>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted d-block">وقت الدخول</small>
                                        <span class="time-badge badge bg-light text-dark">
                                            <?php echo $teacher['check_in_time'] ? date('H:i', strtotime($teacher['check_in_time'])) : '--:--'; ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">وقت الخروج</small>
                                        <span class="time-badge badge bg-light text-dark">
                                            <?php echo $teacher['check_out_time'] ? date('H:i', strtotime($teacher['check_out_time'])) : '--:--'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($teacher['attendance_notes'])): ?>
                                <div class="mt-3">
                                    <small class="text-muted">ملاحظات:</small>
                                    <p class="small mb-0"><?php echo htmlspecialchars($teacher['attendance_notes']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isAdmin()): ?>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="updateAttendance(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>')">
                                        <i class="fas fa-edit me-1"></i>
                                        تحديث الحضور
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal تحديث الحضور -->
    <?php if (isAdmin()): ?>
    <div class="modal fade" id="attendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تحديث حضور المعلم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="modal_teacher_id">
                        
                        <div class="mb-3">
                            <label class="form-label">اسم المعلم</label>
                            <input type="text" class="form-control" id="modal_teacher_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_status" class="form-label">حالة الحضور</label>
                            <select class="form-select" name="status" id="modal_status" required>
                                <option value="present">حاضر</option>
                                <option value="absent">غائب</option>
                                <option value="late">متأخر</option>
                                <option value="early_leave">انصراف مبكر</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control" name="notes" id="modal_notes" rows="3" 
                                      placeholder="أي ملاحظات إضافية..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_attendance" class="btn btn-primary">حفظ التحديث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // تحديث الوقت المباشر
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ar-SA', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        // تشغيل تحديث الوقت كل ثانية
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();
        
        <?php if (isAdmin()): ?>
        // فتح نافذة تحديث الحضور
        function updateAttendance(teacherId, teacherName) {
            document.getElementById('modal_teacher_id').value = teacherId;
            document.getElementById('modal_teacher_name').value = teacherName;
            
            const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
            modal.show();
        }
        
        // تحديد جميع المعلمين كحاضرين
        function markAllPresent() {
            Swal.fire({
                title: 'تأكيد العملية',
                text: 'هل تريد تحديد جميع المعلمين كحاضرين؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، حدد الكل',
                cancelButtonText: 'إلغاء',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('mark_all_present.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('تم تحديد جميع المعلمين كحاضرين', 'success');
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
        
        // تصدير بيانات الحضور
        function exportAttendance() {
            window.open('export_attendance.php?date=<?php echo $today; ?>', '_blank');
        }
        <?php endif; ?>
        
        // تحديث تلقائي للصفحة كل 5 دقائق
        setInterval(function() {
            location.reload();
        }, 300000); // 5 دقائق
    </script>
</body>
</html>
