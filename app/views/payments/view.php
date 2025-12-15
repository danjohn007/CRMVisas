<?php 
$title = 'Ver Pago - CRM Visas';
$pageTitle = 'Detalle del Pago';
ob_start(); 
?>

<div class="max-w-4xl mx-auto">
    <!-- Payment Header -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    Pago <?php echo htmlspecialchars($payment['payment_reference']); ?>
                </h2>
                <p class="text-gray-600">
                    <i class="fas fa-file-alt mr-2"></i>
                    <span class="font-semibold">Solicitud:</span> 
                    <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=view&id=<?php echo $payment['request_id']; ?>" 
                       class="text-blue-600 hover:underline">
                        <?php echo htmlspecialchars($payment['request_number']); ?>
                    </a>
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-user mr-2"></i>
                    <span class="font-semibold">Cliente:</span> 
                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-cog mr-2"></i>
                    <span class="font-semibold">Servicio:</span> 
                    <?php echo htmlspecialchars($payment['service_name']); ?>
                </p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium
                <?php 
                $statusClasses = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'failed' => 'bg-red-100 text-red-800',
                    'refunded' => 'bg-purple-100 text-purple-800'
                ];
                echo $statusClasses[$payment['payment_status']] ?? 'bg-gray-100 text-gray-800';
                ?>">
                <?php echo ucfirst($payment['payment_status']); ?>
            </span>
        </div>

        <div class="border-t pt-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <p class="text-4xl font-bold text-green-600 mb-1">
                            $<?php echo number_format($payment['amount'], 2); ?> <?php echo $payment['currency']; ?>
                        </p>
                        <p class="text-sm text-gray-600">Monto del pago</p>
                    </div>
                    
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                            <span class="font-semibold">Método:</span> 
                            <?php 
                            $methods = [
                                'cash' => 'Efectivo',
                                'credit_card' => 'Tarjeta de Crédito',
                                'debit_card' => 'Tarjeta de Débito',
                                'bank_transfer' => 'Transferencia Bancaria',
                                'paypal' => 'PayPal',
                                'other' => 'Otro'
                            ];
                            echo $methods[$payment['payment_method']] ?? ucfirst($payment['payment_method']);
                            ?>
                        </p>
                        
                        <?php if ($payment['transaction_id']): ?>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-hashtag mr-2 text-blue-600"></i>
                                <span class="font-semibold">ID Transacción:</span> 
                                <?php echo htmlspecialchars($payment['transaction_id']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-calendar-plus mr-2 text-blue-600"></i>
                        <span class="font-semibold">Fecha Creación:</span> 
                        <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?>
                    </p>
                    
                    <?php if ($payment['payment_date']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-calendar-check mr-2 text-green-600"></i>
                            <span class="font-semibold">Fecha Pago:</span> 
                            <?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($payment['due_date']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-calendar-times mr-2 text-red-600"></i>
                            <span class="font-semibold">Fecha Vencimiento:</span> 
                            <?php echo date('d/m/Y', strtotime($payment['due_date'])); ?>
                            <?php 
                            $dueDate = strtotime($payment['due_date']);
                            $today = strtotime('today');
                            if ($payment['payment_status'] !== 'completed' && $dueDate < $today): 
                            ?>
                                <span class="text-xs text-red-600 font-semibold ml-2">Vencido</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($payment['notes']): ?>
            <div class="border-t pt-4">
                <h4 class="font-semibold text-gray-700 mb-2">Notas:</h4>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Information Card -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-info-circle mr-2"></i>Información Adicional
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600 mb-2">
                    <span class="font-semibold">Email del Cliente:</span><br>
                    <a href="mailto:<?php echo htmlspecialchars($payment['email']); ?>" 
                       class="text-blue-600 hover:underline">
                        <?php echo htmlspecialchars($payment['email']); ?>
                    </a>
                </p>
            </div>
            
            <div>
                <p class="text-sm text-gray-600 mb-2">
                    <span class="font-semibold">Estado del Pago:</span><br>
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-sm font-medium
                        <?php 
                        $statusClasses = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'failed' => 'bg-red-100 text-red-800',
                            'refunded' => 'bg-purple-100 text-purple-800'
                        ];
                        echo $statusClasses[$payment['payment_status']] ?? 'bg-gray-100 text-gray-800';
                        ?>">
                        <?php 
                        $statuses = [
                            'pending' => 'Pendiente',
                            'completed' => 'Completado',
                            'failed' => 'Fallido',
                            'refunded' => 'Reembolsado'
                        ];
                        echo $statuses[$payment['payment_status']] ?? ucfirst($payment['payment_status']);
                        ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <!-- Print/Export Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-file-export mr-2"></i>Acciones
        </h3>
        <div class="flex space-x-3">
            <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=receipt&id=<?php echo $payment['id']; ?>" 
               target="_blank"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition inline-block"
               aria-label="Imprimir recibo de pago">
                <i class="fas fa-print mr-2"></i>Imprimir Recibo
            </a>
            <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=pdf&id=<?php echo $payment['id']; ?>" 
               target="_blank"
               class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded transition inline-block"
               title="Abrir recibo y usar 'Imprimir como PDF' en el navegador"
               aria-label="Exportar recibo a PDF usando la función imprimir del navegador">
                <i class="fas fa-file-pdf mr-2"></i>Exportar PDF
            </a>
        </div>
        <p class="text-xs text-gray-500 mt-2">
            <i class="fas fa-info-circle mr-1"></i>
            Para guardar como PDF, haga clic en "Exportar PDF" y use la opción "Imprimir" del navegador seleccionando "Guardar como PDF"
        </p>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between space-x-4">
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments" 
           class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Volver a Pagos
        </a>
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=requests&action=view&id=<?php echo $payment['request_id']; ?>" 
           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-file-alt mr-2"></i>Ver Solicitud
        </a>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
