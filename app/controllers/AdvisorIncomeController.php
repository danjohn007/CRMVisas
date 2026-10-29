<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AdvisorIncomeController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ASESOR]);

        try {
            // En esta vista sólo aparecen servicios de captura manual:
            // copias, actas, impresiones, actualizaciones y otros servicios rápidos.
            $catalog = $this->db->query("
                SELECT * FROM income_catalog
                WHERE is_active=1
                  AND catalog_status='ACTIVO'
                  AND sale_channel IN ('MANUAL','AMBOS')
                  AND authorized_price IS NOT NULL
                ORDER BY category, code
            ")->fetchAll();

            $stmt = $this->db->prepare("
                SELECT id, folio, client_name, type, subtype, status, updated_at
                FROM applications
                WHERE created_by=?
                ORDER BY updated_at DESC, id DESC
                LIMIT 500
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $applications = $stmt->fetchAll();

            $stmt = $this->db->prepare("
                SELECT id,client_folio,full_name,phone,email
                FROM income_quick_clients
                WHERE created_by=?
                ORDER BY updated_at DESC,id DESC
                LIMIT 500
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $quickClients = $stmt->fetchAll();

            // V5.5: listado de ventas con filtros y paginación real del lado del servidor.
            // Se permiten únicamente 20, 30 o 50 elementos por página.
            $allowedPerPage = [20,30,50];
            $salesPerPage = (int)($_GET['per_page'] ?? 20);
            if (!in_array($salesPerPage, $allowedPerPage, true)) {
                $salesPerPage = 20;
            }

            $salesPage = max(1, (int)($_GET['page'] ?? 1));
            $salesDateFrom = trim((string)($_GET['date_from'] ?? ''));
            $salesDateTo = trim((string)($_GET['date_to'] ?? ''));

            if ($salesDateFrom !== '' && !$this->validDate($salesDateFrom)) {
                $salesDateFrom = '';
            }
            if ($salesDateTo !== '' && !$this->validDate($salesDateTo)) {
                $salesDateTo = '';
            }
            if ($salesDateFrom !== '' && $salesDateTo !== '' && $salesDateFrom > $salesDateTo) {
                [$salesDateFrom, $salesDateTo] = [$salesDateTo, $salesDateFrom];
            }

            $salesWhere = [
                's.advisor_id=?',
                "(s.financial_status<>'CANCELADO' OR s.approval_status='RECHAZADA')"
            ];
            $salesParams = [(int)$_SESSION['user_id']];

            if ($salesDateFrom !== '') {
                $salesWhere[] = 's.contract_date>=?';
                $salesParams[] = $salesDateFrom;
            }
            if ($salesDateTo !== '') {
                $salesWhere[] = 's.contract_date<=?';
                $salesParams[] = $salesDateTo;
            }

            $whereSql = implode(' AND ', $salesWhere);

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM income_sales s WHERE {$whereSql}");
            $stmt->execute($salesParams);
            $salesTotalRows = (int)$stmt->fetchColumn();

            $salesTotalPages = max(1, (int)ceil($salesTotalRows / $salesPerPage));
            if ($salesPage > $salesTotalPages) {
                $salesPage = $salesTotalPages;
            }
            $salesOffset = ($salesPage - 1) * $salesPerPage;

            // LIMIT/OFFSET se interpolan sólo después de sanitizarse como enteros permitidos.
            $stmt = $this->db->prepare("
                SELECT s.*,
                       COALESCE(SUM(CASE WHEN p.validation_status='VALIDADO' THEN p.amount ELSE 0 END),0) validated_paid,
                       COALESCE(SUM(CASE WHEN p.validation_status='PENDIENTE' THEN p.amount ELSE 0 END),0) pending_validation,
                       MAX(CASE WHEN p.validation_status='VALIDADO' THEN p.payment_date END) last_payment_date
                FROM income_sales s
                LEFT JOIN income_payments p ON p.sale_id=s.id
                WHERE {$whereSql}
                GROUP BY s.id
                ORDER BY s.contract_date DESC,s.id DESC
                LIMIT {$salesPerPage} OFFSET {$salesOffset}
            ");
            $stmt->execute($salesParams);
            $sales = $stmt->fetchAll();

            $salesFirstRow = $salesTotalRows > 0 ? $salesOffset + 1 : 0;
            $salesLastRow = min($salesOffset + $salesPerPage, $salesTotalRows);

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM income_sales WHERE advisor_id=? AND approval_status='RECHAZADA'");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $rejectedSalesCount = (int)$stmt->fetchColumn();

            $editSale = null;
            $editSalePayments = [];
            $editSaleId = max(0, (int)($_GET['edit_sale'] ?? 0));
            if ($editSaleId > 0) {
                $stmt = $this->db->prepare("
                    SELECT * FROM income_sales
                    WHERE id=? AND advisor_id=? AND approval_status='RECHAZADA' AND sale_origin='MANUAL'
                    LIMIT 1
                ");
                $stmt->execute([$editSaleId,(int)$_SESSION['user_id']]);
                $editSale = $stmt->fetch() ?: null;
                if ($editSale) {
                    $stmt = $this->db->prepare("
                        SELECT payment_method,amount
                        FROM income_payments
                        WHERE sale_id=? AND validation_status='RECHAZADO'
                        ORDER BY id
                    ");
                    $stmt->execute([$editSaleId]);
                    $editSalePayments = $stmt->fetchAll();
                }
            }

            $preselectedApplicationId = (int)($_GET['application_id'] ?? 0);
            $advisorPaymentCsrfToken = $this->advisorPaymentCsrfToken();

            $this->view('advisor-income/index', compact(
                'catalog','applications','quickClients','sales',
                'preselectedApplicationId','advisorPaymentCsrfToken',
                'salesPerPage','salesPage','salesDateFrom','salesDateTo',
                'salesTotalRows','salesTotalPages','salesFirstRow','salesLastRow',
                'rejectedSalesCount','editSale','editSalePayments'
            ));
        } catch (Throwable $e) {
            error_log('Registro manual de ventas asesor: '.$e->getMessage());
            $_SESSION['error']='No se pudo cargar Registrar ventas. Verifique que la migración V5 esté instalada.';
            $this->redirect('/dashboard');
        }
    }

    public function storeSale()
    {
        $this->requireRole([ROLE_ASESOR]);
        if ($_SERVER['REQUEST_METHOD']!=='POST') $this->redirect('/ingresos');
        $this->verifyAdvisorPaymentCsrf();

        $clientSource=trim((string)($_POST['client_source']??''));
        $catalogId=(int)($_POST['catalog_id']??0);
        $quantity=max(1,(int)($_POST['quantity']??1));
        $contractDate=trim((string)($_POST['contract_date']??date('Y-m-d')));
        $followupDate=trim((string)($_POST['next_followup_date']??''));
        $notes=trim((string)($_POST['notes']??''));
        $editSaleId=max(0,(int)($_POST['edit_sale_id']??0));

        $paymentAmounts=$_POST['payment_amount']??[];
        $paymentMethods=$_POST['payment_method']??[];
        $paymentSplitMode=(int)($_POST['payment_split_mode']??0)===1;
        if(!is_array($paymentAmounts))$paymentAmounts=[$paymentAmounts];
        if(!is_array($paymentMethods))$paymentMethods=[$paymentMethods];

        $proofPaths=[];
        $preparedPayments=[];
        $saleLockName='crmvisas_income_sale_folio_'.date('Y');
        $saleLock=false;
        $quickLockName='crmvisas_quick_client_folio_'.date('Y');
        $quickLock=false;

        try {
            if($catalogId<=0 || !$this->validDate($contractDate)){
                throw new RuntimeException('Seleccione el servicio y una fecha válida.');
            }
            if($contractDate>date('Y-m-d')){
                throw new RuntimeException('La fecha de venta no puede estar en el futuro.');
            }
            if($followupDate!=='' && !$this->validDate($followupDate)){
                throw new RuntimeException('La fecha de seguimiento no es válida.');
            }

            $stmt=$this->db->prepare("
                SELECT * FROM income_catalog
                WHERE id=? AND is_active=1 AND catalog_status='ACTIVO'
                  AND sale_channel IN ('MANUAL','AMBOS')
                  AND authorized_price IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$catalogId]);
            $catalog=$stmt->fetch();
            if(!$catalog) throw new RuntimeException('Este concepto no está disponible para venta manual.');

            $applicationId=null;
            $quickClientId=null;
            $clientName='';
            $clientFolio='';

            if(strpos($clientSource,'APP:')===0){
                $applicationId=(int)substr($clientSource,4);
                $stmt=$this->db->prepare("
                    SELECT id,folio,client_name
                    FROM applications
                    WHERE id=? AND created_by=?
                    LIMIT 1
                ");
                $stmt->execute([$applicationId,$_SESSION['user_id']]);
                $client=$stmt->fetch();
                if(!$client) throw new RuntimeException('El expediente seleccionado no pertenece a su cartera.');
                $clientName=trim((string)$client['client_name']);
                $clientFolio=$client['folio'];
            } elseif(strpos($clientSource,'QUICK:')===0){
                $quickClientId=(int)substr($clientSource,6);
                $stmt=$this->db->prepare("
                    SELECT * FROM income_quick_clients
                    WHERE id=? AND created_by=?
                    LIMIT 1
                ");
                $stmt->execute([$quickClientId,$_SESSION['user_id']]);
                $client=$stmt->fetch();
                if(!$client) throw new RuntimeException('El cliente rápido seleccionado no pertenece a su cartera.');
                $clientName=$client['full_name'];
                $clientFolio=$client['client_folio'];
            } elseif($clientSource==='NEW'){
                $clientName=trim((string)($_POST['new_client_name']??''));
                if($clientName==='') throw new RuntimeException('Escriba el nombre del nuevo cliente.');

                $stmt=$this->db->prepare("SELECT GET_LOCK(?,8)");
                $stmt->execute([$quickLockName]);
                $quickLock=(int)$stmt->fetchColumn()===1;
                if(!$quickLock) throw new RuntimeException('No se pudo reservar el folio del nuevo cliente. Intente de nuevo.');

                $year=date('Y');
                $stmt=$this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(client_folio,-6) AS UNSIGNED))
                    FROM income_quick_clients
                    WHERE client_folio LIKE ?
                ");
                $stmt->execute(["CLI-$year-%"]);
                $last=(int)($stmt->fetchColumn()?:0);
                $clientFolio=sprintf('CLI-%s-%06d',$year,$last+1);

                $stmt=$this->db->prepare("
                    INSERT INTO income_quick_clients(client_folio,full_name,phone,email,created_by)
                    VALUES(?,?,NULL,NULL,?)
                ");
                $stmt->execute([$clientFolio,$clientName,$_SESSION['user_id']]);
                $quickClientId=(int)$this->db->lastInsertId();

                $this->releaseLock($quickLockName);
                $quickLock=false;
            } else {
                throw new RuntimeException('Seleccione un cliente existente o registre uno nuevo.');
            }

            // El precio de una venta rápida SIEMPRE sale del Catálogo Maestro.
            // La asesora nunca captura ni altera el precio unitario.
            $unitPrice=(float)$catalog['authorized_price'];
            if($unitPrice<=0){
                throw new RuntimeException('El servicio seleccionado no tiene un precio autorizado válido.');
            }
            $total=round($unitPrice*$quantity,2);

            $initialTotal=0.0;

            if(!$paymentSplitMode){
                // Un solo método = pago completo automático.
                $rawMethod=trim((string)($paymentMethods[0]??''));
                if($rawMethod==='') throw new RuntimeException('Seleccione la forma de pago.');
                $method=financialNormalizePaymentMethod($rawMethod);
                if(!in_array($method,['EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA'],true)){
                    throw new RuntimeException('Los únicos métodos permitidos son Efectivo, Terminal y Transferencia.');
                }
                $preparedPayments[]=[
                    'amount'=>$total,
                    'method'=>$method,
                    'reference'=>'',
                    'proof'=>null,
                ];
                $initialTotal=$total;
            } else {
                foreach($paymentAmounts as $i=>$rawAmount){
                    $amount=parseCurrencyInput($rawAmount);
                    $rawMethod=trim((string)($paymentMethods[$i]??''));

                    // Filas completamente vacías se ignoran.
                    if($amount<=0 && $rawMethod==='') continue;
                    if($amount<=0) throw new RuntimeException('Capture el importe de cada forma de pago.');
                    if($rawMethod==='') throw new RuntimeException('Seleccione el método de cada importe.');

                    $method=financialNormalizePaymentMethod($rawMethod);
                    if(!in_array($method,['EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA'],true)){
                        throw new RuntimeException('Los únicos métodos permitidos son Efectivo, Terminal y Transferencia.');
                    }

                    $preparedPayments[]=[
                        'amount'=>$amount,
                        'method'=>$method,
                        'reference'=>'',
                        'proof'=>null,
                    ];
                    $initialTotal+=$amount;
                }

                if(!$preparedPayments){
                    throw new RuntimeException('Registre las formas de pago de la venta.');
                }

                if(abs($initialTotal-$total)>0.009){
                    throw new RuntimeException(
                        'La suma de las formas de pago debe ser exactamente igual al total de la venta: $'.number_format($total,2).'.'
                    );
                }
            }

            if($editSaleId<=0){
                $stmt=$this->db->prepare("SELECT GET_LOCK(?,8)");
                $stmt->execute([$saleLockName]);
                $saleLock=(int)$stmt->fetchColumn()===1;
                if(!$saleLock) throw new RuntimeException('No se pudo reservar el folio de venta.');
            }

            $this->db->beginTransaction();

            if($editSaleId>0){
                $stmt=$this->db->prepare("
                    SELECT * FROM income_sales
                    WHERE id=? AND advisor_id=? AND approval_status='RECHAZADA' AND sale_origin='MANUAL'
                    LIMIT 1 FOR UPDATE
                ");
                $stmt->execute([$editSaleId,(int)$_SESSION['user_id']]);
                $rejectedSale=$stmt->fetch();
                if(!$rejectedSale) throw new RuntimeException('La venta rechazada ya no está disponible para corrección.');

                $saleId=(int)$rejectedSale['id'];
                $saleFolio=(string)$rejectedSale['sale_folio'];

                $stmt=$this->db->prepare("
                    UPDATE income_sales SET
                      application_id=?,quick_client_id=?,catalog_id=?,catalog_code=?,
                      client_name_snapshot=?,client_folio_snapshot=?,service_name_snapshot=?,
                      quantity=?,unit_price=?,total_amount=?,contract_date=?,next_followup_date=?,
                      financial_status='PENDIENTE',approval_status='PENDIENTE',rejection_reason=NULL,
                      approved_by=NULL,approved_at=NULL,notes=?,updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([
                    $applicationId,$quickClientId,$catalogId,$catalog['code'],$clientName,$clientFolio,$catalog['concept'],
                    $quantity,$unitPrice,$total,$contractDate,$followupDate!==''?$followupDate:null,
                    $notes!==''?$notes:null,$saleId
                ]);

                $this->db->prepare("
                    DELETE FROM income_payments
                    WHERE sale_id=? AND validation_status IN ('RECHAZADO','PENDIENTE')
                ")->execute([$saleId]);
            } else {
                $year=date('Y');
                $stmt=$this->db->prepare("
                    SELECT MAX(CAST(SUBSTRING(sale_folio,-6) AS UNSIGNED))
                    FROM income_sales WHERE sale_folio LIKE ?
                ");
                $stmt->execute(["VTA-$year-%"]);
                $last=(int)($stmt->fetchColumn()?:0);
                $saleFolio=sprintf('VTA-%s-%06d',$year,$last+1);

                $stmt=$this->db->prepare("
                    INSERT INTO income_sales
                      (sale_folio,application_id,quick_client_id,catalog_id,catalog_code,sale_origin,
                       client_name_snapshot,client_folio_snapshot,service_name_snapshot,advisor_id,
                       quantity,unit_price,total_amount,contract_date,next_followup_date,
                       financial_status,approval_status,notes,created_by)
                    VALUES (?,?,?,?,?,'MANUAL',?,?,?,?,?,?,?,?,?,'PENDIENTE','PENDIENTE',?,?)
                ");
                $stmt->execute([
                    $saleFolio,$applicationId,$quickClientId,$catalogId,$catalog['code'],
                    $clientName,$clientFolio,$catalog['concept'],$_SESSION['user_id'],
                    $quantity,$unitPrice,$total,$contractDate,$followupDate!==''?$followupDate:null,
                    $notes!==''?$notes:null,$_SESSION['user_id']
                ]);
                $saleId=(int)$this->db->lastInsertId();
            }

            if($preparedPayments){
                $conceptCode=abs($initialTotal-$total)<=0.009?'COB-004':'COB-001';
                $stmt=$this->db->prepare("
                    INSERT INTO income_payments
                      (sale_id,application_id,quick_client_id,payment_concept_code,amount,payment_method,
                       reference,proof_path,notes,payment_date,validation_status,registered_by,source_type,source_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,'PENDIENTE',?,'MANUAL_SALE',?)
                ");
                foreach($preparedPayments as $payment){
                    $stmt->execute([
                        $saleId,$applicationId,$quickClientId,$conceptCode,$payment['amount'],$payment['method'],
                        $payment['reference']!==''?$payment['reference']:null,$payment['proof'],
                        $notes!==''?$notes:null,$contractDate,$_SESSION['user_id'],$saleId
                    ]);
                }
            }

            incomeRefreshSaleStatus($this->db,$saleId);
            $this->db->commit();

            $this->releaseLock($saleLockName);
            $saleLock=false;

            logAdminControlEvent('control_ingresos',$editSaleId>0?'venta_reenviada':'venta_registrada',
                ($editSaleId>0?'Venta corregida y reenviada por asesora: ':'Venta manual reportada por asesora: ').$clientName,[
                'application_id'=>$applicationId,
                'entity_type'=>'venta_ingreso','entity_id'=>$saleId,'priority'=>'alta',
                'metadata'=>[
                    'folio_venta'=>$saleFolio,'folio_cliente'=>$clientFolio,'concepto'=>$catalog['concept'],
                    'cantidad'=>$quantity,'total'=>$total,'pago_reportado'=>$initialTotal,'estado'=>'PENDIENTE'
                ]
            ]);

            $_SESSION['success']=$editSaleId>0
                ? 'Venta '.$saleFolio.' corregida y reenviada a Gerencia para revisión.'
                : 'Venta '.$saleFolio.' registrada. Se generó el ticket de compra y quedó pendiente de aprobación de Gerencia.';
            $this->redirect('/ingresos/ticket/' . $saleId . '?print=1');
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            if($saleLock)$this->releaseLock($saleLockName);
            if($quickLock)$this->releaseLock($quickLockName);
            foreach($proofPaths as $path){
                if($path && is_file(ROOT_PATH.'/'.$path))@unlink(ROOT_PATH.'/'.$path);
            }
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/ingresos');
    }

    public function storeInstallment($saleId)
    {
        $this->requireRole([ROLE_ASESOR]);
        if($_SERVER['REQUEST_METHOD']!=='POST')$this->redirect('/ingresos');
        $this->verifyAdvisorPaymentCsrf();

        $saleId=(int)$saleId;
        $amount=parseCurrencyInput($_POST['amount']??0);
        $paymentDate=trim((string)($_POST['payment_date']??date('Y-m-d')));
        $rawMethod=trim((string)($_POST['payment_method']??''));
        $reference='';
        $notes=trim((string)($_POST['notes']??''));
        $followupDate=trim((string)($_POST['next_followup_date']??''));
        $proofPath=null;

        try {
            if($saleId<=0 || $amount<=0) throw new RuntimeException('Indique un abono mayor a $0.');
            if(!$this->validDate($paymentDate) || $paymentDate>date('Y-m-d')){
                throw new RuntimeException('La fecha del pago no es válida.');
            }
            if(!financialCanWriteDate($paymentDate,$this->db)){
                throw new RuntimeException('La semana de esa fecha ya está cerrada.');
            }
            if($rawMethod==='') throw new RuntimeException('Seleccione el método de pago.');
            $method=financialNormalizePaymentMethod($rawMethod);
            if(!in_array($method,['EFECTIVO','TERMINAL_TARJETA','TRANSFERENCIA'],true)){
                throw new RuntimeException('Los únicos métodos permitidos son Efectivo, Terminal y Transferencia.');
            }
            if($followupDate!=='' && !$this->validDate($followupDate)){
                throw new RuntimeException('La fecha de seguimiento no es válida.');
            }

            if(!empty($_FILES['payment_proof']['name'])){
                $proofPath=$this->storeProof($_FILES['payment_proof'],'abono-asesor');
            }

            $this->db->beginTransaction();

            $stmt=$this->db->prepare("
                SELECT * FROM income_sales
                WHERE id=? AND advisor_id=? AND financial_status<>'CANCELADO'
                LIMIT 1 FOR UPDATE
            ");
            $stmt->execute([$saleId,$_SESSION['user_id']]);
            $sale=$stmt->fetch();
            if(!$sale) throw new RuntimeException('La venta no existe o no pertenece a su cartera.');
            if($sale['approval_status']!=='VALIDADA'){
                throw new RuntimeException('Gerencia debe aprobar primero la venta antes de registrar nuevos abonos.');
            }

            $stmt=$this->db->prepare("
                SELECT
                  COALESCE(SUM(CASE WHEN validation_status='VALIDADO' THEN amount ELSE 0 END),0) validated_paid,
                  COALESCE(SUM(CASE WHEN validation_status='PENDIENTE' THEN amount ELSE 0 END),0) pending_paid
                FROM income_payments WHERE sale_id=?
            ");
            $stmt->execute([$saleId]);
            $totals=$stmt->fetch()?:['validated_paid'=>0,'pending_paid'=>0];
            $available=max(0,(float)$sale['total_amount']-(float)$totals['validated_paid']-(float)$totals['pending_paid']);

            if($available<=0.009) throw new RuntimeException('No hay saldo disponible para reportar.');
            if($amount-$available>0.009){
                throw new RuntimeException('El abono supera el saldo disponible: $'.number_format($available,2));
            }

            $conceptCode=abs($amount-$available)<=0.009?'COB-003':'COB-002';

            $stmt=$this->db->prepare("
                INSERT INTO income_payments
                  (sale_id,application_id,quick_client_id,payment_concept_code,amount,payment_method,
                   reference,proof_path,notes,payment_date,validation_status,registered_by,source_type,source_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,'PENDIENTE',?,'ADVISOR_INSTALLMENT',?)
            ");
            $stmt->execute([
                $saleId,$sale['application_id']?:null,$sale['quick_client_id']?:null,$conceptCode,$amount,$method,
                $reference!==''?$reference:null,$proofPath,$notes!==''?$notes:null,$paymentDate,
                $_SESSION['user_id'],$saleId
            ]);
            $paymentId=(int)$this->db->lastInsertId();

            if($followupDate!==''){
                $this->db->prepare("UPDATE income_sales SET next_followup_date=?,updated_at=NOW() WHERE id=?")
                    ->execute([$followupDate,$saleId]);
            }

            incomeRefreshSaleStatus($this->db,$saleId);
            $this->db->commit();

            logAdminControlEvent('control_ingresos','abono_reportado_asesora','Abono reportado por asesora: '.$sale['client_name_snapshot'],[
                'application_id'=>$sale['application_id']?:null,
                'entity_type'=>'income_payment','entity_id'=>$paymentId,'priority'=>'alta',
                'metadata'=>[
                    'folio_cliente'=>$sale['client_folio_snapshot'],'folio_venta'=>$sale['sale_folio'],
                    'monto'=>$amount,'metodo'=>$method,'estado'=>'PENDIENTE'
                ]
            ]);

            $_SESSION['success']='Abono de $'.number_format($amount,2).' reportado. Gerencia debe validarlo.';
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            if($proofPath && is_file(ROOT_PATH.'/'.$proofPath))@unlink(ROOT_PATH.'/'.$proofPath);
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('/ingresos');
    }

    public function ticket($id)
    {
        $this->requireRole([ROLE_ASESOR,ROLE_ADMIN,ROLE_GERENTE]);

        $saleId=(int)$id;
        $stmt=$this->db->prepare("
            SELECT s.*,u.full_name advisor_name
            FROM income_sales s
            LEFT JOIN users u ON u.id=s.advisor_id
            WHERE s.id=? AND s.sale_origin='MANUAL'
            LIMIT 1
        ");
        $stmt->execute([$saleId]);
        $sale=$stmt->fetch();

        if(!$sale){
            http_response_code(404);
            exit('Ticket no encontrado.');
        }

        if($this->getUserRole()===ROLE_ASESOR && (int)$sale['advisor_id']!==(int)$_SESSION['user_id']){
            http_response_code(403);
            exit('No tiene permisos para consultar este ticket.');
        }

        $stmt=$this->db->prepare("
            SELECT payment_method,amount,validation_status,payment_date
            FROM income_payments
            WHERE sale_id=?
            ORDER BY id
        ");
        $stmt->execute([$saleId]);
        $payments=$stmt->fetchAll();

        $breakdown=['EFECTIVO'=>0.0,'TERMINAL_TARJETA'=>0.0,'TRANSFERENCIA'=>0.0];
        $reported=0.0;
        foreach($payments as $payment){
            $method=financialNormalizePaymentMethod($payment['payment_method']);
            if(isset($breakdown[$method])) $breakdown[$method]+=(float)$payment['amount'];
            $reported+=(float)$payment['amount'];
        }

        $pending=max(0,(float)$sale['total_amount']-$reported);
        $autoPrint=($_GET['print']??'')==='1';
        $this->view('advisor-income/ticket',compact(
            'sale','payments','breakdown','reported','pending','autoPrint'
        ));
    }

    public function proof($id)
    {
        $this->requireRole([ROLE_ASESOR]);
        $stmt=$this->db->prepare("
            SELECT p.proof_path
            FROM income_payments p
            INNER JOIN income_sales s ON s.id=p.sale_id
            WHERE p.id=? AND s.advisor_id=? LIMIT 1
        ");
        $stmt->execute([(int)$id,$_SESSION['user_id']]);
        $this->sendProof((string)($stmt->fetchColumn()?:''));
    }

    private function advisorPaymentCsrfToken(): string
    {
        if(empty($_SESSION['advisor_payment_csrf'])){
            $_SESSION['advisor_payment_csrf']=bin2hex(random_bytes(24));
        }
        return (string)$_SESSION['advisor_payment_csrf'];
    }

    private function verifyAdvisorPaymentCsrf(): void
    {
        $posted=(string)($_POST['csrf_token']??'');
        $known=(string)($_SESSION['advisor_payment_csrf']??'');
        if($known==='' || $posted==='' || !hash_equals($known,$posted)){
            $_SESSION['error']='La sesión expiró. Recargue la página e intente nuevamente.';
            $this->redirect('/ingresos');
        }
    }

    private function releaseLock(string $lockName): void
    {
        try {
            $stmt=$this->db->prepare("SELECT RELEASE_LOCK(?)");
            $stmt->execute([$lockName]);
        } catch(Throwable $e) {}
    }

    private function fileAt(array $files,int $index): array
    {
        return [
            'name'=>$files['name'][$index]??'',
            'type'=>$files['type'][$index]??'',
            'tmp_name'=>$files['tmp_name'][$index]??'',
            'error'=>$files['error'][$index]??UPLOAD_ERR_NO_FILE,
            'size'=>$files['size'][$index]??0,
        ];
    }

    private function storeProof(array $file,string $prefix): string
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('No se pudo adjuntar el comprobante.');
        if(($file['size']??0)>10*1024*1024)throw new RuntimeException('El comprobante no puede superar 10 MB.');
        $ext=strtolower(pathinfo($file['name']??'',PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','pdf'],true))throw new RuntimeException('El comprobante debe ser JPG, PNG o PDF.');

        $dirRel='storage/finance/payment-proofs/'.date('Y/m');
        $dir=ROOT_PATH.'/'.$dirRel;
        if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)){
            throw new RuntimeException('No fue posible preparar la carpeta de comprobantes.');
        }

        $name=$prefix.'-'.date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$ext;
        if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)){
            throw new RuntimeException('No fue posible guardar el comprobante.');
        }
        return $dirRel.'/'.$name;
    }

    private function sendProof(string $path): void
    {
        if($path==='' || strpos($path,'..')!==false){http_response_code(404);exit('Comprobante no encontrado.');}
        $full=ROOT_PATH.'/'.ltrim($path,'/');
        if(!is_file($full)){http_response_code(404);exit('Comprobante no encontrado.');}
        $ext=strtolower(pathinfo($full,PATHINFO_EXTENSION));
        $types=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
        header('Content-Type: '.($types[$ext]??'application/octet-stream'));
        header('Content-Disposition: inline; filename="'.basename($full).'"');
        readfile($full);exit;
    }

    private function validDate(string $date): bool
    {
        $d=DateTime::createFromFormat('Y-m-d',$date);
        return $d && $d->format('Y-m-d')===$date;
    }
}
