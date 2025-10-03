<?php
// Site yapılandırma dosyası

// Site URL'si (sonunda / olmadan)
define('SITE_URL', 'https://plazaemlak.net/plazanet');

// Site ana dizini (sonunda / olmadan)
define('BASE_PATH', '/plazanet');

// Upload dizini
define('UPLOAD_DIR', 'assets/uploads/properties/');

// Site bilgileri
define('SITE_NAME', 'Plaza Emlak & Yatırım');
define('SITE_PHONE', '+90 272 222 00 03');
define('SITE_EMAIL', 'info@plazaemlak.net');
define('SITE_ADDRESS', 'Afyonkarahisar');

// Yardımcı fonksiyonlar
function url($path = '') {
    if (empty($path)) {
        return SITE_URL;
    }
    // Başında / varsa kaldır
    $path = ltrim($path, '/');
    return SITE_URL . '/' . $path;
}

function asset($path) {
    return url('assets/' . $path);
}

function adminUrl($path = '') {
    return url('admin/' . $path);
}

// Dosya yolu düzeltme fonksiyonu
function fixPath($path) {
    // Eğer path / ile başlıyorsa BASE_PATH ekle
    if (strpos($path, '/') === 0 && strpos($path, BASE_PATH) !== 0) {
        return BASE_PATH . $path;
    }
    return $path;
}
?>