<?php
$title = 'Editar Solicitud - ' . $application['folio'];
ob_start();

$data = json_decode((string)($application['data_json'] ?? ''), true);
if (!is_array($data)) $data = [];

$fieldsJson = json_decode((string)($application['fields_json'] ?? ''), true);
$fields = is_array($fieldsJson) ? ($fieldsJson['fields'] ?? []) : [];

$isCanadianVisa = !empty($application['is_canadian_visa']);
$isUniqueUsPassportForm =
    trim((string)($application['form_name'] ?? '')) === 'CUESTIONARIO ÚNICO - PASAPORTE AMERICANO'
    && trim((string)($application['type'] ?? '')) === 'Pasaporte'
    && trim((string)($application['subtype'] ?? '')) === 'Única Vez';

if (!$isUniqueUsPassportForm && empty($data['nombre']) && !empty($application['client_name'])) {
    $parts = explode(' ', trim((string)$application['client_name']), 2);
    $data['nombre'] = $parts[0] ?? '';
    $data['apellidos'] = $parts[1] ?? '';
}
if ($isUniqueUsPassportForm && empty($data['nombre_cliente']) && !empty($application['client_name'])) {
    $data['nombre_cliente'] = $application['client_name'];
}

$basicKeys = ['nombre','apellidos','email','telefono','nombre_cliente','pago','fecha_cita'];

$normalizeOption = function($option) {
    if (is_array($option)) {
        $value = (string)($option['value'] ?? $option['label'] ?? $option['text'] ?? '');
        $label = (string)($option['label'] ?? $option['text'] ?? $option['value'] ?? '');
        return [$value, $label];
    }
    return [(string)$option, (string)$option];
};
?>

