<?php 
$title = 'Registrar Pago - CRM Visas';
$pageTitle = 'Nuevo Pago';
ob_start(); 
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="<?php echo BASE_URL; ?>/public/index.php?page=payments&action=create">
            <!-- Request Selection -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-file-alt mr-2"></i>Solicitud
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Solicitud *</label>
                    <select name="request_id" required id="request_select" onchange="updateAmount()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Seleccionar Solicitud --</option>
                        <?php foreach ($requests as $req): ?>
                            <option value="<?php echo $req['id']; ?>" 
                                    data-price="<?php echo $req['base_price']; ?>"
                                    <?php echo ($request && $req['id'] == $request['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($req['request_number'] . ' - ' . $req['first_name'] . ' ' . $req['last_name'] . ' - ' . $req['service_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-dollar-sign mr-2"></i>Información del Pago
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monto *</label>
                        <input type="number" name="amount" id="amount" required step="0.01" min="0"
                               value="<?php echo $request['base_price'] ?? ''; ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Moneda *</label>
                        <select name="currency"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="MXN" selected>MXN</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago *</label>
                        <select name="payment_method"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado del Pago *</label>
                        <select name="payment_status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending">Pendiente</option>
                            <option value="processing">Procesando</option>
                            <option value="completed">Completado</option>
                            <option value="failed">Fallido</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Pago</label>
                        <input type="datetime-local" name="payment_date"
                               value="<?php echo date('Y-m-d\TH:i'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Vencimiento</label>
                        <input type="date" name="due_date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">ID de Transacción</label>
                        <input type="text" name="transaction_id"
                               placeholder="Número de referencia o ID de transacción"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                <textarea name="notes" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <a href="<?php echo BASE_URL; ?>/public/index.php?page=payments" 
                   class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Registrar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateAmount() {
    const select = document.getElementById('request_select');
    const option = select.options[select.selectedIndex];
    const price = option.getAttribute('data-price');
    if (price) {
        document.getElementById('amount').value = price;
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
