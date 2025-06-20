<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

// قراءة البيانات المرسلة
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['teacher_id']) || !isset($input['is_present'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة']);
    exit;
}

$teacherId = (int)$input['teacher_id'];
$isPresent = (bool)$input['is_present'];

try {
    // التحقق من وجود المعلم
    $stmt = $db->query("SELECT id FROM teachers WHERE id = ?", [$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'المعلم غير موجود']);
        exit;
    }
    
    // تحديث حالة الحضور في جدول المعلمين
    $db->query("UPDATE teachers SET is_present = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
              [$isPresent ? 1 : 0, $teacherId]);
    
    // تحديث أو إدراج سجل الحضور لليوم الحالي
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    $stmt = $db->query("SELECT id FROM attendance WHERE teacher_id = ? AND attendance_date = ?", 
                      [$teacherId, $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        // تحديث السجل الموجود
        if ($isPresent) {
            $db->query("UPDATE attendance SET status = 'present', check_in_time = COALESCE(check_in_time, ?), check_out_time = NULL WHERE id = ?", 
                      [$currentTime, $attendance['id']]);
        } else {
            $db->query("UPDATE attendance SET status = 'absent', check_out_time = ? WHERE id = ?", 
                      [$currentTime, $attendance['id']]);
        }
    } else {
        // إنشاء سجل جديد
        $status = $isPresent ? 'present' : 'absent';
        $checkInTime = $isPresent ? $currentTime : null;
        $checkOutTime = !$isPresent ? $currentTime : null;
        
        $db->query("INSERT INTO attendance (teacher_id, attendance_date, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, ?)", 
                  [$teacherId, $today, $checkInTime, $checkOutTime, $status]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'تم تحديث حالة الحضور بنجاح',
        'is_present' => $isPresent,
        'time' => $currentTime
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في النظام']);
    error_log('Attendance update error: ' . $e->getMessage());
}
?>
