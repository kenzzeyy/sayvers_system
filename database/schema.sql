-- SAYVERS Management System Database Schema
-- Created for: Sta. Cruz Alliance of Young Volunteers for Emergencies
-- Date: September 13, 2025

CREATE DATABASE sayvers_system;
USE sayvers_system;

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insert default admin user (password: admin123 - should be changed)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    surname VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    age INT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    district VARCHAR(100),
    position VARCHAR(100),
    standing_committee VARCHAR(100),
    subcommittee VARCHAR(100),
    university_school VARCHAR(200),
    course_strand VARCHAR(150),
    year_grade VARCHAR(50),
    emergency_contact_name VARCHAR(150),
    emergency_contact_number VARCHAR(20),
    medical_conditions TEXT,
    skills_trainings TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Member documents table
CREATE TABLE member_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    document_name VARCHAR(255),
    file_path VARCHAR(500),
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Finance categories table
CREATE TABLE finance_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    category_type ENUM('Income', 'Expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO finance_categories (category_name, category_type) VALUES
('Membership Fees', 'Income'),
('Donations', 'Income'),
('Fundraising', 'Income'),
('Training Expenses', 'Expense'),
('Equipment Purchase', 'Expense'),
('Office Supplies', 'Expense'),
('Travel and Transportation', 'Expense'),
('Communication', 'Expense'),
('Other Income', 'Income'),
('Other Expenses', 'Expense');

-- Financial transactions table
CREATE TABLE financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('Income', 'Expense') NOT NULL,
    category_id INT,
    reference_number VARCHAR(50),
    created_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES finance_categories(id)
);

-- Financial documents table
CREATE TABLE financial_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT,
    document_name VARCHAR(255),
    file_path VARCHAR(500),
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES financial_transactions(id) ON DELETE CASCADE
);

-- Audit trail table
CREATE TABLE audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    user_id VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('organization_name', 'Sta. Cruz Alliance of Young Volunteers for Emergencies (SAYVERS)', 'Organization full name'),
('organization_short', 'SAYVERS', 'Organization short name'),
('system_version', '1.0', 'Current system version'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png,gif', 'Allowed file extensions for uploads');

-- Create indexes for better performance
CREATE INDEX idx_members_surname ON members(surname);
CREATE INDEX idx_members_first_name ON members(first_name);
CREATE INDEX idx_members_status ON members(status);
CREATE INDEX idx_transactions_date ON financial_transactions(transaction_date);
CREATE INDEX idx_transactions_type ON financial_transactions(transaction_type);
CREATE INDEX idx_audit_table_record ON audit_trail(table_name, record_id);
CREATE INDEX idx_audit_created_at ON audit_trail(created_at);