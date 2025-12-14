<?php 
$title = 'Ver Servicio - CRM Visas';
$pageTitle = 'Detalle del Servicio';
ob_start(); 
?>

<div class="max-w-4xl mx-auto">
    <!-- Service Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($service['name']); ?>
                </h2>
                <p class="text-gray-600">
                    <span class="font-semibold">Código:</span> <?php echo htmlspecialchars($service['code']); ?>
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium <?php echo $service['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                <?php echo $service['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>
            </span>
        </div>

        <?php if ($service['description']): ?>
            <div class="mb-4">
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-globe mr-2 text-blue-600"></i>
                    <span class="font-semibold">País:</span> <?php echo htmlspecialchars($service['country']); ?>
                </p>
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-tag mr-2 text-blue-600"></i>
                    <span class="font-semibold">Tipo:</span> <?php echo htmlspecialchars($service['visa_type']); ?>
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-clock mr-2 text-blue-600"></i>
                    <span class="font-semibold">Tiempo:</span> <?php echo htmlspecialchars($service['processing_time'] ?? 'N/A'); ?>
                </p>
            </div>
            <div>
                <p class="text-3xl font-bold text-green-600 mb-1">
                    $<?php echo number_format($service['base_price'], 2); ?> <?php echo $service['currency']; ?>
                </p>
                <p class="text-sm text-gray-600">Precio base del servicio</p>
            </div>
        </div>
    </div>

    <!-- Required Documents -->
    <?php if ($service['required_documents']): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-file-alt mr-2"></i>Documentos Requeridos
            </h3>
            <div class="text-gray-700 whitespace-pre-line">
                <?php echo nl2br(htmlspecialchars($service['required_documents'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Document Checklist -->
    <?php if (!empty($checklist)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-check-square mr-2"></i>Lista de Verificación de Documentos
            </h3>
            <ul class="space-y-2">
                <?php foreach ($checklist as $item): ?>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                        <div>
                            <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($item['document_name']); ?></span>
                            <?php if ($item['description']): ?>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($item['required']): ?>
                                <span class="text-xs text-red-600 font-semibold">Obligatorio</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Form Templates -->
    <?php if (!empty($templates)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                <i class="fas fa-file-contract mr-2"></i>Plantillas de Formularios
            </h3>
            <div class="space-y-3">
                <?php foreach ($templates as $template): ?>
                    <div class="border rounded p-3 hover:bg-gray-50">
                        <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($template['name']); ?></h4>
                        <?php if ($template['description']): ?>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($template['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex justify-between space-x-4">
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=services" 
           class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Volver
        </a>
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=services&action=edit&id=<?php echo $service['id']; ?>" 
           class="px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
            <i class="fas fa-edit mr-2"></i>Editar Servicio
        </a>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
