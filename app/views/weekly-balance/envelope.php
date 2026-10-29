<?php
$title = 'Movimientos de sobre';
ob_start();
function envMoney($value) { return '$' . number_format((float)$value, 2); }
?>

<div class="mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <a href="<?= BASE_URL ?>/balance-semanal" class="text-sm text-blue-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Volver al Balance Semanal</a>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mt-2"><?= htmlspecialchars($envelope['name']) ?></h2>
        <p class="text-gray-500 text-sm">Historial completo del fondo. Los movimientos no alteran saldos manualmente; construyen el saldo mediante entradas y salidas.</p>
    </div>
    <div class="flex gap-2">
        <span class="px-3 py-2 rounded-lg text-sm font-semibold <?= $envelope['status']==='ACTIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' ?>"><?= htmlspecialchars($envelope['status']) ?></span>
        <?php if (!empty($envelope['is_free_cash'])): ?><span class="px-3 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-800">Caja libre</span><?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Saldo inicial</p><p class="text-2xl font-bold text-indigo-700 mt-1"><?= envMoney($envelope['opening_balance']) ?></p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Fecha apertura</p><p class="text-lg font-bold text-gray-800 mt-1"><?= htmlspecialchars($envelope['opened_at']) ?></p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Creado por</p><p class="text-lg font-bold text-gray-800 mt-1"><?= htmlspecialchars($envelope['created_by_name'] ?? '-') ?></p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Cierre</p><p class="text-lg font-bold text-gray-800 mt-1"><?= htmlspecialchars($envelope['closed_at'] ?? 'Activo') ?></p></div>
</div>

<div class="bg-white rounded-xl shadow mb-5 overflow-hidden">
    <div class="px-5 py-4 border-b"><h3 class="text-lg font-bold text-gray-800">Movimientos</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1000px]">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-3 py-3 text-left">Fecha</th><th class="px-3 py-3 text-left">Tipo</th><th class="px-3 py-3 text-right">Cantidad</th><th class="px-3 py-3 text-left">Concepto</th><th class="px-3 py-3 text-left">Cliente / Referencia</th><th class="px-3 py-3 text-left">Usuario</th><th class="px-3 py-3 text-left">Observaciones</th></tr></thead>
            <tbody class="divide-y">
                <?php foreach ($movements as $m): $isEntry = in_array($m['movement_type'], ['ENTRADA','TRASPASO_ENTRADA','AJUSTE_ENTRADA']); ?>
                <tr>
                    <td class="px-3 py-3 whitespace-nowrap"><?= htmlspecialchars($m['movement_date']) ?></td>
                    <td class="px-3 py-3"><span class="px-2 py-1 rounded text-xs font-bold <?= $isEntry ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= htmlspecialchars($m['movement_type']) ?></span></td>
                    <td class="px-3 py-3 text-right font-bold <?= $isEntry ? 'text-green-700' : 'text-red-700' ?>"><?= $isEntry ? '+' : '-' ?><?= envMoney($m['amount']) ?></td>
                    <td class="px-3 py-3"><div class="font-medium text-gray-800"><?= htmlspecialchars($m['concept']) ?></div><div class="text-xs text-gray-400"><?= htmlspecialchars($m['source_type']) ?></div></td>
                    <td class="px-3 py-3"><?= htmlspecialchars($m['client_name'] ?: '-') ?><?= $m['reference'] ? '<div class="text-xs text-gray-500">Ref. ' . htmlspecialchars($m['reference']) . '</div>' : '' ?></td>
                    <td class="px-3 py-3"><?= htmlspecialchars($m['user_name'] ?: '-') ?></td>
                    <td class="px-3 py-3"><?= nl2br(htmlspecialchars($m['notes'] ?: '-')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$movements): ?><tr><td colspan="7" class="p-8 text-center text-gray-500">Este sobre aún no tiene movimientos.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-5 py-4 border-b"><h3 class="text-lg font-bold text-gray-800">Arqueos semanales</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-3 py-3 text-left">Periodo</th><th class="px-3 py-3 text-right">Saldo anterior</th><th class="px-3 py-3 text-right">Entradas</th><th class="px-3 py-3 text-right">Salidas</th><th class="px-3 py-3 text-right">Teórico</th><th class="px-3 py-3 text-right">Contado</th><th class="px-3 py-3 text-right">Diferencia</th><th class="px-3 py-3 text-left">Observaciones</th></tr></thead>
            <tbody class="divide-y">
                <?php foreach ($counts as $c): ?>
                <tr>
                    <td class="px-3 py-3 whitespace-nowrap"><?= htmlspecialchars($c['week_start']) ?> al <?= htmlspecialchars($c['week_end']) ?></td>
                    <td class="px-3 py-3 text-right"><?= envMoney($c['opening_balance']) ?></td>
                    <td class="px-3 py-3 text-right text-green-700">+<?= envMoney($c['entries']) ?></td>
                    <td class="px-3 py-3 text-right text-red-700">-<?= envMoney($c['exits']) ?></td>
                    <td class="px-3 py-3 text-right font-bold"><?= envMoney($c['theoretical_balance']) ?></td>
                    <td class="px-3 py-3 text-right font-bold"><?= $c['counted_cash'] === null ? 'Pendiente' : envMoney($c['counted_cash']) ?></td>
                    <td class="px-3 py-3 text-right font-bold <?= abs((float)$c['difference']) < 0.01 ? 'text-green-700' : 'text-red-700' ?>"><?= $c['difference'] === null ? '-' : envMoney($c['difference']) ?></td>
                    <td class="px-3 py-3"><?= nl2br(htmlspecialchars($c['observations'] ?: '-')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$counts): ?><tr><td colspan="8" class="p-8 text-center text-gray-500">Sin arqueos guardados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
