<?php
/**
 * Test de Conexión a Base de Datos
 * Archivo standalone para verificar la conectividad con MySQL
 * 
 * Acceso directo: http://your-domain/test_connection.php
 */

// Cargar configuración
require_once __DIR__ . '/config/config.php';

// Iniciar salida HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - CRM Visas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .test-result {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-card h3 {
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .info-card p {
            color: #666;
            font-size: 16px;
            font-family: 'Courier New', monospace;
        }
        .status-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }
        .details strong {
            color: #333;
        }
        table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        table td:first-child {
            font-weight: 600;
            color: #666;
            width: 40%;
        }
        table td:last-child {
            font-family: 'Courier New', monospace;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔌 Test de Conexión a Base de Datos</h1>
            <p>Sistema CRM de Visas y Pasaportes</p>
        </div>
        
        <div class="content">
            <?php
            // Variables para almacenar resultados
            $connectionSuccess = false;
            $errorMessage = '';
            $dbInfo = [];
            
            // Intentar conexión
            try {
                // Crear conexión PDO
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $connectionSuccess = true;
                
                // Obtener información de la base de datos
                $stmt = $pdo->query("SELECT VERSION() as version");
                $result = $stmt->fetch();
                $dbInfo['version'] = $result['version'];
                
                // Obtener nombre de la base de datos actual
                $stmt = $pdo->query("SELECT DATABASE() as dbname");
                $result = $stmt->fetch();
                $dbInfo['database'] = $result['dbname'];
                
                // Contar tablas
                $stmt = $pdo->query("SHOW TABLES");
                $dbInfo['tables_count'] = $stmt->rowCount();
                
                // Obtener lista de tablas
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $dbInfo['tables'] = $tables;
                
                // Verificar si hay usuarios en la tabla users
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                    $result = $stmt->fetch();
                    $dbInfo['users_count'] = $result['count'];
                } catch (PDOException $e) {
                    $dbInfo['users_count'] = 'Tabla no encontrada';
                }
                
            } catch (PDOException $e) {
                $connectionSuccess = false;
                $errorMessage = $e->getMessage();
            }
            
            // Mostrar resultado
            if ($connectionSuccess) {
                echo '<div class="test-result success">';
                echo '<span class="status-icon">✅</span>';
                echo '<h2 style="margin-bottom: 10px;">¡Conexión Exitosa!</h2>';
                echo '<p>La conexión a la base de datos se estableció correctamente.</p>';
                echo '</div>';
                
                // Mostrar información de la base de datos
                echo '<div class="info-grid">';
                
                echo '<div class="info-card">';
                echo '<h3>Versión MySQL</h3>';
                echo '<p>' . htmlspecialchars($dbInfo['version']) . '</p>';
                echo '</div>';
                
                echo '<div class="info-card">';
                echo '<h3>Base de Datos</h3>';
                echo '<p>' . htmlspecialchars($dbInfo['database']) . '</p>';
                echo '</div>';
                
                echo '<div class="info-card">';
                echo '<h3>Tablas Encontradas</h3>';
                echo '<p>' . $dbInfo['tables_count'] . ' tablas</p>';
                echo '</div>';
                
                echo '<div class="info-card">';
                echo '<h3>Usuarios Registrados</h3>';
                echo '<p>' . htmlspecialchars($dbInfo['users_count']) . '</p>';
                echo '</div>';
                
                echo '</div>';
                
                // Mostrar detalles de configuración
                echo '<div class="details">';
                echo '<h3 style="margin-bottom: 10px; color: #333;">📋 Detalles de Configuración</h3>';
                echo '<table>';
                echo '<tr><td>Host:</td><td>' . htmlspecialchars(DB_HOST) . '</td></tr>';
                echo '<tr><td>Base de Datos:</td><td>' . htmlspecialchars(DB_NAME) . '</td></tr>';
                echo '<tr><td>Usuario:</td><td>' . htmlspecialchars(DB_USER) . '</td></tr>';
                echo '<tr><td>Charset:</td><td>' . htmlspecialchars(DB_CHARSET) . '</td></tr>';
                echo '<tr><td>PHP Version:</td><td>' . phpversion() . '</td></tr>';
                echo '<tr><td>Extensión PDO:</td><td>' . (extension_loaded('pdo_mysql') ? '✅ Instalada' : '❌ No instalada') . '</td></tr>';
                echo '</table>';
                echo '</div>';
                
                // Mostrar lista de tablas
                if (!empty($dbInfo['tables'])) {
                    echo '<div class="details" style="margin-top: 20px;">';
                    echo '<h3 style="margin-bottom: 10px; color: #333;">📊 Tablas en la Base de Datos</h3>';
                    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">';
                    foreach ($dbInfo['tables'] as $table) {
                        echo '<div style="background: white; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">';
                        echo '📁 ' . htmlspecialchars($table);
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="test-result error">';
                echo '<span class="status-icon">❌</span>';
                echo '<h2 style="margin-bottom: 10px;">Error de Conexión</h2>';
                echo '<p>No se pudo establecer la conexión con la base de datos.</p>';
                echo '</div>';
                
                echo '<div class="details">';
                echo '<h3 style="margin-bottom: 10px; color: #333;">🔍 Detalles del Error</h3>';
                echo '<p style="color: #721c24; font-family: monospace; background: white; padding: 10px; border-radius: 4px;">';
                echo htmlspecialchars($errorMessage);
                echo '</p>';
                
                echo '<h3 style="margin: 20px 0 10px; color: #333;">📋 Configuración Actual</h3>';
                echo '<table>';
                echo '<tr><td>Host:</td><td>' . htmlspecialchars(DB_HOST) . '</td></tr>';
                echo '<tr><td>Base de Datos:</td><td>' . htmlspecialchars(DB_NAME) . '</td></tr>';
                echo '<tr><td>Usuario:</td><td>' . htmlspecialchars(DB_USER) . '</td></tr>';
                echo '<tr><td>Charset:</td><td>' . htmlspecialchars(DB_CHARSET) . '</td></tr>';
                echo '</table>';
                
                echo '<h3 style="margin: 20px 0 10px; color: #333;">💡 Posibles Soluciones</h3>';
                echo '<ul style="margin-left: 20px; line-height: 1.8;">';
                echo '<li>Verificar que MySQL esté ejecutándose</li>';
                echo '<li>Comprobar las credenciales en <code>config/config.php</code></li>';
                echo '<li>Asegurarse de que la base de datos existe</li>';
                echo '<li>Verificar permisos del usuario de base de datos</li>';
                echo '<li>Revisar que el host sea correcto (localhost, 127.0.0.1, etc.)</li>';
                echo '</ul>';
                echo '</div>';
            }
            ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="<?php echo BASE_URL; ?>/test-conexion" class="btn">
                    🔍 Ver Test Completo del Sistema
                </a>
                <a href="<?php echo BASE_URL; ?>/login" class="btn" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    🏠 Ir al Sistema
                </a>
            </div>
            
            <div class="details" style="margin-top: 30px; text-align: center; font-size: 12px;">
                <p><strong>Archivo:</strong> test_connection.php</p>
                <p><strong>Ubicación:</strong> Raíz del proyecto</p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
