<?php
$title = 'Configuracion Global';
$geoEnabled = trim((string)($configs['geo_login_enabled']['config_value'] ?? '0')) === '1';
$sucursales = $sucursales ?? [];
ob_start();
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-cog text-gray-600 mr-2"></i>Configuracion del Sistema
    </h2>
    <p class="text-gray-500">Personaliza tu sistema CRM</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    <a href="#section-general" onclick="showSection('general')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-blue-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="fas fa-sliders-h text-blue-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">General</p>
            <p class="text-xs text-gray-500">Nombre, logo, contacto</p>
        </div>
    </a>

    <a href="#section-tema" onclick="showSection('tema')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-purple-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
            <i class="fas fa-palette text-purple-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Tema</p>
            <p class="text-xs text-gray-500">Colores y estilos</p>
        </div>
    </a>

    <a href="#section-correo" onclick="showSection('correo')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-green-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
            <i class="fas fa-envelope text-green-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Correo</p>
            <p class="text-xs text-gray-500">SMTP y notificaciones</p>
        </div>
    </a>

    <a href="#section-pagos" onclick="showSection('pagos')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-yellow-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center">
            <i class="fas fa-credit-card text-yellow-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Pagos</p>
            <p class="text-xs text-gray-500">PayPal, cuentas</p>
        </div>
    </a>

    <a href="#section-horarios" onclick="showSection('horarios')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-orange-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center">
            <i class="fas fa-clock text-orange-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Horarios</p>
            <p class="text-xs text-gray-500">Atencion y servicio</p>
        </div>
    </a>

    <a href="#section-qr" onclick="showSection('qr')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-indigo-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
            <i class="fas fa-qrcode text-indigo-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Codigos QR</p>
            <p class="text-xs text-gray-500">API y configuracion</p>
        </div>
    </a>

    <a href="#section-seguridad" onclick="showSection('seguridad')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-red-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
            <i class="fas fa-shield-alt text-red-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Seguridad</p>
            <p class="text-xs text-gray-500">Login y ubicacion</p>
        </div>
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/configuracion/guardar" enctype="multipart/form-data">

    <div id="section-general" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                <i class="fas fa-sliders-h text-blue-600 text-sm"></i>
            </span>
            General
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Sitio</label>
                <input type="text" name="config_site_name"
                       value="<?= htmlspecialchars($configs['site_name']['config_value'] ?? SITE_NAME) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo del Sitio</label>
                <input type="file" name="site_logo" accept="image/*"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                <?php if (getSiteLogo()): ?>
                <div class="mt-2">
                    <img src="<?= BASE_URL . htmlspecialchars($configs['site_logo']['config_value']) ?>"
                         alt="Logo actual" class="h-16 object-contain">
                </div>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Principal</label>
                <input type="email" name="config_email_from"
                       value="<?= htmlspecialchars($configs['email_from']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar General
            </button>
        </div>
    </div>

    <div id="section-tema" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                <i class="fas fa-palette text-purple-600 text-sm"></i>
            </span>
            Tema
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Color Primario</label>
                <div class="flex items-center space-x-2">
                    <input type="color" name="config_primary_color" id="primary_color"
                           value="<?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"
                           class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                           onchange="document.getElementById('primary_color_text').value = this.value">
                    <input type="text" id="primary_color_text"
                           value="<?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"
                           class="flex-1 border border-gray-300 rounded-lg px-4 py-2" readonly>
                </div>
                <p class="text-xs text-gray-500 mt-1">Navbar, botones y enlaces principales</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Color Secundario</label>
                <div class="flex items-center space-x-2">
                    <input type="color" name="config_secondary_color" id="secondary_color"
                           value="<?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"
                           class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                           onchange="document.getElementById('secondary_color_text').value = this.value">
                    <input type="text" id="secondary_color_text"
                           value="<?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"
                           class="flex-1 border border-gray-300 rounded-lg px-4 py-2" readonly>
                </div>
                <p class="text-xs text-gray-500 mt-1">Hover de botones y elementos secundarios</p>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Tema
            </button>
        </div>
    </div>

    <div id="section-correo" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                <i class="fas fa-envelope text-green-600 text-sm"></i>
            </span>
            Correo &amp; SMTP
        </h3>
        <p class="text-xs text-gray-500 mb-4">Configuracion del servidor de correo para notificaciones del sistema</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Usuario SMTP</label>
                <input type="text" name="config_smtp_user"
                       value="<?= htmlspecialchars($configs['smtp_user']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       autocomplete="username">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contrasena SMTP</label>
                <div class="relative">
                    <input type="password" name="config_smtp_password" id="smtp_password"
                           value="<?= htmlspecialchars($configs['smtp_password']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:ring-2 focus:ring-blue-500"
                           autocomplete="current-password">
                    <button type="button" onclick="toggleSmtpPassword()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                        <i id="smtp_password_icon" class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Servidor de Salida</label>
                <input type="text" name="config_smtp_host"
                       value="<?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto SMTP</label>
                <input type="number" name="config_smtp_port"
                       value="<?= htmlspecialchars($configs['smtp_port']['config_value'] ?? '587') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto IMAP</label>
                <input type="number" name="config_smtp_imap_port"
                       value="<?= htmlspecialchars($configs['smtp_imap_port']['config_value'] ?? '993') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto POP3</label>
                <input type="number" name="config_smtp_pop3_port"
                       value="<?= htmlspecialchars($configs['smtp_pop3_port']['config_value'] ?? '995') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Correo
            </button>
        </div>
    </div>

    <div id="section-pagos" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center mr-3">
                <i class="fas fa-credit-card text-yellow-600 text-sm"></i>
            </span>
            Pagos
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Client ID</label>
                <input type="text" name="config_paypal_client_id"
                       value="<?= htmlspecialchars($configs['paypal_client_id']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Secret</label>
                <input type="password" name="config_paypal_secret"
                       value="<?= htmlspecialchars($configs['paypal_secret']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Pagos
            </button>
        </div>
    </div>

    <div id="section-horarios" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center mr-3">
                <i class="fas fa-clock text-orange-600 text-sm"></i>
            </span>
            Horarios &amp; Contacto
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Telefono 1</label>
                <input type="tel" name="config_contact_phone"
                       value="<?= htmlspecialchars($configs['contact_phone']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Telefono 2</label>
                <input type="tel" name="config_contact_phone_2"
                       value="<?= htmlspecialchars($configs['contact_phone_2']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Horario de Atencion</label>
                <input type="text" name="config_business_hours"
                       value="<?= htmlspecialchars($configs['business_hours']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Horarios
            </button>
        </div>
    </div>

    <div id="section-qr" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                <i class="fas fa-qrcode text-indigo-600 text-sm"></i>
            </span>
            Codigos QR
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">QR API Key</label>
                <input type="text" name="config_qr_api_key"
                       value="<?= htmlspecialchars($configs['qr_api_key']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">QR API URL</label>
                <input type="text" name="config_qr_api_url"
                       value="<?= htmlspecialchars($configs['qr_api_url']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar QR
            </button>
        </div>
    </div>

    <div id="section-seguridad" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                <i class="fas fa-shield-alt text-red-600 text-sm"></i>
            </span>
            Seguridad / Ubicacion
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <input type="hidden" name="config_geo_login_enabled" value="0">
                <label class="inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="geo_login_enabled_toggle" value="1"
                           id="geo_login_toggle"
                           class="sr-only peer"
                           <?= $geoEnabled ? 'checked' : '' ?>>
                    <span id="geo_login_switch_track" class="relative inline-flex h-8 w-16 items-center rounded-full <?= $geoEnabled ? 'bg-green-600' : 'bg-gray-300' ?> transition peer-focus:ring-2 peer-focus:ring-blue-500 peer-focus:ring-offset-2">
                        <span id="geo_login_switch_thumb" class="absolute left-1 h-6 w-6 rounded-full bg-white shadow transition <?= $geoEnabled ? 'translate-x-8' : '' ?>"></span>
                    </span>
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        Login geolocalizado para asesores:
                        <span id="geo_login_toggle_label" class="<?= $geoEnabled ? 'text-green-700' : 'text-gray-500' ?>">
                            <?= $geoEnabled ? 'Encendido' : 'Apagado' ?>
                        </span>
                    </span>
                </label>
            </div>
            <div id="geo_login_fields" class="md:col-span-2 <?= $geoEnabled ? '' : 'hidden' ?>">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700">Sucursales</h4>
                    <button type="button" onclick="addSucursalTab()"
                            class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition text-sm">
                        <i class="fas fa-plus mr-1"></i>Agregar sucursal
                    </button>
                </div>
                <p class="text-xs text-gray-500 mb-3">El acceso se permite si el asesor esta dentro del radio de <strong>cualquier</strong> sucursal activa.</p>

                <div id="sucursal_tabs" class="flex flex-wrap gap-2 mb-4"></div>
                <div id="sucursal_panels"></div>
                <?php if (empty($sucursales)): ?>
                <p id="sucursal_empty_hint" class="text-sm text-gray-500">No hay sucursales configuradas. Agrega al menos una para que el login geolocalizado funcione.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Intentos maximos</label>
                <input type="number" name="config_login_max_attempts"
                       value="<?= htmlspecialchars($configs['login_max_attempts']['config_value'] ?? '5') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bloqueo por intentos (minutos)</label>
                <input type="number" name="config_login_lockout_minutes"
                       value="<?= htmlspecialchars($configs['login_lockout_minutes']['config_value'] ?? '15') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="1440">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cerrar sesion por inactividad (minutos)</label>
                <input type="number" name="config_session_idle_timeout_minutes"
                       value="<?= htmlspecialchars($configs['session_idle_timeout_minutes']['config_value'] ?? '30') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="5" max="1440">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Seguridad
            </button>
        </div>
    </div>

