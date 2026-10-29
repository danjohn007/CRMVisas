<?php
$title='Balance Semanal';
ob_start();
$s=$summary;

$movements=[];
foreach($incomeMovements as $r){
    $movements[]=[
        'date'=>$r['payment_date'],
        'type'=>'INGRESO',
        'detail'=>$r['client_name_snapshot'].' · '.$r['service_name_snapshot'],
        'folio'=>$r['client_folio_snapshot'] ?: $r['sale_folio'],
        'account'=>financePaymentMethodAccountLabel($r['payment_method']),
        'reference'=>$r['reference']?:'—',
        'amount'=>(float)$r['amount'],
    ];
}
foreach($expenseMovements as $e){
    $movements[]=[
        'date'=>$e['expense_date'],
        'type'=>'EGRESO',
        'detail'=>$e['concept'],
        'folio'=>$e['expense_folio']??('#'.$e['id']),
        'account'=>'Efectivo',
        'reference'=>$e['reference']?:'—',
        'amount'=>-(float)$e['amount'],
    ];
}
usort($movements,function($a,$b){
    if($a['date']===$b['date']) return strcmp($a['type'],$b['type']);
    return strcmp($a['date'],$b['date']);
});
?>
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Balance Semanal</h2>
        <p class="text-gray-600">Lo esencial de la semana: cuánto entró, cuánto salió y cuánto debe haber en cada cuenta.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/control-ingresos" class="px-4 py-2 bg-gray-900 text-white rounded-lg font-semibold">Control de Ingresos</a>
        <a href="<?= BASE_URL ?>/egresos" class="px-4 py-2 bg-red-700 text-white rounded-lg font-semibold">Egresos</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <a href="<?= BASE_URL ?>/balance-semanal?week_start=<?= urlencode($previousWeek) ?>" class="px-3 py-2 bg-gray-100 rounded-lg">
        <i class="fas fa-chevron-left mr-1"></i>Anterior
    </a>
    <div class="text-center">
        <div class="text-xs uppercase text-gray-500 font-bold">Semana</div>
        <div class="text-xl font-bold text-blue-900"><?= date('d/m/Y',strtotime($weekStart)) ?> al <?= date('d/m/Y',strtotime($weekEnd)) ?></div>
        <div class="mt-1">
            <?php if(($closure['status']??'ABIERTO')==='CERRADO'): ?>
                <span class="px-3 py-1 rounded-full bg-gray-900 text-white text-xs font-bold">CERRADO</span>
            <?php else: ?>
                <span class="px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-bold">ABIERTO</span>
            <?php endif; ?>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/balance-semanal?week_start=<?= urlencode($nextWeek) ?>" class="px-3 py-2 bg-gray-100 rounded-lg">
        Siguiente<i class="fas fa-chevron-right ml-1"></i>
    </a>
</div>

