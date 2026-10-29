<?php 
$title = 'Reportes';
ob_start(); 
?>

<div class="mb-4 md:mb-6">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Reportes y Estadísticas</h2>
    <p class="text-sm md:text-base text-gray-600">Análisis detallado de solicitudes y finanzas</p>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-4 md:p-6 mb-4 md:mb-6">
    <form method="GET" action="<?= BASE_URL ?>/reportes" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                <input type="date" name="start_date" value="<?= $startDate ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                <input type="date" name="end_date" value="<?= $endDate ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
                    <option value="">Todos</option>
                    <option value="Visa" <?= $type === 'Visa' ? 'selected' : '' ?>>Visa</option>
                    <option value="Pasaporte" <?= $type === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estatus</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
                    <option value="">Todos</option>
                    <option value="<?= STATUS_FORMULARIO_RECIBIDO ?>" <?= $status === STATUS_FORMULARIO_RECIBIDO ? 'selected' : '' ?>><?= STATUS_FORMULARIO_RECIBIDO ?></option>
                    <option value="<?= STATUS_PAGO_VERIFICADO ?>" <?= $status === STATUS_PAGO_VERIFICADO ? 'selected' : '' ?>><?= STATUS_PAGO_VERIFICADO ?></option>
                    <option value="<?= STATUS_EN_ELABORACION_HOJA ?>" <?= $status === STATUS_EN_ELABORACION_HOJA ? 'selected' : '' ?>><?= STATUS_EN_ELABORACION_HOJA ?></option>
                    <option value="<?= STATUS_EN_REVISION ?>" <?= $status === STATUS_EN_REVISION ? 'selected' : '' ?>><?= STATUS_EN_REVISION ?></option>
                    <option value="<?= STATUS_RECHAZADO ?>" <?= $status === STATUS_RECHAZADO ? 'selected' : '' ?>><?= STATUS_RECHAZADO ?></option>
                    <option value="<?= STATUS_APROBADO ?>" <?= $status === STATUS_APROBADO ? 'selected' : '' ?>><?= STATUS_APROBADO ?></option>
                    <option value="<?= STATUS_CITA_SOLICITADA ?>" <?= $status === STATUS_CITA_SOLICITADA ? 'selected' : '' ?>><?= STATUS_CITA_SOLICITADA ?></option>
                    <option value="<?= STATUS_CITA_CONFIRMADA ?>" <?= $status === STATUS_CITA_CONFIRMADA ? 'selected' : '' ?>><?= STATUS_CITA_CONFIRMADA ?></option>
                    <option value="<?= STATUS_PROCESO_EMBAJADA ?>" <?= $status === STATUS_PROCESO_EMBAJADA ? 'selected' : '' ?>><?= STATUS_PROCESO_EMBAJADA ?></option>
                    <option value="<?= STATUS_FINALIZADO ?>" <?= $status === STATUS_FINALIZADO ? 'selected' : '' ?>><?= STATUS_FINALIZADO ?></option>
                </select>
            </div>
        </div>
        
        <div class="flex flex-col md:flex-row justify-between gap-4">
            <button type="submit" class="btn-primary text-white px-4 md:px-6 py-2 rounded-lg hover:opacity-90 text-sm md:text-base">
                <i class="fas fa-search mr-2"></i>Generar Reporte
            </button>
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="<?= BASE_URL ?>/reportes/exportar?format=csv&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&type=<?= $type ?>&status=<?= $status ?>" 
                   class="bg-green-600 text-white px-3 md:px-4 py-2 rounded-lg hover:bg-green-700 inline-block text-center text-sm md:text-base">
                    <i class="fas fa-file-csv mr-2"></i>Exportar CSV
                </a>
                <a href="<?= BASE_URL ?>/reportes/exportar?format=excel&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&type=<?= $type ?>&status=<?= $status ?>" 
                   class="bg-green-600 text-white px-3 md:px-4 py-2 rounded-lg hover:bg-green-700 inline-block text-center text-sm md:text-base">
                    <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Resumen Financiero -->
