<?php 
$title = 'Customer Journey - ' . $application['folio'];

// Helper function for touchpoint CSS classes
function getTouchpointColor($type) {
    $colors = [
        'status_change' => 'bg-blue-500 border-blue-500',
        'payment' => 'bg-green-500 border-green-500',
        'email' => 'bg-purple-500 border-purple-500',
        'call' => 'bg-orange-500 border-orange-500',
        'meeting' => 'bg-indigo-500 border-indigo-500'
    ];
    return $colors[$type] ?? 'bg-gray-500 border-gray-500';
}

// Helper function for touchpoint icons
function getTouchpointIcon($type) {
    $icons = [
        'status_change' => 'fa-exchange-alt',
        'payment' => 'fa-dollar-sign',
        'email' => 'fa-envelope',
        'call' => 'fa-phone',
        'meeting' => 'fa-users',
        'document_upload' => 'fa-file-upload'
    ];
    return $icons[$type] ?? 'fa-circle';
}

ob_start(); 
?>

<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
            <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $application['id'] ?>" class="text-primary hover:underline">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Customer Journey</h2>
                <p class="text-gray-600">Seguimiento completo de la solicitud <?= htmlspecialchars($application['folio']) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Application Summary Card -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-sm text-gray-500 mb-1">Folio</p>
            <p class="text-lg font-bold text-primary"><?= htmlspecialchars($application['folio']) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500 mb-1">Tipo</p>
            <p class="text-lg font-semibold"><?= htmlspecialchars($application['type']) ?> - <?= htmlspecialchars($application['subtype']) ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500 mb-1">Estatus Actual</p>
            <span class="inline-block px-3 py-1 text-sm rounded-full font-medium <?= 
                $application['status'] === STATUS_FINALIZADO ? 'bg-green-100 text-green-800' :
                ($application['status'] === STATUS_APROBADO ? 'bg-blue-100 text-blue-800' :
                ($application['status'] === STATUS_RECHAZADO ? 'bg-red-100 text-red-800' :
                'bg-yellow-100 text-yellow-800'))
            ?>">
                <?= htmlspecialchars($application['status']) ?>
            </span>
        </div>
        <div>
            <p class="text-sm text-gray-500 mb-1">Creado por</p>
            <p class="text-lg font-semibold"><?= htmlspecialchars($application['creator_name']) ?></p>
        </div>
    </div>
    
    <?php if ($application['progress_percentage'] > 0): ?>
    <div class="mt-4">
        <div class="flex justify-between items-center mb-2">
            <p class="text-sm text-gray-600">Progreso del formulario</p>
            <p class="text-sm font-semibold text-gray-800"><?= number_format($application['progress_percentage'], 0) ?>%</p>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $application['progress_percentage'] ?>%"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add New Touchpoint (for admins and managers) -->
