<?php
$title='Mis ventas';
ob_start();
$isEditingRejected = !empty($editSale);
$editClientSource = '';
if ($isEditingRejected) {
    if (!empty($editSale['application_id'])) $editClientSource = 'APP:' . (int)$editSale['application_id'];
    elseif (!empty($editSale['quick_client_id'])) $editClientSource = 'QUICK:' . (int)$editSale['quick_client_id'];
}
$editPaymentRows = $isEditingRejected && !empty($editSalePayments) ? array_values($editSalePayments) : [];
$editSplitMode = count($editPaymentRows) > 1;
?>
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Mis ventas</h2>
        <p class="text-gray-600 mt-1">Ventas rápidas, abonos y seguimiento de saldos.</p>
    </div>
    <a href="<?= BASE_URL ?>/ventas-quincenales"
       class="inline-flex items-center justify-center px-4 py-2 bg-indigo-700 text-white rounded-lg font-semibold">
        <i class="fas fa-chart-column mr-2"></i>Mis ventas por quincena
    </a>
</div>

<?php if(($rejectedSalesCount ?? 0) > 0): ?>
<div class="bg-red-50 border border-red-200 text-red-900 rounded-xl p-4 mb-6 text-sm flex items-start gap-3">
    <i class="fas fa-circle-exclamation mt-0.5"></i>
    <div><strong>Tienes <?= (int)$rejectedSalesCount ?> venta(s) rechazada(s).</strong> Revisa el motivo, corrige y vuelve a enviarlas.</div>
</div>
<?php endif; ?>

