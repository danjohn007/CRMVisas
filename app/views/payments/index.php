<?php 
$title = 'Pagos - CRM Visas';
$pageTitle = 'Gestión de Pagos';
ob_start(); 
?>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <form method="GET" action="<?php echo BASE_URL; ?>/public/index.php" class="flex flex-wrap gap-4">
        <input type="hidden" name="page" value="payments">
        
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Todos los estados</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completado</option>
            <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Fallido</option>
            <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Reembolsado</option>
        </select>
        
        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            <i class="fas fa-filter mr-2"></i>Filtrar
        </button>
        
        <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=create" 
           class="ml-auto px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
            <i class="fas fa-plus mr-2"></i>Registrar Pago
        </a>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solicitud</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-money-bill text-4xl text-gray-300 mb-2"></i>
                            <p>No se encontraron pagos</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-semibold text-blue-600">
                                    <?php echo htmlspecialchars($payment['payment_reference']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo htmlspecialchars($payment['request_number']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-lg font-bold text-green-600">
                                    $<?php echo number_format($payment['amount'], 2); ?>
                                </span>
                                <span class="text-xs text-gray-500"><?php echo $payment['currency']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm capitalize">
                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'pending' => 'bg-yellow-200 text-yellow-800',
                                    'processing' => 'bg-blue-200 text-blue-800',
                                    'completed' => 'bg-green-200 text-green-800',
                                    'failed' => 'bg-red-200 text-red-800',
                                    'refunded' => 'bg-purple-200 text-purple-800',
                                    'cancelled' => 'bg-gray-200 text-gray-800'
                                ];
                                $statusClass = $statusColors[$payment['payment_status']] ?? 'bg-gray-200 text-gray-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo $payment['payment_date'] ? date('d/m/Y', strtotime($payment['payment_date'])) : 'Pendiente'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=view&id=<?php echo $payment['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
