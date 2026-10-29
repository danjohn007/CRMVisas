/**
 * Visual Form Builder with Drag & Drop
 * Replaces JSON textarea with a user-friendly interface
 */
class FormBuilder {
    constructor(containerId, initialData = null) {
        this.container = document.getElementById(containerId);
        this.fields = [];
        this.pages = [{ id: 1, name: 'Página 1', fieldIds: [] }];
        this.currentPage = 1;
        this.paginationEnabled = false;
        this.draggedElement = null;
        this.draggedFieldType = null;
        this.draggedFieldIndex = null;
        this.nextId = 1;
        this.nextPageId = 2;
        
        // Field types available
        this.fieldTypes = [
            { id: 'text', label: 'Texto', icon: 'fa-font' },
            { id: 'email', label: 'Email', icon: 'fa-envelope' },
            { id: 'tel', label: 'Teléfono', icon: 'fa-phone' },
            { id: 'number', label: 'Número', icon: 'fa-hashtag' },
            { id: 'date', label: 'Fecha', icon: 'fa-calendar' },
            { id: 'select', label: 'Selección', icon: 'fa-list' },
            { id: 'textarea', label: 'Área de Texto', icon: 'fa-align-left' },
            { id: 'checkbox', label: 'Casilla', icon: 'fa-check-square' },
            { id: 'file', label: 'Archivo', icon: 'fa-file-upload' },
            { id: 'label', label: 'Etiqueta', icon: 'fa-tag' }
        ];
        
        // Parse initial data if provided
        if (initialData) {
            try {
                const parsed = typeof initialData === 'string' ? JSON.parse(initialData) : initialData;
                const parsedFields = (parsed && Array.isArray(parsed.fields))
                    ? parsed.fields
                    : (Array.isArray(parsed) ? parsed : []);
                if (parsedFields.length > 0) {
                    this.fields = parsedFields.map((field, index) => this.normalizeField(field, index));
                    this.nextId = this.getNextIdFromFields();
                }
            } catch (e) {
                console.error('Error parsing initial data:', e);
            }
        }
        
        // Parse initial pages if provided (for edit mode with existing pagination data)
        const initialPagesData = this.container ? this.container.dataset.initialPages : null;
        if (initialPagesData) {
            try {
                const parsedPages = JSON.parse(initialPagesData);
                if (parsedPages && Array.isArray(parsedPages) && parsedPages.length > 0) {
                    this.pages = parsedPages;
                    this.currentPage = parsedPages[0].id;
                    this.nextPageId = Math.max(...parsedPages.map(p => p.id)) + 1;
                }
            } catch (e) {
                console.error('Error parsing initial pages:', e);
            }
        }
        
        // Check if pagination is enabled
        this.checkPaginationEnabled();
        
        this.render();
        // Serialize initial state so hidden inputs are always populated on page load
        this.updateJSON();
    }
    
    escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    normalizeField(field, index) {
        const normalized = {
            id: (field && field.id) ? String(field.id) : `campo_${index + 1}`,
            type: (field && field.type) ? field.type : 'text',
            label: (field && field.label) ? field.label : 'Campo',
            required: Boolean(field && field.required)
        };

        if (normalized.type === 'select') {
            normalized.options = Array.isArray(field && field.options) ? field.options : ['Opción 1', 'Opción 2'];
        }

        const conditional = field && field.conditional ? field.conditional : null;
        normalized.conditional = {
            enabled: Boolean(conditional && conditional.enabled),
            parentFieldId: conditional && conditional.parentFieldId ? String(conditional.parentFieldId) : '',
            value: conditional && typeof conditional.value !== 'undefined' ? String(conditional.value) : ''
        };

        return normalized;
    }

    getNextIdFromFields() {
        let maxId = 0;
        this.fields.forEach(field => {
            const match = String(field.id || '').match(/^campo_(\d+)$/);
            if (match) {
                maxId = Math.max(maxId, parseInt(match[1], 10));
            }
        });
        return maxId + 1;
    }

