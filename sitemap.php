<?php
// sitemap.php - Ana dizinde otomatik sitemap oluşturucu
// Bu dosyayı tarayıcıdan çağırdığınızda sitemap.xml otomatik oluşur

require_once 'config/database.php';

// Site URL'si
$site_url = "https://www.plazaemlak.com";
// Yerel test için kullanın:
// $site_url = "http://localhost/plazanet";

header('Content-Type: application/xml; charset=utf-8');

// XML başlangıcı
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// 1. Ana sayfa
echo '  <url>' . "\n";
echo '    <loc>' . $site_url . '/</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>daily</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// 2. Sabit sayfalar
$static_pages = [
    ['url' => '/pages/satilik.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/pages/kiralik.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['url' => '/pages/hakkimizda.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['url' => '/pages/iletisim.php', 'priority' => '0.7', 'changefreq' => 'monthly']
];

foreach ($static_pages as $page) {
    echo '  <url>' . "\n";
    echo '    <loc>' . $site_url . $page['url'] . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
    echo '    <priority>' . $page['priority'] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// 3. Tüm aktif ilanları ekle
$stmt = $db->query("
    SELECT p.*, ps.slug, pi.image_path,
           DATE_FORMAT(p.updated_at, '%Y-%m-%d') as lastmod_date
    FROM properties p
    LEFT JOIN property_seo ps ON p.id = ps.property_id
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
    WHERE p.durum = 'aktif'
    ORDER BY p.created_at DESC
");
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($properties as $property) {
    // URL oluştur
    if (!empty($property['slug'])) {
        $url = $site_url . '/ilan/' . $property['slug'];
    } else {
        $url = $site_url . '/pages/detail.php?id=' . $property['id'];
    }
    
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
    echo '    <lastmod>' . ($property['lastmod_date'] ?? date('Y-m-d')) . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    
    // Resim varsa ekle
    if (!empty($property['image_path'])) {
        echo '    <image:image>' . "\n";
        echo '      <image:loc>' . $site_url . '/' . htmlspecialchars($property['image_path']) . '</image:loc>' . "\n";
        echo '      <image:title>' . htmlspecialchars($property['baslik']) . '</image:title>' . "\n";
        echo '    </image:image>' . "\n";
    }
    
    echo '  </url>' . "\n";
}

// XML'i kapat
echo '</urlset>';
?>