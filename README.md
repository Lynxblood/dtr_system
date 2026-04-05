# Time & Attendance System (DMS)

A modern, enterprise-grade web application for managing employee attendance records. Built with PHP, Tailwind CSS, and Flowbite components.

## 📋 Features

- **User Authentication**: Secure login with role-based access control
- **Three User Roles**: 
  - **Admin**: Full system access, manage all data
  - **HR**: Import records, manage employee data, view reports
  - **Employee**: View personal attendance records, download reports
  
- **Attendance Management**:
  - Import attendance records from biometric terminals (Tab-separated .TXT files)
  - View real-time attendance logs with filtering and search
  - Export monthly attendance reports to CSV

- **Modern UI/UX**:
  - Responsive design using Tailwind CSS
  - Flowbite components (modals, tables, forms, alerts)
  - Dark mode support
  - Intuitive navigation and dashboard

- **Security Features**:
  - Password hashing with bcrypt
  - Forced password change on first login
  - Session management and timeout
  - Input validation and sanitization
  - SQL injection protection with prepared statements

## 🏗️ Project Structure

```
DMS/
├── public/
│   ├── index.php                 # Admin/HR Dashboard
│   ├── attendance.php             # Employee Portal
│   ├── login.php                  # Login Page
│   ├── logout.php                 # Logout Handler
│   ├── change-password.php        # Password Change Page
│   ├── export.php                 # CSV Export Handler
│   ├── import-api.php             # Import API Endpoint
│   └── assets/
│       ├── css/
│       │   └── main.css           # Custom Styles
│       └── js/
│           └── main.js            # JavaScript Utilities
│
├── src/
│   ├── config/
│   │   └── config.php             # Application Configuration
│   ├── Core/
│   │   ├── Database.php           # Database Connection (Singleton)
│   │   └── Auth.php               # Authentication & Authorization
│   ├── Components/
│   │   ├── Header.php             # Reusable Header
│   │   ├── Card.php               # Reusable Card Component
│   │   ├── AlertBox.php           # Alert Messages
│   │   ├── SelectInput.php        # Select Input Component
│   │   └── Table.php              # Data Table Component
│   ├── Handlers/
│   │   ├── ImportHandler.php      # File Import Logic
│   │   └── ExportHandler.php      # CSV Export Logic
│   └── Views/
│       ├── admin/
│       └── employee/
│
├── sql/
│   └── schema.sql                 # Database Schema
│
└── README.md                       # This File
```

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependency management)

### Setup Steps

1. **Clone/Extract the Project**
   ```bash
   cd /path/to/xampp/htdocs/
   # Project should be in DMS/ folder
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE dms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Schema**
   ```bash
   mysql -u root -p dms < sql/schema.sql
   ```

4. **Configure Database Connection**
   Edit `src/config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'dms');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

5. **Set File Permissions**
   ```bash
   chmod 755 /path/to/DMS/
   chmod 644 /path/to/DMS/public/*.php
   ```

6. **Access the Application**
   ```
   http://localhost/DMS/public/login.php
   ```

## 🔐 Default Credentials

**First Admin User:**
- Username: Provided by system administrator
- Password: `ilove@BASC1` (Must be changed on first login)

**For Testing (Create Manually):**
```php
// In your database, use PHP to hash password:
<?php
$password = password_hash('ilove@BASC1', PASSWORD_BCRYPT);
// Insert into users table with hashed password
?>
```

## 📊 Usage Guide

### Admin/HR Dashboard
1. Login with admin or HR credentials
2. View all attendance records in the data table
3. **Import Records**: Use the import card to upload .TXT files from biometric terminal
4. **Export Report**: Select employee and month to download CSV
5. Filter and search through all records

### Employee Portal
1. Login with employee credentials
2. Mandatory password change on first login
3. View personal attendance records for selected month
4. Download monthly attendance as CSV
5. Filter records by month

### Import File Format

The system expects a tab-separated (.TXT) file with the following columns:

```
[Index]    [Terminal]    [ID]    [Name]    [IN/OUT]    [Mode]    [DateTime]
1          1001          101     John Doe  0           1         2024-01-01 09:00:00
```

**Column Details:**
- **Index**: Sequential number
- **Terminal**: Terminal machine ID
- **ID**: Employee number (en_no)
- **Name**: Employee Full Name
- **IN/OUT**: 0 for Duty On, 1+ for Duty Off
- **Mode**: Entry mode (1=Card, 2=Face, etc.)
- **DateTime**: Date and time in YYYY-MM-DD HH:MM:SS format

## 🔧 Configuration

### Key Settings (src/config/config.php)

```php
define('DB_HOST', 'localhost');           // Database host
define('DB_NAME', 'dms');                 // Database name
define('DB_USER', 'root');                // Database user
define('DB_PASS', '');                    // Database password

define('DEFAULT_PASSWORD', 'ilove@BASC1'); // Default new user password
define('MIN_PASSWORD_LENGTH', 8);         // Minimum password length
define('SESSION_TIMEOUT', 3600);          // Session timeout (1 hour)
define('MAX_UPLOAD_SIZE', 10485760);      // Max upload size (10MB)
```

