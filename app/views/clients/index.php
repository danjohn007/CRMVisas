<?php 
$title = 'Clientes - CRM Visas';
$pageTitle = 'Gestión de Clientes';
ob_start(); 
?>

<!-- Search and Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <form method="GET" action="<?php echo BASE_URL; ?>/public/index.php" class="flex flex-wrap gap-4">
        <input type="hidden" name="page" value="clients">
        
        <div class="flex-1 min-w-64">
            <input 
                type="text" 
                name="search" 
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Buscar por nombre, email, pasaporte..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Todos los estados</option>
            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activos</option>
            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactivos</option>
            <option value="blacklisted" <?php echo $status === 'blacklisted' ? 'selected' : ''; ?>>Bloqueados</option>
        </select>
        
        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-search mr-2"></i>Buscar
        </button>
        
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=create" 
           class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-plus mr-2"></i>Nuevo Cliente
        </a>
    </form>
</div>

<!-- Clients Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl text-gray-300 mb-2"></i>
                            <p>No se encontraron clientes</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                        </p>
                                        <?php if ($client['passport_number']): ?>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-passport mr-1"></i>
                                                <?php echo htmlspecialchars($client['passport_number']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-800">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>
                                    <?php echo htmlspecialchars($client['email']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-phone mr-1 text-gray-400"></i>
                                    <?php echo htmlspecialchars($client['phone']); ?>
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-800"><?php echo htmlspecialchars($client['city'] ?? ''); ?></p>
                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($client['state'] ?? ''); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'inactive' => 'bg-gray-100 text-gray-800',
                                    'blacklisted' => 'bg-red-100 text-red-800'
                                ];
                                $statusClass = $statusColors[$client['status']] ?? 'bg-gray-100 text-gray-800';
                                $statusLabels = [
                                    'active' => 'Activo',
                                    'inactive' => 'Inactivo',
                                    'blacklisted' => 'Bloqueado'
                                ];
                                $statusLabel = $statusLabels[$client['status']] ?? $client['status'];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=view&id=<?php echo $client['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=edit&id=<?php echo $client['id']; ?>" 
                                       class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (in_array($_SESSION['role'], ['admin', 'supervisor'])): ?>
                                        <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=delete&id=<?php echo $client['id']; ?>" 
                                           class="text-red-600 hover:text-red-800" 
                                           title="Eliminar"
                                           onclick="return confirm('¿Está seguro de eliminar este cliente?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
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