<?php if($pendingReviewCount>0): ?>
<div class="bg-amber-50 border border-amber-300 text-amber-900 p-4 rounded-xl mb-6">
    Hay <strong><?= (int)$pendingReviewCount ?></strong> venta(s) o pago(s) pendientes de revisión. Deben revisarse antes del cierre.
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-xs uppercase text-gray-500 font-bold">Ingresos validados</div>
        <div class="text-3xl font-bold text-green-700 mt-1">$<?= number_format((float)$s['total_income'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-xs uppercase text-gray-500 font-bold">Egresos</div>
        <div class="text-3xl font-bold text-red-700 mt-1">$<?= number_format((float)$s['expenses'],2) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <div class="text-xs uppercase text-gray-500 font-bold">Resultado semanal</div>
        <div class="text-3xl font-bold mt-1 <?= $s['net']>=0?'text-blue-800':'text-red-700' ?>">$<?= number_format((float)$s['net'],2) ?></div>
        <div class="text-xs text-gray-500">Ingresos - egresos</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-bold text-lg mb-1">Ingresos vs egresos</h3>
        <p class="text-sm text-gray-500 mb-4">Comparación directa de la semana seleccionada.</p>
        <div style="height:260px"><canvas id="weeklyIncomeExpenseChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5">
        <h3 class="font-bold text-lg mb-1">Cómo ingresó el dinero</h3>
        <p class="text-sm text-gray-500 mb-4">Distribución entre Efectivo, Terminal y Transferencia.</p>
        <div style="height:260px"><canvas id="weeklyPaymentMethodChart"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow p-5 mb-6">
    <h3 class="font-bold text-lg mb-4">Cuánto debe haber en las cuentas</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
            <div class="text-xs uppercase text-green-800 font-bold">Efectivo / Ahorro</div>
            <div class="text-2xl font-bold text-green-800">$<?= number_format((float)$accountBalances['EFECTIVO'],2) ?></div>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
            <div class="text-xs uppercase text-purple-800 font-bold">Terminal</div>
            <div class="text-2xl font-bold text-purple-800">$<?= number_format((float)$accountBalances['TERMINAL'],2) ?></div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <div class="text-xs uppercase text-blue-800 font-bold">Transferencia</div>
            <div class="text-2xl font-bold text-blue-800">$<?= number_format((float)$accountBalances['TRANSFERENCIA'],2) ?></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow overflow-hidden mb-6">
    <div class="p-5 border-b">
        <h3 class="font-bold text-lg">Movimientos de la semana</h3>
        <p class="text-sm text-gray-500">Ingresos validados y egresos en una sola tabla.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Tipo</th>
                    <th class="p-3 text-left">Cliente / concepto</th>
                    <th class="p-3 text-left">Folio</th>
                    <th class="p-3 text-left">Cuenta</th>
                    <th class="p-3 text-left">Referencia</th>
                    <th class="p-3 text-right">Importe</th>
                </tr>
            </thead>
            <tbody class="divide-y">
            <?php foreach($movements as $m): ?>
                <tr>
                    <td class="p-3"><?= date('d/m/Y',strtotime($m['date'])) ?></td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded-full text-xs font-bold <?= $m['type']==='INGRESO'?'bg-green-100 text-green-800':'bg-red-100 text-red-800' ?>">
                            <?= $m['type'] ?>
                        </span>
                    </td>
                    <td class="p-3"><?= htmlspecialchars($m['detail']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($m['folio']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($m['account']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($m['reference']) ?></td>
                    <td class="p-3 text-right font-bold <?= $m['amount']>=0?'text-green-700':'text-red-700' ?>">
                        <?= $m['amount']>=0?'+':'-' ?>$<?= number_format(abs($m['amount']),2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$movements): ?>
                <tr><td colspan="7" class="p-8 text-center text-gray-500">No hay movimientos registrados en esta semana.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-2xl shadow p-5">
    <?php if(($closure['status']??'ABIERTO')==='ABIERTO'): ?>
        <?php if(!$canCloseByDate): ?>
        <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            El cierre estará disponible a partir del viernes <strong><?= date('d/m/Y',strtotime($closeAvailableDate)) ?></strong>.
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/balance-semanal/cerrar" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <input type="hidden" name="week_start" value="<?= htmlspecialchars($weekStart) ?>">
            <div class="md:col-span-3">
                <label class="text-sm font-bold">Observaciones del cierre</label>
                <textarea name="closing_notes" rows="2" class="w-full px-3 py-2 border rounded-lg" <?= !$canCloseByDate?'disabled':'' ?>></textarea>
            </div>
            <button class="px-5 py-3 bg-gray-900 text-white rounded-xl font-bold"
                    <?= (!$canCloseByDate || $pendingReviewCount>0)?'disabled style="opacity:.5;cursor:not-allowed"':'' ?>>
                <i class="fas fa-lock mr-2"></i><?= !$canCloseByDate?'Disponible el viernes':'Cerrar balance' ?>
            </button>
        </form>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <div class="font-bold">Balance cerrado</div>
                <div class="text-sm text-gray-500">
                    <?= htmlspecialchars($closure['closed_by_name']??'') ?>
                    · <?= !empty($closure['closed_at'])?date('d/m/Y H:i',strtotime($closure['closed_at'])):'' ?>
                </div>
                <?php if(!empty($closure['closing_notes'])): ?><div class="mt-2 text-sm"><?= nl2br(htmlspecialchars($closure['closing_notes'])) ?></div><?php endif; ?>
            </div>
            <?php if($isAdmin): ?>
            <form method="POST" action="<?= BASE_URL ?>/balance-semanal/correccion/<?= (int)$closure['id'] ?>" class="flex gap-2 items-end">
                <div>
                    <label class="text-xs font-bold">Motivo de corrección</label>
                    <input name="reason" required class="px-3 py-2 border rounded-lg">
                </div>
                <button class="px-4 py-2 bg-amber-600 text-white rounded-lg font-bold">Autorizar 30 min</button>
            </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const moneyFormat = (value) => new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: 'MXN'
    }).format(value || 0);

    const incomeExpenseCanvas = document.getElementById('weeklyIncomeExpenseChart');
    if (incomeExpenseCanvas && typeof Chart !== 'undefined') {
        new Chart(incomeExpenseCanvas, {
            type: 'bar',
            data: {
                labels: ['Ingresos', 'Egresos'],
                datasets: [{
                    label: 'Importe',
                    data: [
                        <?= json_encode((float)$s['total_income']) ?>,
                        <?= json_encode((float)$s['expenses']) ?>
                    ],
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

    const methodCanvas = document.getElementById('weeklyPaymentMethodChart');
    if (methodCanvas && typeof Chart !== 'undefined') {
        new Chart(methodCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Efectivo', 'Terminal', 'Transferencia'],
                datasets: [{
                    data: [
                        <?= json_encode((float)$s['cash']) ?>,
                        <?= json_encode((float)$s['terminal']) ?>,
                        <?= json_encode((float)$s['transfer']) ?>
                    ],
                    backgroundColor: ['rgba(22, 163, 74, .78)', 'rgba(147, 51, 234, .78)', 'rgba(37, 99, 235, .78)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${moneyFormat(ctx.raw)}` } }
                }
            }
        });
    }
});
</script>

<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