    injectStyles() {
        if (document.getElementById('form-builder-styles')) return;
        const style = document.createElement('style');
        style.id = 'form-builder-styles';
        style.textContent = `
            .field-drop-zone {
                height: 4px;
                margin: 2px 0;
                border-radius: 4px;
                position: relative;
                transition: all 0.15s ease;
            }
            .field-drop-zone.dragging-active {
                height: 14px;
                background: rgba(59, 130, 246, 0.05);
                border: 1px dashed #93c5fd;
                border-radius: 4px;
            }
            .field-drop-zone.drag-over {
                height: 36px !important;
                background: rgba(59, 130, 246, 0.15) !important;
                border: 2px solid #3b82f6 !important;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .field-drop-zone.drag-over::after {
                content: '↓ Insertar aquí';
                font-size: 11px;
                color: #2563eb;
                font-weight: 600;
                pointer-events: none;
            }
            .field-item.field-dragging {
                opacity: 0.45;
                border: 2px dashed #6b7280 !important;
            }
        `;
        document.head.appendChild(style);
    }

    checkPaginationEnabled() {
        const paginationCheckbox = document.getElementById('pagination_enabled');
        if (paginationCheckbox) {
            this.paginationEnabled = paginationCheckbox.checked;
            paginationCheckbox.addEventListener('change', (e) => {
                this.paginationEnabled = e.target.checked;

                // When enabling pagination, assign all unassigned fields to page 1
                // so existing fields are not lost
                if (this.paginationEnabled) {
                    const page1 = this.pages.find(p => p.id === 1) || this.pages[0];
                    if (page1) {
                        this.fields.forEach(field => {
                            const isAssigned = this.pages.some(p => p.fieldIds.includes(field.id));
                            if (!isAssigned) {
                                page1.fieldIds.push(field.id);
                            }
                        });
                    }
                }

                this.render();
                this.updateJSON();
            });
        }
    }
    
