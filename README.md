# Nura Authentication System

A secure full-stack authentication and profile management system developed as an internship assignment for Nura Intelligence Systems.

## Features

- User registration
- User login with registered credentials
- Password hashing using PHP `password_hash()`
- Password verification using `password_verify()`
- AJAX-based form submission using jQuery
- MySQL user authentication database
- Redis-based login session management
- MongoDB profile information storage
- Protected profile page
- Secure logout and session deletion
- Input validation and sanitization
- Prepared SQL statements
- Bootstrap responsive UI
- Environment-based configuration using `.env`

## Technology Stack

### Frontend
- HTML5
- CSS3
- Bootstrap
- JavaScript
- jQuery
- AJAX

### Backend
- PHP 8.2
- PDO
- Composer

### Databases & Services
- MySQL
- MongoDB Atlas
- Redis Cloud

## Project Structure

```text
nura-authentication-system/
│
├── assets/
├── css/
│   └── style.css
├── js/
│   ├── login.js
│   ├── profile.js
│   └── register.js
├── php/
│   ├── login.php
│   ├── profile.php
│   └── register.php
├── docs/
│   ├── application-flow.md
│   └── database-schema.md
├── index.html
├── login.html
├── register.html
├── profile.html
├── composer.json
├── composer.lock
├── database.sql
├── .env.example
└── .gitignorREADME.md