<div id="nueva-venta" class="bg-white rounded-2xl shadow p-5 md:p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
        <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-cart-plus mr-2 text-blue-600"></i><?= $isEditingRejected ? 'Corregir venta rechazada' : 'Nueva venta' ?></h3>
        <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-semibold">Folio de venta automático</span>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/ingresos/venta" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($advisorPaymentCsrfToken) ?>">
        <input type="hidden" name="edit_sale_id" value="<?= $isEditingRejected ? (int)$editSale['id'] : 0 ?>">

        <div class="xl:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Cliente *</label>
            <input type="text" id="clientSearch" autocomplete="off"
                   placeholder="Buscar cliente por nombre..."
                   class="w-full px-3 py-2 border rounded-lg mb-2">
            <select name="client_source" id="clientSource" required class="w-full px-3 py-2 border rounded-lg">
                <option value="">Seleccione...</option>
                <optgroup label="Clientes con expediente">
                    <?php foreach($applications as $a): ?>
                    <option value="APP:<?= (int)$a['id'] ?>"
                            data-client-name="<?= htmlspecialchars(mb_strtolower((string)($a['client_name']?:'Sin nombre'),'UTF-8')) ?>"
                            <?= (($editClientSource==='APP:'.(int)$a['id']) || (!$isEditingRejected && $preselectedApplicationId===(int)$a['id']))?'selected':'' ?>>
                        <?= htmlspecialchars($a['client_name']?:'Sin nombre') ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php if($quickClients): ?>
                <optgroup label="Clientes de venta rápida">
                    <?php foreach($quickClients as $c): ?>
                    <option value="QUICK:<?= (int)$c['id'] ?>"
                            data-client-name="<?= htmlspecialchars(mb_strtolower((string)$c['full_name'],'UTF-8')) ?>"
                            <?= $editClientSource==='QUICK:'.(int)$c['id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endif; ?>
                <option value="NEW" data-client-name="registrar cliente nuevo">+ Registrar cliente nuevo</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Escriba parte del nombre para reducir la lista.</p>
        </div>

        <div class="xl:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Servicio *</label>
            <select name="catalog_id" id="manualCatalog" required class="w-full px-3 py-2 border rounded-lg">
                <option value="">Seleccione...</option>
                <?php foreach($catalog as $c): ?>
                <option value="<?= (int)$c['id'] ?>" data-price="<?= htmlspecialchars((string)$c['authorized_price']) ?>"
                        <?= $isEditingRejected && (int)$editSale['catalog_id']===(int)$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['concept']) ?> — $<?= number_format((float)$c['authorized_price'],2) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="newClientFields" class="hidden md:col-span-2 xl:col-span-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="font-bold text-emerald-900 mb-3">Cliente nuevo</div>
            <div>
                <label class="text-xs font-bold">Nombre completo *</label>
                <input name="new_client_name" id="newClientName"
                       placeholder="Ej. Juan Pérez"
                       class="w-full px-3 py-2 border rounded-lg bg-white">
            </div>
            <p class="text-xs text-emerald-800 mt-2">Para una venta rápida sólo necesitamos el nombre. El sistema conserva el registro automáticamente.</p>
        </div>

        <div class="xl:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Cantidad *</label>
            <input type="number" name="quantity" id="manualQty" min="1" value="<?= $isEditingRejected ? (int)$editSale['quantity'] : 1 ?>" required class="w-full px-3 py-2 border rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Número de personas, copias, actas o unidades.</p>
        </div>
        <div class="xl:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Total de la venta</label>
            <input id="manualTotal" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-50 font-bold text-lg text-gray-900" value="$0.00">
            <p class="text-xs text-gray-500 mt-1">Se calcula automáticamente con el precio configurado del servicio × cantidad.</p>
        </div>
        <div class="xl:col-span-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha *</label>
            <input type="date" name="contract_date" value="<?= htmlspecialchars($isEditingRejected ? $editSale['contract_date'] : date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-lg">
        </div>

        <div class="md:col-span-2 xl:col-span-4 border rounded-xl p-4 bg-gray-50">
            <input type="hidden" name="payment_split_mode" id="paymentSplitMode" value="<?= $editSplitMode ? 1 : 0 ?>">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                <div>
                    <div class="font-bold text-gray-800">Forma de pago</div>
                    <p class="text-xs text-gray-500">Si pagó con un solo método, sólo selecciónelo: el sistema asignará automáticamente el total completo. Si dividió el pago, agregue otra forma y capture cuánto pagó con cada una.</p>
                </div>
                <button type="button" id="addManualPayment" class="px-3 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg text-sm font-semibold">
                    + Agregar otra forma de pago
                </button>
            </div>
            <div id="manualPaymentParts" class="space-y-3">
                <?php
                    $rowsToRender = $editPaymentRows ?: [['payment_method'=>'','amount'=>0]];
                    foreach($rowsToRender as $idx=>$paymentRow):
                        $methodValue = financialNormalizePaymentMethod($paymentRow['payment_method'] ?? '');
                        $showAmount = $editSplitMode;
                ?>
                <div class="manual-payment-part grid grid-cols-1 <?= $showAmount?'md:grid-cols-2':'' ?> gap-3 bg-white border rounded-lg p-3 relative">
                    <?php if($idx>0): ?><button type="button" class="remove-payment absolute -top-2 -right-2 w-7 h-7 rounded-full bg-red-600 text-white">×</button><?php endif; ?>
                    <div class="manual-amount-wrap <?= $showAmount?'':'hidden' ?>">
                        <label class="block text-xs font-semibold mb-1">Importe</label>
                        <input type="text" inputmode="decimal" data-money-input name="payment_amount[]" value="<?= htmlspecialchars((string)($paymentRow['amount'] ?? 0)) ?>"
                               class="manual-payment-amount w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="manual-method-wrap">
                        <label class="block text-xs font-semibold mb-1">Método</label>
                        <select name="payment_method[]" class="manual-payment-method w-full px-3 py-2 border rounded-lg">
                            <option value="">Seleccione...</option>
                            <option value="EFECTIVO" <?= $methodValue==='EFECTIVO'?'selected':'' ?>>Efectivo</option>
                            <option value="TERMINAL_TARJETA" <?= $methodValue==='TERMINAL_TARJETA'?'selected':'' ?>>Terminal</option>
                            <option value="TRANSFERENCIA" <?= $methodValue==='TRANSFERENCIA'?'selected':'' ?>>Transferencia</option>
                        </select>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="singlePaymentHelp" class="mt-3 text-xs text-blue-700 font-semibold <?= $editSplitMode?'hidden':'' ?>">Con una sola forma de pago no necesita capturar el importe.</div>
            <div class="mt-1 text-sm font-semibold">Total reportado: <span id="manualPaymentTotal">$0.00</span></div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Próximo seguimiento</label>
            <input type="date" name="next_followup_date" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="md:col-span-2 xl:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Observaciones</label>
            <input name="notes" maxlength="500" value="<?= htmlspecialchars($isEditingRejected ? (string)($editSale['notes'] ?? '') : '') ?>" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="flex items-end">
            <button class="w-full px-5 py-3 bg-blue-700 text-white font-bold rounded-xl hover:bg-blue-800">
                <i class="fas fa-paper-plane mr-2"></i><?= $isEditingRejected ? 'Corregir y reenviar' : 'Enviar a aprobación' ?>
            </button>
            <?php if($isEditingRejected): ?>
            <a href="<?= BASE_URL ?>/ingresos" class="mt-2 block text-center text-sm text-gray-600 hover:text-gray-900">Cancelar corrección</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow overflow-hidden">
    <div class="px-5 py-4 border-b">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
            <div>
                <h3 class="font-bold text-gray-800">Mis ventas y saldos</h3>
                <p class="text-xs text-gray-500">Consulta, corrige y da seguimiento a tus ventas.</p>
            </div>

            <form method="GET" action="<?= BASE_URL ?>/ingresos" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 w-full xl:w-auto">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Desde</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($salesDateFrom) ?>"
                           class="w-full px-3 py-2 border rounded-lg bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($salesDateTo) ?>"
                           class="w-full px-3 py-2 border rounded-lg bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Mostrar</label>
                    <select name="per_page" class="w-full px-3 py-2 border rounded-lg bg-white">
                        <?php foreach([20,30,50] as $size): ?>
                        <option value="<?= $size ?>" <?= $salesPerPage===$size?'selected':'' ?>><?= $size ?> ventas</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button class="flex-1 px-4 py-2 bg-blue-700 text-white rounded-lg font-bold hover:bg-blue-800">
                        <i class="fas fa-filter mr-1"></i>Filtrar
                    </button>
                    <?php if($salesDateFrom!=='' || $salesDateTo!=='' || $salesPerPage!==20): ?>
                    <a href="<?= BASE_URL ?>/ingresos" class="px-3 py-2 border rounded-lg text-gray-700 hover:bg-gray-50" title="Limpiar filtros">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-gray-500">
            <span>Mostrando <strong><?= (int)$salesFirstRow ?></strong> a <strong><?= (int)$salesLastRow ?></strong> de <strong><?= (int)$salesTotalRows ?></strong> ventas.</span>
            <?php if($salesDateFrom!=='' || $salesDateTo!==''): ?>
            <span>Periodo: <strong><?= $salesDateFrom!==''?date('d/m/Y',strtotime($salesDateFrom)):'Inicio' ?></strong> al <strong><?= $salesDateTo!==''?date('d/m/Y',strtotime($salesDateTo)):'Hoy' ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="divide-y">
    <?php foreach($sales as $s):
        $paid=(float)$s['validated_paid'];
        $pending=(float)$s['pending_validation'];
        $total=(float)$s['total_amount'];
        $balance=max(0,$total-$paid);
        $available=max(0,$total-$paid-$pending);
        $approval=$s['approval_status']??'VALIDADA';
    ?>
        <div class="p-5">
            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="font-bold text-lg text-gray-900"><?= htmlspecialchars($s['client_name_snapshot']) ?></div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold
                            <?= $approval==='VALIDADA'?'bg-green-100 text-green-800':($approval==='RECHAZADA'?'bg-red-100 text-red-800':'bg-amber-100 text-amber-800') ?>">
                            <?= $approval==='VALIDADA'?'VENTA APROBADA':($approval==='RECHAZADA'?'VENTA RECHAZADA':'PENDIENTE DE APROBACIÓN') ?>
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Cliente: <strong><?= htmlspecialchars($s['client_folio_snapshot']?:'—') ?></strong>
                        · Venta: <strong><?= htmlspecialchars($s['sale_folio']) ?></strong>
                        · <?= htmlspecialchars($s['service_name_snapshot']) ?>
                    </div>
                    <?php if(($s['sale_origin']??'')==='MANUAL'): ?>
                    <div class="mt-2">
                        <a target="_blank" href="<?= BASE_URL ?>/ingresos/ticket/<?= (int)$s['id'] ?>"
                           class="inline-flex items-center text-sm font-bold text-blue-700 hover:text-blue-900">
                            <i class="fas fa-receipt mr-2"></i>Ver / imprimir ticket
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if($approval==='RECHAZADA'): ?>
                    <div class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                        <div class="font-bold"><i class="fas fa-circle-exclamation mr-2"></i>Gerencia rechazó esta venta</div>
                        <div class="mt-1">Motivo: <?= htmlspecialchars($s['rejection_reason'] ?: 'Sin motivo registrado') ?></div>
                        <div class="mt-3">
                            <?php if(($s['sale_origin']??'')==='MANUAL'): ?>
                            <a href="<?= BASE_URL ?>/ingresos?edit_sale=<?= (int)$s['id'] ?>#nueva-venta" class="inline-flex items-center px-3 py-2 rounded-lg bg-red-700 text-white font-bold">
                                <i class="fas fa-pen mr-2"></i>Corregir y reenviar
                            </a>
                            <?php elseif(!empty($s['application_id'])): ?>
                            <a href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$s['application_id'] ?>" class="inline-flex items-center px-3 py-2 rounded-lg bg-red-700 text-white font-bold">
                                <i class="fas fa-file-pen mr-2"></i>Corregir Hoja de Información
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 max-w-3xl">
                        <div><div class="text-xs text-gray-500">Cantidad</div><div class="font-bold"><?= (int)$s['quantity'] ?></div></div>
                        <div><div class="text-xs text-gray-500">Total</div><div class="font-bold">$<?= number_format($total,2) ?></div></div>
                        <div><div class="text-xs text-gray-500">Abonado validado</div><div class="font-bold text-green-700">$<?= number_format($paid,2) ?></div></div>
                        <div><div class="text-xs text-gray-500">Saldo</div><div class="font-bold <?= $balance>0.009?'text-red-700':'text-green-700' ?>">$<?= number_format($balance,2) ?></div></div>
                    </div>

                    <?php if($pending>0): ?>
                    <div class="mt-3 inline-flex px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                        <i class="fas fa-clock mr-2 mt-0.5"></i>$<?= number_format($pending,2) ?> están pendientes de validación.
                    </div>
                    <?php endif; ?>
                </div>

                <?php if($approval==='VALIDADA' && $available>0.009): ?>
                <details class="xl:w-[480px] border rounded-xl">
                    <summary class="cursor-pointer px-4 py-3 font-bold text-blue-700">
                        <i class="fas fa-plus-circle mr-2"></i>Registrar abono
                    </summary>
                    <form method="POST" enctype="multipart/form-data"
                          action="<?= BASE_URL ?>/ingresos/abono/<?= (int)$s['id'] ?>"
                          class="p-4 border-t grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($advisorPaymentCsrfToken) ?>">
                        <div>
                            <label class="text-xs font-bold">Importe *</label>
                            <input type="text" inputmode="decimal" data-money-input name="amount" required
                                   class="w-full px-3 py-2 border rounded-lg">
                            <div class="text-xs text-gray-500 mt-1">Disponible: $<?= number_format($available,2) ?></div>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Método *</label>
                            <select name="payment_method" required class="w-full px-3 py-2 border rounded-lg">
                                <option value="">Seleccione...</option>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TERMINAL_TARJETA">Terminal</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Fecha *</label>
                            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold">Comprobante</label>
                            <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="text-xs font-bold">Próximo seguimiento</label>
                            <input type="date" name="next_followup_date" class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="text-xs font-bold">Observaciones</label>
                            <input name="notes" class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div class="md:col-span-2">
                            <button class="w-full bg-blue-700 text-white py-2 rounded-lg font-bold">
                                Reportar abono
                            </button>
                        </div>
                    </form>
                </details>
                <?php elseif($approval==='PENDIENTE'): ?>
                <div class="xl:w-[340px] rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Gerencia debe aprobar primero esta venta. Después podrá registrar abonos si queda saldo.
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if(!$sales): ?>
        <div class="p-10 text-center text-gray-500">No hay ventas que coincidan con los filtros seleccionados.</div>
    <?php endif; ?>
    </div>

    <?php if($salesTotalPages>1): ?>
    <?php
        $paginationParams = [];
        if($salesDateFrom!=='') $paginationParams['date_from']=$salesDateFrom;
        if($salesDateTo!=='') $paginationParams['date_to']=$salesDateTo;
        $paginationParams['per_page']=$salesPerPage;

        $pageUrl = function($page) use ($paginationParams) {
            $params=$paginationParams;
            $params['page']=$page;
            return BASE_URL.'/ingresos?'.http_build_query($params);
        };

        $startPage=max(1,$salesPage-2);
        $endPage=min($salesTotalPages,$salesPage+2);
        if($salesPage<=3) $endPage=min($salesTotalPages,5);
        if($salesPage>=$salesTotalPages-2) $startPage=max(1,$salesTotalPages-4);
    ?>
    <div class="px-5 py-4 border-t bg-gray-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="text-sm text-gray-600">
            Página <strong><?= (int)$salesPage ?></strong> de <strong><?= (int)$salesTotalPages ?></strong>
        </div>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Paginación de ventas">
            <?php if($salesPage>1): ?>
            <a href="<?= htmlspecialchars($pageUrl(1)) ?>" class="px-3 py-2 rounded-lg border bg-white text-gray-700 hover:bg-blue-50" title="Primera página">
                <i class="fas fa-angles-left"></i>
            </a>
            <a href="<?= htmlspecialchars($pageUrl($salesPage-1)) ?>" class="px-3 py-2 rounded-lg border bg-white text-gray-700 hover:bg-blue-50">
                <i class="fas fa-chevron-left mr-1"></i>Anterior
            </a>
            <?php endif; ?>

            <?php for($p=$startPage;$p<=$endPage;$p++): ?>
            <a href="<?= htmlspecialchars($pageUrl($p)) ?>"
               class="min-w-[40px] text-center px-3 py-2 rounded-lg border font-semibold <?= $p===$salesPage?'bg-blue-700 text-white border-blue-700':'bg-white text-gray-700 hover:bg-blue-50' ?>">
                <?= $p ?>
            </a>
            <?php endfor; ?>

            <?php if($salesPage<$salesTotalPages): ?>
            <a href="<?= htmlspecialchars($pageUrl($salesPage+1)) ?>" class="px-3 py-2 rounded-lg border bg-white text-gray-700 hover:bg-blue-50">
                Siguiente<i class="fas fa-chevron-right ml-1"></i>
            </a>
            <a href="<?= htmlspecialchars($pageUrl($salesTotalPages)) ?>" class="px-3 py-2 rounded-lg border bg-white text-gray-700 hover:bg-blue-50" title="Última página">
                <i class="fas fa-angles-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const source=document.getElementById('clientSource');
    const clientSearch=document.getElementById('clientSearch');
    const newBox=document.getElementById('newClientFields');
    const newName=document.getElementById('newClientName');
    const catalog=document.getElementById('manualCatalog');
    const qty=document.getElementById('manualQty');
    const total=document.getElementById('manualTotal');
    const parts=document.getElementById('manualPaymentParts');
    const totalPaid=document.getElementById('manualPaymentTotal');
    const add=document.getElementById('addManualPayment');
    const splitMode=document.getElementById('paymentSplitMode');
    const singleHelp=document.getElementById('singlePaymentHelp');
    const money=n=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(n||0);

    function updateClientMode(){
        const isNew=source.value==='NEW';
        newBox.classList.toggle('hidden',!isNew);
        newName.required=isNew;
    }

    function saleTotal(){
        const opt=catalog.options[catalog.selectedIndex];
        return parseFloat(opt?.dataset?.price||0)*Math.max(1,parseInt(qty.value||1,10));
    }

    function paymentRows(){
        return Array.from(parts.querySelectorAll('.manual-payment-part'));
    }

    function isSplit(){
        return splitMode.value==='1';
    }

    function setMoneyInput(el,value){
        el.value=money(Math.max(0,value));
    }

    function recalc(){
        const t=saleTotal();
        total.value=money(t);

        const rows=paymentRows();
        if(!isSplit() && rows.length===1){
            const amount=rows[0].querySelector('.manual-payment-amount');
            setMoneyInput(amount,t);
            totalPaid.textContent=money(t);
            totalPaid.className='text-green-700';
            return;
        }

        let paid=0;
        rows.forEach(row=>{
            const el=row.querySelector('.manual-payment-amount');
            paid+=Math.max(0,window.crmParseMoney(el?.value||0));
        });
        totalPaid.textContent=money(paid);
        totalPaid.className=Math.abs(paid-t)<=0.009?'text-green-700':'text-red-700';
    }

    function enableSplitMode(){
        if(isSplit()) return;
        splitMode.value='1';
        singleHelp.classList.add('hidden');
        const first=paymentRows()[0];
        first.classList.add('md:grid-cols-2');
        first.querySelector('.manual-amount-wrap').classList.remove('hidden');
        setMoneyInput(first.querySelector('.manual-payment-amount'),saleTotal());
    }

    function maybeReturnToSingle(){
        const rows=paymentRows();
        if(rows.length!==1) return;
        splitMode.value='0';
        singleHelp.classList.remove('hidden');
        const first=rows[0];
        first.classList.remove('md:grid-cols-2');
        first.querySelector('.manual-amount-wrap').classList.add('hidden');
        setMoneyInput(first.querySelector('.manual-payment-amount'),saleTotal());
        recalc();
    }

    add.addEventListener('click',()=>{
        enableSplitMode();
        const row=document.createElement('div');
        row.className='manual-payment-part grid grid-cols-1 md:grid-cols-2 gap-3 bg-white border rounded-lg p-3 relative';
        row.innerHTML=`
          <button type="button" class="remove-payment absolute -top-2 -right-2 w-7 h-7 rounded-full bg-red-600 text-white">×</button>
          <div class="manual-amount-wrap"><label class="block text-xs font-semibold mb-1">Importe</label><input type="text" inputmode="decimal" data-money-input name="payment_amount[]" value="$0.00" class="manual-payment-amount w-full px-3 py-2 border rounded-lg"></div>
          <div class="manual-method-wrap"><label class="block text-xs font-semibold mb-1">Método</label><select name="payment_method[]" class="manual-payment-method w-full px-3 py-2 border rounded-lg"><option value="">Seleccione...</option><option value="EFECTIVO">Efectivo</option><option value="TERMINAL_TARJETA">Terminal</option><option value="TRANSFERENCIA">Transferencia</option></select></div>`;
        parts.appendChild(row);
        row.querySelector('.remove-payment').addEventListener('click',()=>{row.remove();maybeReturnToSingle();recalc();});
        window.crmInitMoneyInputs(row);
        row.querySelector('.manual-payment-amount').addEventListener('input',recalc);
        recalc();
    });

    source.addEventListener('change',updateClientMode);

    if(clientSearch){
        clientSearch.addEventListener('input',()=>{
            const q=clientSearch.value.trim().toLocaleLowerCase('es-MX');
            Array.from(source.options).forEach(opt=>{
                if(!opt.value){ opt.hidden=false; return; }
                const name=(opt.dataset.clientName||opt.textContent||'').toLocaleLowerCase('es-MX');
                opt.hidden = q!=='' && !name.includes(q);
            });
            const selected=source.options[source.selectedIndex];
            if(selected && selected.hidden){
                source.value='';
                updateClientMode();
            }
        });
    }

    catalog.addEventListener('change',recalc);
    qty.addEventListener('input',recalc);
    parts.querySelectorAll('.manual-payment-amount').forEach(el=>el.addEventListener('input',recalc));
    parts.querySelectorAll('.remove-payment').forEach(btn=>btn.addEventListener('click',()=>{btn.closest('.manual-payment-part').remove();maybeReturnToSingle();recalc();}));
    updateClientMode();
    recalc();
    <?php if($isEditingRejected): ?>
    setTimeout(()=>document.getElementById('nueva-venta')?.scrollIntoView({behavior:'smooth',block:'start'}),120);
    <?php endif; ?>
});
</script>
<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
