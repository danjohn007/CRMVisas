<?php
$title = 'Prueba de Correo SMTP';
ob_start();
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-paper-plane text-green-600 mr-2"></i>Prueba de Envío de Correo
    </h2>
    <p class="text-gray-500">Valida la configuración SMTP del sistema enviando un correo de prueba</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Formulario de prueba -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                <i class="fas fa-envelope text-green-600 text-sm"></i>
            </span>
            Enviar Correo de Prueba
        </h3>

        <p class="text-sm text-gray-500 mb-6">
            La configuración SMTP se lee directamente desde la base de datos (<code class="bg-gray-100 px-1 rounded">global_config</code>).
            Asegúrate de que los campos <strong>smtp_host</strong>, <strong>smtp_user</strong>, <strong>smtp_password</strong> y <strong>smtp_port</strong> estén configurados en
            <a href="<?= BASE_URL ?>/configuracion" class="text-blue-600 underline">Configuración del Sistema</a>.
        </p>

        <div class="mb-4">
            <label for="email_destino" class="block text-sm font-medium text-gray-700 mb-2">
                Email de Destino <span class="text-red-500">*</span>
            </label>
            <input type="email" id="email_destino" name="email_destino"
                   placeholder="ejemplo@correo.com"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   required>
        </div>

        <button id="btn-enviar"
                onclick="enviarCorreoPrueba()"
                class="btn-primary text-white px-6 py-3 rounded-lg hover:opacity-90 transition font-medium w-full flex items-center justify-center">
            <i class="fas fa-paper-plane mr-2"></i>Enviar Correo de Prueba
        </button>

        <!-- Resultado -->
        <div id="resultado" class="mt-5 hidden">
            <!-- se rellena por JS -->
        </div>
    </div>

    <!-- Configuración SMTP actual -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                <i class="fas fa-server text-blue-600 text-sm"></i>
            </span>
            Configuración SMTP Activa
        </h3>
        <?php
        $smtpHost     = getConfig('smtp_host', '');
        $smtpUser     = getConfig('smtp_user', '');
        $smtpPort     = getConfig('smtp_port', '465');
        $smtpImapPort = getConfig('smtp_imap_port', '993');
        $smtpPop3Port = getConfig('smtp_pop3_port', '995');
        $hasConfig    = !empty($smtpHost) && !empty($smtpUser);
        ?>
        <?php if ($hasConfig): ?>
        <ul class="space-y-3 text-sm text-gray-600">
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 w-5 mr-2"></i>
                <span class="font-medium text-gray-700 w-32">Servidor (Host):</span>
                <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($smtpHost) ?></code>
            </li>
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 w-5 mr-2"></i>
                <span class="font-medium text-gray-700 w-32">Usuario SMTP:</span>
                <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($smtpUser) ?></code>
            </li>
            <li class="flex items-center">
                <i class="fas fa-check-circle text-green-500 w-5 mr-2"></i>
                <span class="font-medium text-gray-700 w-32">Puerto SMTP:</span>
                <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($smtpPort) ?></code>
                <span class="ml-2 text-xs text-gray-400"><?= $smtpPort == 465 ? '(SSL/SMTPS)' : '(STARTTLS)' ?></span>
            </li>
            <li class="flex items-center">
                <i class="fas fa-info-circle text-blue-400 w-5 mr-2"></i>
                <span class="font-medium text-gray-700 w-32">Puerto IMAP:</span>
                <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($smtpImapPort) ?></code>
            </li>
            <li class="flex items-center">
                <i class="fas fa-info-circle text-blue-400 w-5 mr-2"></i>
                <span class="font-medium text-gray-700 w-32">Puerto POP3:</span>
                <code class="bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($smtpPop3Port) ?></code>
            </li>
            <li class="flex items-center">
                <i class="fas fa-lock text-gray-400 w-5 mr-2"></i>
                <span class="font-medium text-gray-700 w-32">Contraseña:</span>
                <code class="bg-gray-100 px-2 py-0.5 rounded text-gray-400">••••••••</code>
            </li>
        </ul>
        <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <p class="text-yellow-800 text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                No se encontró configuración SMTP en la base de datos.
                <a href="<?= BASE_URL ?>/configuracion" class="underline font-medium">Configura el servidor SMTP</a> antes de enviar correos de prueba.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function enviarCorreoPrueba() {
    const emailInput = document.getElementById('email_destino');
    const btn        = document.getElementById('btn-enviar');
    const resultado  = document.getElementById('resultado');
    const email      = emailInput.value.trim();

    if (!email) {
        mostrarResultado(false, 'Por favor, ingresa un email de destino.');
        return;
    }

    // Deshabilitar botón durante el envío
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
    resultado.classList.add('hidden');

    const formData = new FormData();
    formData.append('email_destino', email);

    fetch('<?= BASE_URL ?>/test-email/enviar', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        mostrarResultado(data.success, data.message);
    })
    .catch(function(err) {
        mostrarResultado(false, 'Error de red al contactar el servidor: ' + err.message);
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Enviar Correo de Prueba';
    });
}

function mostrarResultado(success, message) {
    const resultado = document.getElementById('resultado');
    resultado.classList.remove('hidden');

    if (success) {
        resultado.innerHTML =
            '<div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">' +
            '  <div class="flex items-start">' +
            '    <i class="fas fa-check-circle text-green-500 text-xl mr-3 mt-0.5"></i>' +
            '    <div>' +
            '      <p class="font-semibold text-green-800">¡Correo enviado exitosamente!</p>' +
            '      <p class="text-green-700 text-sm mt-1">' + escapeHtml(message) + '</p>' +
            '    </div>' +
            '  </div>' +
            '</div>';
    } else {
        resultado.innerHTML =
            '<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">' +
            '  <div class="flex items-start">' +
            '    <i class="fas fa-times-circle text-red-500 text-xl mr-3 mt-0.5"></i>' +
            '    <div>' +
            '      <p class="font-semibold text-red-800">Error al enviar correo</p>' +
            '      <p class="text-red-700 text-sm mt-1 break-words">' + escapeHtml(message) + '</p>' +
            '    </div>' +
            '  </div>' +
            '</div>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Permitir envío con Enter
document.getElementById('email_destino').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { enviarCorreoPrueba(); }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
