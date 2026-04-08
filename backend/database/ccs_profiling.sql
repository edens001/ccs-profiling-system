-- Create database
CREATE DATABASE IF NOT EXISTS ccs_profiling_system;
USE ccs_profiling_system;

-- Users table (for all user types)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    role ENUM('admin', 'dean', 'dept_chair', 'secretary', 'faculty', 'student') NOT NULL,
    profile_picture VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    course VARCHAR(50) NOT NULL,
    year_level INT NOT NULL,
    section VARCHAR(10),
    contact_number VARCHAR(20),
    address TEXT,
    birth_date DATE,
    gender ENUM('Male', 'Female', 'Other'),
    guardian_name VARCHAR(100),
    guardian_contact VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Faculty table
CREATE TABLE faculty (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    faculty_number VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(50) NOT NULL,
    designation VARCHAR(100),
    specialization VARCHAR(100),
    contact_number VARCHAR(20),
    employment_status ENUM('Full-time', 'Part-time', 'Contractual') DEFAULT 'Full-time',
    date_hired DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    admin_level ENUM('Dean', 'Department Chair', 'Secretary') NOT NULL,
    department VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    department VARCHAR(50),
    units INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Sections table
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_code VARCHAR(20) UNIQUE NOT NULL,
    course_id INT,
    year_level INT NOT NULL,
    max_students INT DEFAULT 40,
    academic_year VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- Rooms table
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_code VARCHAR(20) UNIQUE NOT NULL,
    building VARCHAR(50),
    capacity INT NOT NULL,
    room_type ENUM('Lecture', 'Laboratory', 'Office') DEFAULT 'Lecture',
    is_available BOOLEAN DEFAULT TRUE
);

-- Schedules table
CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    faculty_id INT,
    section_id INT,
    room_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Enrollments table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    section_id INT,
    enrollment_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('Enrolled', 'Dropped', 'Completed') DEFAULT 'Enrolled',
    academic_year VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
);

-- Grades table
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT,
    schedule_id INT,
    prelim_grade DECIMAL(5,2),
    midterm_grade DECIMAL(5,2),
    final_grade DECIMAL(5,2),
    remarks ENUM('Passed', 'Failed', 'Incomplete') DEFAULT 'Incomplete',
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
);

-- Syllabus table (fixed - changed 'references' to 'reference_materials')
CREATE TABLE syllabus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    faculty_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    objectives TEXT,
    learning_outcomes TEXT,
    grading_system TEXT,
    policies TEXT,
    reference_materials TEXT,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE SET NULL
);

-- Lessons table
CREATE TABLE lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    syllabus_id INT,
    week_number INT NOT NULL,
    topic VARCHAR(200) NOT NULL,
    objectives TEXT,
    activities TEXT,
    resources TEXT,
    FOREIGN KEY (syllabus_id) REFERENCES syllabus(id) ON DELETE CASCADE
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('Curricular', 'Extracurricular') NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    venue VARCHAR(100),
    organizer VARCHAR(100),
    created_by INT,
    created_at INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Event participants
CREATE TABLE event_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    user_id INT,
    role VARCHAR(50),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample users with actual password hashes (password = 'password123')
INSERT INTO users (username, email, password, first_name, last_name, role) VALUES
('admin', 'admin@ccs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin'),
('dean', 'dean@ccs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', 'dean'),
('faculty1', 'jdelacruz@ccs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Dela Cruz', 'faculty'),
('student1', 'mreyes@student.ccs.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Reyes', 'student');

-- Insert sample courses
INSERT INTO courses (course_code, course_name, department, units, description) VALUES
('CS101', 'Introduction to Computing', 'Computer Studies', 3, 'Fundamental concepts of computing'),
('CS102', 'Computer Programming 1', 'Computer Studies', 3, 'Introduction to programming using Python'),
('CS201', 'Data Structures and Algorithms', 'Computer Studies', 3, 'Advanced programming concepts'),
('IT101', 'Web Development Fundamentals', 'Information Technology', 3, 'HTML, CSS, and JavaScript basics');

-- Insert sample rooms
INSERT INTO rooms (room_code, building, capacity, room_type) VALUES
('R101', 'Main Building', 40, 'Lecture'),
('R102', 'Main Building', 35, 'Lecture'),
('LAB201', 'IT Building', 30, 'Laboratory'),
('LAB202', 'IT Building', 30, 'Laboratory');

-- Insert sample sections
INSERT INTO sections (section_code, course_id, year_level, max_students, academic_year, semester) VALUES
('BSCS-2A', 1, 2, 40, '2024-2025', 2),
('BSIT-1B', 2, 1, 35, '2024-2025', 2);

-- Insert sample students (after users are created)
INSERT INTO students (user_id, student_number, course, year_level, section, contact_number, address, birth_date, gender) 
VALUES (4, '2024-0001', 'BS Computer Science', 2, 'A', '09123456789', 'Manila', '2000-01-15', 'Female');

-- Insert sample faculty
INSERT INTO faculty (user_id, faculty_number, department, designation, specialization, contact_number, employment_status, date_hired)
VALUES (3, 'FAC-2024-001', 'Computer Studies', 'Assistant Professor', 'Web Development', '09234567890', 'Full-time', '2020-06-01');

-- Insert sample admin
INSERT INTO admins (user_id, admin_level, department) VALUES (1, 'Dean', 'Computer Studies');
INSERT INTO admins (user_id, admin_level, department) VALUES (2, 'Dean', 'Computer Studies');