<?php
$title='Egresos';
ob_start();
?>
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Egresos</h2>
        <p class="text-gray-600">Todos los egresos se descuentan exclusivamente de la cuenta de Efectivo.</p>
    </div>
    <form method="GET" class="flex items-end gap-2">
        <div><label class="text-xs font-bold">Mes</label><input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="block px-3 py-2 border rounded-lg"></div>
        <button class="px-4 py-2 bg-gray-800 text-white rounded-lg">Ver</button>
    </form>
</div>

<?php if((float)$balances['EFECTIVO'] <= 0): ?>
<div class="bg-amber-50 border border-amber-300 text-amber-900 rounded-xl p-4 mb-6 text-sm">
    <i class="fas fa-triangle-exclamation mr-2"></i>
    El sistema tiene <strong>$<?= number_format((float)$balances['EFECTIVO'],2) ?></strong> registrados actualmente en Efectivo.
    Esto <strong>no impide capturar un egreso real</strong>. Al registrarlo, el saldo podrá quedar negativo hasta que
    se validen ingresos anteriores o se capture el saldo inicial correspondiente.
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase font-bold text-gray-500">Efectivo disponible</div>
        <div class="text-2xl font-bold <?= $balances['EFECTIVO']>=0?'text-green-700':'text-red-700' ?>">$<?= number_format((float)$balances['EFECTIVO'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase font-bold text-gray-500">Egresos del mes</div>
        <div class="text-2xl font-bold text-red-700">$<?= number_format($monthExpense,2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase font-bold text-gray-500">Método permitido</div>
        <div class="text-xl font-bold text-gray-900">Efectivo únicamente</div>
    </div>
</div>

<?php if($canCreateExpense): ?>
<div class="bg-white rounded-2xl shadow p-5 mb-6">
    <h3 class="font-bold text-lg mb-4">Registrar egreso</h3>
    <form method="POST" action="<?= BASE_URL ?>/egresos/registrar" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="xl:col-span-2">
            <label class="text-sm font-bold">Concepto *</label>
            <input name="concept" required maxlength="200" placeholder="Ej. Luz, Internet, Papelería" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div>
            <label class="text-sm font-bold">Importe *</label>
            <input type="text" inputmode="decimal" data-money-input name="amount" required class="w-full px-3 py-2 border rounded-lg">
            <p class="text-xs text-gray-500 mt-1">
                Efectivo registrado actualmente: <strong>$<?= number_format((float)$balances['EFECTIVO'],2) ?></strong>.
                El egreso puede registrarse aunque el saldo resulte negativo.
            </p>
        </div>
        <div>
            <label class="text-sm font-bold">Referencia</label>
            <input name="reference" maxlength="160" placeholder="Factura / recibo / referencia" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="xl:col-span-2">
            <label class="text-sm font-bold">Evidencia *</label>
            <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="xl:col-span-2">
            <label class="text-sm font-bold">Observaciones</label>
            <input name="notes" maxlength="500" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="xl:col-span-4">
            <button class="w-full bg-red-700 text-white py-3 rounded-xl font-bold">
                <i class="fas fa-arrow-down mr-2"></i>Registrar egreso en Efectivo
            </button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-blue-900 text-sm">
    Los egresos pueden ser registrados por <strong>Gerencia o Administración</strong>.
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow overflow-hidden">
    <div class="px-5 py-4 border-b"><h3 class="font-bold">Historial de egresos</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="p-3 text-left">Folio</th>
                    <th class="p-3 text-left">Fecha / hora</th>
                    <th class="p-3 text-left">Concepto</th>
                    <th class="p-3 text-left">Referencia</th>
                    <th class="p-3 text-left">Registró</th>
                    <th class="p-3 text-center">Evidencia</th>
                    <th class="p-3 text-right">Importe</th>
                </tr>
            </thead>
            <tbody class="divide-y">
            <?php foreach($expenses as $e): ?>
                <tr>
                    <td class="p-3 font-semibold"><?= htmlspecialchars($e['expense_folio']??('#'.$e['id'])) ?></td>
                    <td class="p-3"><?= date('d/m/Y',strtotime($e['expense_date'])) ?> <?= !empty($e['expense_time'])?date('H:i',strtotime($e['expense_time'])):'' ?></td>
                    <td class="p-3"><?= htmlspecialchars($e['concept']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($e['reference']?:'—') ?></td>
                    <td class="p-3"><?= htmlspecialchars($e['created_by_name']??'') ?></td>
                    <td class="p-3 text-center">
                        <?php if(!empty($e['evidence_path'])): ?><a target="_blank" href="<?= BASE_URL ?>/egresos/evidencia/<?= (int)$e['id'] ?>" class="text-blue-700 font-semibold">Ver</a><?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="p-3 text-right font-bold text-red-700">-$<?= number_format((float)$e['amount'],2) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$expenses): ?><tr><td colspan="7" class="p-8 text-center text-gray-500">No hay egresos en este mes.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
