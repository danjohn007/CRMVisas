<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class DashboardController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $role = $this->getUserRole();
        $userId = $_SESSION['user_id'];
        
        // Estadísticas generales
        $stats = [];
        
        try {
            // Total de solicitudes (según rol)
            if ($role === ROLE_ASESOR) {
                // Asesor solo ve solicitudes NO finalizadas ni rechazadas
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total FROM applications 
                    WHERE status NOT IN (?, ?)
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                // Admin y Gerente ven todas
                $stmt = $this->db->query("SELECT COUNT(*) as total FROM applications");
            }
            $stats['total_applications'] = $stmt->fetch()['total'];
            
            // Solicitudes por estatus
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT status, COUNT(*) as count 
                    FROM applications 
                    WHERE status NOT IN (?, ?)
                    GROUP BY status
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT status, COUNT(*) as count 
                    FROM applications 
                    GROUP BY status
                ");
            }
            $stats['by_status'] = $stmt->fetchAll();
            
            // Solicitudes recientes
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT a.*, u.full_name as creator_name 
                    FROM applications a
                    LEFT JOIN users u ON a.created_by = u.id
                    WHERE a.status NOT IN (?, ?)
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT a.*, u.full_name as creator_name 
                    FROM applications a
                    LEFT JOIN users u ON a.created_by = u.id
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ");
            }
            $stats['recent_applications'] = $stmt->fetchAll();
            
            // Estadísticas financieras (solo Admin y Gerente)
            if ($this->canAccessFinancial()) {
                $stmt = $this->db->query("
                    SELECT 
                        SUM(total_costs) as total_costs,
                        SUM(total_paid) as total_paid,
                        SUM(balance) as total_balance
                    FROM financial_status
                ");
                $stats['financial'] = $stmt->fetch();
                
                // Pagos recientes (con identificación del cliente cuando esté disponible)
                $stmt = $this->db->query("
                    SELECT p.*, a.folio,
                           COALESCE(NULLIF(p.client_name_snapshot, ''), a.client_name) AS client_name
                    FROM payments p
                    LEFT JOIN applications a ON p.application_id = a.id
                    ORDER BY p.payment_date DESC, p.id DESC
                    LIMIT 5
                ");
                $stats['recent_payments'] = $stmt->fetchAll();

                // Métodos de pago: únicamente ingresos validados por Gerencia.
                try {
                    $stmt = $this->db->query("
                        SELECT
                            CASE payment_method
                                WHEN 'EFECTIVO' THEN 'Efectivo'
                                WHEN 'TRANSFERENCIA' THEN 'Transferencia'
                                WHEN 'TERMINAL_TARJETA' THEN 'Terminal'
                            END AS payment_method,
                            COUNT(*) AS count,
                            COALESCE(SUM(amount),0) AS total
                        FROM income_payments
                        WHERE validation_status='VALIDADO'
                          AND payment_method IN ('EFECTIVO','TRANSFERENCIA','TERMINAL_TARJETA')
                        GROUP BY payment_method
                        ORDER BY total DESC
                    ");
                    $stats['payments_by_method'] = $stmt->fetchAll();
                } catch (PDOException $e) {
                    // Compatibilidad si aún no se instala la migración V3.
                    $stmt = $this->db->query("
                        SELECT COALESCE(NULLIF(payment_method, ''), 'Otro') AS payment_method,
                               COUNT(*) AS count, SUM(amount) AS total
                        FROM payments
                        GROUP BY COALESCE(NULLIF(payment_method, ''), 'Otro')
                    ");
                    $stats['payments_by_method'] = $stmt->fetchAll();
                }

                // V5: resumen financiero lunes a viernes.
                $weekStart = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
                $weekEnd = (new DateTimeImmutable('friday this week'))->format('Y-m-d');
                $weekly = [
                    'week_start'=>$weekStart,
                    'week_end'=>$weekEnd,
                    'cash_income'=>0.0,
                    'terminal_income'=>0.0,
                    'transfer_income'=>0.0,
                    'total_income'=>0.0,
                    'expenses'=>0.0,
                    'net'=>0.0,
                    'sales_amount'=>0.0,
                    'sales_count'=>0,
                    'accounts_receivable'=>0.0,
                    'pending_validation'=>0.0,
                    'cash_balance'=>0.0,
                    'terminal_balance'=>0.0,
                    'transfer_balance'=>0.0,
                ];

                try {
                    $stmt=$this->db->prepare("
                        SELECT
                          COALESCE(SUM(CASE WHEN payment_method='EFECTIVO' THEN amount ELSE 0 END),0) cash_income,
                          COALESCE(SUM(CASE WHEN payment_method='TERMINAL_TARJETA' THEN amount ELSE 0 END),0) terminal_income,
                          COALESCE(SUM(CASE WHEN payment_method='TRANSFERENCIA' THEN amount ELSE 0 END),0) transfer_income,
                          COALESCE(SUM(amount),0) total_income
                        FROM income_payments
                        WHERE validation_status='VALIDADO'
                          AND payment_method IN ('EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA')
                          AND payment_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$weekStart,$weekEnd]);
                    $income=$stmt->fetch()?:[];
                    foreach(['cash_income','terminal_income','transfer_income','total_income'] as $key){
                        $weekly[$key]=(float)($income[$key]??0);
                    }

                    $stmt=$this->db->prepare("
                        SELECT COUNT(*) sales_count,COALESCE(SUM(total_amount),0) sales_amount
                        FROM income_sales
                        WHERE approval_status='VALIDADA'
                          AND financial_status<>'CANCELADO'
                          AND contract_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$weekStart,$weekEnd]);
                    $sales=$stmt->fetch()?:[];
                    $weekly['sales_count']=(int)($sales['sales_count']??0);
                    $weekly['sales_amount']=(float)($sales['sales_amount']??0);

                    $stmt=$this->db->prepare("
                        SELECT COALESCE(SUM(amount),0)
                        FROM financial_expenses
                        WHERE expense_date BETWEEN ? AND ?
                    ");
                    $stmt->execute([$weekStart,$weekEnd]);
                    $weekly['expenses']=(float)$stmt->fetchColumn();
                    $weekly['net']=$weekly['total_income']-$weekly['expenses'];

                    $stmt=$this->db->query("
                        SELECT COALESCE(SUM(x.balance),0) total
                        FROM (
                          SELECT s.id,
                            s.total_amount-COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) balance
                          FROM income_sales s
                          LEFT JOIN income_payments p ON p.sale_id=s.id
                          WHERE s.approval_status='VALIDADA' AND s.financial_status<>'CANCELADO'
                          GROUP BY s.id HAVING balance>0
                        ) x
                    ");
                    $weekly['accounts_receivable']=(float)($stmt->fetchColumn()?:0);

                    $stmt=$this->db->prepare("
                        SELECT
                          COALESCE((SELECT SUM(p.amount)
                            FROM income_payments p INNER JOIN income_sales s ON s.id=p.sale_id
                            WHERE p.validation_status='PENDIENTE'
                              AND p.payment_date BETWEEN ? AND ?),0)
                        +
                          COALESCE((SELECT SUM(s.total_amount)
                            FROM income_sales s
                            WHERE s.approval_status='PENDIENTE'
                              AND s.contract_date BETWEEN ? AND ?),0)
                    ");
                    $stmt->execute([$weekStart,$weekEnd,$weekStart,$weekEnd]);
                    $weekly['pending_validation']=(float)$stmt->fetchColumn();

                    $balances=financeGetAccountBalances($this->db);
                    $weekly['cash_balance']=(float)$balances['EFECTIVO'];
                    $weekly['terminal_balance']=(float)$balances['TERMINAL'];
                    $weekly['transfer_balance']=(float)$balances['TRANSFERENCIA'];
                } catch (Throwable $e) {
                    error_log('Resumen semanal V5 no disponible: '.$e->getMessage());
                }

                $stats['weekly_financial'] = $weekly;
            }
            
            // Datos para gráfica de solicitudes por mes (últimos 6 meses)
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                    FROM applications
                    WHERE status NOT IN (?, ?) 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                    FROM applications
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month
                ");
            }
            $stats['applications_by_month'] = $stmt->fetchAll();
            
            // Datos para gráfica de tipos de solicitudes
            if ($role === ROLE_ASESOR) {
                $stmt = $this->db->prepare("
                    SELECT type, COUNT(*) as count
                    FROM applications
                    WHERE status NOT IN (?, ?)
                    GROUP BY type
                ");
                $stmt->execute([STATUS_FINALIZADO, STATUS_RECHAZADO]);
            } else {
                $stmt = $this->db->query("
                    SELECT type, COUNT(*) as count
                    FROM applications
                    GROUP BY type
                ");
            }
            $stats['applications_by_type'] = $stmt->fetchAll();

            // Datos para calendario de citas (solicitudes con cita programada)
            try {
                $officeAppointmentExistsStmt = $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM information_schema.columns
                    WHERE table_schema = DATABASE()
                      AND table_name = 'applications'
                      AND column_name = 'office_appointment_date'
                ");
                $hasOfficeAppointmentDate = ((int) ($officeAppointmentExistsStmt->fetch()['total'] ?? 0)) > 0;

                if ($hasOfficeAppointmentDate) {
                    $appointmentSql = "
                        SELECT a.id, a.folio, a.appointment_date, a.canadian_biometric_date, a.office_appointment_date,
                               a.is_canadian_visa, a.type, a.subtype,
                               a.appointment_confirmed_day_before,
                               u.full_name as creator_name
                        FROM applications a
                        LEFT JOIN users u ON a.created_by = u.id
                        WHERE (
                            (COALESCE(a.is_canadian_visa, 0) = 0 AND a.appointment_date IS NOT NULL)
                            OR (a.is_canadian_visa = 1 AND a.canadian_biometric_date IS NOT NULL)
                            OR a.office_appointment_date IS NOT NULL
                        )
                    ";
                } else {
                    $appointmentSql = "
                        SELECT a.id, a.folio, a.appointment_date, a.canadian_biometric_date, NULL AS office_appointment_date,
                               a.is_canadian_visa, a.type, a.subtype,
                               a.appointment_confirmed_day_before,
                               u.full_name as creator_name
                        FROM applications a
                        LEFT JOIN users u ON a.created_by = u.id
                        WHERE (
                            (COALESCE(a.is_canadian_visa, 0) = 0 AND a.appointment_date IS NOT NULL)
                            OR (a.is_canadian_visa = 1 AND a.canadian_biometric_date IS NOT NULL)
                        )
                    ";
                }
                $appointmentParams = [];

                if ($role === ROLE_ASESOR) {
                    $appointmentSql .= " AND a.created_by = ?";
                    $appointmentParams[] = $userId;
                }
                if ($hasOfficeAppointmentDate) {
                    $appointmentSql .= " ORDER BY COALESCE(a.office_appointment_date, a.canadian_biometric_date, a.appointment_date) ASC";
                } else {
                    $appointmentSql .= " ORDER BY COALESCE(a.canadian_biometric_date, a.appointment_date) ASC";
                }

                $stmt = $this->db->prepare($appointmentSql);
                $stmt->execute($appointmentParams);
                $stats['appointments'] = $stmt->fetchAll();
            } catch (PDOException $e) {
                $stats['appointments'] = [];
            }
            
            
        } catch (PDOException $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar estadísticas';
        }
        
        $this->view('dashboard/index', ['stats' => $stats]);
    }
}
