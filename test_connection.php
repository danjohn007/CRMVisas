<?php
/**
 * Connection Test and URL Base Confirmation
 * Tests database connection and displays configuration info
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/url.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - CRM Visas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Test de Conexión - CRM Visas</h1>
            
            <!-- URL Base Test -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-green-600">✓ Configuración de URL Base</h2>
                <div class="space-y-2">
                    <p><strong>URL Base:</strong> <code class="bg-gray-100 px-2 py-1 rounded"><?php echo BASE_URL; ?></code></p>
                    <p><strong>Protocolo:</strong> <?php echo (strpos(BASE_URL, 'https') !== false) ? 'HTTPS (Seguro)' : 'HTTP'; ?></p>
                    <p><strong>Host:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
                    <p><strong>Ruta Base:</strong> <?php echo str_replace($_SERVER['HTTP_HOST'], '', BASE_URL); ?></p>
                </div>
            </div>
            
            <!-- Database Connection Test -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Base de Datos</h2>
                <?php
                try {
                    require_once __DIR__ . '/app/models/Database.php';
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    
                    // Test query
                    $stmt = $conn->query("SELECT VERSION() as version");
                    $result = $stmt->fetch();
                    
                    echo '<div class="text-green-600">';
                    echo '<p class="font-semibold">✓ Conexión Exitosa</p>';
                    echo '<div class="mt-3 space-y-2">';
                    echo '<p><strong>Host:</strong> ' . DB_HOST . '</p>';
                    echo '<p><strong>Base de datos:</strong> ' . DB_NAME . '</p>';
                    echo '<p><strong>Usuario:</strong> ' . DB_USER . '</p>';
                    echo '<p><strong>MySQL Version:</strong> ' . $result['version'] . '</p>';
                    echo '</div>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="text-red-600">';
                    echo '<p class="font-semibold">✗ Error de Conexión</p>';
                    echo '<p class="mt-2">' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<div class="mt-3 bg-yellow-50 border border-yellow-200 rounded p-3">';
                    echo '<p class="text-sm text-yellow-800"><strong>Nota:</strong> Asegúrate de:</p>';
                    echo '<ul class="list-disc list-inside text-sm text-yellow-800 mt-2">';
                    echo '<li>Crear la base de datos ejecutando el archivo SQL en assets/sql/</li>';
                    echo '<li>Verificar las credenciales en config/config.php</li>';
                    echo '<li>Asegurarte de que MySQL esté en ejecución</li>';
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- System Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Información del Sistema</h2>
                <div class="space-y-2">
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                    <p><strong>Zona Horaria:</strong> <?php echo date_default_timezone_get(); ?></p>
                    <p><strong>PDO MySQL:</strong> <?php echo extension_loaded('pdo_mysql') ? '✓ Habilitado' : '✗ No disponible'; ?></p>
                    <p><strong>Tamaño máximo de subida:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Acciones</h2>
                <div class="space-y-3">
                    <a href="<?php echo BASE_URL; ?>/public/index.php" 
                       class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded">
                        Ir al Sistema
                    </a>
                    <a href="<?php echo BASE_URL; ?>/assets/sql/schema.sql" 
                       class="block w-full bg-green-600 hover:bg-green-700 text-white text-center py-2 px-4 rounded">
                        Descargar SQL Schema
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