<div class="max-w-5xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <div>
            <div class="text-sm text-gray-500">Solicitud <?= htmlspecialchars($application['folio']) ?></div>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Editar información</h2>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($application['form_name'] ?? '') ?></p>
        </div>
        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$application['id'] ?>"
           class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>Volver al detalle
        </a>
    </div>

    <?php if (!empty($botApplicationLink)): ?>
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <i class="fab fa-whatsapp mr-2"></i>
        Esta solicitud se creó desde WhatsApp. Puede corregir o completar la información formal aquí;
        el perfilamiento original del bot se conserva como historial y no se sobrescribe.
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/solicitudes/actualizar/<?= (int)$application['id'] ?>" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($editCsrfToken) ?>">

        <div class="bg-white rounded-2xl shadow p-5 md:p-6">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Datos básicos del solicitante</h3>
                    <p class="text-sm text-gray-500">Estos datos actualizan el expediente y el nombre visible en Control de Ingresos.</p>
                </div>
                <span class="text-xs font-mono px-2 py-1 rounded bg-gray-100 text-gray-600"><?= htmlspecialchars($application['folio']) ?></span>
            </div>

            <?php if ($isUniqueUsPassportForm): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre del cliente *</label>
                    <input type="text" name="form_data[nombre_cliente]" required
                           value="<?= htmlspecialchars((string)($data['nombre_cliente'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Pago</label>
                    <input type="text" name="form_data[pago]"
                           value="<?= htmlspecialchars((string)($data['pago'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-500 mt-1">Este dato pertenece al formulario; no modifica el Control de Ingresos.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha de la cita</label>
                    <input type="date" name="form_data[fecha_cita]"
                           value="<?= htmlspecialchars((string)($data['fecha_cita'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre(s) *</label>
                    <input type="text" name="form_data[nombre]" required
                           value="<?= htmlspecialchars((string)($data['nombre'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Apellidos</label>
                    <input type="text" name="form_data[apellidos]"
                           value="<?= htmlspecialchars((string)($data['apellidos'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" name="form_data[email]"
                           value="<?= htmlspecialchars((string)($data['email'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                    <input type="tel" name="form_data[telefono]"
                           value="<?= htmlspecialchars((string)($data['telefono'] ?? '')) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isCanadianVisa): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 pt-5 border-t">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo canadiense *</label>
                    <select name="canadian_tipo" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="Visa Canadiense" <?= ($application['canadian_tipo'] ?? '') === 'Visa Canadiense' ? 'selected' : '' ?>>Visa Canadiense</option>
                        <option value="ETA Canadiense" <?= ($application['canadian_tipo'] ?? '') === 'ETA Canadiense' ? 'selected' : '' ?>>ETA Canadiense</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Modalidad *</label>
                    <select name="canadian_modalidad" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="Primera vez" <?= ($application['canadian_modalidad'] ?? '') === 'Primera vez' ? 'selected' : '' ?>>Primera vez</option>
                        <option value="Renovación" <?= ($application['canadian_modalidad'] ?? '') === 'Renovación' ? 'selected' : '' ?>>Renovación</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($fields): ?>
        <div class="bg-white rounded-2xl shadow p-5 md:p-6">
            <div class="mb-5">
                <h3 class="text-xl font-bold text-gray-800">Información del formulario</h3>
                <p class="text-sm text-gray-500">
                    Puede corregir o completar las respuestas del expediente. Los archivos adjuntos se mantienen y se administran desde Documentos.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($fields as $field):
                $fieldId = trim((string)($field['id'] ?? ''));
                $fieldType = strtolower(trim((string)($field['type'] ?? 'text')));
                $label = trim((string)($field['label'] ?? $fieldId));
                if ($fieldId === '' || in_array($fieldId, $basicKeys, true)) continue;
                $value = $data[$fieldId] ?? '';
                $options = $field['options'] ?? [];
            ?>

                <?php if (in_array($fieldType, ['label','heading','section'], true)): ?>
                <div class="md:col-span-2 mt-3 pt-4 border-t first:border-t-0">
                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($label) ?></h4>
                </div>

                <?php elseif (in_array($fieldType, ['paragraph','html'], true)): ?>
                <div class="md:col-span-2 text-sm text-gray-500"><?= htmlspecialchars($label) ?></div>

                <?php elseif ($fieldType === 'file'): ?>
                <div class="border rounded-xl p-4 bg-gray-50">
                    <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($label) ?></p>
                    <?php if (!empty($value)): ?>
                    <a href="<?= BASE_URL ?>/solicitudes/descargar-archivo/<?= (int)$application['id'] ?>/<?= urlencode($fieldId) ?>"
                       class="inline-flex items-center mt-2 text-sm text-blue-700 font-semibold hover:underline">
                        <i class="fas fa-paperclip mr-1"></i>Ver archivo actual
                    </a>
                    <?php else: ?>
                    <p class="text-xs text-gray-500 mt-2">Sin archivo guardado.</p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-400 mt-2">Para sustituir documentos utilice el apartado de Documentos del expediente.</p>
                </div>

                <?php else: ?>
                <div class="<?= $fieldType === 'textarea' ? 'md:col-span-2' : '' ?>">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <?= htmlspecialchars($label) ?>
                        <?php if (!empty($field['required'])): ?><span class="text-red-500">*</span><?php endif; ?>
                    </label>

                    <?php if ($fieldType === 'textarea'): ?>
                        <textarea name="form_data[<?= htmlspecialchars($fieldId) ?>]" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2"><?= htmlspecialchars(is_array($value) ? implode(', ', $value) : (string)$value) ?></textarea>

                    <?php elseif ($fieldType === 'select' && is_array($options) && $options): ?>
                        <select name="form_data[<?= htmlspecialchars($fieldId) ?>]"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($options as $option): [$optValue,$optLabel] = $normalizeOption($option); ?>
                            <option value="<?= htmlspecialchars($optValue) ?>" <?= (string)$value === $optValue ? 'selected' : '' ?>>
                                <?= htmlspecialchars($optLabel) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($fieldType === 'radio' && is_array($options) && $options): ?>
                        <div class="space-y-2 border rounded-lg p-3">
                            <?php foreach ($options as $option): [$optValue,$optLabel] = $normalizeOption($option); ?>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="radio" name="form_data[<?= htmlspecialchars($fieldId) ?>]"
                                       value="<?= htmlspecialchars($optValue) ?>" <?= (string)$value === $optValue ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($optLabel) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($fieldType === 'checkbox' && is_array($options) && $options): ?>
                        <?php $checkedValues = is_array($value) ? $value : ($value === '' ? [] : [$value]); ?>
                        <div class="space-y-2 border rounded-lg p-3">
                            <?php foreach ($options as $option): [$optValue,$optLabel] = $normalizeOption($option); ?>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="form_data[<?= htmlspecialchars($fieldId) ?>][]"
                                       value="<?= htmlspecialchars($optValue) ?>" <?= in_array($optValue, $checkedValues, true) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($optLabel) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                    <?php else:
                        $htmlType = in_array($fieldType, ['date','email','tel','number','time'], true) ? $fieldType : 'text';
                    ?>
                        <input type="<?= htmlspecialchars($htmlType) ?>"
                               name="form_data[<?= htmlspecialchars($fieldId) ?>]"
                               value="<?= htmlspecialchars(is_array($value) ? implode(', ', $value) : (string)$value) ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow p-5 flex flex-col sm:flex-row gap-3 sm:justify-end">
            <a href="<?= BASE_URL ?>/solicitudes/ver/<?= (int)$application['id'] ?>"
               class="px-5 py-3 rounded-lg bg-gray-200 text-gray-800 font-semibold text-center hover:bg-gray-300">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-3 rounded-lg bg-blue-700 text-white font-bold hover:bg-blue-800">
                <i class="fas fa-save mr-2"></i>Guardar cambios
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
