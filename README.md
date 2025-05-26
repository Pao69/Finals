# 📋 Task Management System

> A modern task management application built with Ionic, Vue.js, and PHP

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Ionic](https://img.shields.io/badge/Ionic-7.5.0-blue.svg)
![Vue](https://img.shields.io/badge/Vue.js-3.3.0-4FC08D.svg)
![PHP](https://img.shields.io/badge/PHP-8.2.12-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-10.4.32-orange.svg)
![TypeScript](https://img.shields.io/badge/TypeScript-5.0.2-blue.svg)

## ✨ Features

📱 **Modern UI/UX**
- Responsive design with Ionic components
- Intuitive interface with Vue.js Composition API
- Dark/Light mode support
- Mobile-first approach
- Smooth animations and transitions

🔐 **Security**
- JWT-based authentication & authorization
- Bcrypt password hashing
- Secure session management
- Login attempt monitoring
- CORS protection
- XSS prevention

📝 **Task Management**
- Create, edit, and delete tasks
- Priority levels (Low, Medium, High)
- Due date tracking with notifications
- Task completion status
- Task categorization
- Bulk actions support

📁 **Resource Management**
- Drag-and-drop file uploads
- Multiple file types support
- Resource description and tagging
- Secure file handling
- File preview capability
- Storage quota management

👤 **User Features**
- Profile management with avatars
- Password reset via email
- Profile picture upload
- Role-based access control
- Activity logging
- Notification preferences

## 🚀 Quick Start

### System Requirements

- **Node.js**: v16.0.0 or higher
- **PHP**: v8.2.12 or higher
- **MySQL**: v10.4.32 or higher
- **Storage**: Minimum 1GB free space
- **Memory**: 2GB RAM minimum
- **XAMPP**: v8.2.12 or higher

### Prerequisites

Before installation, ensure you have:
1. [Node.js](https://nodejs.org/) (LTS version)
2. [XAMPP](https://www.apachefriends.org/) (for PHP & MySQL)
3. npm or yarn package manager
4. Git for version control

### Installation

1. **Clone & Setup**
```bash
# Clone the repository
git clone [repository-url]

# Navigate to project directory
cd [project-directory]

# Install dependencies
npm install

# Create environment file
cp .env.example .env
```

2. **Configure Environment**
```env
# .env file
VITE_API_URL=http://localhost/codes/PROJ/Finals/dbConnect
VITE_APP_NAME="Task Management System"
VITE_FILE_UPLOAD_MAX=5242880
VITE_ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx,xls,xlsx
```

3. **Database Setup**
- Start XAMPP (Apache and MySQL)
- Open phpMyAdmin
- Create new database
- Import SQL schema:

```sql
-- Create database
CREATE DATABASE task_app;

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp(),
  `pfp` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tasks table
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `priority` enum('low','medium','high') DEFAULT 'medium',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_completed` (`completed`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Resources table
CREATE TABLE `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `upload_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Security tables
CREATE TABLE `password_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_attempts` (`user_id`,`attempt_time`),
  CONSTRAINT `password_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expiry` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_code` (`email`,`code`),
  KEY `idx_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

4. **Start Development Server**
```bash
# Development mode
npm run dev

# Production build
npm run build
npm run preview
```

## 📱 Usage Guide

### 👤 User Management

| Feature | Description | Access Level |
|---------|-------------|--------------|
| Registration | Create account with email verification | Public |
| Login | Secure authentication with 2FA option | Public |
| Profile | Update personal info and preferences | User |
| Password Reset | Email-based recovery system | Public |

### ✅ Task Operations

| Action | Steps | Notes |
|--------|--------|-------|
| Create Task | 1. Click "+" button<br>2. Fill details<br>3. Set priority & due date<br>4. Save | Supports attachments |
| View Tasks | 1. Access dashboard<br>2. Use filters/search<br>3. Sort by preference | Multiple view modes |
| Update Task | 1. Select task<br>2. Edit details<br>3. Save changes | Real-time updates |
| Delete Task | 1. Select task<br>2. Click delete<br>3. Confirm action | Soft delete available |

### 📁 Resource Management

| Action | Steps | Supported Types |
|--------|--------|----------------|
| Upload | 1. Open task<br>2. Drag & drop files<br>3. Add description | Images, Documents, Spreadsheets |
| View | 1. Access task<br>2. Open resources tab | Built-in preview |
| Download | 1. Select resource<br>2. Click download | Batch download available |

### ⚙️ Admin Features

| Feature | Description | Access Path |
|---------|-------------|-------------|
| User Management | Manage accounts, roles, permissions | /admin/users |
| System Monitoring | View logs, metrics, performance data | /admin/monitor |
| Global Settings | Configure system parameters | /admin/settings |
| Resource Control | Manage storage, quotas, limitations | /admin/resources |

### ⚙️ Admin Usage Guide
| Action | Steps |
|User Management |	View, add, edit, or delete user accounts and manage user roles |
|System Monitoring |	Access system statistics, user activity logs, and resource usage tracking |
|Global Settings |	Control overarching application settings |

### 🔧 Troubleshooting

| Issue | Solution | Prevention |
|-------|----------|------------|
| Database Connection | 1. Verify XAMPP services<br>2. Check credentials<br>3. Test connection | Regular maintenance |
| File Upload Fails | 1. Check file size<br>2. Verify permissions<br>3. Clear temp files | Set proper limits |
| Login Issues | 1. Clear cache<br>2. Reset session<br>3. Check credentials | Use password manager |
| Performance | 1. Clear browser cache<br>2. Update dependencies<br>3. Check network | Regular updates |

## 🛠️ Development

### Tech Stack
- **Frontend**: Vue.js 3.3, Ionic 7, TypeScript
- **Backend**: PHP 8.2, MySQL 10.4
- **Tools**: Vite, ESLint, Prettier
- **Testing**: Vitest, PHPUnit

### Architecture
```
project/
├── src/                  # Frontend source
│   ├── components/       # Vue components
│   ├── views/           # Page components
│   ├── composables/     # Vue composables
│   └── services/        # API services
├── dbConnect/           # Backend PHP files
├── public/              # Static assets
└── tests/              # Test files
```

## 🤝 Support & Contribution

- Report issues via GitHub Issues
- Join discussions in GitHub Discussions
- Submit PRs for improvements
- Documentation updates welcome

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

---
Made with ❤️ using Ionic, Vue.js, and PHP
