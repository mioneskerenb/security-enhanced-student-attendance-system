# Student Attendance Management System

## Overview

The Student Attendance Management System is a web-based application developed using PHP and MySQL. It is designed to manage student records, class teachers, classes, class arms, session terms, and attendance records.

The system has two main user roles: Administrator and ClassTeacher. The Administrator manages the main records of the system, while the ClassTeacher handles attendance-related tasks for their assigned class and class arm.

This version of the system includes several security enhancements such as secure login, role-based access control, session protection, password hashing, progressive login rate limiting, prepared statements, and database trigger protection.

## System Users

### Administrator

The Administrator is responsible for managing the main records of the system.

Administrator features include:

- Manage admin accounts
- Manage class teachers
- Manage students
- Manage classes
- Manage class arms
- Manage session and term records
- View dashboard totals
- Control system records through protected pages

### ClassTeacher

The ClassTeacher is responsible for attendance management.

ClassTeacher features include:

- View assigned students
- Take attendance
- View class attendance
- View student attendance
- Download attendance report
- Change account password

## Main Features

- Secure login with automatic role detection
- Administrator and ClassTeacher dashboards
- Role-based access control
- Progressive login rate limiting
- Secure session handling
- Class and class arm management
- Class teacher account management
- Student management without student passwords
- Attendance taking
- Attendance viewing by date
- Student attendance filtering
- Attendance report export
- ClassTeacher change password feature
- Admin account creation
- Secure logout
- Professional Bootstrap-based user interface

## Security Enhancements

The system includes the following security improvements:

- SQL Injection prevention using prepared statements
- Password hashing using `password_hash()`
- Password verification using `password_verify()`
- MD5 fallback support for old passwords
- Progressive login lockout after failed attempts
- Session timeout after inactivity
- Browser user-agent validation
- Role-based page protection
- XSS reduction using `htmlspecialchars()`
- Database triggers to prevent manual database manipulation
- Secure logout with session destruction
- Safer database error handling

## Technologies Used

- PHP
- MySQL
- phpMyAdmin
- HTML
- CSS
- Bootstrap
- JavaScript
- jQuery
- DataTables
- XAMPP

## Database

The system uses a MySQL database. The main tables include:

- `tbladmin`
- `tblclassteacher`
- `tblstudents`
- `tblclass`
- `tblclassarms`
- `tblattendance`
- `tblsessionterm`
- `tblterm`
- `login_attempts`

## Important Security Table

The `login_attempts` table is used for progressive login rate limiting. It stores failed login attempts, IP address, lock level, and lock expiration time.

## Database Trigger Protection

Database triggers were implemented to protect important tables from manual insert, update, and delete actions through phpMyAdmin. The system uses MySQL session variables such as:

```sql
@app_user_id
@app_role


Login Data

ClassTeacher

email: keren@gmail.com
password: pass123

Admin
email: admin@mail.com
password: admin123