    render() {
        this.injectStyles();
        this.container.innerHTML = `
            <div class="form-builder">
                <!-- Field Types Palette -->
                <div class="field-palette bg-gray-50 p-4 rounded-lg border-2 border-gray-200 mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-tools mr-2"></i>Tipos de Campo
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        ${this.fieldTypes.map(type => `
                            <button type="button" 
                                    class="field-type-btn bg-white border-2 border-gray-300 rounded-lg p-3 hover:border-blue-500 hover:bg-blue-50 transition cursor-move text-center"
                                    data-field-type="${type.id}"
                                    draggable="true">
                                <i class="fas ${type.icon} text-xl text-gray-600 mb-1"></i>
                                <div class="text-xs font-medium text-gray-700">${type.label}</div>
                            </button>
                        `).join('')}
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle"></i> Arrastra un tipo de campo hacia el área de construcción
                    </p>
                </div>
                
                <!-- Page Management (shown only when pagination is enabled) -->
                ${this.paginationEnabled ? `
                <div class="page-management bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-blue-900">
                            <i class="fas fa-layer-group mr-2"></i>Gestión de Páginas
                        </h3>
                        <button type="button" id="add-page-btn" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                            <i class="fas fa-plus mr-1"></i>Agregar Página
                        </button>
                    </div>
                    <div class="flex space-x-2 overflow-x-auto pb-2" id="pages-tabs">
                        ${this.pages.map(page => `
                            <button type="button" 
                                    class="page-tab px-4 py-2 rounded text-sm whitespace-nowrap ${page.id === this.currentPage ? 'bg-blue-600 text-white' : 'bg-white text-blue-800 border border-blue-300'}"
                                    data-page-id="${page.id}"
                                    aria-label="${page.name} con ${page.fieldIds.length} campo${page.fieldIds.length !== 1 ? 's' : ''}"
                                    ${page.id === this.currentPage ? 'aria-current="page"' : ''}>
                                <i class="fas fa-file-alt mr-1"></i>${page.name} (${page.fieldIds.length})
                            </button>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- Form Fields Area -->
                <div class="fields-area bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[300px]"
                     id="fields-drop-area">
                    ${this.paginationEnabled ? `
                        <div class="text-sm text-blue-700 mb-3 flex items-center justify-between">
                            <span><i class="fas fa-info-circle mr-1"></i>${this.getPageInfoMessage()}</span>
                            ${this.getPageNavigationButton()}
                        </div>
                    ` : ''}
                    <div class="fields-list" id="fields-list">
                        ${this.fields.length === 0 ? `
                            <div class="empty-state text-center py-12 text-gray-400">
                                <i class="fas fa-arrow-up text-4xl mb-3"></i>
                                <p class="text-sm">Arrastra campos aquí para construir tu formulario</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        this.attachEventListeners();
        this.renderFields();
    }
    
    getPageName(pageId) {
        const page = this.pages.find(p => p.id === pageId);
        return page ? page.name : 'Página desconocida';
    }

    getPageInfoMessage() {
        if (this.currentPage === 0) {
            return 'Mostrando <strong>todos los campos</strong> — usa el menú desplegable para mover campos entre páginas';
        }
        return `Mostrando campos de: <strong>${this.getPageName(this.currentPage)}</strong>`;
    }

    getPageNavigationButton() {
        if (this.currentPage !== 0) {
            return `<button type="button" id="show-all-fields-btn" class="text-blue-600 hover:underline text-xs">
                                Ver todos los campos
                            </button>`;
        }
        return `<button type="button" id="hide-all-fields-btn" class="text-blue-600 hover:underline text-xs">
                                Volver a página actual
                            </button>`;
    }

    getAvailableParentSelectFields(index) {
        const currentField = this.fields[index];
        return this.fields.filter((field) => field.type === 'select' && (!currentField || field.id !== currentField.id));
    }
    
    addPage() {
        const newPage = {
            id: this.nextPageId++,
            name: `Página ${this.pages.length + 1}`,
            fieldIds: []
        };
        this.pages.push(newPage);
        this.currentPage = newPage.id;
        this.render();
        this.updateJSON();
    }
    
    isShowingAllPages() {
        return !this.paginationEnabled || this.currentPage === 0;
    }
    
    switchPage(pageId) {
        this.currentPage = pageId;
        this.renderFields();
        // Update tabs styling and aria attributes
        document.querySelectorAll('.page-tab').forEach(tab => {
            const tabPageId = parseInt(tab.dataset.pageId);
            if (tabPageId === pageId) {
                tab.className = 'page-tab px-4 py-2 rounded text-sm whitespace-nowrap bg-blue-600 text-white';
                tab.setAttribute('aria-current', 'page');
            } else {
                tab.className = 'page-tab px-4 py-2 rounded text-sm whitespace-nowrap bg-white text-blue-800 border border-blue-300';
                tab.removeAttribute('aria-current');
            }
        });
    }
    
    attachEventListeners() {
        // Drag start for field types
        const fieldTypeBtns = this.container.querySelectorAll('.field-type-btn');
        fieldTypeBtns.forEach(btn => {
            btn.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('fieldType', btn.dataset.fieldType);
                btn.style.opacity = '0.5';
                this.draggedFieldType = btn.dataset.fieldType;
                this.draggedFieldIndex = null;
                // Activate drop zones so user can see where to insert
                document.querySelectorAll('.field-drop-zone').forEach(z => z.classList.add('dragging-active'));
            });
            
            btn.addEventListener('dragend', (e) => {
                btn.style.opacity = '1';
                this.draggedFieldType = null;
                document.querySelectorAll('.field-drop-zone').forEach(z => z.classList.remove('dragging-active', 'drag-over'));
            });
            
            // Click to add field (mobile friendly)
            btn.addEventListener('click', (e) => {
                this.addField(btn.dataset.fieldType);
            });
        });
        
        // Drop area events — fallback when not dropped on a specific drop zone
        const dropArea = document.getElementById('fields-drop-area');
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            // Only highlight the whole area if no drop zone is already highlighted
            if (!document.querySelector('.field-drop-zone.drag-over')) {
                dropArea.classList.add('border-blue-500', 'bg-blue-50');
            }
        });
        
        dropArea.addEventListener('dragleave', (e) => {
            // Only remove highlight if leaving the drop area entirely
            if (!dropArea.contains(e.relatedTarget)) {
                dropArea.classList.remove('border-blue-500', 'bg-blue-50');
            }
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('border-blue-500', 'bg-blue-50');
            
            const fieldType = e.dataTransfer.getData('fieldType');
            if (fieldType) {
                // Only add to end if this drop was NOT on a specific drop zone
                if (!e.target.classList.contains('field-drop-zone')) {
                    this.addField(fieldType);
                }
            }
        });
        
        // Page management buttons
        if (this.paginationEnabled) {
            const addPageBtn = document.getElementById('add-page-btn');
            if (addPageBtn) {
                addPageBtn.addEventListener('click', () => this.addPage());
            }
            
            const pageTabs = document.querySelectorAll('.page-tab');
            pageTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const pageId = parseInt(tab.dataset.pageId);
                    this.switchPage(pageId);
                });
            });
            
            const showAllBtn = document.getElementById('show-all-fields-btn');
            if (showAllBtn) {
                showAllBtn.addEventListener('click', () => {
                    this.currentPage = 0; // Use 0 to indicate "show all"
                    this.render();
                });
            }

            const hideAllBtn = document.getElementById('hide-all-fields-btn');
            if (hideAllBtn) {
                hideAllBtn.addEventListener('click', () => {
                    this.currentPage = this.pages[0] ? this.pages[0].id : 1;
                    this.render();
                });
            }
        }
    }
    
    addField(type, insertIndex = null) {
        const fieldType = this.fieldTypes.find(ft => ft.id === type);
        if (!fieldType) return;

        let nextFieldId = `campo_${this.nextId++}`;
        while (this.fields.some(field => field.id === nextFieldId)) {
            nextFieldId = `campo_${this.nextId++}`;
        }
        
        const newField = {
            id: nextFieldId,
            type: type,
            label: type === 'label' ? 'Nueva Sección' : fieldType.label,
            required: false,
            conditional: {
                enabled: false,
                parentFieldId: '',
                value: ''
            }
        };
        
        // Add options for select fields
        if (type === 'select') {
            newField.options = ['Opción 1', 'Opción 2'];
        }
        
        // Insert at specific position or at the end
        if (insertIndex !== null && insertIndex >= 0 && insertIndex <= this.fields.length) {
            this.fields.splice(insertIndex, 0, newField);
        } else {
            this.fields.push(newField);
        }
        
        // If pagination is enabled, add field to current page
        if (this.paginationEnabled && this.currentPage) {
            const page = this.pages.find(p => p.id === this.currentPage);
            if (page) {
                page.fieldIds.push(newField.id);
            }
        }
        
        this.renderFields();
        this.updateJSON();
    }
    
    moveField(fromIndex, toIndex) {
        if (fromIndex === toIndex) return;
        
        const field = this.fields.splice(fromIndex, 1)[0];
        // When moving a field down (toIndex > fromIndex), the removal shifts all subsequent
        // indices down by 1, so we must decrement toIndex to compensate.
        const adjustedIndex = toIndex > fromIndex ? toIndex - 1 : toIndex;
        this.fields.splice(adjustedIndex, 0, field);
        
        this.renderFields();
        this.updateJSON();
    }
    
    renderFields() {
        const fieldsList = document.getElementById('fields-list');
        if (!fieldsList) return;
        
        // Determine which fields to show
        let fieldsToShow = this.fields;
        if (!this.isShowingAllPages()) {
            const page = this.pages.find(p => p.id === this.currentPage);
            if (page) {
                fieldsToShow = this.fields.filter(f => page.fieldIds.includes(f.id));
            }
        }
        
        if (fieldsToShow.length === 0) {
            fieldsList.innerHTML = `
                <div class="field-drop-zone" data-insert-index="0"></div>
                <div class="empty-state text-center py-12 text-gray-400">
                    <i class="fas fa-arrow-up text-4xl mb-3"></i>
                    <p class="text-sm">${!this.isShowingAllPages() ? 'Arrastra campos aquí para agregarlos a esta página' : 'Arrastra campos aquí para construir tu formulario'}</p>
                </div>
            `;
            this.attachFieldEventListeners();
            return;
        }
        
        // Build HTML with drop zones between each field for precise insertion
        let html = `<div class="field-drop-zone" data-insert-index="0"></div>`;
        
        fieldsToShow.forEach((field) => {
            const index = this.fields.indexOf(field);
            const isLabel = field.type === 'label';
            html += `
            <div class="field-item ${isLabel ? 'bg-blue-50 border border-blue-300' : 'bg-gray-50 border border-gray-300'} rounded-lg p-4 mb-0" data-index="${index}" draggable="true">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center flex-1">
                        <i class="fas fa-grip-vertical text-gray-400 mr-3 cursor-move field-drag-handle" title="Arrastrar para reordenar"></i>
                        <div>
                            <div class="font-semibold ${isLabel ? 'text-blue-800' : 'text-gray-800'}">${isLabel ? '<i class="fas fa-tag mr-1 text-blue-500"></i>' : ''}${this.escapeHtml(field.label)}</div>
                            <div class="text-xs text-gray-500">ID: ${this.escapeHtml(field.id)} | Tipo: ${this.escapeHtml(field.type)}${isLabel ? ' — Separador de sección' : ''}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        ${this.paginationEnabled ? `
                            <select class="field-page-select text-xs border border-gray-300 rounded px-2 py-1" 
                                    data-field-id="${field.id}"
                                    aria-label="Asignar campo a página">
                                ${this.pages.map(page => `
                                    <option value="${page.id}" ${page.fieldIds.includes(field.id) ? 'selected' : ''}>
                                        ${page.name}
                                    </option>
                                `).join('')}
                            </select>
                        ` : ''}
                        <button type="button" class="btn-delete-field text-red-600 hover:text-red-800" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">${isLabel ? 'Texto de la etiqueta' : 'Nombre de campo'}</label>
                        <input type="text" value="${this.escapeHtml(field.label)}" 
                               class="field-label-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                               data-index="${index}">
                    </div>
                    <div style="display:none">
                        <label class="block text-xs text-gray-600 mb-1">ID del campo</label>
                        <input type="text" value="${field.id}" 
                               class="field-id-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                               data-index="${index}">
                    </div>
                    ${field.type === 'select' ? `
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-600 mb-1">Opciones (separadas por coma)</label>
                            <input type="text" value="${(field.options || []).join(', ')}" 
                                   class="field-options-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                   data-index="${index}">
                        </div>
                    ` : ''}
                    ${!isLabel ? `
                    <div class="col-span-2 flex flex-wrap items-center gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" ${field.required ? 'checked' : ''} 
                                   class="field-required-input mr-2"
                                   data-index="${index}">
                            <span class="text-xs text-gray-700">Campo obligatorio</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" ${field.conditional && field.conditional.enabled ? 'checked' : ''} 
                                   class="field-conditional-enabled-input mr-2"
                                   data-index="${index}">
                            <span class="text-xs text-gray-700">Pregunta condicional</span>
                        </label>
                    </div>
                    <div class="col-span-2 ${field.conditional && field.conditional.enabled ? '' : 'hidden'}" id="conditional-config-${index}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Mostrar cuando</label>
                                <select class="field-conditional-parent-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                        data-index="${index}">
                                    <option value="">Seleccione campo select...</option>
                                    ${this.getAvailableParentSelectFields(index).map(parent => `
                                        <option value="${this.escapeHtml(parent.id)}" ${field.conditional && field.conditional.parentFieldId === parent.id ? 'selected' : ''}>
                                            ${this.escapeHtml(parent.label)}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Si responde</label>
                                <input type="text"
                                       value="${this.escapeHtml(field.conditional ? (field.conditional.value || '') : '')}"
                                       class="field-conditional-value-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                       data-index="${index}"
                                       placeholder="Ej: Sí">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">La pregunta se mostrará solo cuando el campo select elegido tenga ese valor.</p>
                    </div>
                    ` : `
                    <div class="col-span-2">
                        <div class="border-t-2 border-blue-400 mt-1 pt-2">
                            <span class="text-xs text-blue-600 font-semibold uppercase tracking-wide">${this.escapeHtml(field.label)}</span>
                        </div>
                    </div>
                    `}
                </div>
            </div>
            <div class="field-drop-zone" data-insert-index="${index + 1}"></div>
            `;
        });
        
        fieldsList.innerHTML = html;
        
        // Attach event listeners after rendering
        this.attachFieldEventListeners();
    }
    
    attachFieldEventListeners() {
        // Label inputs
        document.querySelectorAll('.field-label-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.updateFieldProperty(index, 'label', e.target.value);
            });
        });
        
        // ID inputs
        document.querySelectorAll('.field-id-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const oldId = this.fields[index].id;
                const newId = e.target.value.trim();
                
                // Validate for duplicate IDs
                const isDuplicate = this.fields.some((field, idx) => 
                    idx !== index && field.id === newId
                );
                
                if (isDuplicate) {
                    alert('Ya existe un campo con ese ID. Por favor, elige un ID único.');
                    e.target.value = oldId; // Reset to old value
                    return;
                }
                
                if (!newId) {
                    alert('El ID no puede estar vacío.');
                    e.target.value = oldId; // Reset to old value
                    return;
                }
                
                // Update field ID in pages
                if (this.paginationEnabled) {
                    this.pages.forEach(page => {
                        const idIndex = page.fieldIds.indexOf(oldId);
                        if (idIndex !== -1) {
                            page.fieldIds[idIndex] = newId;
                        }
                    });
                }
                
                this.updateFieldProperty(index, 'id', newId);
            });
        });
        
        // Options inputs
        document.querySelectorAll('.field-options-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const options = e.target.value.split(',').map(o => o.trim());
                this.updateFieldProperty(index, 'options', options);
            });
        });
        
        // Required checkboxes
        document.querySelectorAll('.field-required-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.updateFieldProperty(index, 'required', e.target.checked);
            });
        });

        document.querySelectorAll('.field-conditional-enabled-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const field = this.fields[index];
                if (!field) return;

                if (!field.conditional) {
                    field.conditional = { enabled: false, parentFieldId: '', value: '' };
                }
                field.conditional.enabled = e.target.checked;
                if (!field.conditional.enabled) {
                    field.conditional.parentFieldId = '';
                    field.conditional.value = '';
                }

                this.renderFields();
                this.updateJSON();
            });
        });

        document.querySelectorAll('.field-conditional-parent-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const field = this.fields[index];
                if (!field) return;
                if (!field.conditional) {
                    field.conditional = { enabled: true, parentFieldId: '', value: '' };
                }
                field.conditional.enabled = true;
                field.conditional.parentFieldId = e.target.value.trim();
                this.updateJSON();
            });
        });

        document.querySelectorAll('.field-conditional-value-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const field = this.fields[index];
                if (!field) return;
                if (!field.conditional) {
                    field.conditional = { enabled: true, parentFieldId: '', value: '' };
                }
                field.conditional.enabled = true;
                field.conditional.value = e.target.value.trim();
                this.updateJSON();
            });
        });
        
        // Page assignment dropdown
        document.querySelectorAll('.field-page-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const fieldId = e.target.dataset.fieldId;
                const newPageId = parseInt(e.target.value);
                
                // Remove field from all pages
                this.pages.forEach(page => {
                    page.fieldIds = page.fieldIds.filter(id => id !== fieldId);
                });
                
                // Add field to selected page
                const targetPage = this.pages.find(p => p.id === newPageId);
                if (targetPage && !targetPage.fieldIds.includes(fieldId)) {
                    targetPage.fieldIds.push(fieldId);
                }
                
                this.updateJSON();
                // Update page tabs to show field counts
                this.render();
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.btn-delete-field').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                this.deleteField(index);
            });
        });
        
        // Field item drag-to-reorder: start drag on existing field
        document.querySelectorAll('.field-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                // Ignore if user is interacting with an input/select inside the field
                if (['INPUT', 'SELECT', 'BUTTON', 'TEXTAREA'].includes(e.target.tagName)) {
                    e.preventDefault();
                    return;
                }
                const index = parseInt(item.dataset.index);
                e.dataTransfer.setData('existingFieldIndex', index);
                e.dataTransfer.effectAllowed = 'move';
                this.draggedFieldIndex = index;
                this.draggedFieldType = null;
                item.classList.add('field-dragging');
                // Activate drop zones
                document.querySelectorAll('.field-drop-zone').forEach(z => z.classList.add('dragging-active'));
            });
            
            item.addEventListener('dragend', (e) => {
                item.classList.remove('field-dragging');
                this.draggedFieldIndex = null;
                document.querySelectorAll('.field-drop-zone').forEach(z => z.classList.remove('dragging-active', 'drag-over'));
            });
        });
        
        // Drop zone events: highlight and handle drops for both new fields and reordering
        document.querySelectorAll('.field-drop-zone').forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'move';
                // Remove highlight from all other zones, add to this one
                document.querySelectorAll('.field-drop-zone').forEach(z => z.classList.remove('drag-over'));
                zone.classList.add('drag-over');
                // Remove the whole-area highlight when a specific zone is targeted
                const dropArea = document.getElementById('fields-drop-area');
                if (dropArea) dropArea.classList.remove('border-blue-500', 'bg-blue-50');
            });
            
            zone.addEventListener('dragleave', (e) => {
                // Only remove if actually leaving this zone (not entering a child element)
                if (!zone.contains(e.relatedTarget)) {
                    zone.classList.remove('drag-over');
                }
            });
            
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('drag-over', 'dragging-active');
                document.querySelectorAll('.field-drop-zone').forEach(z => z.classList.remove('dragging-active', 'drag-over'));
                
                const insertIndex = parseInt(zone.dataset.insertIndex);
                const fieldType = e.dataTransfer.getData('fieldType');
                const existingIndexStr = e.dataTransfer.getData('existingFieldIndex');
                
                if (fieldType) {
                    // New field from palette — insert at specific position
                    this.addField(fieldType, insertIndex);
                } else if (existingIndexStr !== '') {
                    const existingIndex = parseInt(existingIndexStr);
                    if (!isNaN(existingIndex)) {
                        this.moveField(existingIndex, insertIndex);
                    }
                }
            });
        });
    }
    
    updateFieldProperty(index, property, value) {
        if (this.fields[index]) {
            this.fields[index][property] = value;
            this.updateJSON();
        }
    }
    
    deleteField(index) {
        if (confirm('¿Estás seguro de eliminar este campo?')) {
            const fieldId = this.fields[index].id;
            
            // Remove from pages if pagination enabled
            if (this.paginationEnabled) {
                this.pages.forEach(page => {
                    page.fieldIds = page.fieldIds.filter(id => id !== fieldId);
                });
            }
            
            this.fields.splice(index, 1);
            this.renderFields();
            this.updateJSON();
        }
    }
    
    updateJSON() {
        this.fields.forEach(field => {
            if (!field.conditional) {
                return;
            }
            if (!field.conditional.enabled) {
                field.conditional.parentFieldId = '';
                field.conditional.value = '';
                return;
            }

            const parentFieldExists = this.fields.some(parent => parent.id === field.conditional.parentFieldId && parent.type === 'select');
            if (!parentFieldExists || !field.conditional.parentFieldId) {
                field.conditional.enabled = false;
                field.conditional.parentFieldId = '';
                field.conditional.value = '';
                return;
            }

            field.conditional.value = String(field.conditional.value || '').trim();
        });

        const jsonOutput = {
            fields: this.fields
        };
        
        // Update hidden field with JSON
        const hiddenField = document.getElementById('fields_json_hidden');
        if (hiddenField) {
            hiddenField.value = JSON.stringify(jsonOutput);
        }
        
        // Also save pages JSON to a separate hidden field
        if (this.paginationEnabled) {
            let pagesInput = document.getElementById('pages_json_hidden');
            if (!pagesInput) {
                pagesInput = document.createElement('input');
                pagesInput.type = 'hidden';
                pagesInput.id = 'pages_json_hidden';
                pagesInput.name = 'pages_json';
                hiddenField.parentNode.appendChild(pagesInput);
            }
            pagesInput.value = JSON.stringify(this.pages);
        }
        
        // Dispatch event for other components
        const event = new CustomEvent('formbuilder:update', { detail: jsonOutput });
        document.dispatchEvent(event);
    }
    
    getJSON() {
        return JSON.stringify({ fields: this.fields }, null, 2);
    }
    
    getData() {
        return { fields: this.fields };
    }
}

// Global instance
let formBuilder;

// Initialize form builder when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const builderContainer = document.getElementById('form-builder-container');
    if (builderContainer) {
        const initialData = builderContainer.dataset.initialData;
        formBuilder = new FormBuilder('form-builder-container', initialData);
    }
});
