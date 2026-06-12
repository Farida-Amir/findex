-- Create database
CREATE DATABASE IF NOT EXISTS findex_trial;
USE findex_trial;

-- Users table 
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('regular', 'shop', 'moderator', 'admin') DEFAULT 'regular',
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    profile_image VARCHAR(255),
    
    -- National ID fields (for regular users)
    national_id_number VARCHAR(50),
    national_id_image VARCHAR(255),
    national_id_verified BOOLEAN DEFAULT FALSE,
    national_id_verified_at DATETIME,
    national_id_verified_by INT,
    national_id_rejection_reason TEXT,
    
    -- Common verification
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    last_login DATETIME,
    status ENUM('active', 'suspended', 'pending', 'verification_required') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_national_id (national_id_number),
    INDEX idx_status (status),
    FOREIGN KEY (national_id_verified_by) REFERENCES users(id)
);

-- Shops table 
CREATE TABLE shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    business_registration_number VARCHAR(100),
    tax_id VARCHAR(100),
    business_license_number VARCHAR(100),
    
    -- Business license documents
    business_license_image VARCHAR(255),
    trade_license_image VARCHAR(255),
    vat_certificate_image VARCHAR(255),
    
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    website VARCHAR(255),
    description TEXT,
    logo VARCHAR(255),
    
    -- Verification status
    is_approved BOOLEAN DEFAULT FALSE,
    verified_badge BOOLEAN DEFAULT FALSE,
    verification_level ENUM('none', 'basic', 'verified', 'premium') DEFAULT 'none',
    
    subscription_type ENUM('free', 'basic', 'premium') DEFAULT 'free',
    subscription_expires DATE,
    
    approval_notes TEXT,
    approved_by INT,
    approved_at DATETIME,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_business_name (business_name),
    INDEX idx_registration_number (business_registration_number),
    INDEX idx_is_approved (is_approved),
    INDEX idx_verification_level (verification_level),
    INDEX idx_verified_badge (verified_badge)
);

-- Verification requests log
CREATE TABLE verification_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    verification_type ENUM('national_id', 'business_license', 'trade_license', 'vat_certificate') NOT NULL,
    document_path VARCHAR(255) NOT NULL,
    document_number VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_verification_type (verification_type)
);



-- Insert default admin user
INSERT INTO users (email, password_hash, full_name, user_type, is_verified, status) VALUES
('admin@findex.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', TRUE, 'active');