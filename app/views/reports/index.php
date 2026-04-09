<?php 
$title = 'Reportes - CRM Visas';
$pageTitle = 'Reportes y Estadísticas';
ob_start(); 
?>

<!-- Report Type Selection -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <div class="flex flex-wrap gap-4 items-center">
        <select id="reportType" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="dashboard" <?php echo $reportType === 'dashboard' ? 'selected' : ''; ?>>Dashboard General</option>
            <option value="requests" <?php echo $reportType === 'requests' ? 'selected' : ''; ?>>Reporte de Solicitudes</option>
            <option value="financial" <?php echo $reportType === 'financial' ? 'selected' : ''; ?>>Reporte Financiero</option>
            <option value="productivity" <?php echo $reportType === 'productivity' ? 'selected' : ''; ?>>Productividad</option>
        </select>
        
        <input type="date" id="startDate" value="<?php echo $startDate; ?>" class="px-4 py-2 border border-gray-300 rounded-lg">
        <input type="date" id="endDate" value="<?php echo $endDate; ?>" class="px-4 py-2 border border-gray-300 rounded-lg">
        
        <button onclick="loadReport()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            <i class="fas fa-sync-alt mr-2"></i>Generar Reporte
        </button>
        
        <button id="exportBtn" 
                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                aria-label="Exportar reporte a CSV"
                aria-describedby="exportWarning">
            <i class="fas fa-download mr-2"></i>Exportar
        </button>
    </div>
    
    <!-- Export warning message (hidden by default) -->
    <div id="exportWarning" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm hidden">
        <i class="fas fa-info-circle mr-2"></i>
        El reporte de Dashboard no se puede exportar. Por favor seleccione otro tipo de reporte (Solicitudes, Financiero o Productividad).
    </div>
</div>

<!-- Report Content -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php if ($reportType === 'dashboard'): ?>
        <!-- Requests by Status Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Solicitudes por Estado</h3>
            <canvas id="statusChart"></canvas>
        </div>

        <!-- Revenue Trend Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Tendencia de Ingresos</h3>
            <canvas id="revenueChart"></canvas>
        </div>
    <?php elseif ($reportType === 'productivity'): ?>
        <!-- Productivity Report -->
        <div class="col-span-full bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">Productividad por Asesor</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asesor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Solicitudes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completadas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasa de Éxito</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (is_array($data) && !empty($data)): ?>
                            <?php foreach ($data as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td class="px-6 py-4 capitalize"><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td class="px-6 py-4 text-center font-bold"><?php echo $row['total_requests']; ?></td>
                                    <td class="px-6 py-4 text-center text-green-600 font-bold"><?php echo $row['completed_requests']; ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <?php 
                                        $rate = $row['total_requests'] > 0 ? ($row['completed_requests'] / $row['total_requests'] * 100) : 0;
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $rate >= 80 ? 'bg-green-100 text-green-800' : ($rate >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo number_format($rate, 1); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay datos disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="col-span-full bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">Seleccione un tipo de reporte y haga clic en "Generar Reporte"</p>
        </div>
    <?php endif; ?>
</div>

<script>
function loadReport() {
    const type = document.getElementById('reportType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    window.location.href = `<?php echo BASE_URL; ?>/public/index.php?page=reports&type=${type}&start_date=${startDate}&end_date=${endDate}`;
}

function exportReport() {
    const type = document.getElementById('reportType').value;
    const exportWarning = document.getElementById('exportWarning');
    
    // Dashboard cannot be exported
    if (type === 'dashboard') {
        // Show warning message
        exportWarning.classList.remove('hidden');
        // Hide after 5 seconds
        setTimeout(() => {
            exportWarning.classList.add('hidden');
        }, 5000);
        return;
    }
    
    // Hide warning if visible
    exportWarning.classList.add('hidden');
    
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const format = 'csv';
    
    // Build export URL
    const url = `<?php echo BASE_URL; ?>/public/index.php?page=reports&action=export&type=${type}&start_date=${startDate}&end_date=${endDate}&format=${format}`;
    
    // Open in new window to trigger download
    window.location.href = url;
}

// Update export button state when report type changes
document.addEventListener('DOMContentLoaded', function() {
    const reportType = document.getElementById('reportType');
    const exportBtn = document.getElementById('exportBtn');
    const exportWarning = document.getElementById('exportWarning');
    
    function updateExportButton() {
        if (reportType.value === 'dashboard') {
            exportBtn.disabled = true;
            exportBtn.classList.add('opacity-50', 'cursor-not-allowed');
            exportBtn.classList.remove('hover:bg-green-700');
        } else {
            exportBtn.disabled = false;
            exportBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            exportBtn.classList.add('hover:bg-green-700');
            exportWarning.classList.add('hidden');
        }
    }
    
    // Add click handler for export button
    exportBtn.addEventListener('click', exportReport);
    
    reportType.addEventListener('change', updateExportButton);
    updateExportButton(); // Initialize on page load
});

<?php if ($reportType === 'dashboard' && isset($data['requestsByStatus'])): ?>
// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($data['requestsByStatus'], 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($data['requestsByStatus'], 'count')); ?>,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    }
});

// Revenue Chart
<?php if (isset($data['revenueTrend'])): ?>
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($data['revenueTrend'], 'date')); ?>,
        datasets: [{
            label: 'Ingresos',
            data: <?php echo json_encode(array_column($data['revenueTrend'], 'total')); ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
<?php endif; ?>
<?php endif; ?>
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
