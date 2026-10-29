<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';
require_once ROOT_PATH . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailController extends BaseController {

    /**
     * Muestra la vista de prueba de envío de correo
     */
    public function testForm() {
        $this->requireRole([ROLE_ADMIN]);
        $this->view('email/test', []);
    }

    /**
     * Envía un correo de prueba usando la configuración SMTP de global_config
     * Retorna JSON con resultado
     */
    public function sendTest() {
        $this->requireRole([ROLE_ADMIN]);

        header('Content-Type: application/json; charset=utf-8');

        $to = trim($_POST['email_destino'] ?? '');
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email de destino inválido o no proporcionado.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Leer configuración SMTP dinámicamente desde global_config
        try {
            $stmt = $this->db->query("SELECT config_key, config_value FROM global_config WHERE config_key IN ('smtp_user','smtp_password','smtp_host','smtp_port')");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al leer configuración SMTP desde base de datos: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $smtpHost     = $rows['smtp_host']     ?? '';
        $smtpUser     = $rows['smtp_user']     ?? '';
        $smtpPassword = $rows['smtp_password'] ?? '';
        $smtpPort     = (int)($rows['smtp_port'] ?? 465);

        if (empty($smtpHost) || empty($smtpUser) || empty($smtpPassword)) {
            echo json_encode([
                'success' => false,
                'message' => 'Configuración SMTP incompleta. Verifica smtp_host, smtp_user y smtp_password en Configuración del Sistema.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPassword;
            $mail->Port       = $smtpPort;

            // Seleccionar encriptación según el puerto
            if ($smtpPort == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtpUser, getSiteName());
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'Correo de prueba - ' . getSiteName();
            $mail->Body    = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #3b82f6;">✅ Prueba de configuración SMTP</h2>
                    <p>Este es un correo de prueba enviado desde <strong>' . htmlspecialchars(getSiteName()) . '</strong>.</p>
                    <p>La configuración SMTP está funcionando correctamente.</p>
                    <hr style="margin: 20px 0; border-color: #e5e7eb;">
                    <p style="color: #6b7280; font-size: 12px;">
                        Enviado desde: ' . htmlspecialchars($smtpUser) . '<br>
                        Servidor: ' . htmlspecialchars($smtpHost) . ':' . $smtpPort . '<br>
                        Fecha: ' . date('d/m/Y H:i:s') . '
                    </p>
                </div>';
            $mail->AltBody = 'Correo de prueba desde ' . getSiteName() . '. La configuración SMTP está funcionando correctamente.';

            $mail->send();

            echo json_encode([
                'success' => true,
                'message' => 'Correo de prueba enviado correctamente a ' . htmlspecialchars($to) . '.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al enviar correo: ' . $mail->ErrorInfo
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
