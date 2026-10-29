<?php 
$title = 'Editar Usuario';
ob_start(); 
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="<?= BASE_URL ?>/usuarios" class="text-primary hover:opacity-90">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <h2 class="text-3xl font-bold text-gray-800">Editar Usuario</h2>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/usuarios/actualizar/<?= $user['id'] ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Usuario <span class="text-red-500">*</span>
                </label>
                <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre Completo <span class="text-red-500">*</span>
                </label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nueva Contraseña
                </label>
                <input type="password" name="password"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Dejar en blanco para no cambiar">
                <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Rol <span class="text-red-500">*</span>
                </label>
                <select name="role" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="<?= ROLE_ADMIN ?>" <?= $user['role'] === ROLE_ADMIN ? 'selected' : '' ?>><?= ROLE_ADMIN ?></option>
                    <option value="<?= ROLE_GERENTE ?>" <?= $user['role'] === ROLE_GERENTE ? 'selected' : '' ?>><?= ROLE_GERENTE ?></option>
                    <option value="<?= ROLE_ASESOR ?>" <?= $user['role'] === ROLE_ASESOR ? 'selected' : '' ?>><?= ROLE_ASESOR ?></option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="md:col-span-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Usuario activo</span>
                </label>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
            <a href="<?= BASE_URL ?>/usuarios" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90">
                <i class="fas fa-save mr-2"></i>Actualizar Usuario
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
