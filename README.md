# Nura Authentication System

A secure and responsive authentication system developed as part of the Nura Intelligence Systems internship assignment.

The application provides user registration, login authentication, Redis-based session management, and profile management using PHP, MySQL, MongoDB, jQuery/AJAX, and Bootstrap.

---

## 🚀 Live Application

🔗 **Live URL:**  
https://nura-authentication-system.onrender.com/login.html

🔗 **GitHub Repository:**  
https://github.com/VanishaarunV7/nura-authentication-system

---

## ✨ Features

- User registration
- Secure password hashing
- User login and authentication
- Redis-based session management
- Profile management
- MongoDB profile storage
- MySQL authentication database
- AJAX-based form submissions
- jQuery frontend interactions
- Bootstrap responsive UI
- Input validation and sanitization
- Prepared SQL statements
- Secure environment variables
- Logout and session termination
- Responsive design for desktop and mobile

---

## 🛠️ Technologies Used

### Frontend

- HTML5
- CSS3
- JavaScript
- jQuery
- AJAX
- Bootstrap

### Backend

- PHP 8.2
- Composer
- Predis
- MongoDB PHP Driver

### Databases

- MySQL
- MongoDB
- Redis

### Deployment & Cloud Services

- Docker
- Render
- MongoDB Atlas
- Aiven MySQL
- Redis Cloud

---

## 🏗️ System Architecture

The application uses three databases, each with a specific responsibility.

### MySQL

MySQL stores authentication-related information:

- User ID
- Username
- Email
- Hashed password
- Account creation timestamp

### MongoDB

MongoDB stores additional user profile information:

- User ID
- Full name
- Age
- Bio
- Interests
- Profile update timestamp

### Redis

Redis manages authentication sessions.

Session information is stored using a hashed authentication token:

```text
session:<SHA-256(auth_token)>

🔄 Application Flow
1. Registration Flow
User
  ↓
Registration Page
  ↓
jQuery / AJAX
  ↓
PHP Register API
  ↓
Input Validation
  ↓
Password Hashing
  ↓
MySQL
  ↓
Registration Successful
  ↓
Login Page
2. Login Flow
User
  ↓
Login Page
  ↓
jQuery / AJAX
  ↓
PHP Login API
  ↓
MySQL User Verification
  ↓
Password Verification
  ↓
Redis Session Creation
  ↓
Authentication Cookie
  ↓
Profile Page
3. Profile Loading Flow
Profile Page
  ↓
PHP Profile API
  ↓
Redis Session Verification
  ↓
MySQL User Information
  +
MongoDB Profile Information
  ↓
Profile Display
4. Profile Update Flow
Profile Form
  ↓
jQuery / AJAX
  ↓
PHP Profile API
  ↓
Redis Session Verification
  ↓
Input Validation
  ↓
MongoDB
  ↓
Profile Updated
5. Logout Flow
Logout
  ↓
PHP Profile API
  ↓
Redis Session Deleted
  ↓
Authentication Cookie Removed
  ↓
Login Page
📁 Project Structure
nura-authentication-system/
│
├── assets/
│
├── css/
│
├── js/
│   ├── login.js
│   ├── profile.js
│   └── register.js
│
├── php/
│   ├── login.php
│   ├── profile.php
│   └── register.php
│
├── docs/
│   ├── application-flow.md
│   ├── database-schema.md
│   ├── application flow.png
│   ├── My SQL ER diagram.png
│   ├── mongodb relationship.png
│   ├── Redis flow.png
│   └── overall architecture.png
│
├── index.html
├── login.html
├── profile.html
├── register.html
├── Dockerfile
├── composer.json
├── composer.lock
├── ca.pem
├── .gitignore
└── README.md
🔐 Security Measures

The application implements several security practices:

Passwords are securely hashed using PHP password hashing.
SQL queries use prepared statements.
User input is validated before processing.
Profile data is safely handled on the frontend.
Authentication sessions are stored in Redis.
Authentication tokens are hashed before being used as Redis keys.
Sensitive credentials are stored using environment variables.
.env is excluded from Git using .gitignore.
Composer dependencies are managed separately from application code.
HTTPS is used for the deployed application.
Database credentials are not hard-coded in the source code.
⚙️ Local Setup
1. Clone the Repository
git clone https://github.com/VanishaarunV7/nura-authentication-system.git
2. Open the Project

Place the project inside the XAMPP htdocs directory:

C:\xampp\htdocs\nura-authentication-system
3. Install Composer Dependencies
composer install
4. Configure Environment Variables

Create a .env file in the project root.

Add the required configuration:

MYSQL_HOST
MYSQL_PORT
MYSQL_DATABASE
MYSQL_USER
MYSQL_PASSWORD
MYSQL_SSL_CA

MONGODB_URI
MONGODB_DATABASE

REDIS_SCHEME
REDIS_HOST
REDIS_PORT
REDIS_USERNAME
REDIS_PASSWORD

Never commit the .env file to GitHub.

5. Start XAMPP

Start the following services:

Apache
MySQL
6. Open the Application
http://localhost/nura-authentication-system/
🌐 Deployment

The application is deployed using Docker on Render.

Cloud services used by the deployed application:

Render — Application hosting
Aiven MySQL — Cloud MySQL database
MongoDB Atlas — Cloud MongoDB database
Redis Cloud — Redis session management

Environment variables are configured directly in the deployment platform and are not stored in the source code.

🧪 Application Testing

The following application flows were tested successfully:

User registration
Duplicate username validation
Duplicate email validation
Login with valid credentials
Login with invalid credentials
Redis session creation
Profile loading
Profile creation
Profile update
Profile persistence
Logout
Login after logout
Session validation
Responsive frontend
Cloud database connectivity
Production deployment
🗄️ Database Design
MySQL

The MySQL database contains the users table.

It stores:

id
username
email
password_hash
created_at
MongoDB

MongoDB contains the profiles collection.

It stores:

user_id
fullName
age
bio
interests
updated_at
Redis

Redis stores temporary authentication sessions.

Example:

session:<SHA-256(auth_token)>

The Redis session is used to verify whether the logged-in user is authenticated.

📊 Documentation

The docs/ folder contains the project's technical documentation and diagrams.

Included documentation:

Application flow diagram
Database schema documentation
MySQL ER diagram
MongoDB relationship diagram
Redis session flow
Overall system architecture
📌 Project Objective

The objective of this project is to develop a modular authentication system demonstrating:

Secure user authentication
Session management
Multiple database integration
PHP backend APIs
AJAX-based communication
Responsive frontend development
Cloud database integration
Docker-based deployment
Secure environment variable management
🔗 Important Links
Live Application

https://nura-authentication-system.onrender.com/login.html

GitHub Repository

https://github.com/VanishaarunV7/nura-authentication-system

👩‍💻 Developer

Vanisha Arun

B.E. Computer Science and Engineering

GitHub:

https://github.com/VanishaarunV7
