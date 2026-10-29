<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class FinancialController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        $this->redirect('/control-ingresos');
    }

    public function show($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        $this->redirect('/solicitudes/ver/' . (int)$id);
    }

    public function addCost($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        $_SESSION['error'] = 'El expediente financiero ahora se alimenta desde la venta registrada con el Catálogo Maestro de Ingresos.';
        $this->redirect('/solicitudes/ver/' . (int)$id);
    }

    public function registerPayment($id)
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        $_SESSION['error'] = 'Los abonos se registran desde Control de Ingresos dentro del mismo expediente financiero.';
        $this->redirect('/solicitudes/ver/' . (int)$id);
    }
}
