-- Create database
CREATE DATABASE IF NOT EXISTS plantilla;
USE plantilla;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    photo VARCHAR(255),
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Divisions table (for data management)
CREATE TABLE divisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Records table (for data management)
CREATE TABLE records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    division_id INT NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    salary_grade VARCHAR(10) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    data JSON,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Organizational codes table
CREATE TABLE organizational_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Applicants table
CREATE TABLE applicants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    position_id INT NOT NULL,
    department_id INT NOT NULL,
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected') NOT NULL DEFAULT 'pending',
    resume_path VARCHAR(255),
    photo_path VARCHAR(255),
    remarks TEXT,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES organizational_codes(id),
    FOREIGN KEY (department_id) REFERENCES organizational_codes(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'create', 'update', 'delete', 'upload', 'download') NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- File uploads table
CREATE TABLE file_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('csv', 'xlsx', 'pdf', 'image') NOT NULL,
    file_size INT NOT NULL,
    status ENUM('pending', 'processed', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User permissions table
CREATE TABLE user_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    can_view BOOLEAN DEFAULT TRUE,
    can_create BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_module (user_id, module)
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_division ON records(division_id);
CREATE INDEX idx_employee_status ON records(status);
CREATE INDEX idx_org_code ON organizational_codes(code);

-- Insert default admin user
INSERT INTO users (username, password, email, first_name, last_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System', 'Administrator', 'admin');

-- Insert sample divisions
INSERT INTO divisions (code, name, description) VALUES
('00', 'All Divisions', 'All organizational divisions'),
('OA', 'Office of the Administrator', 'Head office division'),
('AD', 'Administrative Division', 'General administration'),
('HR', 'Human Resources Management', 'HR operations'),
('RM', 'Records Management Section', 'Document management'),
('PP', 'Procurement Section', 'Purchasing services'),
('FP', 'Financial Planning', 'Budget management'),
('AC', 'Accounting Section', 'Financial records'),
('BP', 'Budget Planning', 'Fiscal planning'),
('MS', 'Management Services', 'Operational support'),
('ET', 'Engineering Services', 'Technical support'),
('ME', 'Meteorological Equipment', 'Weather instruments'),
('MG', 'Meteorological Guides', 'Forecasting standards'),
('EI', 'Engineering Infrastructure', 'Facilities maintenance'),
('WF', 'Weather Forecasting', 'Daily forecasts'),
('MD', 'Meteorological Data', 'Weather information'),
('TS', 'Techniques Section', 'Analysis methods'),
('AM', 'Aeronautical Meteorology', 'Aviation weather'),
('MM', 'Marine Meteorology', 'Maritime forecasts'),
('HY', 'Hydrometeorology', 'Water systems'),
('HD', 'Hydrological Data', 'Water monitoring'),
('FF', 'Flood Forecasting', 'Flood warnings'),
('HT', 'Hydrometeorological Telemetry', 'Remote sensing'),
('CL', 'Climatology', 'Climate patterns'),
('CM', 'Climate Monitoring', 'Climate tracking'),
('FW', 'Farm Weather', 'Agricultural forecasts'),
('IA', 'Impact Assessment', 'Weather effects'),
('CD', 'Climate Data', 'Climate records'),
('RD', 'Research Development', 'Scientific studies'),
('AS', 'Astronomy Space', 'Celestial events'),
('CR', 'Climate Research', 'Climate studies'),
('HM', 'Hydrometeorology Research', 'Water systems research'),
('NM', 'Numerical Modeling', 'Weather simulations'),
('TP', 'Training Public Info', 'Education outreach'),
('NL', 'Northern Luzon', 'Regional services'),
('AN', 'Agno Flood System', 'Agno river basin'),
('PA', 'Pampanga Flood System', 'Pampanga river basin'),
('SL', 'Southern Luzon', 'Regional services'),
('BI', 'Bicol Flood System', 'Bicol region'),
('VS', 'Visayas', 'Regional services'),
('NMI', 'Northern Mindanao', 'Regional services'),  -- Changed from NM to NMI
('SMI', 'Southern Mindanao', 'Regional services'),  -- Changed from SM to SMI
('FS', 'Field Stations', 'Regional field offices');

-- Insert default permissions for admin
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete)
SELECT id, 'dashboard', TRUE, TRUE, TRUE, TRUE FROM users WHERE role = 'admin';

INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete)
SELECT id, 'organizational_codes', TRUE, TRUE, TRUE, TRUE FROM users WHERE role = 'admin';

INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete)
SELECT id, 'applicants', TRUE, TRUE, TRUE, TRUE FROM users WHERE role = 'admin';

INSERT INTO user_permissions (user_id, module, can_view, can_create, can_edit, can_delete)
SELECT id, 'users', TRUE, TRUE, TRUE, TRUE FROM users WHERE role = 'admin';

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES 
('site_name', 'Plantilla Management System', 'Name of the system'),
('site_description', 'A comprehensive plantilla management system', 'Description of the system'),
('upload_max_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'csv,xlsx,pdf,jpg,jpeg,png', 'Comma-separated list of allowed file types'),
('session_timeout', '3600', 'Session timeout in seconds (1 hour)');