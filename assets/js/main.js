// نظام أرشفة الأساتذة - ملف JavaScript الرئيسي

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة التطبيق
    initializeApp();
    
    // تهيئة الأحداث
    initializeEvents();
    
    // تهيئة التحقق من النماذج
    initializeFormValidation();
    
    // تهيئة رفع الملفات
    initializeFileUpload();
    
    // تهيئة الجداول
    initializeTables();
});

// تهيئة التطبيق
function initializeApp() {
    // إخفاء شاشة التحميل إذا كانت موجودة
    const loader = document.querySelector('.loader');
    if (loader) {
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    }
    
    // تهيئة التلميحات
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // تهيئة النوافذ المنبثقة
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// تهيئة الأحداث
function initializeEvents() {
    // حدث النقر على أزرار الحذف
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
            e.preventDefault();
            const button = e.target.classList.contains('btn-delete') ? e.target : e.target.closest('.btn-delete');
            showDeleteConfirmation(button);
        }
    });
    
    // حدث تغيير حالة الحضور
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('attendance-toggle')) {
            updateAttendanceStatus(e.target);
        }
    });
    
    // حدث البحث المباشر
    const searchInputs = document.querySelectorAll('.live-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            performLiveSearch(this);
        });
    });
    
    // حدث تصفية الجداول
    const filterSelects = document.querySelectorAll('.table-filter');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterTable(this);
        });
    });
}

// تهيئة التحقق من النماذج
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // التحقق من كلمات المرور المطابقة
    const passwordConfirm = document.querySelector('#password_confirm');
    const password = document.querySelector('#password');
    
    if (passwordConfirm && password) {
        passwordConfirm.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}

// تهيئة رفع الملفات
function initializeFileUpload() {
    const fileUploadAreas = document.querySelectorAll('.file-upload-area');
    
    fileUploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        
        if (input) {
            // حدث السحب والإفلات
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });
            
            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    input.files = files;
                    handleFileSelect(input);
                }
            });
            
            // حدث اختيار الملف
            input.addEventListener('change', function() {
                handleFileSelect(this);
            });
            
            // حدث النقر على المنطقة
            area.addEventListener('click', function() {
                input.click();
            });
        }
    });
}

// تهيئة الجداول
function initializeTables() {
    // إضافة أرقام الصفوف
    const tables = document.querySelectorAll('.table-numbered');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            const numberCell = row.querySelector('.row-number');
            if (numberCell) {
                numberCell.textContent = index + 1;
            }
        });
    });
    
    // تهيئة الترتيب
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            sortTable(this);
        });
    });
}

