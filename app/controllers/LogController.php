<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class LogController extends BaseController {
    
    public function index() {
        $this->requireRole([ROLE_ADMIN]);
        
        $logFile = ROOT_PATH . '/error.log';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 50;
        $search = $_GET['search'] ?? '';
        $level = $_GET['level'] ?? '';
        
        $logs = [];
        $total = 0;
        
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Más recientes primero
            
            // Filtrar logs
            if (!empty($search) || !empty($level)) {
                $lines = array_filter($lines, function($line) use ($search, $level) {
                    $matches = true;
                    
                    if (!empty($search)) {
                        $matches = $matches && (stripos($line, $search) !== false);
                    }
                    
                    if (!empty($level)) {
                        $matches = $matches && (stripos($line, $level) !== false);
                    }
                    
                    return $matches;
                });
            }
            
            $total = count($lines);
            
            // Paginar
            $offset = ($page - 1) * $perPage;
            $lines = array_slice($lines, $offset, $perPage);
            
            // Parsear logs
            foreach ($lines as $line) {
                $logs[] = $this->parseLogLine($line);
            }
        }
        
        $totalPages = ceil($total / $perPage);
        
        $this->view('logs/index', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'level' => $level,
            'logFile' => $logFile
        ]);
    }
    
    private function parseLogLine($line) {
        // Intentar parsear el formato de log de PHP
        $pattern = '/^\[([^\]]+)\] (.+)$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'date' => $matches[1] ?? '',
                'message' => $matches[2] ?? $line,
                'level' => $this->detectLogLevel($line),
                'raw' => $line
            ];
        }
        
        return [
            'date' => '',
            'message' => $line,
            'level' => 'info',
            'raw' => $line
        ];
    }
    
    private function detectLogLevel($line) {
        $lineLower = strtolower($line);
        
        if (strpos($lineLower, 'error') !== false || strpos($lineLower, 'fatal') !== false) {
            return 'error';
        } elseif (strpos($lineLower, 'warning') !== false || strpos($lineLower, 'warn') !== false) {
            return 'warning';
        } elseif (strpos($lineLower, 'notice') !== false) {
            return 'notice';
        }
        
        return 'info';
    }
    
    public function clear() {
        $this->requireRole([ROLE_ADMIN]);
        
        $logFile = ROOT_PATH . '/error.log';
        
        if (file_exists($logFile)) {
            // Crear backup antes de limpiar
            $backupFile = ROOT_PATH . '/error_backup_' . date('Y-m-d_H-i-s') . '.log';
            copy($logFile, $backupFile);
            
            // Limpiar archivo
            file_put_contents($logFile, '');
            
            $_SESSION['success'] = 'Log limpiado exitosamente. Backup creado: ' . basename($backupFile);
        } else {
            $_SESSION['error'] = 'Archivo de log no encontrado';
        }
        
        $this->redirect('/logs');
    }
    
    public function download() {
        $this->requireRole([ROLE_ADMIN]);
        
        $logFile = ROOT_PATH . '/error.log';
        
        if (file_exists($logFile)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="error_log_' . date('Y-m-d_H-i-s') . '.log"');
            header('Content-Length: ' . filesize($logFile));
            readfile($logFile);
            exit;
        } else {
            $_SESSION['error'] = 'Archivo de log no encontrado';
            $this->redirect('/logs');
        }
    }
}
