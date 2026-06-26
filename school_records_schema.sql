CREATE DATABASE IF NOT EXISTS school_records_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_records_db;

-- ── Outstanding Fees Table 
CREATE TABLE IF NOT EXISTS outstanding_fees (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  matric_number VARCHAR(50)  NOT NULL,
  fee_type      VARCHAR(100) NOT NULL,   
  amount        DECIMAL(10,2) NOT NULL,
  session       VARCHAR(20)  DEFAULT NULL, 
  status        ENUM('owing','paid') DEFAULT 'owing',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Library Records Table 
CREATE TABLE IF NOT EXISTS library_records (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  matric_number   VARCHAR(50)  NOT NULL,
  book_title      VARCHAR(200) NOT NULL,
  book_code       VARCHAR(50)  DEFAULT NULL,
  date_borrowed   DATE         DEFAULT NULL,
  due_date        DATE         DEFAULT NULL,
  status          ENUM('borrowed','returned') DEFAULT 'borrowed',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Sample Data: Outstanding Fees 
INSERT INTO outstanding_fees (matric_number, fee_type, amount, session, status) VALUES
('CSC/2020/001', 'Hostel Fee',        15000.00, '2025/2026', 'owing'),
('CSC/2020/001', 'Library Late Fee',   2500.00, '2025/2026', 'owing'),
('CSC/2020/002', 'School Fee Balance', 8000.00, '2025/2026', 'owing'),
('ENG/2021/010', 'Hostel Fee',        15000.00, '2025/2026', 'paid'),
('LAW/2019/045', 'Departmental Due',   3000.00, '2025/2026', 'owing'),
('MED/2022/003', 'Lab Fee',            5000.00, '2025/2026', 'paid');

-- ── Sample Data: Library Records 
INSERT INTO library_records (matric_number, book_title, book_code, date_borrowed, due_date, status) VALUES
('CSC/2020/001', 'Introduction to Algorithms',        'LIB-CS-014', '2025-11-01', '2025-11-15', 'borrowed'),
('CSC/2020/002', 'Database Systems Concepts',         'LIB-CS-022', '2025-10-10', '2025-10-24', 'returned'),
('ENG/2021/010', 'Engineering Mathematics Vol. 2',    'LIB-ENG-008','2025-09-15', '2025-09-29', 'returned'),
('LAW/2019/045', 'Nigerian Constitutional Law',       'LIB-LAW-003','2025-11-05', '2025-11-19', 'borrowed'),
('MED/2022/003', 'Clinical Anatomy',                  'LIB-MED-011','2025-08-20', '2025-09-03', 'returned');

-- Verify
SELECT * FROM outstanding_fees;
SELECT * FROM library_records;