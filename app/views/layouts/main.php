<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Tramite Visa Americana En Queretaro By Vanesa Torres' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php
    $primaryColor = getConfig('primary_color', '#123b78');
    $secondaryColor = getConfig('secondary_color', '#2c77c8');
    $accentGold = '#c7a45a';

    function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    $primaryRgb = hexToRgb($primaryColor);
    $secondaryRgb = hexToRgb($secondaryColor);
    $goldRgb = hexToRgb($accentGold);
    ?>
    <style>
        :root {
            --primary-color: <?= $primaryColor ?>;
            --secondary-color: <?= $secondaryColor ?>;
            --accent-gold: <?= $accentGold ?>;
            --primary-rgb: <?= $primaryRgb['r'] ?>, <?= $primaryRgb['g'] ?>, <?= $primaryRgb['b'] ?>;
            --secondary-rgb: <?= $secondaryRgb['r'] ?>, <?= $secondaryRgb['g'] ?>, <?= $secondaryRgb['b'] ?>;
            --gold-rgb: <?= $goldRgb['r'] ?>, <?= $goldRgb['g'] ?>, <?= $goldRgb['b'] ?>;
            --bg-base: #f6f1e7;
            --bg-soft: #fbf8f3;
            --text-main: #142132;
            --text-muted: #6f7d91;
            --line-soft: #dfe5ee;
            --sidebar-width: 270px;
            --sidebar-collapsed-width: 88px;
            --sidebar-bg: linear-gradient(180deg, #0d2858 0%, #103b78 48%, #1a5ba6 100%);
        }

        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(circle at 6% 8%, rgba(var(--secondary-rgb), .10), transparent 20%),
                radial-gradient(circle at 94% 84%, rgba(var(--gold-rgb), .10), transparent 18%),
                linear-gradient(180deg, #f8f4ec 0%, #f4efe6 100%);
            min-height: 100vh;
        }
        a { transition: color .2s ease, background .2s ease, border-color .2s ease, opacity .2s ease, transform .2s ease; }

        .bg-primary { background-color: var(--primary-color) !important; }
        .bg-secondary { background-color: var(--secondary-color) !important; }
        .text-primary { color: var(--primary-color) !important; }
        .border-primary { border-color: var(--primary-color) !important; }
        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            box-shadow: 0 10px 26px rgba(var(--primary-rgb), .18);
        }
        .btn-primary:hover { filter: brightness(1.03); transform: translateY(-1px); }

        .app-topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            margin: 14px;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,.9);
            background: rgba(255,255,255,.74);
            box-shadow: 0 18px 48px rgba(15, 33, 65, .08);
            backdrop-filter: blur(18px);
        }
        .app-topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
        }
        .brand-block {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }
        .menu-toggle, .desktop-toggle, .icon-chip {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid rgba(var(--primary-rgb), .12);
            background: rgba(var(--primary-rgb), .06);
            color: var(--primary-color);
            display: inline-grid;
            place-items: center;
            cursor: pointer;
        }
        .menu-toggle:hover, .desktop-toggle:hover, .icon-chip:hover {
            background: rgba(var(--primary-rgb), .1);
        }
        .brand-logo-inline {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            object-fit: contain;
            background: #fff;
            border: 1px solid rgba(var(--primary-rgb), .12);
            padding: 4px;
        }
        .brand-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1.18;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            max-width: 520px;
        }
        .brand-subtitle {
            margin-top: 4px;
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px 8px 8px;
            border-radius: 18px;
            border: 1px solid rgba(var(--primary-rgb), .1);
            background: rgba(255,255,255,.8);
        }
        .topbar-avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(var(--secondary-rgb), .14), rgba(var(--gold-rgb), .16));
            color: var(--primary-color);
            font-weight: 800;
        }
        .logout-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 16px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 12px 28px rgba(var(--primary-rgb), .18);
        }
        .logout-link:hover { color: white; transform: translateY(-1px); }

        .app-shell {
            display: flex;
            gap: 16px;
            padding: 0 14px 14px;
        }
        #sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
            min-height: calc(100vh - 110px);
            border-radius: 28px;
            background: var(--sidebar-bg);
            box-shadow: 0 24px 60px rgba(10, 35, 75, .22);
            color: rgba(255,255,255,.92);
            transition: width .28s ease, transform .28s ease;
            position: sticky;
            top: 96px;
            overflow: hidden;
        }
        .sidebar-inner {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 110px);
        }
        .sidebar-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 22px 18px 14px;
        }
        .sidebar-badge {
            width: 44px;
            height: 44px;
            border-radius: 16px;
            background: rgba(255,255,255,.14);
            display: grid;
            place-items: center;
            font-size: 18px;
            color: #f8e3ad;
        }
        .sidebar-brand-title {
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
        }
        .sidebar-brand-subtitle {
            margin-top: 4px;
            font-size: 12px;
            color: rgba(255,255,255,.68);
        }
        .sidebar-scroll {
            padding: 10px 12px 18px;
            overflow-y: auto;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 4px 0;
            padding: 13px 14px;
            border-radius: 18px;
            color: rgba(255,255,255,.86);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            position: relative;
        }
        .sidebar-link:hover {
            background: rgba(255,255,255,.10);
            color: #fff;
        }
        .sidebar-link.active {
            background: rgba(255,255,255,.14);
            color: white;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.12);
        }
        .sidebar-link.active::before {
            content: "";
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 24px;
            border-radius: 999px;
            background: linear-gradient(180deg, #f7df9e, #d3ab58);
        }
        .sidebar-link i {
            width: 22px;
            text-align: center;
            font-size: 15px;
            color: inherit;
            flex-shrink: 0;
        }
        .sidebar-link .sidebar-menu-text,
        .sidebar-head-copy,
        .sidebar-footer-copy { white-space: nowrap; }
        .sidebar-counter {
            margin-left: auto;
            min-width: 24px;
            height: 24px;
            padding: 0 7px;
            border-radius: 999px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-footer {
            margin-top: auto;
            padding: 14px 16px 18px;
            border-top: 1px solid rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-footer-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(255,255,255,.12);
            display: grid;
            place-items: center;
            color: #f8e3ad;
        }
        .sidebar-footer-title {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }
        .sidebar-footer-subtitle {
            font-size: 11px;
            color: rgba(255,255,255,.66);
        }

        .main-content {
            flex: 1;
            min-width: 0;
            padding: 4px 0 20px;
        }
        .content-area {
            min-height: calc(100vh - 120px);
            padding: 10px 8px;
        }
        .alert-box {
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid;
            margin-bottom: 14px;
            box-shadow: 0 12px 30px rgba(15, 33, 65, .04);
        }
        .alert-success { background: #effaf4; border-color: #bbf7d0; color: #166534; }
        .alert-error { background: #fff1f2; border-color: #fecdd3; color: #be123c; }

        /* Common panel improvements */
        .table-container,
        main .bg-white.rounded-lg.shadow,
        main .bg-white.rounded-lg,
        main .bg-white.shadow,
        main .bg-white.rounded-xl,
        main .bg-white.rounded-2xl,
        main .bg-white.border,
        main .rounded-lg.border,
        main .rounded-xl.border {
            border: 1px solid rgba(15, 33, 65, .07);
            box-shadow: 0 16px 42px rgba(15, 33, 65, .05);
        }
        .table-container {
            border-radius: 22px;
            overflow: hidden;
            background: rgba(255,255,255,.85);
        }
        .table-container table {
            min-width: 720px;
            width: 100%;
        }
        .table-container thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            border-bottom: 1px solid #e5eaf1;
        }
        .table-container tbody td {
            vertical-align: middle;
        }
        .table-container tbody tr:hover {
            background: rgba(var(--secondary-rgb), .03);
        }
        input, select, textarea {
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: rgba(var(--secondary-rgb), .58) !important;
            box-shadow: 0 0 0 4px rgba(var(--secondary-rgb), .12) !important;
        }
        .fixed.inset-0 .bg-white,
        .fixed.inset-0 .rounded-lg.bg-white,
        .fixed.inset-0 .rounded-xl.bg-white {
            border-radius: 24px !important;
            box-shadow: 0 28px 70px rgba(15, 33, 65, .22) !important;
            border: 1px solid rgba(15, 33, 65, .08);
        }

        /* Desktop collapsed state */
        body.sidebar-collapsed #sidebar {
            width: var(--sidebar-collapsed-width);
        }
        body.sidebar-collapsed .sidebar-head-copy,
        body.sidebar-collapsed .sidebar-menu-text,
        body.sidebar-collapsed .sidebar-footer-copy,
        body.sidebar-collapsed .sidebar-counter {
            display: none !important;
        }
        body.sidebar-collapsed .sidebar-head,
        body.sidebar-collapsed .sidebar-footer {
            justify-content: center;
        }
        body.sidebar-collapsed .sidebar-link {
            justify-content: center;
            padding-left: 12px;
            padding-right: 12px;
        }
        body.sidebar-collapsed .sidebar-link.active::before {
            left: auto;
            right: 8px;
            width: 4px;
            height: 18px;
        }

        #sidebar-overlay {
            transition: opacity .25s ease;
        }
        @media (max-width: 1024px) {
            .brand-title { font-size: 13px; max-width: 380px; }
        }
        @media (max-width: 768px) {
            .app-topbar { margin: 10px 10px 0; border-radius: 20px; }
            .app-topbar-inner { padding: 12px; }
            .desktop-toggle { display: none; }
            .brand-title { font-size: 12px; max-width: 250px; line-height: 1.15; }
            .brand-subtitle, .topbar-user-copy { display: none; }
            .app-shell { padding: 0 10px 10px; }
            #sidebar {
                position: fixed;
                top: 84px;
                left: 10px;
                z-index: 45;
                width: 270px;
                min-height: calc(100vh - 94px);
                transform: translateX(-112%);
            }
            #sidebar.open { transform: translateX(0); }
            #sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, .45);
                backdrop-filter: blur(2px);
                opacity: 0;
                pointer-events: none;
                z-index: 35;
            }
            #sidebar-overlay.open {
                opacity: 1;
                pointer-events: auto;
            }
            .main-content { padding-top: 8px; }
            .content-area { padding: 8px 0 16px; }
        }
    </style>
