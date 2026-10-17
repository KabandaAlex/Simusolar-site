-- Job Portal Database Migration
-- Run this migration to add job portal tables to existing database

USE tulima_solar;

-- Job Categories Table
CREATE TABLE IF NOT EXISTS job_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Job Locations Table
CREATE TABLE IF NOT EXISTS job_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    is_remote TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sort (sort_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Jobs Table
CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    qualifications TEXT,
    job_type ENUM('full-time', 'part-time', 'contract', 'internship', 'volunteer') NOT NULL,
    employment_type ENUM('permanent', 'temporary', 'seasonal') NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    salary_currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    is_featured TINYINT(1) DEFAULT 0,
    is_urgent TINYINT(1) DEFAULT 0,
    application_deadline DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES job_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_category (category_id),
    INDEX idx_location (location_id),
    INDEX idx_type (job_type),
    INDEX idx_deadline (application_deadline),
    INDEX idx_featured (is_featured),
    INDEX idx_urgent (is_urgent)
) ENGINE=InnoDB;

-- Job Tags Table
CREATE TABLE IF NOT EXISTS job_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Job Tag Relations Table (Many-to-Many)
CREATE TABLE IF NOT EXISTS job_tag_relations (
    job_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (job_id, tag_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES job_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Job Applications Table
CREATE TABLE IF NOT EXISTS job_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    applicant_name VARCHAR(255) NOT NULL,
    applicant_email VARCHAR(255) NOT NULL,
    applicant_phone VARCHAR(50),
    cover_letter TEXT,
    resume_path VARCHAR(500),
    status ENUM('pending', 'reviewed', 'interview', 'accepted', 'rejected') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    INDEX idx_job (job_id),
    INDEX idx_status (status),
    INDEX idx_date (created_at),
    INDEX idx_email (applicant_email)
) ENGINE=InnoDB;

-- Job Application Documents Table
CREATE TABLE IF NOT EXISTS job_application_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    document_type ENUM('resume', 'cover_letter', 'other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
    INDEX idx_application (application_id)
) ENGINE=InnoDB;

-- Insert default job categories
INSERT INTO job_categories (name, slug, description, sort_order) VALUES
('Engineering', 'engineering', 'Engineering positions', 1),
('Sales & Marketing', 'sales-marketing', 'Sales and marketing roles', 2),
('Finance & Accounting', 'finance-accounting', 'Financial and accounting positions', 3),
('Human Resources', 'human-resources', 'HR and people management', 4),
('Operations', 'operations', 'Operations and logistics', 5),
('IT & Technology', 'it-technology', 'IT and technical roles', 6),
('Research & Development', 'research-development', 'R&D positions', 7),
('Administrative', 'administrative', 'Administrative and support roles', 8),
('Executive', 'executive', 'Executive and leadership positions', 9),
('Customer Service', 'customer-service', 'Customer service roles', 10)
ON DUPLICATE KEY UPDATE name=name;

-- Insert default job locations
INSERT INTO job_locations (name, country, city, is_remote, sort_order) VALUES
('Nairobi, Kenya', 'Kenya', 'Nairobi', 0, 1),
('Mombasa, Kenya', 'Kenya', 'Mombasa', 0, 2),
('Kisumu, Kenya', 'Kenya', 'Kisumu', 0, 3),
('Remote - Africa', 'Kenya', '', 1, 4),
('Remote - International', 'Kenya', '', 1, 5)
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample job tags
INSERT INTO job_tags (name, slug) VALUES
('Leadership', 'leadership'),
('Team Management', 'team-management'),
('Project Management', 'project-management'),
('Communication', 'communication'),
('Problem Solving', 'problem-solving'),
('Technical Skills', 'technical-skills'),
('Foreign Language', 'foreign-language'),
('Certifications', 'certifications')
ON DUPLICATE KEY UPDATE name=name;

-- Create indexes for search functionality
CREATE INDEX idx_jobs_search ON jobs(title, description, requirements, qualifications);
CREATE INDEX idx_applications_search ON job_applications(applicant_name, applicant_email, applicant_phone);
