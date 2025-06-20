<?php
// مكون رفع الملفات
// يمكن استخدامه في أي صفحة تحتاج لرفع ملفات

function renderFileUploadSection($existingFiles = [], $inputName = 'files', $allowMultiple = true) {
    $multipleAttr = $allowMultiple ? 'multiple' : '';
    $inputNameAttr = $allowMultiple ? $inputName . '[]' : $inputName;
    ?>
    
    <!-- قسم رفع الملفات -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-paperclip me-2"></i>
                الملفات والمرفقات
            </h6>
        </div>
        <div class="card-body">
            <!-- منطقة رفع الملفات -->
            <div class="upload-area border-2 border-dashed border-primary rounded p-4 text-center mb-3" 
                 style="background-color: #f8f9ff;">
                <div class="upload-icon mb-3">
                    <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                </div>
                <h5 class="text-primary">اسحب الملفات هنا أو اضغط للاختيار</h5>
                <p class="text-muted mb-3">
                    يمكنك رفع الصور والمستندات (PDF, Word, Excel, PowerPoint)
                </p>
                <input type="file" 
                       name="<?php echo $inputNameAttr; ?>" 
                       id="fileInput" 
                       class="form-control d-none" 
                       <?php echo $multipleAttr; ?>
                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-plus me-2"></i>
                    اختيار الملفات
                </button>
                <div class="mt-2">
                    <small class="text-muted">
                        الحد الأقصى: 10 ميجابايت لكل ملف | 
                        الأنواع المسموحة: JPG, PNG, PDF, Word, Excel, PowerPoint
                    </small>
                </div>
            </div>
            
            <!-- معاينة الملفات المختارة -->
            <div id="selectedFiles" class="row g-3 mb-3" style="display: none;">
                <div class="col-12">
                    <h6 class="text-success">
                        <i class="fas fa-check-circle me-2"></i>
                        الملفات المختارة:
                    </h6>
                </div>
            </div>
            
            <!-- الملفات الموجودة -->
            <?php if (!empty($existingFiles)): ?>
            <div class="existing-files">
                <h6 class="text-info mb-3">
                    <i class="fas fa-folder me-2"></i>
                    الملفات الموجودة:
                </h6>
                <div class="row g-3">
                    <?php foreach ($existingFiles as $file): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card file-card h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="file-icon me-3">
                                        <i class="<?php echo getFileIcon($file['file_type']); ?> fa-2x"></i>
                                    </div>
                                    <div class="file-info flex-grow-1">
                                        <h6 class="mb-1 text-truncate" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                            <?php echo htmlspecialchars($file['original_name']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo formatFileSize($file['file_size']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="file-actions mt-2">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="<?php echo getFileUrl($file['file_path']); ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo getFileUrl($file['file_path']); ?>" 
                                           download="<?php echo $file['original_name']; ?>"
                                           class="btn btn-outline-success">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                onclick="deleteFile(<?php echo $file['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .upload-area {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .upload-area:hover {
        background-color: #e3f2fd !important;
        border-color: #1976d2 !important;
    }
    
    .file-card {
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }
    
    .file-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .file-preview {
        max-width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .progress {
        height: 4px;
    }
    </style>

    <script>
    // معالجة اختيار الملفات
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const files = e.target.files;
        const selectedFilesDiv = document.getElementById('selectedFiles');
        
        if (files.length > 0) {
            selectedFilesDiv.style.display = 'block';
            let html = '<div class="col-12"><h6 class="text-success"><i class="fas fa-check-circle me-2"></i>الملفات المختارة:</h6></div>';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = formatFileSize(file.size);
                const fileIcon = getFileIconByName(file.name);
                
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card file-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="file-icon me-3">
                                        <i class="${fileIcon} fa-2x"></i>
                                    </div>
                                    <div class="file-info flex-grow-1">
                                        <h6 class="mb-1 text-truncate" title="${file.name}">${file.name}</h6>
                                        <small class="text-muted">${fileSize}</small>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="display: none;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            selectedFilesDiv.innerHTML = html;
        } else {
            selectedFilesDiv.style.display = 'none';
        }
    });
    
    // السحب والإفلات
    const uploadArea = document.querySelector('.upload-area');
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#e3f2fd';
        this.style.borderColor = '#1976d2';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#f8f9ff';
        this.style.borderColor = '#007bff';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#f8f9ff';
        this.style.borderColor = '#007bff';
        
        const files = e.dataTransfer.files;
        document.getElementById('fileInput').files = files;
        
        // تشغيل حدث التغيير
        const event = new Event('change', { bubbles: true });
        document.getElementById('fileInput').dispatchEvent(event);
    });
    
    uploadArea.addEventListener('click', function() {
        document.getElementById('fileInput').click();
    });
    
    // دوال مساعدة
    function formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }
    
    function getFileIconByName(fileName) {
        const extension = fileName.split('.').pop().toLowerCase();
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            return 'fas fa-image text-success';
        } else if (extension === 'pdf') {
            return 'fas fa-file-pdf text-danger';
        } else if (['doc', 'docx'].includes(extension)) {
            return 'fas fa-file-word text-primary';
        } else if (['xls', 'xlsx'].includes(extension)) {
            return 'fas fa-file-excel text-success';
        } else if (['ppt', 'pptx'].includes(extension)) {
            return 'fas fa-file-powerpoint text-warning';
        } else if (['zip', 'rar'].includes(extension)) {
            return 'fas fa-file-archive text-secondary';
        } else {
            return 'fas fa-file text-muted';
        }
    }
    
    function deleteFile(fileId) {
        if (confirm('هل أنت متأكد من حذف هذا الملف؟')) {
            // إرسال طلب حذف عبر AJAX
            fetch('delete_file.php', {
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
    
    <?php
}

// دوال مساعدة للملفات
function getFileIcon($mimeType) {
    if (strpos($mimeType, 'image/') === 0) {
        return 'fas fa-image text-success';
    } elseif (strpos($mimeType, 'pdf') !== false) {
        return 'fas fa-file-pdf text-danger';
    } elseif (strpos($mimeType, 'word') !== false || strpos($mimeType, 'document') !== false) {
        return 'fas fa-file-word text-primary';
    } elseif (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'sheet') !== false) {
        return 'fas fa-file-excel text-success';
    } elseif (strpos($mimeType, 'powerpoint') !== false || strpos($mimeType, 'presentation') !== false) {
        return 'fas fa-file-powerpoint text-warning';
    } elseif (strpos($mimeType, 'zip') !== false || strpos($mimeType, 'rar') !== false) {
        return 'fas fa-file-archive text-secondary';
    } else {
        return 'fas fa-file text-muted';
    }
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getFileUrl($filePath) {
    return str_replace('\\', '/', $filePath);
}
?>
