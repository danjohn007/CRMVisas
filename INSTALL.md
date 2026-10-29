# 🚀 INSTALLATION GUIDE - CRM Visas y Pasaportes

## Quick Start (5 minutes)

### Step 1: Download the System
```bash
git clone https://github.com/danjohn007/CRMIntranet.git
cd CRMIntranet
```

### Step 2: Setup Database
1. Open phpMyAdmin or MySQL Workbench
2. Execute the file: `database/schema.sql`
3. This creates:
   - Database: `crm_visas`
   - All tables
   - Sample data

**Note:** If you're upgrading from an older version, you may need to run additional migrations. See the [Migration Guide](#database-migrations) below.

### Step 3: Configure Database Connection
Edit `config/config.php` lines 15-18:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'crm_visas');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 4: Set Permissions
```bash
chmod -R 755 public/uploads
```

### Step 5: Test Installation

#### Quick Database Test
Visit: `http://localhost/CRMIntranet/test_connection.php`

This will verify:
- ✅ Database connection
- ✅ MySQL version
- ✅ Database tables
- ✅ User count

#### Complete System Test
Visit: `http://localhost/CRMIntranet/test-conexion`

✅ All checks should pass

### Step 6: Login
- URL: `http://localhost/CRMIntranet/`
- User: **admin**
- Pass: **password123**

---

## Detailed Installation

### System Requirements

#### Minimum Requirements:
- **Web Server:** Apache 2.4+
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Disk Space:** 50 MB minimum
- **Memory:** 128 MB minimum

#### PHP Extensions Required:
- ✅ PDO
- ✅ pdo_mysql
- ✅ json
- ✅ mbstring
- ✅ openssl

#### Apache Modules Required:
- ✅ mod_rewrite (for friendly URLs)

### Installation on Different Platforms

#### 🪟 Windows (XAMPP)

1. **Download XAMPP:**
   - https://www.apachefriends.org/

2. **Extract project to:**
   ```
   C:\xampp\htdocs\CRMIntranet
   ```

3. **Start Apache and MySQL** from XAMPP Control Panel

4. **Access phpMyAdmin:**
   - http://localhost/phpmyadmin

5. **Import database:**
   - Click "Import"
   - Select `database/schema.sql`
   - Click "Go"

6. **Configure:**
   - Edit `config/config.php`
   - Usually default settings work (root, no password)

7. **Access system:**
   - http://localhost/CRMIntranet/

#### 🪟 Windows (WAMP)

1. **Download WAMP:**
   - https://www.wampserver.com/

2. **Extract project to:**
   ```
   C:\wamp64\www\CRMIntranet
   ```

3. Follow steps 3-7 from XAMPP instructions

#### 🐧 Linux (Ubuntu/Debian)

1. **Install LAMP Stack:**
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql php-mbstring php-json
   ```

2. **Enable mod_rewrite:**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **Clone project:**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/danjohn007/CRMIntranet.git
   cd CRMIntranet
   ```

4. **Set permissions:**
   ```bash
   sudo chown -R www-data:www-data .
   sudo chmod -R 755 public/uploads
   ```

5. **Configure Apache:**
   Edit `/etc/apache2/sites-available/000-default.conf`:
   ```apache
   <Directory /var/www/html/CRMIntranet>
       AllowOverride All
   </Directory>
   ```

6. **Restart Apache:**
   ```bash
   sudo systemctl restart apache2
   ```

7. **Import database:**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

8. **Access:**
   - http://localhost/CRMIntranet/

#### 🍎 macOS (MAMP)

1. **Download MAMP:**
   - https://www.mamp.info/

2. **Extract project to:**
   ```
   /Applications/MAMP/htdocs/CRMIntranet
   ```

3. **Start MAMP servers**

4. **Access phpMyAdmin:**
   - http://localhost:8888/phpMyAdmin/ (default MAMP port)

5. **Import database** (same as Windows)

6. **Configure:**
   - Edit `config/config.php`
   - MAMP usually uses root:root

7. **Access:**
   - http://localhost:8888/CRMIntranet/

---

## Post-Installation Configuration

### 1. Test System Health

Visit: `http://your-domain/test-conexion`

**Verify:**
- ✅ URL Base configured
- ✅ Database connection OK
- ✅ All tables exist
- ✅ Users registered
- ✅ Upload directory writable
- ✅ PHP extensions loaded

### 2. Change Default Passwords

**IMPORTANT:** Change default passwords immediately!

1. Login as admin
2. Go to: **Usuarios**
3. Edit each user
4. Change password

### 3. Configure Email (Optional)

1. Login as admin
2. Go to: **Configuración**
3. Set:
   - SMTP server
   - Email from
   - Email credentials

### 4. Customize Site

1. Go to: **Configuración**
2. Change:
   - Site name
   - Logo (upload)
   - Colors
   - Contact info

### 5. Configure Apache for Production

**Enable .htaccess:**

Edit Apache config (`httpd.conf` or site config):

```apache
<Directory "/path/to/CRMIntranet">
    AllowOverride All
    Require all granted
</Directory>
```

**Restart Apache:**
```bash
# Linux
sudo systemctl restart apache2

# Windows XAMPP
# Use XAMPP Control Panel

# macOS
sudo apachectl restart
```

---

## Security Configuration

### 1. File Permissions

```bash
# Linux/macOS
chmod -R 755 .
chmod -R 775 public/uploads
chmod 644 config/config.php
chmod 644 database/schema.sql
```

