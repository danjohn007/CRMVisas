<?php
$title = 'Detalle de Traslado';
ob_start();
$months = [1=>'ENE',2=>'FEB',3=>'MAR',4=>'ABR',5=>'MAY',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SEP',10=>'OCT',11=>'NOV',12=>'DIC'];
$ts = strtotime($transfer['transfer_date']);
$transferTitle = 'TRASLADO ' . date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
$isFinished = $transfer['status'] === 'FINALIZADO';
$capacity = (int)$transfer['capacity'];
$occupiedMap = array_fill_keys(array_map('intval',$occupiedSeats), true);
?>

<style>
.trip-page{display:grid;gap:18px}.trip-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;padding:24px 26px;border-radius:28px;background:rgba(255,255,255,.86);border:1px solid rgba(15,33,65,.08);box-shadow:0 16px 40px rgba(15,33,65,.05)}
.trip-title{font-size:28px;font-weight:800;color:#111827;letter-spacing:-.03em}.trip-sub{margin-top:6px;color:#64748b;font-size:13px}.trip-actions{display:flex;gap:10px;flex-wrap:wrap}.trip-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 15px;border-radius:15px;border:1px solid #dbe3ec;background:#fff;color:#334155;font-weight:700}.trip-btn.primary{background:linear-gradient(90deg,#123b78,#2c77c8);color:#fff;border-color:transparent}.trip-btn.danger{background:#fff1f2;color:#be123c;border-color:#fecdd3}.trip-btn.success{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.trip-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.trip-stat{padding:17px 18px;border-radius:21px;background:rgba(255,255,255,.86);border:1px solid rgba(15,33,65,.08);box-shadow:0 12px 28px rgba(15,33,65,.04)}.trip-stat small{display:block;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}.trip-stat strong{display:block;margin-top:7px;font-size:24px;color:#111827}.trip-progress{grid-column:1/-1;padding:17px 18px;border-radius:21px;background:rgba(255,255,255,.86);border:1px solid rgba(15,33,65,.08)}.progress-row{display:flex;justify-content:space-between;gap:10px;align-items:center;font-size:12px;color:#64748b;margin-bottom:8px}.bar{height:9px;background:#e9eef5;border-radius:999px;overflow:hidden}.bar>span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#123b78,#2c77c8)}
.trip-board{background:rgba(255,255,255,.9);border:1px solid rgba(15,33,65,.08);border-radius:26px;box-shadow:0 16px 40px rgba(15,33,65,.05);overflow:hidden}.board-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;padding:18px 20px;border-bottom:1px solid #edf1f5}.board-head h3{margin:0;font-size:17px;font-weight:800;color:#111827}.board-head p{margin:4px 0 0;color:#64748b;font-size:12px}.table-scroll{overflow-x:auto}.trip-table{width:100%;min-width:930px;border-collapse:collapse}.trip-table th{padding:13px 15px;text-align:left;background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8edf3}.trip-table td{padding:14px 15px;border-bottom:1px solid #edf1f5;color:#1f2937;font-size:13px}.trip-table tbody tr{transition:.18s ease}.trip-table tbody tr.clickable{cursor:pointer}.trip-table tbody tr.clickable:hover{background:#f8fbff}.advisor-badge{display:inline-flex;align-items:center;gap:7px}.advisor-dot{width:9px;height:9px;border-radius:50%;background:#2c77c8}.seat-pill{display:inline-flex;min-width:34px;height:31px;padding:0 9px;align-items:center;justify-content:center;border-radius:10px;background:#eef4fb;color:#123b78;font-weight:800}.ready-btn{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;border:1px solid #cbd5e1;background:#fff;color:#cbd5e1;cursor:pointer;margin:auto}.ready-btn.ready{background:#111827;color:#fff;border-color:#111827}.ready-static{width:31px;height:31px;border-radius:50%;display:grid;place-items:center;margin:auto;background:#eef2f7;color:#94a3b8}.ready-static.ready{background:#111827;color:#fff}.empty-row{text-align:center!important;padding:44px!important;color:#64748b!important}.privacy-note{padding:12px 16px;border-radius:16px;background:#eff6ff;border:1px solid #dbeafe;color:#1e40af;font-size:12px}
.modal-layer{position:fixed;inset:0;z-index:80;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.48);backdrop-filter:blur(4px)}.modal-layer.open{display:flex}.client-modal{width:min(690px,100%);max-height:92vh;overflow-y:auto;background:#fff;border-radius:28px;padding:23px;box-shadow:0 30px 90px rgba(15,23,42,.28)}.client-modal-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}.client-modal-head h3{font-size:20px;font-weight:800;margin:0}.close-modal{width:38px;height:38px;border-radius:12px;border:1px solid #e5e7eb;background:#fff}.client-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.cf label{display:block;font-size:11px;font-weight:800;color:#475569;margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em}.cf input,.cf select{width:100%;border:1px solid #d9e0e9;border-radius:14px;padding:11px 12px;background:#fff}.cf.full{grid-column:1/-1}.modal-footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:20px;padding-top:16px;border-top:1px solid #edf1f5}.modal-footer-right{display:flex;gap:9px;margin-left:auto}.btn-soft{padding:10px 14px;border-radius:13px;border:1px solid #dbe3eb;background:#fff;color:#475569;font-weight:700}.btn-remove{padding:10px 14px;border-radius:13px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c;font-weight:700}.helper{font-size:11px;color:#7c8797;margin-top:6px}
@media(max-width:900px){.trip-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.trip-stats{grid-template-columns:1fr 1fr}.trip-title{font-size:23px}.client-form-grid{grid-template-columns:1fr}.cf.full{grid-column:auto}.trip-head{padding:20px}.trip-btn{width:100%;justify-content:center}.trip-actions{width:100%}}
</style>

<div class="trip-page">
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="<?= BASE_URL ?>/traslados" class="hover:text-blue-700"><i class="fas fa-arrow-left mr-1"></i> Traslados</a>
        <span>/</span><span><?= htmlspecialchars($transferTitle) ?></span>
    </div>

    <section class="trip-head">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h2 class="trip-title"><?= htmlspecialchars($transferTitle) ?></h2>
                <?php if ($isFinished): ?>
                    <span class="px-3 py-1 rounded-full bg-slate-200 text-slate-600 text-xs font-bold">Finalizado</span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Activo</span>
                <?php endif; ?>
            </div>
            <div class="trip-sub"><?= htmlspecialchars($transfer['vehicle_label']) ?> · Capacidad <?= $capacity ?> personas</div>
            <?php if (!empty($transfer['notes'])): ?><div class="trip-sub mt-2"><i class="fas fa-note-sticky mr-1"></i><?= htmlspecialchars($transfer['notes']) ?></div><?php endif; ?>
        </div>

        <div class="trip-actions">
            <?php if ($canEdit): ?>
                <button type="button" class="trip-btn primary" onclick="openAddClientModal()"><i class="fas fa-user-plus"></i> Agregar cliente</button>
            <?php endif; ?>
            <?php if ($canCreateCloseReopen && !$isFinished): ?>
                <form method="POST" action="<?= BASE_URL ?>/traslados/cerrar/<?= (int)$transfer['id'] ?>" onsubmit="return confirm('¿Cerrar este traslado? Dejará de ser visible para las asesoras.');">
                    <button type="submit" class="trip-btn danger"><i class="fas fa-lock"></i> Cerrar traslado</button>
                </form>
            <?php elseif ($canCreateCloseReopen && $isFinished): ?>
                <form method="POST" action="<?= BASE_URL ?>/traslados/reabrir/<?= (int)$transfer['id'] ?>" onsubmit="return confirm('¿Reabrir este traslado? Volverá a estar disponible para las asesoras.');">
                    <button type="submit" class="trip-btn success"><i class="fas fa-lock-open"></i> Reabrir traslado</button>
                </form>
            <?php endif; ?>
            <?php if (!empty($canDelete)): ?>
                <form method="POST" action="<?= BASE_URL ?>/traslados/eliminar/<?= (int)$transfer['id'] ?>" onsubmit="return confirm('¿Eliminar definitivamente este traslado y toda su lista de clientes? Esta acción no se puede deshacer.');" class="inline-block">
                    <button type="submit" class="trip-btn danger"><i class="fas fa-trash"></i> Eliminar traslado</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <section class="trip-stats">
        <div class="trip-stat"><small>Clientes registrados</small><strong><?= (int)$stats['total'] ?></strong></div>
        <div class="trip-stat"><small>Todo listo</small><strong><?= (int)$stats['ready'] ?></strong></div>
        <div class="trip-stat"><small>Pendientes</small><strong><?= (int)$stats['pending'] ?></strong></div>
        <div class="trip-stat"><small>Lugares disponibles</small><strong><?= (int)$stats['available'] ?></strong></div>
        <div class="trip-progress">
            <div class="progress-row"><span>Ocupación del traslado</span><strong><?= (int)$stats['total'] ?> / <?= $capacity ?> lugares</strong></div>
            <div class="bar"><span style="width:<?= (float)$stats['occupancy_percent'] ?>%"></span></div>
        </div>
    </section>

    <?php if ($isAdvisor): ?>
    <div class="privacy-note"><i class="fas fa-user-shield mr-1"></i> Como asesora, aquí sólo aparecen los clientes que están bajo tu responsabilidad.</div>
    <?php endif; ?>

    <section class="trip-board">
        <div class="board-head">
            <div>
                <h3>Lista del traslado</h3>
                <p><?= $isFinished ? 'Consulta histórica. Este traslado ya no puede modificarse.' : 'Haz clic en un registro para editarlo. Marca TODO LISTO cuando el cliente ya tenga todo preparado.' ?></p>
            </div>
            <?php if (!$isFinished): ?><span class="text-xs text-slate-500"><?= (int)$stats['ready'] ?> de <?= (int)$stats['total'] ?> clientes listos</span><?php endif; ?>
        </div>

        <div class="table-scroll">
            <table class="trip-table">
                <thead>
                    <tr>
                        <th>ASESORA</th>
                        <th>NÚMERO DE LUGAR</th>
                        <th>NOMBRE DEL CLIENTE</th>
                        <th>TELÉFONO</th>
                        <th>CITA PARA REC Y REV</th>
                        <th class="text-center">TODO LISTO</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $rowCanEdit = $canEdit && ($canManageAllClients || (int)$row['advisor_id'] === (int)$_SESSION['user_id']);
                    $rowData = [
                        'id'=>(int)$row['id'],'advisor_id'=>(int)$row['advisor_id'],'seat_number'=>(int)$row['seat_number'],
                        'client_name'=>$row['client_name'],'phone'=>$row['phone'] ?? '',
                        'review_appointment'=>!empty($row['review_appointment']) ? date('Y-m-d\TH:i',strtotime($row['review_appointment'])) : '',
                    ];
                ?>
                    <tr class="<?= $rowCanEdit ? 'clickable' : '' ?>" <?= $rowCanEdit ? 'onclick=\'openEditClientModal(' . json_encode($rowData,JSON_HEX_APOS|JSON_HEX_QUOT) . ')\'' : '' ?>>
                        <td><span class="advisor-badge"><span class="advisor-dot"></span><?= htmlspecialchars($row['advisor_name'] ?? 'Asesora') ?></span></td>
                        <td><span class="seat-pill"><?= str_pad((string)$row['seat_number'],2,'0',STR_PAD_LEFT) ?></span></td>
                        <td>
                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($row['client_name']) ?></div>
                            <?php if (!empty($row['application_folio'])): ?><div class="text-[11px] text-slate-400 mt-1"><?= htmlspecialchars($row['application_folio']) ?></div><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                        <td><?= !empty($row['review_appointment']) ? date('d/m/Y · H:i',strtotime($row['review_appointment'])) : '—' ?></td>
                        <td class="text-center">
                            <?php if ($rowCanEdit): ?>
                            <form method="POST" action="<?= BASE_URL ?>/traslados/cliente/listo/<?= (int)$row['id'] ?>" onclick="event.stopPropagation()">
                                <button type="submit" class="ready-btn <?= !empty($row['is_ready']) ? 'ready' : '' ?>" title="Cambiar estado TODO LISTO"><i class="fas fa-check"></i></button>
                            </form>
                            <?php else: ?>
                            <span class="ready-static <?= !empty($row['is_ready']) ? 'ready' : '' ?>"><?php if (!empty($row['is_ready'])): ?><i class="fas fa-check"></i><?php endif; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="empty-row">No hay clientes registrados en este traslado<?= $isAdvisor ? ' bajo tu responsabilidad' : '' ?>.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php if ($canEdit): ?>
<div id="add-client-modal" class="modal-layer" role="dialog" aria-modal="true">
    <div class="client-modal">
        <div class="client-modal-head"><div><h3>Agregar cliente al traslado</h3><p class="text-xs text-slate-500 mt-1">Selecciona un expediente o captura el cliente manualmente.</p></div><button type="button" class="close-modal" onclick="closeAddClientModal()"><i class="fas fa-xmark"></i></button></div>
        <form method="POST" action="<?= BASE_URL ?>/traslados/cliente">
            <input type="hidden" name="transfer_id" value="<?= (int)$transfer['id'] ?>">
            <div class="client-form-grid">
                <div class="cf full">
                    <label>Buscar expediente existente (opcional)</label>
                    <input type="text" id="app-search" placeholder="Buscar por nombre o folio..." autocomplete="off">
                    <select id="application_id" name="application_id" class="mt-2">
                        <option value="0">Capturar cliente manualmente</option>
                        <?php foreach($applications as $app): ?>
                        <option value="<?= (int)$app['id'] ?>" data-name="<?= htmlspecialchars($app['display_name'],ENT_QUOTES) ?>" data-phone="<?= htmlspecialchars($app['phone'],ENT_QUOTES) ?>"><?= htmlspecialchars($app['display_name']) ?> · <?= htmlspecialchars($app['folio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="helper">Si seleccionas un expediente, nombre y teléfono se precargan.</div>
                </div>
                <?php if ($canManageAllClients): ?>
                <div class="cf"><label>Asesora *</label><select name="advisor_id" required><option value="">Seleccione...</option><?php foreach($advisors as $advisor): ?><option value="<?= (int)$advisor['id'] ?>"><?= htmlspecialchars($advisor['full_name']) ?></option><?php endforeach; ?></select></div>
                <?php else: ?><input type="hidden" name="advisor_id" value="<?= (int)$_SESSION['user_id'] ?>"><?php endif; ?>
                <div class="cf"><label>Número de lugar *</label><select name="seat_number" required><option value="">Seleccione...</option><?php foreach($availableSeats as $seat): ?><option value="<?= (int)$seat ?>"><?= str_pad((string)$seat,2,'0',STR_PAD_LEFT) ?></option><?php endforeach; ?></select></div>
                <div class="cf"><label>Nombre del cliente *</label><input id="client_name" type="text" name="client_name" maxlength="200" required></div>
                <div class="cf"><label>Teléfono *</label><input id="client_phone" type="text" name="phone" maxlength="30" required></div>
                <div class="cf full"><label>Cita para REC Y REV *</label><input type="datetime-local" name="review_appointment" required></div>
            </div>
            <div class="modal-footer"><span></span><div class="modal-footer-right"><button type="button" class="btn-soft" onclick="closeAddClientModal()">Cancelar</button><button type="submit" class="btn-primary px-5 py-3 rounded-xl font-bold">Agregar cliente</button></div></div>
        </form>
    </div>
</div>

<div id="edit-client-modal" class="modal-layer" role="dialog" aria-modal="true">
    <div class="client-modal">
        <div class="client-modal-head"><div><h3>Editar cliente</h3><p class="text-xs text-slate-500 mt-1">Actualiza la información operativa del traslado.</p></div><button type="button" class="close-modal" onclick="closeEditClientModal()"><i class="fas fa-xmark"></i></button></div>
        <form id="edit-client-form" method="POST" action="">
            <div class="client-form-grid">
                <?php if ($canManageAllClients): ?>
                <div class="cf"><label>Asesora *</label><select id="edit_advisor_id" name="advisor_id" required><?php foreach($advisors as $advisor): ?><option value="<?= (int)$advisor['id'] ?>"><?= htmlspecialchars($advisor['full_name']) ?></option><?php endforeach; ?></select></div>
                <?php endif; ?>
                <div class="cf"><label>Número de lugar *</label><select id="edit_seat_number" name="seat_number" required><?php for($seat=1;$seat<=$capacity;$seat++): ?><option value="<?= $seat ?>" data-occupied="<?= isset($occupiedMap[$seat]) ? '1':'0' ?>"><?= str_pad((string)$seat,2,'0',STR_PAD_LEFT) ?></option><?php endfor; ?></select></div>
                <div class="cf"><label>Nombre del cliente *</label><input id="edit_client_name" type="text" name="client_name" maxlength="200" required></div>
                <div class="cf"><label>Teléfono *</label><input id="edit_phone" type="text" name="phone" maxlength="30" required></div>
                <div class="cf full"><label>Cita para REC Y REV *</label><input id="edit_review_appointment" type="datetime-local" name="review_appointment" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="delete-client-btn" class="btn-remove"><i class="fas fa-trash mr-1"></i> Retirar cliente</button>
                <div class="modal-footer-right"><button type="button" class="btn-soft" onclick="closeEditClientModal()">Cancelar</button><button type="submit" class="btn-primary px-5 py-3 rounded-xl font-bold">Guardar cambios</button></div>
            </div>
        </form>
        <form id="delete-client-form" method="POST" action="" class="hidden"></form>
    </div>
</div>
<?php endif; ?>

<script>
const baseUrl = <?= json_encode(BASE_URL) ?>;
function openAddClientModal(){ document.getElementById('add-client-modal')?.classList.add('open'); }
function closeAddClientModal(){ document.getElementById('add-client-modal')?.classList.remove('open'); }
function closeEditClientModal(){ document.getElementById('edit-client-modal')?.classList.remove('open'); }
function openEditClientModal(data){
    const modal=document.getElementById('edit-client-modal'); if(!modal) return;
    document.getElementById('edit-client-form').action=baseUrl+'/traslados/cliente/actualizar/'+data.id;
    const adv=document.getElementById('edit_advisor_id'); if(adv) adv.value=String(data.advisor_id);
    const seat=document.getElementById('edit_seat_number');
    [...seat.options].forEach(opt=>{ opt.disabled = opt.dataset.occupied==='1' && Number(opt.value)!==Number(data.seat_number); });
    seat.value=String(data.seat_number);
    document.getElementById('edit_client_name').value=data.client_name||'';
    document.getElementById('edit_phone').value=data.phone||'';
    document.getElementById('edit_review_appointment').value=data.review_appointment||'';
    document.getElementById('delete-client-form').action=baseUrl+'/traslados/cliente/eliminar/'+data.id;
    document.getElementById('delete-client-btn').onclick=function(){ if(confirm('¿Retirar a este cliente del traslado?')) document.getElementById('delete-client-form').submit(); };
    modal.classList.add('open');
}
const appSelect=document.getElementById('application_id');
if(appSelect){
    appSelect.addEventListener('change',function(){ const opt=this.options[this.selectedIndex]; if(Number(this.value)>0){ document.getElementById('client_name').value=opt.dataset.name||''; document.getElementById('client_phone').value=opt.dataset.phone||''; }});
    const search=document.getElementById('app-search');
    search?.addEventListener('input',function(){ const q=this.value.toLowerCase().trim(); [...appSelect.options].forEach((opt,i)=>{ if(i===0){opt.hidden=false;return;} opt.hidden=q!==''&&!opt.text.toLowerCase().includes(q); }); });
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeAddClientModal();closeEditClientModal();}});
</script>

<?php $content=ob_get_clean(); require ROOT_PATH.'/app/views/layouts/main.php'; ?>
