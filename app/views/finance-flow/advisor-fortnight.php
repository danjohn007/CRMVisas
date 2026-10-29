<?php
$title=$isAdvisor?'Mis ventas por quincena':'Ventas de asesoras';
ob_start();
?>
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800"><?= $isAdvisor?'Mis ventas por quincena':'Ventas y horas extra de asesoras' ?></h2>
        <p class="text-gray-600">Consulta sencilla por periodo.</p>
    </div>
    <?php if($isAdvisor): ?>
    <a href="<?= BASE_URL ?>/ingresos" class="px-4 py-2 rounded-lg bg-gray-800 text-white font-semibold">
        <i class="fas fa-arrow-left mr-2"></i>Mis ventas
    </a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl shadow p-5 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-<?= $isAdvisor?'3':'4' ?> gap-3 items-end">
        <div>
            <label class="text-sm font-bold">Desde</label>
            <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div>
            <label class="text-sm font-bold">Hasta</label>
            <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="w-full px-3 py-2 border rounded-lg">
        </div>
        <?php if(!$isAdvisor): ?>
        <div>
            <label class="text-sm font-bold">Asesora</label>
            <select name="advisor_id" class="w-full px-3 py-2 border rounded-lg">
                <option value="0">Todas</option>
                <?php foreach($advisors as $a): ?>
                <option value="<?= (int)$a['id'] ?>" <?= $selectedAdvisorId===(int)$a['id']?'selected':'' ?>>
                    <?= htmlspecialchars($a['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button class="px-4 py-2 bg-blue-700 text-white rounded-lg font-bold">Filtrar</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-<?= $isAdvisor?'3':'4' ?> gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Periodo</div>
        <div class="font-bold"><?= date('d/m/Y',strtotime($start)) ?> al <?= date('d/m/Y',strtotime($end)) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Cantidad de citas</div>
        <div class="font-bold text-2xl text-indigo-800"><?= count($sales) ?></div>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Personas</div>
        <div class="font-bold text-2xl text-blue-800"><?= (int)$totalOperations ?></div>
    </div>
    <?php if(!$isAdvisor): ?>
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs uppercase text-gray-500 font-bold">Total vendido</div>
        <div class="font-bold text-2xl text-green-700">$<?= number_format($totalAmount,2) ?></div>
    </div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-2xl shadow overflow-hidden mb-6">
    <div class="px-5 py-4 border-b">
        <h3 class="font-bold text-lg">Ventas del periodo</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="p-3 text-left">Fecha</th>
                    <?php if(!$isAdvisor): ?><th class="p-3 text-left">Asesora</th><?php endif; ?>
                    <th class="p-3 text-left">Cliente</th>
                    <th class="p-3 text-left">Folio</th>
                    <th class="p-3 text-left">Servicio</th>
                    <th class="p-3 text-center">Personas</th>
                    <?php if(!$isAdvisor): ?><th class="p-3 text-right">Total</th><?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y">
            <?php foreach($sales as $sale): ?>
                <tr>
                    <td class="p-3"><?= date('d/m/Y',strtotime($sale['contract_date'])) ?></td>
                    <?php if(!$isAdvisor): ?><td class="p-3"><?= htmlspecialchars($sale['advisor_name']??'') ?></td><?php endif; ?>
                    <td class="p-3 font-semibold"><?= htmlspecialchars($sale['client_name_snapshot']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($sale['client_folio_snapshot']?:$sale['sale_folio']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($sale['service_name_snapshot']) ?></td>
                    <td class="p-3 text-center"><?= (int)$sale['quantity'] ?></td>
                    <?php if(!$isAdvisor): ?><td class="p-3 text-right font-bold">$<?= number_format((float)$sale['total_amount'],2) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if(!$sales): ?>
                <tr><td colspan="<?= $isAdvisor?'5':'7' ?>" class="p-8 text-center text-gray-500">No hay ventas aprobadas en este periodo.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-2xl shadow p-5">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
        <div>
            <h3 class="font-bold text-lg">Horas extra</h3>
            <p class="text-sm text-gray-500">Total del periodo: <strong><?= number_format($overtimeHours,2) ?> horas</strong></p>
        </div>
    </div>

    <?php if($isAdvisor): ?>
    <form method="POST" action="<?= BASE_URL ?>/ventas-quincenales/horas-extra"
          class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5 border rounded-xl p-4 bg-gray-50">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="period_start" value="<?= htmlspecialchars($start) ?>">
        <input type="hidden" name="period_end" value="<?= htmlspecialchars($end) ?>">
        <div>
            <label class="text-xs font-bold">Fecha *</label>
            <input type="date" name="work_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-lg bg-white">
        </div>
        <div>
            <label class="text-xs font-bold">Horas *</label>
            <input type="number" name="hours" min="0.25" max="24" step="0.25" required class="w-full px-3 py-2 border rounded-lg bg-white">
        </div>
        <div>
            <label class="text-xs font-bold">Motivo *</label>
            <input name="description" required maxlength="500" placeholder="Ej. Cita con cliente" class="w-full px-3 py-2 border rounded-lg bg-white">
        </div>
        <div class="flex items-end">
            <button class="w-full bg-indigo-700 text-white rounded-lg py-2 font-bold">Agregar</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="p-3 text-left">Fecha</th>
                    <?php if(!$isAdvisor): ?><th class="p-3 text-left">Asesora</th><?php endif; ?>
                    <th class="p-3 text-left">Motivo</th>
                    <th class="p-3 text-right">Horas</th>
                </tr>
            </thead>
            <tbody class="divide-y">
            <?php foreach($overtime as $o): ?>
                <tr>
                    <td class="p-3"><?= date('d/m/Y',strtotime($o['work_date'])) ?></td>
                    <?php if(!$isAdvisor): ?><td class="p-3"><?= htmlspecialchars($o['advisor_name']??'') ?></td><?php endif; ?>
                    <td class="p-3"><?= htmlspecialchars($o['description']) ?></td>
                    <td class="p-3 text-right font-bold"><?= number_format((float)$o['hours'],2) ?> h</td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$overtime): ?>
                <tr><td colspan="<?= $isAdvisor?'3':'4' ?>" class="p-6 text-center text-gray-500">Sin horas extra en este periodo.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