</head>
<body>
    <?php
    $siteLogo = getSiteLogo();
    $headerLogoUrl = BASE_URL . '/logo-visas-original.png';
    $initials = strtoupper(substr(trim($_SESSION['user_name'] ?? 'U'), 0, 1));
    ?>

    <nav class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-block">
                <button id="mobile-menu-button"
                        class="menu-toggle md:hidden"
                        aria-label="Abrir menú de navegación"
                        aria-expanded="false"
                        aria-controls="sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <button id="desktop-sidebar-toggle"
                        class="desktop-toggle hidden md:inline-grid"
                        type="button"
                        aria-label="Ocultar o mostrar menú lateral">
                    <i class="fas fa-bars-staggered"></i>
                </button>

                <img src="<?= htmlspecialchars($headerLogoUrl) ?>" alt="Logo del sistema" class="brand-logo-inline">

                <div class="min-w-0">
                    <div class="brand-title">Tramite Visa Americana En Queretaro By Vanesa Torres</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $notifications = getUpcomingNotifications();
                $unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
                ?>
                <div class="relative" id="notification-wrapper">
                    <button id="notification-btn"
                            class="icon-chip relative"
                            aria-label="Notificaciones"
                            aria-haspopup="true"
                            aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span id="notif-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-5 min-w-[1.25rem] px-1 inline-flex items-center justify-center">
                            <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                        </span>
                        <?php else: ?>
                        <span id="notif-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-5 min-w-[1.25rem] px-1 items-center justify-center">0</span>
                        <?php endif; ?>
                    </button>

                    <div id="notification-dropdown"
                         class="hidden absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-xl z-50 border border-gray-200 overflow-hidden"
                         role="menu"
                         aria-label="Lista de notificaciones">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-slate-50">
                            <span class="font-semibold text-gray-800 text-sm">Citas próximas</span>
                            <span class="text-xs text-gray-500">Próximos 2 días</span>
                        </div>
                        <ul class="max-h-80 overflow-y-auto divide-y divide-gray-100" id="notif-list">
                            <?php if (empty($notifications)): ?>
                            <li class="px-4 py-6 text-center text-gray-500 text-sm">
                                <i class="fas fa-check-circle text-green-400 text-2xl mb-2 block"></i>
                                No hay citas próximas
                            </li>
                            <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                            <?php
                                $appId   = (int) $notif['application_id'];
                                $nType   = htmlspecialchars($notif['notification_type']);
                                $isRead  = (bool) $notif['is_read'];
                                $folio   = htmlspecialchars($notif['folio'] ?? '');
                                $client  = htmlspecialchars($notif['client_name'] ?? 'Cliente');
                                $apptDt  = $notif['appointment_date'];
                                $apptFmt = $apptDt ? date('d/m/Y H:i', strtotime($apptDt)) : '';
                                $location = $notif['location'] ? htmlspecialchars($notif['location']) : '';
                                $label   = $nType === 'biometric' ? 'Cita biométrica' : 'Cita consular';
                                $checkId = "notif-{$appId}-{$nType}";
                            ?>
                            <li class="px-4 py-3 <?= $isRead ? 'opacity-60' : 'bg-yellow-50' ?>" data-app-id="<?= $appId ?>" data-notif-type="<?= $nType ?>">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <input type="checkbox"
                                               id="<?= $checkId ?>"
                                               class="notif-checkbox h-4 w-4 rounded border-gray-300 cursor-pointer accent-blue-600"
                                               data-app-id="<?= $appId ?>"
                                               data-notif-type="<?= $nType ?>"
                                               <?= $isRead ? 'checked' : '' ?>
                                               title="Marcar como visto">
                                    </div>
                                    <label for="<?= $checkId ?>" class="flex-1 cursor-pointer">
                                        <p class="text-sm font-medium text-gray-800 leading-tight"><?= $client ?></p>
                                        <p class="text-xs text-gray-500"><?= $folio ?></p>
                                        <p class="text-xs text-blue-600 mt-0.5"><i class="fas fa-calendar-alt mr-1"></i><?= $label ?>: <?= $apptFmt ?></p>
                                        <?php if ($location): ?>
                                        <p class="text-xs text-gray-400 mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i><?= $location ?></p>
                                        <?php endif; ?>
                                    </label>
                                    <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $appId ?>" class="flex-shrink-0 text-blue-500 hover:text-blue-700" title="Ver solicitud">
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <div class="topbar-user hidden sm:flex">
                    <div class="topbar-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="topbar-user-copy">
                        <p class="font-semibold text-sm leading-tight text-slate-800"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></p>
                        <p class="text-xs text-slate-500 leading-tight"><?= htmlspecialchars($_SESSION['user_role'] ?? '') ?></p>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/logout" class="logout-link">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span class="hidden md:inline">Salir</span>
                </a>
            </div>
        </div>
    </nav>

    <div id="sidebar-overlay" class="md:hidden"></div>

    <div class="app-shell">
        <aside id="sidebar">
            <div class="sidebar-inner">
                <div class="sidebar-head">
                    <div class="sidebar-badge"><i class="fas fa-passport"></i></div>
                    <div class="sidebar-head-copy">
                        <div class="sidebar-brand-title">Visas Querétaro</div>
                        
                    </div>
                </div>

                <div class="sidebar-scroll">
                    <nav>
                        <a href="<?= BASE_URL ?>/dashboard" class="sidebar-link">
                            <i class="fas fa-house"></i>
                            <span class="sidebar-menu-text">Dashboard</span>
                        </a>

                        <a href="<?= BASE_URL ?>/solicitudes" class="sidebar-link">
                            <i class="fas fa-file-lines"></i>
                            <span class="sidebar-menu-text">Solicitudes</span>
                        </a>

                        <a href="<?= BASE_URL ?>/traslados" class="sidebar-link">
                            <i class="fas fa-bus-simple"></i>
                            <span class="sidebar-menu-text">Traslados</span>
                        </a>

                        <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN, ROLE_ASESOR])): ?>
                        <?php $waPending = getWhatsAppPendingCount(); ?>
                        <a href="<?= BASE_URL ?>/whatsapp" class="sidebar-link">
                            <i class="fab fa-whatsapp"></i>
                            <span class="sidebar-menu-text flex-1">Conversaciones WhatsApp</span>
                            <?php if ($waPending > 0): ?>
                            <span class="sidebar-counter"><?= $waPending > 99 ? '99+' : $waPending ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>

                        <?php if (($_SESSION['user_role'] ?? '') === ROLE_ASESOR): ?>
                        <?php $advisorRejectedSales = getAdvisorRejectedSalesCount(); ?>
                        <a href="<?= BASE_URL ?>/ingresos" class="sidebar-link">
                            <i class="fas fa-sack-dollar"></i>
                            <span class="sidebar-menu-text flex-1">Mis ventas</span>
                            <?php if ($advisorRejectedSales > 0): ?>
                            <span class="sidebar-counter" title="Ventas rechazadas por corregir"><?= $advisorRejectedSales > 99 ? '99+' : $advisorRejectedSales ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= BASE_URL ?>/ventas-quincenales" class="sidebar-link">
                            <i class="fas fa-chart-column"></i>
                            <span class="sidebar-menu-text">Mis ventas por quincena</span>
                        </a>
                        <?php endif; ?>

                        <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN])): ?>
                        <a href="<?= BASE_URL ?>/formularios" class="sidebar-link">
                            <i class="fas fa-pen-ruler"></i>
                            <span class="sidebar-menu-text">Constructor de Formularios</span>
                        </a>
                        <?php endif; ?>

                        <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN, ROLE_GERENTE])): ?>
                        <a href="<?= BASE_URL ?>/ventas-quincenales" class="sidebar-link">
                            <i class="fas fa-chart-line"></i>
                            <span class="sidebar-menu-text">Ventas de asesoras</span>
                        </a>
                        <a href="<?= BASE_URL ?>/control-ingresos" class="sidebar-link">
                            <i class="fas fa-money-check-dollar"></i>
                            <span class="sidebar-menu-text">Control de Ingresos</span>
                        </a>
                        <a href="<?= BASE_URL ?>/balance-semanal" class="sidebar-link">
                            <i class="fas fa-calendar-week"></i>
                            <span class="sidebar-menu-text">Balance Semanal</span>
                        </a>
                        <a href="<?= BASE_URL ?>/egresos" class="sidebar-link">
                            <i class="fas fa-arrow-trend-down"></i>
                            <span class="sidebar-menu-text">Egresos</span>
                        </a>
                        <a href="<?= BASE_URL ?>/resumen-mensual" class="sidebar-link">
                            <i class="fas fa-calendar-days"></i>
                            <span class="sidebar-menu-text">Resumen mensual</span>
                        </a>
                        <?php if (($_SESSION['user_role'] ?? '') === ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/sobres" class="sidebar-link">
                            <i class="fas fa-envelope-open-text"></i>
                            <span class="sidebar-menu-text">Sobres</span>
                        </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/reportes" class="sidebar-link">
                            <i class="fas fa-chart-pie"></i>
                            <span class="sidebar-menu-text">Reportes</span>
                        </a>
                        <?php endif; ?>

                        <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN])): ?>
                        <?php $adminControlUnread = getAdminControlUnreadCount(); ?>
                        <a href="<?= BASE_URL ?>/centro-control" class="sidebar-link">
                            <i class="fas fa-tower-broadcast"></i>
                            <span class="sidebar-menu-text flex-1">Centro de Control</span>
                            <?php if ($adminControlUnread > 0): ?>
                            <span class="sidebar-counter"><?= $adminControlUnread > 99 ? '99+' : $adminControlUnread ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= BASE_URL ?>/usuarios" class="sidebar-link">
                            <i class="fas fa-users"></i>
                            <span class="sidebar-menu-text">Usuarios</span>
                        </a>
                        <a href="<?= BASE_URL ?>/configuracion" class="sidebar-link">
                            <i class="fas fa-sliders"></i>
                            <span class="sidebar-menu-text">Configuración</span>
                        </a>
                        <a href="<?= BASE_URL ?>/test-email" class="sidebar-link">
                            <i class="fas fa-paper-plane"></i>
                            <span class="sidebar-menu-text">Test de Correo</span>
                        </a>
                        <a href="<?= BASE_URL ?>/auditoria" class="sidebar-link">
                            <i class="fas fa-clipboard-list"></i>
                            <span class="sidebar-menu-text">Auditoría</span>
                        </a>
                        <a href="<?= BASE_URL ?>/logs" class="sidebar-link">
                            <i class="fas fa-triangle-exclamation"></i>
                            <span class="sidebar-menu-text">Logs de Errores</span>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="content-area">
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-box alert-success" role="alert">
                    <p><?= htmlspecialchars($_SESSION['success']) ?></p>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-box alert-error" role="alert">
                    <p><?= htmlspecialchars($_SESSION['error']) ?></p>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <script>
        window.crmParseMoney = function(value) {
            const cleaned = String(value ?? '').replace(/[$,\s]/g, '');
            const number = parseFloat(cleaned);
            return Number.isFinite(number) ? number : 0;
        };

        window.crmFormatMoney = function(value) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(value) || 0);
        };

        window.crmInitMoneyInput = function(input) {
            if (!input || input.dataset.moneyReady === '1') return;
            input.dataset.moneyReady = '1';
            input.setAttribute('inputmode', 'decimal');

            const showFormatted = () => {
                if (document.activeElement === input) return;
                if (String(input.value ?? '').trim() === '') return;
                input.value = window.crmFormatMoney(window.crmParseMoney(input.value));
            };
            const showEditable = () => {
                const value = window.crmParseMoney(input.value);
                input.value = value === 0 ? '' : String(value);
                requestAnimationFrame(() => input.select());
            };

            input.addEventListener('focus', showEditable);
            input.addEventListener('blur', showFormatted);
            showFormatted();
        };

        window.crmInitMoneyInputs = function(root) {
            (root || document).querySelectorAll('[data-money-input]').forEach(window.crmInitMoneyInput);
        };

        document.addEventListener('DOMContentLoaded', function() {
            window.crmInitMoneyInputs(document);

            document.addEventListener('submit', function(e) {
                e.target.querySelectorAll('[data-money-input]').forEach(function(input) {
                    input.value = String(window.crmParseMoney(input.value));
                });
            }, true);

            const currentPath = window.location.pathname.replace(/\/$/, '');
            const links = document.querySelectorAll('.sidebar-link');
            links.forEach(link => {
                try {
                    const href = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '');
                    if (href && (currentPath === href || (href !== '' && currentPath.startsWith(href)))) {
                        link.classList.add('active');
                    }
                } catch (e) {}
            });

            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const desktopToggle = document.getElementById('desktop-sidebar-toggle');
            const collapseKey = 'crm-sidebar-collapsed';

            if (window.innerWidth >= 768 && localStorage.getItem(collapseKey) === '1') {
                document.body.classList.add('sidebar-collapsed');
            }

            if (desktopToggle) {
                desktopToggle.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem(collapseKey, document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
                });
            }

            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', function() {
                    const isOpen = sidebar.classList.toggle('open');
                    overlay.classList.toggle('open');
                    mobileMenuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    if (isOpen && links.length > 0) links[0].focus();
                });
            }
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('open');
                    if (mobileMenuButton) mobileMenuButton.setAttribute('aria-expanded', 'false');
                });
            }
            links.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 768) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('open');
                        if (mobileMenuButton) mobileMenuButton.setAttribute('aria-expanded', 'false');
                    }
                });
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('open');
                    if (mobileMenuButton) {
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                        mobileMenuButton.focus();
                    }
                }
            });

            const notifBtn = document.getElementById('notification-btn');
            const notifDropdown = document.getElementById('notification-dropdown');
            const notifBadge = document.getElementById('notif-badge');

            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isHidden = notifDropdown.classList.toggle('hidden');
                    notifBtn.setAttribute('aria-expanded', !isHidden);
                });

                document.addEventListener('click', function(e) {
                    if (!notifDropdown.classList.contains('hidden') && !notifDropdown.contains(e.target) && e.target !== notifBtn) {
                        notifDropdown.classList.add('hidden');
                        notifBtn.setAttribute('aria-expanded', 'false');
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !notifDropdown.classList.contains('hidden')) {
                        notifDropdown.classList.add('hidden');
                        notifBtn.setAttribute('aria-expanded', 'false');
                        notifBtn.focus();
                    }
                });

                document.querySelectorAll('.notif-checkbox').forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        const appId = this.dataset.appId;
                        const notifType = this.dataset.notifType;
                        const action = this.checked ? 'mark-read' : 'mark-unread';
                        const li = this.closest('li');

                        const formData = new FormData();
                        formData.append('application_id', appId);
                        formData.append('notification_type', notifType);

                        fetch('<?= BASE_URL ?>/notifications/' + action, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                const count = data.unread_count;
                                if (notifBadge) {
                                    if (count > 0) {
                                        notifBadge.textContent = count > 99 ? '99+' : count;
                                        notifBadge.classList.remove('hidden');
                                        notifBadge.classList.add('inline-flex');
                                    } else {
                                        notifBadge.classList.add('hidden');
                                        notifBadge.classList.remove('inline-flex');
                                    }
                                }
                                if (li) {
                                    if (action === 'mark-read') {
                                        li.classList.add('opacity-60');
                                        li.classList.remove('bg-yellow-50');
                                    } else {
                                        li.classList.remove('opacity-60');
                                        li.classList.add('bg-yellow-50');
                                    }
                                }
                            }
                        })
                        .catch(function() {
                            checkbox.checked = !checkbox.checked;
                        });
                    });
                });
            }
        });
    </script>
</body>
</html>
