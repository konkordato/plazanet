<?php
// CSRF (Cross-Site Request Forgery) KORUMASI

// CSRF token oluştur
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Token 1 saat sonra yenilensin
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Token süresi dolmuş mu?
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        return false;
    }
    
    // Token eşleşiyor mu?
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Form için CSRF input oluştur
function csrfInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// POST isteğinde CSRF kontrolü
function checkCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            die('CSRF token hatası! Bu işlem güvenlik nedeniyle engellendi.');
        }
    }
}

/* KULLANIM:

1. Form sayfasında:
<?php echo csrfInput(); ?>

2. Form işleme sayfasında:
<?php checkCSRF(); ?>

*/
?>