<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class CashEnvelopeController extends BaseController
{
    private function cutoffDate(): string
    {
        return (string)getConfig('cash_envelope_system_start', '2026-11-01');
    }

    public function index()
    {
        $this->requireRole([ROLE_ADMIN]);

        $cutoff = $this->cutoffDate();
        $stmt = $this->db->query("
            SELECT e.*, u.full_name created_by_name, uu.full_name updated_by_name
            FROM monthly_cash_envelopes e
            LEFT JOIN users u ON u.id=e.created_by
            LEFT JOIN users uu ON uu.id=e.updated_by
            ORDER BY e.month_start DESC
        ");
        $envelopes = $stmt->fetchAll();

        $this->view('finance-flow/envelopes', [
            'envelopes' => $envelopes,
            'cutoff' => $cutoff,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function store()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->verifyCsrf('/sobres');

        $month = trim((string)($_POST['month'] ?? ''));
        $amount = parseCurrencyInput($_POST['amount'] ?? 0);
        $notes = trim((string)($_POST['notes'] ?? ''));
        $cutoff = $this->cutoffDate();

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $_SESSION['error'] = 'Seleccione un mes válido.';
            $this->redirect('/sobres');
        }
        $monthStart = $month . '-01';
        if ($monthStart >= $cutoff) {
            $_SESSION['error'] = 'Sobres sólo se usa para meses anteriores al inicio automático del sistema (' . date('m/Y', strtotime($cutoff)) . ').';
            $this->redirect('/sobres');
        }
        if ($amount < 0) {
            $_SESSION['error'] = 'La cantidad del sobre no puede ser negativa.';
            $this->redirect('/sobres');
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO monthly_cash_envelopes(month_start,amount,notes,created_by,updated_by)
                VALUES(?,?,?,?,?)
                ON DUPLICATE KEY UPDATE amount=VALUES(amount),notes=VALUES(notes),updated_by=VALUES(updated_by),updated_at=NOW()
            ");
            $stmt->execute([$monthStart,$amount,$notes!==''?$notes:null,$_SESSION['user_id'],$_SESSION['user_id']]);
            logAudit('save', 'sobres', 'Sobre mensual ' . $month . ' = $' . number_format($amount,2));
            $_SESSION['success'] = 'Sobre de ' . $this->monthLabel($monthStart) . ' guardado correctamente.';
        } catch (Throwable $e) {
            error_log('Error guardando sobre mensual: ' . $e->getMessage());
            $_SESSION['error'] = 'No fue posible guardar el sobre.';
        }
        $this->redirect('/sobres');
    }

    public function delete($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->verifyCsrf('/sobres');
        $stmt = $this->db->prepare("SELECT * FROM monthly_cash_envelopes WHERE id=? LIMIT 1");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        if (!$row) {
            $_SESSION['error'] = 'Sobre no encontrado.';
            $this->redirect('/sobres');
        }
        $stmt = $this->db->prepare("DELETE FROM monthly_cash_envelopes WHERE id=?");
        $stmt->execute([(int)$id]);
        logAudit('delete', 'sobres', 'Sobre mensual eliminado: ' . $row['month_start']);
        $_SESSION['success'] = 'Sobre eliminado.';
        $this->redirect('/sobres');
    }

    private function csrfToken(): string
    {
        if (empty($_SESSION['cash_envelope_csrf'])) $_SESSION['cash_envelope_csrf'] = bin2hex(random_bytes(24));
        return (string)$_SESSION['cash_envelope_csrf'];
    }

    private function verifyCsrf(string $redirect): void
    {
        $posted = (string)($_POST['csrf_token'] ?? '');
        $known = (string)($_SESSION['cash_envelope_csrf'] ?? '');
        if ($posted === '' || $known === '' || !hash_equals($known, $posted)) {
            $_SESSION['error'] = 'La sesión expiró. Recargue la página e intente de nuevo.';
            $this->redirect($redirect);
        }
    }

    private function monthLabel(string $date): string
    {
        $months=[1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
        $ts=strtotime($date);
        return ucfirst($months[(int)date('n',$ts)]) . ' ' . date('Y',$ts);
    }
}
