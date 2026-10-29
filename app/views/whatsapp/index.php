<?php
$title = 'Conversaciones WhatsApp';
ob_start();

function waStatusBadge($status, $mode) {
    if ($status === 'WAITING_HUMAN') return ['En espera de asesora', 'bg-orange-100 text-orange-700', 'fa-clock'];
    if ($status === 'IN_PROGRESS') return ['Atención humana', 'bg-green-100 text-green-700', 'fa-headset'];
    if ($status === 'CLOSED') return ['Cerrada', 'bg-gray-200 text-gray-600', 'fa-lock'];
    return ['Atendida por bot', 'bg-blue-100 text-blue-700', 'fa-robot'];
}

function waSenderStyle($senderType) {
    if ($senderType === 'USER') return ['justify-start', 'bg-white border border-gray-200 text-gray-800', 'Cliente'];
    if ($senderType === 'BOT') return ['justify-end', 'bg-blue-50 border border-blue-100 text-gray-800', 'Bot'];
    if ($senderType === 'ADMIN') return ['justify-end', 'bg-purple-50 border border-purple-100 text-gray-800', 'Administrador'];
    return ['justify-end', 'bg-green-50 border border-green-100 text-gray-800', 'Asesora'];
}
?>

<div class="space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fab fa-whatsapp text-green-600"></i> Conversaciones WhatsApp
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                <?= $isAdmin
                    ? 'Vista administrativa completa: bot, solicitudes de atención humana, asesoras e historial de intervenciones.'
                    : 'Su cartera de clientes asignados automáticamente. Los clientes permanecen en su lista aunque la conversación vuelva al bot.' ?>
            </p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-center text-xs">
            <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/whatsapp?filter=bot" class="bg-white border rounded-lg px-3 py-2 hover:border-blue-300">
                <span class="block text-xl font-bold text-blue-600"><?= (int)$stats['bot'] ?></span>Con bot
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/whatsapp?filter=waiting" class="bg-white border rounded-lg px-3 py-2 hover:border-orange-300">
                <span class="block text-xl font-bold text-orange-600"><?= (int)$stats['waiting'] ?></span>En espera
            </a>
            <a href="<?= BASE_URL ?>/whatsapp?filter=<?= $isAdmin ? 'active' : 'mine' ?>" class="bg-white border rounded-lg px-3 py-2 hover:border-green-300">
                <span class="block text-xl font-bold text-green-600"><?= (int)($isAdmin ? $stats['active'] : $stats['mine']) ?></span><?= $isAdmin ? 'Humanas' : 'Mis clientes' ?>
            </a>
            <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/whatsapp?filter=closed" class="bg-white border rounded-lg px-3 py-2 hover:border-gray-400">
                <span class="block text-xl font-bold text-gray-600"><?= (int)$stats['closed'] ?></span>Cerradas
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="rounded-lg border px-4 py-3 text-sm <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <?php $qSuffix = $search !== '' ? '&q=' . urlencode($search) : ''; ?>
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="<?= BASE_URL ?>/whatsapp<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>" class="px-3 py-2 rounded-lg border transition <?= $filter === '' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-white text-gray-700 hover:border-gray-300' ?>"><?= $isAdmin ? 'Todos permitidos' : 'Todos mis clientes' ?></a>
        <a href="<?= BASE_URL ?>/whatsapp?filter=waiting<?= $qSuffix ?>" class="px-3 py-2 rounded-lg border transition <?= $filter === 'waiting' ? 'bg-orange-500 text-white border-orange-500 shadow-sm' : 'bg-white text-gray-700 hover:border-orange-200' ?>">Esperando asesora</a>
        <?php if ($isAdmin): ?>
        <a href="<?= BASE_URL ?>/whatsapp?filter=bot<?= $qSuffix ?>" class="px-3 py-2 rounded-lg border transition <?= $filter === 'bot' ? 'bg-blue-500 text-white border-blue-500 shadow-sm' : 'bg-white text-gray-700 hover:border-blue-200' ?>">Solo bot</a>
        <a href="<?= BASE_URL ?>/whatsapp?filter=active<?= $qSuffix ?>" class="px-3 py-2 rounded-lg border transition <?= $filter === 'active' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white text-gray-700 hover:border-green-200' ?>">Atención humana</a>
        <a href="<?= BASE_URL ?>/whatsapp?filter=closed<?= $qSuffix ?>" class="px-3 py-2 rounded-lg border transition <?= $filter === 'closed' ? 'bg-gray-700 text-white border-gray-700 shadow-sm' : 'bg-white text-gray-700 hover:border-gray-300' ?>">Cerradas</a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/whatsapp?filter=mine<?= $qSuffix ?>" class="px-3 py-2 rounded-lg border transition <?= $filter === 'mine' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white text-gray-700 hover:border-green-200' ?>">Mis conversaciones</a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-start">
        <!-- Lista -->
        <section class="xl:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b bg-gradient-to-b from-gray-50 to-white space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-gray-900 flex items-center gap-2"><i class="fas fa-inbox text-primary"></i>Bandeja</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <?= (int)$totalConversations ?> conversación<?= (int)$totalConversations === 1 ? '' : 'es' ?> encontrada<?= (int)$totalConversations === 1 ? '' : 's' ?>
                        </p>
                    </div>
                    <button type="button" onclick="location.reload()" class="w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-500 hover:text-primary hover:border-primary/30 transition" title="Actualizar">
                        <i class="fas fa-rotate"></i>
                    </button>
                </div>

                <form method="GET" action="<?= BASE_URL ?>/whatsapp" class="relative">
                    <?php if ($filter !== ''): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
                    <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" maxlength="100"
                           class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-20 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/10 outline-none"
                           placeholder="Buscar nombre, teléfono o trámite..." autocomplete="off">
                    <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 bg-primary hover:opacity-90 text-white rounded-lg px-3 py-1.5 text-xs font-semibold">Buscar</button>
                </form>

                <?php if ($search !== ''): ?>
                <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="text-gray-500 truncate">Resultados para <strong class="text-gray-700">“<?= htmlspecialchars($search) ?>”</strong></span>
                    <a href="<?= BASE_URL ?>/whatsapp<?= $filter !== '' ? '?filter=' . urlencode($filter) : '' ?>" class="text-primary font-semibold hover:underline whitespace-nowrap">Limpiar</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="max-h-[72vh] overflow-y-auto bg-gray-50/40">
                <?php if (empty($conversations)): ?>
                <div class="p-10 text-center text-gray-500 text-sm">
                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                        <i class="far fa-comments text-2xl text-gray-300"></i>
                    </div>
                    <p class="font-semibold text-gray-700">No encontramos conversaciones</p>
                    <p class="text-xs text-gray-400 mt-1"><?= $search !== '' ? 'Pruebe con otro nombre, teléfono o trámite.' : 'No hay conversaciones para este filtro.' ?></p>
                </div>
                <?php endif; ?>

                <div class="p-2 space-y-2">
                    <?php foreach ($conversations as $conv): ?>
                    <?php
                        [$statusLabel, $statusClass, $statusIcon] = waStatusBadge($conv['status'], $conv['mode']);
                        $displayName = trim((string)($conv['profile_name'] ?: $conv['phone']));
                        $initial = mb_strtoupper(mb_substr($displayName !== '' ? $displayName : '?', 0, 1));
                        $isSelected = $selected && (int)$selected['id'] === (int)$conv['id'];
                    ?>
                    <a href="<?= BASE_URL ?>/whatsapp?filter=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>&page=<?= (int)$page ?>&conversation=<?= (int)$conv['id'] ?>"
                       class="block rounded-xl border p-3 transition <?= $isSelected ? 'bg-white border-primary shadow-sm ring-1 ring-primary/10' : 'bg-white border-gray-100 hover:border-gray-300 hover:shadow-sm' ?>">
                        <div class="flex gap-3 min-w-0">
                            <div class="relative flex-shrink-0">
                                <div class="w-10 h-10 rounded-full <?= $conv['status'] === 'WAITING_HUMAN' ? 'bg-orange-100 text-orange-700' : ($conv['status'] === 'IN_PROGRESS' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') ?> flex items-center justify-center font-bold text-sm">
                                    <?= htmlspecialchars($initial) ?>
                                </div>
                                <?php if ($conv['status'] === 'WAITING_HUMAN'): ?>
                                <span class="absolute -right-0.5 -bottom-0.5 w-3 h-3 rounded-full bg-orange-500 border-2 border-white animate-pulse" title="Esperando atención"></span>
                                <?php endif; ?>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm text-gray-900 truncate"><?= htmlspecialchars($displayName) ?></p>
                                        <p class="text-[11px] text-gray-400 truncate">+<?= htmlspecialchars($conv['phone']) ?></p>
                                    </div>
                                    <span class="text-[10px] text-gray-400 whitespace-nowrap pt-0.5"><?= $conv['last_message_at'] ? date('d/m H:i', strtotime($conv['last_message_at'])) : '' ?></span>
                                </div>

                                <p class="text-xs font-medium text-gray-600 mt-1 truncate"><i class="fas fa-passport text-gray-300 mr-1"></i><?= htmlspecialchars($conv['service_name'] ?: 'Sin trámite') ?></p>
                                <p class="text-xs text-gray-500 mt-1.5 truncate"><?= htmlspecialchars($conv['last_message_text'] ?: 'Sin mensajes de texto') ?></p>

                                <div class="flex items-center justify-between gap-2 mt-2.5">
                                    <span class="inline-flex items-center gap-1 text-[10px] px-2 py-1 rounded-full <?= $statusClass ?>">
                                        <i class="fas <?= $statusIcon ?>"></i><?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                    <?php if (!empty($conv['assigned_agent_name'])): ?>
                                    <span class="text-[10px] text-green-700 truncate max-w-[45%]" title="<?= htmlspecialchars($conv['assigned_agent_name']) ?>"><i class="fas fa-user-headset mr-1"></i><?= htmlspecialchars($conv['assigned_agent_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="px-3 py-3 border-t bg-white flex items-center justify-between gap-2">
                <span class="text-[11px] text-gray-500">Página <?= (int)$page ?> de <?= (int)$totalPages ?></span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                    <a href="<?= BASE_URL ?>/whatsapp?filter=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>&page=<?= (int)$page - 1 ?>" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:border-primary hover:text-primary" title="Página anterior"><i class="fas fa-chevron-left text-xs"></i></a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="<?= BASE_URL ?>/whatsapp?filter=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>&page=<?= (int)$page + 1 ?>" class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:border-primary hover:text-primary" title="Página siguiente"><i class="fas fa-chevron-right text-xs"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Conversación -->
        <section class="xl:col-span-5 bg-white rounded-xl shadow border border-gray-100 overflow-hidden min-h-[72vh] flex flex-col">
            <?php if (!$selected): ?>
            <div class="flex-1 flex items-center justify-center p-10 text-center text-gray-500">
                <div><i class="far fa-comments text-5xl text-gray-200 mb-4"></i><p>Seleccione una conversación para consultar su historial.</p></div>
            </div>
            <?php else: ?>
            <?php
                [$selectedStatusLabel, $selectedStatusClass, $selectedStatusIcon] = waStatusBadge($selected['status'], $selected['mode']);
                $isAssignedToCurrent = (int)($selected['assigned_agent_id'] ?? 0) === $currentUserId;
                $canTake = $selected['status'] === 'WAITING_HUMAN'
                    || ($selected['status'] === 'OPEN' && $selected['mode'] === 'BOT' && ($isAdmin || $isAssignedToCurrent));
                $canReply = $selected['status'] === 'IN_PROGRESS' && ($isAdmin || $isAssignedToCurrent);
                $canRelease = $canReply;
                $canSendTemplate = $selected['status'] !== 'CLOSED' && ($isAdmin || $isAssignedToCurrent);
            ?>
            <div class="p-4 border-b bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-bold text-gray-900 truncate"><?= htmlspecialchars($selected['profile_name'] ?: $selected['phone']) ?></h2>
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full <?= $selectedStatusClass ?>">
                            <i class="fas <?= $selectedStatusIcon ?>"></i><?= htmlspecialchars($selectedStatusLabel) ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">+<?= htmlspecialchars($selected['phone']) ?> · <?= htmlspecialchars($selected['service_name'] ?: 'Sin servicio seleccionado') ?></p>
                    <?php if ($selected['assigned_agent_name']): ?>
                    <p class="text-xs <?= $selected['mode'] === 'BOT' ? 'text-blue-700' : 'text-green-700' ?> mt-1">
                        <i class="fas fa-user-check mr-1"></i>Cliente asignado a <?= htmlspecialchars($selected['assigned_agent_name']) ?>
                        <?= $selected['mode'] === 'BOT' ? ' · Bot activo' : ' · Atención humana activa' ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php if ($canTake): ?>
                    <form method="POST" action="<?= BASE_URL ?>/whatsapp/tomar/<?= (int)$selected['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-headset mr-1"></i><?= $selected['status'] === 'OPEN' ? 'Retomar atención' : 'Tomar conversación' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($canRelease): ?>
                    <form method="POST" action="<?= BASE_URL ?>/whatsapp/devolver-bot/<?= (int)$selected['id'] ?>" onsubmit="return confirm('¿Devolver esta conversación al bot? El historial se conservará.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm">
                            <i class="fas fa-robot mr-1"></i>Devolver al bot
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($isAdmin && $selected['status'] !== 'CLOSED'): ?>
                    <form method="POST" action="<?= BASE_URL ?>/whatsapp/cerrar/<?= (int)$selected['id'] ?>" onsubmit="return confirm('¿Cerrar la conversación? No se eliminarán mensajes ni intervenciones.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button class="bg-gray-700 hover:bg-gray-800 text-white px-3 py-2 rounded-lg text-sm">
                            <i class="fas fa-lock mr-1"></i>Cerrar
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($selected['status'] === 'WAITING_HUMAN' && $selected['handoff_reason']): ?>
            <div class="mx-4 mt-4 bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 text-sm text-orange-800">
                <i class="fas fa-bell mr-2"></i><strong>Motivo del handoff:</strong> <?= htmlspecialchars($selected['handoff_reason']) ?>
            </div>
            <?php endif; ?>

            <div id="wa-message-container" class="flex-1 p-4 md:p-5 bg-gray-50 space-y-3 overflow-y-auto max-h-[54vh] min-h-[420px]">
                <?php foreach ($messages as $message): ?>
                <?php [$justify, $bubble, $senderDefault] = waSenderStyle($message['sender_type']); ?>
                <div class="flex <?= $justify ?>" data-message-id="<?= (int)$message['id'] ?>">
                    <div class="max-w-[88%] md:max-w-[78%] rounded-2xl px-4 py-3 shadow-sm <?= $bubble ?>">
                        <div class="flex items-center justify-between gap-4 mb-1">
                            <span class="text-[11px] font-bold <?= $message['sender_type'] === 'USER' ? 'text-gray-600' : ($message['sender_type'] === 'BOT' ? 'text-blue-700' : 'text-green-700') ?>">
                                <?= htmlspecialchars($message['sender_name'] ?: $senderDefault) ?>
                                <?php if ($message['sender_type'] === 'BOT'): ?><i class="fas fa-robot ml-1"></i><?php endif; ?>
                            </span>
                            <span class="text-[10px] text-gray-400"><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></span>
                        </div>
                        <p class="text-sm whitespace-pre-wrap break-words"><?= nl2br(htmlspecialchars($message['message_text'] ?: '[' . $message['message_type'] . ']')) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                <p class="text-center text-gray-400 text-sm py-10">Esta conversación todavía no tiene mensajes guardados.</p>
                <?php endif; ?>
            </div>

            <div class="border-t p-4 bg-white space-y-3">
                <?php if ($canReply): ?>
                <form method="POST" action="<?= BASE_URL ?>/whatsapp/enviar/<?= (int)$selected['id'] ?>" class="flex gap-2 items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="flex-1">
                        <label for="message" class="block text-xs font-semibold text-gray-600 mb-1">Responder como <?= htmlspecialchars($_SESSION['user_name'] ?? 'asesora') ?></label>
                        <textarea id="message" name="message" rows="2" maxlength="4000" required
                                  class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-200 focus:border-green-500 resize-none"
                                  placeholder="Escriba su respuesta. Los mensajes enviados quedan registrados y no pueden editarse ni borrarse."></textarea>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white rounded-xl px-4 py-3 font-medium">
                        <i class="fas fa-paper-plane"></i><span class="hidden md:inline ml-2">Enviar</span>
                    </button>
                </form>
                <?php elseif ($selected['status'] === 'WAITING_HUMAN'): ?>
                <div class="text-center text-sm text-orange-700 bg-orange-50 border border-orange-100 rounded-lg p-3">
                    Tome la conversación antes de responder. El bot ya está pausado para este usuario.
                </div>
                <?php elseif ($selected['status'] === 'OPEN'): ?>
                <div class="text-center text-sm text-blue-700 bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <i class="fas fa-robot mr-1"></i>
                    El bot está activo.
                    <?php if ($isAssignedToCurrent): ?>
                        Este cliente sigue asignado a usted. Puede retomar la atención humana o enviarle una plantilla de seguimiento.
                    <?php elseif ($isAdmin && $selected['assigned_agent_name']): ?>
                        El cliente permanece asignado a <?= htmlspecialchars($selected['assigned_agent_name']) ?>.
                    <?php else: ?>
                        Puede consultar el historial completo desde administración.
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="text-center text-sm text-gray-600 bg-gray-100 rounded-lg p-3">Conversación cerrada. El historial permanece disponible para administración.</div>
                <?php endif; ?>

                <?php if ($canSendTemplate): ?>
                <details class="rounded-xl border border-emerald-200 bg-emerald-50/60 group" <?= $selected['mode'] === 'BOT' ? 'open' : '' ?>>
                    <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between gap-3 text-sm font-semibold text-emerald-800">
                        <span><i class="fas fa-message mr-2"></i>Enviar plantilla de seguimiento de Meta</span>
                        <span class="text-[11px] font-normal text-emerald-700">seguimiento_tramite_visas</span>
                    </summary>
                    <form method="POST" action="<?= BASE_URL ?>/whatsapp/plantilla/<?= (int)$selected['id'] ?>" class="px-4 pb-4 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="rounded-lg bg-white border border-emerald-100 p-3 text-xs text-gray-600">
                            <p><strong>{{1}}</strong> se llenará automáticamente con: <strong><?= htmlspecialchars($selected['profile_name'] ?: 'cliente') ?></strong></p>
                            <p class="mt-1"><strong>{{2}}</strong> será el mensaje que escriba a continuación.</p>
                        </div>

                        <div>
                            <label for="template_message" class="block text-xs font-semibold text-gray-700 mb-1">Mensaje de seguimiento</label>
                            <textarea id="template_message" name="template_message" rows="3" maxlength="1000" required
                                      class="w-full border border-emerald-200 bg-white rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 resize-none"
                                      placeholder="Ejemplo: Nos comunicamos para dar seguimiento a su trámite de visa."></textarea>
                        </div>

                        <div class="text-[11px] text-gray-500 leading-relaxed">
                            Vista del mensaje:
                            <div class="mt-1 bg-white rounded-lg border border-gray-200 p-3 text-gray-700">
                                Hola <?= htmlspecialchars($selected['profile_name'] ?: 'cliente') ?> 👋<br><br>
                                <span class="text-gray-400">[Su mensaje de seguimiento]</span><br><br>
                                Esperamos que tenga un excelente día. Si necesita apoyo o desea continuar con su trámite, estamos a sus órdenes.
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-4 py-2.5 text-sm font-semibold"
                                onclick="return confirm('¿Enviar la plantilla de seguimiento a este cliente?');">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar plantilla
                        </button>
                    </form>
                </details>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Contexto -->
        <aside class="xl:col-span-3 space-y-4">
            <?php if ($selected): ?>
            <div class="bg-white rounded-xl shadow border border-gray-100 p-4">
                <h3 class="font-bold text-gray-800 mb-3"><i class="fas fa-address-card text-primary mr-2"></i>Contexto del contacto</h3>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-xs text-gray-500">WhatsApp</dt><dd class="font-medium">+<?= htmlspecialchars($selected['phone']) ?></dd></div>
                    <div><dt class="text-xs text-gray-500">Servicio</dt><dd class="font-medium"><?= htmlspecialchars($selected['service_name'] ?: 'Sin seleccionar') ?></dd></div>
                    <div><dt class="text-xs text-gray-500">Tipo</dt><dd><?= htmlspecialchars($selected['contact_type']) ?></dd></div>
                    <div><dt class="text-xs text-gray-500">Estado del lead</dt><dd><?= htmlspecialchars($selected['lead_status']) ?></dd></div>
                    <div><dt class="text-xs text-gray-500">Modo actual</dt><dd class="font-medium"><?= $selected['mode'] === 'HUMAN' ? 'Atención humana' : 'Bot' ?></dd></div>
                    <div><dt class="text-xs text-gray-500">Encargada asignada</dt><dd class="font-medium"><?= htmlspecialchars($selected['assigned_agent_name'] ?: 'Sin asignar') ?></dd></div>
                    <?php if (!empty($selected['application_id'])): ?>
                    <div><dt class="text-xs text-gray-500">Solicitud vinculada</dt><dd><a class="text-primary hover:underline" href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$selected['application_id'] ?>">Ver solicitud #<?= (int)$selected['application_id'] ?></a></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-100 p-4">
                <h3 class="font-bold text-gray-800 mb-1">
                    <i class="fas fa-file-circle-plus text-emerald-600 mr-2"></i>Iniciar trámite formal
                </h3>
                <p class="text-xs text-gray-500 mb-3">
                    Convierte un perfilamiento del bot en una Solicitud. El historial de WhatsApp se conserva y la nueva solicitud queda asignada a la misma asesora.
                </p>

                <?php if (!$formalizationReady): ?>
                <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-3 py-2 text-xs">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    Falta instalar la migración <strong>08_whatsapp_a_solicitudes_v4.sql</strong>.
                </div>
                <?php elseif (empty($formalizationServices)): ?>
                <p class="text-sm text-gray-500">Todavía no hay un trámite del bot listo para convertir en solicitud.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($formalizationServices as $formal): ?>
                    <div class="rounded-xl border <?= !empty($formal['link']) ? 'border-green-200 bg-green-50/50' : 'border-gray-200' ?> p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($formal['service_name'] ?: $formal['service_code']) ?></p>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    <?= (int)($formal['answer_count'] ?? 0) ?> respuesta<?= (int)($formal['answer_count'] ?? 0) === 1 ? '' : 's' ?> del bot
                                </p>
                                <?php if (($formal['commercial_interest'] ?? '') === 'SI'): ?>
                                <span class="inline-flex mt-1 text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                                    Cliente quiere continuar
                                </span>
                                <?php elseif (($formal['commercial_interest'] ?? '') === 'NO'): ?>
                                <span class="inline-flex mt-1 text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-semibold">
                                    No por ahora
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($formal['link'])): ?>
                            <span class="text-[10px] px-2 py-1 rounded-full bg-green-100 text-green-800 font-bold whitespace-nowrap">
                                Solicitud creada
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($formal['link'])): ?>
                            <div class="mt-3 rounded-lg bg-white border border-green-200 p-3">
                                <p class="text-xs text-gray-500">Expediente vinculado</p>
                                <p class="font-bold text-green-800"><?= htmlspecialchars($formal['link']['folio']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($formal['link']['status'] ?? '') ?></p>
                                <a href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$formal['link']['application_id'] ?>"
                                   class="inline-flex items-center mt-2 text-sm font-semibold text-green-700 hover:underline">
                                    <i class="fas fa-arrow-up-right-from-square mr-1"></i>Abrir solicitud
                                </a>
                            </div>
                        <?php elseif (!empty($formal['forms'])): ?>
                            <form method="POST"
                                  action="<?= BASE_URL ?>/whatsapp/crear-solicitud/<?= (int)$selected['id'] ?>"
                                  class="mt-3 space-y-2"
                                  onsubmit="return confirm('¿Crear la solicitud formal con los datos que ya obtuvo el bot?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="service_code" value="<?= htmlspecialchars($formal['service_code']) ?>">

                                <?php if (count($formal['forms']) === 1): ?>
                                    <?php $onlyForm = $formal['forms'][0]; ?>
                                    <input type="hidden" name="form_id" value="<?= (int)$onlyForm['id'] ?>">
                                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2">
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-bold">Formulario que se utilizará</p>
                                        <p class="text-xs font-semibold text-gray-700 mt-0.5"><?= htmlspecialchars($onlyForm['name']) ?></p>
                                    </div>
                                <?php else: ?>
                                    <label class="block text-[11px] font-bold text-gray-600">Seleccione el formulario correcto</label>
                                    <select name="form_id" required class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 bg-white">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($formal['forms'] as $form): ?>
                                        <option value="<?= (int)$form['id'] ?>">
                                            <?= htmlspecialchars($form['name']) ?>
                                            <?= !empty($form['subtype']) ? ' · ' . htmlspecialchars($form['subtype']) : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <button type="submit"
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-2 text-sm font-bold">
                                    <i class="fas fa-file-circle-plus mr-2"></i>Crear solicitud
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="mt-3 rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-600">
                                <i class="fas fa-circle-info mr-1"></i>
                                <?= htmlspecialchars($formal['compatibility_note'] ?? 'No existe todavía un formulario publicado compatible con este servicio. Primero debe configurarse en Formularios.') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-100 p-4">
                <h3 class="font-bold text-gray-800 mb-3"><i class="fas fa-list-check text-blue-600 mr-2"></i>Primer contacto</h3>
                <?php if (empty($profileAnswers)): ?>
                <p class="text-sm text-gray-500">Aún no hay respuestas de perfilamiento registradas.</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php $lastProfileService = null; ?>
                    <?php foreach ($profileAnswers as $answer): ?>
                        <?php if ($lastProfileService !== $answer['service_code']): ?>
                            <?php if ($lastProfileService !== null): ?></div></div><?php endif; ?>
                            <div class="rounded-lg border border-blue-100 overflow-hidden">
                                <div class="bg-blue-50 px-3 py-2 border-b border-blue-100">
                                    <p class="text-xs font-bold text-blue-900">
                                        <i class="fas fa-passport mr-1"></i><?= htmlspecialchars($answer['service_name']) ?>
                                        <?php if (($selected['service_code'] ?? '') === $answer['service_code']): ?>
                                            <span class="ml-1 text-[10px] bg-blue-600 text-white rounded-full px-2 py-0.5">Actual</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="p-3 space-y-3">
                            <?php $lastProfileService = $answer['service_code']; ?>
                        <?php endif; ?>
                        <div class="border-b border-gray-100 pb-2 last:border-0">
                            <p class="text-[11px] text-gray-500 leading-snug"><?= htmlspecialchars($answer['question_label']) ?></p>
                            <p class="text-sm font-semibold text-gray-800 mt-1"><?= htmlspecialchars($answer['answer_label'] ?: $answer['answer_value']) ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($lastProfileService !== null): ?></div></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-100 p-4">
                <h3 class="font-bold text-gray-800 mb-3"><i class="fas fa-clock-rotate-left text-purple-600 mr-2"></i>Intervenciones</h3>
                <?php if (empty($events)): ?>
                <p class="text-sm text-gray-500">Sin eventos de atención registrados.</p>
                <?php else: ?>
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    <?php foreach ($events as $event): ?>
                    <div class="border-l-2 border-gray-200 pl-3">
                        <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($event['detail'] ?: $event['event_type']) ?></p>
                        <p class="text-[10px] text-gray-400 mt-1">
                            <?= htmlspecialchars($event['actor_name'] ?: 'Sistema') ?><?= $event['actor_role'] ? ' · ' . htmlspecialchars($event['actor_role']) : '' ?> · <?= date('d/m/Y H:i', strtotime($event['created_at'])) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-xs text-gray-600">
                <p class="font-bold text-gray-700 mb-2"><i class="fas fa-shield-halved mr-1"></i>Historial protegido</p>
                <p>Las asesoras pueden tomar, responder y devolver al bot sus conversaciones sin perder la asignación del cliente. También pueden enviar la plantilla de seguimiento a sus clientes asignados. No existen acciones para editar ni borrar mensajes o conversaciones. El administrador conserva visibilidad completa.</p>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php if ($selected): ?>
<script>
(function () {
    const container = document.getElementById('wa-message-container');
    if (container) container.scrollTop = container.scrollHeight;

    const conversationId = <?= (int)$selected['id'] ?>;
    let latestMessageId = <?= empty($messages) ? 0 : (int)end($messages)['id'] ?>;

    const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
    const senderClasses = (type) => {
        if (type === 'USER') return ['justify-start', 'bg-white border border-gray-200 text-gray-800', 'text-gray-600'];
        if (type === 'BOT') return ['justify-end', 'bg-blue-50 border border-blue-100 text-gray-800', 'text-blue-700'];
        if (type === 'ADMIN') return ['justify-end', 'bg-purple-50 border border-purple-100 text-gray-800', 'text-purple-700'];
        return ['justify-end', 'bg-green-50 border border-green-100 text-gray-800', 'text-green-700'];
    };

    async function pollConversation() {
        try {
            const response = await fetch(`<?= BASE_URL ?>/whatsapp/api/conversacion/${conversationId}`, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            if (!data.ok || !Array.isArray(data.messages) || !container) return;
            const newMessages = data.messages.filter(m => Number(m.id) > latestMessageId);
            if (!newMessages.length) return;
            const nearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 120;
            for (const m of newMessages) {
                const [justify, bubble, labelClass] = senderClasses(m.sender_type);
                const row = document.createElement('div');
                row.className = `flex ${justify}`;
                row.dataset.messageId = m.id;
                const date = new Date(String(m.created_at).replace(' ', 'T'));
                const stamp = Number.isNaN(date.getTime()) ? esc(m.created_at) : date.toLocaleString('es-MX', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
                row.innerHTML = `<div class="max-w-[88%] md:max-w-[78%] rounded-2xl px-4 py-3 shadow-sm ${bubble}">
                    <div class="flex items-center justify-between gap-4 mb-1"><span class="text-[11px] font-bold ${labelClass}">${esc(m.sender_name || m.sender_type)}</span><span class="text-[10px] text-gray-400">${stamp}</span></div>
                    <p class="text-sm whitespace-pre-wrap break-words">${esc(m.message_text || '[' + m.message_type + ']')}</p></div>`;
                container.appendChild(row);
                latestMessageId = Math.max(latestMessageId, Number(m.id));
            }
            if (nearBottom) container.scrollTop = container.scrollHeight;
        } catch (_) {}
    }

    setInterval(pollConversation, 6000);
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
