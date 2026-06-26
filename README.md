# Online Student Clearance System

## Overview

The Online Student Clearance System is a web-based application developed to automate the student clearance process in tertiary institutions. The system eliminates the traditional manual clearance procedure by allowing students to submit clearance requests, complete clearance forms, make online payments, and monitor the status of their applications through a centralized platform.

The application also provides office staff with tools to review registration requests, verify payments, approve or reject clearance applications, and manage the entire clearance workflow efficiently.

---

## Features

* Student registration and login
* Student dashboard
* Registration request submission
* Clearance form submission
* Online payment integration using Paystack
* Payment verification
* Clearance request tracking
* Office dashboard
* Approval and rejection of clearance requests
* Role-based access control
* Real-time clearance status updates

---

## Technologies Used

### Frontend

* HTML5
* CSS3
* JavaScript

### Backend

* PHP

### Database

* MySQL

### Payment Gateway

* Paystack API

### Development Environment

* XAMPP
* phpMyAdmin
* Visual Studio Code

---

## System Requirements

* PHP 8.0 or later
* MySQL 8.0 or later
* Apache Web Server (XAMPP recommended)
* Modern web browser (Chrome, Firefox, Edge)

---

## Installation

1. Clone the repository

```bash
git clone https://github.com/yourusername/online-student-clearance-system.git
```

2. Copy the project folder into the XAMPP `htdocs` directory.

3. Start Apache and MySQL from XAMPP.

4. Import the provided SQL database into phpMyAdmin.

5. Update the database configuration in `db.php` if necessary.

6. Open your browser and navigate to:

```
http://localhost/your-project-folder
```

---

## User Roles

### Student

* Register an account
* Login
* Submit registration request
* Fill clearance form
* Make payment
* Track clearance progress

### Office Staff

* Login
* Review student registration requests
* Verify payments
* Approve or reject clearance requests
* Manage student records

---

## Project Structure

```
project-folder/
│
├── admin/
├── assets/
├── css/
├── js/
├── uploads/
├── database/
├── db.php
├── login.php
├── logout.php
├── index.php
└── README.md
```

---

## Database

The project uses MySQL and consists of the following tables:

* office
* student
* registration_request
* payments
* clearance_request
* clearance_form

---

## Future Improvements

* Email notifications
* SMS notifications
* Student profile editing
* Admin dashboard analytics
* PDF clearance certificate generation
* Multi-factor authentication

---

## Author

**Oluwanifemi Afuye**

Department of Computer Science

Redeemers College of Technology and Management

National Diploma (ND) Project

2026

---

## License

This project was developed for academic purposes as a National Diploma project.
