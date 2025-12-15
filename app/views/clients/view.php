<?php 
$title = 'Ver Cliente - CRM Visas';
$pageTitle = 'Detalle del Cliente';
ob_start(); 
?>

<div class="max-w-6xl mx-auto">
    <!-- Client Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                </h2>
                <p class="text-gray-600">
                    <i class="fas fa-envelope mr-2"></i>
                    <?php echo htmlspecialchars($client['email']); ?>
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-phone mr-2"></i>
                    <?php echo htmlspecialchars($client['phone']); ?>
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $client['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                <?php echo $client['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-passport mr-2 text-blue-600"></i>
                    <span class="font-semibold">Pasaporte:</span> <?php echo htmlspecialchars($client['passport_number'] ?? 'N/A'); ?>
                </p>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-flag mr-2 text-blue-600"></i>
                    <span class="font-semibold">Nacionalidad:</span> <?php echo htmlspecialchars($client['nationality'] ?? 'N/A'); ?>
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-birthday-cake mr-2 text-blue-600"></i>
                    <span class="font-semibold">Fecha Nacimiento:</span> <?php echo $client['birth_date'] ? date('d/m/Y', strtotime($client['birth_date'])) : 'N/A'; ?>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-mobile-alt mr-2 text-blue-600"></i>
                    <span class="font-semibold">Móvil:</span> <?php echo htmlspecialchars($client['mobile'] ?? 'N/A'); ?>
                </p>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-venus-mars mr-2 text-blue-600"></i>
                    <span class="font-semibold">Género:</span> <?php echo $client['gender'] ? ucfirst($client['gender']) : 'N/A'; ?>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>
                    <span class="font-semibold">Dirección:</span> <?php echo htmlspecialchars($client['address'] ?? 'N/A'); ?>
                </p>
                <p class="text-sm text-gray-600">
                    <?php echo htmlspecialchars(($client['city'] ?? '') . ', ' . ($client['state'] ?? '') . ', ' . ($client['country'] ?? '')); ?>
                </p>
            </div>
        </div>

        <?php if ($client['notes']): ?>
            <div class="border-t mt-4 pt-4">
                <h4 class="font-semibold text-gray-700 mb-2">Notas:</h4>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($client['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($client['emergency_contact_name'] || $client['emergency_contact_phone']): ?>
            <div class="border-t mt-4 pt-4">
                <h4 class="font-semibold text-gray-700 mb-2">Contacto de Emergencia:</h4>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-user mr-2"></i>
                    <?php echo htmlspecialchars($client['emergency_contact_name'] ?? 'N/A'); ?>
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-phone mr-2"></i>
                    <?php echo htmlspecialchars($client['emergency_contact_phone'] ?? 'N/A'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Solicitudes -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-file-alt mr-2"></i>Solicitudes de Servicio
        </h3>
        <?php if (!empty($requests)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Número</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Servicio</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Fecha</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($request['request_number']); ?></td>
                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($request['service_name']); ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        <?php 
                                        $statusClasses = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'in_process' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        echo $statusClasses[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm"><?php echo date('d/m/Y', strtotime($request['created_at'])); ?></td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=view&id=<?php echo $request['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">No hay solicitudes registradas</p>
        <?php endif; ?>
    </div>

    <!-- Documentos -->
    <?php if (!empty($documents)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-file mr-2"></i>Documentos
            </h3>
            <div class="space-y-2">
                <?php foreach ($documents as $document): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($document['document_type']); ?></p>
                            <p class="text-xs text-gray-600">
                                Subido: <?php echo date('d/m/Y H:i', strtotime($document['created_at'])); ?>
                            </p>
                        </div>
                        <?php if ($document['file_path']): ?>
                            <a href="<?php echo BASE_URL . '/' . $document['file_path']; ?>" 
                               target="_blank"
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-download"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Interacciones -->
    <?php if (!empty($interactions)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-comments mr-2"></i>Interacciones Recientes
            </h3>
            <div class="space-y-3">
                <?php foreach ($interactions as $interaction): ?>
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($interaction['interaction_type']); ?></span>
                            <span class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($interaction['interaction_date'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600"><?php echo nl2br(htmlspecialchars($interaction['notes'])); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Por: <?php echo htmlspecialchars($interaction['user_name']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex justify-between space-x-4">
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients" 
           class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=clients&action=edit&id=<?php echo $client['id']; ?>" 
           class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
            <i class="fas fa-edit mr-2"></i>Editar Cliente
        </a>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
