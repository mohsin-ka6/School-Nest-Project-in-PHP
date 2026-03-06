# AI School Management System

A comprehensive web-based school management system built with PHP, MySQL, and modern web technologies. This system provides complete management solutions for educational institutions with role-based access control for administrators, teachers, students, parents, and staff.

## 🎯 Features

### 🔐 Authentication & User Management
- **Secure Login System**: Role-based authentication (Admin, Teacher, Student, Parent, Staff)
- **User Profile Management**: Complete profile management with photo uploads
- **Password Management**: Secure password hashing, reset functionality
- **Session Management**: Automatic session timeout and security

### 👥 Student Management
- **Admission & Registration**: Complete student registration process
- **Student Information**: Personal, academic, medical, transport details
- **Student ID Generation**: Automatic unique ID generation
- **Student Portal**: Dedicated interface for students

### 👨‍🏫 Teacher & Staff Management
- **Teacher Profiles**: Complete teacher information management
- **Staff Directory**: Comprehensive staff database
- **Class Assignments**: Assign teachers to classes and subjects
- **Teacher Portal**: Dedicated interface for teachers

### 📚 Class & Section Management
- **Class Creation**: Create and manage grade levels (Grade 1-12)
- **Section Management**: Manage sections (A, B, C, etc.)
- **Subject Allocation**: Assign subjects to classes and teachers
- **Timetable Management**: Create and manage class schedules

### ✅ Attendance Management
- **Daily Student Attendance**: Mark and track student attendance
- **Teacher/Staff Attendance**: Monitor staff attendance
- **Attendance Reports**: Generate attendance reports and analytics
- **Biometric Integration**: Ready for biometric/face recognition systems

### 📅 Timetable & Scheduling
- **Automatic Timetable Generation**: Smart timetable creation
- **Class Schedules**: Manage daily class schedules
- **Exam Scheduling**: Schedule and manage examinations
- **Room Allocation**: Assign rooms to classes and exams

### 💰 Fee & Accounts Management
- **Fee Structure**: Define and manage fee structures
- **Fee Collection**: Track fee payments and dues
- **Invoices & Receipts**: Generate invoices and payment receipts
- **Expense Management**: Track school expenses
- **Financial Reports**: Generate financial reports and analytics

### 📝 Examination & Result Management
- **Exam Scheduling**: Create and manage exam schedules
- **Marks Entry**: Enter and manage student marks
- **Grading System**: Automated grading and report card generation
- **Report Cards**: Generate comprehensive report cards
- **Transcripts**: Create student transcripts

### 📖 Library Management
- **Books Database**: Complete book catalog management
- **Issue/Return Tracking**: Track book issues and returns
- **Fine Management**: Calculate and track overdue fines
- **Availability Status**: Real-time book availability

### 🚌 Transport Management
- **Bus Routes**: Manage transport routes and stops
- **Driver Allocation**: Assign drivers to routes
- **Student Transport**: Assign students to transport routes
- **Transport Fees**: Manage transport fee collection

### 📢 Communication & Notifications
- **Email/SMS Alerts**: Send notifications to parents and students
- **Notice Board**: Post and manage announcements
- **Chat System**: Teacher-parent communication (optional)
- **Notification Center**: Centralized notification management

### 👨‍👩‍👧‍👦 Parent Portal
- **Student Progress Reports**: View child's academic progress
- **Attendance Overview**: Monitor child's attendance
- **Fee Management**: View and pay fees online
- **Communication**: Communicate with teachers

### 🎓 Student Portal
- **Timetable View**: Access personal class schedule
- **Exam Results**: View examination results
- **Assignments**: Access and submit assignments
- **Notices**: View school announcements

### 📊 Admin Dashboard
- **System Overview**: Complete system statistics
- **Quick Actions**: Fast access to common tasks
- **Reports & Analytics**: Comprehensive reporting system
- **System Management**: Complete system administration

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.0
- **Charts**: Chart.js (optional)
- **Security**: PDO, Prepared Statements, Password Hashing

## 📁 Project Structure

