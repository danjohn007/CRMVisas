<?php 
$title = 'Ver Solicitud - CRM Visas';
$pageTitle = 'Detalle de la Solicitud';
ob_start(); 
?>

<div class="max-w-6xl mx-auto">
    <!-- Request Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    Solicitud <?php echo htmlspecialchars($request['request_number']); ?>
                </h2>
                <p class="text-gray-600">
                    <i class="fas fa-user mr-2"></i>
                    <span class="font-semibold">Cliente:</span> 
                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=view&id=<?php echo $request['client_id']; ?>" 
                       class="text-blue-600 hover:underline">
                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                    </a>
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-cog mr-2"></i>
                    <span class="font-semibold">Servicio:</span> <?php echo htmlspecialchars($request['service_name']); ?>
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium
                <?php 
                echo match($request['status']) {
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'in_process' => 'bg-blue-100 text-blue-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800'
                };
                ?>">
                <?php echo ucfirst($request['status']); ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-calendar mr-2 text-blue-600"></i>
                    <span class="font-semibold">Fecha Envío:</span> 
                    <?php echo date('d/m/Y', strtotime($request['submission_date'])); ?>
                </p>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-clock mr-2 text-blue-600"></i>
                    <span class="font-semibold">Estimado:</span> 
                    <?php echo $request['estimated_completion'] ? date('d/m/Y', strtotime($request['estimated_completion'])) : 'N/A'; ?>
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-flag mr-2 text-blue-600"></i>
                    <span class="font-semibold">Prioridad:</span> 
                    <?php echo ucfirst($request['priority']); ?>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-user-tie mr-2 text-blue-600"></i>
                    <span class="font-semibold">Asignado a:</span> 
                    <?php echo htmlspecialchars($request['assigned_name'] ?? 'Sin asignar'); ?>
                </p>
                <?php if ($request['completion_date']): ?>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-check-circle mr-2 text-green-600"></i>
                        <span class="font-semibold">Completado:</span> 
                        <?php echo date('d/m/Y', strtotime($request['completion_date'])); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-3xl font-bold text-green-600 mb-1">
                    $<?php echo number_format($request['base_price'], 2); ?> <?php echo $request['currency']; ?>
                </p>
                <p class="text-sm text-gray-600">Precio del servicio</p>
            </div>
        </div>

        <?php if ($request['notes']): ?>
            <div class="border-t mt-4 pt-4">
                <h4 class="font-semibold text-gray-700 mb-2">Notas del Cliente:</h4>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($request['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($request['internal_notes']): ?>
            <div class="border-t mt-4 pt-4">
                <h4 class="font-semibold text-gray-700 mb-2">Notas Internas:</h4>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($request['internal_notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Document Checklist -->
    <?php if (!empty($documentChecklist)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-check-square mr-2"></i>Lista de Verificación de Documentos
            </h3>
            <div class="space-y-2">
                <?php foreach ($documentChecklist as $item): ?>
                    <div class="flex items-center justify-between p-3 rounded
                        <?php echo $item['status'] === 'received' ? 'bg-green-50' : 'bg-gray-50'; ?>">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-<?php echo $item['status'] === 'received' ? 'check-circle text-green-600' : 'circle text-gray-400'; ?>"></i>
                            <div>
                                <p class="font-medium text-gray-800">
                                    <?php echo htmlspecialchars($item['document_name']); ?>
                                    <?php if ($item['is_required']): ?>
                                        <span class="text-xs text-red-600 font-semibold ml-2">Obligatorio</span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($item['file_path']): ?>
                                    <a href="<?php echo BASE_URL . '/' . $item['file_path']; ?>" 
                                       target="_blank"
                                       class="text-xs text-blue-600 hover:underline">
                                        <i class="fas fa-download mr-1"></i>Descargar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="text-xs text-gray-600">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Payments -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4 border-b pb-2">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-money-bill mr-2"></i>Pagos
            </h3>
            <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=create&request_id=<?php echo $request['id']; ?>" 
               class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-sm transition">
                <i class="fas fa-plus mr-1"></i>Agregar Pago
            </a>
        </div>
        <?php if (!empty($payments)): ?>
            <div class="space-y-3">
                <?php foreach ($payments as $payment): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-medium text-gray-800">
                                <?php echo htmlspecialchars($payment['payment_reference']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <span class="font-semibold">Monto:</span> 
                                $<?php echo number_format($payment['amount'], 2); ?> <?php echo $payment['currency']; ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo date('d/m/Y', strtotime($payment['created_at'])); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                <?php 
                                echo match($payment['payment_status']) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?php echo ucfirst($payment['payment_status']); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=view&id=<?php echo $payment['id']; ?>" 
                               class="block mt-2 text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">No hay pagos registrados</p>
        <?php endif; ?>
    </div>

    <!-- Status History -->
    <?php if (!empty($statusHistory)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-history mr-2"></i>Historial de Estados
            </h3>
            <div class="space-y-3">
                <?php foreach ($statusHistory as $history): ?>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 mt-2 bg-blue-600 rounded-full"></div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-gray-800">
                                    <?php if ($history['old_status']): ?>
                                        <?php echo ucfirst($history['old_status']); ?> → 
                                    <?php endif; ?>
                                    <?php echo ucfirst($history['new_status']); ?>
                                </p>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?>
                                </span>
                            </div>
                            <?php if ($history['notes']): ?>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($history['notes']); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1">
                                Por: <?php echo htmlspecialchars($history['changed_by_name']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex justify-between space-x-4">
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests" 
           class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=edit&id=<?php echo $request['id']; ?>" 
           class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
            <i class="fas fa-edit mr-2"></i>Editar Solicitud
        </a>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
