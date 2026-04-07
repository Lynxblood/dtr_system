-- Time & Attendance System Database Schema

-- =====================================================
-- Users Table
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'employee', 'president', 'head') DEFAULT 'employee',
    en_no INT,
    requires_password_change TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- =====================================================
-- Employees Table
-- =====================================================
CREATE TABLE IF NOT EXISTS employees (
    en_no INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    head_of_employee INT NULL, -- References users.id for head role
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    FOREIGN KEY (head_of_employee) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- Attendance Logs Table
-- =====================================================
CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tm_no INT,
    en_no INT NOT NULL,
    in_out INT,
    mode INT,
    record_datetime DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_log (en_no, record_datetime),
    FOREIGN KEY (en_no) REFERENCES employees(en_no) ON DELETE CASCADE,
    INDEX idx_en_no (en_no),
    INDEX idx_datetime (record_datetime),
    INDEX idx_in_out (in_out)
);

-- =====================================================
-- Employee Submissions Table
-- =====================================================
CREATE TABLE IF NOT EXISTS employee_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    en_no INT NOT NULL,
    month VARCHAR(7) NOT NULL, -- YYYY-MM
    file_path VARCHAR(255),
    status ENUM('draft', 'employee_signed', 'submitted_to_head', 'head_signed', 'submitted_to_president', 'president_signed', 'submitted_to_hr', 'approved', 'rejected') DEFAULT 'draft',
    employee_signed_at TIMESTAMP NULL,
    head_signed_at TIMESTAMP NULL,
    president_signed_at TIMESTAMP NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (en_no) REFERENCES employees(en_no) ON DELETE CASCADE,
    INDEX idx_en_no_month (en_no, month),
    INDEX idx_status (status)
);

-- =====================================================
-- Notifications Table
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    en_no INT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (en_no) REFERENCES employees(en_no) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_en_no (en_no),
    INDEX idx_read (is_read)
);

-- =====================================================
-- Certificates Table (for electronic signatures)
-- =====================================================
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Signature Styles Table (for electronic signatures)
-- =====================================================
CREATE TABLE IF NOT EXISTS signature_styles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    certificate_id INT NOT NULL,
    font_family VARCHAR(100) NOT NULL DEFAULT 'dejavusans',
    font_size INT NOT NULL DEFAULT 9,
    font_style VARCHAR(10) NOT NULL DEFAULT '',
    show_name TINYINT(1) NOT NULL DEFAULT 1,
    show_location TINYINT(1) NOT NULL DEFAULT 1,
    show_date TINYINT(1) NOT NULL DEFAULT 1,
    show_unique TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE,
    INDEX idx_certificate_id (certificate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTE: For existing databases, add user_id and allow nullable en_no in notifications:
-- ALTER TABLE notifications ADD COLUMN user_id INT NULL;
-- ALTER TABLE notifications MODIFY COLUMN en_no INT NULL;
-- ALTER TABLE notifications ADD INDEX idx_user_id (user_id);
-- ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- =====================================================
-- Insert Default Admin User (optional)
-- =====================================================
-- Username: admin, Password: admin123 (hashed with bcrypt, min 8 chars)
-- INSERT INTO users (username, password, role, requires_password_change) VALUES
-- ('admin', '$2y$10$your_bcrypt_hash_here', 'admin', 0);

-- Sample password hash for testing: password_hash('ilove@BASC1', PASSWORD_BCRYPT)
-- Contact your administrator for the actual admin credentials