</form>

<div class="bg-white rounded-xl shadow p-6 mt-2">
    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
        <i class="fas fa-eye text-gray-500 mr-2"></i>Configuracion Actual
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">General</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Sitio:</span> <?= htmlspecialchars($configs['site_name']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Email:</span> <?= htmlspecialchars($configs['email_from']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Telefono 1:</span> <?= htmlspecialchars($configs['contact_phone']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Telefono 2:</span> <?= htmlspecialchars($configs['contact_phone_2']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Horario:</span> <?= htmlspecialchars($configs['business_hours']['config_value'] ?? '-') ?></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Correo SMTP</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Usuario:</span> <?= htmlspecialchars($configs['smtp_user']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Servidor:</span> <?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">SMTP:</span> <?= htmlspecialchars($configs['smtp_port']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">IMAP:</span> <?= htmlspecialchars($configs['smtp_imap_port']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">POP3:</span> <?= htmlspecialchars($configs['smtp_pop3_port']['config_value'] ?? '-') ?></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tema</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li class="flex items-center space-x-2">
                    <span class="font-medium text-gray-700">Primario:</span>
                    <span class="inline-block w-5 h-5 rounded border border-gray-300"
                          style="background-color: <?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"></span>
                    <span><?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?></span>
                </li>
                <li class="flex items-center space-x-2">
                    <span class="font-medium text-gray-700">Secundario:</span>
                    <span class="inline-block w-5 h-5 rounded border border-gray-300"
                          style="background-color: <?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"></span>
                    <span><?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?></span>
                </li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Seguridad</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Geo login:</span> <?= $geoEnabled ? 'Activo' : 'Inactivo' ?></li>
                <li><span class="font-medium text-gray-700">Sucursales:</span> <?= count($sucursales) ?> (<?= count(array_filter($sucursales, function ($s) { return (int) $s['activo'] === 1; })) ?> activas)</li>
                <li><span class="font-medium text-gray-700">Intentos:</span> <?= htmlspecialchars($configs['login_max_attempts']['config_value'] ?? '5') ?></li>
                <li><span class="font-medium text-gray-700">Inactividad:</span> <?= htmlspecialchars($configs['session_idle_timeout_minutes']['config_value'] ?? '30') ?> min</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const BASE_URL_JS = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const SUCURSALES_INICIALES = <?= json_encode($sucursales, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const branchMaps = {};
const branchTimers = {};
const sucursalKeys = [];
let sucursalCounter = 0;
let sucursalsInitialized = false;

function showSection(sectionId) {
    const el = document.getElementById('section-' + sectionId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.classList.add('ring-2', 'ring-blue-400');
        setTimeout(function() { el.classList.remove('ring-2', 'ring-blue-400'); }, 2000);
    }
}

function toggleSmtpPassword() {
    const input = document.getElementById('smtp_password');
    const icon = document.getElementById('smtp_password_icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function updateGeoLoginFields() {
    const toggle = document.getElementById('geo_login_toggle');
    const fields = document.getElementById('geo_login_fields');
    const label = document.getElementById('geo_login_toggle_label');
    const track = document.getElementById('geo_login_switch_track');
    const thumb = document.getElementById('geo_login_switch_thumb');

    if (!toggle || !fields || !label || !track || !thumb) {
        return;
    }

    if (toggle.checked) {
        fields.classList.remove('hidden');
        label.textContent = 'Encendido';
        label.classList.remove('text-gray-500');
        label.classList.add('text-green-700');
        track.classList.remove('bg-gray-300');
        track.classList.add('bg-green-600');
        thumb.classList.add('translate-x-8');
    } else {
        fields.classList.add('hidden');
        label.textContent = 'Apagado';
        label.classList.remove('text-green-700');
        label.classList.add('text-gray-500');
        track.classList.remove('bg-green-600');
        track.classList.add('bg-gray-300');
        thumb.classList.remove('translate-x-8');
    }

    if (toggle.checked) {
        setTimeout(initSucursales, 100);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('geo_login_toggle');
    if (toggle) {
        toggle.addEventListener('change', updateGeoLoginFields);
        updateGeoLoginFields();
    }
});

function initSucursales() {
    if (sucursalsInitialized) {
        if (sucursalKeys.length > 0) {
            showSucursalTab(sucursalKeys[0]);
        }
        return;
    }
    sucursalsInitialized = true;

    if (Array.isArray(SUCURSALES_INICIALES) && SUCURSALES_INICIALES.length > 0) {
        SUCURSALES_INICIALES.forEach(function(item) {
            addSucursalTab(item);
        });
        showSucursalTab(sucursalKeys[0]);
    }
}

function escapeHtmlAttr(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function addSucursalTab(existing) {
    sucursalCounter++;
    const data = existing || { id: null, nombre: '', direccion: '', latitud: '', longitud: '', radio_metros: 100, activo: 1 };
    const key = data.id ? ('s' + data.id) : ('new' + sucursalCounter);

    sucursalKeys.push(key);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = BASE_URL_JS + '/configuracion/sucursales/guardar';
    form.id = 'sucursal-form-' + key;
    form.style.display = 'none';
    document.body.appendChild(form);

    const tabsContainer = document.getElementById('sucursal_tabs');
    const pill = document.createElement('button');
    pill.type = 'button';
    pill.id = 'sucursal_tab_btn_' + key;
    pill.textContent = data.nombre || 'Nueva sucursal';
    pill.onclick = function() { showSucursalTab(key); };
    tabsContainer.appendChild(pill);

    const panelsContainer = document.getElementById('sucursal_panels');
    const panel = document.createElement('div');
    panel.id = 'sucursal_panel_' + key;
    panel.className = 'hidden border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50';
    panel.innerHTML = buildSucursalPanelHTML(key, data);
    panelsContainer.appendChild(panel);

    const emptyHint = document.getElementById('sucursal_empty_hint');
    if (emptyHint) {
        emptyHint.remove();
    }

    showSucursalTab(key);
    return key;
}

function buildSucursalPanelHTML(key, data) {
    const nombre = escapeHtmlAttr(data.nombre);
    const direccion = escapeHtmlAttr(data.direccion);
    const lat = (data.latitud === null || data.latitud === undefined) ? '' : escapeHtmlAttr(data.latitud);
    const lng = (data.longitud === null || data.longitud === undefined) ? '' : escapeHtmlAttr(data.longitud);
    const radio = data.radio_metros || 100;
    const activo = data.activo === undefined || data.activo === null || Number(data.activo) === 1;
    const id = data.id || '';
    const formId = 'sucursal-form-' + key;

    return `
        <input type="hidden" name="sucursal_id" value="${id}" form="${formId}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la sucursal</label>
                <input type="text" id="suc_${key}_nombre" name="nombre" value="${nombre}" required
                       form="${formId}" oninput="onSucursalNameInput('${key}', this.value)"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej. Sucursal Centro">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" id="suc_${key}_activo" name="activo" value="1" form="${formId}" ${activo ? 'checked' : ''}
                           class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <span class="ml-2 text-sm text-gray-700">Sucursal activa (aplica para el login geolocalizado)</span>
                </label>
            </div>
            <div class="md:col-span-2 relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">Direccion</label>
                <input type="text" id="suc_${key}_direccion" name="direccion" value="${direccion}" form="${formId}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Escribe una direccion para buscarla en el mapa" autocomplete="off"
                       oninput="handleAddressInputFor('${key}', event)"
                       onblur="setTimeout(function() { hideAddressSuggestionsFor('${key}'); }, 200)">
                <div id="suc_${key}_suggestions" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                <p class="text-xs text-gray-500 mt-1">Selecciona una sugerencia para completar coordenadas automaticamente.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Latitud</label>
                <input type="text" id="suc_${key}_lat" name="latitud" value="${lat}" form="${formId}"
                       onchange="syncMapFromCoordinateInputsFor('${key}')"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="20.5888">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Longitud</label>
                <input type="text" id="suc_${key}_lng" name="longitud" value="${lng}" form="${formId}"
                       onchange="syncMapFromCoordinateInputsFor('${key}')"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" placeholder="-100.3899">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Radio permitido (metros)</label>
                <input type="number" id="suc_${key}_radio" name="radio_metros" value="${radio}" form="${formId}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500" min="1" max="100000">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="useCurrentLocationFor('${key}')"
                        class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition text-sm">
                    <i class="fas fa-location-crosshairs mr-2"></i>Usar mi ubicacion actual
                </button>
            </div>
            <div class="md:col-span-2">
                <p id="suc_${key}_status" class="text-sm text-gray-500"></p>
            </div>
            <div class="md:col-span-2">
                <div id="suc_${key}_map" class="h-72 w-full rounded-lg border border-gray-300"></div>
                <p class="text-xs text-gray-500 mt-2">Puedes hacer clic en el mapa o arrastrar el marcador para ajustar la ubicacion de esta sucursal.</p>
            </div>
        </div>
        <div class="mt-4 flex justify-between items-center">
            <button type="button" onclick="removeSucursalTab('${key}', ${id ? id : 'null'})" class="text-red-600 hover:text-red-800 text-sm">
                <i class="fas fa-trash mr-1"></i>${id ? 'Eliminar sucursal' : 'Quitar'}
            </button>
            <button type="submit" form="${formId}" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar sucursal
            </button>
        </div>
    `;
}

function onSucursalNameInput(key, value) {
    const pill = document.getElementById('sucursal_tab_btn_' + key);
    if (pill) {
        pill.textContent = value || 'Nueva sucursal';
    }
}

function showSucursalTab(key) {
    sucursalKeys.forEach(function(k) {
        const panel = document.getElementById('sucursal_panel_' + k);
        const pill = document.getElementById('sucursal_tab_btn_' + k);
        const isActive = k === key;

        if (panel) {
            panel.classList.toggle('hidden', !isActive);
        }
        if (pill) {
            pill.className = 'px-3 py-1.5 rounded-full text-sm border transition ' +
                (isActive ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400');
        }
    });

    setTimeout(function() { ensureBranchMap(key); }, 100);
}

function removeSucursalTab(key, id) {
    if (id) {
        if (!confirm('¿Eliminar esta sucursal? Esta accion no se puede deshacer.')) {
            return;
        }
        const delForm = document.createElement('form');
        delForm.method = 'POST';
        delForm.action = BASE_URL_JS + '/configuracion/sucursales/eliminar/' + id;
        document.body.appendChild(delForm);
        delForm.submit();
        return;
    }

    if (!confirm('¿Quitar esta sucursal sin guardar?')) {
        return;
    }

    const panel = document.getElementById('sucursal_panel_' + key);
    const pill = document.getElementById('sucursal_tab_btn_' + key);
    const form = document.getElementById('sucursal-form-' + key);
    if (panel) panel.remove();
    if (pill) pill.remove();
    if (form) form.remove();
    delete branchMaps[key];
    delete branchTimers[key];

    const idx = sucursalKeys.indexOf(key);
    if (idx >= 0) {
        sucursalKeys.splice(idx, 1);
    }

    if (sucursalKeys.length > 0) {
        showSucursalTab(sucursalKeys[sucursalKeys.length - 1]);
    } else {
        const panelsContainer = document.getElementById('sucursal_panels');
        if (panelsContainer && !document.getElementById('sucursal_empty_hint')) {
            const hint = document.createElement('p');
            hint.id = 'sucursal_empty_hint';
            hint.className = 'text-sm text-gray-500';
            hint.textContent = 'No hay sucursales configuradas. Agrega al menos una para que el login geolocalizado funcione.';
            panelsContainer.insertAdjacentElement('afterend', hint);
        }
    }
}

function getBranchLatLng(key) {
    const latEl = document.getElementById('suc_' + key + '_lat');
    const lngEl = document.getElementById('suc_' + key + '_lng');
    const lat = parseFloat((latEl && latEl.value) || '');
    const lng = parseFloat((lngEl && lngEl.value) || '');

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        return [lat, lng];
    }

    return [20.5888, -100.3899];
}

function ensureBranchMap(key) {
    const mapEl = document.getElementById('suc_' + key + '_map');
    if (!mapEl || mapEl.offsetParent === null) {
        return;
    }

    if (!window.L) {
        const status = document.getElementById('suc_' + key + '_status');
        if (status) {
            status.textContent = 'No se pudo cargar el mapa. Revisa la conexion a OpenStreetMap.';
            status.className = 'text-sm text-red-600';
        }
        return;
    }

    const initialLatLng = getBranchLatLng(key);
    let entry = branchMaps[key];

    if (!entry) {
        const map = L.map(mapEl).setView(initialLatLng, 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker(initialLatLng, { draggable: true }).addTo(map);
        marker.on('dragend', function(event) {
            const position = event.target.getLatLng();
            setLatLngFor(key, position.lat, position.lng, false);
            reverseGeocodeFor(key, position.lat, position.lng);
        });

        map.on('click', function(event) {
            setLatLngFor(key, event.latlng.lat, event.latlng.lng, true);
            reverseGeocodeFor(key, event.latlng.lat, event.latlng.lng);
        });

        entry = { map: map, marker: marker };
        branchMaps[key] = entry;
    } else {
        entry.map.invalidateSize();
    }

    setTimeout(function() { entry.map.invalidateSize(); }, 150);
}

function setLatLngFor(key, lat, lng, updateMap) {
    const latEl = document.getElementById('suc_' + key + '_lat');
    const lngEl = document.getElementById('suc_' + key + '_lng');

    if (latEl) {
        latEl.value = Number(lat).toFixed(8);
    }
    if (lngEl) {
        lngEl.value = Number(lng).toFixed(8);
    }

    if (updateMap) {
        setMapMarkerFor(key, Number(lat), Number(lng), true);
    }
}

function setMapMarkerFor(key, lat, lng, centerMap) {
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    if (!branchMaps[key]) {
        ensureBranchMap(key);
    }

    const entry = branchMaps[key];
    if (!entry) {
        return;
    }

    const latLng = [lat, lng];
    entry.marker.setLatLng(latLng);

    if (centerMap) {
        entry.map.setView(latLng, Math.max(entry.map.getZoom(), 16));
    }
}

function syncMapFromCoordinateInputsFor(key) {
    const latEl = document.getElementById('suc_' + key + '_lat');
    const lngEl = document.getElementById('suc_' + key + '_lng');
    const lat = parseFloat((latEl && latEl.value) || '');
    const lng = parseFloat((lngEl && lngEl.value) || '');

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        setMapMarkerFor(key, lat, lng, true);
    }
}

function useCurrentLocationFor(key) {
    const status = document.getElementById('suc_' + key + '_status');

    if (!navigator.geolocation) {
        if (status) {
            status.textContent = 'Este navegador no permite obtener ubicacion.';
            status.className = 'text-sm text-red-600';
        }
        return;
    }

    if (status) {
        status.textContent = 'Obteniendo ubicacion...';
        status.className = 'text-sm text-gray-500';
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            setLatLngFor(key, position.coords.latitude, position.coords.longitude, true);
            reverseGeocodeFor(key, position.coords.latitude, position.coords.longitude);
            if (status) {
                status.textContent = 'Ubicacion capturada.';
                status.className = 'text-sm text-green-600';
            }
        },
        function() {
            if (status) {
                status.textContent = 'No se pudo obtener la ubicacion. Revise permisos del navegador.';
                status.className = 'text-sm text-red-600';
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function handleAddressInputFor(key, event) {
    const query = event.target.value.trim();

    branchTimers[key] = branchTimers[key] || {};
    clearTimeout(branchTimers[key].address);

    if (query.length < 3) {
        hideAddressSuggestionsFor(key);
        return;
    }

    branchTimers[key].address = setTimeout(function() {
        searchAddressFor(key, query);
    }, 350);
}

function searchAddressFor(key, query) {
    const suggestions = document.getElementById('suc_' + key + '_suggestions');
    if (!suggestions) {
        return;
    }

    suggestions.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Buscando...</div>';
    suggestions.classList.remove('hidden');

    const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6&countrycodes=mx&q=' + encodeURIComponent(query);

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'Accept-Language': 'es'
        }
    })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Busqueda no disponible');
            }
            return response.json();
        })
        .then(function(results) { renderAddressSuggestionsFor(key, results); })
        .catch(function() {
            suggestions.innerHTML = '<div class="px-4 py-3 text-sm text-red-600">No se pudieron cargar sugerencias.</div>';
        });
}

function renderAddressSuggestionsFor(key, results) {
    const suggestions = document.getElementById('suc_' + key + '_suggestions');
    if (!suggestions) {
        return;
    }

    if (!Array.isArray(results) || results.length === 0) {
        suggestions.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Sin resultados.</div>';
        suggestions.classList.remove('hidden');
        return;
    }

    suggestions.innerHTML = '';
    results.forEach(function(result) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'block w-full px-4 py-3 text-left text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none border-b border-gray-100 last:border-b-0';
        button.textContent = result.display_name;
        ['pointerdown', 'mousedown', 'click'].forEach(function(evtName) {
            button.addEventListener(evtName, function(event) {
                event.preventDefault();
                selectAddressSuggestionFor(key, result);
            });
        });
        suggestions.appendChild(button);
    });

    suggestions.classList.remove('hidden');
}