// عرض تأكيد الحذف
function showDeleteConfirmation(button) {
    const itemName = button.dataset.itemName || 'هذا العنصر';
    const deleteUrl = button.dataset.deleteUrl || button.href;
    
    Swal.fire({
        title: 'تأكيد الحذف',
        text: `هل أنت متأكد من حذف ${itemName}؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            if (deleteUrl) {
                window.location.href = deleteUrl;
            } else {
                // إرسال نموذج الحذف
                const form = button.closest('form');
                if (form) {
                    form.submit();
                }
            }
        }
    });
}

// تحديث حالة الحضور
function updateAttendanceStatus(toggle) {
    const teacherId = toggle.dataset.teacherId;
    const isPresent = toggle.checked;
    
    fetch('ajax/update_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            teacher_id: teacherId,
            is_present: isPresent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('تم تحديث حالة الحضور بنجاح', 'success');
        } else {
            showToast('خطأ في تحديث حالة الحضور', 'error');
            toggle.checked = !isPresent; // إعادة الحالة السابقة
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('خطأ في الاتصال', 'error');
        toggle.checked = !isPresent;
    });
}

// البحث المباشر
function performLiveSearch(input) {
    const searchTerm = input.value.toLowerCase();
    const targetTable = document.querySelector(input.dataset.target);
    
    if (targetTable) {
        const rows = targetTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // تحديث أرقام الصفوف
        updateRowNumbers(targetTable);
    }
}

// تصفية الجدول
function filterTable(select) {
    const filterValue = select.value;
    const targetTable = document.querySelector(select.dataset.target);
    const filterColumn = parseInt(select.dataset.column);
    
    if (targetTable) {
        const rows = targetTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells[filterColumn]) {
                const cellText = cells[filterColumn].textContent.trim();
                
                if (filterValue === '' || cellText === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
        
        updateRowNumbers(targetTable);
    }
}

// ترتيب الجدول
function sortTable(header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const isAscending = !header.classList.contains('sort-asc');
    
    // إزالة فئات الترتيب من جميع الرؤوس
    table.querySelectorAll('.sortable').forEach(h => {
        h.classList.remove('sort-asc', 'sort-desc');
    });
    
    // إضافة فئة الترتيب للرأس الحالي
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    // ترتيب الصفوف
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        // التحقق من الأرقام
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // ترتيب النصوص
        return isAscending ? 
            aText.localeCompare(bText, 'ar') : 
            bText.localeCompare(aText, 'ar');
    });
    
    // إعادة ترتيب الصفوف في الجدول
    rows.forEach(row => tbody.appendChild(row));
    
    // تحديث أرقام الصفوف
    updateRowNumbers(table);
}

// تحديث أرقام الصفوف
function updateRowNumbers(table) {
    const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
    visibleRows.forEach((row, index) => {
        const numberCell = row.querySelector('.row-number');
        if (numberCell) {
            numberCell.textContent = index + 1;
        }
    });
}

// معالجة اختيار الملف
function handleFileSelect(input) {
    const files = input.files;
    const preview = input.parentNode.querySelector('.file-preview');
    
    if (preview) {
        preview.innerHTML = '';
        
        Array.from(files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex align-items-center mb-2';
            
            const icon = getFileIcon(file.type);
            const size = formatFileSize(file.size);
            
            fileItem.innerHTML = `
                <i class="${icon} me-2"></i>
                <span class="file-name me-auto">${file.name}</span>
                <span class="file-size text-muted">${size}</span>
            `;
            
            preview.appendChild(fileItem);
        });
    }
}

// الحصول على أيقونة الملف
function getFileIcon(fileType) {
    if (fileType.startsWith('image/')) {
        return 'fas fa-image text-success';
    } else if (fileType === 'application/pdf') {
        return 'fas fa-file-pdf text-danger';
    } else if (fileType.includes('word')) {
        return 'fas fa-file-word text-primary';
    } else if (fileType.includes('excel') || fileType.includes('spreadsheet')) {
        return 'fas fa-file-excel text-success';
    } else {
        return 'fas fa-file text-secondary';
    }
}

// تنسيق حجم الملف
function formatFileSize(bytes) {
    if (bytes === 0) return '0 بايت';
    
    const k = 1024;
    const sizes = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// عرض رسالة منبثقة
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // إزالة التوست بعد إخفائه
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// إنشاء حاوية التوست
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// تحديث الوقت المباشر
function updateLiveTime() {
    const timeElements = document.querySelectorAll('.live-time');
    
    timeElements.forEach(element => {
        const now = new Date();
        const timeString = now.toLocaleTimeString('ar-SA', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        element.textContent = timeString;
    });
}

// تشغيل تحديث الوقت كل ثانية
setInterval(updateLiveTime, 1000);

// تهيئة الرسوم البيانية (إذا كانت مطلوبة)
function initializeCharts() {
    // يمكن إضافة مكتبة رسوم بيانية مثل Chart.js هنا
}

// حفظ البيانات تلقائياً
function autoSave(formId) {
    const form = document.getElementById(formId);
    if (form) {
        const formData = new FormData(form);
        
        fetch('ajax/auto_save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('تم حفظ البيانات تلقائياً', 'success');
            }
        })
        .catch(error => {
            console.error('Auto-save error:', error);
        });
    }
}

// تصدير البيانات
function exportData(format, url) {
    const link = document.createElement('a');
    link.href = url + '?format=' + format;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// طباعة الصفحة
function printPage() {
    window.print();
}

// مشاركة البيانات
function shareData(data) {
    if (navigator.share) {
        navigator.share(data)
        .then(() => console.log('تم المشاركة بنجاح'))
        .catch((error) => console.log('خطأ في المشاركة:', error));
    } else {
        // نسخ الرابط إلى الحافظة
        navigator.clipboard.writeText(data.url)
        .then(() => showToast('تم نسخ الرابط إلى الحافظة', 'success'))
        .catch(() => showToast('فشل في نسخ الرابط', 'error'));
    }
}
