<?php
/**
 * Vista pública de solicitudes para asesoras
 * Muestra solicitudes en estado "Cita programada" donde la asesor puede
 * confirmar un día antes que la cita sigue vigente.
 */
$title = 'Confirmación de Citas - Solicitudes';
ob_start();
?>
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Confirmación de Citas</h2>
    <p class="text-gray-600">Revisa y confirma las citas del día siguiente</p>
</div>

<?php if (empty($appointmentApplications)): ?>
<div class="bg-white rounded-lg shadow p-8 text-center">
    <i class="fas fa-calendar-check text-4xl text-green-400 mb-4"></i>
    <p class="text-gray-500">No hay citas pendientes de confirmación para mañana</p>
</div>
<?php else: ?>
<div class="space-y-4">
    <?php foreach ($appointmentApplications as $app): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="font-mono font-bold text-primary text-lg"><?= htmlspecialchars($app['folio']) ?></p>
                <p class="text-gray-600 text-sm"><?= htmlspecialchars($app['form_name'] ?? '-') ?></p>
                <?php if (!empty($app['appointment_date'])): ?>
                <p class="text-blue-700 text-sm font-semibold mt-1">
                    <i class="fas fa-calendar mr-1"></i>
                    Cita: <?= date('d/m/Y H:i', strtotime($app['appointment_date'])) ?>
                </p>
                <?php endif; ?>
                <p class="text-gray-500 text-xs mt-1">Creado por: <?= htmlspecialchars($app['creator_name']) ?></p>
            </div>
            <div class="flex items-center gap-4">
                <?php if ($app['appointment_confirmed_day_before']): ?>
                    <span class="text-green-600 font-semibold text-sm">
                        <i class="fas fa-check-circle mr-1"></i>Cita confirmada
                    </span>
                <?php else: ?>
                    <form method="POST" action="<?= BASE_URL ?>/solicitudes/confirmar-cita/<?= $app['id'] ?>">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                            <i class="fas fa-check mr-1"></i>Confirmar que la cita sigue vigente
                        </button>
                    </form>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>" class="text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-eye mr-1"></i>Ver
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
