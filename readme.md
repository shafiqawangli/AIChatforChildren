# Starter Web Application

A comprehensive PHP-based web application featuring user authentication, role-based access control, and AI-powered chat functionality.

## Features

- **User Authentication System**: Complete sign-up, sign-in, email verification, and password recovery
- **Role-Based Access Control**: Support for three user roles (child, parent, admin)
- **AI Chat Integration**: LLM-powered conversational interface
- **Admin Management**: User and system administration capabilities
- **Database Migrations**: Structured database schema management

## System Requirements

- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Composer**: Latest version
- **Web Server**: PHP built-in server or Apache/Nginx

## Installation Guide

### Step 1: Database Configuration

#### 1.1 Create MySQL Database

First, Run mysql: sudo systemctl start mysql

And create a new database in MySQL:
```sql
CREATE DATABASE starter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 1.2 Configure Database Connection

Edit the `.env` file in the project root directory and configure your database settings:

```env
# Database Settings
DB_HOST=localhost
DB_PORT=3306
DB_NAME=starter
DB_USERNAME=root
DB_PASS=your_database_password
```

**Configuration Parameters:**
- `DB_HOST`: Database server address (default: localhost)
- `DB_PORT`: MySQL port (default: 3306)
- `DB_NAME`: Database name (default: starter)
- `DB_USERNAME`: MySQL username
- `DB_PASS`: MySQL password (leave empty if no password is set)

### Step 2: AI Chat Configuration

The application includes an AI-powered chat feature that uses a Large Language Model (LLM) API. Configure the API settings in your `.env` file:

```env
# LLM API Settings
LLM_API_KEY="your-api-key-here"
LLM_API_URL="https://api.deepseek.com/v1/chat/completions"
```

**Configuration Parameters:**
- `LLM_API_KEY`: Your API key for the LLM service
- `LLM_API_URL`: The endpoint URL for the LLM API

**Note**: The current configuration uses DeepSeek API. You can replace these values with any OpenAI-compatible API endpoint (e.g., OpenAI, Azure OpenAI, or other LLM providers).

### Step 3: Run Database Migrations

Execute the database migrations to create all necessary tables:

```bash
php database/migrate.php
```

This command will create the following database structure:
- **users** table: Stores user accounts with authentication and verification data
- **conversations** table: Stores chat conversation metadata for each user
- **messages** table: Stores individual messages within conversations

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('child', 'parent', 'admin') DEFAULT 'child' NOT NULL,
    verification_code INT NULL,
    verification_status ENUM('pending', 'verified') DEFAULT 'pending',
    verification_requested_at TIMESTAMP NULL,
    request_attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Conversations Table
```sql
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'New Chat',
    auto_renamed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Messages Table
```sql
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Optional - Rollback Migrations:**

If you need to rollback (drop) all tables, run:

```bash
php database/migrate.php --down
```

## Running the Application

### Option 1: Using Apache with .htaccess (Production Ready)

This is the recommended method for production deployment. The application uses `.htaccess` for URL rewriting and routing.

#### Prerequisites

1. **Apache Web Server** with `mod_rewrite` enabled
2. Project deployed to web server directory (e.g., `/var/www/html/`)

#### Step-by-Step Deployment Guide

##### 1. Install Dependencies

```bash
cd /var/www/html/AIChatforChildren
composer install --no-dev --optimize-autoloader
```

##### 2. Configure Base URL

The application uses `.htaccess` for URL rewriting. You must configure the `base_url` correctly based on your deployment path.

**Edit `config/config.php`:**

```php
<?php

return [
    /**
     * Application Base URL
     * - For root directory: '/'
     * - For subdirectory: '/subdirectory/'
     */
    'base_url' => '/AIChatforChildren/',  // Modify according to your deployment path

    'auth' => [
        'require_verification' => false,  // Email verification toggle
    ]
];
```

**Configuration Examples:**

| Deployment Location | base_url Setting | Access URL |
|---------------------|------------------|------------|
| Root directory | `'/'` | `http://yourdomain.com/` |
| Subdirectory | `'/myapp/'` | `http://yourdomain.com/myapp/` |
| This project | `'/AIChatforChildren/'` | `http://localhost/AIChatforChildren/` |

##### 3. Verify .htaccess Configuration

Ensure the `RewriteBase` in `.htaccess` matches your `base_url`:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On

# Set rewrite base path (must match base_url in config.php)
RewriteBase /AIChatforChildren/

# Allow direct access to static files
RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|svg|ico|woff|woff2|ttf|eot)$ [NC]
RewriteRule ^ - [L]

# Don't rewrite if file or directory exists
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Redirect all requests to index.php
RewriteRule ^(.*)$ index.php [QSA,L]

</IfModule>
```

**Important:** `RewriteBase` and `base_url` must be identical!

##### 4. Enable Apache mod_rewrite

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2

# CentOS/RHEL
# mod_rewrite is usually enabled by default
# Verify in httpd.conf: LoadModule rewrite_module modules/mod_rewrite.so
sudo systemctl restart httpd
```

