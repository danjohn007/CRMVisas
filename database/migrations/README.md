# Database Migrations

## Audit Trail Table Migration

### Quick Fix for Missing audit_trail Table

If you're getting the error:
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'recursos_visas.audit_trail' doesn't exist
```

Follow these steps:

### Option 1: Web-Based Migration (Recommended)

1. Access the migration script via your browser:
   ```
   http://your-domain/database/migrations/migrate_audit_trail.php
   ```

2. The script will:
   - Check if the table exists
   - Create the `audit_trail` table
   - Insert sample audit data
   - Show you a verification of the table structure

3. Once completed, you can access the Auditoría module at:
   ```
   http://your-domain/auditoria
   ```

### Option 2: Command Line Migration

1. Navigate to your project root directory
2. Run the migration script:
   ```bash
   php database/migrations/migrate_audit_trail.php
   ```

### Option 3: Direct SQL Import

1. Access phpMyAdmin or your MySQL client
2. Select your database (`recursos_visas`)
3. Go to the SQL tab
4. Copy and paste the contents of `create_audit_trail_table.sql`
5. Click "Go" to execute

### Option 4: MySQL Command Line

```bash
mysql -u your_username -p your_database < database/migrations/create_audit_trail_table.sql
```

Replace:
- `your_username` with your MySQL username
- `your_database` with your database name (e.g., `recursos_visas`)

## Table Structure

The `audit_trail` table includes:
- `id` - Auto-incrementing primary key
- `user_id` - Foreign key to users table
- `user_name` - User's full name (cached for performance)
- `user_email` - User's email (cached for performance)
- `action` - Type of action (login, logout, create, update, delete)
- `module` - Module where action occurred (Auth, Solicitudes, etc.)
- `description` - Detailed description of the action
- `ip_address` - IP address of the user
- `user_agent` - Browser user agent string
- `created_at` - Timestamp of the action

## Indexes

The table includes indexes on:
- `user_id` - For filtering by user
- `action` - For filtering by action type
- `module` - For filtering by module
- `created_at` - For date range filtering

These indexes ensure fast query performance even with thousands of audit records.

## Security Note

After running the migration, you may want to restrict access to the migration script by:
1. Deleting the `migrate_audit_trail.php` file
2. Or moving it outside the web root
3. Or adding access control in your `.htaccess` file

## Troubleshooting

If the migration fails:

1. **Permission Error**: Make sure your database user has CREATE TABLE privileges
2. **Foreign Key Error**: Ensure the `users` table exists first
3. **Connection Error**: Check your database credentials in `config/config.php`

## Support

For more information, see:
- `INSTALL.md` - Installation guide
- `FEATURES.md` - Feature documentation
