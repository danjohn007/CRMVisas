<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'CRM Visas'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .sidebar-link:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        .sidebar-link.active {
            background-color: rgba(59, 130, 246, 0.2);
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg flex-shrink-0 overflow-y-auto">
            <div class="p-6 border-b">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-passport text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">CRM Visas</h1>
                        <p class="text-xs text-gray-600">Sistema de Gestión</p>
                    </div>
                </div>
            </div>

            <nav class="p-4">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=dashboard" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-users w-5"></i>
                    <span>Clientes</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/public/index.php?page=services" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-cog w-5"></i>
                    <span>Servicios</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Solicitudes</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-money-bill w-5"></i>
                    <span>Pagos</span>
                </a>

                <?php if (in_array($_SESSION['role'], ['admin', 'supervisor'])): ?>
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=financial" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Financiero</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/public/index.php?page=reports" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Reportes</span>
                </a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=users" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-user-cog w-5"></i>
                    <span>Usuarios</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/public/index.php?page=settings" 
                   class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 transition">
                    <i class="fas fa-sliders-h w-5"></i>
                    <span>Configuración</span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="absolute bottom-0 left-0 right-0 p-3 bg-gray-50 border-t">
                <div class="flex items-center space-x-2 mb-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-800"><?php echo $_SESSION['full_name']; ?></p>
                        <p class="text-xs text-gray-600 capitalize"><?php echo $_SESSION['role']; ?></p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=logout" 
                   class="flex items-center justify-center space-x-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition">
                    <i class="fas fa-sign-out-alt text-xs"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-2xl font-semibold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                    <div class="flex items-center space-x-4">
                        <button class="relative text-gray-600 hover:text-gray-800">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
                        </button>
                        <span class="text-gray-600"><?php echo date('d/m/Y'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
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

                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>
</body>
</html>