### 2. Hide Config Files (Production)

Add to `.htaccess`:
```apache
<FilesMatch "config\.php">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. Enable Error Logging

In `config/config.php`, change for production:
```php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users
ini_set('log_errors', 1);     // Log to file
```

### 4. Backup Database

**Daily backup recommended:**

```bash
# Linux/macOS
mysqldump -u root -p crm_visas > backup_$(date +%Y%m%d).sql

# Or use phpMyAdmin Export feature
```

---

## Database Migrations

### Audit Trail Table Migration

If you're upgrading from a previous version and need to add the audit trail feature, follow these steps:

#### Option 1: Automatic Migration (Recommended)

Visit the migration script in your browser:
```
http://your-domain/database/migrations/migrate_audit_trail.php
```

The script will automatically:
- Check if the table exists
- Create the `audit_trail` table
- Insert sample data
- Verify the installation

#### Option 2: Manual Migration (phpMyAdmin)

1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Execute the file: `database/migrations/create_audit_trail_table.sql`

#### Option 3: Command Line

```bash
cd /path/to/CRMIntranet
php database/migrations/migrate_audit_trail.php
```

**Important:** After running the migration, the Auditoría module will be accessible from the admin sidebar menu.

For more details, see: `database/migrations/README.md`

---

## Troubleshooting

### Problem: 404 Error on all pages

**Solution:**
1. Verify mod_rewrite is enabled
2. Check .htaccess files exist
3. Verify Apache AllowOverride is set to "All"

```bash
# Linux - Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Problem: Database connection error

**Solution:**
1. Verify MySQL is running
2. Check credentials in `config/config.php`
3. Test MySQL connection:

```bash
mysql -u root -p
SHOW DATABASES;
```

### Problem: audit_trail table doesn't exist

**Error Message:**
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'recursos_visas.audit_trail' doesn't exist
```

**Solution:**
1. Run the migration script (see [Database Migrations](#database-migrations) section above)
2. Or access: `http://your-domain/database/migrations/migrate_audit_trail.php`
3. The Auditoría module will guide you through the setup if the table is missing

**Quick Fix:**
```sql
-- Run this SQL in phpMyAdmin
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Problem: Blank page after login

**Solution:**
1. Check PHP error log
2. Verify PHP version is 7.4+
3. Check all required extensions:

```bash
php -m | grep -E 'pdo|mysql|json|mbstring'
```

### Problem: Cannot upload files

**Solution:**
1. Check directory permissions:
```bash
ls -la public/uploads
```

2. Increase PHP limits in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

3. Restart web server

### Problem: URLs contain "index.php"

**Solution:**
1. Verify `.htaccess` exists in project root AND public folder
2. Enable mod_rewrite
3. Restart Apache

---

## Development Environment Setup

### 1. Enable Debug Mode

In `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### 2. Database Seed Data

Already included in `schema.sql`:
- 4 test users (all roles)
- 2 sample forms
- 5 sample applications
- Financial data
- Status history

### 3. Test Credentials

| Role | Username | Password | Email |
|------|----------|----------|-------|
| Admin | admin | password123 | admin@crmvisas.com |
| Gerente | gerente01 | password123 | gerente@crmvisas.com |
| Asesor | asesor01 | password123 | asesor1@crmvisas.com |
| Asesor | asesor02 | password123 | asesor2@crmvisas.com |

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Change all default passwords
- [ ] Update database credentials
- [ ] Disable display_errors in PHP
- [ ] Enable error logging
- [ ] Set proper file permissions
- [ ] Test all modules
- [ ] Backup database
- [ ] Configure SSL certificate
- [ ] Set up automated backups
- [ ] Configure email settings

### Recommended Hosting

**Minimum Specs:**
- 2 CPU cores
- 2 GB RAM
- 10 GB SSD
- PHP 7.4+
- MySQL 5.7+

**Recommended Providers:**
- DigitalOcean (Droplet)
- AWS (EC2 + RDS)
- Linode
- VPS with cPanel

---

## Maintenance

### Daily Tasks
- Monitor error logs
- Check disk space
- Review new applications

### Weekly Tasks
- Backup database
- Review financial reports
- Update user access

### Monthly Tasks
- Security audit
- Performance optimization
- Update documentation

---

## Support & Documentation

### Resources:
- **README.md** - Complete feature documentation
- **schema.sql** - Database structure with comments
- **Test page** - System diagnostics at `/test-conexion`

### Getting Help:
1. Check error logs: `error.log`
2. Visit test page: `/test-conexion`
3. Review troubleshooting section above

---

## System URLs

After installation, these URLs will be available:

- **Login:** `/login`
- **Dashboard:** `/dashboard`
- **Solicitudes:** `/solicitudes`
- **Formularios:** `/formularios` (Admin only)
- **Financiero:** `/financiero` (Admin/Gerente)
- **Usuarios:** `/usuarios` (Admin only)
- **Reportes:** `/reportes` (Admin/Gerente)
- **Configuración:** `/configuracion` (Admin only)
- **Auditoría:** `/auditoria` (Admin only)
- **Logs:** `/logs` (Admin only)
- **Test:** `/test-conexion`

---

**Installation complete!** 🎉

The system is now ready to use. Login with admin credentials and start managing visa/passport applications.

For questions or issues, refer to the README.md or contact support.
