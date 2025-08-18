-- SMS WTF Database Schema
CREATE DATABASE IF NOT EXISTS sms_wtf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sms_wtf;

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Phone numbers table
CREATE TABLE phone_numbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    display_name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- SMS messages table
CREATE TABLE sms_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    sender_number VARCHAR(20),
    sender_name VARCHAR(100),
    message TEXT NOT NULL,
    received_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_number (phone_number),
    INDEX idx_received_at (received_at),
    INDEX idx_sender_number (sender_number)
);

-- Website settings table
CREATE TABLE website_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO website_settings (setting_key, setting_value) VALUES 
('site_password_enabled', 'false'),
('site_password_hash', ''),
('site_title', 'SMS WTF'),
('webhook_secret', '');

-- Create default admin user (username: admin, password: admin123)
-- Password hash for 'admin123'
INSERT INTO admin_users (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
