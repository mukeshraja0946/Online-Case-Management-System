

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(50) DEFAULT NULL,
    staff_id VARCHAR(50) DEFAULT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'staff') NOT NULL
);

CREATE TABLE IF NOT EXISTS cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(50) NOT NULL,
    case_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    staff_remark TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS valid_staff_ids (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id VARCHAR(50) UNIQUE NOT NULL
);

-- Insert sample valid staff IDs
INSERT INTO valid_staff_ids (staff_id) VALUES ('STAFF001'), ('STAFF002'), ('STAFF003');
