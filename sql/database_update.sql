-- Add missing tables for Scholar system

-- Create student_academic_status table
CREATE TABLE IF NOT EXISTS student_academic_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    total_return_service INT DEFAULT 0,
    renewal_status ENUM('renewed', 'pending', 'not_renewed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_academic_period (user_id, academic_year, semester)
);

-- Create return_service table
CREATE TABLE IF NOT EXISTS return_service (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    hours_completed INT NOT NULL DEFAULT 0,
    activity_description TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rs_period (user_id, academic_year, semester)
);

-- Create academic_status_options table
CREATE TABLE IF NOT EXISTS academic_status_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create student_current_status table
CREATE TABLE IF NOT EXISTS student_current_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data for academic status options
INSERT INTO academic_status_options (status, description) VALUES
('Enrolled', 'Currently enrolled in classes'),
('On Leave', 'Temporarily not attending classes'),
('Graduated', 'Completed the program'),
('Dropped', 'No longer in the program');

-- Insert sample data for student_academic_status
INSERT INTO student_academic_status (user_id, academic_year, semester, status, total_return_service)
VALUES (3, '2023-2024', '1st', 'Enrolled', 40);

-- Insert sample data for return_service
INSERT INTO return_service (user_id, academic_year, semester, hours_completed, activity_description, status)
VALUES (3, '2023-2024', '1st', 40, 'Community outreach program', 'approved');

-- Add middle_name column to users table
ALTER TABLE users
ADD COLUMN middle_name VARCHAR(50) NULL AFTER first_name;