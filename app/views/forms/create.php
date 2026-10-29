<?php 
$title = 'Crear Formulario';
ob_start(); 
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="<?= BASE_URL ?>/formularios" class="text-primary hover:underline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <h2 class="text-3xl font-bold text-gray-800">Crear Formulario</h2>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/formularios/guardar">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre del Formulario <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Visa Americana - Primera Vez">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                          placeholder="Descripción opcional del formulario"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Seleccione...</option>
                    <option value="Visa">Visa</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" name="subtype"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Primera vez, Renovación, etc.">
            </div>
            
            <!-- Cost Section -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Costo del Servicio <span class="text-gray-400">(Opcional)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="text" inputmode="decimal" data-money-input name="cost" value="0.00"
                           class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="$0.00">
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle"></i> Deja en 0 si no aplica
                </p>
            </div>
            
            <!-- Pagination Section -->
            <div class="md:col-span-2 border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Insertar Paginación
                        </label>
                        <p class="text-xs text-gray-500">
                            Divide el formulario en secciones para guardar el avance
                        </p>
                    </div>
                    <input type="checkbox" name="pagination_enabled" id="pagination_enabled" value="1"
                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                </div>
                
                <div id="pagination-config" style="display: none;" class="bg-gray-50 rounded-lg p-4 mt-3">
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-layer-group mr-1"></i> 
                        Al habilitar paginación, podrás dividir tus campos en secciones. 
                        Los solicitantes podrán guardar su progreso y continuar después.
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 text-sm text-blue-700">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> Después de crear el formulario, podrás editar las páginas 
                        y asignar campos a cada sección.
                    </div>
                </div>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Campos del Formulario <span class="text-red-500">*</span>
                </label>
                
                <!-- Visual Form Builder -->
                <div id="form-builder-container" data-initial-data=""></div>
                
                <!-- Hidden field to store JSON -->
                <input type="hidden" name="fields_json" id="fields_json_hidden" required>
                
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> Arrastra y suelta campos para construir tu formulario
                </p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
            <a href="<?= BASE_URL ?>/formularios" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90">
                <i class="fas fa-save mr-2"></i>Guardar Formulario
            </button>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>/js/form-builder.js"></script>
<script>
// Toggle pagination configuration visibility
document.getElementById('pagination_enabled').addEventListener('change', function() {
    const paginationConfig = document.getElementById('pagination-config');
    paginationConfig.style.display = this.checked ? 'block' : 'none';
});
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
