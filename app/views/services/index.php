<?php 
$title = 'Servicios de Visa - CRM Visas';
$pageTitle = 'Servicios y Visas';
ob_start(); 
?>

<div class="mb-6">
    <a href="<?php echo BASE_URL; ?>/public/index.php?page=services&action=create" 
       class="inline-block px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Nuevo Servicio
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($services as $service): ?>
        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                        <?php echo htmlspecialchars($service['name']); ?>
                    </h3>
                    <p class="text-sm text-gray-600 mb-1">
                        <i class="fas fa-globe mr-2"></i><?php echo htmlspecialchars($service['country']); ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-tag mr-2"></i><?php echo htmlspecialchars($service['visa_type']); ?>
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $service['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                    <?php echo $service['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>
                </span>
            </div>
            
            <?php if ($service['description']): ?>
                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                    <?php echo htmlspecialchars($service['description']); ?>
                </p>
            <?php endif; ?>
            
            <div class="border-t pt-4 mb-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">
                        <i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($service['processing_time'] ?? 'N/A'); ?>
                    </span>
                    <span class="text-lg font-bold text-green-600">
                        $<?php echo number_format($service['base_price'], 2); ?> <?php echo $service['currency']; ?>
                    </span>
                </div>
            </div>
            
            <div class="flex items-center justify-between text-sm text-gray-600 mb-4">
                <span>
                    <i class="fas fa-file-alt mr-1"></i>
                    <?php echo $service['request_count']; ?> solicitudes
                </span>
                <span class="text-xs">
                    Código: <?php echo htmlspecialchars($service['code']); ?>
                </span>
            </div>
            
            <div class="flex space-x-2">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=services&action=view&id=<?php echo $service['id']; ?>" 
                   class="flex-1 text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition text-sm">
                    <i class="fas fa-eye mr-1"></i>Ver
                </a>
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=services&action=edit&id=<?php echo $service['id']; ?>" 
                   class="flex-1 text-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded transition text-sm">
                    <i class="fas fa-edit mr-1"></i>Editar
                </a>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($services)): ?>
        <div class="col-span-full text-center py-12">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No hay servicios registrados</p>
            <a href="<?php echo BASE_URL; ?>/public/index.php?page=services&action=create" 
               class="inline-block mt-4 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                Crear Primer Servicio
            </a>
        </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
