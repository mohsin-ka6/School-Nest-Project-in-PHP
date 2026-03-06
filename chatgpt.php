<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--secondary-color) 0%, #1a2530 100%);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            transition: all var(--transition-speed) ease;
            z-index: 1000;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-heading {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 40px;
            height: 40px;
            background-color: var(--accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .school-name {
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar.collapsed .school-name {
            display: none;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar.collapsed .toggle-btn {
            margin: 0 auto;
        }

        .sidebar-nav {
            padding: 15px 0;
        }

        .sidebar-item {
            margin-bottom: 5px;
        }

        .sidebar-link, .dropdown-toggle {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-link:hover, .dropdown-toggle:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--primary-color);
        }

        .sidebar-link.active, .dropdown-toggle.active {
            background-color: rgba(52, 152, 219, 0.2);
            color: white;
            border-left-color: var(--primary-color);
        }

        .sidebar-link i, .dropdown-toggle i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 18px;
        }

        .sidebar.collapsed .link-text {
            display: none;
        }

        .dropdown-toggle::after {
            margin-left: auto;
            transition: transform 0.2s;
        }

        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(90deg);
        }

        .sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }

        .sidebar-submenu {
            background-color: rgba(0, 0, 0, 0.2);
            padding-left: 20px;
        }

        .sidebar-submenu .sidebar-link {
            padding: 10px 20px;
            font-size: 14px;
            border-left: none;
            padding-left: 50px;
        }

        .sidebar-submenu .sidebar-link.active {
            background-color: rgba(52, 152, 219, 0.15);
            color: white;
        }

        .sidebar.collapsed .sidebar-submenu {
            display: none;
        }

        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
        }

        .logout-link {
            color: rgba(255, 255, 255, 0.7);
        }

        .logout-link:hover {
            color: #e74c3c;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left var(--transition-speed) ease;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        .header {
            background-color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .content-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--secondary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            .sidebar:not(.collapsed) {
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .main-content:not(.expanded) {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-heading">
            <div class="logo-container">
                <div class="logo">
                    <i class="fas fa-school"></i>
                </div>
                <div class="school-name">Bright Future Academy</div>
            </div>
            <button class="toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <ul class="list-unstyled sidebar-nav">
            <li class="sidebar-item">
                <a href="#" class="sidebar-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#frontoffice-submenu" role="button">
                    <i class="fas fa-desktop"></i>
                    <span class="link-text">Front Office</span>
                </a>
                <div class="collapse sidebar-submenu" id="frontoffice-submenu">
                    <a href="#" class="sidebar-link">Admission Query</a>
                    <a href="#" class="sidebar-link">Visitor Book</a>
                    <a href="#" class="sidebar-link">Phone Log</a>
                    <a href="#" class="sidebar-link">Complaints</a>
                </div>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#academics-submenu" role="button">
                    <i class="fas fa-graduation-cap"></i>
                    <span class="link-text">Academics</span>
                </a>
                <div class="collapse sidebar-submenu" id="academics-submenu">
                    <a href="#" class="sidebar-link">Classes & Sections</a>
                    <a href="#" class="sidebar-link">Subjects</a>
                    <a href="#" class="sidebar-link">Assign Subjects</a>
                    <a href="#" class="sidebar-link">Assign Teachers</a>
                    <a href="#" class="sidebar-link">Class Routine</a>
                </div>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#students-submenu" role="button">
                    <i class="fas fa-user-graduate"></i>
                    <span class="link-text">Students</span>
                </a>
                <div class="collapse sidebar-submenu" id="students-submenu">
                    <a href="#" class="sidebar-link">Add Student</a>
                    <a href="#" class="sidebar-link">Manage Students</a>
                    <a href="#" class="sidebar-link">Promote Students</a>
                </div>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#hr-submenu" role="button">
                    <i class="fas fa-sitemap"></i>
                    <span class="link-text">Human Resources</span>
                </a>
                <div class="collapse sidebar-submenu" id="hr-submenu">
                    <a href="#" class="sidebar-link">Staff Directory</a>
                    <a href="#" class="sidebar-link">ID Card Generator</a>
                    <a href="#" class="sidebar-link">Manage Parents</a>
                </div>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#fees-submenu" role="button">
                    <i class="fas fa-money-check-alt"></i>
                    <span class="link-text">Fees</span>
                </a>
                <div class="collapse sidebar-submenu" id="fees-submenu">
                    <a href="#" class="sidebar-link">Fee Groups</a>
                    <a href="#" class="sidebar-link">Fee Types</a>
                    <a href="#" class="sidebar-link">Concession Types</a>
                    <a href="#" class="sidebar-link">Assign Concessions</a>
                    <a href="#" class="sidebar-link">Fee Structure</a>
                    <a href="#" class="sidebar-link">Generate Invoice</a>
                    <a href="#" class="sidebar-link">Manage Invoices</a>
                    <a href="#" class="sidebar-link">Quick Collect</a>
                    <a href="#" class="sidebar-link">Payment History</a>
                </div>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#comm-submenu" role="button">
                    <i class="fas fa-bullhorn"></i>
                    <span class="link-text">Communication</span>
                </a>
                <div class="collapse sidebar-submenu" id="comm-submenu">
                    <a href="#" class="sidebar-link">News & Events</a>
                    <a href="#" class="sidebar-link">Invitation Maker</a>
                    <a href="#" class="sidebar-link">Invitation Templates</a>
                    <a href="#" class="sidebar-link">Send SMS</a>
                </div>
            </li>
            
            <li class="sidebar-item">
                <a href="#" class="sidebar-link">
                    <i class="fas fa-user-clock"></i>
                    <span class="link-text">Attendance</span>
                </a>
            </li>
            
            <li class="sidebar-item">
                <a class="dropdown-toggle" data-bs-toggle="collapse" href="#exams-submenu" role="button">
                    <i class="fas fa-book-open"></i>
                    <span class="link-text">Exams</span>
                </a>
                <div class="collapse sidebar-submenu" id="exams-submenu">
                    <a href="#" class="sidebar-link">Exam Types</a>
                    <a href="#" class="sidebar-link">Marks Grade</a>
                    <a href="#" class="sidebar-link">Exam Schedule</a>
                    <a href="#" class="sidebar-link">Marks Register</a>
                </div>
            </li>
            
            <li class="sidebar-item logout-section">
                <a href="#" class="sidebar-link logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h1 class="page-title">Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">JS</div>
                <span>John Smith</span>
            </div>
        </div>
        
        <div class="content-card">
            <h2 class="card-title">Welcome to School Management System</h2>
            <p>This is a professional sidebar navigation for a school management system. The sidebar includes all the menu items from your PHP code and is fully responsive.</p>
            <p>You can toggle the sidebar using the button in the top-left corner. On mobile devices, the sidebar will automatically collapse to save space.</p>
        </div>
        
        <div class="content-card">
            <h2 class="card-title">System Features</h2>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-graduation-cap text-primary me-2"></i>Student Management</h5>
                            <p class="card-text">Manage student records, admissions, and promotions efficiently.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chalkboard-teacher text-success me-2"></i>Academic Planning</h5>
                            <p class="card-text">Create class routines, assign subjects and manage academic schedules.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-money-bill-wave text-warning me-2"></i>Fee Management</h5>
                            <p class="card-text">Handle fee structures, invoices, and payment tracking with ease.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Change icon based on state
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('collapsed')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-bars');
                }
            });
            
            // Auto-collapse on mobile
            function handleResize() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                    sidebarToggle.querySelector('i').classList.remove('fa-bars');
                    sidebarToggle.querySelector('i').classList.add('fa-chevron-right');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    sidebarToggle.querySelector('i').classList.remove('fa-chevron-right');
                    sidebarToggle.querySelector('i').classList.add('fa-bars');
                }
            }
            
            // Initial check
            handleResize();
            
            // Listen for resize events
            window.addEventListener('resize', handleResize);
            
            // Set active menu items
            const menuItems = document.querySelectorAll('.sidebar-link');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Remove active class from all items
                    menuItems.forEach(i => i.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // If it's a dropdown item, also activate the parent
                    if (this.parentElement.parentElement.classList.contains('sidebar-submenu')) {
                        const parentToggle = this.closest('.sidebar-item').querySelector('.dropdown-toggle');
                        if (parentToggle) {
                            parentToggle.classList.add('active');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>