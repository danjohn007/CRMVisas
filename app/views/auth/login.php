<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CRM Visas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-600 rounded-full mb-4">
                <i class="fas fa-passport text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">CRM Visas</h1>
            <p class="text-gray-600 mt-2">Sistema de Gestión de Trámites</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-lg shadow-xl p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Iniciar Sesión</h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=login">
                <!-- Username -->
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-user mr-2"></i>Usuario o Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ingresa tu usuario o email">
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ingresa tu contraseña">
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Iniciar Sesión
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 font-medium mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Credenciales de Demo:
                </p>
                <div class="text-xs text-gray-600 space-y-1">
                    <p><strong>Admin:</strong> admin / password123</p>
                    <p><strong>Supervisor:</strong> supervisor1 / password123</p>
                    <p><strong>Asesor:</strong> asesor1 / password123</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-gray-600 text-sm">
            <p>&copy; <?php echo date('Y'); ?> CRM Visas. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
