<?php 
$title = 'Usuarios';
ob_start(); 
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">Gestión de Usuarios</h2>
        <p class="text-gray-600">Administración de usuarios del sistema</p>
    </div>
    <a href="<?= BASE_URL ?>/usuarios/crear" class="btn-primary text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
        <i class="fas fa-user-plus mr-2"></i>Nuevo Usuario
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre Completo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-sm text-gray-900"><?= htmlspecialchars($user['username']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= 
                            $user['role'] === ROLE_ADMIN ? 'bg-purple-100 text-purple-800' :
                            ($user['role'] === ROLE_GERENTE ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800')
                        ?>">
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <?= htmlspecialchars($user['phone'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($user['is_active']): ?>
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">
                            <i class="fas fa-check-circle"></i> Activo
                        </span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 font-medium">
                            <i class="fas fa-times-circle"></i> Inactivo
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-3">
                            <a href="<?= BASE_URL ?>/usuarios/editar/<?= $user['id'] ?>" 
                               class="text-primary hover:underline" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="<?= BASE_URL ?>/usuarios/eliminar/<?= $user['id'] ?>" 
                                  class="inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?')">
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                        <p>No hay usuarios registrados</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
        <div class="text-sm text-gray-700">
            Página <span class="font-semibold"><?= $page ?></span> de <span class="font-semibold"><?= $totalPages ?></span>
            (Total: <?= $total ?> registros)
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
