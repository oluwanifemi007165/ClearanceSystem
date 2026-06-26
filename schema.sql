-- Student Clearance System — Database Schema

CREATE DATABASE IF NOT EXISTS clearance_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clearance_db;

CREATE TABLE IF NOT EXISTS students (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(150) NOT NULL,
  matric_number VARCHAR(50)  NOT NULL UNIQUE,
  department    VARCHAR(100) NOT NULL,
  level         VARCHAR(10)  NOT NULL,
  email         VARCHAR(150),
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS offices (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  office_name   VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  amount     DECIMAL(10,2) NOT NULL,
  tx_ref     VARCHAR(100)  NOT NULL UNIQUE,
  status     ENUM('pending','success','failed') DEFAULT 'success',
  paid_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clearance_forms (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  student_id        INT  NOT NULL UNIQUE,
  reason            TEXT NOT NULL,
  address           TEXT NOT NULL,
  phone             VARCHAR(20)  NOT NULL,
  emergency_contact VARCHAR(200) NOT NULL,
  submitted_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clearance_requests (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  office     VARCHAR(50) NOT NULL,
  status     ENUM('pending','approved','rejected') DEFAULT 'pending',
  comment    TEXT,
  sort_order INT DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  UNIQUE KEY uq_student_office (student_id, office)
);

-- Sample students
INSERT IGNORE INTO students (full_name, matric_number, department, level, email) VALUES
('John Adebayo',   'CSC/2020/001', 'Computer Science',       '400', 'john@uni.edu.ng'),
('Sarah Okon',     'CSC/2020/002', 'Computer Science',       '400', 'sarah@uni.edu.ng'),
('Michael Okafor', 'ENG/2021/010', 'Electrical Engineering', '300', 'michael@uni.edu.ng'),
('Aisha Bello',    'LAW/2019/045', 'Law',                    '500', 'aisha@uni.edu.ng'),
('David Chukwu',   'MED/2022/003', 'Medicine',               '200', 'david@uni.edu.ng');
('Afuye Nifemi',   'CSC/2022/003', 'Computer Science',       '200', 'nifemi@uni.edu.ng');

-- Sample offices (password for all: office123)
-- Hash generated with: php -r "echo password_hash('office123', PASSWORD_DEFAULT);"
INSERT IGNORE INTO offices (office_name, username, password_hash) VALUES
('Bursary',                     'bursary',                                   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Head Of Department',          'head of department',                        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Dean Of School',              'dean of school',                            '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('School Office',               'school office',                             '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Student Affairs',             'student affairs',                           '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Center Of Enterprenur',       'center of enterprenur',                     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Admission Office',            'admission office',                          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Accademic Affairs',           'accademic affairs',                         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
("Rector's Office",             'rectors_office',                            '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');