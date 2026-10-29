<?php
$title = 'Detalle financiero';
ob_start();
?>
<div class="mb-5">
    <a href="<?= BASE_URL ?>/financiero" class="text-sm text-blue-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Volver al módulo financiero</a>
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mt-2">Solicitud <?= htmlspecialchars($application['folio'] ?? '') ?></h2>
    <p class="text-gray-500"><?= htmlspecialchars($application['client_name'] ?? '') ?> · <?= htmlspecialchars(trim(($application['type'] ?? '') . (!empty($application['subtype']) ? ' - ' . $application['subtype'] : ''))) ?></p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Total costos</p><p class="text-2xl font-bold text-gray-800">$<?= number_format((float)($application['total_costs'] ?? 0),2) ?></p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Total pagado</p><p class="text-2xl font-bold text-green-700">$<?= number_format((float)($application['total_paid'] ?? 0),2) ?></p></div>
    <div class="bg-white rounded-xl shadow p-4"><p class="text-xs uppercase text-gray-500 font-bold">Saldo</p><p class="text-2xl font-bold text-red-700">$<?= number_format((float)($application['balance'] ?? 0),2) ?></p></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Registrar pago</h3>
        <form method="POST" action="<?= BASE_URL ?>/financiero/registrar-pago/<?= (int)$application['id'] ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div><label class="text-sm font-medium">Monto *</label><input type="number" min="0.01" step="0.01" name="amount" required class="w-full px-3 py-2 border rounded-lg"></div>
            <div><label class="text-sm font-medium">Fecha *</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-lg"></div>
            <div><label class="text-sm font-medium">Forma de pago *</label><select name="payment_method" id="financialPaymentMethod" required class="w-full px-3 py-2 border rounded-lg"><option value="">Seleccione...</option><option value="EFECTIVO">Efectivo</option><option value="TRANSFERENCIA">Transferencia</option><option value="TERMINAL_TARJETA">Terminal / Tarjeta</option></select></div>
            <div id="financialEnvelopeWrap" class="hidden"><label class="text-sm font-medium">Sobre / Caja destino *</label><select name="cash_envelope_id" id="financialEnvelope" class="w-full px-3 py-2 border rounded-lg"><option value="">Seleccione...</option><?php foreach ($cashDestinations ?? [] as $e): ?><option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="text-sm font-medium">Referencia / folio</label><input name="reference" class="w-full px-3 py-2 border rounded-lg"></div>
            <div><label class="text-sm font-medium">Observaciones</label><input name="notes" class="w-full px-3 py-2 border rounded-lg"></div>
            <div class="md:col-span-2"><button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg">Guardar pago</button></div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-5 py-4 border-b"><h3 class="font-bold text-gray-800">Pagos registrados</h3></div>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-3 py-2 text-left">Fecha</th><th class="px-3 py-2 text-right">Monto</th><th class="px-3 py-2 text-left">Forma</th><th class="px-3 py-2 text-left">Referencia</th></tr></thead><tbody class="divide-y">
        <?php foreach ($payments as $p): ?><tr><td class="px-3 py-2"><?= htmlspecialchars($p['payment_date']) ?></td><td class="px-3 py-2 text-right font-bold text-green-700">$<?= number_format((float)$p['amount'],2) ?></td><td class="px-3 py-2"><?= htmlspecialchars(financialPaymentMethodLabel($p['payment_method'])) ?></td><td class="px-3 py-2"><?= htmlspecialchars($p['reference'] ?? '-') ?></td></tr><?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="4" class="p-6 text-center text-gray-500">Sin pagos.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const method=document.getElementById('financialPaymentMethod'), wrap=document.getElementById('financialEnvelopeWrap'), env=document.getElementById('financialEnvelope');
    if(method&&wrap&&env){ const sync=()=>{const cash=method.value==='EFECTIVO';wrap.classList.toggle('hidden',!cash);env.required=cash;if(!cash)env.value='';}; method.addEventListener('change',sync);sync(); }
});
</script>
<?php $content=ob_get_clean(); require ROOT_PATH . '/app/views/layouts/main.php'; ?>
