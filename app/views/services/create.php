<?php 
$title = 'Nuevo Servicio - CRM Visas';
$pageTitle = 'Crear Servicio de Visa';
ob_start(); 
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=services&action=create">
            <!-- Basic Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-info-circle mr-2"></i>Información Básica
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Servicio *</label>
                        <input type="text" name="name" required
                               placeholder="Ej: Visa de Turista USA"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                        <input type="text" name="code" required
                               placeholder="Ej: USA-TOURIST"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">País *</label>
                        <input type="text" name="country" required
                               placeholder="Ej: Estados Unidos"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Visa *</label>
                        <input type="text" name="visa_type" required
                               placeholder="Ej: Turista, Estudiante, Trabajo"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo de Procesamiento</label>
                        <input type="text" name="processing_time"
                               placeholder="Ej: 15-30 días"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
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
                               placeholder="0.00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Moneda *</label>
                        <select name="currency"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="MXN" selected>MXN - Peso Mexicano</option>
                            <option value="USD">USD - Dólar Americano</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="CAD">CAD - Dólar Canadiense</option>
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
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Ejemplo: Pasaporte vigente, Acta de nacimiento, Fotografías, etc.</p>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active" selected>Activo</option>
                    <option value="inactive">Inactivo</option>
                    <option value="archived">Archivado</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=services" 
                   class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Crear Servicio
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
