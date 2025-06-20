<?php
class Database {
    private $db;
    private $dbPath;
    
    public function __construct($dbPath = null) {
        // استخدام المسار المحدد في config.php أو المسار الافتراضي
        $this->dbPath = $dbPath ?? (defined('DB_PATH') ? DB_PATH : 'C:\xampp\htdocs\school-system\database\school_archive.db');
        $this->connect();
        $this->createTables();
    }
    
    private function connect() {
        try {
            // إنشاء مجلد قاعدة البيانات إذا لم يكن موجوداً
            $dir = dirname($this->dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
        }
    }
    
    private function createTables() {
        $sql = "
        -- جدول المستخدمين
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'teacher' CHECK (role IN ('admin', 'teacher')),
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- جدول المعلمين
        CREATE TABLE IF NOT EXISTS teachers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE,
            employee_id VARCHAR(20) UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            hire_date DATE,
            birth_date DATE,
            national_id VARCHAR(20),
            subject VARCHAR(100),
            grade_level VARCHAR(50),
            photo VARCHAR(255),
            is_present INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        
        -- جدول المؤهلات والشهادات
        CREATE TABLE IF NOT EXISTS qualifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            degree_type VARCHAR(100),
            institution VARCHAR(200),
            major VARCHAR(100),
            graduation_year INTEGER,
            grade VARCHAR(20),
            certificate_file VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        );
        
        -- جدول الخبرات
        CREATE TABLE IF NOT EXISTS experiences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            position VARCHAR(100),
            institution VARCHAR(200),
            start_date DATE,
            end_date DATE,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        );
        
