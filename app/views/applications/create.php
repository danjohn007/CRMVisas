<?php 
$title = 'Crear Solicitud';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Nueva Solicitud</h2>
    <p class="text-gray-600">Complete los datos para crear un nuevo trámite</p>
</div>


<!-- ─── FLUJO ESTÁNDAR ─────────────────────────────────────── -->
<div id="form-standard" class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/solicitudes/crear" id="applicationForm">
        <input type="hidden" name="is_canadian_visa" value="0">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de trámite <span class="text-red-500">*</span>
            </label>
            <select name="form_id" id="form_id" required 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Seleccione el tipo de trámite --</option>
                <?php foreach ($forms as $form): ?>
                <option value="<?= $form['id'] ?>"
                        data-form-name="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-form-type="<?= htmlspecialchars($form['type'], ENT_QUOTES, 'UTF-8') ?>"
                        data-form-subtype="<?= htmlspecialchars($form['subtype'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($form['name']) ?> (<?= htmlspecialchars($form['type']) ?><?= !empty($form['subtype']) ? ' - ' . htmlspecialchars($form['subtype']) : '' ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Datos básicos del solicitante -->
        <div id="basic-fields" class="hidden space-y-4">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Datos básicos del solicitante</h3>
            <p class="text-sm text-gray-500 mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                El cuestionario completo será enviado al cliente vía enlace para que lo llene directamente.
            </p>
            <div id="basic-fields-default" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="form_data[nombre]" id="field_nombre" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Nombre(s)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos <span class="text-red-500">*</span></label>
                    <input type="text" name="form_data[apellidos]" id="field_apellidos" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Apellido paterno y materno">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="form_data[email]" id="field_email" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="correo@ejemplo.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono <span class="text-red-500">*</span></label>
                    <input type="tel" name="form_data[telefono]" id="field_telefono" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Número de teléfono"
                           pattern="[0-9+()\-\s]+" inputmode="tel"
                           title="Solo se permiten números y caracteres telefónicos">
                </div>
            </div>
            <div id="basic-fields-us-passport" class="hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del cliente <span class="text-red-500">*</span></label>
                    <input type="text" name="form_data[nombre_cliente]" id="field_nombre_cliente" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Nombre del cliente">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">El pago <span class="text-red-500">*</span></label>
                    <input type="text" name="form_data[pago]" id="field_pago" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Monto o referencia de pago">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de la cita <span class="text-red-500">*</span></label>
                    <input type="date" name="form_data[fecha_cita]" id="field_fecha_cita" required disabled
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="mt-8 flex gap-4">
            <button type="submit" id="submit-btn" disabled
                    class="btn-primary text-white px-8 py-3 rounded-lg hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-2"></i>Crear Solicitud
            </button>
            <a href="<?= BASE_URL ?>/solicitudes" class="bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<!-- ─── FLUJO VISA CANADIENSE ─────────────────────────────── -->
