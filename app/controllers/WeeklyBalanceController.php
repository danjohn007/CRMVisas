<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class WeeklyBalanceController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);

        try {
            [$weekStart,$weekEnd]=$this->resolveWeek($_GET['week_start']??null);
            $data=$this->buildData($weekStart,$weekEnd,true);
            $data['previousWeek']=date('Y-m-d',strtotime($weekStart.' -7 days'));
            $data['nextWeek']=date('Y-m-d',strtotime($weekStart.' +7 days'));
            $data['isAdmin']=$this->getUserRole()===ROLE_ADMIN;
            $data['canCloseByDate']=date('Y-m-d') >= $weekEnd;
            $data['closeAvailableDate']=$weekEnd;
            $this->view('weekly-balance/index',$data);
        } catch(Throwable $e) {
            error_log('Balance semanal V5: '.$e->getMessage());
            $_SESSION['error']='No se pudo cargar el Balance Semanal. Verifique la migración V5.';
            $this->redirect('/control-ingresos');
        }
    }

    public function closeWeek()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        [$start,$end]=$this->resolveWeek($_POST['week_start']??null);
        $notes=trim((string)($_POST['closing_notes']??''));

        try {
            if(date('Y-m-d')<$end){
                throw new RuntimeException('El balance no puede cerrarse antes del viernes '.date('d/m/Y',strtotime($end)).'.');
            }

            $data=$this->buildData($start,$end,true);
            if(($data['closure']['status']??'')==='CERRADO' && !$this->correctionActive($data['closure'])){
                throw new RuntimeException('Este balance ya está cerrado.');
            }
            if((int)$data['pendingReviewCount']>0){
                throw new RuntimeException('No puede cerrar: existen ventas o pagos pendientes de revisión en el periodo.');
            }

            $s=$data['summary'];
            $b=$data['accountBalances'];

            $stmt=$this->db->prepare("
                UPDATE financial_weekly_closures SET
                  week_end=?,status='CERRADO',
                  total_income=?,total_cash_income=?,total_transfer_income=?,
                  total_card_income=?,total_deposit_income=0,total_other_income=0,
                  total_sales_contracted=?,sales_count=?,payments_count=?,liquidations_count=?,
                  total_expenses=?,weekly_result=?,accounts_receivable=?,
                  cash_account_balance=?,terminal_account_balance=?,transfer_account_balance=?,
                  closing_notes=?,closed_by=?,closed_at=NOW(),
                  correction_authorized_by=NULL,correction_authorized_until=NULL,correction_reason=NULL,
                  updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([
                $end,$s['total_income'],$s['cash'],$s['transfer'],$s['terminal'],
                $s['sales_amount'],$s['sales_count'],$s['payments_count'],$s['liquidations_count'],
                $s['expenses'],$s['net'],$s['accounts_receivable'],
                $b['EFECTIVO'],$b['TERMINAL'],$b['TRANSFERENCIA'],
                $notes!==''?$notes:null,$_SESSION['user_id'],$data['closure']['id']
            ]);

            logAdminControlEvent('balance_semanal','semana_cerrada',"Balance semanal cerrado: $start al $end",[
                'entity_type'=>'balance_semanal','entity_id'=>(int)$data['closure']['id'],'priority'=>'alta',
                'metadata'=>[
                    'ingresos'=>$s['total_income'],'egresos'=>$s['expenses'],'resultado'=>$s['net'],
                    'efectivo'=>$b['EFECTIVO'],'terminal'=>$b['TERMINAL'],'transferencia'=>$b['TRANSFERENCIA']
                ]
            ]);

            $_SESSION['success']='Balance semanal cerrado. Los movimientos quedaron protegidos.';
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/balance-semanal?week_start='.urlencode($start));
    }

    public function authorizeCorrection($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $id=(int)$id;
        $reason=trim((string)($_POST['reason']??''));

        try {
            if($reason==='')throw new RuntimeException('Debe indicar el motivo de la corrección.');
            $stmt=$this->db->prepare("SELECT * FROM financial_weekly_closures WHERE id=? AND status='CERRADO' LIMIT 1");
            $stmt->execute([$id]);
            $closure=$stmt->fetch();
            if(!$closure)throw new RuntimeException('Cierre no encontrado.');

            $until=date('Y-m-d H:i:s',time()+30*60);
            $this->db->prepare("
                UPDATE financial_weekly_closures
                SET correction_authorized_by=?,correction_authorized_until=?,correction_reason=?,updated_at=NOW()
                WHERE id=?
            ")->execute([$_SESSION['user_id'],$until,$reason,$id]);

            $this->db->prepare("
                INSERT INTO financial_correction_log
                  (weekly_closure_id,action_type,entity_type,reason,metadata_json,authorized_by)
                VALUES (?,'AUTORIZACION_CORRECCION','balance_semanal',?,?,?)
            ")->execute([$id,$reason,json_encode(['hasta'=>$until],JSON_UNESCAPED_UNICODE),$_SESSION['user_id']]);

            $_SESSION['success']='Corrección autorizada durante 30 minutos.';
            $this->redirect('/balance-semanal?week_start='.urlencode($closure['week_start']));
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
            $this->redirect('/balance-semanal');
        }
    }

    // Se conserva la ruta histórica, pero la exportación fue retirada de la vista por requisito V5.
    public function report()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $_SESSION['success']='En V5 el Balance Semanal se consulta directamente en pantalla; se retiraron los botones PDF y Excel.';
        $this->redirect('/balance-semanal');
    }

    private function buildData(string $start,string $end,bool $createClosure): array
    {
        $closure=$createClosure?$this->getOrCreateClosure($start,$end):$this->getClosure($start);
        $summary=$this->summary($start,$end);
        $accountBalances=financeGetAccountBalances($this->db,$end);

        $stmt=$this->db->prepare("
            SELECT p.payment_date,p.amount,p.payment_method,p.reference,p.receiver_account,
                   s.sale_folio,s.client_name_snapshot,s.client_folio_snapshot,s.service_name_snapshot,
                   r.full_name registered_by_name,v.full_name validated_by_name
            FROM income_payments p
            INNER JOIN income_sales s ON s.id=p.sale_id
            LEFT JOIN users r ON r.id=p.registered_by
            LEFT JOIN users v ON v.id=p.validated_by
            WHERE p.validation_status='VALIDADO'
              AND p.payment_method IN ('EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA')
              AND p.payment_date BETWEEN ? AND ?
            ORDER BY p.payment_date,p.id
        ");
        $stmt->execute([$start,$end]);
        $incomeMovements=$stmt->fetchAll();

        $stmt=$this->db->prepare("
            SELECT e.*,u.full_name created_by_name
            FROM financial_expenses e
            LEFT JOIN users u ON u.id=e.created_by
            WHERE e.expense_date BETWEEN ? AND ?
            ORDER BY e.expense_date,e.expense_time,e.id
        ");
        $stmt->execute([$start,$end]);
        $expenseMovements=$stmt->fetchAll();

        $stmt=$this->db->prepare("
            SELECT u.full_name advisor_name,
                   COUNT(*) sales_count,
                   SUM(s.quantity) quantity_total,
                   SUM(s.total_amount) sales_amount
            FROM income_sales s
            LEFT JOIN users u ON u.id=s.advisor_id
            WHERE s.approval_status='VALIDADA'
              AND s.financial_status<>'CANCELADO'
              AND s.contract_date BETWEEN ? AND ?
            GROUP BY s.advisor_id,u.full_name
            ORDER BY sales_amount DESC
        ");
        $stmt->execute([$start,$end]);
        $salesByAdvisor=$stmt->fetchAll();

        $stmt=$this->db->prepare("
            SELECT s.service_name_snapshot,
                   SUM(s.quantity) quantity_total,
                   COUNT(*) sales_count,
                   SUM(s.total_amount) sales_amount
            FROM income_sales s
            WHERE s.approval_status='VALIDADA'
              AND s.financial_status<>'CANCELADO'
              AND s.contract_date BETWEEN ? AND ?
            GROUP BY s.service_name_snapshot
            ORDER BY quantity_total DESC,s.service_name_snapshot
        ");
        $stmt->execute([$start,$end]);
        $salesByService=$stmt->fetchAll();

        $receivables=$this->db->query("
            SELECT s.sale_folio,s.client_folio_snapshot,s.client_name_snapshot,s.service_name_snapshot,
                   s.total_amount,
                   COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) paid,
                   s.total_amount-COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) balance
            FROM income_sales s
            LEFT JOIN income_payments p ON p.sale_id=s.id
            WHERE s.approval_status='VALIDADA' AND s.financial_status<>'CANCELADO'
            GROUP BY s.id
            HAVING balance>0.009
            ORDER BY balance DESC,s.client_name_snapshot
        ")->fetchAll();

        $stmt=$this->db->prepare("
            SELECT
              (SELECT COUNT(*) FROM income_sales
               WHERE approval_status='PENDIENTE' AND financial_status<>'CANCELADO'
                 AND contract_date BETWEEN ? AND ?)
              +
              (SELECT COUNT(*) FROM income_payments p
               INNER JOIN income_sales s ON s.id=p.sale_id
               WHERE p.validation_status='PENDIENTE'
                 AND s.approval_status='VALIDADA'
                 AND p.payment_date BETWEEN ? AND ?)
        ");
        $stmt->execute([$start,$end,$start,$end]);
        $pendingReviewCount=(int)$stmt->fetchColumn();

        return [
            'weekStart'=>$start,'weekEnd'=>$end,'closure'=>$closure,'summary'=>$summary,
            'accountBalances'=>$accountBalances,'incomeMovements'=>$incomeMovements,
            'expenseMovements'=>$expenseMovements,'salesByAdvisor'=>$salesByAdvisor,
            'salesByService'=>$salesByService,'receivables'=>$receivables,
            'pendingReviewCount'=>$pendingReviewCount,
        ];
    }

    private function summary(string $start,string $end): array
    {
        $stmt=$this->db->prepare("
            SELECT
              COALESCE(SUM(CASE WHEN payment_method='EFECTIVO' THEN amount ELSE 0 END),0) cash,
              COALESCE(SUM(CASE WHEN payment_method='TERMINAL_TARJETA' THEN amount ELSE 0 END),0) terminal,
              COALESCE(SUM(CASE WHEN payment_method='TRANSFERENCIA' THEN amount ELSE 0 END),0) transfer,
              COALESCE(SUM(amount),0) total_income,
              COUNT(*) payments_count,
              SUM(CASE WHEN payment_concept_code='COB-003' THEN 1 ELSE 0 END) liquidations_count
            FROM income_payments
            WHERE validation_status='VALIDADO'
              AND payment_method IN ('EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA')
              AND payment_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        $s=$stmt->fetch()?:[];

        $stmt=$this->db->prepare("
            SELECT COUNT(*) sales_count,
                   COALESCE(SUM(quantity),0) quantity_total,
                   COALESCE(SUM(total_amount),0) sales_amount
            FROM income_sales
            WHERE approval_status='VALIDADA'
              AND financial_status<>'CANCELADO'
              AND contract_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        $sales=$stmt->fetch()?:[];

        $stmt=$this->db->prepare("
            SELECT COALESCE(SUM(amount),0)
            FROM financial_expenses WHERE expense_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start,$end]);
        $expenses=(float)$stmt->fetchColumn();

        $receivables=(float)$this->db->query("
            SELECT COALESCE(SUM(x.balance),0) FROM (
              SELECT s.id,
                     s.total_amount-COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) balance
              FROM income_sales s
              LEFT JOIN income_payments p ON p.sale_id=s.id
              WHERE s.approval_status='VALIDADA' AND s.financial_status<>'CANCELADO'
              GROUP BY s.id HAVING balance>0
            ) x
        ")->fetchColumn();

        $totalIncome=(float)($s['total_income']??0);

        return array_merge($s,$sales,[
            'expenses'=>$expenses,
            'net'=>$totalIncome-$expenses,
            'accounts_receivable'=>$receivables,
        ]);
    }

    private function getOrCreateClosure(string $start,string $end): array
    {
        $stmt=$this->db->prepare("
            INSERT INTO financial_weekly_closures(week_start,week_end,status)
            VALUES(?,?,'ABIERTO')
            ON DUPLICATE KEY UPDATE week_end=IF(status='ABIERTO',VALUES(week_end),week_end)
        ");
        $stmt->execute([$start,$end]);
        return $this->getClosure($start);
    }

    private function getClosure(string $start): array
    {
        $stmt=$this->db->prepare("
            SELECT w.*,u.full_name closed_by_name
            FROM financial_weekly_closures w
            LEFT JOIN users u ON u.id=w.closed_by
            WHERE w.week_start=? LIMIT 1
        ");
        $stmt->execute([$start]);
        return $stmt->fetch()?:[];
    }

    private function correctionActive(array $closure): bool
    {
        return !empty($closure['correction_authorized_until'])
            && strtotime($closure['correction_authorized_until'])>=time();
    }

    private function resolveWeek($input): array
    {
        try{$d=new DateTime($input?:'today');}catch(Throwable $e){$d=new DateTime('today');}
        $day=(int)$d->format('N');
        if($day!==1)$d->modify('-'.($day-1).' days');
        $start=$d->format('Y-m-d');
        $end=(clone $d)->modify('+4 days')->format('Y-m-d');
        return [$start,$end];
    }
}
