<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - CRM Visas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-check-circle text-green-500"></i>
                Test de Conexión y Configuración
            </h1>
            <p class="text-gray-600">Sistema CRM de Visas y Pasaportes</p>
        </div>
        
        <div class="space-y-4">
            <?php foreach ($results as $key => $result): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start">
                    <div class="mr-4">
                        <?php if ($result['status'] === 'success'): ?>
                            <i class="fas fa-check-circle text-3xl text-green-500"></i>
                        <?php elseif ($result['status'] === 'warning'): ?>
                            <i class="fas fa-exclamation-triangle text-3xl text-yellow-500"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-3xl text-red-500"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-800 mb-2 capitalize">
                            <?= str_replace('_', ' ', $key) ?>
                        </h3>
                        <p class="text-gray-700 mb-2"><?= htmlspecialchars($result['message']) ?></p>
                        <div class="bg-gray-50 p-3 rounded mt-2">
                            <code class="text-sm text-gray-800"><?= htmlspecialchars($result['value']) ?></code>
                        </div>
                        
                        <?php if (isset($result['tables'])): ?>
                        <div class="mt-3">
                            <p class="text-sm text-gray-600 mb-2">Tablas encontradas:</p>
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach ($result['tables'] as $table): ?>
                                <div class="bg-blue-50 px-3 py-1 rounded text-sm text-blue-800">
                                    <i class="fas fa-table mr-2"></i><?= htmlspecialchars($table) ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
            <h3 class="text-lg font-bold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>Información del Sistema
            </h3>
            <ul class="space-y-2 text-blue-700">
                <li><i class="fas fa-home mr-2"></i><strong>Página Principal:</strong> <a href="<?= BASE_URL ?>" class="underline"><?= BASE_URL ?></a></li>
                <li><i class="fas fa-sign-in-alt mr-2"></i><strong>Login:</strong> <a href="<?= BASE_URL ?>/login" class="underline"><?= BASE_URL ?>/login</a></li>
                <li><i class="fas fa-database mr-2"></i><strong>Base de Datos:</strong> <?= DB_NAME ?></li>
                <li><i class="fas fa-folder mr-2"></i><strong>Directorio Raíz:</strong> <?= ROOT_PATH ?></li>
            </ul>
        </div>
        
        <div class="mt-6 text-center">
            <a href="<?= BASE_URL ?>/login" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-sign-in-alt mr-2"></i>Ir al Login
            </a>
        </div>
    </div>
</body>
</html>
