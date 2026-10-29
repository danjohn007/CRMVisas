<?php
$title = 'Centro de Control';
ob_start();

$priorityClasses = [
    'baja' => 'bg-gray-100 text-gray-700',
    'normal' => 'bg-blue-100 text-blue-700',
    'alta' => 'bg-orange-100 text-orange-700',
    'critica' => 'bg-red-100 text-red-700',
];

$moduleIcons = [
    'solicitudes' => 'fa-file-alt',
    'formularios' => 'fa-edit',
    'usuarios' => 'fa-users',
    'financiero' => 'fa-dollar-sign',
    'ingresos' => 'fa-coins',
    'egresos' => 'fa-scale-balanced',
    'documentos' => 'fa-folder-open',
];
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Centro de Control</h1>
            <p class="text-sm text-gray-500 mt-1">Comunicacion interna de movimientos del CRM para administracion.</p>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/centro-control/marcar-todo-leido">
            <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition">
                <i class="fas fa-check-double mr-2"></i>Marcar todo como leido
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Filtrados</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= (int)$stats['total'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">No leidos</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?= (int)$stats['unread'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Hoy</p>
            <p class="text-2xl font-bold text-blue-600 mt-1"><?= (int)$stats['today'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase font-semibold">Alta prioridad</p>
            <p class="text-2xl font-bold text-orange-600 mt-1"><?= (int)$stats['high'] ?></p>
        </div>
    </div>

    <form method="GET" action="<?= BASE_URL ?>/centro-control" class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Modulo</label>
                <select name="module" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <?php foreach ($modules as $module): ?>
                    <option value="<?= htmlspecialchars($module) ?>" <?= $filters['module'] === $module ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($module)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Accion</label>
                <select name="action" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>>
                        <?= htmlspecialchars($action) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Usuario</label>
                <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= (int)$user['id'] ?>" <?= (string)$filters['user_id'] === (string)$user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Folio o texto</label>
                <input type="text" name="folio" value="<?= htmlspecialchars($filters['folio']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="VISA-...">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Prioridad</label>
                <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    <?php foreach (['baja', 'normal', 'alta', 'critica'] as $priority): ?>
                    <option value="<?= $priority ?>" <?= $filters['priority'] === $priority ? 'selected' : '' ?>>
                        <?= ucfirst($priority) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Lectura</label>
                <select name="read_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="unread" <?= $filters['read_status'] === 'unread' ? 'selected' : '' ?>>No leidos</option>
                    <option value="read" <?= $filters['read_status'] === 'read' ? 'selected' : '' ?>>Leidos</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <a href="<?= BASE_URL ?>/centro-control" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">Limpiar</a>
            <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg text-sm hover:opacity-90">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($events)): ?>
        <div class="p-10 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
            <p>No hay eventos con los filtros seleccionados.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($events as $event): ?>
            <?php
                $metadata = json_decode($event['metadata_json'] ?? '', true) ?: [];
                $icon = $moduleIcons[$event['module']] ?? 'fa-circle-info';
                $priorityClass = $priorityClasses[$event['priority']] ?? $priorityClasses['normal'];
                $isRead = (bool)$event['is_read'];
            ?>
            <div class="p-4 <?= $isRead ? 'bg-white' : 'bg-yellow-50' ?>">
                <div class="flex flex-col md:flex-row md:items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-gray-900"><?= htmlspecialchars(ucfirst($event['module'])) ?></span>
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700"><?= htmlspecialchars($event['action']) ?></span>
                            <span class="text-xs px-2 py-1 rounded-full <?= $priorityClass ?>"><?= htmlspecialchars($event['priority']) ?></span>
                            <?php if (!$isRead): ?>
                            <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">Nuevo</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-800"><?= htmlspecialchars($event['description']) ?></p>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-gray-500">
                            <span><i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($event['created_at'])) ?></span>
                            <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($event['actor_full_name'] ?: $event['user_name'] ?: 'Sistema') ?></span>
                            <?php if (!empty($event['folio'])): ?>
                            <span><i class="fas fa-hashtag mr-1"></i><?= htmlspecialchars($event['folio']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($metadata)): ?>
                        <details class="mt-3">
                            <summary class="text-xs text-blue-600 cursor-pointer">Ver detalle interno</summary>
                            <pre class="mt-2 bg-gray-50 border border-gray-200 rounded p-3 text-xs overflow-x-auto"><?= htmlspecialchars(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </details>
                        <?php endif; ?>
                    </div>
                    <div class="flex md:flex-col gap-2 flex-shrink-0">
                        <?php if (!empty($event['application_id'])): ?>
                        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$event['application_id'] ?>"
                           class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-medium">
                            <i class="fas fa-external-link-alt mr-1"></i>Solicitud
                        </a>
                        <?php elseif (($event['entity_type'] ?? '') === 'formulario' && !empty($event['entity_id'])): ?>
                        <a href="<?= BASE_URL ?>/formularios/editar/<?= (int)$event['entity_id'] ?>"
                           class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-medium">
                            <i class="fas fa-external-link-alt mr-1"></i>Formulario
                        </a>
                        <?php elseif (($event['entity_type'] ?? '') === 'usuario' && !empty($event['entity_id'])): ?>
                        <a href="<?= BASE_URL ?>/usuarios/editar/<?= (int)$event['entity_id'] ?>"
                           class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-medium">
                            <i class="fas fa-external-link-alt mr-1"></i>Usuario
                        </a>
                        <?php endif; ?>
                        <?php if (!$isRead): ?>
                        <form method="POST" action="<?= BASE_URL ?>/centro-control/marcar-leido">
                            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                            <button type="submit" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-medium w-full">
                                <i class="fas fa-check mr-1"></i>Leido
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center items-center gap-2">
        <?php
        $query = $_GET;
        for ($i = 1; $i <= $totalPages; $i++):
            $query['page'] = $i;
        ?>
        <a href="<?= BASE_URL ?>/centro-control?<?= htmlspecialchars(http_build_query($query)) ?>"
           class="px-3 py-2 rounded <?= $i === $page ? 'btn-primary text-white' : 'bg-white text-gray-700 border border-gray-300' ?> text-sm">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
