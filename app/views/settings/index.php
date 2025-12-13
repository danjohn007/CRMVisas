<?php 
$title = 'Configuración del Sistema - CRM Visas';
$pageTitle = 'Configuración';
ob_start(); 
?>

<!-- Tabs -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="border-b">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
            <a href="?page=settings&tab=general" 
               class="<?php echo $tab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                General
            </a>
            <a href="?page=settings&tab=email" 
               class="<?php echo $tab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Email
            </a>
            <a href="?page=settings&tab=contact" 
               class="<?php echo $tab === 'contact' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Contacto
            </a>
            <a href="?page=settings&tab=theme" 
               class="<?php echo $tab === 'theme' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Tema
            </a>
            <a href="?page=settings&tab=payment" 
               class="<?php echo $tab === 'payment' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pagos
            </a>
        </nav>
    </div>
</div>

<!-- Settings Form -->
<form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=settings&action=save">
    <input type="hidden" name="tab" value="<?php echo $tab; ?>">
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <?php if (isset($settings[$tab])): ?>
            <div class="space-y-6">
                <?php foreach ($settings[$tab] as $setting): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo htmlspecialchars($setting['description'] ?? $setting['setting_key']); ?>
                        </label>
                        
                        <?php if ($setting['setting_type'] === 'textarea'): ?>
                            <textarea 
                                name="settings[<?php echo $setting['setting_key']; ?>]"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ><?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?></textarea>
                        
                        <?php elseif ($setting['setting_type'] === 'color'): ?>
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="color" 
                                    name="settings[<?php echo $setting['setting_key']; ?>]"
                                    value="<?php echo htmlspecialchars($setting['setting_value'] ?? '#3b82f6'); ?>"
                                    class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?></span>
                            </div>
                        
                        <?php elseif ($setting['setting_type'] === 'boolean'): ?>
                            <select 
                                name="settings[<?php echo $setting['setting_key']; ?>]"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Sí</option>
                                <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>No</option>
                            </select>
                        
                        <?php else: ?>
                            <input 
                                type="<?php echo $setting['setting_type'] === 'email' ? 'email' : 'text'; ?>" 
                                name="settings[<?php echo $setting['setting_key']; ?>]"
                                value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php endif; ?>
                        
                        <?php if ($setting['description']): ?>
                            <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($setting['setting_key']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">No hay configuraciones disponibles en esta sección</p>
        <?php endif; ?>
    </div>
    
    <div class="flex justify-end">
        <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
            <i class="fas fa-save mr-2"></i>Guardar Configuración
        </button>
    </div>
</form>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