##### 5. Configure Apache Virtual Host (Recommended)

Edit your Apache configuration file (e.g., `/etc/apache2/sites-available/000-default.conf`):

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html

    # Allow .htaccess overrides
    <Directory /var/www/html/AIChatforChildren>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

**Note:** `AllowOverride All` is required for `.htaccess` to work!

##### 6. Set File Permissions

```bash
# Set proper permissions
chmod -R 755 /var/www/html/AIChatforChildren
chown -R www-data:www-data /var/www/html/AIChatforChildren

# Secure .env file
chmod 600 /var/www/html/AIChatforChildren/.env
```

##### 7. Start Apache Services

```bash
# Start Apache
sudo systemctl start apache2

# Start MySQL
sudo systemctl start mysql

# Enable auto-start on boot
sudo systemctl enable apache2
sudo systemctl enable mysql
```

##### 8. Create Administrator Account

First-time setup requires creating an admin account. Choose one method:

**Method A: Direct Database Insert**

```sql
INSERT INTO users (name, email, password, role, verification_status, created_at)
VALUES (
    'Admin',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin',
    'verified',
    NOW()
);
```

**Method B: Register Then Modify Role**

1. Register a new account via the sign-up page
2. Update the role in database:

```sql
UPDATE users SET role = 'admin', verification_status = 'verified' WHERE email = 'your@email.com';
```

##### 9. Access the Application

Open your browser and navigate to:

```
http://localhost/AIChatforChildren/
```

The system will automatically redirect to the sign-in page:

```
http://localhost/AIChatforChildren/sign-in
```

**Default Login (if using Method A):**
- Email: `admin@example.com`
- Password: `password`

**⚠️ Important:** Change the password immediately after first login!

#### How .htaccess Routing Works

The `.htaccess` file enables clean URLs by rewriting all requests through `index.php`:

```apache
RewriteEngine On                    # Enable URL rewriting
RewriteBase /AIChatforChildren/     # Set base path

# 1. Static files bypass routing
RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|svg|ico|woff|woff2|ttf|eot)$ [NC]
RewriteRule ^ - [L]

# 2. Existing files/directories accessed directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# 3. All other requests forwarded to index.php
RewriteRule ^(.*)$ index.php [QSA,L]
```

**URL Routing Examples:**

| User Visits | Processing Flow |
|------------|-----------------|
| `/AIChatforChildren/` | → `index.php` → Router → Redirect to `/sign-in` |
| `/AIChatforChildren/sign-in` | → `index.php` → Router → `pages/auth/signin.php` |
| `/AIChatforChildren/admin/users` | → `index.php` → Router → Middleware → `pages/admin/users.php` |
| `/AIChatforChildren/assets/css/admin.css` | → Direct file access, bypasses PHP |

#### Troubleshooting

**Problem: All pages show 404**

**Solution:** Enable Apache `mod_rewrite` module

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Problem: .htaccess not working**

**Solution:** Ensure `AllowOverride All` is set in Apache configuration

```apache
<Directory /var/www/html>
    AllowOverride All
</Directory>
```

**Problem: CSS/JS files not loading**

**Solution:** Verify `base_url` in `config/config.php` matches your deployment path

**Problem: After changing deployment path, links break**

**Solution:** Update both locations:
1. `.htaccess` → `RewriteBase`
2. `config/config.php` → `base_url`

They must be identical!

---

### Option 2: Using PHP Built-in Server (Development Only)

For quick development testing, you can use PHP's built-in server:

#### Main Application

```bash
php -S localhost:8080 -t .
```

Then open your browser and navigate to:
```
http://localhost:8080
```

The application will be accessible with the following features:
- User registration and login
- Email verification system
- Password recovery
- Role-based dashboards
- AI chat interface

#### Admin Management Interface

To access the administrator account management interface:

```bash
php -S localhost:8080 admin_management.php
```

Then open your browser and navigate to:
```
http://localhost:8080
```

**Admin Management Features:**
- Create new administrator accounts
- Edit existing administrator profiles
- Delete administrator accounts
- Manage user roles and permissions

**⚠️ Note:** PHP built-in server is suitable for development only. Use Apache + .htaccess for production.

## Project Structure

```
starter/
├── app/
│   ├── controllers/     # Application controllers
│   └── models/          # Data models
├── config/              # Configuration files
├── core/                # Core framework classes
├── database/
│   ├── migrations/      # Database migration files
│   └── migrate.php      # Migration runner
├── pages/               # View templates
│   ├── admin/           # Admin panel pages
│   ├── auth/            # Authentication pages
│   ├── child/           # Child role pages
│   ├── parent/          # Parent role pages
│   └── home.php         # Base entry pages        
├── utils/               # Utility functions
├── vendor/              # Composer dependencies
├── .env                 # Environment configuration
├── index.php            # Main application entry point
├── admin_management.php # Admin management entry point
└── composer.json        # Composer configuration
```



