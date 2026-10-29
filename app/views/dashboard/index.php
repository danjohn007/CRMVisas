<?php
$title = 'Dashboard';
ob_start();

$isAsesor = (($_SESSION['user_role'] ?? '') === ROLE_ASESOR);
$userName = trim($_SESSION['user_name'] ?? 'Usuario');
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
$roleLabel = $_SESSION['user_role'] ?? 'Usuario';
$financial = $stats['financial'] ?? ['total_costs' => 0, 'total_paid' => 0, 'total_balance' => 0];
$statusCount = count($stats['by_status'] ?? []);
$recentCount = count($stats['recent_applications'] ?? []);
$appointmentCount = count($stats['appointments'] ?? []);
$advisorRejectedSales = $isAsesor ? getAdvisorRejectedSalesCount() : 0;
?>

<style>
.dashboard-shell { display: grid; gap: 20px; }
.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 30px;
    background:
        radial-gradient(circle at 85% 20%, rgba(255,255,255,.22), transparent 16%),
        radial-gradient(circle at 12% 90%, rgba(199,164,90,.18), transparent 18%),
        linear-gradient(135deg, #11326c 0%, #19549d 58%, #0f2b5d 100%);
    color: white;
    padding: 28px;
    box-shadow: 0 24px 70px rgba(17, 50, 108, .22);
}
.hero-card::after {
    content: "";
    position: absolute;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,.20), transparent 70%);
    right: -120px;
    bottom: -120px;
}
.hero-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(280px, .95fr); gap: 22px; position: relative; z-index: 1; }
.hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.14);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 16px;
}
.hero-title { margin: 0; font-size: clamp(28px, 4vw, 40px); line-height: 1.04; font-weight: 800; letter-spacing: -.04em; }
.hero-subtitle { margin: 12px 0 0; max-width: 650px; color: rgba(255,255,255,.84); font-size: 15px; line-height: 1.75; }
.hero-tags { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.14);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}
.hero-tag:hover { background: rgba(255,255,255,.18); color: #fff; }
.hero-insights { display: grid; gap: 14px; }
.hero-mini-card {
    border-radius: 24px;
    padding: 18px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.16);
    backdrop-filter: blur(10px);
}
.hero-mini-card small { display: block; color: rgba(255,255,255,.74); font-size: 12px; margin-bottom: 8px; }
.hero-mini-card strong { display: block; font-size: 28px; font-weight: 800; }
.hero-mini-card p { margin: 8px 0 0; color: rgba(255,255,255,.8); font-size: 13px; line-height: 1.55; }
.metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
.metric-card {
    background: rgba(255,255,255,.84);
    border: 1px solid rgba(15,33,65,.08);
    border-radius: 24px;
    padding: 20px;
    box-shadow: 0 14px 34px rgba(15, 33, 65, .05);
}
.metric-card-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.metric-eyebrow { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
.metric-value { font-size: 30px; font-weight: 800; color: #0f172a; line-height: 1; }
.metric-note { margin-top: 10px; color: #6b7280; font-size: 13px; line-height: 1.5; }
.metric-icon {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    font-size: 18px;
    color: white;
    box-shadow: 0 12px 26px rgba(15, 33, 65, .12);
}
.metric-icon.primary { background: linear-gradient(135deg, #123b78, #2c77c8); }
.metric-icon.success { background: linear-gradient(135deg, #059669, #34d399); }
.metric-icon.warning { background: linear-gradient(135deg, #b45309, #f59e0b); }
.metric-icon.danger { background: linear-gradient(135deg, #be123c, #fb7185); }
.widget-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.widget-card {
    background: rgba(255,255,255,.88);
    border-radius: 26px;
    border: 1px solid rgba(15,33,65,.08);
    box-shadow: 0 16px 42px rgba(15,33,65,.05);
    overflow: hidden;
}
.widget-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 20px 22px 0;
}
.widget-title { margin: 0; font-size: 18px; font-weight: 800; color: #111827; }
.widget-subtitle { margin-top: 6px; color: #6b7280; font-size: 13px; line-height: 1.55; }
.widget-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: rgba(44,119,200,.10);
    color: #123b78;
    display: grid;
    place-items: center;
}
.widget-body { padding: 18px 22px 22px; }
.finance-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.finance-pill {
    border-radius: 18px;
    padding: 16px;
    border: 1px solid rgba(15,33,65,.08);
    background: #fff;
}
.finance-pill small { display: block; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.finance-pill strong { display: block; margin-top: 8px; font-size: 21px; color: #0f172a; }
.finance-bottom { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
.finance-balance {
    border-radius: 18px;
    padding: 15px 16px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border: 1px solid rgba(15,33,65,.07);
}
.finance-balance span { display: block; color: #64748b; font-size: 12px; }
.finance-balance strong { display: block; margin-top: 8px; font-size: 20px; font-weight: 800; }
.summary-list { display: grid; gap: 10px; }
.summary-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 16px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
}
.summary-item strong { color: #1f2937; font-size: 14px; }
.summary-item span {
    min-width: 34px;
    height: 34px;
    padding: 0 12px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(90deg, #123b78, #2c77c8);
    color: white;
    font-size: 13px;
    font-weight: 800;
}
.calendar-shell { display: grid; gap: 18px; }
.calendar-grid { gap: 6px !important; }
.recent-table-card .table-container { box-shadow: none; border: 0; }
.table-status {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.quick-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
}
.quick-stat small { color: #64748b; font-size: 12px; display: block; }
.quick-stat strong { display: block; margin-top: 4px; font-size: 18px; color: #111827; }
@media (max-width: 1200px) {
    .metric-grid, .finance-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hero-grid, .widget-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .hero-card { padding: 22px; border-radius: 24px; }
    .metric-grid, .widget-grid, .finance-grid, .finance-bottom { grid-template-columns: 1fr; }
    .widget-head, .widget-body { padding-left: 18px; padding-right: 18px; }
}
</style>

<div class="dashboard-shell">
    <section class="hero-card">
        <div class="hero-grid">
            <div>
                <span class="hero-kicker"><i class="fas fa-star"></i> <?= $greeting ?> · <?= htmlspecialchars($roleLabel) ?></span>
                <h2 class="hero-title">Bienvenido a tu área de trabajo, <?= htmlspecialchars($userName) ?>.</h2>
                <p class="hero-subtitle">Consulta solicitudes, citas e indicadores desde un solo lugar.</p>

                <div class="hero-tags">
                    <a class="hero-tag" href="<?= BASE_URL ?>/solicitudes"><i class="fas fa-file-lines"></i> Ver solicitudes</a>
                    <?php if ($isAsesor): ?>
                        <a class="hero-tag" href="<?= BASE_URL ?>/ingresos"><i class="fas fa-sack-dollar"></i> Mis ventas</a>
                        <a class="hero-tag" href="<?= BASE_URL ?>/whatsapp"><i class="fab fa-whatsapp"></i> Conversaciones</a>
                    <?php else: ?>
                        <a class="hero-tag" href="<?= BASE_URL ?>/balance-semanal"><i class="fas fa-calendar-week"></i> Balance semanal</a>
                        <a class="hero-tag" href="<?= BASE_URL ?>/reportes"><i class="fas fa-chart-pie"></i> Reportes</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-insights">
                <div class="hero-mini-card">
                    <small>Solicitudes activas en el sistema</small>
                    <strong><?= number_format($stats['total_applications'] ?? 0) ?></strong>
                </div>
                <div class="hero-mini-card">
                    <small>Actividad reciente</small>
                    <strong><?= number_format($recentCount) ?></strong>
                </div>
            </div>
        </div>
    </section>

    <?php if($isAsesor && $advisorRejectedSales>0): ?>
    <a href="<?= BASE_URL ?>/ingresos" class="block rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-900 font-semibold shadow-sm">
        <i class="fas fa-circle-exclamation mr-2"></i>Tienes <?= (int)$advisorRejectedSales ?> venta(s) rechazada(s) por corregir.
    </a>
    <?php endif; ?>

    <section class="metric-grid">
        <article class="metric-card">
            <div class="metric-card-top">
                <div>
                    <div class="metric-eyebrow">Solicitudes</div>
                    <div class="metric-value"><?= number_format($stats['total_applications'] ?? 0) ?></div>
                </div>
                <div class="metric-icon primary"><i class="fas fa-folder-open"></i></div>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-card-top">
                <div>
                    <div class="metric-eyebrow">Estatus activos</div>
                    <div class="metric-value"><?= number_format($statusCount) ?></div>
                </div>
                <div class="metric-icon success"><i class="fas fa-chart-pie"></i></div>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-card-top">
                <div>
                    <div class="metric-eyebrow">Citas registradas</div>
                    <div class="metric-value"><?= number_format($appointmentCount) ?></div>
                </div>
                <div class="metric-icon warning"><i class="fas fa-calendar-check"></i></div>
            </div>
        </article>

        <article class="metric-card">
            <div class="metric-card-top">
                <div>
                    <div class="metric-eyebrow"><?= $isAsesor ? 'Actividad reciente' : 'Saldo pendiente' ?></div>
                    <div class="metric-value"><?= $isAsesor ? number_format($recentCount) : '$' . number_format($financial['total_balance'] ?? 0, 2) ?></div>
                </div>
                <div class="metric-icon danger"><i class="fas fa-wallet"></i></div>
            </div>
        </article>
    </section>

    <?php if (!$isAsesor && !empty($stats['weekly_financial'])):
        $weekly = $stats['weekly_financial'];
    ?>
    <section class="widget-card">
        <div class="widget-head">
            <div>
                <h3 class="widget-title">Resumen financiero semanal</h3>
                <p class="widget-subtitle">Semana actual de lunes a viernes · <?= date('d/m/Y', strtotime($weekly['week_start'])) ?> al <?= date('d/m/Y', strtotime($weekly['week_end'])) ?></p>
            </div>
            <a href="<?= BASE_URL ?>/balance-semanal" class="hero-tag" style="background: rgba(18,59,120,.08); color:#123b78; border-color:rgba(18,59,120,.08);">
                <i class="fas fa-arrow-up-right-from-square"></i> Ver balance semanal
            </a>
        </div>
        <div class="widget-body">
            <div class="finance-grid">
                <div class="finance-pill"><small>Ventas aprobadas</small><strong>$<?= number_format($weekly['sales_amount'], 2) ?></strong></div>
                <div class="finance-pill"><small>Efectivo recibido</small><strong>$<?= number_format($weekly['cash_income'], 2) ?></strong></div>
                <div class="finance-pill"><small>Terminal</small><strong>$<?= number_format($weekly['terminal_income'], 2) ?></strong></div>
                <div class="finance-pill"><small>Transferencia</small><strong>$<?= number_format($weekly['transfer_income'], 2) ?></strong></div>
                <div class="finance-pill"><small>Ingresos</small><strong>$<?= number_format($weekly['total_income'], 2) ?></strong></div>
                <div class="finance-pill"><small>Egresos</small><strong>$<?= number_format($weekly['expenses'], 2) ?></strong></div>
                <div class="finance-pill"><small>Resultado</small><strong class="<?= $weekly['net'] >= 0 ? 'text-emerald-700' : 'text-rose-700' ?>">$<?= number_format($weekly['net'], 2) ?></strong></div>
                <div class="finance-pill"><small>Pendiente de validar</small><strong>$<?= number_format($weekly['pending_validation'], 2) ?></strong></div>
            </div>
            <div class="finance-bottom">
                <div class="finance-balance"><span>Efectivo / ahorro acumulado</span><strong class="text-emerald-700">$<?= number_format($weekly['cash_balance'], 2) ?></strong></div>
                <div class="finance-balance"><span>Saldo terminal</span><strong class="text-violet-700">$<?= number_format($weekly['terminal_balance'], 2) ?></strong></div>
                <div class="finance-balance"><span>Saldo transferencia</span><strong class="text-sky-700">$<?= number_format($weekly['transfer_balance'], 2) ?></strong></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="widget-grid">
        <article class="widget-card">
            <div class="widget-head">
                <div>
                    <h3 class="widget-title">Solicitudes por estatus</h3>
                    
                </div>
                <div class="widget-icon"><i class="fas fa-chart-pie"></i></div>
            </div>
            <div class="widget-body">
                <div class="relative" style="height: 320px;"><canvas id="statusChart"></canvas></div>
            </div>
        </article>

        <article class="widget-card">
            <div class="widget-head">
                <div>
                    <h3 class="widget-title">Tendencia de solicitudes</h3>
                    
                </div>
                <div class="widget-icon"><i class="fas fa-chart-line"></i></div>
            </div>
            <div class="widget-body">
                <div class="relative" style="height: 320px;"><canvas id="trendChart"></canvas></div>
            </div>
        </article>

        <article class="widget-card">
            <div class="widget-head">
                <div>
                    <h3 class="widget-title">Tipos de solicitudes</h3>
                    
                </div>
                <div class="widget-icon"><i class="fas fa-chart-bar"></i></div>
            </div>
            <div class="widget-body">
                <div class="relative" style="height: 320px;"><canvas id="typeChart"></canvas></div>
            </div>
        </article>

        <article class="widget-card">
            <div class="widget-head">
                <div>
                    <h3 class="widget-title"><?= (!empty($stats['payments_by_method'])) ? 'Métodos de pago' : 'Resumen de solicitudes' ?></h3>

                </div>
                <div class="widget-icon"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="widget-body">
                <?php if (!empty($stats['payments_by_method'])): ?>
                    <div class="relative" style="height: 320px;"><canvas id="paymentMethodChart"></canvas></div>
                <?php else: ?>
                    <div class="summary-list">
                        <?php foreach ($stats['by_status'] ?? [] as $status): ?>
                            <div class="summary-item">
                                <strong><?= htmlspecialchars($status['status']) ?></strong>
                                <span><?= (int) $status['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($stats['by_status'])): ?>
                            <p class="text-gray-500 text-center py-6">No hay solicitudes registradas.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <?php if (!$isAsesor): ?>
    <section class="widget-grid">
        <article class="widget-card calendar-shell">
            <div class="widget-head">
                <div>
                    <h3 class="widget-title">Calendario de citas</h3>
                    
                </div>
                <div class="flex items-center gap-2">
                    <button id="prevMonth" class="icon-chip" type="button"><i class="fas fa-chevron-left"></i></button>
                    <span id="calendarTitle" class="text-sm font-semibold text-slate-700 min-w-[120px] text-center"></span>
                    <button id="nextMonth" class="icon-chip" type="button"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="widget-body">
                <div class="grid grid-cols-7 gap-1 mb-2">
                    <?php foreach (['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'] as $day): ?>
                    <div class="text-center text-xs font-semibold text-gray-500 py-2"><?= $day ?></div>
                    <?php endforeach; ?>
                </div>
                <div id="calendarGrid" class="grid grid-cols-7 calendar-grid"></div>
                <div id="dayDetail" class="hidden mt-4 p-4 bg-blue-50 rounded-2xl border border-blue-200">
                    <h4 class="font-semibold text-blue-900 mb-2" id="dayDetailTitle"></h4>
                    <div id="dayDetailContent"></div>
                </div>
            </div>
        </article>

        <article class="widget-card">
            <div class="widget-head">
                <div>
                    <h3 class="widget-title">Indicadores rápidos</h3>
                    
                </div>
                <div class="widget-icon"><i class="fas fa-bolt"></i></div>
            </div>
            <div class="widget-body space-y-3">
                <div class="quick-stat">
                    <div>
                        <small>Solicitudes recientes</small>
                        <strong><?= number_format($recentCount) ?></strong>
                    </div>
                    <i class="fas fa-file-lines text-primary text-xl"></i>
                </div>
                <div class="quick-stat">
                    <div>
                        <small>Total costos</small>
                        <strong>$<?= number_format($financial['total_costs'] ?? 0, 2) ?></strong>
                    </div>
                    <i class="fas fa-coins text-emerald-600 text-xl"></i>
                </div>
                <div class="quick-stat">
                    <div>
                        <small>Total pagado</small>
                        <strong>$<?= number_format($financial['total_paid'] ?? 0, 2) ?></strong>
                    </div>
                    <i class="fas fa-money-bill-wave text-sky-600 text-xl"></i>
                </div>
                <div class="quick-stat">
                    <div>
                        <small>Saldo pendiente</small>
                        <strong>$<?= number_format($financial['total_balance'] ?? 0, 2) ?></strong>
                    </div>
                    <i class="fas fa-circle-exclamation text-rose-600 text-xl"></i>
                </div>
            </div>
        </article>
    </section>

    <section class="widget-card recent-table-card">
        <div class="widget-head">
            <div>
                <h3 class="widget-title">Solicitudes recientes</h3>
                
            </div>
            <div class="widget-icon"><i class="fas fa-clock-rotate-left"></i></div>
        </div>
        <div class="widget-body pt-4">
            <div class="table-container">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="px-3 md:px-6 py-3 text-left whitespace-nowrap">Folio</th>
                            <th class="px-3 md:px-6 py-3 text-left whitespace-nowrap">Tipo</th>
                            <th class="px-3 md:px-6 py-3 text-left whitespace-nowrap">Estatus</th>
                            <th class="px-3 md:px-6 py-3 text-left whitespace-nowrap hidden md:table-cell">Creado por</th>
                            <th class="px-3 md:px-6 py-3 text-left whitespace-nowrap">Fecha</th>
                            <th class="px-3 md:px-6 py-3 text-left whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($stats['recent_applications'] ?? [] as $app): ?>
                            <?php
                            $statusClass = $app['status'] === STATUS_FINALIZADO ? 'bg-emerald-100 text-emerald-700' :
                                ($app['status'] === STATUS_APROBADO ? 'bg-sky-100 text-sky-700' :
                                ($app['status'] === STATUS_RECHAZADO ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'));
                            ?>
                            <tr>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap"><span class="font-mono text-sm text-primary font-semibold"><?= htmlspecialchars($app['folio']) ?></span></td>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($app['type']) ?></td>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap"><span class="table-status <?= $statusClass ?>"><?= htmlspecialchars($app['status']) ?></span></td>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell"><?= htmlspecialchars($app['creator_name']) ?></td>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y', strtotime($app['created_at'])) ?></td>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                    <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>" class="hero-tag" style="padding:8px 12px;background: rgba(18,59,120,.08); color:#123b78; border-color:rgba(18,59,120,.08);">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($stats['recent_applications'])): ?>
                        <tr>
                            <td colspan="6" class="px-3 md:px-6 py-10 text-center text-gray-500 text-sm md:text-base">No hay solicitudes recientes.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#123b78';
    const secondaryColor = getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim() || '#2c77c8';
    const goldColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-gold').trim() || '#c7a45a';

    function hexToRgba(hex, alpha) {
        const normalized = hex.replace('#', '');
        const bigint = parseInt(normalized, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function generateColors(count) {
        const colors = [primaryColor, secondaryColor, goldColor, '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
        return Array.from({length: count}, (_, i) => colors[i % colors.length]);
    }

    const defaultLegend = {
        position: 'bottom',
        labels: {
            boxWidth: 12,
            boxHeight: 12,
            usePointStyle: true,
            pointStyle: 'circle',
            padding: 16,
            color: '#475569',
            font: { size: 11, family: 'Inter, sans-serif' }
        }
    };

    <?php if (!empty($stats['by_status'])): ?>
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats['by_status'], 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['by_status'], 'count')) ?>,
                    backgroundColor: generateColors(<?= count($stats['by_status']) ?>),
                    borderWidth: 0,
                    hoverOffset: 8,
                    cutout: '64%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: defaultLegend }
            }
        });
    }
    <?php endif; ?>

    <?php if (!empty($stats['applications_by_month'])): ?>
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        const gradient = trendCtx.getContext('2d').createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, hexToRgba(primaryColor, .30));
        gradient.addColorStop(1, hexToRgba(primaryColor, .02));

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($item) {
                    $date = DateTime::createFromFormat('Y-m', $item['month']);
                    return $date ? $date->format('M Y') : $item['month'];
                }, $stats['applications_by_month'])) ?>,
                datasets: [{
                    label: 'Solicitudes',
                    data: <?= json_encode(array_column($stats['applications_by_month'], 'count')) ?>,
                    borderColor: primaryColor,
                    backgroundColor: gradient,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: primaryColor,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    borderWidth: 3,
                    tension: .35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#64748b' },
                        grid: { color: 'rgba(148,163,184,.16)' }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (!empty($stats['applications_by_type'])): ?>
    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats['applications_by_type'], 'type')) ?>,
                datasets: [{
                    label: 'Solicitudes',
                    data: <?= json_encode(array_column($stats['applications_by_type'], 'count')) ?>,
                    backgroundColor: generateColors(<?= count($stats['applications_by_type']) ?>),
                    borderRadius: 12,
                    borderSkipped: false,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#64748b' },
                        grid: { color: 'rgba(148,163,184,.16)' }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if (!$isAsesor): ?>
    const appointments = <?= json_encode(array_map(function($a) {
        $date = !empty($a['office_appointment_date'])
            ? $a['office_appointment_date']
            : ((!empty($a['is_canadian_visa']) && !empty($a['canadian_biometric_date']))
            ? $a['canadian_biometric_date']
            : ($a['appointment_date'] ?? ''));
        return [
            'id' => $a['id'],
            'folio' => $a['folio'] ?? '',
            'date' => substr($date, 0, 10),
            'type' => $a['type'] ?? '',
            'subtype' => $a['subtype'] ?? '',
            'creator' => $a['creator_name'] ?? '',
            'confirmed' => (bool)($a['appointment_confirmed_day_before'] ?? false),
        ];
    }, $stats['appointments'] ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    let calYear = new Date().getFullYear();
    let calMonth = new Date().getMonth();

    function renderCalendar(year, month) {
        const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        document.getElementById('calendarTitle').textContent = monthNames[month] + ' ' + year;

        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');

        const apptByDate = {};
        appointments.forEach(a => {
            if (!apptByDate[a.date]) apptByDate[a.date] = [];
            apptByDate[a.date].push(a);
        });

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'p-1';
            grid.appendChild(empty);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
            const appts = apptByDate[dateStr] || [];
            const isToday = dateStr === todayStr;
            const hasAppts = appts.length > 0;

            const cell = document.createElement('div');
            cell.className = 'relative min-h-[66px] rounded-2xl border text-center transition cursor-pointer p-2 ' +
                (isToday ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-slate-700 border-slate-200 hover:border-blue-200 hover:bg-blue-50') +
                (hasAppts ? ' ring-2 ring-orange-300 ring-offset-1' : '');

            const dayNum = document.createElement('span');
            dayNum.className = 'text-sm font-semibold block';
            dayNum.textContent = day;
            cell.appendChild(dayNum);

            if (hasAppts) {
                const dotWrap = document.createElement('div');
                dotWrap.className = 'flex justify-center gap-1 mt-2 flex-wrap';
                appts.slice(0, 3).forEach(a => {
                    const dot = document.createElement('span');
                    dot.className = 'w-2 h-2 rounded-full inline-block ' + (a.confirmed ? 'bg-emerald-500' : 'bg-amber-400');
                    dotWrap.appendChild(dot);
                });
                if (appts.length > 3) {
                    const more = document.createElement('span');
                    more.className = 'text-[11px] ' + (isToday ? 'text-blue-100' : 'text-slate-400');
                    more.textContent = '+' + (appts.length - 3);
                    dotWrap.appendChild(more);
                }
                cell.appendChild(dotWrap);
                cell.addEventListener('click', function() { showDayDetail(dateStr, appts); });
            } else {
                cell.style.cursor = 'default';
            }

            grid.appendChild(cell);
        }
    }

    function showDayDetail(dateStr, appts) {
        const detail = document.getElementById('dayDetail');
        const title = document.getElementById('dayDetailTitle');
        const content = document.getElementById('dayDetailContent');
        const parts = dateStr.split('-');
        title.textContent = 'Citas del ' + parts[2] + '/' + parts[1] + '/' + parts[0];
        content.innerHTML = '';
        appts.forEach(function(a) {
            const row = document.createElement('div');
            row.className = 'flex flex-col md:flex-row md:items-center md:justify-between gap-2 py-3 border-b border-blue-100 last:border-0';

            const info = document.createElement('div');
            info.innerHTML = '<div class="font-mono text-sm text-blue-800 font-semibold">' + a.folio + '</div>' +
                '<div class="text-xs text-slate-600 mt-1">' + a.type + (a.subtype ? ' - ' + a.subtype : '') + ' · ' + a.creator + '</div>';

            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center w-fit text-xs px-3 py-1 rounded-full ' + (a.confirmed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700');
            badge.innerHTML = a.confirmed ? '<i class="fas fa-check mr-1"></i>Confirmada' : '<i class="fas fa-clock mr-1"></i>Pendiente';

            row.appendChild(info);
            row.appendChild(badge);
            content.appendChild(row);
        });
        detail.classList.remove('hidden');
    }

    document.getElementById('prevMonth').addEventListener('click', function() {
        calMonth--;
        if (calMonth < 0) { calMonth = 11; calYear--; }
        document.getElementById('dayDetail').classList.add('hidden');
        renderCalendar(calYear, calMonth);
    });
    document.getElementById('nextMonth').addEventListener('click', function() {
        calMonth++;
        if (calMonth > 11) { calMonth = 0; calYear++; }
        document.getElementById('dayDetail').classList.add('hidden');
        renderCalendar(calYear, calMonth);
    });
    renderCalendar(calYear, calMonth);
    <?php endif; ?>

    <?php if (!empty($stats['payments_by_method'])): ?>
    const paymentCtx = document.getElementById('paymentMethodChart');
    if (paymentCtx) {
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats['payments_by_method'], 'payment_method')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['payments_by_method'], 'total')) ?>,
                    backgroundColor: generateColors(<?= count($stats['payments_by_method']) ?>),
                    borderWidth: 0,
                    hoverOffset: 8,
                    cutout: '62%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: defaultLegend,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + Number(context.parsed || 0).toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
