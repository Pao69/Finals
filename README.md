# 📋 Task Management System

> A modern task management application built with Ionic, React, and PHP

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Ionic](https://img.shields.io/badge/Ionic-latest-blue.svg)
![React](https://img.shields.io/badge/React-latest-61dafb.svg)
![PHP](https://img.shields.io/badge/PHP-8.2.12-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-10.4.32-orange.svg)

## ✨ Features

📱 **Modern UI/UX**
- Responsive design
- Intuitive interface
- Dark mode support

🔐 **Security**
- User authentication & authorization
- Password hashing
- Session management
- Login attempt monitoring

📝 **Task Management**
- Create, edit, and delete tasks
- Priority levels
- Due date tracking
- Task completion status

📁 **Resource Management**
- File uploads
- Multiple file types support
- Resource description
- Secure file handling

👤 **User Features**
- Profile management
- Password reset
- Profile picture upload
- Role-based access control

## 🚀 Quick Start

### Prerequisites

Before you begin, ensure you have the following installed:
- [Node.js](https://nodejs.org/) (Latest LTS version)
- [XAMPP](https://www.apachefriends.org/) (for PHP & MySQL)
- npm or yarn package manager

### Installation

1. **Clone & Install**
```bash
# Clone the repository
git clone [repository-url]

# Navigate to project directory
cd [project-directory]

# Install dependencies
npm install
```

2. **Database Setup**
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

3. **Start Development Server**
```bash
npm run dev
```

## 📱 Usage Guide

### 👤 User Management

| Feature | Description |
|---------|-------------|
| Registration | Create account with username, email, phone, password |
| Login | Secure authentication with email/password |
| Profile | Update personal information and profile picture |
| Password Reset | Self-service password recovery via email |

### ✅ Task Operations

| Action | Steps |
|--------|--------|
| Create Task | Click "Add Task" → Fill details → Save |
| View Tasks | Dashboard displays all tasks with filters |
| Update Task | Select task → Modify → Save Changes |
| Delete Task | Select task → Delete → Confirm |

### 📁 Resource Management

| Action | Steps |
|--------|--------|
| Upload | Navigate to task → Add Resource → Select file |
| View | Access from task details |
| Download | Click on resource to download |

## 🔧 Troubleshooting

| Issue | Solution |
|-------|----------|
| Database Connection | Check XAMPP services & credentials |
| File Upload Fails | Verify size limits & permissions |
| Login Issues | Clear cache or reset password |

## 🤝 Support

Need help? Found a bug? Please create an issue in the repository.

---
Made with ❤️ using Ionic, Vue and PHP 
