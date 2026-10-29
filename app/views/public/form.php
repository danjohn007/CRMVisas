<?php
$rawFormDescription = trim((string)($form['description'] ?? ''));
$specificFormDescription = $rawFormDescription;
if ($rawFormDescription !== '') {
    $parts = preg_split('/Estimado cliente,/iu', $rawFormDescription, 2);
    $specificFormDescription = trim((string)($parts[0] ?? $rawFormDescription));
}
$standardClientNotice = "Estimado cliente, recuerde que la responsabilidad para que su proceso sea un éxito depende de ambas partes: nosotros con nuestra experiencia y usted con apoyarnos a entregar datos y requisitos que solicitamos durante su proceso, en tiempo y forma.

Una recomendación sobre este cuestionario es NO DEJAR RESPUESTAS EN BLANCO; en caso de ser así, asumiremos que no hay respuesta y así llenaremos su solicitud.

Por favor, LEA LAS PREGUNTAS CUIDADOSAMENTE y, si tiene alguna pregunta, siempre estamos disponibles para aclarar sus dudas con nuestras asesoras.";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['name']) ?> - Visas Querétaro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-field-required:after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-8 px-4 shadow-lg">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= htmlspecialchars($form['name']) ?></h1>
            <?php if ($specificFormDescription !== ''): ?>
            <p class="text-blue-100 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($specificFormDescription) ?></p>
            <?php endif; ?>
            <div class="mt-5 rounded-xl border border-white/20 bg-white/10 p-4 text-sm md:text-base text-blue-50 whitespace-pre-line leading-relaxed">
                <?= htmlspecialchars($standardClientNotice) ?>
            </div>
            <div class="mt-4 flex items-center space-x-4 text-sm">
                <span><i class="fas fa-file-alt mr-2"></i><?= htmlspecialchars($form['type']) ?></span>
                <?php if (!empty($form['subtype'])): ?>
                <span><i class="fas fa-tag mr-2"></i><?= htmlspecialchars($form['subtype']) ?></span>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <?php if (!empty($alreadyCompleted)): ?>
        <!-- Already completed message -->
        <div class="bg-green-50 border-l-4 border-green-500 p-6 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-bold text-green-800">¡Formulario ya completado!</h3>
                    <p class="text-green-700">Este cuestionario ya fue llenado anteriormente. Solo se permite una respuesta por solicitud.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Progress Bar (if pagination enabled) -->
        <?php if ($form['pagination_enabled'] && $pages): ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Progreso del Formulario</h2>
                <span id="progress-text" class="text-sm font-semibold text-blue-600">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div id="progress-bar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="mt-3 text-sm text-gray-600">
                <span id="page-indicator">Página 1 de <?= count($pages) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Success Message -->
        <div id="success-message" class="hidden bg-green-50 border-l-4 border-green-500 p-6 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-bold text-green-800">¡Formulario Enviado Exitosamente!</h3>
                    <p class="text-green-700">Gracias por completar el formulario. Hemos recibido tu información y te contactaremos pronto.</p>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
            <form id="public-form" class="space-y-6" novalidate>
                <input type="hidden" id="submission-id" name="submissionId" value="">
                <input type="hidden" id="current-page" name="currentPage" value="1">
                
                <?php foreach ($fields['fields'] as $field): ?>
                <div class="form-field"
                     data-field-id="<?= htmlspecialchars($field['id']) ?>"
                     data-conditional-enabled="<?= !empty($field['conditional']['enabled']) ? '1' : '0' ?>"
                     data-conditional-parent="<?= htmlspecialchars($field['conditional']['parentFieldId'] ?? '') ?>"
                     data-conditional-value="<?= htmlspecialchars($field['conditional']['value'] ?? '') ?>"
                     data-page="<?php
                    // Find which page this field belongs to
                    $pageAssigned = false;
                    if (!empty($form['pagination_enabled']) && !empty($pages)) {
                        foreach ($pages as $page) {
                            if (in_array($field['id'], $page['fieldIds'])) {
                                echo $page['id'];
                                $pageAssigned = true;
                                break;
                            }
                        }
                        // If not assigned to any page, default to page 1
                        if (!$pageAssigned) {
                            echo '1';
                        }
                    } else {
                        echo '1';
                    }
                ?>">
                    <?php if ($field['type'] !== 'label'): ?>
                    <label class="block text-sm font-medium text-gray-700 mb-2 <?= !empty($field['required']) ? 'form-field-required' : '' ?>">
                        <?= htmlspecialchars($field['label']) ?>
                    </label>
                    <?php if (!empty($field['helpText'])): ?>
                    <p class="text-sm font-bold text-gray-900 -mt-1 mb-2">
                        <?= htmlspecialchars($field['helpText']) ?>
                    </p>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($field['type'] === 'label'): ?>
                        <div class="col-span-full border-b-2 border-gray-300 pb-1">
                            <h3 class="text-base font-bold text-gray-800 uppercase tracking-wide">
                                <?= htmlspecialchars($field['label']) ?>
                            </h3>
                        </div>
                    
                    <?php elseif ($field['type'] === 'text' || $field['type'] === 'email' || $field['type'] === 'tel'): ?>
                        <input type="<?= htmlspecialchars($field['type']) ?>" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="<?= htmlspecialchars($field['label']) ?>">
                    
                    <?php elseif ($field['type'] === 'number'): ?>
                        <input type="number" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    
                    <?php elseif ($field['type'] === 'date'): ?>
                        <input type="date" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= htmlspecialchars($field['id']) ?>"
                                  id="field_<?= htmlspecialchars($field['id']) ?>"
                                  <?= !empty($field['required']) ? 'required' : '' ?>
                                  rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="<?= htmlspecialchars($field['label']) ?>"></textarea>
                    
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select name="<?= htmlspecialchars($field['id']) ?>"
                                id="field_<?= htmlspecialchars($field['id']) ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Seleccione...</option>
                            <?php foreach ($field['options'] ?? [] as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    
                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="<?= htmlspecialchars($field['id']) ?>"
                                   id="field_<?= htmlspecialchars($field['id']) ?>"
                                   <?= !empty($field['required']) ? 'required' : '' ?>
                                   class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <label for="field_<?= htmlspecialchars($field['id']) ?>" class="ml-2 text-sm text-gray-700">
                                <?= htmlspecialchars($field['label']) ?>
                            </label>
                        </div>
                    
                    <?php elseif ($field['type'] === 'file'): ?>
                        <input type="file" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle"></i> Formatos: PDF, JPG, PNG, DOC, DOCX (Máx. 10MB)
                        </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Auto-save status -->
                <div id="autosave-status" class="text-sm text-gray-500 text-center hidden">
                    <i class="fas fa-cloud-upload-alt mr-1"></i>
                    <span id="autosave-text">Guardando...</span>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t">
                    <div class="flex gap-4">
                        <button type="button" id="prev-page-btn" 
                                class="hidden px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Anterior
                        </button>
                        
                        <button type="button" id="save-draft-btn" 
                                class="w-full md:w-auto px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-save mr-2"></i>Guardar Borrador
                        </button>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="button" id="next-page-btn"
                                class="hidden px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-arrow-right mr-2"></i>Siguiente
                        </button>
                        
                        <button type="submit" id="submit-btn"
                                class="w-full md:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Formulario
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer Info -->
        <div class="text-center mt-8 text-sm text-gray-600">
            <p><i class="fas fa-lock mr-1"></i>Tus datos están protegidos y serán utilizados únicamente para procesar tu solicitud</p>
            <p class="mt-2">Creado por: <?= htmlspecialchars($form['creator_name']) ?> | <?= htmlspecialchars($form['creator_email']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const form = document.getElementById('public-form');
        const submitBtn = document.getElementById('submit-btn');
        const saveDraftBtn = document.getElementById('save-draft-btn');
        const prevPageBtn = document.getElementById('prev-page-btn');
        const nextPageBtn = document.getElementById('next-page-btn');
        const autosaveStatus = document.getElementById('autosave-status');
        const autosaveText = document.getElementById('autosave-text');
        const successMessage = document.getElementById('success-message');
        const submissionIdInput = document.getElementById('submission-id');
        const currentPageInput = document.getElementById('current-page');
        
        // Configuration
        const AUTOSAVE_DELAY_MS = 3000; // Auto-save after 3 seconds of no input
        const paginationEnabled = <?= json_encode($form['pagination_enabled'] ?? false, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const pages = <?= json_encode($pages ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const totalPages = pages.length || 1;
        const FORM_TOKEN = '<?= $token ?>';
        const LOCALSTORAGE_KEY = `form_draft_${FORM_TOKEN}`;
        
        let currentPage = 1;
        let autosaveTimeout;

        initializeConditionalRequiredTracking();
        
        // Load draft from localStorage on page load
        loadDraftFromLocalStorage();
        
        // Initialize pagination
        if (paginationEnabled && pages.length > 0) {
            initializePagination();
            showPage(1);
        } else {
            applyConditionalVisibility();
        }
        
        // Auto-save on input change
        form.addEventListener('input', function() {
            applyConditionalVisibility();
            clearTimeout(autosaveTimeout);
            autosaveTimeout = setTimeout(autoSave, AUTOSAVE_DELAY_MS);
        });

        form.querySelectorAll('select').forEach(function(selectEl) {
            selectEl.addEventListener('change', function() {
                applyConditionalVisibility();
                calculateProgress();
            });
        });
        
        // Save draft manually
        saveDraftBtn.addEventListener('click', function() {
            saveDraftToLocalStorage();
        });
        
        // Submit form
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const missingFields = validateRequiredFields();
            if (missingFields.length > 0) {
                const missingLabels = missingFields.map(function(f) {
                    return f.label + (f.pageNum ? ' (Página ' + f.pageNum + ')' : '');
                });
                const msg = 'Favor de contestar todo el formulario, pregunta pendiente de respuesta: ' + missingLabels.join(', ');
                alert(msg);
                // Navigate to the page with the first missing field
                if (paginationEnabled && pages.length > 0 && missingFields[0].pageId) {
                    showPage(missingFields[0].pageId);
                }
                return;
            }
            saveForm(true);
        });
        
        // Previous page button
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                const targetPage = currentPage - 1;
                saveDraftToLocalStorage();
                showPage(targetPage);
            }
        });
        
        // Next page button
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                const targetPage = currentPage + 1;
                saveDraftToLocalStorage();
                showPage(targetPage);
            }
        });
        
        function loadDraftFromLocalStorage() {
            try {
                const savedDraft = localStorage.getItem(LOCALSTORAGE_KEY);
                if (savedDraft) {
                    const draftData = JSON.parse(savedDraft);
                    
                    // Restore form field values
                    Object.keys(draftData).forEach(fieldName => {
                        // Field names don't have the 'field_' prefix, but IDs do
                        const field = document.getElementById(`field_${fieldName}`);
                        if (field) {
                            // Skip file inputs - they cannot be programmatically set for security reasons
                            if (field.type === 'file') {
                                return;
                            }
                            
                            if (field.type === 'checkbox') {
                                field.checked = draftData[fieldName] === 'on' || draftData[fieldName] === true;
                            } else if (field.type === 'radio') {
                                // For radio buttons, check if this radio's value matches the saved value
                                const radioButtons = document.getElementsByName(fieldName);
                                radioButtons.forEach(radio => {
                                    if (radio.value === draftData[fieldName]) {
                                        radio.checked = true;
                                    }
                                });
                            } else if (field.tagName === 'SELECT') {
                                // For select elements, verify option exists before setting
                                const optionExists = Array.from(field.options).some(option => option.value === draftData[fieldName]);
                                if (optionExists) {
                                    field.value = draftData[fieldName];
                                }
                            } else {
                                field.value = draftData[fieldName];
                            }
                        }
                    });
                    
                    console.log('Draft loaded from localStorage');
                }
            } catch (error) {
                console.error('Error loading draft from localStorage:', error);
            }
        }

        function initializeConditionalRequiredTracking() {
            document.querySelectorAll('.form-field input[required], .form-field select[required], .form-field textarea[required]').forEach(function(control) {
                control.dataset.originalRequired = '1';
            });
        }
        
        function saveDraftToLocalStorage() {
            try {
                const formData = new FormData(form);
                const data = {};
                
                // Store field values using their field names (without 'field_' prefix)
                // Skip file inputs since they can't be restored from localStorage
                for (let [key, value] of formData.entries()) {
                    if (key !== 'submissionId' && key !== 'currentPage') {
                        // Check if this is a file input
                        const field = form.elements[key] || document.getElementById(`field_${key}`);
                        if (field && field.type === 'file') {
                            // Skip file inputs - they cannot be stored in localStorage
                            continue;
                        }
                        data[key] = value;
                    }
                }
                
                localStorage.setItem(LOCALSTORAGE_KEY, JSON.stringify(data));
                
                // Show feedback
                autosaveStatus.classList.remove('hidden');
                autosaveText.textContent = '✓ Borrador guardado localmente';
                setTimeout(() => {
                    autosaveStatus.classList.add('hidden');
                }, 2000);
                
                console.log('Draft saved to localStorage');
            } catch (error) {
                console.error('Error saving draft to localStorage:', error);
                alert('Error al guardar el borrador localmente');
            }
        }
        
        function initializePagination() {
            // No additional initialization needed for now
        }
        
        function showPage(pageNum) {
            currentPage = pageNum;
            currentPageInput.value = pageNum;
            
            // Hide all fields first
            const allFields = document.querySelectorAll('.form-field');
            allFields.forEach(field => {
                field.style.display = 'none';
            });
            
            // Show fields for current page
            const currentPageData = pages.find(p => p.id === pageNum);
            if (currentPageData) {
                currentPageData.fieldIds.forEach(fieldId => {
                    const fieldElement = document.querySelector(`.form-field[data-field-id="${fieldId}"]`);
                    if (fieldElement) {
                        fieldElement.style.display = 'block';
                    }
                });
            }
            
            applyConditionalVisibility();
            
            // Update page indicator
            const pageIndicator = document.getElementById('page-indicator');
            if (pageIndicator) {
                pageIndicator.textContent = `Página ${pageNum} de ${totalPages}`;
            }
            
            // Update button visibility
            updateNavigationButtons();
            
            // Calculate and update progress
            calculateProgress();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function updateNavigationButtons() {
            // Previous button
            if (currentPage > 1) {
                prevPageBtn.classList.remove('hidden');
            } else {
                prevPageBtn.classList.add('hidden');
            }
            
            // Next button and Submit button
            if (currentPage < totalPages) {
                nextPageBtn.classList.remove('hidden');
                submitBtn.classList.add('hidden');
                saveDraftBtn.classList.add('hidden');
            } else {
                nextPageBtn.classList.add('hidden');
                submitBtn.classList.remove('hidden');
                saveDraftBtn.classList.remove('hidden');
            }
        }
        
        function calculateProgress() {
            if (!paginationEnabled || pages.length === 0) return;
            
            // Get all unique field IDs across all pages
            const uniqueFieldIds = new Set();
            pages.forEach(page => {
                page.fieldIds.forEach(fieldId => uniqueFieldIds.add(fieldId));
            });
            
            // Count filled fields and track radio groups
            let filledCount = 0;
            let totalCountableFields = 0;
            const processedRadioGroups = new Set(); // Track radio groups to count only once
            
            uniqueFieldIds.forEach(fieldId => {
                const field = document.getElementById(`field_${fieldId}`);
                if (field) {
                    const fieldContainer = field.closest('.form-field');
                    if (fieldContainer && fieldContainer.style.display === 'none') {
                        return;
                    }

                    let isFilled = false;
                    let shouldCount = true;
                    
                    if (field.type === 'checkbox') {
                        isFilled = field.checked;
                    } else if (field.type === 'radio') {
                        // For radio buttons, check if any in the group is selected
                        // Only count each radio group once
                        const radioName = field.name;
                        if (!processedRadioGroups.has(radioName)) {
                            processedRadioGroups.add(radioName);
                            const radioGroup = document.querySelectorAll(`input[name="${radioName}"]`);
                            isFilled = Array.from(radioGroup).some(radio => radio.checked);
                        } else {
                            // Skip this field as we already processed this radio group
                            shouldCount = false;
                        }
                    } else {
                        // For text inputs, selects, textareas, etc.
                        isFilled = field.value && field.value.trim() !== '';
                    }
                    
                    if (shouldCount) {
                        totalCountableFields++;
                        if (isFilled) {
                            filledCount++;
                        }
                    }
                }
            });
            
            const percentage = totalCountableFields > 0 ? (filledCount / totalCountableFields) * 100 : 0;
            updateProgress(percentage);
        }
        
        function autoSave() {
            // Auto-save to localStorage instead of server
            saveDraftToLocalStorage();
        }
        
        function saveForm(isCompleted = false, callback = null, errorCallback = null) {
            submitBtn.disabled = true;
            saveDraftBtn.disabled = true;
            
            autosaveStatus.classList.remove('hidden');
            autosaveText.textContent = isCompleted ? 'Enviando...' : 'Guardando...';
            
            const formData = new FormData(form);
            const data = {};
            const payload = new FormData();
            
            // Separate file inputs from regular inputs
            for (let [key, value] of formData.entries()) {
                if (key !== 'submissionId' && key !== 'currentPage') {
                    // Try to find field element - support both standard form.elements and field_ prefix
                    const field = form.elements[key] || document.getElementById(`field_${key}`);
                    if (field && field.type === 'file') {
                        // Add file directly to payload
                        if (field.files && field.files[0]) {
                            payload.append(key, field.files[0]);
                            // Store filename in data for JSON
                            data[key] = field.files[0].name;
                        }
                    } else {
                        // Regular field
                        data[key] = value;
                    }
                }
            }
            
            payload.append('formData', JSON.stringify(data));
            payload.append('currentPage', document.getElementById('current-page').value);
            payload.append('isCompleted', isCompleted);
            
            <?php if (!empty($appId)): ?>
            payload.append('appId', '<?= intval($appId) ?>');
            <?php endif; ?>

            if (submissionIdInput.value) {
                payload.append('submissionId', submissionIdInput.value);
            }
            
            fetch('<?= BASE_URL ?>/form/<?= $token ?>/submit', {
                method: 'POST',
                body: payload
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (result.submissionId && !submissionIdInput.value) {
                        submissionIdInput.value = result.submissionId;
                    }
                    
                    if (isCompleted) {
                        // Clear localStorage when form is successfully submitted
                        try {
                            localStorage.removeItem(LOCALSTORAGE_KEY);
                            console.log('Draft cleared from localStorage after successful submission');
                        } catch (error) {
                            console.error('Error clearing localStorage:', error);
                        }
                        
                        form.style.display = 'none';
                        successMessage.classList.remove('hidden');
                        window.scrollTo(0, 0);
                    } else {
                        autosaveText.textContent = '✓ Guardado';
                        setTimeout(() => {
                            autosaveStatus.classList.add('hidden');
                        }, 2000);
                    }
                    
                    // Update progress if available
                    if (result.progressPercentage) {
                        updateProgress(result.progressPercentage);
                    } else if (paginationEnabled) {
                        calculateProgress();
                    }
                    
                    // Execute callback if provided
                    if (callback) {
                        callback();
                    }
                } else {
                    alert('Error: ' + (result.error || 'No se pudo guardar el formulario'));
                    
                    // Execute error callback if provided
                    if (errorCallback) {
                        errorCallback();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el formulario. Por favor, intenta de nuevo.');
                
                // Execute error callback if provided
                if (errorCallback) {
                    errorCallback();
                }
            })
            .finally(() => {
                submitBtn.disabled = false;
                saveDraftBtn.disabled = false;
            });
        }
        
        function updateProgress(percentage) {
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            
            if (progressBar && progressText) {
                progressBar.style.width = percentage + '%';
                progressText.textContent = Math.round(percentage) + '%';
            }
        }

        function validateRequiredFields() {
            const missingFields = [];

            // Build page lookup: pageId -> sequential page number
            const pageMap = {};
            if (paginationEnabled && pages.length > 0) {
                pages.forEach(function(page, index) {
                    pageMap[String(page.id)] = { num: index + 1, id: page.id };
                });
            }

            // Build field -> page mapping from data-page attribute on .form-field divs
            const fieldPageMap = {};
            document.querySelectorAll('.form-field').forEach(function(fieldDiv) {
                const fid = fieldDiv.getAttribute('data-field-id');
                const pid = fieldDiv.getAttribute('data-page');
                if (fid) {
                    fieldPageMap[fid] = pid;
                }
            });

            const processedRadioNames = new Set();

            document.querySelectorAll('.form-field').forEach(function(fieldDiv) {
                if (!shouldValidateField(fieldDiv)) {
                    return;
                }

                const fieldId = fieldDiv.getAttribute('data-field-id');
                const pageIdAttr = fieldDiv.getAttribute('data-page');
                const pageInfo = paginationEnabled && pageIdAttr ? pageMap[String(pageIdAttr)] : null;
                const pageNum = pageInfo ? pageInfo.num : null;
                const pageId = pageInfo ? pageInfo.id : null;

                // Get display label from the first <label> in the div
                const labelEl = fieldDiv.querySelector('label');
                const label = labelEl ? labelEl.textContent.replace('*', '').trim() : fieldId;

                // Check required inputs, selects, and textareas
                const inputs = fieldDiv.querySelectorAll('input[required], select[required], textarea[required]');
                inputs.forEach(function(input) {
                    if (input.type === 'radio') {
                        const rname = input.name;
                        if (!processedRadioNames.has(rname)) {
                            processedRadioNames.add(rname);
                            const group = document.getElementsByName(rname);
                            if (!Array.from(group).some(r => r.checked)) {
                                missingFields.push({ label: label, pageNum: pageNum, pageId: pageId });
                            }
                        }
                    } else if (input.type === 'checkbox') {
                        if (!input.checked) {
                            missingFields.push({ label: label, pageNum: pageNum, pageId: pageId });
                        }
                    } else if (input.type === 'file') {
                        // File inputs: check if a file has been selected
                        if (!input.files || input.files.length === 0) {
                            missingFields.push({ label: label, pageNum: pageNum, pageId: pageId });
                        }
                    } else {
                        if (!input.value || input.value.trim() === '') {
                            missingFields.push({ label: label, pageNum: pageNum, pageId: pageId });
                        }
                    }
                });
            });

            return missingFields;
        }

        function applyConditionalVisibility() {
            const conditionalFields = document.querySelectorAll('.form-field[data-conditional-enabled="1"]');
            conditionalFields.forEach(function(fieldDiv) {
                const fieldPage = parseInt(fieldDiv.getAttribute('data-page') || '1', 10);
                if (paginationEnabled && pages.length > 0 && fieldPage !== currentPage) {
                    fieldDiv.style.display = 'none';
                    return;
                }

                const parentFieldId = fieldDiv.getAttribute('data-conditional-parent');
                const expectedValue = (fieldDiv.getAttribute('data-conditional-value') || '').trim();

                if (!parentFieldId || !expectedValue) {
                    return;
                }

                const parentField = document.getElementById(`field_${parentFieldId}`);
                if (!parentField) {
                    return;
                }

                const parentValue = String(parentField.value || '').trim();
                const shouldShow = parentValue === expectedValue;
                const controls = fieldDiv.querySelectorAll('input, select, textarea');

                fieldDiv.style.display = shouldShow ? 'block' : 'none';

                controls.forEach(function(control) {
                    const isRequired = control.hasAttribute('required');
                    if (shouldShow) {
                        if (control.dataset.originalRequired === '1') {
                            control.setAttribute('required', 'required');
                        }
                    } else {
                        if (isRequired) {
                            if (typeof control.dataset.originalRequired === 'undefined') {
                                control.dataset.originalRequired = '1';
                            }
                            control.removeAttribute('required');
                        }
                    }
                });
            });
        }
        function shouldValidateField(fieldDiv) {
            if (fieldDiv.getAttribute('data-conditional-enabled') !== '1') {
                return true;
            }

            const parentFieldId = fieldDiv.getAttribute('data-conditional-parent');
            const expectedValue = (fieldDiv.getAttribute('data-conditional-value') || '').trim();

            if (!parentFieldId || !expectedValue) {
                return false;
            }

            const parentField = document.getElementById(`field_${parentFieldId}`);
            if (!parentField) {
                return false;
            }

            return String(parentField.value || '').trim() === expectedValue;
        }
    </script>
</body>
</html>
