<?php 
$title = 'Editar Servicio - CRM Visas';
$pageTitle = 'Editar Servicio de Visa';
ob_start(); 
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=services&action=edit&id=<?php echo $service['id']; ?>">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-info-circle mr-2"></i>Información Básica
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Servicio *</label>
                        <input type="text" name="name" required
                               value="<?php echo htmlspecialchars($service['name']); ?>"
                               placeholder="Ej: Visa de Turista USA"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                        <input type="text" name="code" required
                               value="<?php echo htmlspecialchars($service['code']); ?>"
                               placeholder="Ej: USA-TOURIST"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">País *</label>
                        <input type="text" name="country" required
                               value="<?php echo htmlspecialchars($service['country']); ?>"
                               placeholder="Ej: Estados Unidos"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Visa *</label>
                        <input type="text" name="visa_type" required
                               value="<?php echo htmlspecialchars($service['visa_type']); ?>"
                               placeholder="Ej: Turista, Estudiante, Trabajo"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo de Procesamiento</label>
                        <input type="text" name="processing_time"
                               value="<?php echo htmlspecialchars($service['processing_time'] ?? ''); ?>"
                               placeholder="Ej: 15-30 días"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-dollar-sign mr-2"></i>Precio
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio Base *</label>
                        <input type="number" name="base_price" required step="0.01" min="0"
                               value="<?php echo htmlspecialchars($service['base_price']); ?>"
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Moneda *</label>
                        <select name="currency"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="MXN" <?php echo $service['currency'] === 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano</option>
                            <option value="USD" <?php echo $service['currency'] === 'USD' ? 'selected' : ''; ?>>USD - Dólar Americano</option>
                            <option value="EUR" <?php echo $service['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                            <option value="CAD" <?php echo $service['currency'] === 'CAD' ? 'selected' : ''; ?>>CAD - Dólar Canadiense</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Documents Required -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-file-alt mr-2"></i>Documentos Requeridos
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lista de Documentos</label>
                    <textarea name="required_documents" rows="5"
                              placeholder="Ingrese cada documento en una línea nueva"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($service['required_documents'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Ejemplo: Pasaporte vigente, Acta de nacimiento, Fotografías, etc.</p>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active" <?php echo $service['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactive" <?php echo $service['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                    <option value="archived" <?php echo $service['status'] === 'archived' ? 'selected' : ''; ?>>Archivado</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=services&action=view&id=<?php echo $service['id']; ?>" 
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
