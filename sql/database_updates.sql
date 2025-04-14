-- Add financial management tables to the database

-- Create funds_management table to track money transfers into the system
CREATE TABLE IF NOT EXISTS funds_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'adjustment') NOT NULL,
    reference_number VARCHAR(50),
    source VARCHAR(100) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create allowance_claims table to track which students have claimed their allowances
CREATE TABLE IF NOT EXISTS allowance_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    allowance_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    claim_date DATETIME,
    status ENUM('pending', 'claimed', 'unclaimed', 'expired') DEFAULT 'pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (allowance_id) REFERENCES allowance_schedule(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Create financial_reports table to store generated reports
CREATE TABLE IF NOT EXISTS financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('monthly', 'yearly', 'custom') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_funds DECIMAL(10,2) NOT NULL,
    total_disbursed DECIMAL(10,2) NOT NULL,
    total_unclaimed DECIMAL(10,2) NOT NULL,
    report_data JSON,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Add balance column to track available funds in the system
ALTER TABLE allowance_schedule ADD COLUMN claimed BOOLEAN DEFAULT FALSE;