<div id="form-canadian" class="hidden bg-white rounded-lg shadow p-6 border-2 border-red-200">
    <form method="POST" action="<?= BASE_URL ?>/solicitudes/crear" id="applicationFormCanadian">
        <input type="hidden" name="is_canadian_visa" value="1">

        <div class="mb-4 p-3 bg-red-50 rounded-lg border border-red-200">
            <p class="text-red-700 font-semibold text-sm">
                <i class="fas fa-info-circle mr-1"></i>
                Flujo especializado para Visa / ETA Canadiense
            </p>
        </div>

        <h3 class="text-xl font-bold text-gray-800 mb-4">Campos iniciales</h3>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Formulario de cliente <span class="text-red-500">*</span>
            </label>
            <select name="form_id" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                <option value="">-- Seleccione el formulario --</option>
                <?php foreach ($forms as $form): ?>
                <option value="<?= $form['id'] ?>">
                    <?= htmlspecialchars($form['name']) ?> (<?= htmlspecialchars($form['type']) ?><?= !empty($form['subtype']) ? ' - ' . htmlspecialchars($form['subtype']) : '' ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="canadian_tipo" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">-- Seleccione --</option>
                    <option value="Visa Canadiense">Visa Canadiense</option>
                    <option value="ETA Canadiense">ETA Canadiense (si aplica)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Modalidad <span class="text-red-500">*</span>
                </label>
                <select name="canadian_modalidad" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">-- Seleccione --</option>
                    <option value="Primera vez">Primera vez</option>
                    <option value="Renovación">Renovación</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="form_data[nombre]" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       placeholder="Nombre(s)">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos <span class="text-red-500">*</span></label>
                <input type="text" name="form_data[apellidos]" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       placeholder="Apellido paterno y materno">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="form_data[email]" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       placeholder="correo@ejemplo.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono <span class="text-red-500">*</span></label>
                <input type="tel" name="form_data[telefono]" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"
                       placeholder="Número de teléfono"
                       pattern="[0-9+()\-\s]+" inputmode="tel"
                       title="Solo se permiten números y caracteres telefónicos">
            </div>
        </div>

        <div class="mt-8 flex gap-4">
            <button type="submit"
                    class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 transition font-semibold">
                <i class="fas fa-save mr-2"></i>Crear Solicitud Visa Canadiense
            </button>
            <a href="<?= BASE_URL ?>/solicitudes" class="bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function setFlow(flow) {
    var standard = document.getElementById('form-standard');
    var canadian = document.getElementById('form-canadian');
    var btnStd   = document.getElementById('btn-standard');
    var btnCan   = document.getElementById('btn-canadian');

    if (flow === 'canadian') {
        standard.classList.add('hidden');
        canadian.classList.remove('hidden');
        btnStd.classList.remove('bg-blue-600', 'text-white');
        btnStd.classList.add('bg-white', 'text-blue-600');
        btnCan.classList.remove('bg-white', 'text-red-600', 'hover:bg-red-600', 'hover:text-white');
        btnCan.classList.add('bg-red-600', 'text-white');
    } else {
        canadian.classList.add('hidden');
        standard.classList.remove('hidden');
        btnCan.classList.remove('bg-red-600', 'text-white');
        btnCan.classList.add('bg-white', 'text-red-600', 'hover:bg-red-600', 'hover:text-white');
        btnStd.classList.remove('bg-white', 'text-blue-600');
        btnStd.classList.add('bg-blue-600', 'text-white');
    }
}

function isUniqueUsPassportForm(selectEl) {
    if (!selectEl || !selectEl.value || !selectEl.selectedOptions.length) {
        return false;
    }
    var selected = selectEl.selectedOptions[0];
    var formName = (selected.dataset.formName || '').trim();
    var formType = (selected.dataset.formType || '').trim();
    var formSubtype = (selected.dataset.formSubtype || '').trim();
    return formName === 'CUESTIONARIO ÚNICO - PASAPORTE AMERICANO'
        && formType === 'Pasaporte'
        && formSubtype === 'Única Vez';
}

function setInputsEnabled(container, enabled) {
    if (!container) return;
    container.querySelectorAll('input, select, textarea').forEach(function(input) {
        if (typeof input.dataset.initialRequired === 'undefined') {
            input.dataset.initialRequired = input.required ? '1' : '0';
        }
        input.disabled = !enabled;
        input.required = enabled ? input.dataset.initialRequired === '1' : false;
    });
}

function setContainerVisibility(container, visible) {
    if (!container) return;
    if (visible) {
        container.classList.remove('hidden');
        container.classList.add('grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4');
        return;
    }
    container.classList.add('hidden');
    container.classList.remove('grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4');
}

document.getElementById('form_id').addEventListener('change', function() {
    var basicFields = document.getElementById('basic-fields');
    var defaultBasicFields = document.getElementById('basic-fields-default');
    var usPassportBasicFields = document.getElementById('basic-fields-us-passport');
    var submitBtn   = document.getElementById('submit-btn');
    if (this.value) {
        basicFields.classList.remove('hidden');
        submitBtn.disabled = false;
        if (isUniqueUsPassportForm(this)) {
            defaultBasicFields.classList.add('hidden');
            setContainerVisibility(usPassportBasicFields, true);
            setInputsEnabled(defaultBasicFields, false);
            setInputsEnabled(usPassportBasicFields, true);
        } else {
            setContainerVisibility(usPassportBasicFields, false);
            defaultBasicFields.classList.remove('hidden');
            setInputsEnabled(usPassportBasicFields, false);
            setInputsEnabled(defaultBasicFields, true);
        }
    } else {
        basicFields.classList.add('hidden');
        submitBtn.disabled = true;
        setContainerVisibility(usPassportBasicFields, false);
        setInputsEnabled(defaultBasicFields, false);
        setInputsEnabled(usPassportBasicFields, false);
    }
});

// Restrict phone inputs to numeric and allowed characters only
document.querySelectorAll('input[type="tel"]').forEach(function(input) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9+()\-\s]/g, '');
    });
});
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
