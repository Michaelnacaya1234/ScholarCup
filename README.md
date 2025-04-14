# Scholar Management System

A comprehensive system for managing scholarship students, staff, and administrators.

## Database Setup Instructions

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin in your browser
2. Click on "Import" in the top menu
3. Click "Choose File" and select the `database.sql` file
4. Click "Go" to import the database structure
5. The script will automatically create a database named `scholar_db`

### Option 2: Using MySQL Command Line
1. Open your terminal or command prompt
2. Run the following command:
   ```
   mysql -u username -p < database.sql
   ```
   Replace `username` with your MySQL username

### Option 3: Manual Steps
1. Create a database named `scholar_db`:
   ```sql
   CREATE DATABASE scholar_db;
   ```
2. Select the database:
   ```sql
   USE scholar_db;
   ```
3. Import the SQL file:
   ```
   mysql -u username -p scholar_db < database.sql
   ```

## Directory Setup

1. Run the directory setup script to create all necessary folders:
   - Navigate to http://your-domain.com/Scholar/setup_directories.php 
   - This will create all necessary upload directories with proper permissions

## Database Structure Maintenance

If you encounter any issues with missing columns:
1. Run http://your-domain.com/Scholar/run_database_check.php
2. This script will detect and add any missing database columns

## User Accounts

Default accounts for testing:
- Admin: admin@example.com / password
- Staff: staff@example.com / password
- Student: student@example.com / password

## System Requirements

- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)
- Modern web browser

## Features

- Student profile management
- Staff management
- Document upload and tracking
- Allowance schedule management
- Status tracking
- Academic performance tracking

## Troubleshooting

If you encounter any issues:

1. **Profile Image Upload Issues**
   - Make sure the setup_directories.php script has been run
   - Verify that your web server has write permissions to the uploads directory
   - Check PHP file upload settings in php.ini

2. **Database Connection Issues**
   - Verify database credentials in includes/config/database.php
   - Ensure MySQL server is running

3. **Page Loading Issues**
   - Check for PHP errors in your server logs
   - Verify that all required PHP extensions are enabled

## Support

For additional support, please contact system administrator.

## Project Structure

```
Scholar/
├── admin/                  # Admin dashboard and related pages
├── assets/                 # CSS, JavaScript, and image files
│   └── css/
│       └── style.css       # Main CSS file for the entire application
├── includes/               # Shared PHP classes and functions
│   ├── config/             # Configuration files
│   │   ├── database.php    # Database connection class
│   │   └── email.php       # Email service class
│   └── Auth.php            # Authentication class
├── staff/                  # Staff dashboard and related pages
├── student/                # Student dashboard and related pages
│   └── login_check.php     # Login verification and redirection
├── database.php            # Simple database connection file
├── database.sql            # SQL file for database setup
├── forgot_password.php     # Password recovery page
├── index.php               # Login page
├── logout.php              # Logout script
└── README.md               # Project documentation
```

## Technologies Used

- PHP (OOP approach)
- MySQL
- HTML/CSS
- JavaScript
- Font Awesome for icons

## Security Features

- Password hashing (MD5 for demonstration, use more secure methods in production)
- Session management
- Input validation and sanitization
- Protection against SQL injection

## Notes

- This is a demonstration project with simplified implementations
- For production use, implement more secure password hashing (e.g., password_hash)
- Configure proper email sending functionality in the EmailService class
- Add more comprehensive error handling and logging