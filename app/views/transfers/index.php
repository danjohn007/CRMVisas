<?php
$title = 'Traslados';
ob_start();

$monthNames = [1=>'ENE',2=>'FEB',3=>'MAR',4=>'ABR',5=>'MAY',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SEP',10=>'OCT',11=>'NOV',12=>'DIC'];
$formatTransferTitle = function($date) use ($monthNames) {
    $ts = strtotime($date);
    return 'TRASLADO ' . date('d', $ts) . ' ' . $monthNames[(int)date('n', $ts)] . ' ' . date('Y', $ts);
};
?>

<style>
.transfer-page { display: grid; gap: 20px; }
.transfer-hero {
    display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;
    padding:24px 26px; border-radius:28px;
    background:linear-gradient(135deg, rgba(18,59,120,.98), rgba(44,119,200,.94));
    color:#fff; box-shadow:0 20px 50px rgba(18,59,120,.18);
}
.transfer-hero h2 { margin:0; font-size:30px; font-weight:800; letter-spacing:-.03em; }
.transfer-hero p { margin:7px 0 0; color:rgba(255,255,255,.8); max-width:720px; font-size:14px; }
.transfer-create-btn { display:inline-flex; align-items:center; gap:9px; padding:12px 17px; border-radius:16px; background:#fff; color:#123b78; font-weight:800; box-shadow:0 12px 26px rgba(0,0,0,.12); }
.transfer-tabs { display:flex; gap:10px; padding:6px; width:max-content; max-width:100%; border-radius:18px; background:rgba(255,255,255,.78); border:1px solid rgba(15,33,65,.07); box-shadow:0 10px 28px rgba(15,33,65,.04); }
.transfer-tab { border:0; background:transparent; padding:10px 18px; border-radius:13px; color:#6b7280; font-weight:700; cursor:pointer; }
.transfer-tab.active { background:linear-gradient(90deg,#123b78,#2c77c8); color:#fff; box-shadow:0 9px 20px rgba(18,59,120,.16); }
.transfer-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }
.transfer-card { background:rgba(255,255,255,.88); border:1px solid rgba(15,33,65,.08); border-radius:24px; padding:20px; box-shadow:0 14px 34px rgba(15,33,65,.05); transition:.25s ease; }
.transfer-card:hover { transform:translateY(-3px); box-shadow:0 20px 46px rgba(18,59,120,.10); }
.transfer-card-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
.transfer-date { font-size:18px; font-weight:800; color:#111827; line-height:1.25; }
.transfer-meta { margin-top:6px; color:#6b7280; font-size:12px; }
.status-pill { display:inline-flex; align-items:center; gap:6px; padding:7px 10px; border-radius:999px; font-size:11px; font-weight:800; }
.status-active { background:#dcfce7; color:#166534; }
.status-finished { background:#e5e7eb; color:#4b5563; }
.transfer-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin:18px 0; }
.transfer-stat { background:#f8fafc; border:1px solid #eef2f6; border-radius:16px; padding:12px; text-align:center; }
.transfer-stat strong { display:block; color:#111827; font-size:18px; }
.transfer-stat span { display:block; margin-top:3px; color:#7c8797; font-size:10px; text-transform:uppercase; letter-spacing:.04em; }
.progress-track { height:9px; background:#e9eef5; border-radius:999px; overflow:hidden; }
.progress-fill { height:100%; background:linear-gradient(90deg,#123b78,#2c77c8); border-radius:999px; }
.transfer-card-foot { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:16px; }
.transfer-open { display:inline-flex; align-items:center; gap:7px; padding:10px 14px; border-radius:14px; background:#123b78; color:#fff; font-weight:700; font-size:13px; }
.transfer-open:hover { color:#fff; background:#0d2f61; }
.empty-card { grid-column:1/-1; padding:42px; text-align:center; background:rgba(255,255,255,.72); border:1px dashed #cbd5e1; border-radius:24px; color:#64748b; }
.modal-backdrop { position:fixed; inset:0; z-index:70; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.48); backdrop-filter:blur(4px); }
.modal-backdrop.open { display:flex; }
.modal-card { width:min(560px,100%); background:#fff; border-radius:28px; padding:24px; box-shadow:0 30px 90px rgba(15,23,42,.28); }
.modal-title-row { display:flex; justify-content:space-between; gap:14px; align-items:center; margin-bottom:18px; }
.modal-title-row h3 { margin:0; font-size:21px; font-weight:800; }
.modal-close { width:38px; height:38px; border-radius:12px; border:1px solid #e5e7eb; background:#fff; cursor:pointer; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.field label { display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:7px; }
.field input,.field select,.field textarea { width:100%; border:1px solid #d9e0e9; border-radius:14px; padding:11px 12px; background:#fff; }
.field-full { grid-column:1/-1; }
.modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
.btn-muted { padding:11px 15px; border-radius:14px; border:1px solid #dbe2ea; background:#fff; font-weight:700; color:#475569; }
@media(max-width:1100px){.transfer-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:700px){.transfer-grid{grid-template-columns:1fr}.transfer-hero h2{font-size:24px}.form-grid{grid-template-columns:1fr}.field-full{grid-column:auto}.transfer-tabs{width:100%}.transfer-tab{flex:1;padding:10px 12px}.transfer-stats{grid-template-columns:repeat(3,1fr)}}
</style>

<div class="transfer-page">
    <section class="transfer-hero">
        <div>
            <h2>Traslados</h2>
            <p>Organiza clientes, lugares y citas de recolección/revisión sin mezclar información financiera.</p>
        </div>
        <?php if ($canCreate): ?>
        <button type="button" class="transfer-create-btn" onclick="openTransferModal()">
            <i class="fas fa-plus"></i> Crear nuevo traslado
        </button>
        <?php endif; ?>
    </section>

    <div class="transfer-tabs" role="tablist">
        <button class="transfer-tab active" id="tab-active" type="button" onclick="switchTransferTab('active')">Activos</button>
        <?php if ($canSeeHistory): ?>
        <button class="transfer-tab" id="tab-finished" type="button" onclick="switchTransferTab('finished')">Finalizados</button>
        <?php endif; ?>
    </div>

    <section id="panel-active" class="transfer-grid">
        <?php foreach ($activeTransfers as $transfer):
            $capacity = max(1,(int)$transfer['capacity']);
            $clients = (int)$transfer['client_count'];
            $ready = (int)$transfer['ready_count'];
            $occupancy = min(100, round(($clients/$capacity)*100));
        ?>
        <article class="transfer-card">
            <div class="transfer-card-head">
                <div>
                    <div class="transfer-date"><?= htmlspecialchars($formatTransferTitle($transfer['transfer_date'])) ?></div>
                    <div class="transfer-meta"><?= htmlspecialchars($transfer['vehicle_label']) ?></div>
                </div>
                <span class="status-pill status-active"><i class="fas fa-circle text-[8px]"></i> Activo</span>
            </div>

            <div class="transfer-stats">
                <div class="transfer-stat"><strong><?= $clients ?></strong><span>Clientes</span></div>
                <div class="transfer-stat"><strong><?= $ready ?></strong><span>Listos</span></div>
                <div class="transfer-stat"><strong><?= max(0,$capacity-$clients) ?></strong><span>Disponibles</span></div>
            </div>

            <div class="progress-track"><div class="progress-fill" style="width:<?= $occupancy ?>%"></div></div>

            <div class="transfer-card-foot">
                <span class="text-xs text-slate-500"><?= $clients ?> de <?= $capacity ?> lugares ocupados</span>
                <a class="transfer-open" href="<?= BASE_URL ?>/traslados/ver/<?= (int)$transfer['id'] ?>">Abrir <i class="fas fa-arrow-right"></i></a>
            </div>
        </article>
        <?php endforeach; ?>

        <?php if (empty($activeTransfers)): ?>
        <div class="empty-card">
            <i class="fas fa-bus text-3xl text-slate-300 mb-3"></i>
            <div class="font-semibold text-slate-700">No hay traslados activos</div>
            <div class="text-sm mt-1">Cuando se cree un traslado aparecerá aquí.</div>
        </div>
        <?php endif; ?>
    </section>

    <?php if ($canSeeHistory): ?>
    <section id="panel-finished" class="transfer-grid hidden">
        <?php foreach ($finishedTransfers as $transfer):
            $capacity = max(1,(int)$transfer['capacity']);
            $clients = (int)$transfer['client_count'];
            $ready = (int)$transfer['ready_count'];
        ?>
        <article class="transfer-card">
            <div class="transfer-card-head">
                <div>
                    <div class="transfer-date"><?= htmlspecialchars($formatTransferTitle($transfer['transfer_date'])) ?></div>
                    <div class="transfer-meta"><?= htmlspecialchars($transfer['vehicle_label']) ?></div>
                </div>
                <span class="status-pill status-finished"><i class="fas fa-check"></i> Finalizado</span>
            </div>

            <div class="transfer-stats">
                <div class="transfer-stat"><strong><?= $clients ?></strong><span>Clientes</span></div>
                <div class="transfer-stat"><strong><?= $ready ?></strong><span>Listos</span></div>
                <div class="transfer-stat"><strong><?= $capacity ?></strong><span>Capacidad</span></div>
            </div>

            <div class="transfer-card-foot">
                <span class="text-xs text-slate-500"><?= !empty($transfer['closed_at']) ? 'Cerrado ' . date('d/m/Y', strtotime($transfer['closed_at'])) : 'Histórico' ?></span>
                <a class="transfer-open" href="<?= BASE_URL ?>/traslados/ver/<?= (int)$transfer['id'] ?>">Consultar <i class="fas fa-arrow-right"></i></a>
            </div>
        </article>
        <?php endforeach; ?>

        <?php if (empty($finishedTransfers)): ?>
        <div class="empty-card">
            <i class="fas fa-box-archive text-3xl text-slate-300 mb-3"></i>
            <div class="font-semibold text-slate-700">Aún no hay traslados finalizados</div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php if ($canCreate): ?>
<div id="transfer-modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="transfer-modal-title">
    <div class="modal-card">
        <div class="modal-title-row">
            <div>
                <h3 id="transfer-modal-title">Crear nuevo traslado</h3>
                <p class="text-sm text-slate-500 mt-1">Cada fecha tendrá su propia lista independiente.</p>
            </div>
            <button class="modal-close" type="button" onclick="closeTransferModal()"><i class="fas fa-xmark"></i></button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/traslados/crear">
            <div class="form-grid">
                <div class="field">
                    <label>Fecha del traslado *</label>
                    <input type="date" name="transfer_date" required>
                </div>
                <div class="field">
                    <label>Vehículo / capacidad *</label>
                    <select name="capacity" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($capacityOptions as $capacity => $label): ?>
                        <option value="<?= (int)$capacity ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field field-full">
                    <label>Observaciones internas (opcional)</label>
                    <textarea name="notes" rows="3" placeholder="Información operativa del traslado..."></textarea>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-muted" onclick="closeTransferModal()">Cancelar</button>
                <button type="submit" class="btn-primary px-5 py-3 rounded-xl font-bold">Crear traslado</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function switchTransferTab(tab) {
    const activePanel = document.getElementById('panel-active');
    const finishedPanel = document.getElementById('panel-finished');
    const activeButton = document.getElementById('tab-active');
    const finishedButton = document.getElementById('tab-finished');

    if (tab === 'finished' && finishedPanel) {
        activePanel.classList.add('hidden');
        finishedPanel.classList.remove('hidden');
        activeButton.classList.remove('active');
        finishedButton.classList.add('active');
    } else {
        activePanel.classList.remove('hidden');
        if (finishedPanel) finishedPanel.classList.add('hidden');
        activeButton.classList.add('active');
        if (finishedButton) finishedButton.classList.remove('active');
    }
}
function openTransferModal() { const el=document.getElementById('transfer-modal'); if(el) el.classList.add('open'); }
function closeTransferModal() { const el=document.getElementById('transfer-modal'); if(el) el.classList.remove('open'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeTransferModal(); });
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
