<?php 
$title = 'Auditoría del Sistema';
ob_start(); 
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/dashboard" class="text-primary hover:underline text-sm mb-2 inline-block">
        <i class="fas fa-arrow-left mr-2"></i>Volver a Dashboard
    </a>
    <h2 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-clipboard-list mr-2"></i>Auditoría del Sistema
    </h2>
    <p class="text-gray-600">Registro de actividades y cambios realizados en el sistema</p>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="<?= BASE_URL ?>/auditoria" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-calendar mr-1"></i>Fecha Inicio
            </label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-calendar mr-1"></i>Fecha Fin
            </label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-user mr-1"></i>Usuario
            </label>
            <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos los usuarios</option>
                <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-search mr-1"></i>Acción
            </label>
            <input type="text" name="action" value="<?= htmlspecialchars($action) ?>" 
                   placeholder="Buscar acción..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-cube mr-1"></i>Módulo
            </label>
            <select name="module" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos los módulos</option>
                <?php foreach ($modules as $mod): ?>
                <option value="<?= htmlspecialchars($mod) ?>" <?= $module === $mod ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mod) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Registros</p>
                <p class="text-3xl font-bold text-primary"><?= number_format($stats['total_records'] ?? 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">
                    <?= date('d/m/Y', strtotime($startDate)) ?> a <?= date('d/m/Y', strtotime($endDate)) ?>
                </p>
            </div>
            <i class="fas fa-list text-4xl text-blue-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Usuarios Activos</p>
                <p class="text-3xl font-bold text-green-600"><?= $stats['active_users'] ?? 0 ?></p>
            </div>
            <i class="fas fa-users text-4xl text-green-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Páginas</p>
                <p class="text-3xl font-bold text-orange-600"><?= $totalPages ?></p>
            </div>
            <i class="fas fa-file text-4xl text-orange-200"></i>
        </div>
    </div>
</div>

<!-- Tabla de Auditoría -->
<?php
$auditActionClass = function ($action) {
    $classes = [
        'login' => 'bg-green-100 text-green-800',
        'logout' => 'bg-gray-100 text-gray-800',
        'create' => 'bg-blue-100 text-blue-800',
        'update' => 'bg-yellow-100 text-yellow-800',
        'delete' => 'bg-red-100 text-red-800',
        'login_geo_denied' => 'bg-red-100 text-red-800',
        'login_failed' => 'bg-orange-100 text-orange-800',
        'login_blocked' => 'bg-red-100 text-red-800',
    ];

    return $classes[$action] ?? 'bg-purple-100 text-purple-800';
};

$auditSecurityActions = ['login_geo_denied', 'login_failed', 'login_blocked'];
$auditExcerpt = function ($text, $limit = 115) {
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));
    if ($text === '') {
        return '-';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') > $limit ? mb_substr($text, 0, $limit, 'UTF-8') . '...' : $text;
    }

    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
};
?>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Riesgo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resumen</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($auditLogs as $log): ?>
                <?php $isSecurityEvent = in_array($log['action'], $auditSecurityActions, true); ?>
                <tr class="hover:bg-gray-50 <?= $isSecurityEvent ? 'bg-red-50/40 border-l-4 border-red-400' : '' ?>">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($log['user_email'] ?? '') ?></p>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $auditActionClass($log['action']) ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php if ($isSecurityEvent): ?>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-shield-halved mr-1"></i>Crítico
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                            Normal
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($log['module']) ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 max-w-md">
                        <?= htmlspecialchars($auditExcerpt($log['description'] ?? '')) ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <button type="button"
                                class="audit-detail-btn inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-primary border border-blue-100 rounded-lg hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer"
                                data-date="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>"
                                data-user="<?= htmlspecialchars($log['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>"
                                data-email="<?= htmlspecialchars($log['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-action="<?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?>"
                                data-module="<?= htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') ?>"
                                data-description="<?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ip="<?= htmlspecialchars($log['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                data-metadata="<?= htmlspecialchars($log['metadata_json'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fas fa-eye mr-2"></i>Ver detalle
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($auditLogs)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                        <p>No hay registros de auditoría para los filtros seleccionados</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Mostrando página <span class="font-medium"><?= $page ?></span> de 
                <span class="font-medium"><?= $totalPages ?></span>
                (<?= number_format($total) ?> registros totales)
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&user_id=<?= urlencode($userId) ?>&action=<?= urlencode($action) ?>&module=<?= urlencode($module) ?>" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Anterior
                </a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&user_id=<?= urlencode($userId) ?>&action=<?= urlencode($action) ?>&module=<?= urlencode($module) ?>" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Siguiente
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="audit-detail-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="audit-detail-title">
    <div class="absolute inset-0 bg-gray-900 bg-opacity-60" data-audit-detail-close></div>
    <div class="relative flex min-h-dvh items-center justify-center p-4">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-lg shadow-xl">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 bg-white px-6 py-5 border-b border-gray-200">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Auditoría del sistema</p>
                    <h3 id="audit-detail-title" class="mt-1 text-xl font-bold text-gray-900">Detalle de auditoría</h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                        <span id="audit-detail-date"></span>
                        <span id="audit-detail-header-action" class="hidden px-2 py-1 text-xs font-medium rounded-full"></span>
                        <span id="audit-detail-security-badge" class="hidden px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"></span>
                    </div>
                </div>
                <button type="button" id="audit-detail-close" class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 border border-gray-200 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer" aria-label="Cerrar detalle">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i class="fas fa-user"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Usuario</h4>
                            <p class="text-xs text-gray-500">Persona asociada al registro</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Usuario del registro</p>
                            <p id="audit-detail-user" class="mt-1 text-sm font-semibold text-gray-900"></p>
                            <p id="audit-detail-email" class="text-xs text-gray-500"></p>
                        </div>
                        <div id="audit-detail-affected-user-wrap">
                            <p class="text-xs font-medium uppercase text-gray-500">Usuario afectado</p>
                            <p id="audit-detail-affected-user" class="mt-1 text-sm font-semibold text-gray-900"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                            <i class="fas fa-clipboard-list"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Evento</h4>
                            <p class="text-xs text-gray-500">Datos generales de la actividad</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Acción</p>
                            <p id="audit-detail-action" class="mt-1 text-sm font-semibold text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Módulo</p>
                            <p id="audit-detail-module" class="mt-1 text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">IP</p>
                            <p id="audit-detail-ip" class="mt-1 text-sm font-mono text-gray-900"></p>
                        </div>
                    </div>
                </div>

                <div id="audit-detail-location-section" class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Ubicación</h4>
                                <p class="text-xs text-gray-500">Información detectada durante el intento</p>
                            </div>
                        </div>
                        <a id="audit-detail-map-link" href="#" target="_blank" rel="noopener noreferrer"
                           class="hidden inline-flex items-center justify-center rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-map-location-dot mr-2"></i>Abrir mapa
                        </a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Estado</p>
                            <p id="audit-detail-location-status" class="mt-1 text-sm font-semibold text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Ubicación detectada</p>
                            <p id="audit-detail-detected-location" class="mt-1 break-words text-sm font-mono text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Ubicación permitida</p>
                            <p id="audit-detail-allowed-location" class="mt-1 break-words text-sm font-mono text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-gray-500">Distancia</p>
                            <p id="audit-detail-distance" class="mt-1 text-sm font-semibold text-gray-900"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                            <i class="fas fa-align-left"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Descripción</h4>
                            <p class="text-xs text-gray-500">Mensaje original del registro</p>
                        </div>
                    </div>
                    <p id="audit-detail-summary" class="mb-3 text-sm font-medium text-gray-900"></p>
                    <p id="audit-detail-description" class="whitespace-pre-wrap break-words text-sm leading-6 text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-4"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('audit-detail-modal');
    const closeBtn = document.getElementById('audit-detail-close');
    let lastFocusedButton = null;

    const fields = {
        date: document.getElementById('audit-detail-date'),
        user: document.getElementById('audit-detail-user'),
        email: document.getElementById('audit-detail-email'),
        action: document.getElementById('audit-detail-action'),
        headerAction: document.getElementById('audit-detail-header-action'),
        securityBadge: document.getElementById('audit-detail-security-badge'),
        module: document.getElementById('audit-detail-module'),
        summary: document.getElementById('audit-detail-summary'),
        description: document.getElementById('audit-detail-description'),
        ip: document.getElementById('audit-detail-ip'),
        affectedUserWrap: document.getElementById('audit-detail-affected-user-wrap'),
        affectedUser: document.getElementById('audit-detail-affected-user'),
        locationSection: document.getElementById('audit-detail-location-section'),
        locationStatus: document.getElementById('audit-detail-location-status'),
        detectedLocation: document.getElementById('audit-detail-detected-location'),
        allowedLocation: document.getElementById('audit-detail-allowed-location'),
        distance: document.getElementById('audit-detail-distance'),
        mapLink: document.getElementById('audit-detail-map-link')
    };

    const actionClasses = {
        login: 'bg-green-100 text-green-800',
        logout: 'bg-gray-100 text-gray-800',
        create: 'bg-blue-100 text-blue-800',
        update: 'bg-yellow-100 text-yellow-800',
        delete: 'bg-red-100 text-red-800',
        login_geo_denied: 'bg-red-100 text-red-800',
        login_failed: 'bg-orange-100 text-orange-800',
        login_blocked: 'bg-red-100 text-red-800'
    };

    const securityActions = ['login_geo_denied', 'login_failed', 'login_blocked'];
    const locationStatusLabels = {
        missing: 'No recibida',
        invalid: 'Inválida',
        inside_range: 'Dentro del área',
        outside_range: 'Fuera del área',
        not_required: 'No requerida'
    };

    function normalizeText(text) {
        return (text || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function getPartValue(parts, label) {
        const normalizedLabel = normalizeText(label) + ':';
        const part = parts.find(function (item) {
            return normalizeText(item).startsWith(normalizedLabel);
        });

        return part ? part.slice(part.indexOf(':') + 1).trim() : '';
    }

    function parseMetadata(value) {
        if (!value) {
            return null;
        }

        try {
            const parsed = JSON.parse(value);
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (error) {
            return null;
        }
    }

    function locationLabel(value) {
        if (!value) {
            return '';
        }

        if (typeof value === 'string') {
            return value;
        }

        if (value.label) {
            return value.label;
        }

        if (value.latitude !== undefined && value.longitude !== undefined) {
            return value.latitude + ', ' + value.longitude;
        }

        return '';
    }

    function parseAuditDescription(description) {
        const parts = (description || '').split('|').map(function (part) {
            return part.trim();
        }).filter(Boolean);

        const summary = parts.find(function (part) {
            const normalized = normalizeText(part);
            return !normalized.startsWith('ubicacion detectada:')
                && !normalized.startsWith('mapa:')
                && !normalized.startsWith('distancia del area permitida:')
                && !normalized.startsWith('ubicacion permitida registrada:');
        }) || description || '-';

        const affectedUserMatch = summary.match(/asesor:\s*(.+)$/i);
        const detectedLocation = getPartValue(parts, 'Ubicacion detectada');

        return {
            summary: summary,
            affectedUser: affectedUserMatch ? affectedUserMatch[1].trim() : '',
            detectedLocation: detectedLocation,
            locationStatus: /no recibida/i.test(detectedLocation) ? 'No recibida' : (/invalid/i.test(detectedLocation) ? 'Inválida' : ''),
            mapUrl: getPartValue(parts, 'Mapa'),
            distance: getPartValue(parts, 'Distancia del area permitida'),
            allowedLocation: getPartValue(parts, 'Ubicacion permitida registrada')
        };
    }

    function buildDetailData(description, metadata) {
        const fallback = parseAuditDescription(description);

        if (!metadata) {
            return fallback;
        }

        return {
            summary: fallback.summary,
            affectedUser: metadata.affected_user || metadata.affected_user_name || fallback.affectedUser,
            detectedLocation: locationLabel(metadata.detected_location) || fallback.detectedLocation,
            locationStatus: locationStatusLabels[metadata.location_status] || metadata.location_status || fallback.locationStatus,
            mapUrl: metadata.map_url || fallback.mapUrl,
            distance: metadata.distance_label || fallback.distance,
            allowedLocation: locationLabel(metadata.allowed_location) || fallback.allowedLocation
        };
    }

    function setActionBadge(action) {
        const badgeClass = actionClasses[action] || 'bg-purple-100 text-purple-800';
        fields.headerAction.className = 'px-2 py-1 text-xs font-medium rounded-full ' + badgeClass;
        fields.headerAction.textContent = action || '-';
        fields.headerAction.classList.remove('hidden');
    }

    function toggleSecurityBadge(action, metadata) {
        const isSecurity = securityActions.includes(action) || Boolean(metadata && metadata.is_security_event);
        fields.securityBadge.classList.toggle('hidden', !isSecurity);
        fields.securityBadge.textContent = isSecurity ? 'Evento crítico' : '';
    }

    function setOptionalText(element, value) {
        element.textContent = value || '-';
    }

    function toggleAffectedUser(value) {
        if (value) {
            fields.affectedUser.textContent = value;
            fields.affectedUserWrap.classList.remove('hidden');
            return;
        }

        fields.affectedUser.textContent = '';
        fields.affectedUserWrap.classList.add('hidden');
    }

    function toggleLocation(detail) {
        const hasLocationData = Boolean(detail.locationStatus || detail.detectedLocation || detail.allowedLocation || detail.distance || detail.mapUrl);
        fields.locationSection.classList.toggle('hidden', !hasLocationData);

        if (!hasLocationData) {
            fields.mapLink.classList.add('hidden');
            fields.mapLink.removeAttribute('href');
            return;
        }

        setOptionalText(fields.locationStatus, detail.locationStatus);
        setOptionalText(fields.detectedLocation, detail.detectedLocation);
        setOptionalText(fields.allowedLocation, detail.allowedLocation);
        setOptionalText(fields.distance, detail.distance);

        if (/^https?:\/\/(www\.)?google\.com\/maps/i.test(detail.mapUrl)) {
            fields.mapLink.href = detail.mapUrl;
            fields.mapLink.classList.remove('hidden');
        } else {
            fields.mapLink.classList.add('hidden');
            fields.mapLink.removeAttribute('href');
        }
    }

    function openDetail(button) {
        const action = button.dataset.action || '-';
        const description = button.dataset.description || '-';
        const metadata = parseMetadata(button.dataset.metadata || '');
        const detail = buildDetailData(description, metadata);

        lastFocusedButton = button;
        fields.date.textContent = button.dataset.date || '';
        fields.user.textContent = button.dataset.user || 'Sistema';
        fields.email.textContent = button.dataset.email || '';
        fields.action.textContent = action;
        fields.module.textContent = button.dataset.module || '-';
        fields.summary.textContent = detail.summary;
        fields.description.textContent = description;
        fields.ip.textContent = button.dataset.ip || '-';
        setActionBadge(action);
        toggleSecurityBadge(action, metadata);
        toggleAffectedUser(detail.affectedUser);
        toggleLocation(detail);
        modal.classList.remove('hidden');
        closeBtn.focus();
    }

    function closeDetail() {
        modal.classList.add('hidden');
        if (lastFocusedButton) {
            lastFocusedButton.focus();
        }
    }

    document.querySelectorAll('.audit-detail-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            openDetail(button);
        });
    });

    closeBtn.addEventListener('click', closeDetail);
    modal.querySelectorAll('[data-audit-detail-close]').forEach(function (element) {
        element.addEventListener('click', closeDetail);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeDetail();
        }
    });
});
</script>
<?php $content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
