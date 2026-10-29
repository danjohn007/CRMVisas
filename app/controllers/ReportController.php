<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ReportController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        
        try {
            $where = ["DATE(a.created_at) BETWEEN ? AND ?"];
            $params = [$startDate, $endDate];
            
            if (!empty($type)) {
                $where[] = "a.type = ?";
                $params[] = $type;
            }
            
            if (!empty($status)) {
                $where[] = "a.status = ?";
                $params[] = $status;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $where);
            
            // Resumen de solicitudes
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_applications,
                    a.type,
                    a.status,
                    COUNT(DISTINCT a.created_by) as unique_creators
                FROM applications a
                $whereClause
                GROUP BY a.type, a.status
            ");
            $stmt->execute($params);
            $summary = $stmt->fetchAll();
            
            // Resumen financiero
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(fs.total_costs) as total_costs,
                    SUM(fs.total_paid) as total_paid,
                    SUM(fs.balance) as total_balance,
                    COUNT(CASE WHEN fs.status = ? THEN 1 END) as pagado_count,
                    COUNT(CASE WHEN fs.status = ? THEN 1 END) as parcial_count,
                    COUNT(CASE WHEN fs.status = ? THEN 1 END) as pendiente_count
                FROM financial_status fs
                JOIN applications a ON fs.application_id = a.id
                $whereClause
            ");
            $stmt->execute([FINANCIAL_PAGADO, FINANCIAL_PARCIAL, FINANCIAL_PENDIENTE, ...$params]);
            $financial = $stmt->fetch();

            // Control de Ingresos V3: sólo pagos validados por Gerencia.
            $cashFlow = [
                'cash' => 0.0,
                'transfer' => 0.0,
                'terminal' => 0.0,
                'income' => 0.0,
                'expenses' => 0.0,
                'net' => 0.0,
                'sales_amount' => 0.0,
                'sales_count' => 0,
                'receivables' => 0.0,
                'pending_validation' => 0.0,
            ];
            try {
                $stmt = $this->db->prepare("
                    SELECT
                        COALESCE(SUM(CASE WHEN payment_method='EFECTIVO' THEN amount ELSE 0 END),0) AS cash,
                        COALESCE(SUM(CASE WHEN payment_method='TRANSFERENCIA' THEN amount ELSE 0 END),0) AS transfer,
                        COALESCE(SUM(CASE WHEN payment_method='TERMINAL_TARJETA' THEN amount ELSE 0 END),0) AS terminal,
                        COALESCE(SUM(amount),0) AS income
                    FROM income_payments
                    WHERE validation_status='VALIDADO'
                      AND payment_method IN ('EFECTIVO','TRANSFERENCIA','TERMINAL_TARJETA')
                      AND payment_date BETWEEN ? AND ?
                ");
                $stmt->execute([$startDate, $endDate]);
                $incomePeriod = $stmt->fetch() ?: [];
                $cashFlow['cash'] = (float)($incomePeriod['cash'] ?? 0);
                $cashFlow['transfer'] = (float)($incomePeriod['transfer'] ?? 0);
                $cashFlow['terminal'] = (float)($incomePeriod['terminal'] ?? 0);
                $cashFlow['income'] = (float)($incomePeriod['income'] ?? 0);

                $stmt=$this->db->prepare("
                    SELECT COALESCE(SUM(amount),0)
                    FROM financial_expenses
                    WHERE expense_date BETWEEN ? AND ?
                ");
                $stmt->execute([$startDate,$endDate]);
                $cashFlow['expenses']=(float)$stmt->fetchColumn();
                $cashFlow['net']=$cashFlow['income']-$cashFlow['expenses'];

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) AS sales_count, COALESCE(SUM(total_amount),0) AS sales_amount
                    FROM income_sales
                    WHERE approval_status='VALIDADA'
                      AND financial_status<>'CANCELADO'
                      AND contract_date BETWEEN ? AND ?
                ");
                $stmt->execute([$startDate,$endDate]);
                $salesPeriod=$stmt->fetch() ?: [];
                $cashFlow['sales_count']=(int)($salesPeriod['sales_count']??0);
                $cashFlow['sales_amount']=(float)($salesPeriod['sales_amount']??0);

                $stmt=$this->db->query("
                    SELECT COALESCE(SUM(x.balance),0) total
                    FROM (
                      SELECT s.id,
                        s.total_amount-COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) balance
                      FROM income_sales s
                      LEFT JOIN income_payments p ON p.sale_id=s.id
                      WHERE s.approval_status='VALIDADA' AND s.financial_status<>'CANCELADO'
                      GROUP BY s.id
                      HAVING balance>0
                    ) x
                ");
                $cashFlow['receivables']=(float)($stmt->fetch()['total']??0);

                $stmt=$this->db->prepare("
                    SELECT COALESCE(SUM(amount),0) total
                    FROM income_payments
                    WHERE validation_status='PENDIENTE'
                      AND payment_date BETWEEN ? AND ?
                ");
                $stmt->execute([$startDate,$endDate]);
                $cashFlow['pending_validation']=(float)($stmt->fetch()['total']??0);
            } catch (PDOException $e) {
                error_log('Estadística Control de Ingresos V3 no disponible: ' . $e->getMessage());
            }

            // Top asesores
            $stmt = $this->db->prepare("
                SELECT 
                    u.full_name,
                    COUNT(a.id) as total_applications,
                    SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) as finalizadas
                FROM applications a
                JOIN users u ON a.created_by = u.id
                $whereClause
                GROUP BY u.id, u.full_name
                ORDER BY total_applications DESC
                LIMIT 10
            ");
            $stmt->execute([STATUS_FINALIZADO, ...$params]);
            $topAdvisors = $stmt->fetchAll();
            
            // Solicitudes por día
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(a.created_at) as date,
                    COUNT(*) as count
                FROM applications a
                $whereClause
                GROUP BY DATE(a.created_at)
                ORDER BY date ASC
            ");
            $stmt->execute($params);
            $byDay = $stmt->fetchAll();
            
            $this->view('reports/index', [
                'summary' => $summary,
                'financial' => $financial,
                'cashFlow' => $cashFlow,
                'topAdvisors' => $topAdvisors,
                'byDay' => $byDay,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'type' => $type,
                'status' => $status
            ]);
            
        } catch (PDOException $e) {
            error_log("Error al generar reporte: " . $e->getMessage());
            $_SESSION['error'] = 'Error al generar reporte';
            $this->view('reports/index', []);
        }
    }
    
    public function export() {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);
        
        $format = $_GET['format'] ?? 'csv';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        
        try {
            $where = ["DATE(a.created_at) BETWEEN ? AND ?"];
            $params = [$startDate, $endDate];
            
            if (!empty($type)) {
                $where[] = "a.type = ?";
                $params[] = $type;
            }
            
            if (!empty($status)) {
                $where[] = "a.status = ?";
                $params[] = $status;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $where);
            
            // Obtener datos
            $stmt = $this->db->prepare("
                SELECT 
                    a.folio,
                    a.type,
                    a.subtype,
                    a.status,
                    u.full_name as creator_name,
                    a.created_at,
                    fs.total_costs,
                    fs.total_paid,
                    fs.balance,
                    fs.status as financial_status
                FROM applications a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN financial_status fs ON a.id = fs.application_id
                $whereClause
                ORDER BY a.created_at DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            if ($format === 'csv') {
                $this->exportCSV($data, $startDate, $endDate);
            } elseif ($format === 'excel') {
                $this->exportExcel($data, $startDate, $endDate);
            } else {
                $_SESSION['error'] = 'Formato de exportación no soportado';
                $this->redirect('/reportes');
            }
            
        } catch (PDOException $e) {
            error_log("Error al exportar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al exportar datos';
            $this->redirect('/reportes');
        }
    }
    
    private function exportCSV($data, $startDate, $endDate) {
        $filename = "reporte_solicitudes_{$startDate}_a_{$endDate}.csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, [
            'Folio',
            'Tipo',
            'Subtipo',
            'Estatus',
            'Creado por',
            'Fecha creación',
            'Total costos',
            'Total pagado',
            'Saldo',
            'Estado financiero'
        ]);
        
        // Datos
        foreach ($data as $row) {
            fputcsv($output, [
                $row['folio'],
                $row['type'],
                $row['subtype'] ?? '-',
                $row['status'],
                $row['creator_name'],
                date('d/m/Y H:i', strtotime($row['created_at'])),
                '$' . number_format($row['total_costs'] ?? 0, 2),
                '$' . number_format($row['total_paid'] ?? 0, 2),
                '$' . number_format($row['balance'] ?? 0, 2),
                $row['financial_status'] ?? '-'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function exportExcel($data, $startDate, $endDate) {
        // Exportación simple en formato HTML que Excel puede abrir
        $filename = "reporte_solicitudes_{$startDate}_a_{$endDate}.xls";
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1">';
        echo '<tr style="background-color: #3b82f6; color: white; font-weight: bold;">';
        echo '<th>Folio</th>';
        echo '<th>Tipo</th>';
        echo '<th>Subtipo</th>';
        echo '<th>Estatus</th>';
        echo '<th>Creado por</th>';
        echo '<th>Fecha creación</th>';
        echo '<th>Total costos</th>';
        echo '<th>Total pagado</th>';
        echo '<th>Saldo</th>';
        echo '<th>Estado financiero</th>';
        echo '</tr>';
        
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['folio']) . '</td>';
            echo '<td>' . htmlspecialchars($row['type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['subtype'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>' . htmlspecialchars($row['creator_name']) . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</td>';
            echo '<td>$' . number_format($row['total_costs'] ?? 0, 2) . '</td>';
            echo '<td>$' . number_format($row['total_paid'] ?? 0, 2) . '</td>';
            echo '<td>$' . number_format($row['balance'] ?? 0, 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['financial_status'] ?? '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
}
