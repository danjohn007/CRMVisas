<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class IncomeExpenseController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);

        try {
            $q=trim((string)($_GET['q']??''));
            $advisorId=(int)($_GET['advisor_id']??0);
            $state=strtoupper(trim((string)($_GET['state']??'')));
            if(!in_array($state,['','PENDIENTE','PARCIAL','LIQUIDADO'],true))$state='';

            $balances=financeGetAccountBalances($this->db);

            $stmt=$this->db->query("
                SELECT s.*,u.full_name advisor_name,
                       COALESCE(SUM(CASE WHEN p.validation_status='PENDIENTE' THEN p.amount ELSE 0 END),0) pending_amount,
                       COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) validated_amount
                FROM income_sales s
                LEFT JOIN users u ON u.id=s.advisor_id
                LEFT JOIN income_payments p ON p.sale_id=s.id
                WHERE s.approval_status='PENDIENTE'
                  AND s.financial_status<>'CANCELADO'
                GROUP BY s.id
                ORDER BY s.contract_date,s.id
            ");
            $pendingSales=$stmt->fetchAll();

            $pendingSalePayments=[];
            if($pendingSales){
                $ids=array_map(fn($x)=>(int)$x['id'],$pendingSales);
                $ph=implode(',',array_fill(0,count($ids),'?'));
                $stmt=$this->db->prepare("
                    SELECT p.*,s.sale_folio
                    FROM income_payments p
                    INNER JOIN income_sales s ON s.id=p.sale_id
                    WHERE p.validation_status='PENDIENTE'
                      AND p.sale_id IN ($ph)
                    ORDER BY p.id
                ");
                $stmt->execute($ids);
                foreach($stmt->fetchAll() as $p){
                    $pendingSalePayments[(int)$p['sale_id']][]=$p;
                }
            }

            $stmt=$this->db->query("
                SELECT p.*,s.sale_folio,s.client_name_snapshot,s.client_folio_snapshot,
                       s.service_name_snapshot,s.total_amount,
                       r.full_name registered_by_name,a.full_name advisor_name,
                       pc.concept payment_concept
                FROM income_payments p
                INNER JOIN income_sales s ON s.id=p.sale_id
                LEFT JOIN users r ON r.id=p.registered_by
                LEFT JOIN users a ON a.id=s.advisor_id
                LEFT JOIN income_payment_concepts pc ON pc.code=p.payment_concept_code
                WHERE p.validation_status='PENDIENTE'
                  AND s.approval_status='VALIDADA'
                  AND s.financial_status<>'CANCELADO'
                ORDER BY p.payment_date,p.id
            ");
            $pendingPayments=$stmt->fetchAll();

            $where=["s.financial_status<>'CANCELADO'","s.approval_status='VALIDADA'"];
            $params=[];
            if($advisorId>0){$where[]='s.advisor_id=?';$params[]=$advisorId;}
            if($state!==''){$where[]='s.financial_status=?';$params[]=$state;}
            if($q!==''){
                $where[]="(s.client_name_snapshot LIKE ? OR s.client_folio_snapshot LIKE ? OR s.sale_folio LIKE ? OR s.service_name_snapshot LIKE ?)";
                $like='%'.$q.'%';
                array_push($params,$like,$like,$like,$like);
            }

            $stmt=$this->db->prepare("
                SELECT s.*,u.full_name advisor_name,
                       COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) validated_paid,
                       COALESCE(SUM(CASE WHEN p.validation_status='PENDIENTE' THEN p.amount ELSE 0 END),0) pending_validation,
                       MAX(CASE WHEN p.validation_status='VALIDADO' THEN p.payment_date END) last_payment_date
                FROM income_sales s
                LEFT JOIN users u ON u.id=s.advisor_id
                LEFT JOIN income_payments p ON p.sale_id=s.id
                WHERE ".implode(' AND ',$where)."
                GROUP BY s.id
                ORDER BY s.contract_date DESC,s.id DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            $sales=$stmt->fetchAll();

            $stmt=$this->db->query("
                SELECT p.*,s.sale_folio,s.client_name_snapshot,s.client_folio_snapshot,
                       s.service_name_snapshot,v.full_name validated_by_name
                FROM income_payments p
                INNER JOIN income_sales s ON s.id=p.sale_id
                LEFT JOIN users v ON v.id=p.validated_by
                WHERE p.validation_status='VALIDADO'
                ORDER BY p.validated_at DESC,p.id DESC
                LIMIT 30
            ");
            $recentPayments=$stmt->fetchAll();

            $advisors=$this->db->query("
                SELECT id,COALESCE(NULLIF(full_name,''),username) full_name
                FROM users WHERE role='Asesor' AND is_active=1
                ORDER BY full_name
            ")->fetchAll();

            $catalog=$this->db->query("SELECT * FROM income_catalog ORDER BY category,code")->fetchAll();
            $isCatalogAdmin=$this->getUserRole()===ROLE_ADMIN;
            $catalogCsrfToken=$this->catalogCsrfToken();
            $approvalCsrfToken=$this->approvalCsrfToken();

            $receivables=0.0;
            foreach($sales as $s){
                $receivables+=max(0,(float)$s['total_amount']-(float)$s['validated_paid']);
            }

            $this->view('income-expense/index',compact(
                'balances','pendingSales','pendingSalePayments','pendingPayments','sales','recentPayments',
                'advisors','catalog','isCatalogAdmin','catalogCsrfToken','approvalCsrfToken','q','advisorId','state','receivables'
            ));
        } catch(Throwable $e) {
            error_log('Control de Ingresos V5: '.$e->getMessage());
            $_SESSION['error']='No se pudo cargar Control de Ingresos. Verifique la migración V5.';
            $this->redirect('/dashboard');
        }
    }

    public function approveSale($id)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $this->verifyApprovalCsrf();
        $id=(int)$id;

        try {
            $this->db->beginTransaction();

            $stmt=$this->db->prepare("SELECT * FROM income_sales WHERE id=? LIMIT 1 FOR UPDATE");
            $stmt->execute([$id]);
            $sale=$stmt->fetch();
            if(!$sale) throw new RuntimeException('Venta no encontrada.');
            if($sale['approval_status']!=='PENDIENTE') throw new RuntimeException('La venta ya fue revisada.');

            $stmt=$this->db->prepare("
                SELECT * FROM income_payments
                WHERE sale_id=? AND validation_status='PENDIENTE'
                ORDER BY id FOR UPDATE
            ");
            $stmt->execute([$id]);
            $payments=$stmt->fetchAll();

            foreach($payments as $p){
                $method=financialNormalizePaymentMethod($p['payment_method']);
                if(!in_array($method,['EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA'],true)){
                    throw new RuntimeException('La venta contiene un método de pago no permitido en V5.');
                }

                $receiver=financePaymentMethodAccountLabel($method);
                $this->db->prepare("
                    UPDATE income_payments
                    SET validation_status='VALIDADO',receiver_account=?,cash_envelope_id=NULL,
                        validated_by=?,validated_at=NOW(),validation_notes='Aprobado con la venta',updated_at=NOW()
                    WHERE id=?
                ")->execute([$receiver,$_SESSION['user_id'],$p['id']]);

                incomeSyncValidatedPaymentToLegacy($this->db,(int)$p['id']);
            }

            $this->db->prepare("
                UPDATE income_sales
                SET approval_status='VALIDADA',approved_by=?,approved_at=NOW(),rejection_reason=NULL,updated_at=NOW()
                WHERE id=?
            ")->execute([$_SESSION['user_id'],$id]);

            incomeRefreshSaleStatus($this->db,$id);
            $this->db->commit();

            logAdminControlEvent('control_ingresos','venta_aprobada','Venta aprobada: '.$sale['sale_folio'],[
                'application_id'=>$sale['application_id']?:null,'entity_type'=>'income_sale','entity_id'=>$id,'priority'=>'alta',
                'metadata'=>[
                    'folio_cliente'=>$sale['client_folio_snapshot'],'folio_venta'=>$sale['sale_folio'],
                    'cliente'=>$sale['client_name_snapshot'],'servicio'=>$sale['service_name_snapshot'],
                    'total'=>(float)$sale['total_amount'],'pagos_validados'=>count($payments)
                ]
            ]);

            $_SESSION['success']='Venta '.$sale['sale_folio'].' aprobada. Los pagos fueron enviados a sus cuentas correspondientes.';
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/control-ingresos');
    }

    public function rejectSale($id)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $this->verifyApprovalCsrf();
        $id=(int)$id;
        $reason=trim((string)($_POST['reason']??''));

        try {
            if($reason==='') throw new RuntimeException('Indique el motivo del rechazo.');

            $this->db->beginTransaction();
            $stmt=$this->db->prepare("SELECT * FROM income_sales WHERE id=? LIMIT 1 FOR UPDATE");
            $stmt->execute([$id]);
            $sale=$stmt->fetch();
            if(!$sale) throw new RuntimeException('Venta no encontrada.');
            if($sale['approval_status']!=='PENDIENTE') throw new RuntimeException('La venta ya fue revisada.');

            $this->db->prepare("
                UPDATE income_sales
                SET approval_status='RECHAZADA',financial_status='CANCELADO',
                    approved_by=?,approved_at=NOW(),rejection_reason=?,updated_at=NOW()
                WHERE id=?
            ")->execute([$_SESSION['user_id'],$reason,$id]);

            $this->db->prepare("
                UPDATE income_payments
                SET validation_status='RECHAZADO',validated_by=?,validated_at=NOW(),
                    validation_notes=?,updated_at=NOW()
                WHERE sale_id=? AND validation_status='PENDIENTE'
            ")->execute([$_SESSION['user_id'],$reason,$id]);

            if(!empty($sale['application_id'])) incomeRefreshApplicationFinancialStatus($this->db,(int)$sale['application_id']);
            $this->db->commit();

            logAdminControlEvent('control_ingresos','venta_rechazada','Venta rechazada: '.$sale['sale_folio'],[
                'application_id'=>$sale['application_id']?:null,'entity_type'=>'income_sale','entity_id'=>$id,'priority'=>'alta',
                'metadata'=>['motivo'=>$reason,'folio_venta'=>$sale['sale_folio']]
            ]);

            $_SESSION['success']='Venta rechazada. No se considerará en ventas ni cuentas.';
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/control-ingresos');
    }

    public function validatePayment($id)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $this->verifyApprovalCsrf();
        $id=(int)$id;
        $notes=trim((string)($_POST['validation_notes']??''));

        try {
            $this->db->beginTransaction();

            $stmt=$this->db->prepare("
                SELECT p.*,s.approval_status,s.total_amount,s.client_name_snapshot,s.sale_folio
                FROM income_payments p
                INNER JOIN income_sales s ON s.id=p.sale_id
                WHERE p.id=? LIMIT 1 FOR UPDATE
            ");
            $stmt->execute([$id]);
            $p=$stmt->fetch();
            if(!$p) throw new RuntimeException('Pago no encontrado.');
            if($p['validation_status']!=='PENDIENTE') throw new RuntimeException('Este pago ya fue revisado.');
            if($p['approval_status']!=='VALIDADA') throw new RuntimeException('Primero debe aprobarse la venta.');
            if(!financialCanWriteDate($p['payment_date'],$this->db)){
                throw new RuntimeException('La semana de este pago está cerrada.');
            }

            $method=financialNormalizePaymentMethod($p['payment_method']);
            if(!in_array($method,['EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA'],true)){
                throw new RuntimeException('Método de pago no permitido.');
            }

            $totals=incomeGetSaleTotals($this->db,(int)$p['sale_id']);
            if((float)$p['amount']-$totals['balance']>0.009){
                throw new RuntimeException('El pago supera el saldo pendiente.');
            }

            $receiver=financePaymentMethodAccountLabel($method);
            $this->db->prepare("
                UPDATE income_payments
                SET validation_status='VALIDADO',receiver_account=?,cash_envelope_id=NULL,
                    validated_by=?,validated_at=NOW(),validation_notes=?,updated_at=NOW()
                WHERE id=?
            ")->execute([
                $receiver,$_SESSION['user_id'],$notes!==''?$notes:null,$id
            ]);

            incomeSyncValidatedPaymentToLegacy($this->db,$id);
            incomeRefreshSaleStatus($this->db,(int)$p['sale_id']);
            financialLogCorrectionIfNeeded($p['payment_date'],'ingreso_validado',$id,[
                'cliente'=>$p['client_name_snapshot'],'monto'=>(float)$p['amount'],'cuenta'=>$receiver
            ],$this->db);

            $this->db->commit();

            logAdminControlEvent('control_ingresos','pago_validado','Abono validado: '.$p['sale_folio'],[
                'application_id'=>$p['application_id']?:null,'entity_type'=>'income_payment','entity_id'=>$id,'priority'=>'alta',
                'metadata'=>['monto'=>(float)$p['amount'],'metodo'=>$method,'cuenta'=>$receiver]
            ]);

            $_SESSION['success']='Pago validado. El dinero se agregó a '.$receiver.'.';
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/control-ingresos');
    }

    public function rejectPayment($id)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $this->verifyApprovalCsrf();
        $id=(int)$id;
        $reason=trim((string)($_POST['reason']??''));

        try {
            if($reason==='') throw new RuntimeException('Indique el motivo del rechazo.');

            $stmt=$this->db->prepare("
                SELECT p.*,s.client_name_snapshot,s.sale_folio
                FROM income_payments p INNER JOIN income_sales s ON s.id=p.sale_id
                WHERE p.id=? LIMIT 1
            ");
            $stmt->execute([$id]);
            $p=$stmt->fetch();
            if(!$p || $p['validation_status']!=='PENDIENTE') throw new RuntimeException('El pago ya fue revisado o no existe.');

            $this->db->prepare("
                UPDATE income_payments
                SET validation_status='RECHAZADO',validated_by=?,validated_at=NOW(),
                    validation_notes=?,updated_at=NOW()
                WHERE id=?
            ")->execute([$_SESSION['user_id'],$reason,$id]);

            financeSyncValidatedIncomePayment($this->db,$id);
            incomeRefreshSaleStatus($this->db,(int)$p['sale_id']);

            logAdminControlEvent('control_ingresos','pago_rechazado','Pago rechazado: '.$p['sale_folio'],[
                'application_id'=>$p['application_id']?:null,'entity_type'=>'income_payment','entity_id'=>$id,'priority'=>'alta',
                'metadata'=>['motivo'=>$reason,'monto'=>(float)$p['amount']]
            ]);
            $_SESSION['success']='Pago rechazado. No afectó las cuentas.';
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/control-ingresos');
    }

    // Compatibilidad: en V5 los abonos los reporta la asesora.
    public function addPayment($saleId)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $_SESSION['error']='En V5 los abonos de cliente los reporta la asesora desde su vista de ventas y Gerencia sólo los valida o rechaza.';
        $this->redirect('/control-ingresos');
    }

    public function proof($id)
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $stmt=$this->db->prepare("SELECT proof_path FROM income_payments WHERE id=? LIMIT 1");
        $stmt->execute([(int)$id]);
        $this->sendProof((string)($stmt->fetchColumn()?:''));
    }

    public function createCatalogItem()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->verifyCatalogCsrf();

        $category=trim((string)($_POST['category']??''));
        $concept=trim((string)($_POST['concept']??''));
        $priceMode=strtoupper(trim((string)($_POST['price_mode']??'FIJO')));
        $status=strtoupper(trim((string)($_POST['catalog_status']??'ACTIVO')));
        $channel=strtoupper(trim((string)($_POST['sale_channel']??'SOLICITUD')));
        $notes=trim((string)($_POST['notes']??''));
        $rawPrice=trim((string)($_POST['authorized_price']??''));
        $price=$rawPrice===''?null:parseCurrencyInput($rawPrice);
        $lock='crmvisas_income_catalog_code';
        $got=false;

        try {
            $this->validateCatalogInput($category,$concept,$priceMode,$status,$channel,$price);
            $stmt=$this->db->prepare("SELECT GET_LOCK(?,8)");$stmt->execute([$lock]);$got=(int)$stmt->fetchColumn()===1;
            if(!$got) throw new RuntimeException('No se pudo reservar el código del catálogo.');

            $last=(int)($this->db->query("
                SELECT MAX(CAST(SUBSTRING(code,5) AS UNSIGNED))
                FROM income_catalog WHERE code REGEXP '^ING-[0-9]+$'
            ")->fetchColumn()?:0);
            $code='ING-'.str_pad((string)($last+1),3,'0',STR_PAD_LEFT);
            $isActive=$status==='ACTIVO'?1:0;

            $stmt=$this->db->prepare("
                INSERT INTO income_catalog
                  (code,category,concept,authorized_price,price_mode,is_active,catalog_status,sale_channel,notes)
                VALUES(?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([$code,$category,$concept,$price,$priceMode,$isActive,$status,$channel,$notes!==''?$notes:null]);

            $_SESSION['success']="Concepto $code creado.";
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
        } finally {
            if($got){try{$stmt=$this->db->prepare("SELECT RELEASE_LOCK(?)");$stmt->execute([$lock]);}catch(Throwable $e){}}
        }
        $this->redirect('/control-ingresos#catalogo-ingresos');
    }

    public function updateCatalogItem($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->verifyCatalogCsrf();

        $id=(int)$id;
        $category=trim((string)($_POST['category']??''));
        $concept=trim((string)($_POST['concept']??''));
        $priceMode=strtoupper(trim((string)($_POST['price_mode']??'FIJO')));
        $status=strtoupper(trim((string)($_POST['catalog_status']??'ACTIVO')));
        $channel=strtoupper(trim((string)($_POST['sale_channel']??'SOLICITUD')));
        $notes=trim((string)($_POST['notes']??''));
        $rawPrice=trim((string)($_POST['authorized_price']??''));
        $price=$rawPrice===''?null:parseCurrencyInput($rawPrice);

        try {
            $this->validateCatalogInput($category,$concept,$priceMode,$status,$channel,$price);
            $isActive=$status==='ACTIVO'?1:0;
            $stmt=$this->db->prepare("
                UPDATE income_catalog
                SET category=?,concept=?,authorized_price=?,price_mode=?,is_active=?,
                    catalog_status=?,sale_channel=?,notes=?,updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$category,$concept,$price,$priceMode,$isActive,$status,$channel,$notes!==''?$notes:null,$id]);
            $_SESSION['success']='Concepto actualizado.';
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
        }
        $this->redirect('/control-ingresos#catalogo-ingresos');
    }

    public function changeCatalogStatus($id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->verifyCatalogCsrf();

        $id=(int)$id;
        $status=strtoupper(trim((string)($_POST['catalog_status']??'')));
        try {
            if(!in_array($status,['ACTIVO','SUSPENDIDO','INACTIVO'],true))throw new RuntimeException('Estado no válido.');
            $stmt=$this->db->prepare("SELECT * FROM income_catalog WHERE id=? LIMIT 1");$stmt->execute([$id]);$item=$stmt->fetch();
            if(!$item)throw new RuntimeException('Concepto no encontrado.');
            if($status==='ACTIVO'
                && ($item['price_mode']??'FIJO')==='FIJO'
                && ($item['authorized_price']===null || (float)$item['authorized_price']<=0)){
                throw new RuntimeException('Un concepto de precio FIJO necesita un precio mayor a $0 para activarse.');
            }
            $this->db->prepare("UPDATE income_catalog SET catalog_status=?,is_active=?,updated_at=NOW() WHERE id=?")
                ->execute([$status,$status==='ACTIVO'?1:0,$id]);
            $_SESSION['success']='Estado actualizado a '.$status.'.';
        } catch(Throwable $e) {
            $_SESSION['error']=$e->getMessage();
        }
        $this->redirect('/control-ingresos#catalogo-ingresos');
    }

    // Rutas históricas.
    public function export()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $_SESSION['success']='Los reportes consolidados se consultan ahora en Resumen mensual.';
        $this->redirect('/resumen-mensual');
    }

    public function storeIncome()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $_SESSION['error']='Los ingresos se originan desde Hoja de Información o Registrar ventas y después se validan aquí.';
        $this->redirect('/control-ingresos');
    }

    public function storeExpense()
    {
        $this->requireRole([ROLE_ADMIN,ROLE_GERENTE]);
        $_SESSION['error']='Los egresos se registran en el módulo Egresos.';
        $this->redirect('/egresos');
    }

    private function approvalCsrfToken(): string
    {
        if(empty($_SESSION['income_approval_csrf'])){
            $_SESSION['income_approval_csrf']=bin2hex(random_bytes(24));
        }
        return (string)$_SESSION['income_approval_csrf'];
    }

    private function verifyApprovalCsrf(): void
    {
        $posted=(string)($_POST['csrf_token']??'');
        $known=(string)($_SESSION['income_approval_csrf']??'');
        if($known==='' || $posted==='' || !hash_equals($known,$posted)){
            $_SESSION['error']='La sesión de validación expiró. Recargue Control de Ingresos.';
            $this->redirect('/control-ingresos');
        }
    }

    private function validateCatalogInput(string $category,string $concept,string $priceMode,string $status,string $channel,?float $price): void
    {
        if($category===''||$concept==='')throw new RuntimeException('Categoría y concepto son obligatorios.');
        if(!in_array($priceMode,['FIJO','POR_CONFIRMAR','SEGUN_DIFERENCIA','SEGUN_COTIZACION'],true))throw new RuntimeException('Modalidad no válida.');
        if(!in_array($status,['ACTIVO','SUSPENDIDO','INACTIVO'],true))throw new RuntimeException('Estado no válido.');
        if(!in_array($channel,['SOLICITUD','MANUAL','AMBOS'],true))throw new RuntimeException('Canal de venta no válido.');
        if($price!==null && $price<0)throw new RuntimeException('El precio no puede ser negativo.');
        if($status==='ACTIVO' && $priceMode==='FIJO' && ($price===null || $price<=0)){
            throw new RuntimeException('Un concepto de precio FIJO necesita un precio mayor a $0.');
        }
    }

    private function catalogCsrfToken(): string
    {
        if(empty($_SESSION['income_catalog_csrf']))$_SESSION['income_catalog_csrf']=bin2hex(random_bytes(24));
        return (string)$_SESSION['income_catalog_csrf'];
    }

    private function verifyCatalogCsrf(): void
    {
        $posted=(string)($_POST['csrf_token']??'');
        $known=(string)($_SESSION['income_catalog_csrf']??'');
        if($known===''||$posted===''||!hash_equals($known,$posted)){
            $_SESSION['error']='La sesión del catálogo expiró.';
            $this->redirect('/control-ingresos#catalogo-ingresos');
        }
    }

    private function sendProof(string $path): void
    {
        if($path===''||strpos($path,'..')!==false){http_response_code(404);exit('Comprobante no encontrado.');}
        $full=ROOT_PATH.'/'.ltrim($path,'/');
        if(!is_file($full)){http_response_code(404);exit('Comprobante no encontrado.');}
        $ext=strtolower(pathinfo($full,PATHINFO_EXTENSION));
        $types=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'];
        header('Content-Type: '.($types[$ext]??'application/octet-stream'));
        header('Content-Disposition: inline; filename="'.basename($full).'"');
        header('Cache-Control: private, no-store');
        readfile($full);exit;
    }
}
