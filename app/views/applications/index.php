<?php 
$title = 'Solicitudes';
ob_start(); 
$flow = $flow ?? '';
$isAsesorRole = $_SESSION['user_role'] === ROLE_ASESOR;
?>

<div class="mb-4 md:mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Solicitudes</h2>
    </div>
    <a href="<?= BASE_URL ?>/solicitudes/crear" class="btn-primary text-white px-4 md:px-6 py-2 md:py-3 rounded-lg hover:opacity-90 transition text-sm md:text-base">
        <i class="fas fa-plus mr-2"></i>Nueva Solicitud
    </a>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-4 md:mb-6">
    <form method="GET" action="<?= BASE_URL ?>/solicitudes" class="grid grid-cols-1 md:grid-cols-3 gap-4" id="filterForm">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Flujo</label>
            <select name="flow" id="flowSelect" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base" onchange="updateStatusOptions()">
                <option value="" <?= $flow === '' ? 'selected' : '' ?>>Todos los flujos</option>
                <option value="normal" <?= $flow === 'normal' ? 'selected' : '' ?>>Flujo normal</option>
                <option value="canadiense" <?= $flow === 'canadiense' ? 'selected' : '' ?>>Flujo canadiense 🍁</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estatus</label>
            <select name="status" id="statusSelect" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
                <!-- Options populated by JS based on selected flow -->
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-gray-600 text-white px-4 md:px-6 py-2 rounded-lg hover:bg-gray-700 transition text-sm md:text-base">
                <i class="fas fa-search mr-2"></i>Filtrar
            </button>
        </div>
    </form>
</div>

<script>
// Status options per flow (PHP-injected current values)
var currentStatus = <?= json_encode($status) ?>;
var isAsesor = <?= json_encode($isAsesorRole) ?>;

var normalStatuses = [
    { value: '', label: 'Todos los estatus' },
    { value: <?= json_encode(STATUS_NUEVO) ?>, label: <?= json_encode(STATUS_NUEVO . ' (Nuevo)') ?> },
    { value: <?= json_encode(STATUS_VALIDANDO_RESPUESTAS) ?>, label: <?= json_encode(STATUS_VALIDANDO_RESPUESTAS) ?> },
    { value: <?= json_encode(STATUS_LISTO_SOLICITUD) ?>, label: <?= json_encode(STATUS_LISTO_SOLICITUD) ?> },
    { value: <?= json_encode(STATUS_EN_ESPERA_PAGO) ?>, label: <?= json_encode(STATUS_EN_ESPERA_PAGO) ?> },
    { value: <?= json_encode(STATUS_CITA_PROGRAMADA) ?>, label: <?= json_encode(STATUS_CITA_PROGRAMADA) ?> },
    { value: <?= json_encode(STATUS_EN_ESPERA_RESULTADO) ?>, label: <?= json_encode(STATUS_EN_ESPERA_RESULTADO) ?> },
    <?php if (!$isAsesorRole): ?>
    { value: <?= json_encode(STATUS_TRAMITE_CERRADO) ?>, label: <?= json_encode(STATUS_TRAMITE_CERRADO) ?> },
    { value: <?= json_encode(STATUS_FINALIZADO) ?>, label: <?= json_encode(STATUS_FINALIZADO . ' (legacy)') ?> },
    <?php endif; ?>
];

var canadianStatuses = [
    { value: '', label: 'Todos los estatus' },
    { value: <?= json_encode(STATUS_NUEVO) ?>, label: <?= json_encode(STATUS_NUEVO . ' (Nuevo)') ?> },
    { value: <?= json_encode(STATUS_LISTO_SOLICITUD) ?>, label: 'Listo para carga en portal' },
    { value: <?= json_encode(STATUS_EN_ESPERA_PAGO) ?>, label: 'En espera de cita biométrica' },
    { value: <?= json_encode(STATUS_CITA_PROGRAMADA) ?>, label: 'Biométricos programados' },
    { value: <?= json_encode(STATUS_EN_ESPERA_RESULTADO) ?>, label: 'En espera de resolución' },
    <?php if (!$isAsesorRole): ?>
    { value: <?= json_encode(STATUS_TRAMITE_CERRADO) ?>, label: <?= json_encode(STATUS_TRAMITE_CERRADO) ?> },
    <?php endif; ?>
];

