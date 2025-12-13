<?php 
$title = 'Nueva Solicitud - CRM Visas';
$pageTitle = 'Crear Solicitud de Visa';
ob_start(); 
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=create">
            <!-- Client Selection -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-user mr-2"></i>Cliente
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Cliente *</label>
                        <select name="client_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccionar Cliente --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name'] . ' (' . $client['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            ¿No encuentras el cliente? 
                            <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=create" 
                               class="text-blue-600 hover:text-blue-700">Crear nuevo cliente</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Service Selection -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-globe mr-2"></i>Servicio
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Servicio/Visa *</label>
                        <select name="service_id" required id="service_select"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Seleccionar Servicio --</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['base_price']; ?>">
                                    <?php echo htmlspecialchars($service['name'] . ' (' . $service['code'] . ') - $' . number_format($service['base_price'], 2)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Request Details -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-file-alt mr-2"></i>Detalles de la Solicitud
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asignar a</label>
                        <select name="assigned_to"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Sin asignar --</option>
                            <?php foreach ($asesores as $asesor): ?>
                                <option value="<?php echo $asesor['id']; ?>">
                                    <?php echo htmlspecialchars($asesor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select name="status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending">Pendiente</option>
                            <option value="in_process">En Proceso</option>
                            <option value="documents_review">Revisión de Documentos</option>
                            <option value="payment_pending">Pago Pendiente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                        <select name="priority"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Estimada de Completado</label>
                        <input type="date" name="estimated_completion"
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-sticky-note mr-2"></i>Notas
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas Públicas (Visible para el cliente)</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas Internas (Solo para staff)</label>
                        <textarea name="internal_notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests" 
                   class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Crear Solicitud
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
