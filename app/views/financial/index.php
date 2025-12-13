<?php 
$title = 'Módulo Financiero - CRM Visas';
$pageTitle = 'Gestión Financiera';
ob_start(); 
?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Ingresos</p>
                <p class="text-3xl font-bold text-green-600 mt-2">
                    $<?php echo number_format($totals['total_income'] ?? 0, 2); ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-arrow-up text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Egresos</p>
                <p class="text-3xl font-bold text-red-600 mt-2">
                    $<?php echo number_format($totals['total_expense'] ?? 0, 2); ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-arrow-down text-red-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Balance</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">
                    $<?php echo number_format(($totals['total_income'] ?? 0) - ($totals['total_expense'] ?? 0), 2); ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-balance-scale text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <form method="GET" action="<?php echo BASE_URL; ?>/public/index.php" class="flex flex-wrap gap-4">
        <input type="hidden" name="page" value="financial">
        
        <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">Todos los tipos</option>
            <option value="income" <?php echo $type === 'income' ? 'selected' : ''; ?>>Ingresos</option>
            <option value="expense" <?php echo $type === 'expense' ? 'selected' : ''; ?>>Egresos</option>
        </select>
        
        <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="px-4 py-2 border border-gray-300 rounded-lg">
        <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="px-4 py-2 border border-gray-300 rounded-lg">
        
        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            <i class="fas fa-filter mr-2"></i>Filtrar
        </button>
    </form>
</div>

<!-- Transactions Table -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <h3 class="text-lg font-semibold">Transacciones</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No hay transacciones en el período seleccionado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">
                                <?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $transaction['transaction_type'] === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $transaction['transaction_type'] === 'income' ? 'Ingreso' : 'Egreso'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo htmlspecialchars($transaction['category_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo htmlspecialchars($transaction['description']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold <?php echo $transaction['transaction_type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transaction['transaction_type'] === 'income' ? '+' : '-'; ?>
                                    $<?php echo number_format($transaction['amount'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                <?php echo htmlspecialchars($transaction['reference'] ?? '-'); ?>
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
