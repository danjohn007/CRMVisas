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
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            width: 22rem;
            max-width: 90vw;
            max-height: 24rem;
            overflow-y: auto;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 50;
        }
        .notification-dropdown.show {
            display: block;
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

            <div class="absolute bottom-0 w-64 p-3 bg-gray-50 border-t">
                <div class="flex items-center space-x-2 mb-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p class="text-xs text-gray-600 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=logout" 
                   class="flex items-center justify-center space-x-1 w-full px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition">
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
                        <div class="relative">
                            <button id="notificationBtn" class="relative text-gray-600 hover:text-gray-800 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span id="notificationBadge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">0</span>
                            </button>
                            <div id="notificationDropdown" class="notification-dropdown">
                                <div class="p-4 border-b flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-800">Notificaciones</h3>
                                    <button id="markAllReadBtn" class="text-xs text-blue-600 hover:text-blue-800">
                                        Marcar todas como leídas
                                    </button>
                                </div>
                                <div id="notificationList" class="divide-y">
                                    <div class="p-4 text-center text-gray-500 text-sm">
                                        Cargando...
                                    </div>
                                </div>
                            </div>
                        </div>
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
    
    <script>
        // Notification system
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationList = document.getElementById('notificationList');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        
        // Toggle notification dropdown
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            if (notificationDropdown.classList.contains('show')) {
                loadNotifications();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && e.target !== notificationBtn) {
                notificationDropdown.classList.remove('show');
            }
        });
        
        // Load notification count
        function loadNotificationCount() {
            fetch('<?php echo BASE_URL; ?>/public/index.php?page=notifications&action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        notificationBadge.textContent = data.count > 99 ? '99+' : data.count;
                        notificationBadge.classList.remove('hidden');
                    } else {
                        notificationBadge.classList.add('hidden');
                    }
                })
                .catch(error => console.error('Error loading notification count:', error));
        }
        
        // Load notifications
        function loadNotifications() {
            fetch('<?php echo BASE_URL; ?>/public/index.php?page=notifications&action=get&limit=10')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayNotifications(data.notifications);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    notificationList.innerHTML = '<div class="p-4 text-center text-red-500 text-sm">Error al cargar notificaciones</div>';
                });
        }
        
        // Display notifications
        function displayNotifications(notifications) {
            if (notifications.length === 0) {
                notificationList.innerHTML = '<div class="p-4 text-center text-gray-500 text-sm">No hay notificaciones</div>';
                return;
            }
            
            notificationList.innerHTML = '';
            notifications.forEach(notif => {
                const isUnread = notif.is_read == 0;
                const date = new Date(notif.created_at);
                const formattedDate = date.toLocaleDateString('es-ES', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const notifDiv = document.createElement('div');
                notifDiv.className = `p-3 hover:bg-gray-50 ${isUnread ? 'bg-blue-50' : ''} cursor-pointer`;
                notifDiv.setAttribute('data-notification-id', notif.id);
                notifDiv.onclick = () => handleNotificationClick(notif.id, notif.link || '');
                
                const headerDiv = document.createElement('div');
                headerDiv.className = 'flex items-start justify-between mb-1';
                
                const titleH4 = document.createElement('h4');
                titleH4.className = `text-sm font-medium text-gray-800 ${isUnread ? 'font-bold' : ''}`;
                titleH4.textContent = notif.title;
                headerDiv.appendChild(titleH4);
                
                if (isUnread) {
                    const badge = document.createElement('span');
                    badge.className = 'w-2 h-2 bg-blue-600 rounded-full';
                    headerDiv.appendChild(badge);
                }
                
                const messageP = document.createElement('p');
                messageP.className = 'text-xs text-gray-600 mb-1';
                messageP.textContent = notif.message;
                
                const dateSpan = document.createElement('span');
                dateSpan.className = 'text-xs text-gray-400';
                dateSpan.textContent = formattedDate;
                
                notifDiv.appendChild(headerDiv);
                notifDiv.appendChild(messageP);
                notifDiv.appendChild(dateSpan);
                notificationList.appendChild(notifDiv);
            });
        }
        
        // Handle notification click
        function handleNotificationClick(notifId, link) {
            // Mark as read
            fetch('<?php echo BASE_URL; ?>/public/index.php?page=notifications&action=markRead', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + notifId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotificationCount();
                    if (link) {
                        window.location.href = link;
                    }
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        // Mark all as read
        markAllReadBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fetch('<?php echo BASE_URL; ?>/public/index.php?page=notifications&action=markAllRead', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotificationCount();
                    loadNotifications();
                }
            })
            .catch(error => console.error('Error marking all as read:', error));
        });
        
        // Load notification count on page load
        loadNotificationCount();
        
        // Refresh notification count every 30 seconds
        setInterval(loadNotificationCount, 30000);
    </script>
</body>
</html>
