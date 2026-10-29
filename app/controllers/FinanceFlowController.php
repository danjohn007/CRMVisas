<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class FinanceFlowController extends BaseController
{
    public function advisorFortnight()
    {
        $this->requireRole([ROLE_ASESOR,ROLE_ADMIN,ROLE_GERENTE]);

        [$start,$end]=$this->resolveAdvisorPeriod($_GET['start']??null,$_GET['end']??null);

        $role=$this->getUserRole();
        $isAdvisor=$role===ROLE_ASESOR;
        $selectedAdvisorId=$isAdvisor
            ? (int)$_SESSION['user_id']
            : max(0,(int)($_GET['advisor_id']??0));

        $where=[
            "s.approval_status='VALIDADA'",
            "s.financial_status<>'CANCELADO'",
            "s.contract_date BETWEEN ? AND ?"
        ];
        $params=[$start,$end];

        if($selectedAdvisorId>0){
            $where[]='s.advisor_id=?';
            $params[]=$selectedAdvisorId;
        }

        $stmt=$this->db->prepare("
            SELECT s.sale_folio,s.client_folio_snapshot,s.client_name_snapshot,
                   s.service_name_snapshot,s.catalog_code,s.quantity,s.unit_price,s.total_amount,
                   s.contract_date,u.full_name advisor_name
            FROM income_sales s
            LEFT JOIN users u ON u.id=s.advisor_id
            WHERE ".implode(' AND ',$where)."
            ORDER BY s.contract_date DESC,s.id DESC
        ");
        $stmt->execute($params);
        $sales=$stmt->fetchAll();

        $overtimeWhere=["o.work_date BETWEEN ? AND ?"];
        $overtimeParams=[$start,$end];
        if($selectedAdvisorId>0){
            $overtimeWhere[]='o.advisor_id=?';
            $overtimeParams[]=$selectedAdvisorId;
        }

        $stmt=$this->db->prepare("
            SELECT o.*,u.full_name advisor_name
            FROM advisor_overtime o
            LEFT JOIN users u ON u.id=o.advisor_id
            WHERE ".implode(' AND ',$overtimeWhere)."
            ORDER BY o.work_date DESC,o.id DESC
        ");
        $stmt->execute($overtimeParams);
        $overtime=$stmt->fetchAll();

        $advisors=[];
        if(!$isAdvisor){
            $advisors=$this->db->query("
                SELECT id,COALESCE(NULLIF(full_name,''),username) full_name
                FROM users
                WHERE role='Asesor' AND is_active=1
                ORDER BY full_name
            ")->fetchAll();
        }

        $totalOperations=0;
        $totalAmount=0.0;
        foreach($sales as $sale){
            $totalOperations+=(int)$sale['quantity'];
            $totalAmount+=(float)$sale['total_amount'];
        }

        $overtimeHours=0.0;
        foreach($overtime as $o)$overtimeHours+=(float)$o['hours'];

        $csrfToken=$this->financeCsrfToken();

        $this->view('finance-flow/advisor-fortnight',compact(
            'start','end','sales','overtime','totalOperations','totalAmount','overtimeHours',
            'csrfToken','isAdvisor','selectedAdvisorId','advisors'
        ));
    }

    public function storeOvertime()
    {
        $this->requireRole([ROLE_ASESOR]);
        $this->verifyFinanceCsrf('/ventas-quincenales');

        $date=trim((string)($_POST['work_date']??date('Y-m-d')));
        $hours=(float)($_POST['hours']??0);
        $description=trim((string)($_POST['description']??''));

        try {
            if(!$this->validDate($date) || $date>date('Y-m-d')){
                throw new RuntimeException('La fecha de horas extra no es válida.');
            }
            if($hours<=0 || $hours>24) throw new RuntimeException('Indique un número de horas válido.');
            if($description==='') throw new RuntimeException('Indique el motivo de las horas extra.');

            $stmt=$this->db->prepare("
                INSERT INTO advisor_overtime(advisor_id,work_date,hours,description)
                VALUES(?,?,?,?)
            ");
            $stmt->execute([$_SESSION['user_id'],$date,$hours,$description]);

            logAdminControlEvent('ventas_asesora','horas_extra','Horas extra registradas por asesora',[
                'entity_type'=>'advisor_overtime','entity_id'=>(int)$this->db->lastInsertId(),'priority'=>'normal',
                'metadata'=>['fecha'=>$date,'horas'=>$hours,'motivo'=>$description]
            ]);
            $_SESSION['success']='Horas extra registradas.';
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/ventas-quincenales?start='.urlencode($_POST['period_start']??'').'&end='.urlencode($_POST['period_end']??''));
    }

    public function expenses()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);

        $month=preg_match('/^\d{4}-\d{2}$/',(string)($_GET['month']??''))?$_GET['month']:date('Y-m');
        $start=$month.'-01';
        $end=date('Y-m-t',strtotime($start));

        $stmt=$this->db->prepare("
            SELECT e.*,u.full_name created_by_name
            FROM financial_expenses e
            LEFT JOIN users u ON u.id=e.created_by
            WHERE e.expense_date BETWEEN ? AND ?
            ORDER BY e.expense_date DESC,e.expense_time DESC,e.id DESC
        ");
        $stmt->execute([$start,$end]);
        $expenses=$stmt->fetchAll();

        $stmt=$this->db->prepare("
            SELECT COALESCE(SUM(amount),0) FROM financial_expenses
            WHERE expense_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        $monthExpense=(float)$stmt->fetchColumn();

        $balances=financeGetAccountBalances($this->db);
        $canCreateExpense=in_array($this->getUserRole(),[ROLE_ADMIN,ROLE_GERENTE],true);
        $csrfToken=$this->financeCsrfToken();

        $this->view('finance-flow/expenses',compact(
            'month','expenses','monthExpense','balances','canCreateExpense','csrfToken'
        ));
    }

    public function storeExpense()
    {
        // V5.1: Gerencia y Administración pueden registrar egresos.
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $this->verifyFinanceCsrf('/egresos');

        $concept=trim((string)($_POST['concept']??''));
        $amount=parseCurrencyInput($_POST['amount']??0);
        $reference=trim((string)($_POST['reference']??''));
        $notes=trim((string)($_POST['notes']??''));
        $evidencePath=null;
        $lockName='crmvisas_expense_folio_'.date('Y');
        $gotLock=false;

        try {
            if($concept==='') throw new RuntimeException('Indique de qué fue el egreso.');
            if($amount<=0) throw new RuntimeException('El importe del egreso debe ser mayor a $0.');

            // El egreso representa un movimiento real y debe poder registrarse aunque
            // el saldo calculado de Efectivo sea cero o negativo. Si ocurre, el Balance
            // mostrará la diferencia para que Gerencia pueda detectar ingresos faltantes
            // o un saldo inicial aún no capturado.
            $cashBefore=financeCashAvailable($this->db);

            if(empty($_FILES['evidence']['name'])){
                throw new RuntimeException('Adjunte la evidencia del egreso.');
            }
            $evidencePath=$this->storeEvidence($_FILES['evidence']);

            $stmt=$this->db->prepare("SELECT GET_LOCK(?,8)");
            $stmt->execute([$lockName]);
            $gotLock=(int)$stmt->fetchColumn()===1;
            if(!$gotLock) throw new RuntimeException('No se pudo reservar el folio del egreso.');

            $year=date('Y');
            $stmt=$this->db->prepare("
                SELECT MAX(CAST(SUBSTRING(expense_folio,-6) AS UNSIGNED))
                FROM financial_expenses
                WHERE expense_folio LIKE ?
            ");
            $stmt->execute(["EGR-$year-%"]);
            $last=(int)($stmt->fetchColumn()?:0);
            $folio=sprintf('EGR-%s-%06d',$year,$last+1);

            $stmt=$this->db->prepare("
                INSERT INTO financial_expenses
                  (expense_folio,concept,amount,payment_method,cash_envelope_id,reference,evidence_path,
                   notes,expense_date,expense_time,created_by)
                VALUES (?,?,?,'EFECTIVO',NULL,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $folio,$concept,$amount,$reference!==''?$reference:null,$evidencePath,
                $notes!==''?$notes:null,date('Y-m-d'),date('H:i:s'),$_SESSION['user_id']
            ]);
            $expenseId=(int)$this->db->lastInsertId();

            financeSyncExpenseAccountMovement($this->db,$expenseId);
            $cashAfter=financeCashAvailable($this->db);

            financialLogCorrectionIfNeeded(date('Y-m-d'),'egreso',$expenseId,[
                'folio'=>$folio,'concepto'=>$concept,'monto'=>$amount,'cuenta'=>'EFECTIVO',
                'saldo_efectivo_antes'=>$cashBefore,'saldo_efectivo_despues'=>$cashAfter
            ],$this->db);

            logAdminControlEvent('egresos','registrar','Egreso registrado: '.$folio,[
                'entity_type'=>'financial_expense','entity_id'=>$expenseId,'priority'=>'alta',
                'metadata'=>[
                    'folio'=>$folio,'concepto'=>$concept,'monto'=>$amount,'cuenta'=>'EFECTIVO',
                    'saldo_efectivo_antes'=>$cashBefore,'saldo_efectivo_despues'=>$cashAfter
                ]
            ]);

            $_SESSION['success']='Egreso '.$folio.' registrado y descontado de Efectivo.'
                .($cashAfter<0
                    ? ' El saldo de Efectivo quedó en -$'.number_format(abs($cashAfter),2)
                      .'; revise si falta validar un ingreso o capturar el saldo inicial.'
                    : ' Saldo de Efectivo: $'.number_format($cashAfter,2).'.');
        } catch(Throwable $e) {
            if($evidencePath && is_file(ROOT_PATH.'/'.$evidencePath))@unlink(ROOT_PATH.'/'.$evidencePath);
            $_SESSION['error']=$e->getMessage();
        } finally {
            if($gotLock){
                try{$stmt=$this->db->prepare("SELECT RELEASE_LOCK(?)");$stmt->execute([$lockName]);}catch(Throwable $e){}
            }
        }

        $this->redirect('/egresos');
    }

    public function expenseEvidence($id)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $stmt=$this->db->prepare("SELECT evidence_path,expense_folio FROM financial_expenses WHERE id=? LIMIT 1");
        $stmt->execute([(int)$id]);
        $row=$stmt->fetch();
        if(!$row || empty($row['evidence_path'])){http_response_code(404);exit('Evidencia no encontrada.');}

        $path=(string)$row['evidence_path'];
        if(strpos($path,'..')!==false){http_response_code(400);exit('Ruta no válida.');}
        $full=ROOT_PATH.'/'.ltrim($path,'/');
        if(!is_file($full)){http_response_code(404);exit('Evidencia no encontrada.');}

        $ext=strtolower(pathinfo($full,PATHINFO_EXTENSION));
        $types=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'];
        header('Content-Type: '.($types[$ext]??'application/octet-stream'));
        header('Content-Disposition: inline; filename="'.basename($row['expense_folio']).'.'.$ext.'"');
        header('Cache-Control: private, no-store');
        readfile($full);exit;
    }

    public function monthly()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);

        $month=preg_match('/^\d{4}-\d{2}$/',(string)($_GET['month']??''))?$_GET['month']:date('Y-m');
        $start=$month.'-01';
        $end=date('Y-m-t',strtotime($start));
        $previousEnd=date('Y-m-t',strtotime($start.' -1 month'));

        $stmt=$this->db->prepare("
            SELECT COALESCE(NULLIF(s.catalog_code,''),'SIN-CODIGO') service_code,
                   s.service_name_snapshot,
                   SUM(s.quantity) quantity_total,
                   COUNT(*) sales_count,
                   SUM(s.total_amount) amount_total
            FROM income_sales s
            WHERE s.approval_status='VALIDADA'
              AND s.financial_status<>'CANCELADO'
              AND s.contract_date BETWEEN ? AND ?
            GROUP BY COALESCE(NULLIF(s.catalog_code,''),'SIN-CODIGO'),s.service_name_snapshot
            ORDER BY s.service_name_snapshot
        ");
        $stmt->execute([$start,$end]);
        $serviceGroups=$stmt->fetchAll();

        $stmt=$this->db->prepare("
            SELECT COALESCE(NULLIF(s.catalog_code,''),'SIN-CODIGO') service_code,
                   s.contract_date,s.client_name_snapshot,s.client_folio_snapshot,s.sale_folio,
                   s.service_name_snapshot,s.quantity,s.unit_price,s.total_amount,
                   u.full_name advisor_name
            FROM income_sales s
            LEFT JOIN users u ON u.id=s.advisor_id
            WHERE s.approval_status='VALIDADA'
              AND s.financial_status<>'CANCELADO'
              AND s.contract_date BETWEEN ? AND ?
            ORDER BY s.service_name_snapshot,s.contract_date,s.id
        ");
        $stmt->execute([$start,$end]);
        $allServiceDetail=$stmt->fetchAll();

        $serviceDetails=[];
        foreach($allServiceDetail as $row){
            $key=(string)$row['service_code'].'|'.(string)$row['service_name_snapshot'];
            if(!isset($serviceDetails[$key]))$serviceDetails[$key]=[];
            $serviceDetails[$key][]=$row;
        }

        $stmt=$this->db->prepare("
            SELECT
              COALESCE(SUM(CASE WHEN payment_method='EFECTIVO' THEN amount ELSE 0 END),0) cash,
              COALESCE(SUM(CASE WHEN payment_method='TERMINAL_TARJETA' THEN amount ELSE 0 END),0) terminal,
              COALESCE(SUM(CASE WHEN payment_method='TRANSFERENCIA' THEN amount ELSE 0 END),0) transfer,
              COALESCE(SUM(amount),0) total
            FROM income_payments
            WHERE validation_status='VALIDADO'
              AND payment_method IN ('EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA')
              AND payment_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        $income=$stmt->fetch()?:['cash'=>0,'terminal'=>0,'transfer'=>0,'total'=>0];

        $stmt=$this->db->prepare("
            SELECT COALESCE(SUM(amount),0)
            FROM financial_expenses WHERE expense_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        $expenses=(float)$stmt->fetchColumn();

        $balances=financeGetAccountBalances($this->db,$end);
        $previousBalances=financeGetAccountBalances($this->db,$previousEnd);

        // V5.9: los Sobres históricos son cierres de efectivo, no ingresos.
        // Se usan como base del ahorro y, desde el corte configurado, se continúa
        // automáticamente con los movimientos reales de EFECTIVO.
        $cashSavings=$this->cashSavingsAtDate($end);
        $previousCashSavings=$this->cashSavingsAtDate($previousEnd);
        $balances['EFECTIVO']=$cashSavings;
        $balances['TOTAL']=(float)$balances['EFECTIVO']+(float)$balances['TERMINAL']+(float)$balances['TRANSFERENCIA'];
        $previousBalances['EFECTIVO']=$previousCashSavings;
        $previousBalances['TOTAL']=(float)$previousBalances['EFECTIVO']+(float)$previousBalances['TERMINAL']+(float)$previousBalances['TRANSFERENCIA'];

        $totalOperations=0;
        $totalSalesAmount=0.0;
        foreach($serviceGroups as $g){
            $totalOperations+=(int)$g['quantity_total'];
            $totalSalesAmount+=(float)$g['amount_total'];
        }

        $monthlyNet=(float)$income['total']-$expenses;
        $cashNet=(float)$income['cash']-$expenses;

        $this->view('finance-flow/monthly',compact(
            'month','start','end','previousEnd','serviceGroups','serviceDetails',
            'income','expenses','balances','previousBalances','totalOperations','totalSalesAmount',
            'monthlyNet','cashNet','cashSavings','previousCashSavings'
        ));
    }

    private function cashSavingsAtDate(string $throughDate): float
    {
        $cutoff=(string)getConfig('cash_envelope_system_start','2026-11-01');
        $throughTs=strtotime($throughDate);
        $cutoffTs=strtotime($cutoff);

        try {
            $stmt=$this->db->prepare("
                SELECT amount,month_start
                FROM monthly_cash_envelopes
                WHERE month_start<=?
                ORDER BY month_start DESC
                LIMIT 1
            ");
            $stmt->execute([date('Y-m-01',$throughTs)]);
            $envelope=$stmt->fetch();

            // Antes del corte, el último sobre capturado representa el efectivo al cierre.
            if($throughTs<$cutoffTs){
                if($envelope) return (float)$envelope['amount'];
                return (float)(financeGetAccountBalances($this->db,$throughDate)['EFECTIVO']??0);
            }

            // Desde el corte, tomar el último sobre anterior al corte como saldo de arranque.
            $stmt=$this->db->prepare("
                SELECT amount,month_start
                FROM monthly_cash_envelopes
                WHERE month_start<?
                ORDER BY month_start DESC
                LIMIT 1
            ");
            $stmt->execute([$cutoff]);
            $baseline=$stmt->fetch();
            $baseAmount=$baseline?(float)$baseline['amount']:0.0;

            $stmt=$this->db->prepare("
                SELECT COALESCE(SUM(CASE
                    WHEN m.movement_type IN ('INGRESO','AJUSTE_ENTRADA') THEN m.amount
                    WHEN m.movement_type IN ('EGRESO','AJUSTE_SALIDA') THEN -m.amount
                    ELSE 0 END),0)
                FROM finance_account_movements m
                INNER JOIN finance_accounts a ON a.id=m.account_id
                WHERE a.code='EFECTIVO'
                  AND m.movement_date>=?
                  AND m.movement_date<=?
            ");
            $stmt->execute([$cutoff,$throughDate]);
            return $baseAmount+(float)$stmt->fetchColumn();
        } catch(Throwable $e) {
            error_log('No se pudo calcular ahorro desde Sobres: '.$e->getMessage());
            return (float)(financeGetAccountBalances($this->db,$throughDate)['EFECTIVO']??0);
        }
    }

    private function resolveAdvisorPeriod($startInput,$endInput): array
    {
        if($startInput && $endInput && $this->validDate($startInput) && $this->validDate($endInput) && $startInput<=$endInput){
            return [$startInput,$endInput];
        }

        $today=new DateTime('today');
        $day=(int)$today->format('j');
        if($day<=15){
            $start=$today->format('Y-m-01');
            $end=$today->format('Y-m-15');
        }else{
            $start=$today->format('Y-m-16');
            $end=$today->format('Y-m-t');
        }
        return [$start,$end];
    }

    private function financeCsrfToken(): string
    {
        if(empty($_SESSION['finance_flow_csrf']))$_SESSION['finance_flow_csrf']=bin2hex(random_bytes(24));
        return (string)$_SESSION['finance_flow_csrf'];
    }

    private function verifyFinanceCsrf(string $redirect): void
    {
        $posted=(string)($_POST['csrf_token']??'');
        $known=(string)($_SESSION['finance_flow_csrf']??'');
        if($known==='' || $posted==='' || !hash_equals($known,$posted)){
            $_SESSION['error']='La sesión expiró. Recargue la página e intente nuevamente.';
            $this->redirect($redirect);
        }
    }

    private function storeEvidence(array $file): string
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('No se pudo adjuntar la evidencia.');
        if(($file['size']??0)>10*1024*1024)throw new RuntimeException('La evidencia no puede superar 10 MB.');
        $ext=strtolower(pathinfo($file['name']??'',PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','webp','pdf'],true))throw new RuntimeException('La evidencia debe ser JPG, PNG, WEBP o PDF.');

        $rel='storage/finance/expense-evidence/'.date('Y/m');
        $dir=ROOT_PATH.'/'.$rel;
        if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
            throw new RuntimeException('No se pudo preparar la carpeta de evidencias.');
        }
        $name='egreso-'.date('YmdHis').'-'.bin2hex(random_bytes(5)).'.'.$ext;
        if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('No se pudo guardar la evidencia.');
        return $rel.'/'.$name;
    }

    private function validDate(string $date): bool
    {
        $d=DateTime::createFromFormat('Y-m-d',$date);
        return $d && $d->format('Y-m-d')===$date;
    }
}
