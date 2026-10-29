<?php
/**
 * Appointment Reminder Email Cron Script
 *
 * Finds applications with appointments scheduled within the next 2 days and
 * sends reminder emails to all relevant recipients (deduplicated).
 *
 * Handles both:
 *   - Standard (consular) appointments via applications.appointment_date
 *   - Canadian visa biometric appointments via applications.canadian_biometric_date
 *
 * This script is safe to run multiple times; the reminder_sent flags prevent
 * duplicate emails.
 *
 * Edge-case: If an appointment is created for "tomorrow", the next cron run
 * will pick it up and send the reminder (since reminder_sent = 0).
 *
 * Suggested cron schedule (hourly):
 *   0 * * * * /usr/bin/php /var/www/html/public/cron/appointment_reminders.php >> /var/log/crm_reminders.log 2>&1
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be executed from the command line.');
}

require_once __DIR__ . '/../../config/config.php';
require_once ROOT_PATH . '/vendor/autoload.php';

$db = Database::getInstance()->getConnection();

// ── Date window: today through 2 days from now ─────────────────────────────

$today       = date('Y-m-d');
$twoDaysLater = date('Y-m-d', strtotime('+2 days'));

echo '[' . date('Y-m-d H:i:s') . "] Starting appointment reminder check (window: $today – $twoDaysLater)\n";

// ── Queries ────────────────────────────────────────────────────────────────

$queries = [
    'consular' => [
        'sql' => "
            SELECT id, folio, appointment_date AS appt_date
            FROM applications
            WHERE appointment_date IS NOT NULL
              AND DATE(appointment_date) >= ?
              AND DATE(appointment_date) <= ?
              AND (appointment_reminder_sent IS NULL OR appointment_reminder_sent = 0)
              AND (client_attended IS NULL OR client_attended = 0)
              AND status NOT IN (?, ?)
        ",
        'params'  => [$today, $twoDaysLater, STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO],
        'flagCol' => 'appointment_reminder_sent',
    ],
    'biometric' => [
        'sql' => "
            SELECT id, folio, canadian_biometric_date AS appt_date
            FROM applications
            WHERE canadian_biometric_date IS NOT NULL
              AND DATE(canadian_biometric_date) >= ?
              AND DATE(canadian_biometric_date) <= ?
              AND (biometric_reminder_sent IS NULL OR biometric_reminder_sent = 0)
              AND (canadian_client_attended_biometrics IS NULL OR canadian_client_attended_biometrics = 0)
              AND status NOT IN (?, ?)
        ",
        'params'  => [$today, $twoDaysLater, STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO],
        'flagCol' => 'biometric_reminder_sent',
    ],
];

$totalSent   = 0;
$totalErrors = 0;

foreach ($queries as $type => $cfg) {
    try {
        $stmt = $db->prepare($cfg['sql']);
        $stmt->execute($cfg['params']);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo '[' . date('H:i:s') . "] ERROR querying $type appointments: " . $e->getMessage() . "\n";
        $totalErrors++;
        continue;
    }

    if (empty($applications)) {
        echo '[' . date('H:i:s') . "] No pending $type reminders.\n";
        continue;
    }

    foreach ($applications as $app) {
        $folio  = $app['folio'];
        $appId  = (int)$app['id'];
        $apptDt = $app['appt_date'];

        echo '[' . date('H:i:s') . "] Sending $type reminder for $folio (appointment: $apptDt) ... ";

        try {
            $ok = sendAppointmentNotificationEmail($appId, $type, $apptDt, true, $db);
        } catch (Exception $e) {
            $ok = false;
            error_log("Cron exception sending $type reminder for $folio: " . $e->getMessage());
        }

        if ($ok) {
            // Mark reminder as sent
            $flagCol = $cfg['flagCol'];
            try {
                $db->prepare("UPDATE applications SET $flagCol = 1 WHERE id = ?")
                   ->execute([$appId]);
            } catch (PDOException $e) {
                error_log("Cron: could not set $flagCol for application #$appId: " . $e->getMessage());
            }
            $totalSent++;
            echo "OK\n";
        } else {
            $totalErrors++;
            echo "FAILED (check error log)\n";
        }
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Done. Sent: $totalSent, Errors: $totalErrors\n";
