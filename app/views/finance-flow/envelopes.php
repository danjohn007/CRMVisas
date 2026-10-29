<?php
$title='Sobres';
ob_start();
$months=[1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
$cutoffMonth=date('Y-m',strtotime($cutoff));
?>
<style>
.envelope-page{display:grid;gap:20px}.envelope-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;flex-wrap:wrap;padding:24px 26px;border-radius:28px;background:linear-gradient(135deg,#10326a,#2c77c8);color:#fff;box-shadow:0 20px 50px rgba(16,50,106,.18)}.envelope-hero h2{font-size:30px;font-weight:800;margin:0}.envelope-hero p{margin:7px 0 0;color:rgba(255,255,255,.82);font-size:14px;max-width:760px}.envelope-card{background:rgba(255,255,255,.88);border:1px solid rgba(15,33,65,.08);border-radius:24px;padding:22px;box-shadow:0 14px 34px rgba(15,33,65,.05)}.envelope-form{display:grid;grid-template-columns:180px 220px 1fr auto;gap:14px;align-items:end}.field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:7px}.field input,.field select{width:100%;border:1px solid #d9e0e9;border-radius:14px;padding:11px 12px;background:#fff}.save-btn{height:46px;padding:0 18px;border:0;border-radius:14px;background:linear-gradient(90deg,#10326a,#2c77c8);color:#fff;font-weight:800;cursor:pointer}.note{padding:14px 16px;border-radius:16px;background:#fff7e8;border:1px solid #ead29c;color:#74591e;font-size:13px}.history-table{width:100%;border-collapse:collapse}.history-table th{background:#f8fafc;color:#64748b;text-align:left;padding:12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em}.history-table td{padding:14px 12px;border-top:1px solid #edf1f5}.amount{font-size:18px;font-weight:800;color:#10326a}.delete-btn{border:0;background:#fee2e2;color:#b91c1c;padding:8px 11px;border-radius:11px;font-weight:700;cursor:pointer}@media(max-width:900px){.envelope-form{grid-template-columns:1fr 1fr}.envelope-form .wide{grid-column:1/-1}}@media(max-width:600px){.envelope-form{grid-template-columns:1fr}.envelope-form .wide{grid-column:auto}}
</style>
<div class="envelope-page">
<section class="envelope-hero"><div><h2>Sobres</h2><p>Registra el efectivo real con el que cerraron los meses anteriores. Estos montos son sólo un historial de ahorro en efectivo: no se suman a ventas ni ingresos.</p></div><div class="text-sm font-semibold">Inicio automático: <?= date('d/m/Y',strtotime($cutoff)) ?></div></section>
<div class="note"><strong>Importante:</strong> captura aquí los cierres históricos, por ejemplo Julio $150,000, Agosto $200,000 y Septiembre $100,000. A partir de <?= $months[(int)date('n',strtotime($cutoff))] ?> <?= date('Y',strtotime($cutoff)) ?> el sistema continuará automáticamente con Efectivo recibido menos Egresos.</div>
<section class="envelope-card">
<h3 class="text-lg font-bold mb-4">Registrar / actualizar sobre mensual</h3>
<form method="POST" action="<?= BASE_URL ?>/sobres/guardar" class="envelope-form">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
<div class="field"><label>Mes</label><input type="month" name="month" max="<?= htmlspecialchars(date('Y-m',strtotime($cutoff.' -1 month'))) ?>" required></div>
<div class="field"><label>Efectivo al cierre</label><input type="text" name="amount" data-money-input placeholder="$0.00" required></div>
<div class="field wide"><label>Observaciones (opcional)</label><input type="text" name="notes" maxlength="255" placeholder="Ej. Efectivo acumulado antes del inicio del sistema"></div>
<button type="submit" class="save-btn"><i class="fas fa-floppy-disk mr-2"></i>Guardar sobre</button>
</form>
</section>
<section class="envelope-card overflow-x-auto">
<h3 class="text-lg font-bold mb-4">Historial de sobres</h3>
<table class="history-table"><thead><tr><th>Mes</th><th>Efectivo al cierre</th><th>Observaciones</th><th>Actualizado por</th><th></th></tr></thead><tbody>
<?php foreach($envelopes as $e): $ts=strtotime($e['month_start']); ?>
<tr><td class="font-semibold"><?= htmlspecialchars($months[(int)date('n',$ts)].' '.date('Y',$ts)) ?></td><td class="amount">$<?= number_format((float)$e['amount'],2) ?></td><td class="text-sm text-gray-600"><?= htmlspecialchars($e['notes']??'') ?></td><td class="text-sm text-gray-600"><?= htmlspecialchars($e['updated_by_name']?:$e['created_by_name']?:'') ?></td><td class="text-right"><form method="POST" action="<?= BASE_URL ?>/sobres/eliminar/<?= (int)$e['id'] ?>" onsubmit="return confirm('¿Eliminar este sobre histórico?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><button class="delete-btn" type="submit"><i class="fas fa-trash"></i></button></form></td></tr>
<?php endforeach; ?>
<?php if(!$envelopes): ?><tr><td colspan="5" class="text-center text-gray-500 py-10">Todavía no hay sobres históricos registrados.</td></tr><?php endif; ?>
</tbody></table>
</section>
</div>
<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
