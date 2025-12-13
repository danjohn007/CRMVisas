<?php 
$title = 'Dashboard - CRM Visas';
$pageTitle = 'Dashboard';
ob_start(); 
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Clients -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Clientes</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_clients']); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Requests -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Solicitudes</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_requests']); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-file-alt text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Solicitudes Pendientes</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo number_format($stats['pending_requests']); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Ingresos del Mes</p>
                <p class="text-3xl font-bold text-green-600 mt-2">$<?php echo number_format($stats['monthly_revenue'], 2); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Requests -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Solicitudes Recientes</h3>
        </div>
        <div class="p-6">
            <?php if (empty($recentRequests)): ?>
                <p class="text-gray-500 text-center py-8">No hay solicitudes recientes</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recentRequests as $request): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($request['service_name']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo htmlspecialchars($request['request_number']); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <?php
                                $statusColors = [
                                    'draft' => 'bg-gray-200 text-gray-800',
                                    'pending' => 'bg-yellow-200 text-yellow-800',
                                    'in_process' => 'bg-blue-200 text-blue-800',
                                    'documents_review' => 'bg-purple-200 text-purple-800',
                                    'payment_pending' => 'bg-orange-200 text-orange-800',
                                    'completed' => 'bg-green-200 text-green-800',
                                    'cancelled' => 'bg-red-200 text-red-800',
                                    'rejected' => 'bg-red-200 text-red-800'
                                ];
                                $statusClass = $statusColors[$request['status']] ?? 'bg-gray-200 text-gray-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                                <p class="text-xs text-gray-500 mt-2">
                                    <?php echo date('d/m/Y', strtotime($request['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests" 
                       class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                        Ver todas las solicitudes →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Tasks -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Tareas Pendientes</h3>
        </div>
        <div class="p-6">
            <?php if (empty($pendingTasks)): ?>
                <p class="text-gray-500 text-center py-8">No hay tareas pendientes</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pendingTasks as $task): ?>
                        <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-calendar text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($task['title']); ?></p>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($task['description'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="far fa-clock mr-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($task['start_datetime'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">
                    <i class="fas fa-check-circle text-4xl text-green-400 mb-2"></i><br>
                    ¡Todo al día! No hay tareas pendientes.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
    <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=create" 
       class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-6 flex items-center space-x-4 transition">
        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <i class="fas fa-user-plus text-2xl"></i>
        </div>
        <div>
            <p class="font-semibold">Nuevo Cliente</p>
            <p class="text-sm opacity-90">Registrar cliente</p>
        </div>
    </a>

    <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=create" 
       class="bg-green-600 hover:bg-green-700 text-white rounded-lg p-6 flex items-center space-x-4 transition">
        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <i class="fas fa-file-plus text-2xl"></i>
        </div>
        <div>
            <p class="font-semibold">Nueva Solicitud</p>
            <p class="text-sm opacity-90">Crear solicitud de visa</p>
        </div>
    </a>

    <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments" 
       class="bg-purple-600 hover:bg-purple-700 text-white rounded-lg p-6 flex items-center space-x-4 transition">
        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <i class="fas fa-money-check-alt text-2xl"></i>
        </div>
        <div>
            <p class="font-semibold">Registrar Pago</p>
            <p class="text-sm opacity-90">Gestionar pagos</p>
        </div>
    </a>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
