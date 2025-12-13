<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página No Encontrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-8xl"></i>
        </div>
        <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
        <p class="text-2xl text-gray-600 mb-8">Página No Encontrada</p>
        <p class="text-gray-500 mb-8">La página que buscas no existe o ha sido movida.</p>
        <a href="<?php echo BASE_URL; ?>/public/index.php" 
           class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-home mr-2"></i>
            Volver al Inicio
        </a>
    </div>
</body>
</html>
