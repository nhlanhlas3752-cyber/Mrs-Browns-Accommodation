<?php
require_once __DIR__ . '/db.php';

// ================================================================
//  SESSION
// ================================================================
function session_start_once(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in(): bool {
    session_start_once();
    return !empty($_SESSION['admin_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . DASHBOARD_URL . '/login.php');
        exit;
    }
    _enforce_session_timeout();
}

function _enforce_session_timeout(): void {
    $now = time();

    // Absolute timeout
    if (isset($_SESSION['login_time']) && ($now - $_SESSION['login_time']) > SESSION_ABSOLUTE_TIMEOUT) {
        logout(); header('Location: ' . DASHBOARD_URL . '/login.php?reason=timeout'); exit;
    }

    // Idle timeout
    if (isset($_SESSION['last_active']) && ($now - $_SESSION['last_active']) > SESSION_IDLE_TIMEOUT) {
        logout(); header('Location: ' . DASHBOARD_URL . '/login.php?reason=idle'); exit;
    }

    $_SESSION['last_active'] = $now;
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function attempt_login(string $email, string $password): bool {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login:' . $ip;

    // Check rate limit (5 per 15 min per IP)
    if (rate_limit_exceeded($key, 5, 900)) {
        return false;
    }

    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([trim($email)]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        session_start_once();
        session_regenerate_id(true);
        $_SESSION['admin_id']    = $row['id'];
        $_SESSION['login_time']  = time();
        $_SESSION['last_active'] = time();
        // Clear CSRF token so a new one is issued post-login
        unset($_SESSION['csrf_token']);
        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$row['id']]);
        rate_limit_clear($key);
        audit_log($row['id'], 'login', 'user', $row['id']);
        return true;
    }

    rate_limit_record($key);
    audit_log(null, 'login_failed', 'email', null, trim($email));
    return false;
}

function logout(): void {
    session_start_once();
    if (!empty($_SESSION['admin_id'])) {
        audit_log($_SESSION['admin_id'], 'logout', 'user', $_SESSION['admin_id']);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ================================================================
//  CSRF
// ================================================================
function csrf_token(): string {
    session_start_once();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool {
    session_start_once();
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !$token) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_abort(): void {
    http_response_code(403);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    } else {
        echo '<p>Security error — please go back and try again.</p>';
    }
    exit;
}

// ================================================================
//  RATE LIMITING  (stored in login_attempts table)
// ================================================================
function rate_limit_exceeded(string $key, int $max, int $window_seconds): bool {
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->execute([$key, $window_seconds]);
        return (int)$stmt->fetchColumn() >= $max;
    } catch (PDOException $e) {
        return false;
    }
}

function rate_limit_record(string $key): void {
    try {
        db()->prepare('INSERT INTO login_attempts (identifier) VALUES (?)')->execute([$key]);
        // Prune records older than 1 day to keep table lean
        db()->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)')->execute();
    } catch (PDOException $e) {}
}

function rate_limit_clear(string $key): void {
    try {
        db()->prepare('DELETE FROM login_attempts WHERE identifier = ?')->execute([$key]);
    } catch (PDOException $e) {}
}

// ================================================================
//  AUDIT LOGGING
// ================================================================
function audit_log(?int $user_id, string $action, ?string $entity = null, ?int $entity_id = null, ?string $details = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        db()->prepare(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$user_id, $action, $entity, $entity_id, $details, $ip]);
    } catch (PDOException $e) {}
}

// ================================================================
//  USER MANAGEMENT
// ================================================================
function email_exists(string $email): bool {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([trim($email)]);
    return (bool) $stmt->fetch();
}

function register_user(string $name, string $email, string $password, string $role = 'admin'): bool {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    try {
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $result = $stmt->execute([trim($name), trim($email), $hash, $role]);
        if ($result) {
            audit_log(null, 'user_created', 'email', null, trim($email));
        }
        return $result;
    } catch (PDOException $e) {
        return false;
    }
}

// ================================================================
//  PASSWORD RESET
// ================================================================
function create_reset_token(string $email): ?string {
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([trim($email)]);
    if (!$stmt->fetch()) return null;

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([trim($email)]);
    db()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)')
        ->execute([trim($email), $token, $expires]);

    audit_log(null, 'password_reset_requested', 'email', null, trim($email));
    return $token;
}

function validate_reset_token(string $token): ?string {
    $stmt = db()->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([trim($token)]);
    $row = $stmt->fetch();
    return $row ? $row['email'] : null;
}

function reset_password(string $token, string $password): bool {
    $email = validate_reset_token($token);
    if (!$email) return false;

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    db()->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([$hash, $email]);
    db()->prepare('DELETE FROM password_resets WHERE token = ?')->execute([trim($token)]);
    audit_log(null, 'password_reset_completed', 'email', null, $email);
    return true;
}
