<?php
$title='Resumen mensual financiero';
ob_start();
?>
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Resumen mensual</h2>
        <p class="text-gray-600">Ventas por servicio, detalle de clientes y ahorro acumulado.</p>
    </div>
    <div class="flex items-end gap-2 flex-wrap">
        <?php if (($_SESSION['user_role'] ?? '') === ROLE_ADMIN): ?>
        <a href="<?= BASE_URL ?>/sobres" class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg font-bold border border-amber-200">
            <i class="fas fa-envelope-open-text mr-2"></i>Sobres
        </a>
        <?php endif; ?>
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="text-xs font-bold">Mes</label>
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="block px-3 py-2 border rounded-lg">
            </div>
            <button class="px-4 py-2 bg-blue-700 text-white rounded-lg">Consultar</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Cantidad vendida</div>
        <div class="text-2xl font-bold text-blue-800"><?= (int)$totalOperations ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Total vendido</div>
        <div class="text-2xl font-bold text-indigo-800">$<?= number_format($totalSalesAmount,2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Ingresos validados</div>
        <div class="text-2xl font-bold text-green-700">$<?= number_format((float)$income['total'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Resultado del mes</div>
        <div class="text-2xl font-bold <?= $monthlyNet>=0?'text-green-700':'text-red-700' ?>">$<?= number_format($monthlyNet,2) ?></div>
        <div class="text-xs text-gray-500">Ingresos - egresos</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-bold text-lg mb-1">Operaciones por servicio</h3>
        <p class="text-sm text-gray-500 mb-4">Cantidad de trámites o servicios vendidos en el mes.</p>
        <div style="height:300px"><canvas id="monthlyServicesChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-bold text-lg mb-1">Ingresos vs egresos</h3>
        <p class="text-sm text-gray-500 mb-4">Comparación del dinero confirmado durante el mes.</p>
        <div style="height:300px"><canvas id="monthlyIncomeExpenseChart"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow overflow-hidden mb-6">
    <div class="px-5 py-4 border-b">
        <h3 class="font-bold text-lg">Servicios vendidos en el mes</h3>
        <p class="text-sm text-gray-500">Cada servicio muestra cantidad, importe y el detalle de quién lo realizó.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="p-3 text-left">Servicio</th>
                    <th class="p-3 text-center">Cantidad</th>
                    <th class="p-3 text-right">Total vendido</th>
                    <th class="p-3 text-left">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y align-top">
            <?php foreach($serviceGroups as $g):
                $detailKey=(string)$g['service_code'].'|'.(string)$g['service_name_snapshot'];
                $detailRows=$serviceDetails[$detailKey]??[];
            ?>
                <tr>
                    <td class="p-3 font-semibold"><?= htmlspecialchars($g['service_name_snapshot']) ?></td>
                    <td class="p-3 text-center font-bold"><?= (int)$g['quantity_total'] ?></td>
                    <td class="p-3 text-right font-bold">$<?= number_format((float)$g['amount_total'],2) ?></td>
                    <td class="p-3 min-w-[520px]">
                        <details>
                            <summary class="cursor-pointer inline-flex px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-bold">
                                Ver <?= count($detailRows) ?> registro(s)
                            </summary>
                            <div class="mt-3 border rounded-lg overflow-hidden bg-white">
                                <table class="w-full text-xs">
                                    <thead class="bg-gray-50 text-gray-500 uppercase">
                                        <tr>
                                            <th class="p-2 text-left">Fecha</th>
                                            <th class="p-2 text-left">Cliente</th>
                                            <th class="p-2 text-left">Folio</th>
                                            <th class="p-2 text-left">Asesora</th>
                                            <th class="p-2 text-center">Cant.</th>
                                            <th class="p-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                    <?php foreach($detailRows as $d): ?>
                                        <tr>
                                            <td class="p-2"><?= date('d/m/Y',strtotime($d['contract_date'])) ?></td>
                                            <td class="p-2 font-semibold"><?= htmlspecialchars($d['client_name_snapshot']) ?></td>
                                            <td class="p-2"><?= htmlspecialchars($d['client_folio_snapshot']?:$d['sale_folio']) ?></td>
                                            <td class="p-2"><?= htmlspecialchars($d['advisor_name']??'') ?></td>
                                            <td class="p-2 text-center"><?= (int)$d['quantity'] ?></td>
                                            <td class="p-2 text-right font-bold">$<?= number_format((float)$d['total_amount'],2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$serviceGroups): ?>
                <tr><td colspan="4" class="p-10 text-center text-gray-500">No hay ventas aprobadas en este mes.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td class="p-3">TOTAL</td>
                    <td class="p-3 text-center"><?= (int)$totalOperations ?></td>
                    <td class="p-3 text-right">$<?= number_format($totalSalesAmount,2) ?></td>
                    <td class="p-3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-bold text-lg mb-4">Movimiento del mes</h3>
        <div class="space-y-3">
            <div class="flex justify-between border-b pb-2"><span>Efectivo recibido</span><strong>$<?= number_format((float)$income['cash'],2) ?></strong></div>
            <div class="flex justify-between border-b pb-2"><span>Terminal recibido</span><strong>$<?= number_format((float)$income['terminal'],2) ?></strong></div>
            <div class="flex justify-between border-b pb-2"><span>Transferencias recibidas</span><strong>$<?= number_format((float)$income['transfer'],2) ?></strong></div>
            <div class="flex justify-between border-b pb-2 text-red-700"><span>Egresos (sólo efectivo)</span><strong>-$<?= number_format($expenses,2) ?></strong></div>
            <div class="flex justify-between pt-1 text-lg"><span>Resultado del mes</span><strong>$<?= number_format($monthlyNet,2) ?></strong></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-bold text-lg mb-4">Ahorro y saldos acumulados</h3>
        <div class="space-y-3">
            <div class="flex justify-between border-b pb-2">
                <span>Ahorro al cierre del mes anterior</span>
                <strong>$<?= number_format($previousCashSavings,2) ?></strong>
            </div>
            <div class="flex justify-between border-b pb-2 text-green-700">
                <span>+ Efectivo recibido este mes</span>
                <strong>$<?= number_format((float)$income['cash'],2) ?></strong>
            </div>
            <div class="flex justify-between border-b pb-2 text-red-700">
                <span>- Egresos de este mes</span>
                <strong>$<?= number_format($expenses,2) ?></strong>
            </div>
            <div class="flex justify-between border-b pb-3 text-lg">
                <span>Ahorro acumulado al cierre del mes</span>
                <strong class="<?= $cashSavings>=0?'text-green-700':'text-red-700' ?>">$<?= number_format($cashSavings,2) ?></strong>
            </div>
            <div class="flex justify-between"><span>Saldo Terminal</span><strong>$<?= number_format((float)$balances['TERMINAL'],2) ?></strong></div>
            <div class="flex justify-between"><span>Saldo Transferencia</span><strong>$<?= number_format((float)$balances['TRANSFERENCIA'],2) ?></strong></div>
            <div class="flex justify-between pt-2 border-t"><span>Total de las 3 cuentas</span><strong>$<?= number_format((float)$balances['TOTAL'],2) ?></strong></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const moneyFormat = (value) => new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: 'MXN'
    }).format(value || 0);

    const serviceLabels = <?= json_encode(array_values(array_map(fn($g) => $g['service_name_snapshot'], $serviceGroups)), JSON_UNESCAPED_UNICODE) ?>;
    const serviceQuantities = <?= json_encode(array_values(array_map(fn($g) => (int)$g['quantity_total'], $serviceGroups))) ?>;

    const servicesCanvas = document.getElementById('monthlyServicesChart');
    if (servicesCanvas && typeof Chart !== 'undefined') {
        new Chart(servicesCanvas, {
            type: 'bar',
            data: {
                labels: serviceLabels,
                datasets: [{
                    label: 'Cantidad',
                    data: serviceQuantities,
                    backgroundColor: 'rgba(37, 99, 235, .75)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 1,
                    borderRadius: 7
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    const movementCanvas = document.getElementById('monthlyIncomeExpenseChart');
    if (movementCanvas && typeof Chart !== 'undefined') {
        new Chart(movementCanvas, {
            type: 'bar',
            data: {
                labels: ['Ingresos', 'Egresos'],
                datasets: [{
                    label: 'Importe',
                    data: [<?= json_encode((float)$income['total']) ?>, <?= json_encode((float)$expenses) ?>],
                    backgroundColor: ['rgba(22, 163, 74, .75)', 'rgba(220, 38, 38, .75)'],
                    borderColor: ['rgb(22, 163, 74)', 'rgb(220, 38, 38)'],
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => moneyFormat(ctx.raw) } }
                },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => moneyFormat(v) } } }
            }
        });
    }
});
</script>

<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