### Roles & Permissions

| Action | Admin | HR | Employee |
|--------|-------|----|----|
| View All Records | ✅ | ✅ | ❌ |
| Import Records | ✅ | ✅ | ❌ |
| Export All Reports | ✅ | ✅ | ❌ |
| View Own Records | ✅ | ✅ | ✅ |
| Export Own Report | ✅ | ✅ | ✅ |
| Change Password | ✅ | ✅ | ✅ |

## 💡 Component Usage

### Using AlertBox Component
```php
<?php
use App\Components\AlertBox;

AlertBox::success('Operation completed successfully!');
AlertBox::error('An error occurred while processing.');
AlertBox::warning('Please review your input.');
AlertBox::info('Additional information message.');
AlertBox::container('alertId'); // For AJAX responses
?>
```

### Using Card Component
```php
<?php
use App\Components\Card;

// Method 1: Open/Close
Card::open('Title', 'Description');
// ... content ...
Card::close();

// Method 2: Complete
Card::render('Title', '<p>Content here</p>', 'Description');
?>
```

### Using SelectInput Component
```php
<?php
use App\Components\SelectInput;

$options = [
    'key1' => 'Option 1',
    'key2' => 'Option 2',
];

SelectInput::render('selectName', 'Label', $options, 'key1', true);
?>
```

### Using Table Component
```php
<?php
use App\Components\Table;

$headers = ['ID', 'Name', 'Email'];
$rows = [
    ['001', 'John Doe', 'john@example.com'],
    ['002', 'Jane Smith', 'jane@example.com'],
];

Table::render('tableId', $headers, $rows, 0, 'asc');
?>
```

## 🔒 Security Best Practices

1. **Always Use Prepared Statements**: Prevents SQL injection
2. **Hash Passwords**: Uses bcrypt hashing
3. **Input Validation**: All user input is validated
4. **Output Encoding**: HTML special characters are escaped
5. **Session Security**: Sessions are properly managed
6. **HTTPS Recommended**: Use HTTPS in production
7. **Regular Backups**: Backup database regularly
8. **Update Dependencies**: Keep frameworks and libraries updated

## 🐛 Troubleshooting

### Database Connection Error
- Check config.php settings
- Verify MySQL is running
- Confirm database exists
- Check user permissions

### Import File Not Processing
- Ensure file is in correct .TXT format
- Verify tab-separated columns
- Check datetime format (YYYY-MM-DD HH:MM:SS)
- Check max upload size in config.php

### Password Change Loop
- Clear session: Log out and back in
- Check `requires_password_change` field in users table
- Verify password meets requirements (8+ chars)

### Table Not Displaying Correctly
- Ensure jQuery is loaded before DataTables
- Check browser console for JavaScript errors
- Verify Tailwind CSS is loaded

## 📝 API Endpoints

### Import Handler
**Endpoint**: `POST /public/import-api.php`

**Parameters**: 
- `file` (multipart/form-data) - .TXT file from biometric terminal

**Response**:
```json
{
  "status": "success",
  "message": "Import complete. Added 150 logs and created accounts for 5 new employees."
}
```

### Export Handler
**Endpoint**: `GET /public/export.php`

**Parameters**:
- `en_no` (required) - Employee number
- `month` (required) - Month in YYYY-MM format

**Response**: CSV file download

## 📈 Performance Optimization

- Database queries use indexes on frequently searched columns
- Records limited to 1000 for initial load
- Prepared statements prevent query compilation overhead
- CSS and JS are minified in production

## 🔄 Maintenance

### Regular Tasks
- **Weekly**: Monitor error logs
- **Monthly**: Backup database
- **Quarterly**: Review security permissions
- **Annually**: Update dependencies

### Database Cleanup
```sql
-- Archive old records (older than 1 year)
DELETE FROM attendance_logs 
WHERE record_datetime < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Optimize tables
OPTIMIZE TABLE attendance_logs, employees, users;
```

## 🚢 Deployment

### Production Checklist
- [ ] Update database credentials in config.php
- [ ] Set appropriate file permissions (755 for dirs, 644 for files)
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall rules
- [ ] Set up log rotation
- [ ] Configure automated backups
- [ ] Set PHP error reporting to production mode
- [ ] Clear temporary files

## 📞 Support & Contribution

For issues, feature requests, or contributions, please:
1. Create detailed issue reports
2. Include error messages and logs
3. Specify your environment (PHP version, MySQL version, etc.)
4. Test in development before production deployment

## 📄 License

This project is proprietary and should only be used by authorized organizations.

## 🎯 Version History

**v2.0.0** (Current) - Enterprise Refactor
- Component-based architecture
- Improved security
- Flowbite UI components
- Better code organization

**v1.0.0** - Initial Release
- Basic attendance tracking
- User management

---

**Last Updated**: January 2024
**Documentation**: For more help, contact your system administrator
