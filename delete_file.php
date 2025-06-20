<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مسموح']);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

// قراءة البيانات
$input = json_decode(file_get_contents('php://input'), true);
$fileId = $input['file_id'] ?? null;

if (!$fileId) {
    echo json_encode(['success' => false, 'message' => 'معرف الملف مطلوب']);
    exit;
}

try {
    // إنشاء كائن رفع الملفات
    $fileUpload = new FileUpload($db);
    
    // الحصول على معرف المعلم إذا كان المستخدم معلماً
    $teacherId = null;
    if (isTeacher()) {
        $stmt = $db->query("SELECT id FROM teachers WHERE user_id = ?", [$_SESSION['user_id']]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($teacher) {
            $teacherId = $teacher['id'];
        }
    }
    
    // حذف الملف
    $result = $fileUpload->deleteFile($fileId, $teacherId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'تم حذف الملف بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في حذف الملف']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