<?php if (isset($financial)): ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Costos</p>
                <p class="text-2xl md:text-3xl font-bold text-primary truncate">$<?= number_format($financial['total_costs'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-dollar-sign text-3xl md:text-4xl text-gray-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Pagado</p>
                <p class="text-2xl md:text-3xl font-bold text-green-600 truncate">$<?= number_format($financial['total_paid'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-check-circle text-3xl md:text-4xl text-green-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Saldo Pendiente</p>
                <p class="text-2xl md:text-3xl font-bold text-red-600 truncate">$<?= number_format($financial['total_balance'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-exclamation-circle text-3xl md:text-4xl text-red-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6 text-center">
        <p class="text-gray-600 text-xs md:text-sm mb-2">Pagado</p>
        <p class="text-xl md:text-2xl font-bold text-green-600"><?= $financial['pagado_count'] ?? 0 ?></p>
        <p class="text-xs text-gray-500">solicitudes</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6 text-center">
        <p class="text-gray-600 text-xs md:text-sm mb-2">Pago Parcial</p>
        <p class="text-xl md:text-2xl font-bold text-yellow-600"><?= $financial['parcial_count'] ?? 0 ?></p>
        <p class="text-xs text-gray-500">solicitudes</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6 text-center">
        <p class="text-gray-600 text-xs md:text-sm mb-2">Pendiente</p>
        <p class="text-xl md:text-2xl font-bold text-red-600"><?= $financial['pendiente_count'] ?? 0 ?></p>
        <p class="text-xs text-gray-500">solicitudes</p>
    </div>
</div>
<?php endif; ?>

<?php if (isset($cashFlow)): ?>
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Estadísticas de Ingresos</h3>
            <p class="text-sm text-gray-500">Los importes recibidos incluyen únicamente pagos validados por Gerencia.</p>
        </div>
        <a href="<?= BASE_URL ?>/control-ingresos" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-semibold">Ver Control de Ingresos</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3">
            <p class="text-xs text-indigo-700 font-semibold uppercase">Ventas contratadas</p>
            <p class="text-lg font-bold text-indigo-800">$<?= number_format($cashFlow['sales_amount'] ?? 0, 2) ?></p>
            <p class="text-xs text-indigo-600"><?= (int)($cashFlow['sales_count'] ?? 0) ?> ventas</p>
        </div>
        <div class="rounded-lg border border-green-100 bg-green-50 p-3">
            <p class="text-xs text-green-700 font-semibold uppercase">Efectivo</p>
            <p class="text-lg font-bold text-green-800">$<?= number_format($cashFlow['cash'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
            <p class="text-xs text-blue-700 font-semibold uppercase">Transferencia</p>
            <p class="text-lg font-bold text-blue-800">$<?= number_format($cashFlow['transfer'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-purple-100 bg-purple-50 p-3">
            <p class="text-xs text-purple-700 font-semibold uppercase">Terminal</p>
            <p class="text-lg font-bold text-purple-800">$<?= number_format($cashFlow['terminal'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-red-100 bg-red-50 p-3">
            <p class="text-xs text-red-700 font-semibold uppercase">Egresos</p>
            <p class="text-lg font-bold text-red-800">$<?= number_format($cashFlow['expenses'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <p class="text-xs text-gray-700 font-semibold uppercase">Ingresos validados</p>
            <p class="text-lg font-bold text-gray-800">$<?= number_format($cashFlow['income'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3">
            <p class="text-xs text-emerald-700 font-semibold uppercase">Resultado</p>
            <p class="text-lg font-bold <?= ($cashFlow['net'] ?? 0)>=0?'text-emerald-800':'text-red-800' ?>">$<?= number_format($cashFlow['net'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-red-100 bg-red-50 p-3">
            <p class="text-xs text-red-700 font-semibold uppercase">Cuentas por cobrar</p>
            <p class="text-lg font-bold text-red-800">$<?= number_format($cashFlow['receivables'] ?? 0, 2) ?></p>
        </div>
        <div class="rounded-lg border border-amber-100 bg-amber-50 p-3">
            <p class="text-xs text-amber-700 font-semibold uppercase">Pendiente de validar</p>
            <p class="text-lg font-bold text-amber-800">$<?= number_format($cashFlow['pending_validation'] ?? 0, 2) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Resumen por Tipo y Estatus -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 md:p-6 border-b">
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Solicitudes por Tipo y Estatus</h3>
        </div>
        <div class="p-4 md:p-6">
            <div class="space-y-3">
                <?php if (!empty($summary)): ?>
                    <?php foreach ($summary as $item): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div class="flex-1 min-w-0">
                            <span class="font-medium text-gray-700 text-sm md:text-base"><?= htmlspecialchars($item['type']) ?></span>
                            <span class="text-gray-500"> - </span>
                            <span class="text-xs md:text-sm text-gray-600"><?= htmlspecialchars($item['status']) ?></span>
                        </div>
                        <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-bold ml-2 flex-shrink-0">
                            <?= $item['total_applications'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500 py-4 text-sm md:text-base">No hay datos para mostrar</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 md:p-6 border-b">
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Top Asesores</h3>
        </div>
        <div class="p-4 md:p-6">
            <div class="space-y-3">
                <?php if (!empty($topAdvisors)): ?>
                    <?php foreach ($topAdvisors as $advisor): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-700 text-sm md:text-base truncate"><?= htmlspecialchars($advisor['full_name']) ?></p>
                            <p class="text-xs text-gray-500">
                                <?= $advisor['finalizadas'] ?> finalizadas de <?= $advisor['total_applications'] ?>
                            </p>
                        </div>
                        <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-bold ml-2 flex-shrink-0">
                            <?= $advisor['total_applications'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500 py-4 text-sm md:text-base">No hay datos para mostrar</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico por Día -->
<?php if (!empty($byDay)): ?>
<div class="bg-white rounded-lg shadow">
    <div class="p-4 md:p-6 border-b">
        <h3 class="text-lg md:text-xl font-bold text-gray-800">Solicitudes por Día</h3>
    </div>
    <div class="p-4 md:p-6">
        <div class="space-y-2">
            <?php foreach ($byDay as $day): ?>
            <div class="flex items-center">
                <span class="text-xs md:text-sm text-gray-600 w-20 md:w-32 flex-shrink-0"><?= date('d/m/Y', strtotime($day['date'])) ?></span>
                <div class="flex-1 ml-2 md:ml-4">
                    <div class="bg-blue-200 rounded-full h-6 flex items-center" style="width: <?= min(100, ($day['count'] * 10)) ?>%">
                        <span class="ml-2 md:ml-3 text-xs font-medium text-blue-800"><?= $day['count'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
