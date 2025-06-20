<?php
require_once 'config/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    redirect('login.php');
}

// التحقق من أن المستخدم معلم
if (!isTeacher()) {
    redirect('index.php');
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
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileCategory = sanitize($_POST['file_category'] ?? '');
    $fileDescription = sanitize($_POST['file_description'] ?? '');
    
    if (empty($fileCategory)) {
        showMessage('يرجى اختيار نوع الملف', 'error');
        redirect('index.php');
    }
    
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        showMessage('يرجى اختيار ملف واحد على الأقل', 'error');
        redirect('index.php');
    }
    
    try {
        // إنشاء كائن رفع الملفات
        $fileUpload = new FileUpload($db);
        
        $uploadedFiles = [];
        $errors = [];
        
        // معالجة كل ملف
        $fileCount = count($_FILES['files']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];
                
                try {
                    $result = $fileUpload->uploadFile($file, $fileCategory, $teacherId);
                    $uploadedFiles[] = $result;
                    
                    // حفظ وصف الملف إذا تم توفيره
                    if (!empty($fileDescription)) {
                        $db->query("UPDATE files SET description = ? WHERE id = ?", 
                                  [$fileDescription, $result['id']]);
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "خطأ في رفع الملف {$file['name']}: " . $e->getMessage();
                }
            }
        }
        
        // عرض النتائج
        if (!empty($uploadedFiles)) {
            $successCount = count($uploadedFiles);
            $message = "تم رفع $successCount ملف بنجاح";
            if (!empty($errors)) {
                $message .= " مع وجود بعض الأخطاء";
            }
            showMessage($message, 'success');
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                showMessage($error, 'error');
            }
        }
        
    } catch (Exception $e) {
        showMessage('خطأ في رفع الملفات: ' . $e->getMessage(), 'error');
    }
}

redirect('index.php');
?>
