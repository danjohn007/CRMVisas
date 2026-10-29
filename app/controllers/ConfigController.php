<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ConfigController extends BaseController {

    public function index() {
        $this->requireRole([ROLE_ADMIN]);

        try {
            $stmt = $this->db->query("SELECT * FROM global_config ORDER BY config_key ASC");
            $configArray = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $config) {
                $configArray[$config['config_key']] = $config;
            }

            $this->view('config/index', ['configs' => $configArray, 'sucursales' => $this->getSucursales()]);
        } catch (PDOException $e) {
            error_log("Error al cargar configuracion: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar configuracion';
            $this->view('config/index', ['configs' => [], 'sucursales' => []]);
        }
    }

    private function getSucursales(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM sucursales ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // La tabla puede no existir todavia si no se aplico la migracion
            // database/migrations/create_sucursales_table.sql
            error_log("Error al cargar sucursales: " . $e->getMessage());
            return [];
        }
    }

    public function saveSucursal() {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracion');
        }

        $id = (isset($_POST['sucursal_id']) && $_POST['sucursal_id'] !== '') ? (int) $_POST['sucursal_id'] : null;
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $latitud = trim((string) ($_POST['latitud'] ?? ''));
        $longitud = trim((string) ($_POST['longitud'] ?? ''));
        $radio = trim((string) ($_POST['radio_metros'] ?? ''));
        $activo = isset($_POST['activo']) ? 1 : 0;

        try {
            if ($nombre === '') {
                throw new Exception('El nombre de la sucursal es obligatorio');
            }
            if (!is_numeric($latitud) || (float) $latitud < -90 || (float) $latitud > 90) {
                throw new Exception('La latitud de la sucursal no es valida');
            }
            if (!is_numeric($longitud) || (float) $longitud < -180 || (float) $longitud > 180) {
                throw new Exception('La longitud de la sucursal no es valida');
            }
            $radioInt = $this->validateIntegerRange($radio, 1, 100000, 'El radio permitido debe estar entre 1 y 100000 metros');

            if ($id) {
                $stmt = $this->db->prepare("
                    UPDATE sucursales
                    SET nombre = ?, direccion = ?, latitud = ?, longitud = ?, radio_metros = ?, activo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $direccion, $latitud, $longitud, $radioInt, $activo, $id]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO sucursales (nombre, direccion, latitud, longitud, radio_metros, activo)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nombre, $direccion, $latitud, $longitud, $radioInt, $activo]);
            }

            $_SESSION['success'] = 'Sucursal guardada exitosamente';
        } catch (Exception $e) {
            error_log("Error al guardar sucursal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar sucursal: ' . $e->getMessage();
        }

        $this->redirect('/configuracion#section-seguridad');
    }

    public function deleteSucursal($id) {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracion');
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM sucursales WHERE id = ?");
            $stmt->execute([(int) $id]);
            $_SESSION['success'] = 'Sucursal eliminada';
        } catch (PDOException $e) {
            error_log("Error al eliminar sucursal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar sucursal';
        }

        $this->redirect('/configuracion#section-seguridad');
    }

    public function save() {
        $this->requireRole([ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/configuracion');
        }

        try {
            $this->db->beginTransaction();

            $_POST['config_geo_login_enabled'] = isset($_POST['geo_login_enabled_toggle']) ? '1' : '0';
            unset($_POST['geo_login_enabled_toggle']);

            foreach ($_POST as $key => $value) {
                if (strpos($key, 'config_') !== 0) {
                    continue;
                }

                $configKey = str_replace('config_', '', $key);
                $preparedConfig = $this->prepareConfigValue($configKey, $value);
                $configValue = $preparedConfig['value'];
                $configType = $preparedConfig['type'];

                $stmt = $this->db->prepare("
                    INSERT INTO global_config (config_key, config_value, config_type)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE config_value = ?, config_type = ?
                ");
                $stmt->execute([$configKey, $configValue, $configType, $configValue, $configType]);
            }

            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['site_logo'];
                $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                    throw new Exception('Tipo de archivo no permitido para el logo');
                }

                $uploadDir = ROOT_PATH . '/public/uploads/config';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'logo_' . time() . '.' . $fileType;
                $filePath = $uploadDir . '/' . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $relativePath = '/uploads/config/' . $newFileName;

                    $stmt = $this->db->prepare("
                        INSERT INTO global_config (config_key, config_value, config_type)
                        VALUES ('site_logo', ?, 'file')
                        ON DUPLICATE KEY UPDATE config_value = ?
                    ");
                    $stmt->execute([$relativePath, $relativePath]);
                } else {
                    throw new Exception('No se pudo guardar el logo');
                }
            }

            $this->db->commit();
            $_SESSION['success'] = 'Configuracion guardada exitosamente';
            $this->redirect('/configuracion');
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al guardar configuracion: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar configuracion: ' . $e->getMessage();
            $this->redirect('/configuracion');
        }
    }

    private function prepareConfigValue($configKey, $value) {
        $configValue = trim((string) $value);

        switch ($configKey) {
            case 'geo_login_enabled':
                return ['value' => $configValue === '1' ? '1' : '0', 'type' => 'boolean'];

            case 'geo_login_latitude':
                if ($configValue !== '' && (!is_numeric($configValue) || (float) $configValue < -90 || (float) $configValue > 90)) {
                    throw new Exception('La latitud de login geolocalizado no es valida');
                }
                return ['value' => $configValue, 'type' => 'number'];

            case 'geo_login_longitude':
                if ($configValue !== '' && (!is_numeric($configValue) || (float) $configValue < -180 || (float) $configValue > 180)) {
                    throw new Exception('La longitud de login geolocalizado no es valida');
                }
                return ['value' => $configValue, 'type' => 'number'];

            case 'geo_login_radius_meters':
                return ['value' => (string) $this->validateIntegerRange($configValue, 1, 100000, 'El radio permitido debe estar entre 1 y 100000 metros'), 'type' => 'number'];

            case 'login_max_attempts':
                return ['value' => (string) $this->validateIntegerRange($configValue, 1, 20, 'El limite de intentos debe estar entre 1 y 20'), 'type' => 'number'];

            case 'login_lockout_minutes':
                return ['value' => (string) $this->validateIntegerRange($configValue, 1, 1440, 'El bloqueo debe estar entre 1 y 1440 minutos'), 'type' => 'number'];

            case 'session_idle_timeout_minutes':
                return ['value' => (string) $this->validateIntegerRange($configValue, 5, 1440, 'La inactividad debe estar entre 5 y 1440 minutos'), 'type' => 'number'];

            default:
                return ['value' => $configValue, 'type' => 'text'];
        }
    }

    private function validateIntegerRange($value, $min, $max, $message) {
        if (!ctype_digit((string) $value)) {
            throw new Exception($message);
        }

        $intValue = (int) $value;
        if ($intValue < $min || $intValue > $max) {
            throw new Exception($message);
        }

        return $intValue;
    }
}