```
ai_school/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   ├── images/
│   └── uploads/
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── modules/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── students.php
│   │   ├── teachers.php
│   │   ├── classes.php
│   │   ├── fees.php
│   │   └── reports.php
│   ├── teacher/
│   │   ├── dashboard.php
│   │   ├── attendance.php
│   │   ├── results.php
│   │   └── timetable.php
│   ├── student/
│   │   ├── dashboard.php
│   │   ├── timetable.php
│   │   ├── results.php
│   │   └── fees.php
│   ├── parent/
│   │   ├── dashboard.php
│   │   ├── children.php
│   │   ├── attendance.php
│   │   └── fees.php
│   └── staff/
│       ├── dashboard.php
│       └── library.php
├── api/
│   ├── attendance.php
│   ├── fees.php
│   ├── library.php
│   └── transport.php
├── database/
│   └── school_management.sql
├── index.php
├── login.php
├── logout.php
└── README.md
```

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for additional packages)

### Step 1: Clone or Download
```bash
git clone https://github.com/yourusername/ai_school.git
cd ai_school
```

### Step 2: Database Setup
1. Create a MySQL database:
```sql
CREATE DATABASE myschool;
```

2. Import the database schema:
```bash
mysql -u root -p school_management < database/school_management.sql
```

### Step 3: Configuration
1. Edit `config/database.php`:
```php
private $host = "localhost";
private $db_name = "school_management";
private $username = "your_username";
private $password = "your_password";
```

2. Edit `config/config.php`:
```php
define('SITE_URL', 'http://localhost/ai_school');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### Step 4: File Permissions
```bash
chmod 755 assets/uploads/
chmod 644 config/database.php
chmod 644 config/config.php
```

### Step 5: Web Server Configuration
Ensure your web server points to the project directory and has proper permissions.

## 🔑 Default Login Credentials

After installation, you can use these demo credentials:

- **Admin**: `admin` / `password`
- **Teacher**: `teacher` / `password`
- **Student**: `student` / `password`
- **Parent**: `parent` / `password`
- **Staff**: `staff` / `password`

## 📖 Usage Guide

### For Administrators
1. **Dashboard**: View system overview and statistics
2. **Student Management**: Add, edit, and manage student records
3. **Teacher Management**: Manage teacher profiles and assignments
4. **Class Management**: Create and manage classes and sections
5. **Fee Management**: Set up fee structures and track payments
6. **Reports**: Generate comprehensive reports

### For Teachers
1. **Attendance**: Mark daily student attendance
2. **Results**: Enter and manage student marks
3. **Timetable**: View and manage class schedules
4. **Communications**: Send messages to parents

### For Students
1. **Timetable**: View personal class schedule
2. **Results**: Check examination results
3. **Fees**: View fee details and payments
4. **Assignments**: Access and submit assignments

### For Parents
1. **Children**: View children's information
2. **Progress**: Monitor academic progress
3. **Attendance**: Check attendance records
4. **Fees**: View and pay fees online

## 🔧 Customization

### Adding New Modules
1. Create new files in appropriate module directory
2. Add navigation links in `includes/header.php`
3. Update database schema if needed
4. Add permissions in database

### Styling
- Modify `assets/css/style.css` for custom styling
- Update Bootstrap classes for layout changes
- Add custom JavaScript in `assets/js/script.js`

### Database
- Add new tables in `database/school_management.sql`
- Update functions in `includes/functions.php`
- Modify queries in respective modules

## 🔒 Security Features

- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Token-based protection
- **Password Security**: Bcrypt hashing
- **Session Security**: Secure session management
- **File Upload Security**: Type and size validation

## 📊 Reporting Features

- **Student Reports**: Academic performance, attendance
- **Financial Reports**: Fee collection, expenses
- **Attendance Reports**: Daily, monthly, yearly
- **Exam Reports**: Results analysis, grade distribution
- **Export Options**: PDF, Excel, CSV formats

## 🔄 API Endpoints

The system includes RESTful API endpoints for:
- Attendance management
- Fee processing
- Library operations
- Transport management
- Notifications

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running

2. **File Upload Issues**
   - Check file permissions on `assets/uploads/`
   - Verify PHP upload settings

3. **Session Issues**
   - Check PHP session configuration
   - Verify session storage permissions

4. **Email Not Working**
   - Configure SMTP settings in `config/config.php`
   - Check server email configuration

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Email: support@aischool.com
- Documentation: [Wiki](https://github.com/yourusername/ai_school/wiki)

## 🔄 Updates

### Version 1.0.0
- Initial release with core features
- Complete user management system
- Basic reporting functionality

### Planned Features
- Mobile app integration
- Advanced analytics dashboard
- Multi-language support
- Advanced reporting tools
- Integration with external systems

---

**Note**: This is a comprehensive school management system designed for educational institutions. Please ensure compliance with local data protection regulations when implementing in production environments.
