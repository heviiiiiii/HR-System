-- ============================================
-- QIU Portal Database Schema (FULL)
-- Qaiwan International University
-- Plain Text Passwords Version
-- ============================================

CREATE DATABASE IF NOT EXISTS qiu_portal;
USE qiu_portal;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (order matters)
DROP TABLE IF EXISTS assignment_submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;

DROP TABLE IF EXISTS job_applications;
DROP TABLE IF EXISTS job_positions;

DROP TABLE IF EXISTS leave_requests;

DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS employees;

DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Users Table (Main Authentication)
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'employee', 'student') NOT NULL DEFAULT 'employee',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    photo VARCHAR(255) DEFAULT 'default.jpg',
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Departments Table
-- ============================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Employees Table (Extended Info)
-- ============================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    department_id INT,
    position VARCHAR(100),
    hire_date DATE,
    salary DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Students Table (Extended Info)
-- ============================================
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    department_id INT,
    program VARCHAR(100),
    enrollment_date DATE,
    gpa DECIMAL(3,2) DEFAULT 0.00,
    semester INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Leave Requests Table
-- ============================================
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type ENUM('annual', 'sick', 'maternity', 'emergency', 'other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    document_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Job Positions Table
-- ============================================
CREATE TABLE job_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    department_id INT,
    description TEXT,
    requirements TEXT,
    salary_range VARCHAR(50),
    posted_date DATE DEFAULT (CURRENT_DATE),
    deadline DATE,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Job Applications Table
-- ============================================
CREATE TABLE job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    applicant_name VARCHAR(100) NOT NULL,
    applicant_email VARCHAR(100) NOT NULL,
    applicant_phone VARCHAR(20),
    cv_path VARCHAR(255) NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'reviewed', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES job_positions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- ACADEMIC MODULE
-- =========================================================

-- ============================================
-- Courses Table
-- ============================================
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL,
    description TEXT,
    credits INT NOT NULL DEFAULT 3,
    department_id INT,
    instructor_id INT NULL,
    semester INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Enrollments Table
-- ============================================
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_user_id INT NOT NULL,
    course_id INT NOT NULL,
    grade VARCHAR(5) NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_course (student_user_id, course_id),
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Assignments Table
-- ============================================
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    max_score INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Assignment Submissions Table
-- ============================================
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score INT NULL,
    feedback TEXT,
    UNIQUE KEY uniq_submit (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Insert Default Data (PLAIN TEXT PASSWORDS)
-- ============================================

-- Admin User
INSERT INTO users (email, password, role, full_name, phone, photo) VALUES 
('admin@uniq.edu.iq', 'admin123', 'admin', 'System Admin', '+964 750 000 0001', 'manager.jpg');

-- HR User
INSERT INTO users (email, password, role, full_name, phone, photo) VALUES
('hr@uniq.edu.iq', 'hr12345', 'hr', 'HR Manager', '+964 750 000 0002', 'default.jpg');

-- Default Departments
INSERT INTO departments (name, description) VALUES 
('Software Engineering', 'Department of Software Engineering and Computer Science'),
('Business Administration', 'School of Business and Management'),
('Dentistry', 'College of Dentistry'),
('Health and Science', 'Faculty of Health Sciences'),
('PAP', 'Public Administration and Policy'),
('Accounting', 'Department of Accounting and Finance'),
('Human Resources', 'HR Department');

-- Instructor User
INSERT INTO users (email, password, role, full_name, phone, photo) VALUES
('instructor@uniq.edu.iq', 'emp12345', 'employee', 'Dr. Ahmed Instructor', '+964 750 000 0100', 'default.jpg');

-- Employee record (instructor)
INSERT INTO employees (user_id, employee_code, department_id, position, hire_date, salary)
VALUES
((SELECT id FROM users WHERE email='instructor@uniq.edu.iq'), 'EMP-1001', 1, 'Lecturer', '2024-09-01', 2500.00);

-- Student User
INSERT INTO users (email, password, role, full_name, phone, photo) VALUES
('student@uniq.edu.iq', 'std12345', 'student', 'Amad Student', '+964 750 000 0200', 'default.jpg');

-- Student record
INSERT INTO students (user_id, student_code, department_id, program, enrollment_date, gpa, semester)
VALUES
((SELECT id FROM users WHERE email='student@uniq.edu.iq'), 'STD-2001', 1, 'Software Engineering', '2025-10-01', 3.40, 2);

-- Sample Job Positions
INSERT INTO job_positions (title, department_id, description, requirements, salary_range, deadline, status) VALUES 
('Lecturer', 1, 'Full-time lecturer position in Software Engineering', 'PhD or Masters in Computer Science, 3+ years experience', '$2,500 - $3,500', '2026-02-15', 'open'),
('Administrative Assistant', 2, 'Support administrative operations', 'Bachelor degree, excellent communication skills', '$1,500 - $2,000', '2026-02-10', 'open'),
('Research Assistant', 1, 'Assist in ongoing research projects', 'Masters degree, research experience', '$1,800 - $2,200', '2026-02-20', 'open'),
('Teaching Assistant', 4, 'Support teaching activities', 'Bachelor degree in related field', '$1,200 - $1,600', '2026-02-25', 'open');

-- Sample Courses
INSERT INTO courses (course_code, course_name, description, credits, department_id, instructor_id, semester) VALUES
('SE101', 'Introduction to Programming', 'Basics of programming using modern languages.', 3, 1, (SELECT id FROM employees WHERE employee_code='EMP-1001'), 1),
('SE201', 'Data Structures', 'Arrays, Linked Lists, Trees, Graphs, and complexity.', 4, 1, (SELECT id FROM employees WHERE employee_code='EMP-1001'), 2),
('SE301', 'Database Systems', 'Relational model, SQL, normalization, and transactions.', 3, 1, (SELECT id FROM employees WHERE employee_code='EMP-1001'), 3);

-- Enroll student into courses
INSERT INTO enrollments (student_user_id, course_id, grade) VALUES
((SELECT id FROM users WHERE email='student@uniq.edu.iq'), (SELECT id FROM courses WHERE course_code='SE101'), 'A'),
((SELECT id FROM users WHERE email='student@uniq.edu.iq'), (SELECT id FROM courses WHERE course_code='SE201'), NULL);

-- Sample Assignments
INSERT INTO assignments (course_id, title, description, due_date, max_score) VALUES
((SELECT id FROM courses WHERE course_code='SE101'), 'Assignment 1 - Variables', 'Solve variable and input/output tasks.', '2026-02-10', 100),
((SELECT id FROM courses WHERE course_code='SE201'), 'Assignment 1 - Arrays', 'Implement array operations and analyze complexity.', '2026-02-15', 100);

-- ============================================
-- End of Schema
-- ============================================