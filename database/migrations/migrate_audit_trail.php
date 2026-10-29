<?php
/**
 * Migration Script: Create audit_trail table
 * 
 * This script creates the audit_trail table if it doesn't exist.
 * Run this script once to set up the audit logging system.
 * 
 * Usage from command line:
 *   php database/migrations/migrate_audit_trail.php
 * 
 * Or access via browser:
 *   http://your-domain/database/migrations/migrate_audit_trail.php
 */

// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Audit Trail Table Migration</h2>\n";
    echo "<p>Starting migration process...</p>\n";
    
    // Check if table already exists
    $checkQuery = "SHOW TABLES LIKE 'audit_trail'";
    $result = $db->query($checkQuery);
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Table 'audit_trail' already exists. Skipping creation.</p>\n";
    } else {
        echo "<p>Creating 'audit_trail' table...</p>\n";
        
        // Create the table
        $createTableSQL = "
        CREATE TABLE `audit_trail` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) DEFAULT NULL,
          `user_name` varchar(100) DEFAULT NULL,
          `user_email` varchar(100) DEFAULT NULL,
          `action` varchar(100) NOT NULL COMMENT 'login, logout, create, update, delete, etc',
          `module` varchar(100) NOT NULL COMMENT 'usuarios, solicitudes, formularios, etc',
          `description` text NOT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `action` (`action`),
          KEY `module` (`module`),
          KEY `created_at` (`created_at`),
          CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        if ($db->query($createTableSQL)) {
            echo "<p style='color: green;'>✅ Table 'audit_trail' created successfully!</p>\n";
            
            // Insert sample data (optional)
            echo "<p>Inserting sample audit data...</p>\n";
            $sampleDataSQL = "
            INSERT INTO `audit_trail` (`user_id`, `user_name`, `user_email`, `action`, `module`, `description`, `ip_address`, `created_at`) VALUES
            (1, 'Carlos Administrador', 'admin@crmvisas.com', 'login', 'Auth', 'Inicio de sesión exitoso', '200.68.174.77', '2026-01-29 18:26:00'),
            (1, 'Carlos Administrador', 'admin@crmvisas.com', 'create', 'Solicitudes', 'Solicitud creada: VISA-2026-000001', '200.68.174.77', '2026-01-29 12:07:00'),
            (1, 'Carlos Administrador', 'admin@crmvisas.com', 'login', 'Auth', 'Inicio de sesión exitoso', '189.128.214.175', '2026-01-29 12:01:00'),
            (2, 'María González López', 'gerente@crmvisas.com', 'update', 'Solicitudes', 'Cambio de estatus: VISA-2026-000001 - En revisión', '187.194.216.48', '2026-01-28 18:22:00'),
            (1, 'Carlos Administrador', 'admin@crmvisas.com', 'login', 'Auth', 'Inicio de sesión exitoso', '189.141.15.215', '2026-01-28 18:16:00'),
            (3, 'Juan Pérez Ramírez', 'asesor1@crmvisas.com', 'create', 'Solicitudes', 'Solicitud creada: VISA-2026-000002', '187.194.216.48', '2026-01-28 12:47:00')
            ";
            
            if ($db->query($sampleDataSQL)) {
                echo "<p style='color: green;'>✅ Sample data inserted successfully!</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠️ Sample data insertion failed (may be due to foreign key constraints): " . $db->error . "</p>\n";
            }
        } else {
            throw new Exception("Failed to create table: " . $db->error);
        }
    }
    
    // Verify table structure
    echo "<p>Verifying table structure...</p>\n";
    $columnsQuery = "DESCRIBE audit_trail";
    $columnsResult = $db->query($columnsQuery);
    
    if ($columnsResult) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
        while ($row = $columnsResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Count records
    $countQuery = "SELECT COUNT(*) as total FROM audit_trail";
    $countResult = $db->query($countQuery);
    $count = $countResult->fetch_assoc()['total'];
    
    echo "<h3 style='color: green;'>✅ Migration completed successfully!</h3>\n";
    echo "<p>Total audit records: <strong>{$count}</strong></p>\n";
    echo "<p>You can now access the <a href='" . BASE_URL . "/auditoria'>Auditoría module</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Migration failed!</h3>\n";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database connection and permissions.</p>\n";
}
?>
