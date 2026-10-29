<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Tramite Visa Americana En Queretaro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
    $geoLoginEnabled = getConfig('geo_login_enabled', '0') === '1';
    $loginLogoUrl = BASE_URL . '/logo-visas-original.png';
    $landingUrl = 'https://tramitevisaamericanaqueretaro.com/';
    ?>
    <style>
        :root {
            --navy: #10326a;
            --navy-deep: #0a234b;
            --blue: #2695d5;
            --blue-soft: #d8ebf7;
            --gold: #c7a45a;
            --gold-soft: #f5ebd6;
            --cream: #f8f3ea;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #d8e0ea;
            --danger: #b42318;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 20%, rgba(38,149,213,.10), transparent 25%),
                radial-gradient(circle at 90% 80%, rgba(199,164,90,.12), transparent 22%),
                linear-gradient(135deg, #efe6d5 0%, #f9f6ef 55%, #f2ebdf 100%);
            padding: 28px;
        }
        .login-wrapper {
            width: min(1180px, 100%);
            min-height: calc(100vh - 56px);
            margin: 0 auto;
            background: rgba(255,255,255,.75);
            border: 1px solid rgba(255,255,255,.9);
            border-radius: 34px;
            overflow: hidden;
            box-shadow: 0 26px 80px rgba(24, 45, 84, .18);
            display: grid;
            grid-template-columns: minmax(420px, 1fr) minmax(380px, 520px);
            backdrop-filter: blur(18px);
        }
        .form-side {
            padding: 42px 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.82);
        }
        .form-content {
            width: min(100%, 430px);
        }
        .top-actions {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }
        .top-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(16,50,106,.06);
            color: var(--navy);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: transform .2s ease, background .2s ease;
        }
        .top-link:hover { background: rgba(16,50,106,.1); transform: translateY(-1px); }
        .mini-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: var(--gold-soft);
            color: #7a5c1f;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 18px;
        }
        .brand-row {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .brand-row img {
            width: 190px;
            height: 190px;
            object-fit: contain;
            border-radius: 20px;
            background: #fff;
            border: 1px solid #edf1f6;
            padding: 6px;
            box-shadow: 0 10px 25px rgba(16,50,106,.08);
        }
        .brand-copy h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.03;
            font-weight: 800;
            color: #111827;
            letter-spacing: -.04em;
        }
        .brand-copy p {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
        }
        .form-group { margin-bottom: 17px; }
        .field-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }
        .field-wrap { position: relative; }
        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        .login-input {
            width: 100%;
            height: 54px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 0 52px 0 45px;
            font-size: 14px;
            color: #111827;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .login-input:focus {
            outline: none;
            border-color: rgba(38,149,213,.7);
            box-shadow: 0 0 0 4px rgba(38,149,213,.12);
            transform: translateY(-1px);
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            color: #7c8799;
            cursor: pointer;
            border-radius: 12px;
        }
        .password-toggle:hover { background: rgba(16,50,106,.06); }
        .captcha-shell {
            display: grid;
            gap: 10px;
            padding: 14px;
            border-radius: 20px;
            border: 1px solid #e7ecf3;
            background: linear-gradient(180deg, #ffffff 0%, #fafcfe 100%);
        }
        .captcha-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            color: #516074;
        }
        .captcha-top strong {
            font-size: 18px;
            color: var(--navy);
            white-space: nowrap;
        }
        .login-button {
            width: 100%;
            height: 56px;
            margin-top: 8px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(90deg, var(--navy-deep) 0%, var(--navy) 40%, var(--blue) 100%);
            color: white;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .02em;
            cursor: pointer;
            box-shadow: 0 16px 34px rgba(16,50,106,.22);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .login-button:hover { transform: translateY(-1px); box-shadow: 0 20px 38px rgba(16,50,106,.26); }
        .login-button:disabled { opacity: .8; cursor: wait; }
        .message-box {
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 14px;
            font-size: 13px;
            line-height: 1.5;
        }
        .error-box { background: #fef3f2; border: 1px solid #fecdca; color: var(--danger); }
        .geo-box { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
        .foot-note {
            margin-top: 16px;
            color: #6b7280;
            font-size: 12px;
            text-align: center;
        }
        .visual-side {
            position: relative;
            overflow: hidden;
            padding: 36px;
            background:
                radial-gradient(circle at 82% 18%, rgba(255,255,255,.24), transparent 18%),
                radial-gradient(circle at 24% 68%, rgba(199,164,90,.16), transparent 18%),
                linear-gradient(160deg, #123577 0%, #1f5ea9 56%, #0d2f68 100%);
            color: white;
            display: flex;
            align-items: stretch;
        }
        .visual-side::before,
        .visual-side::after {
            content: "";
            position: absolute;
            inset: auto;
            border-radius: 55% 45% 58% 42% / 45% 57% 43% 55%;
            filter: blur(6px);
            opacity: .7;
        }
        .visual-side::before {
            width: 290px;
            height: 290px;
            background: linear-gradient(140deg, rgba(255,255,255,.34), rgba(255,255,255,.02));
            top: -70px;
            right: -50px;
        }
        .visual-side::after {
            width: 300px;
            height: 300px;
            background: linear-gradient(160deg, rgba(199,164,90,.38), rgba(255,255,255,.04));
            bottom: -90px;
            left: -40px;
        }
        .visual-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
            border-radius: 28px;
            border: 1px solid rgba(255,255,255,.18);
            background: linear-gradient(180deg, rgba(255,255,255,.14), rgba(255,255,255,.08));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.15);
            padding: 28px;
            backdrop-filter: blur(16px);
        }
        .visual-content h2 {
            margin: 0 0 16px;
            font-size: clamp(28px, 3.2vw, 42px);
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: -.04em;
        }
        .visual-content p {
            margin: 0;
            color: rgba(255,255,255,.82);
            font-size: 15px;
            line-height: 1.8;
            max-width: 420px;
        }
        .feature-list {
            display: grid;
            gap: 14px;
            margin-top: 54px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.12);
        }
        .feature-item i {
            width: 38px;
            height: 38px;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(255,255,255,.18);
            color: #f8e3ad;
        }
        .feature-item span {
            font-size: 14px;
            font-weight: 600;
            color: #f8fbff;
        }
        .visual-footer > div {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .visual-footer {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-top: 30px;
            margin-top: 58px;
            border-top: 1px solid rgba(255,255,255,.14);
        }
        .visual-footer img {
            width: 82px;
            height: 82px;
            border-radius: 18px;
            object-fit: contain;
            background: rgba(255,255,255,.92);
            padding: 6px;
        }
        .visual-footer strong {
            display: block;
            font-size: 15px;
            margin-bottom: 4px;
        }
        .visual-footer small { color: rgba(255,255,255,.72); font-size: 12px; }
        @media (max-width: 980px) {
            body { padding: 16px; }
            .login-wrapper {
                min-height: auto;
                grid-template-columns: 1fr;
            }
            .visual-side {
                min-height: 360px;
                order: -1;
                padding: 22px;
            }
            .form-side { padding: 28px 22px 30px; }
            .top-actions { flex-wrap: wrap; }
        }
        @media (max-width: 540px) {
            body { padding: 0; }
            .login-wrapper { min-height: 100vh; border-radius: 0; }
            .visual-side { min-height: auto; }
            .brand-row { align-items: flex-start; }
            .brand-copy h1 { font-size: 28px; }
            .visual-content { padding: 22px; }
            .visual-content h2 { font-size: 30px; }
            .brand-row img { width: 160px; height: 160px; }
            .feature-list { margin-top: 38px; }
            .visual-footer { margin-top: 42px; padding-top: 24px; }
            .visual-footer img { width: 72px; height: 72px; }
        }
    </style>
</head>
<body>
    <main class="login-wrapper">
        <section class="form-side">
            <div class="form-content">
                <div class="top-actions">
                    <a href="<?= htmlspecialchars($landingUrl) ?>" class="top-link">
                        <i class="fas fa-arrow-left"></i>
                        Ir a la landing
                    </a>
                </div>

                <div class="brand-row">
                    <img src="<?= htmlspecialchars($loginLogoUrl) ?>" alt="Trámite Visa Americana En Querétaro">
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="message-box error-box" role="alert">
                        <i class="fas fa-circle-exclamation mr-1"></i>
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                <?php unset($_SESSION['error']); endif; ?>

                <div id="geo-status" class="message-box geo-box hidden" role="status"></div>

                <form id="login-form" action="<?= BASE_URL ?>/login" method="POST" data-geo-enabled="<?= $geoLoginEnabled ? '1' : '0' ?>">
                    <input type="hidden" id="latitude" name="latitude" value="">
                    <input type="hidden" id="longitude" name="longitude" value="">

                    <div class="form-group">
                        <label class="field-label" for="username">Correo electrónico</label>
                        <div class="field-wrap">
                            <i class="fas fa-envelope field-icon"></i>
                            <input class="login-input" type="text" id="username" name="username" required autocomplete="username" placeholder="correo@dominio.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="field-label" for="password">Contraseña</label>
                        <div class="field-wrap">
                            <i class="fas fa-lock field-icon"></i>
                            <input class="login-input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="Ingrese su contraseña">
                            <button class="password-toggle" type="button" id="password-toggle" aria-label="Mostrar contraseña" title="Mostrar contraseña">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="field-label" for="captcha">Verificación humana</label>
                        <?php
                        if (!isset($_SESSION['captcha_answer']) || !isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
                            $_SESSION['captcha_num1'] = rand(1, 10);
                            $_SESSION['captcha_num2'] = rand(1, 10);
                            $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
                        }
                        ?>
                        <div class="captcha-shell">
                            <div class="captcha-top">
                                <span><i class="fas fa-shield-halved mr-2"></i>Complete esta validación antes de ingresar</span>
                                <strong><?= (int)$_SESSION['captcha_num1'] ?> + <?= (int)$_SESSION['captcha_num2'] ?> = ?</strong>
                            </div>
                            <div class="field-wrap">
                                <i class="fas fa-check field-icon"></i>
                                <input class="login-input" type="number" id="captcha" name="captcha" required inputmode="numeric" placeholder="Escriba el resultado">
                            </div>
                        </div>
                    </div>

                    <button id="login-button" type="submit" class="login-button">
                        Iniciar sesión <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>

                <p class="foot-note">Trámite De Visa Americana En Querétaro By Vanesa Torres</p>
            </div>
        </section>

        <section class="visual-side" aria-hidden="true">
            <div class="visual-content">
                <div>
                    <h2>Gestiona solicitudes, clientes y seguimiento desde un solo lugar.</h2>

                    <div class="feature-list">
                        <div class="feature-item">
                            <i class="fas fa-passport"></i>
                            <span>Solicitudes y expedientes</span>
                        </div>
                        <div class="feature-item">
                            <i class="fab fa-whatsapp"></i>
                            <span>Seguimiento desde WhatsApp</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Indicadores y reportes</span>
                        </div>
                    </div>
                </div>

                <div class="visual-footer">
                    <img src="<?= htmlspecialchars($loginLogoUrl) ?>" alt="Logo Visas">
                    <div>
                        <strong>Tramite Visa Americana En Queretaro By Vanesa Torres</strong>
                        <small>Acceso privado al sistema</small>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
    (function() {
        const form = document.getElementById('login-form');
        const button = document.getElementById('login-button');
        const statusBox = document.getElementById('geo-status');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const geoEnabled = form.dataset.geoEnabled === '1';
        const password = document.getElementById('password');
        const toggle = document.getElementById('password-toggle');

        toggle.addEventListener('click', function() {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.innerHTML = show ? '<i class="far fa-eye-slash"></i>' : '<i class="far fa-eye"></i>';
            toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });

        function showStatus(message) {
            statusBox.textContent = message;
            statusBox.classList.remove('hidden');
        }

        function submitForm() {
            form.dataset.submitting = '1';
            form.submit();
        }

        form.addEventListener('submit', function(event) {
            if (!geoEnabled || form.dataset.submitting === '1') return;

            event.preventDefault();
            button.disabled = true;
            showStatus('Verificando ubicación...');

            if (!navigator.geolocation) {
                submitForm();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    latitudeInput.value = position.coords.latitude;
                    longitudeInput.value = position.coords.longitude;
                    submitForm();
                },
                function() { submitForm(); },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
            );
        });
    })();
    </script>
</body>
</html>
