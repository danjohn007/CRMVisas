<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuthController extends BaseController {
    private const GEO_DENIED_MESSAGE = 'Error al iniciar sesión: Por favor, contacte al administrador del sistema.';

    public function login() {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login');
    }

    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        $ipAddress = $this->getClientIp();

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Por favor, ingrese usuario y contrasena';
            $this->redirect('/login');
        }

        if ($this->isLoginLocked($username, $ipAddress)) {
            logAudit('login_blocked', 'autenticacion', "Login bloqueado temporalmente para: $username");
            $_SESSION['error'] = 'Demasiados intentos fallidos. Intente nuevamente en ' . max(1, (int) getConfig('login_lockout_minutes', 15)) . ' minutos.';
            $this->redirect('/login');
        }

        if (empty($captcha) || !isset($_SESSION['captcha_answer'])) {
            $this->failLoginAttempt(
                'Por favor, complete la verificacion humana.',
                $username,
                $ipAddress
            );
        }

        if ((int) $captcha !== (int) $_SESSION['captcha_answer']) {
            $this->failLoginAttempt(
                'Verificacion humana incorrecta. Por favor, intente nuevamente.',
                $username,
                $ipAddress,
                "Verificacion humana incorrecta para: $username"
            );
        }

        unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);

        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, full_name, role, is_active
                FROM users
                WHERE (username = :username OR email = :email) AND is_active = 1
            ");
            $stmt->execute(['username' => $username, 'email' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (!$this->validateGeoLogin($user)) {
                    $this->registerFailedLogin($username, $ipAddress);
                    $geoDeniedAudit = $this->buildGeoDeniedAuditContext($user);
                    logAudit('login_geo_denied', 'autenticacion', $geoDeniedAudit['description'], $geoDeniedAudit['metadata']);
                    $_SESSION['error'] = self::GEO_DENIED_MESSAGE;
                    $this->redirect('/login');
                }

                $this->clearFailedLogins($username, $ipAddress);
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                $stmt = $this->db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                logAudit('login', 'autenticacion', "Usuario {$user['full_name']} inicio sesion");

                $this->redirect('/dashboard');
            }

            $this->failLoginAttempt(
                'Usuario o contrasena incorrectos.',
                $username,
                $ipAddress,
                "Intento de inicio de sesion fallido para: $username"
            );
        } catch (PDOException $e) {
            error_log("Error en autenticacion: " . $e->getMessage());
            $_SESSION['error'] = 'Error al iniciar sesion. Por favor, intente nuevamente.';
            $this->redirect('/login');
        }
    }

    public function logout() {
        if (isset($_SESSION['user_name'])) {
            logAudit('logout', 'autenticacion', "Usuario {$_SESSION['user_name']} cerro sesion");
        }

        session_destroy();
        $this->redirect('/login');
    }

    private function getClientIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function normalizeLoginIdentifier(string $username): string {
        return strtolower(trim($username));
    }

    private function isLoginLocked(string $username, string $ipAddress): bool {
        $maxAttempts = max(1, (int) getConfig('login_max_attempts', 5));
        $lockoutMinutes = max(1, (int) getConfig('login_lockout_minutes', 15));

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM login_attempts
                WHERE username = ?
                  AND ip_address = ?
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$lockoutMinutes} MINUTE)
            ");
            $stmt->execute([$this->normalizeLoginIdentifier($username), $ipAddress]);
            return (int) $stmt->fetchColumn() >= $maxAttempts;
        } catch (PDOException $e) {
            error_log("Error checking login attempts: " . $e->getMessage());
            return false;
        }
    }

    private function getRemainingLoginAttempts(string $username, string $ipAddress): int {
        $maxAttempts = max(1, (int) getConfig('login_max_attempts', 5));
        $lockoutMinutes = max(1, (int) getConfig('login_lockout_minutes', 15));

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM login_attempts
                WHERE username = ?
                  AND ip_address = ?
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$lockoutMinutes} MINUTE)
            ");
            $stmt->execute([$this->normalizeLoginIdentifier($username), $ipAddress]);
            return max(0, $maxAttempts - (int) $stmt->fetchColumn());
        } catch (PDOException $e) {
            error_log("Error calculating remaining login attempts: " . $e->getMessage());
            return $maxAttempts;
        }
    }

    private function buildFailedLoginMessage(string $baseMessage, string $username, string $ipAddress): string {
        $remainingAttempts = $this->getRemainingLoginAttempts($username, $ipAddress);
        $lockoutMinutes = max(1, (int) getConfig('login_lockout_minutes', 15));

        if ($remainingAttempts <= 0) {
            return $baseMessage . ' Demasiados intentos fallidos. Intente nuevamente en ' . $lockoutMinutes . ' minutos.';
        }

        return $baseMessage . ' Intentos restantes: ' . $remainingAttempts . '.';
    }

    private function failLoginAttempt(string $baseMessage, string $username, string $ipAddress, ?string $auditMessage = null): void {
        $this->registerFailedLogin($username, $ipAddress);

        if ($auditMessage !== null) {
            logAudit('login_failed', 'autenticacion', $auditMessage);
        }

        $_SESSION['error'] = $this->buildFailedLoginMessage($baseMessage, $username, $ipAddress);
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
        $this->redirect('/login');
    }

    private function registerFailedLogin(string $username, string $ipAddress): void {
        $locationData = $this->getLoginLocationAttemptData();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (
                    username,
                    ip_address,
                    latitude,
                    longitude,
                    allowed_latitude,
                    allowed_longitude,
                    distance_meters,
                    location_status,
                    user_agent
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->normalizeLoginIdentifier($username),
                $ipAddress,
                $locationData['latitude'],
                $locationData['longitude'],
                $locationData['allowed_latitude'],
                $locationData['allowed_longitude'],
                $locationData['distance_meters'],
                $locationData['location_status'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error registering failed login: " . $e->getMessage());
            $this->registerFailedLoginLegacy($username, $ipAddress);
        }
    }

    private function registerFailedLoginLegacy(string $username, string $ipAddress): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address, user_agent)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $this->normalizeLoginIdentifier($username),
                $ipAddress,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error registering failed login legacy fallback: " . $e->getMessage());
        }
    }

    private function clearFailedLogins(string $username, string $ipAddress): void {
        try {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = ? AND ip_address = ?");
            $stmt->execute([$this->normalizeLoginIdentifier($username), $ipAddress]);
        } catch (PDOException $e) {
            error_log("Error clearing failed logins: " . $e->getMessage());
        }
    }

    private function buildGeoDeniedAuditContext(array $user): array {
        $username = $user['username'] ?? 'desconocido';
        $locationData = $this->getLoginLocationAttemptData();
        $parts = ["Login denegado por ubicacion para asesor: $username"];

        $metadata = [
            'event_type' => 'login_geo_denied',
            'is_security_event' => true,
            'affected_user' => $username,
            'affected_user_id' => $user['id'] ?? null,
            'affected_user_name' => $user['full_name'] ?? null,
            'location_status' => $locationData['location_status'],
            'detected_location' => null,
            'allowed_location' => null,
            'distance_meters' => $locationData['distance_meters'],
            'distance_label' => null,
            'map_url' => null,
        ];

        if ($locationData['latitude'] !== null && $locationData['longitude'] !== null) {
            $lat = number_format((float) $locationData['latitude'], 8, '.', '');
            $lng = number_format((float) $locationData['longitude'], 8, '.', '');
            $mapUrl = "https://www.google.com/maps?q=$lat,$lng";

            $parts[] = "Ubicacion detectada: $lat, $lng";
            $parts[] = "Mapa: $mapUrl";
            $metadata['detected_location'] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'label' => "$lat, $lng",
            ];
            $metadata['map_url'] = $mapUrl;
        } elseif ($locationData['location_status'] === 'missing') {
            $parts[] = 'Ubicacion detectada: no recibida por el navegador';
            $metadata['detected_location'] = [
                'status' => 'missing',
                'label' => 'No recibida por el navegador',
            ];
        } elseif ($locationData['location_status'] === 'invalid') {
            $parts[] = 'Ubicacion detectada: coordenadas invalidas';
            $metadata['detected_location'] = [
                'status' => 'invalid',
                'label' => 'Coordenadas invalidas',
            ];
        }

        if ($locationData['distance_meters'] !== null) {
            $distance = (float) $locationData['distance_meters'];
            $distanceLabel = $distance >= 1000
                ? number_format($distance / 1000, 2, '.', '') . ' km'
                : number_format($distance, 0, '.', '') . ' m';
            $parts[] = "Distancia del area permitida: $distanceLabel";
            $metadata['distance_label'] = $distanceLabel;
        }

        if ($locationData['allowed_latitude'] !== null && $locationData['allowed_longitude'] !== null) {
            $allowedLat = number_format((float) $locationData['allowed_latitude'], 8, '.', '');
            $allowedLng = number_format((float) $locationData['allowed_longitude'], 8, '.', '');
            $parts[] = "Sucursal mas cercana registrada: $allowedLat, $allowedLng";
            $metadata['allowed_location'] = [
                'latitude' => (float) $allowedLat,
                'longitude' => (float) $allowedLng,
                'label' => "$allowedLat, $allowedLng",
            ];
        }

        if ($locationData['nearest_sucursal_nombre'] !== null) {
            $parts[] = "Sucursal mas cercana: {$locationData['nearest_sucursal_nombre']}";
            $metadata['nearest_sucursal'] = $locationData['nearest_sucursal_nombre'];
        }

        return [
            'description' => implode(' | ', $parts),
            'metadata' => $metadata,
        ];
    }

    /**
     * Sucursales activas para el login geolocalizado. El acceso es valido si el
     * usuario esta dentro del radio de CUALQUIERA de ellas (no de una sola ubicacion).
     */
    private function getActiveSucursales(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM sucursales WHERE activo = 1");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al cargar sucursales para geolocalizacion: " . $e->getMessage());
            return [];
        }
    }

    private function getLoginLocationAttemptData(): array {
        $data = [
            'latitude' => null,
            'longitude' => null,
            'allowed_latitude' => null,
            'allowed_longitude' => null,
            'distance_meters' => null,
            'location_status' => getConfig('geo_login_enabled', '0') === '1' ? 'missing' : 'not_required',
            'nearest_sucursal_nombre' => null,
        ];

        $userLat = $_POST['latitude'] ?? null;
        $userLng = $_POST['longitude'] ?? null;

        if ($this->isValidLatitude($userLat) && $this->isValidLongitude($userLng)) {
            $data['latitude'] = round((float) $userLat, 8);
            $data['longitude'] = round((float) $userLng, 8);
        } elseif ($userLat !== null || $userLng !== null) {
            $data['location_status'] = 'invalid';
        }

        if ($data['latitude'] !== null && $data['longitude'] !== null) {
            $nearest = null;
            $nearestDistance = null;

            foreach ($this->getActiveSucursales() as $sucursal) {
                $distance = $this->distanceInMeters(
                    (float) $sucursal['latitud'],
                    (float) $sucursal['longitud'],
                    (float) $data['latitude'],
                    (float) $data['longitude']
                );

                if ($nearestDistance === null || $distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearest = $sucursal;
                }
            }

            if ($nearest !== null) {
                $data['allowed_latitude'] = round((float) $nearest['latitud'], 8);
                $data['allowed_longitude'] = round((float) $nearest['longitud'], 8);
                $data['distance_meters'] = round($nearestDistance, 2);
                $data['nearest_sucursal_nombre'] = $nearest['nombre'];
                $data['location_status'] = $nearestDistance <= (float) $nearest['radio_metros'] ? 'inside_range' : 'outside_range';
            }
        }

        return $data;
    }

    private function validateGeoLogin(array $user): bool {
        if (($user['role'] ?? '') !== ROLE_ASESOR) {
            return true;
        }

        if (getConfig('geo_login_enabled', '0') !== '1') {
            return true;
        }

        $userLat = $_POST['latitude'] ?? null;
        $userLng = $_POST['longitude'] ?? null;

        if (!$this->isValidLatitude($userLat) || !$this->isValidLongitude($userLng)) {
            return false;
        }

        foreach ($this->getActiveSucursales() as $sucursal) {
            $radiusMeters = (float) $sucursal['radio_metros'];
            if ($radiusMeters <= 0) {
                continue;
            }

            $distance = $this->distanceInMeters(
                (float) $sucursal['latitud'],
                (float) $sucursal['longitud'],
                (float) $userLat,
                (float) $userLng
            );

            if ($distance <= $radiusMeters) {
                return true;
            }
        }

        return false;
    }

    private function isValidLatitude($value): bool {
        return is_numeric($value) && (float) $value >= -90 && (float) $value <= 90;
    }

    private function isValidLongitude($value): bool {
        return is_numeric($value) && (float) $value >= -180 && (float) $value <= 180;
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
