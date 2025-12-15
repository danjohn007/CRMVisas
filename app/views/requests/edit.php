<?php 
$title = 'Editar Solicitud - CRM Visas';
$pageTitle = 'Editar Solicitud';
ob_start(); 
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=edit&id=<?php echo $request['id']; ?>">
            <!-- Request Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-info-circle mr-2"></i>Información de la Solicitud
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-600 mb-2">
                            <span class="font-semibold">Número de Solicitud:</span> 
                            <?php echo htmlspecialchars($request['request_number']); ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                        <select name="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="in_process" <?php echo $request['status'] === 'in_process' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completado</option>
                            <option value="cancelled" <?php echo $request['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad *</label>
                        <select name="priority" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low" <?php echo $request['priority'] === 'low' ? 'selected' : ''; ?>>Baja</option>
                            <option value="medium" <?php echo $request['priority'] === 'medium' ? 'selected' : ''; ?>>Media</option>
                            <option value="high" <?php echo $request['priority'] === 'high' ? 'selected' : ''; ?>>Alta</option>
                            <option value="urgent" <?php echo $request['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgente</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-user-tie mr-2"></i>Asignación
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asignar a</label>
                        <select name="assigned_to"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Sin asignar</option>
                            <?php foreach ($asesores as $asesor): ?>
                                <option value="<?php echo $asesor['id']; ?>" 
                                        <?php echo $request['assigned_to'] == $asesor['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($asesor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Estimada de Completación</label>
                        <input type="date" name="estimated_completion"
                               value="<?php echo htmlspecialchars($request['estimated_completion'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-sticky-note mr-2"></i>Notas
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas Públicas</label>
                        <textarea name="notes" rows="4"
                                  placeholder="Notas visibles para el cliente"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($request['notes'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Estas notas son visibles para el cliente</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas Internas</label>
                        <textarea name="internal_notes" rows="4"
                                  placeholder="Notas internas del equipo"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($request['internal_notes'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Estas notas son solo para uso interno</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=view&id=<?php echo $request['id']; ?>" 
                   class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
