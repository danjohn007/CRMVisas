<?php
class Router {
    private $routes = [];
    
    public function __construct() {
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        // Rutas de Autenticación
        $this->routes['GET']['/'] = ['AuthController', 'login'];
        $this->routes['GET']['/login'] = ['AuthController', 'login'];
        $this->routes['POST']['/login'] = ['AuthController', 'authenticate'];
        $this->routes['GET']['/logout'] = ['AuthController', 'logout'];
        
        // Dashboard
        $this->routes['GET']['/dashboard'] = ['DashboardController', 'index'];

        // WhatsApp híbrido - Administrador ve todo; asesoras solo handoffs pendientes/asignados
        $this->routes['GET']['/whatsapp'] = ['WhatsAppController', 'index'];
        $this->routes['GET']['/whatsapp/api/lista'] = ['WhatsAppController', 'listJson'];
        $this->routes['GET']['/whatsapp/api/conversacion/{id}'] = ['WhatsAppController', 'conversationJson'];
        $this->routes['POST']['/whatsapp/tomar/{id}'] = ['WhatsAppController', 'take'];
        $this->routes['POST']['/whatsapp/enviar/{id}'] = ['WhatsAppController', 'send'];
        $this->routes['POST']['/whatsapp/plantilla/{id}'] = ['WhatsAppController', 'sendTemplate'];
        $this->routes['POST']['/whatsapp/devolver-bot/{id}'] = ['WhatsAppController', 'release'];
        $this->routes['POST']['/whatsapp/cerrar/{id}'] = ['WhatsAppController', 'close'];
        $this->routes['POST']['/whatsapp/crear-solicitud/{id}'] = ['WhatsAppController', 'createApplication'];

        // Centro de Control interno
        $this->routes['GET']['/centro-control'] = ['AdminControlController', 'index'];
        $this->routes['POST']['/centro-control/marcar-leido'] = ['AdminControlController', 'markRead'];
        $this->routes['POST']['/centro-control/marcar-todo-leido'] = ['AdminControlController', 'markAllRead'];
        
        // Usuarios
        $this->routes['GET']['/usuarios'] = ['UserController', 'index'];
        $this->routes['GET']['/usuarios/crear'] = ['UserController', 'create'];
        $this->routes['POST']['/usuarios/guardar'] = ['UserController', 'store'];
        $this->routes['GET']['/usuarios/editar/{id}'] = ['UserController', 'edit'];
        $this->routes['POST']['/usuarios/actualizar/{id}'] = ['UserController', 'update'];
        $this->routes['POST']['/usuarios/eliminar/{id}'] = ['UserController', 'delete'];
        
        // Constructor de Formularios
        $this->routes['GET']['/formularios'] = ['FormController', 'index'];
        $this->routes['GET']['/formularios/crear'] = ['FormController', 'create'];
        $this->routes['POST']['/formularios/guardar'] = ['FormController', 'store'];
        $this->routes['GET']['/formularios/editar/{id}'] = ['FormController', 'edit'];
        $this->routes['POST']['/formularios/actualizar/{id}'] = ['FormController', 'update'];
        $this->routes['POST']['/formularios/eliminar/{id}'] = ['FormController', 'delete'];
        $this->routes['POST']['/formularios/publicar/{id}'] = ['FormController', 'publish'];
        
        $this->routes['POST']['/solicitudes/guardar-hoja-info/{id}'] = ['ApplicationController', 'saveInfoSheet'];
        $this->routes['POST']['/solicitudes/guardar-familiar/{id}'] = ['ApplicationController', 'saveFamiliar'];
        $this->routes['POST']['/solicitudes/eliminar-familiar/{id}'] = ['ApplicationController', 'deleteFamiliar'];
        $this->routes['POST']['/solicitudes/marcar-asistencia/{id}'] = ['ApplicationController', 'markClientAttended'];
        $this->routes['POST']['/solicitudes/vincular-formulario/{id}'] = ['ApplicationController', 'linkForm'];
        $this->routes['POST']['/solicitudes/guardar-cita-oficina/{id}'] = ['ApplicationController', 'saveOfficeAppointment'];
        $this->routes['POST']['/solicitudes/guardar-respuestas/{id}'] = ['ApplicationController', 'saveFormResponses'];
        $this->routes['POST']['/solicitudes/confirmar-respuestas/{id}'] = ['ApplicationController', 'confirmFormResponses'];
        
        // Solicitudes
        $this->routes['GET']['/solicitudes'] = ['ApplicationController', 'index'];
        $this->routes['GET']['/solicitudes/crear'] = ['ApplicationController', 'create'];
        $this->routes['POST']['/solicitudes/crear'] = ['ApplicationController', 'store'];
        $this->routes['GET']['/solicitudes/ver/{id}'] = ['ApplicationController', 'show'];
        $this->routes['GET']['/solicitudes/nota-remision/{id}'] = ['ApplicationController', 'remission'];
        $this->routes['GET']['/solicitudes/editar/{id}'] = ['ApplicationController', 'edit'];
        $this->routes['POST']['/solicitudes/actualizar/{id}'] = ['ApplicationController', 'update'];
        $this->routes['POST']['/solicitudes/cambiar-estatus/{id}'] = ['ApplicationController', 'changeStatus'];
        $this->routes['POST']['/solicitudes/subir-documento/{id}'] = ['ApplicationController', 'uploadDocument'];
        $this->routes['POST']['/solicitudes/agregar-indicacion/{id}'] = ['ApplicationController', 'addNote'];
        $this->routes['GET']['/solicitudes/descargar-archivo/{id}/{fieldId}'] = ['ApplicationController', 'downloadFormFile'];
        $this->routes['GET']['/solicitudes/descargar-documento/{docId}'] = ['ApplicationController', 'downloadDocument'];
        $this->routes['GET']['/solicitudes/ver-documento/{docId}'] = ['ApplicationController', 'viewDocument'];
        $this->routes['POST']['/solicitudes/eliminar/{id}'] = ['ApplicationController', 'delete'];
        
        // Traslados - control operativo independiente de Finanzas
        $this->routes['GET']['/traslados'] = ['TransferController', 'index'];
        $this->routes['POST']['/traslados/crear'] = ['TransferController', 'create'];
        $this->routes['GET']['/traslados/ver/{id}'] = ['TransferController', 'show'];
        $this->routes['POST']['/traslados/cliente'] = ['TransferController', 'storeClient'];
        $this->routes['POST']['/traslados/cliente/actualizar/{id}'] = ['TransferController', 'updateClient'];
        $this->routes['POST']['/traslados/cliente/listo/{id}'] = ['TransferController', 'toggleReady'];
        $this->routes['POST']['/traslados/cliente/eliminar/{id}'] = ['TransferController', 'deleteClient'];
        $this->routes['POST']['/traslados/cerrar/{id}'] = ['TransferController', 'close'];
        $this->routes['POST']['/traslados/reabrir/{id}'] = ['TransferController', 'reopen'];
        $this->routes['POST']['/traslados/eliminar/{id}'] = ['TransferController', 'delete'];

        // Módulo Financiero
        $this->routes['GET']['/financiero'] = ['FinancialController', 'index'];
        $this->routes['GET']['/financiero/solicitud/{id}'] = ['FinancialController', 'show'];
        $this->routes['POST']['/financiero/agregar-costo/{id}'] = ['FinancialController', 'addCost'];
        $this->routes['POST']['/financiero/registrar-pago/{id}'] = ['FinancialController', 'registerPayment'];

        // Balance Semanal de INGRESOS (lunes a viernes)
        $this->routes['GET']['/balance-semanal'] = ['WeeklyBalanceController', 'index'];
        $this->routes['POST']['/balance-semanal/cerrar'] = ['WeeklyBalanceController', 'closeWeek'];
        $this->routes['POST']['/balance-semanal/correccion/{id}'] = ['WeeklyBalanceController', 'authorizeCorrection'];
        $this->routes['GET']['/balance-semanal/reporte'] = ['WeeklyBalanceController', 'report'];

        // Control administrativo de Ingresos
        $this->routes['GET']['/control-ingresos'] = ['IncomeExpenseController', 'index'];
        $this->routes['POST']['/control-ingresos/aprobar-venta/{id}'] = ['IncomeExpenseController', 'approveSale'];
        $this->routes['POST']['/control-ingresos/rechazar-venta/{id}'] = ['IncomeExpenseController', 'rejectSale'];
        $this->routes['GET']['/ingresos-egresos'] = ['IncomeExpenseController', 'index']; // compatibilidad
        $this->routes['POST']['/control-ingresos/validar/{id}'] = ['IncomeExpenseController', 'validatePayment'];
        $this->routes['POST']['/control-ingresos/rechazar/{id}'] = ['IncomeExpenseController', 'rejectPayment'];
        $this->routes['POST']['/control-ingresos/abono/{id}'] = ['IncomeExpenseController', 'addPayment'];
        $this->routes['GET']['/control-ingresos/comprobante/{id}'] = ['IncomeExpenseController', 'proof'];
        $this->routes['GET']['/control-ingresos/exportar'] = ['IncomeExpenseController', 'export'];
        $this->routes['POST']['/control-ingresos/catalogo/crear'] = ['IncomeExpenseController', 'createCatalogItem'];
        $this->routes['POST']['/control-ingresos/catalogo/editar/{id}'] = ['IncomeExpenseController', 'updateCatalogItem'];
        $this->routes['POST']['/control-ingresos/catalogo/estado/{id}'] = ['IncomeExpenseController', 'changeCatalogStatus'];

        // Rutas anteriores conservadas sólo para compatibilidad.
        $this->routes['POST']['/ingresos-egresos/nuevo-ingreso'] = ['IncomeExpenseController', 'storeIncome'];
        $this->routes['POST']['/ingresos-egresos/nuevo-egreso'] = ['IncomeExpenseController', 'storeExpense'];

        // Registro de ventas por Asesor
        $this->routes['GET']['/ingresos'] = ['AdvisorIncomeController', 'index'];
        $this->routes['POST']['/ingresos/venta'] = ['AdvisorIncomeController', 'storeSale'];
        $this->routes['GET']['/ingresos/ticket/{id}'] = ['AdvisorIncomeController', 'ticket'];
        $this->routes['POST']['/ingresos/abono/{id}'] = ['AdvisorIncomeController', 'storeInstallment'];
        $this->routes['GET']['/ingresos/comprobante/{id}'] = ['AdvisorIncomeController', 'proof'];

        // Ventas quincenales / productividad de la asesora
        $this->routes['GET']['/ventas-quincenales'] = ['FinanceFlowController', 'advisorFortnight'];
        $this->routes['POST']['/ventas-quincenales/horas-extra'] = ['FinanceFlowController', 'storeOvertime'];

        // Egresos - consulta Admin/Gerente, captura exclusivamente Gerencia
        $this->routes['GET']['/egresos'] = ['FinanceFlowController', 'expenses'];
        $this->routes['POST']['/egresos/registrar'] = ['FinanceFlowController', 'storeExpense'];
        $this->routes['GET']['/egresos/evidencia/{id}'] = ['FinanceFlowController', 'expenseEvidence'];

        // Resumen mensual con detalle por servicio
        $this->routes['GET']['/resumen-mensual'] = ['FinanceFlowController', 'monthly'];

        // Sobres históricos de efectivo - exclusivo Administrador
        $this->routes['GET']['/sobres'] = ['CashEnvelopeController', 'index'];
        $this->routes['POST']['/sobres/guardar'] = ['CashEnvelopeController', 'store'];
        $this->routes['POST']['/sobres/eliminar/{id}'] = ['CashEnvelopeController', 'delete'];
        
        // Reportes
        $this->routes['GET']['/reportes'] = ['ReportController', 'index'];
        $this->routes['GET']['/reportes/exportar'] = ['ReportController', 'export'];
        
        // Configuración
        $this->routes['GET']['/configuracion'] = ['ConfigController', 'index'];
        $this->routes['POST']['/configuracion/guardar'] = ['ConfigController', 'save'];
        $this->routes['POST']['/configuracion/sucursales/guardar'] = ['ConfigController', 'saveSucursal'];
        $this->routes['POST']['/configuracion/sucursales/eliminar/{id}'] = ['ConfigController', 'deleteSucursal'];
        
        // Logs de Errores
        $this->routes['GET']['/logs'] = ['LogController', 'index'];
        $this->routes['POST']['/logs/limpiar'] = ['LogController', 'clear'];
        $this->routes['GET']['/logs/descargar'] = ['LogController', 'download'];
        
        // Auditoría
        $this->routes['GET']['/auditoria'] = ['AuditController', 'index'];
        
        // Customer Journey
        $this->routes['GET']['/customer-journey/{id}'] = ['CustomerJourneyController', 'show'];
        $this->routes['POST']['/customer-journey/agregar/{id}'] = ['CustomerJourneyController', 'addTouchpoint'];
        
        // Public Forms (no authentication required)
        $this->routes['GET']['/form/{token}'] = ['PublicFormController', 'show'];
        $this->routes['POST']['/form/{token}/submit'] = ['PublicFormController', 'submit'];

        // Public solicitudes (for asesoras confirming appointments)
        $this->routes['GET']['/public/solicitudes'] = ['ApplicationController', 'publicSolicitudes'];
        $this->routes['POST']['/solicitudes/confirmar-cita/{id}'] = ['ApplicationController', 'confirmAppointment'];

        // Notifications
        $this->routes['POST']['/notifications/mark-read']   = ['NotificationController', 'markRead'];
        $this->routes['POST']['/notifications/mark-unread'] = ['NotificationController', 'markUnread'];
        
        // Test de Conexión
        $this->routes['GET']['/test-conexion'] = ['TestController', 'connection'];

        // Test de Correo SMTP
        $this->routes['GET']['/test-email']         = ['EmailController', 'testForm'];
        $this->routes['POST']['/test-email/enviar'] = ['EmailController', 'sendTest'];
    }
    
    public function dispatch($uri) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Buscar ruta exacta
        if (isset($this->routes[$method][$uri])) {
            $this->callController($this->routes[$method][$uri]);
            return;
        }
        
        // Buscar ruta con parámetros
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remover el match completo
                $this->callController($handler, $matches);
                return;
            }
        }
        
        // Ruta no encontrada
        http_response_code(404);
        echo "404 - Página no encontrada";
    }
    
    private function callController($handler, $params = []) {
        list($controllerName, $method) = $handler;
        
        $controllerFile = ROOT_PATH . '/app/controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            die("Controlador no encontrado: $controllerName");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            die("Clase del controlador no encontrada: $controllerName");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $method)) {
            die("Método no encontrado: $method en $controllerName");
        }
        
        call_user_func_array([$controller, $method], $params);
    }
}