<?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-plus-circle text-primary mr-2"></i>Agregar Punto de Contacto
    </h3>
    
    <form method="POST" action="<?= BASE_URL ?>/customer-journey/agregar/<?= $application['id'] ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de Punto de Contacto <span class="text-red-500">*</span>
            </label>
            <select name="touchpoint_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option value="">Seleccione...</option>
                <option value="email">📧 Email</option>
                <option value="call">📞 Llamada</option>
                <option value="meeting">🤝 Reunión</option>
                <option value="status_change">📊 Cambio de Estatus</option>
                <option value="payment">💰 Pago</option>
                <option value="document_upload">📄 Carga de Documento</option>
                <option value="note">📝 Nota</option>
                <option value="other">🔔 Otro</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Método de Contacto
            </label>
            <select name="contact_method" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option value="">N/A</option>
                <option value="email">Email</option>
                <option value="phone">Teléfono</option>
                <option value="in-person">En Persona</option>
                <option value="online">En Línea</option>
            </select>
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Título <span class="text-red-500">*</span>
            </label>
            <input type="text" name="title" required
                   class="w-full border border-gray-300 rounded-lg px-4 py-2"
                   placeholder="Ej: Llamada de seguimiento">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
            <textarea name="description" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-4 py-2"
                      placeholder="Detalles adicionales sobre este punto de contacto"></textarea>
        </div>
        
        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90">
                <i class="fas fa-save mr-2"></i>Agregar Punto de Contacto
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Timeline View -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-route text-primary mr-2"></i>Línea de Tiempo del Cliente
    </h3>
    
    <div class="relative">
        <!-- Vertical line -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
        
        <?php if (empty($touchpoints)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-history text-6xl mb-4"></i>
            <p>No hay puntos de contacto registrados aún</p>
        </div>
        <?php else: ?>
        
        <div class="space-y-6">
            <?php foreach ($touchpoints as $index => $touchpoint): ?>
            <div class="relative pl-12">
                <!-- Timeline dot -->
                <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center <?= getTouchpointColor($touchpoint['touchpoint_type']) ?>">
                    <i class="fas <?= getTouchpointIcon($touchpoint['touchpoint_type']) ?> text-white text-sm"></i>
                </div>
                
                <!-- Content card -->
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?= getTouchpointColor($touchpoint['touchpoint_type']) ?>">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-bold text-gray-800"><?= htmlspecialchars($touchpoint['touchpoint_title']) ?></h4>
                        <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($touchpoint['created_at'])) ?></span>
                    </div>
                    
                    <?php if (!empty($touchpoint['touchpoint_description'])): ?>
                    <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($touchpoint['touchpoint_description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="px-2 py-1 bg-white rounded-full text-gray-600">
                            <i class="fas fa-tag mr-1"></i><?= ucfirst(str_replace('_', ' ', $touchpoint['touchpoint_type'])) ?>
                        </span>
                        
                        <?php if (!empty($touchpoint['contact_method'])): ?>
                        <span class="px-2 py-1 bg-white rounded-full text-gray-600">
                            <i class="fas fa-comment mr-1"></i><?= ucfirst($touchpoint['contact_method']) ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($touchpoint['user_name'])): ?>
                        <span class="px-2 py-1 bg-white rounded-full text-gray-600">
                            <i class="fas fa-user mr-1"></i><?= htmlspecialchars($touchpoint['user_name']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- Status History Section -->
<?php if (!empty($statusHistory)): ?>
<div class="bg-white rounded-lg shadow-lg p-6 mt-6">
    <h3 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-history text-primary mr-2"></i>Historial de Cambios de Estatus
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">De</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">A</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comentario</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modificado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($statusHistory as $history): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?= date('d/m/Y H:i', strtotime($history['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <?= $history['previous_status'] ? htmlspecialchars($history['previous_status']) : '-' ?>
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold text-blue-600">
                        <?= htmlspecialchars($history['new_status']) ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?= htmlspecialchars($history['comment'] ?? '-') ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?= htmlspecialchars($history['changed_by_name']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Financial Summary (if available and user has permission) -->
<?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE]) && $financialStatus): ?>
<div class="bg-white rounded-lg shadow-lg p-6 mt-6">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">
        <i class="fas fa-dollar-sign text-primary mr-2"></i>Resumen Financiero
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-blue-600 mb-1">Costo Total</p>
            <p class="text-2xl font-bold text-blue-800">$<?= number_format($financialStatus['total_costs'], 2) ?></p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <p class="text-sm text-green-600 mb-1">Total Pagado</p>
            <p class="text-2xl font-bold text-green-800">$<?= number_format($financialStatus['total_paid'], 2) ?></p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
            <p class="text-sm text-orange-600 mb-1">Saldo Pendiente</p>
            <p class="text-2xl font-bold text-orange-800">$<?= number_format($financialStatus['balance'], 2) ?></p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
            <p class="text-sm text-purple-600 mb-1">Estado</p>
            <p class="text-xl font-bold <?= 
                $financialStatus['status'] === 'Pagado' ? 'text-green-800' :
                ($financialStatus['status'] === 'Parcial' ? 'text-yellow-800' : 'text-red-800')
            ?>"><?= htmlspecialchars($financialStatus['status']) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
