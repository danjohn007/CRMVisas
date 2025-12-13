<?php 
$title = 'Usuarios - CRM Visas';
$pageTitle = 'Gestión de Usuarios';
ob_start(); 
?>

<div class="mb-6">
    <a href="<?php echo BASE_URL; ?>/public/index.php?page=users&action=create" 
       class="inline-block px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Nuevo Usuario
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre Completo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Último Acceso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No hay usuarios registrados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </span>
                                    </div>
                                    <span class="font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $roleColors = [
                                    'admin' => 'bg-red-100 text-red-800',
                                    'supervisor' => 'bg-blue-100 text-blue-800',
                                    'asesor' => 'bg-green-100 text-green-800',
                                    'cliente' => 'bg-gray-100 text-gray-800'
                                ];
                                $roleLabels = [
                                    'admin' => 'Administrador',
                                    'supervisor' => 'Supervisor',
                                    'asesor' => 'Asesor',
                                    'cliente' => 'Cliente'
                                ];
                                $roleClass = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $roleClass; ?>">
                                    <?php echo $roleLabels[$user['role']] ?? $user['role']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'inactive' => 'bg-gray-100 text-gray-800',
                                    'suspended' => 'bg-red-100 text-red-800'
                                ];
                                $statusClass = $statusColors[$user['status']] ?? 'bg-gray-100 text-gray-800';
                                $statusLabels = [
                                    'active' => 'Activo',
                                    'inactive' => 'Inactivo',
                                    'suspended' => 'Suspendido'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo $statusLabels[$user['status']] ?? $user['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="<?php echo BASE_URL; ?>/public/index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" 
                                   class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
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