function updateStatusOptions() {
    var flow = document.getElementById('flowSelect').value;
    var select = document.getElementById('statusSelect');
    var options = (flow === 'canadiense') ? canadianStatuses : normalStatuses;
    select.innerHTML = '';
    options.forEach(function(opt) {
        var el = document.createElement('option');
        el.value = opt.value;
        el.textContent = opt.label;
        if (opt.value === currentStatus) el.selected = true;
        select.appendChild(el);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateStatusOptions);
</script>

<!-- Tabla de Solicitudes -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Nombre del solicitante</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Servicio</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Primera vez / Renovación</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Color / Estatus</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Responsable</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Fecha de ingreso</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Expediente</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($applications as $app):
                    // Extract applicant name from data_json or client_name column
                    $appData = json_decode($app['data_json'] ?? '{}', true) ?: [];
                    $clientName = trim($appData['nombre_cliente'] ?? '');
                    if (empty($clientName)) {
                        $clientName = trim(($appData['nombre'] ?? '') . ' ' . ($appData['apellidos'] ?? ''));
                    }
                    if (empty($clientName)) $clientName = $app['client_name'] ?? '-';

                    // Determine primera vez / renovación
                    $subtype    = $app['subtype'] ?? '';
                    $esRenovación = stripos($subtype, 'renov') !== false;
                    $tipoLabel  = $esRenovación ? 'Renovación' : 'Primera vez';

                    // Is Canadian visa flow?
                    $appIsCanadian = !empty($app['is_canadian_visa']);

                    // Status color class
                    $sc = 'bg-gray-100 text-gray-800';
                    if (in_array($app['status'], [STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO])) $sc = 'bg-green-100 text-green-800';
                    elseif ($app['status'] === STATUS_EN_ESPERA_RESULTADO) $sc = 'bg-purple-100 text-purple-800';
                    elseif ($app['status'] === STATUS_CITA_PROGRAMADA)     $sc = 'bg-blue-100 text-blue-800';
                    elseif ($app['status'] === STATUS_EN_ESPERA_PAGO)      $sc = 'bg-yellow-100 text-yellow-800';
                    elseif ($app['status'] === STATUS_LISTO_SOLICITUD)     $sc = 'bg-red-100 text-red-800';

                    // Status display label (Canadian flow uses different labels)
                    $statusLabel = $app['status'];
                    if ($appIsCanadian) {
                        if ($app['status'] === STATUS_LISTO_SOLICITUD)     $statusLabel = 'Listo para carga en portal';
                        elseif ($app['status'] === STATUS_EN_ESPERA_PAGO)  $statusLabel = 'En espera de cita biométrica';
                        elseif ($app['status'] === STATUS_CITA_PROGRAMADA) $statusLabel = 'Biométricos programados';
                        elseif ($app['status'] === STATUS_EN_ESPERA_RESULTADO) $statusLabel = 'En espera de resolución';
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 md:px-6 py-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($clientName) ?></span>
                            <?php if (($appData['origen'] ?? '') === 'WhatsApp / Bot'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-bold">
                                <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($app['folio']) ?></p>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900"><?= htmlspecialchars($app['type']) ?></span>
                        <?php if ($appIsCanadian): ?>
                        <span class="ml-1 text-base" title="Visa Canadiense">🍁</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                        <span class="text-sm <?= $esRenovación ? 'text-orange-600' : 'text-blue-600' ?>">
                            <?= $esRenovación ? '<i class="fas fa-redo mr-1"></i>' : '<i class="fas fa-star mr-1"></i>' ?><?= $tipoLabel ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= $sc ?>">
                            <?= htmlspecialchars($statusLabel) ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell">
                        <?= htmlspecialchars($app['creator_name']) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>"
                           class="btn-primary text-white px-3 py-1.5 rounded-lg hover:opacity-90 transition text-xs font-medium">
                            <i class="fas fa-folder-open mr-1"></i>Abrir expediente
                        </a>
                        <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                        <form method="POST" action="<?= BASE_URL ?>/solicitudes/eliminar/<?= $app['id'] ?>"
                              class="inline" onsubmit="return confirm('Esta accion no se puede deshacer.')">
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p>No se encontraron solicitudes</p>
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
            (Total: <?= $total ?> registros)
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&flow=<?= urlencode($flow) ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&flow=<?= urlencode($flow) ?>" 
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
