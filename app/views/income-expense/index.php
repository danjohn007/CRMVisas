<?php
$title='Control de Ingresos';
ob_start();
?>
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Control de Ingresos</h2>
        <p class="text-gray-600">Revise ventas y abonos reportados por las asesoras antes de que afecten las cuentas.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/balance-semanal" class="px-4 py-2 bg-indigo-700 text-white rounded-lg font-semibold">Balance semanal</a>
        <a href="<?= BASE_URL ?>/egresos" class="px-4 py-2 bg-red-700 text-white rounded-lg font-semibold">Egresos</a>
        <a href="<?= BASE_URL ?>/resumen-mensual" class="px-4 py-2 bg-gray-900 text-white rounded-lg font-semibold">Resumen mensual</a>
    </div>
</div>

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-900">
    <strong>Regla:</strong> mientras una venta o un abono estén pendientes, no cuentan como dinero recibido.
    Al aprobarlos, el sistema envía automáticamente el importe a <strong>Efectivo, Terminal o Transferencia</strong>.
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Efectivo / Ahorro</div>
        <div class="text-2xl font-bold text-green-700">$<?= number_format((float)$balances['EFECTIVO'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Terminal</div>
        <div class="text-2xl font-bold text-purple-700">$<?= number_format((float)$balances['TERMINAL'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Transferencia</div>
        <div class="text-2xl font-bold text-blue-700">$<?= number_format((float)$balances['TRANSFERENCIA'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Cuentas por cobrar</div>
        <div class="text-2xl font-bold text-red-700">$<?= number_format($receivables,2) ?></div>
    </div>
</div>

<?php if($pendingSales): ?>
<div class="bg-white rounded-2xl shadow mb-6 overflow-hidden border border-amber-200">
    <div class="px-5 py-4 bg-amber-50 border-b border-amber-200">
        <h3 class="font-bold text-amber-900"><i class="fas fa-file-circle-check mr-2"></i>Ventas pendientes de aprobación</h3>
        <p class="text-sm text-amber-800">Aprobar la venta valida los pagos reportados y distribuye el dinero entre las tres cuentas.</p>
    </div>
    <div class="divide-y">
    <?php foreach($pendingSales as $sale): $parts=$pendingSalePayments[(int)$sale['id']]??[]; ?>
        <div class="p-5">
            <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                <div class="lg:col-span-2">
                    <div class="font-bold text-lg"><?= htmlspecialchars($sale['client_name_snapshot']) ?></div>
                    <div class="text-sm text-gray-500">Folio cliente: <?= htmlspecialchars($sale['client_folio_snapshot']?:'—') ?></div>
                    <div class="text-sm text-indigo-700 font-semibold"><?= htmlspecialchars($sale['sale_folio']) ?></div>
                </div>
                <div class="lg:col-span-2">
                    <div class="text-xs uppercase text-gray-500">Servicio</div>
                    <div class="font-semibold"><?= htmlspecialchars($sale['service_name_snapshot']) ?></div>
                    <div class="text-sm text-gray-500">Cantidad: <?= (int)$sale['quantity'] ?> · <?= date('d/m/Y',strtotime($sale['contract_date'])) ?></div>
                </div>
                <?php if(($sale['sale_origin']??'')==='MANUAL'): ?>
                <div class="lg:col-span-6 -mt-1">
                    <a target="_blank" href="<?= BASE_URL ?>/ingresos/ticket/<?= (int)$sale['id'] ?>"
                       class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-sm font-bold">
                        <i class="fas fa-receipt mr-2"></i>Ver comprobante / ticket de compra
                    </a>
                </div>
                <?php endif; ?>
                <div>
                    <div class="text-xs uppercase text-gray-500">Total venta</div>
                    <div class="text-xl font-bold">$<?= number_format((float)$sale['total_amount'],2) ?></div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-500">Reportado</div>
                    <div class="text-xl font-bold text-amber-700">$<?= number_format((float)$sale['pending_amount'],2) ?></div>
                </div>
            </div>

            <?php if($parts): ?>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php foreach($parts as $p): ?>
                <div class="rounded-lg border bg-gray-50 p-3">
                    <div class="text-xs text-gray-500"><?= htmlspecialchars(financialPaymentMethodLabel($p['payment_method'])) ?></div>
                    <div class="font-bold">$<?= number_format((float)$p['amount'],2) ?></div>
                    <?php if(!empty($p['proof_path'])): ?>
                        <a target="_blank" href="<?= BASE_URL ?>/control-ingresos/comprobante/<?= (int)$p['id'] ?>" class="text-xs text-blue-700 font-semibold">
                            <i class="fas fa-eye mr-1"></i>Ver comprobante
                        </a>
                    <?php elseif(($sale['sale_origin']??'')==='MANUAL'): ?>
                        <div class="text-xs text-blue-700 font-semibold mt-1"><i class="fas fa-receipt mr-1"></i>Comprobante incluido en el ticket</div>
                    <?php else: ?>
                        <div class="text-xs text-gray-400 mt-1">Sin comprobante adjunto</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="mt-4 rounded-lg bg-gray-50 border p-3 text-sm text-gray-600">
                Venta sin pago reportado. Puede aprobarse para que quede oficialmente registrada; el cliente seguirá con el total pendiente.
            </div>
            <?php endif; ?>

            <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                <form method="POST" action="<?= BASE_URL ?>/control-ingresos/aprobar-venta/<?= (int)$sale['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($approvalCsrfToken) ?>">
                    <button class="w-full bg-green-700 text-white py-2.5 rounded-lg font-bold">
                        <i class="fas fa-check mr-2"></i>Aprobar venta
                    </button>
                </form>
                <form method="POST" action="<?= BASE_URL ?>/control-ingresos/rechazar-venta/<?= (int)$sale['id'] ?>" class="flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($approvalCsrfToken) ?>">
                    <input name="reason" required class="flex-1 px-3 py-2 border rounded-lg" placeholder="Motivo del rechazo">
                    <button class="px-4 py-2 bg-red-700 text-white rounded-lg font-bold">Rechazar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if($pendingPayments): ?>
<div class="bg-white rounded-2xl shadow mb-6 overflow-hidden border border-blue-200">
    <div class="px-5 py-4 bg-blue-50 border-b border-blue-200">
        <h3 class="font-bold text-blue-900"><i class="fas fa-coins mr-2"></i>Abonos pendientes de validación</h3>
        <p class="text-sm text-blue-800">Son pagos posteriores reportados por las asesoras sobre ventas ya aprobadas.</p>
    </div>
    <div class="divide-y">
    <?php foreach($pendingPayments as $p): ?>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-center">
                <div class="md:col-span-2">
                    <div class="font-bold"><?= htmlspecialchars($p['client_name_snapshot']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($p['client_folio_snapshot']) ?> · <?= htmlspecialchars($p['sale_folio']) ?></div>
                    <div class="text-sm text-gray-600"><?= htmlspecialchars($p['service_name_snapshot']) ?></div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Método / cuenta</div>
                    <div class="font-semibold"><?= htmlspecialchars(financialPaymentMethodLabel($p['payment_method'])) ?></div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Importe</div>
                    <div class="font-bold text-xl">$<?= number_format((float)$p['amount'],2) ?></div>
                    <?php if(!empty($p['proof_path'])): ?>
                        <a target="_blank" href="<?= BASE_URL ?>/control-ingresos/comprobante/<?= (int)$p['id'] ?>" class="text-xs text-blue-700 font-semibold">
                            <i class="fas fa-eye mr-1"></i>Ver comprobante
                        </a>
                    <?php else: ?>
                        <div class="text-xs text-gray-400">Sin comprobante adjunto</div>
                    <?php endif; ?>
                </div>
                <div class="text-sm">
                    <div><?= date('d/m/Y',strtotime($p['payment_date'])) ?></div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                <form method="POST" action="<?= BASE_URL ?>/control-ingresos/validar/<?= (int)$p['id'] ?>" class="flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($approvalCsrfToken) ?>">
                    <input name="validation_notes" class="flex-1 px-3 py-2 border rounded-lg" placeholder="Nota de validación (opcional)">
                    <button class="px-4 py-2 bg-green-700 text-white rounded-lg font-bold">Validar</button>
                </form>
                <form method="POST" action="<?= BASE_URL ?>/control-ingresos/rechazar/<?= (int)$p['id'] ?>" class="flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($approvalCsrfToken) ?>">
                    <input name="reason" required class="flex-1 px-3 py-2 border rounded-lg" placeholder="Motivo del rechazo">
                    <button class="px-4 py-2 bg-red-700 text-white rounded-lg font-bold">Rechazar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow mb-6 overflow-hidden">
    <div class="p-5 border-b">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-3">
            <div>
                <h3 class="font-bold text-lg">Expedientes financieros</h3>
                <p class="text-sm text-gray-500">Consulte saldo, servicio y estado de cada venta.</p>
            </div>
            <form method="GET" class="flex flex-wrap gap-2 items-end">
                <div><label class="text-xs font-bold">Cliente / folio</label><input name="q" value="<?= htmlspecialchars($q) ?>" class="block px-3 py-2 border rounded-lg" placeholder="Nombre, VISA-, CLI- o VTA-"></div>
                <div><label class="text-xs font-bold">Asesora</label><select name="advisor_id" class="block px-3 py-2 border rounded-lg"><option value="0">Todas</option><?php foreach($advisors as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $advisorId===(int)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['full_name']) ?></option><?php endforeach; ?></select></div>
                <div><label class="text-xs font-bold">Saldo</label><select name="state" class="block px-3 py-2 border rounded-lg"><option value="">Todos</option><option value="PENDIENTE" <?= $state==='PENDIENTE'?'selected':'' ?>>Pendiente</option><option value="PARCIAL" <?= $state==='PARCIAL'?'selected':'' ?>>Parcial</option><option value="LIQUIDADO" <?= $state==='LIQUIDADO'?'selected':'' ?>>Liquidado</option></select></div>
                <button class="px-4 py-2 bg-gray-900 text-white rounded-lg">Filtrar</button>
            </form>
        </div>
    </div>

    <div class="divide-y">
    <?php foreach($sales as $s):
        $paid=(float)$s['validated_paid']; $balance=max(0,(float)$s['total_amount']-$paid);
    ?>
        <div class="p-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <div class="flex gap-2 items-center flex-wrap">
                        <div class="font-bold text-lg"><?= htmlspecialchars($s['client_name_snapshot']) ?></div>
                        <?php if($balance>0.009): ?><span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 text-xs font-bold">SALDO PENDIENTE</span>
                        <?php else: ?><span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 text-xs font-bold">LIQUIDADO</span><?php endif; ?>
                    </div>
                    <div class="text-sm text-gray-500"><?= htmlspecialchars($s['client_folio_snapshot']) ?> · <?= htmlspecialchars($s['sale_folio']) ?> · <?= htmlspecialchars($s['service_name_snapshot']) ?></div>
                    <div class="text-sm text-gray-500">Asesora: <?= htmlspecialchars($s['advisor_name']??'') ?></div>
                    <?php if(($s['sale_origin']??'')==='MANUAL'): ?>
                    <div class="mt-2">
                        <a target="_blank" href="<?= BASE_URL ?>/ingresos/ticket/<?= (int)$s['id'] ?>"
                           class="inline-flex items-center text-xs font-bold text-blue-700 hover:text-blue-900">
                            <i class="fas fa-receipt mr-1"></i>Ver ticket de compra
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-3 gap-5">
                    <div><div class="text-xs text-gray-500">Total</div><strong>$<?= number_format((float)$s['total_amount'],2) ?></strong></div>
                    <div><div class="text-xs text-gray-500">Abonado</div><strong class="text-green-700">$<?= number_format($paid,2) ?></strong></div>
                    <div><div class="text-xs text-gray-500">Saldo</div><strong class="<?= $balance>0.009?'text-red-700':'text-green-700' ?>">$<?= number_format($balance,2) ?></strong></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if(!$sales): ?><div class="p-10 text-center text-gray-500">No hay ventas con esos filtros.</div><?php endif; ?>
    </div>
</div>

<div id="catalogo-ingresos" class="bg-white rounded-2xl shadow p-5">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3 mb-4">
        <div>
            <h3 class="font-bold text-lg">Catálogo maestro de ingresos</h3>
            <p class="text-sm text-gray-500">
                Canal Solicitud = aparece en Hoja de Información. Manual = copias, actas, impresiones, etc. Ambos = disponible en los dos.
            </p>
        </div>
        <?php if($isCatalogAdmin): ?>
        <details class="xl:w-[600px] border rounded-xl">
            <summary class="cursor-pointer px-4 py-3 font-bold text-blue-700">+ Crear concepto</summary>
            <form method="POST" action="<?= BASE_URL ?>/control-ingresos/catalogo/crear" class="p-4 border-t grid grid-cols-1 md:grid-cols-2 gap-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($catalogCsrfToken) ?>">
                <input name="category" required placeholder="Categoría" class="px-3 py-2 border rounded-lg">
                <input name="concept" required placeholder="Concepto" class="px-3 py-2 border rounded-lg">
                <input type="text" inputmode="decimal" data-money-input name="authorized_price" placeholder="Precio" class="px-3 py-2 border rounded-lg">
                <select name="price_mode" class="px-3 py-2 border rounded-lg"><option value="FIJO">Precio fijo</option><option value="POR_CONFIRMAR">Por confirmar</option><option value="SEGUN_DIFERENCIA">Según diferencia</option><option value="SEGUN_COTIZACION">Según cotización</option></select>
                <select name="sale_channel" class="px-3 py-2 border rounded-lg"><option value="SOLICITUD">Hoja de Información</option><option value="MANUAL">Venta manual</option><option value="AMBOS">Ambos</option></select>
                <select name="catalog_status" class="px-3 py-2 border rounded-lg"><option value="ACTIVO">Activo</option><option value="SUSPENDIDO">Suspendido</option><option value="INACTIVO">Inactivo</option></select>
                <input name="notes" placeholder="Notas" class="md:col-span-2 px-3 py-2 border rounded-lg">
                <button class="md:col-span-2 bg-blue-700 text-white rounded-lg py-2 font-bold">Guardar concepto</button>
            </form>
        </details>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr><th class="p-2 text-left">Código</th><th class="p-2 text-left">Concepto</th><th class="p-2 text-right">Precio</th><th class="p-2 text-center">Canal</th><th class="p-2 text-center">Estado</th><?php if($isCatalogAdmin): ?><th class="p-2 text-right">Editar</th><?php endif; ?></tr>
            </thead>
            <tbody class="divide-y">
            <?php foreach($catalog as $c): $status=$c['catalog_status']??($c['is_active']?'ACTIVO':'INACTIVO'); ?>
                <tr>
                    <td class="p-2 font-semibold"><?= htmlspecialchars($c['code']) ?></td>
                    <td class="p-2"><div class="font-medium"><?= htmlspecialchars($c['concept']) ?></div><div class="text-xs text-gray-500"><?= htmlspecialchars($c['category']) ?></div></td>
                    <td class="p-2 text-right"><?= $c['authorized_price']!==null?'$'.number_format((float)$c['authorized_price'],2):'—' ?></td>
                    <td class="p-2 text-center text-xs font-semibold"><?= htmlspecialchars($c['sale_channel']??'SOLICITUD') ?></td>
                    <td class="p-2 text-center"><span class="px-2 py-1 rounded-full text-xs font-bold <?= $status==='ACTIVO'?'bg-green-100 text-green-800':($status==='SUSPENDIDO'?'bg-amber-100 text-amber-800':'bg-gray-200 text-gray-700') ?>"><?= htmlspecialchars($status) ?></span></td>
                    <?php if($isCatalogAdmin): ?>
                    <td class="p-2 text-right">
                        <details class="inline-block text-left">
                            <summary class="cursor-pointer px-3 py-1.5 rounded-lg bg-gray-800 text-white text-xs">Editar</summary>
                            <div class="mt-2 w-[350px] max-w-[85vw] p-3 border rounded-xl bg-white shadow-lg">
                                <form method="POST" action="<?= BASE_URL ?>/control-ingresos/catalogo/editar/<?= (int)$c['id'] ?>" class="grid gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($catalogCsrfToken) ?>">
                                    <input name="category" required value="<?= htmlspecialchars($c['category']) ?>" class="px-3 py-2 border rounded-lg">
                                    <input name="concept" required value="<?= htmlspecialchars($c['concept']) ?>" class="px-3 py-2 border rounded-lg">
                                    <input type="text" inputmode="decimal" data-money-input name="authorized_price" value="<?= htmlspecialchars((string)$c['authorized_price']) ?>" class="px-3 py-2 border rounded-lg">
                                    <select name="price_mode" class="px-3 py-2 border rounded-lg"><?php foreach(['FIJO','POR_CONFIRMAR','SEGUN_DIFERENCIA','SEGUN_COTIZACION'] as $m): ?><option value="<?= $m ?>" <?= $c['price_mode']===$m?'selected':'' ?>><?= $m ?></option><?php endforeach; ?></select>
                                    <select name="sale_channel" class="px-3 py-2 border rounded-lg"><?php foreach(['SOLICITUD','MANUAL','AMBOS'] as $m): ?><option value="<?= $m ?>" <?= ($c['sale_channel']??'SOLICITUD')===$m?'selected':'' ?>><?= $m ?></option><?php endforeach; ?></select>
                                    <select name="catalog_status" class="px-3 py-2 border rounded-lg"><?php foreach(['ACTIVO','SUSPENDIDO','INACTIVO'] as $m): ?><option value="<?= $m ?>" <?= $status===$m?'selected':'' ?>><?= $m ?></option><?php endforeach; ?></select>
                                    <input name="notes" value="<?= htmlspecialchars((string)($c['notes']??'')) ?>" class="px-3 py-2 border rounded-lg">
                                    <button class="bg-blue-700 text-white rounded-lg py-2 font-bold">Guardar cambios</button>
                                </form>
                                <div class="mt-3 pt-3 border-t flex gap-2 flex-wrap">
                                    <?php foreach(['ACTIVO'=>'Activar','SUSPENDIDO'=>'Suspender','INACTIVO'=>'Desactivar'] as $st=>$label): if($status===$st)continue; ?>
                                    <form method="POST" action="<?= BASE_URL ?>/control-ingresos/catalogo/estado/<?= (int)$c['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($catalogCsrfToken) ?>">
                                        <input type="hidden" name="catalog_status" value="<?= $st ?>">
                                        <button class="px-2.5 py-1.5 rounded-lg text-xs font-bold <?= $st==='ACTIVO'?'bg-green-700':($st==='SUSPENDIDO'?'bg-amber-600':'bg-gray-600') ?> text-white"><?= $label ?></button>
                                    </form>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
