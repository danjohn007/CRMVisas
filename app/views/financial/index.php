<?php 
$title = 'Módulo Financiero';
ob_start(); 
?>

<div class="mb-4 md:mb-6">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Módulo Financiero</h2>
    <p class="text-sm md:text-base text-gray-600">Control de costos y pagos de solicitudes</p>
</div>

<!-- Resumen Financiero -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Solicitudes</p>
                <p class="text-2xl md:text-3xl font-bold text-primary"><?= $summary['total_applications'] ?? 0 ?></p>
            </div>
            <i class="fas fa-file-invoice text-3xl md:text-4xl text-gray-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Costos</p>
                <p class="text-lg md:text-2xl font-bold text-gray-800 truncate">$<?= number_format($summary['total_costs'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-dollar-sign text-3xl md:text-4xl text-gray-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Pagado</p>
                <p class="text-lg md:text-2xl font-bold text-green-600 truncate">$<?= number_format($summary['total_paid'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-check-circle text-3xl md:text-4xl text-green-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Saldo Pendiente</p>
                <p class="text-lg md:text-2xl font-bold text-red-600 truncate">$<?= number_format($summary['total_balance'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-exclamation-circle text-3xl md:text-4xl text-red-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
</div>

<!-- Estadísticas por Estado -->
<div class="bg-white rounded-lg shadow p-4 md:p-6 mb-4 md:mb-6">
    <?php
    $totalByType = (int) ($totalApplicationsByType ?? 0);
    $typeStyles = [
        'Visa' => [
            'container' => 'bg-blue-50 border-blue-100',
            'icon' => 'text-blue-500 bg-blue-100',
            'badge' => 'bg-blue-600 text-white',
            'progress' => 'bg-blue-600'
        ],
        'Pasaporte' => [
            'container' => 'bg-emerald-50 border-emerald-100',
            'icon' => 'text-emerald-500 bg-emerald-100',
            'badge' => 'bg-emerald-600 text-white',
            'progress' => 'bg-emerald-600'
        ]
    ];
    ?>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
        <div>
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Distribución por Tipo de Formulario</h3>
            <p class="text-sm text-gray-500">Solicitudes registradas según el tipo capturado en el sistema.</p>
        </div>
        <span class="inline-flex items-center self-start md:self-auto px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-sm font-medium">
            Total: <?= $totalByType ?>
        </span>
    </div>

    <?php if (!empty($applicationsByType)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($applicationsByType as $typeItem): ?>
            <?php
            $typeName = $typeItem['type'] ?? 'Sin tipo';
            $typeCount = (int) ($typeItem['count'] ?? 0);
            $typePercentage = $totalByType > 0 ? (int) round(($typeCount / $totalByType) * 100) : 0;
            $styles = $typeStyles[$typeName] ?? [
                'container' => 'bg-gray-50 border-gray-100',
                'icon' => 'text-gray-500 bg-gray-100',
                'badge' => 'bg-gray-700 text-white',
                'progress' => 'bg-gray-700'
            ];
            $typeIcon = $typeName === 'Pasaporte' ? 'fa-passport' : 'fa-file-alt';
            ?>
        <div class="border rounded-xl p-4 md:p-5 <?= $styles['container'] ?>">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-12 h-12 rounded-full flex items-center justify-center <?= $styles['icon'] ?>">
                        <i class="fas <?= $typeIcon ?> text-lg"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Tipo de formulario</p>
                        <p class="text-lg font-semibold text-gray-800 truncate"><?= htmlspecialchars($typeName) ?></p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-bold <?= $styles['badge'] ?>">
                    <?= $typeCount ?>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                    <span>Participación</span>
                    <span><?= $typePercentage ?>%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-white/80 overflow-hidden">
                    <div class="h-full rounded-full <?= $styles['progress'] ?>" style="width: <?= $typePercentage ?>%;"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="py-8 text-center text-gray-500">
        <i class="fas fa-chart-pie text-3xl mb-3 text-gray-300"></i>
        <p>No hay tipos de formulario registrados todavía.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Lista de Solicitudes -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">Solicitudes con Información Financiera</h3>
    </div>
    
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Folio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estatus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Costos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Pagado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($applications as $app): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-sm font-semibold text-primary">
                            <?= htmlspecialchars($app['folio']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?= htmlspecialchars($app['type']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full <?= 
                            $app['status'] === STATUS_FINALIZADO ? 'bg-green-100 text-green-800' :
                            ($app['status'] === STATUS_APROBADO ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')
                        ?>">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                        $<?= number_format($app['total_costs'] ?? 0, 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                        $<?= number_format($app['total_paid'] ?? 0, 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?= ($app['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' ?>">
                        $<?= number_format($app['balance'] ?? 0, 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($app['financial_status']): ?>
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= 
                            $app['financial_status'] === FINANCIAL_PAGADO ? 'bg-green-100 text-green-800' :
                            ($app['financial_status'] === FINANCIAL_PARCIAL ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                        ?>">
                            <?= htmlspecialchars($app['financial_status']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/financiero/solicitud/<?= (int)$app['id'] ?>" class="text-blue-600 hover:underline font-medium">Ver / registrar pago</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p>No hay solicitudes registradas</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
        <div class="text-sm text-gray-700">
            Mostrando página <span class="font-semibold"><?= $page ?></span> de <span class="font-semibold"><?= $totalPages ?></span>
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" 
               class="px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
