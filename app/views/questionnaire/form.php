<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuestionario - <?php echo htmlspecialchars($link['service_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-3xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                    <i class="fas fa-passport text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($link['service_name']); ?>
                </h1>
                <p class="text-gray-600">
                    Solicitante: <?php echo htmlspecialchars($link['first_name'] . ' ' . $link['last_name']); ?>
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    Solicitud: <?php echo htmlspecialchars($link['request_number']); ?>
                </p>
            </div>
        </div>

        <?php if ($success): ?>
            <!-- Success Message -->
            <div class="bg-green-100 border border-green-400 rounded-lg p-6 text-center">
                <i class="fas fa-check-circle text-green-600 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-green-800 mb-2">¡Formulario Enviado!</h2>
                <p class="text-green-700">
                    Gracias por completar el cuestionario. Nos pondremos en contacto contigo pronto.
                </p>
            </div>
        <?php elseif ($error): ?>
            <!-- Error Message -->
            <div class="bg-red-100 border border-red-400 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-circle text-red-600 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-red-800 mb-2">Error</h2>
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php else: ?>
            <!-- Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="POST" id="questionnaireForm">
                    <?php if (empty($fields)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No hay campos configurados para este formulario.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($fields as $field): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php echo htmlspecialchars($field['field_label']); ?>
                                        <?php if ($field['is_required']): ?>
                                            <span class="text-red-600">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php 
                                    $fieldName = 'field_' . $field['id'];
                                    $value = $responses[$field['id']] ?? '';
                                    $required = $field['is_required'] ? 'required' : '';
                                    ?>

                                    <?php if ($field['field_type'] === 'text'): ?>
                                        <input type="text" 
                                               name="<?php echo $fieldName; ?>" 
                                               value="<?php echo htmlspecialchars($value); ?>"
                                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                               <?php echo $required; ?>
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                                        <textarea 
                                            name="<?php echo $fieldName; ?>" 
                                            rows="4"
                                            placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                            <?php echo $required; ?>
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($value); ?></textarea>

                                    <?php elseif ($field['field_type'] === 'number'): ?>
                                        <input type="number" 
                                               name="<?php echo $fieldName; ?>" 
                                               value="<?php echo htmlspecialchars($value); ?>"
                                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                               <?php echo $required; ?>
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                                    <?php elseif ($field['field_type'] === 'email'): ?>
                                        <input type="email" 
                                               name="<?php echo $fieldName; ?>" 
                                               value="<?php echo htmlspecialchars($value); ?>"
                                               placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                               <?php echo $required; ?>
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                                    <?php elseif ($field['field_type'] === 'date'): ?>
                                        <input type="date" 
                                               name="<?php echo $fieldName; ?>" 
                                               value="<?php echo htmlspecialchars($value); ?>"
                                               <?php echo $required; ?>
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                                    <?php elseif ($field['field_type'] === 'select'): ?>
                                        <select name="<?php echo $fieldName; ?>" 
                                                <?php echo $required; ?>
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Seleccionar...</option>
                                            <?php 
                                            $options = explode(',', $field['field_options'] ?? '');
                                            foreach ($options as $option): 
                                                $option = trim($option);
                                                if ($option):
                                            ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" 
                                                        <?php echo $value === $option ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </select>

                                    <?php elseif ($field['field_type'] === 'radio'): ?>
                                        <div class="space-y-2">
                                            <?php 
                                            $options = explode(',', $field['field_options'] ?? '');
                                            foreach ($options as $option): 
                                                $option = trim($option);
                                                if ($option):
                                            ?>
                                                <label class="flex items-center">
                                                    <input type="radio" 
                                                           name="<?php echo $fieldName; ?>" 
                                                           value="<?php echo htmlspecialchars($option); ?>"
                                                           <?php echo $value === $option ? 'checked' : ''; ?>
                                                           <?php echo $required; ?>
                                                           class="mr-2">
                                                    <span><?php echo htmlspecialchars($option); ?></span>
                                                </label>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>

                                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                        <div class="space-y-2">
                                            <?php 
                                            $options = explode(',', $field['field_options'] ?? '');
                                            $selectedValues = explode(',', $value);
                                            foreach ($options as $option): 
                                                $option = trim($option);
                                                if ($option):
                                            ?>
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           name="<?php echo $fieldName; ?>[]" 
                                                           value="<?php echo htmlspecialchars($option); ?>"
                                                           <?php echo in_array($option, $selectedValues) ? 'checked' : ''; ?>
                                                           class="mr-2">
                                                    <span><?php echo htmlspecialchars($option); ?></span>
                                                </label>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($field['help_text']): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo htmlspecialchars($field['help_text']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-8 flex justify-center">
                            <button type="submit" 
                                    class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Enviar Formulario
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Info -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Este formulario es seguro y confidencial.</p>
                <p class="mt-1">
                    <?php if ($link['expires_at']): ?>
                        Válido hasta: <?php echo date('d/m/Y H:i', strtotime($link['expires_at'])); ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Auto-save functionality -->
    <script>
    let saveTimeout;
    document.getElementById('questionnaireForm')?.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            console.log('Auto-saving...');
            // Implement auto-save via AJAX here if needed
        }, 2000);
    });
    </script>
</body>
</html>
