<?php
// Emoji yerine metin ikonlar
function getIcon($type) {
    $icons = [
        'home' => 'ANA',
        'building' => 'İLN',
        'add' => '+',
        'seo' => 'SEO',
        'users' => 'KLN',
        'crm' => 'CRM',
        'portfolio' => 'PRF',
        'settings' => 'AYR',
        'logout' => 'ÇKŞ'
    ];
    return isset($icons[$type]) ? $icons[$type] : '•';
}
?>