-- Create database if not exists
CREATE DATABASE IF NOT EXISTS bsccl_maintenance;
USE bsccl_maintenance;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Circuit list table
CREATE TABLE IF NOT EXISTS circuit_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    circuit_id VARCHAR(50) NOT NULL,
    admin_a VARCHAR(255),
    admin_b VARCHAR(255),
    bandwidth VARCHAR(50),
    status VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_circuit_id (circuit_id),
    INDEX idx_status (status)
);

-- Maintenance records table
CREATE TABLE IF NOT EXISTS maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (start_time, end_time)
);

-- Maintenance circuits table
CREATE TABLE IF NOT EXISTS maintenance_circuits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_id INT NOT NULL,
    circuit_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maintenance_id) REFERENCES maintenance_records(id) ON DELETE CASCADE,
    INDEX idx_circuit (circuit_id)
);

-- Affected circuits table
CREATE TABLE IF NOT EXISTS affected_circuits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_id INT NOT NULL,
    circuit_id VARCHAR(50) NOT NULL,
    bandwidth VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maintenance_id) REFERENCES maintenance_records(id) ON DELETE CASCADE,
    INDEX idx_maintenance (maintenance_id),
    INDEX idx_circuit (circuit_id)
);

-- Create upload_logs table to track file uploads
CREATE TABLE IF NOT EXISTS upload_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_type ENUM('circuit', 'maintenance') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password) VALUES 
('Admin', 'admin@bsccl.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');