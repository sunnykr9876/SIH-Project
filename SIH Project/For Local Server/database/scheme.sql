-- htdocs/database/schema.sql

-- 1. Users Table (Handles authentication for all roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('STUDENT', 'VERIFICATION_OFFICER', 'ADMIN', 'SUPER_ADMIN') DEFAULT 'STUDENT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Students Profile Table (Extended details for students)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(15),
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    category VARCHAR(10) DEFAULT 'ST',
    annual_income DECIMAL(10,2),
    aadhaar_masked VARCHAR(14), -- Storing only masked format like XXXX XXXX 9012
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Scholarships Table (CMS for schemes)
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    scheme_type VARCHAR(100),
    short_description TEXT,
    benefits TEXT,
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Applications Table (Tracks student submissions)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    scholarship_id INT NOT NULL,
    status ENUM('DRAFT', 'SUBMITTED', 'DOCUMENT_VERIFICATION', 'DEFICIENCY', 'UNDER_SCRUTINY', 'ELIGIBLE', 'NOT_ELIGIBLE', 'SHORTLISTED', 'SELECTED', 'REJECTED', 'APPROVED') DEFAULT 'DRAFT',
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
);