function selectAddressSuggestionFor(key, result) {
    const addressInput = document.getElementById('suc_' + key + '_direccion');
    const status = document.getElementById('suc_' + key + '_status');
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    const displayName = String(result.display_name || '');

    if (addressInput) {
        addressInput.value = displayName;
    }

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        setLatLngFor(key, lat, lng, true);
        if (status) {
            status.textContent = 'Direccion seleccionada y coordenadas actualizadas.';
            status.className = 'text-sm text-green-600';
        }
    }

    hideAddressSuggestionsFor(key);
}

function reverseGeocodeFor(key, lat, lng) {
    const addressInput = document.getElementById('suc_' + key + '_direccion');
    const status = document.getElementById('suc_' + key + '_status');
    const parsedLat = Number(lat);
    const parsedLng = Number(lng);

    if (!addressInput || !Number.isFinite(parsedLat) || !Number.isFinite(parsedLng)) {
        return;
    }

    branchTimers[key] = branchTimers[key] || {};
    clearTimeout(branchTimers[key].reverse);

    branchTimers[key].reverse = setTimeout(function() {
        if (status) {
            status.textContent = 'Buscando direccion del punto seleccionado...';
            status.className = 'text-sm text-gray-500';
        }

        const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
            + encodeURIComponent(parsedLat.toFixed(8))
            + '&lon='
            + encodeURIComponent(parsedLng.toFixed(8))
            + '&zoom=18&addressdetails=1';

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Accept-Language': 'es'
            }
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Direccion no disponible');
                }
                return response.json();
            })
            .then(function(result) {
                if (result && result.display_name) {
                    addressInput.value = result.display_name;
                    if (status) {
                        status.textContent = 'Direccion actualizada desde el mapa.';
                        status.className = 'text-sm text-green-600';
                    }
                } else if (status) {
                    status.textContent = 'Coordenadas actualizadas. No se encontro una direccion exacta.';
                    status.className = 'text-sm text-yellow-700';
                }
            })
            .catch(function() {
                if (status) {
                    status.textContent = 'Coordenadas actualizadas. No se pudo consultar la direccion.';
                    status.className = 'text-sm text-yellow-700';
                }
            });
    }, 300);
}

function hideAddressSuggestionsFor(key) {
    const suggestions = document.getElementById('suc_' + key + '_suggestions');
    if (suggestions) {
        suggestions.classList.add('hidden');
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
