<?php 
$title = 'Solicitudes - CRM Visas';
$pageTitle = 'Gestión de Solicitudes';
ob_start(); 
?>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <form method="GET" action="<?php echo BASE_URL; ?>/public/index.php" class="flex flex-wrap gap-4">
        <input type="hidden" name="page" value="requests">
        
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos los estados</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
            <option value="in_process" <?php echo $status === 'in_process' ? 'selected' : ''; ?>>En Proceso</option>
            <option value="documents_review" <?php echo $status === 'documents_review' ? 'selected' : ''; ?>>Revisión Documentos</option>
            <option value="payment_pending" <?php echo $status === 'payment_pending' ? 'selected' : ''; ?>>Pago Pendiente</option>
            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completado</option>
            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
        </select>
        
        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-filter mr-2"></i>Filtrar
        </button>
        
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=create" 
           class="ml-auto px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-plus mr-2"></i>Nueva Solicitud
        </a>
    </form>
</div>

<!-- Requests Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Solicitud</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asignado a</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-folder-open text-4xl text-gray-300 mb-2"></i>
                            <p>No se encontraron solicitudes</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-semibold text-blue-600">
                                    <?php echo htmlspecialchars($request['request_number']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($request['client_email']); ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800">
                                <?php echo htmlspecialchars($request['service_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars($request['assigned_name'] ?? 'Sin asignar'); ?>
                            </td>
                            <td class="px-6 py-4">
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
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($request['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=view&id=<?php echo $request['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=edit&id=<?php echo $request['id']; ?>" 
                                       class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