        -- جدول التحضير اليومي
        CREATE TABLE IF NOT EXISTS daily_preparations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            subject VARCHAR(100),
            grade_level VARCHAR(50),
            lesson_title VARCHAR(200),
            objectives TEXT,
            content TEXT,
            teaching_methods TEXT,
            resources TEXT,
            evaluation_methods TEXT,
            homework TEXT,
            notes TEXT,
            preparation_date DATE,
            duration INTEGER DEFAULT 45,
            files TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        );
        
        -- جدول الأنشطة والمسابقات
        CREATE TABLE IF NOT EXISTS activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            activity_type VARCHAR(20) DEFAULT 'activity' CHECK (activity_type IN ('competition', 'activity', 'project')),
            target_grade VARCHAR(50),
            start_date DATE,
            end_date DATE,
            participants_count INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'planned' CHECK (status IN ('planned', 'ongoing', 'completed', 'cancelled')),
            results TEXT,
            files VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        );
        
        -- جدول متابعة المنهج
        CREATE TABLE IF NOT EXISTS curriculum_progress (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            subject VARCHAR(100),
            grade VARCHAR(50),
            unit_number INTEGER,
            unit_title VARCHAR(200),
            total_lessons INTEGER,
            completed_lessons INTEGER DEFAULT 0,
            start_date DATE,
            expected_end_date DATE,
            actual_end_date DATE,
            progress_percentage DECIMAL(5,2) DEFAULT 0,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        );
        
        -- جدول التقارير
        CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            report_type VARCHAR(20) DEFAULT 'monthly' CHECK (report_type IN ('monthly', 'quarterly', 'annual', 'special')),
            title VARCHAR(200),
            content TEXT,
            report_period_start DATE,
            report_period_end DATE,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        );

        -- جدول التنويهات والإنذارات
        CREATE TABLE IF NOT EXISTS warnings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            warning_type VARCHAR(20) DEFAULT 'notice' CHECK (warning_type IN ('notice', 'warning', 'final_warning')),
            title VARCHAR(200),
            description TEXT,
            issued_by INTEGER,
            issue_date DATE,
            is_read INTEGER DEFAULT 0,
            response TEXT,
            response_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
            FOREIGN KEY (issued_by) REFERENCES users(id)
        );

        -- جدول الحضور والغياب
        CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            attendance_date DATE,
            check_in_time TIME,
            check_out_time TIME,
            status VARCHAR(20) DEFAULT 'present' CHECK (status IN ('present', 'absent', 'late', 'early_leave')),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        );

        -- جدول الملفات
        CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER,
            file_name VARCHAR(255),
            original_name VARCHAR(255),
            file_path VARCHAR(500),
            file_type VARCHAR(50),
            file_size INTEGER,
            category VARCHAR(20) DEFAULT 'other' CHECK (category IN ('certificate', 'document', 'photo', 'report', 'other')),
            description TEXT,
            uploaded_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        );

        -- جدول الاختصاصات
        CREATE TABLE IF NOT EXISTS subjects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            name_en VARCHAR(100),
            description TEXT,
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- جدول الصفوف
        CREATE TABLE IF NOT EXISTS grades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            name_en VARCHAR(50),
            level INTEGER,
            description TEXT,
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        ";
        
        try {
            $this->db->exec($sql);
            $this->insertDefaultData();
        } catch (PDOException $e) {
            die('خطأ في إنشاء الجداول: ' . $e->getMessage());
        }
    }
    
    private function insertDefaultData() {
        // إدراج مدير افتراضي
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();

        if ($adminCount == 0) {
            $stmt = $this->db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        }

        // إدراج الاختصاصات الافتراضية
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM subjects");
        $stmt->execute();
        $subjectsCount = $stmt->fetchColumn();

        if ($subjectsCount == 0) {
            $defaultSubjects = [
                ['الرياضيات', 'Mathematics', 'تدريس مادة الرياضيات', 1],
                ['اللغة العربية', 'Arabic Language', 'تدريس مادة اللغة العربية', 2],
                ['اللغة الإنجليزية', 'English Language', 'تدريس مادة اللغة الإنجليزية', 3],
                ['العلوم', 'Science', 'تدريس مادة العلوم', 4],
                ['الاجتماعيات', 'Social Studies', 'تدريس مادة الاجتماعيات', 5],
                ['التربية الإسلامية', 'Islamic Education', 'تدريس مادة التربية الإسلامية', 6],
                ['التربية الفنية', 'Art Education', 'تدريس مادة التربية الفنية', 7],
                ['التربية الرياضية', 'Physical Education', 'تدريس مادة التربية الرياضية', 8],
                ['الحاسوب', 'Computer Science', 'تدريس مادة الحاسوب', 9],
                ['الموسيقى', 'Music', 'تدريس مادة الموسيقى', 10]
            ];

            $stmt = $this->db->prepare("INSERT INTO subjects (name, name_en, description, sort_order) VALUES (?, ?, ?, ?)");
            foreach ($defaultSubjects as $subject) {
                $stmt->execute($subject);
            }
        }

        // إدراج الصفوف الافتراضية
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM grades");
        $stmt->execute();
        $gradesCount = $stmt->fetchColumn();

        if ($gradesCount == 0) {
            $defaultGrades = [
                ['الصف الأول', 'Grade 1', 1, 'الصف الأول الابتدائي', 1],
                ['الصف الثاني', 'Grade 2', 2, 'الصف الثاني الابتدائي', 2],
                ['الصف الثالث', 'Grade 3', 3, 'الصف الثالث الابتدائي', 3],
                ['الصف الرابع', 'Grade 4', 4, 'الصف الرابع الابتدائي', 4],
                ['الصف الخامس', 'Grade 5', 5, 'الصف الخامس الابتدائي', 5],
                ['الصف السادس', 'Grade 6', 6, 'الصف السادس الابتدائي', 6],
                ['الصف السابع', 'Grade 7', 7, 'الصف السابع المتوسط', 7],
                ['الصف الثامن', 'Grade 8', 8, 'الصف الثامن المتوسط', 8],
                ['الصف التاسع', 'Grade 9', 9, 'الصف التاسع المتوسط', 9],
                ['الصف العاشر', 'Grade 10', 10, 'الصف العاشر الثانوي', 10],
                ['الصف الحادي عشر', 'Grade 11', 11, 'الصف الحادي عشر الثانوي', 11],
                ['الصف الثاني عشر', 'Grade 12', 12, 'الصف الثاني عشر الثانوي', 12]
            ];

            $stmt = $this->db->prepare("INSERT INTO grades (name, name_en, level, description, sort_order) VALUES (?, ?, ?, ?, ?)");
            foreach ($defaultGrades as $grade) {
                $stmt->execute($grade);
            }
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('خطأ في تنفيذ الاستعلام: ' . $e->getMessage());
        }
    }
    
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
}
?>
