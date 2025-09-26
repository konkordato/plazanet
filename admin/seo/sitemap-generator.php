<?php
// admin/seo/sitemap-generator.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Site URL'si (gerçek domain'inizi yazın)
$site_url = "https://www.plazaemlak.com";
// Yerel test için: $site_url = "http://localhost/plazanet";

// Sitemap oluştur butonu tıklandıysa
if (isset($_POST['generate_sitemap'])) {
    
    try {
        // XML başlangıcı
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        // 1. Ana sayfa
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . $site_url . '/</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";
        
        // 2. Sabit sayfalar
        $static_pages = [
            ['url' => '/pages/satilik.php', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/pages/kiralik.php', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/pages/hakkimizda.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/pages/iletisim.php', 'priority' => '0.7', 'changefreq' => 'monthly']
        ];
        
        foreach ($static_pages as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $site_url . $page['url'] . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
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
                // Slug yoksa ID ile oluştur
                $url = $site_url . '/pages/detail.php?id=' . $property['id'];
            }
            
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . ($property['lastmod_date'] ?? date('Y-m-d')) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            
            // Resim varsa ekle
            if (!empty($property['image_path'])) {
                $xml .= '    <image:image>' . "\n";
                $xml .= '      <image:loc>' . $site_url . '/' . htmlspecialchars($property['image_path']) . '</image:loc>' . "\n";
                $xml .= '      <image:title>' . htmlspecialchars($property['baslik']) . '</image:title>' . "\n";
                $xml .= '    </image:image>' . "\n";
            }
            
            $xml .= '  </url>' . "\n";
        }
        
        // XML'i kapat
        $xml .= '</urlset>';
        
        // Dosyayı kaydet (ana dizinde)
        $sitemap_path = '../../sitemap.xml';
        if (file_put_contents($sitemap_path, $xml)) {
            $success = "Sitemap.xml başarıyla oluşturuldu! (" . count($properties) . " ilan eklendi)";
            
            // Aynı zamanda robots.txt'yi de güncelle
            $robots_content = "# Plaza Emlak Robots.txt\n";
            $robots_content .= "User-agent: *\n";
            $robots_content .= "Allow: /\n\n";
            $robots_content .= "# Sitemap konumu\n";
            $robots_content .= "Sitemap: " . $site_url . "/sitemap.xml\n\n";
            $robots_content .= "# Admin panelini engelle\n";
            $robots_content .= "Disallow: /admin/\n";
            $robots_content .= "Disallow: /config/\n";
            $robots_content .= "Disallow: /includes/\n\n";
            $robots_content .= "# Arama motorlarına izin ver\n";
            $robots_content .= "User-agent: Googlebot\n";
            $robots_content .= "Allow: /\n";
            $robots_content .= "Crawl-delay: 0\n\n";
            $robots_content .= "User-agent: Bingbot\n";
            $robots_content .= "Allow: /\n";
            $robots_content .= "Crawl-delay: 1\n";
            
            file_put_contents('../../robots.txt', $robots_content);
            
        } else {
            $error = "Sitemap.xml oluşturulamadı! Dosya yazma izinlerini kontrol edin.";
        }
        
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// İstatistikleri çek
$total_properties = $db->query("SELECT COUNT(*) FROM properties WHERE durum = 'aktif'")->fetchColumn();
$total_pages = 5 + $total_properties; // Sabit sayfalar + ilanlar

// Son sitemap oluşturma tarihi
$sitemap_file = '../../sitemap.xml';
$last_generated = '';
if (file_exists($sitemap_file)) {
    $last_generated = date('d.m.Y H:i:s', filemtime($sitemap_file));
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap Oluşturucu - Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            position: fixed;
            height: 100vh;
            background: #2c3e50;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .sitemap-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .info-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .info-card h2 {
            margin: 0 0 20px 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-box.success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-box.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .feature-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 30px;
        }
        
        .feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
        }
        
        .btn-generate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: #95a5a6;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            display: inline-block;
            margin-left: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .preview-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }
        
        .preview-box h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
        }
        
        .code-preview {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .last-generated {
            background: #e8f4f8;
            padding: 10px 15px;
            border-radius: 5px;
            color: #0c5460;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .instructions {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffc107;
            color: #856404;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Sitemap.xml Oluşturucu</h3>
                </div>
                <div class="navbar-right">
                    <span>Hoş geldiniz, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>
            
            <div class="content">
                <div class="sitemap-container">
                    <!-- Mesajlar -->
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            ✅ <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            ❌ <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- İstatistikler -->
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $total_pages; ?></div>
                            <div class="stat-label">Toplam Sayfa</div>
                        </div>
                        
                        <div class="stat-box success">
                            <div class="stat-number"><?php echo $total_properties; ?></div>
                            <div class="stat-label">Aktif İlan</div>
                        </div>
                        
                        <div class="stat-box warning">
                            <div class="stat-number">5</div>
                            <div class="stat-label">Sabit Sayfa</div>
                        </div>
                    </div>
                    
                    <!-- Ana Bilgi Kutusu -->
                    <div class="info-card">
                        <h2>📍 Sitemap.xml Nedir?</h2>
                        
                        <p>Sitemap, web sitenizin tüm sayfalarını listeleyen ve arama motorlarına sunan bir XML dosyasıdır.</p>
                        
                        <ul class="feature-list">
                            <li>Google ve diğer arama motorları sitenizi daha hızlı tarar</li>
                            <li>Yeni ilanlarınız daha hızlı Google'da görünür</li>
                            <li>Tüm sayfalarınızın indexlenmesini sağlar</li>
                            <li>SEO performansınızı artırır</li>
                            <li>Site haritanız otomatik güncellenir</li>
                        </ul>
                        
                        <?php if ($last_generated): ?>
                            <div class="last-generated">
                                📅 Son oluşturma: <?php echo $last_generated; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <button type="submit" name="generate_sitemap" class="btn-generate">
                                🗺️ Sitemap.xml Oluştur
                            </button>
                            <a href="index.php" class="btn-back">
                                ← Geri Dön
                            </a>
                        </form>
                        
                        <div class="instructions">
                            <strong>📌 Önemli:</strong> Sitemap oluşturduktan sonra Google Search Console'a eklemeyi unutmayın!
                            <br><br>
                            <strong>Sitemap URL'niz:</strong> <?php echo $site_url; ?>/sitemap.xml
                        </div>
                    </div>
                    
                    <!-- Örnek Önizleme -->
                    <div class="info-card">
                        <h2>👁️ Sitemap Önizlemesi</h2>
                        
                        <div class="preview-box">
                            <h3>Oluşturulacak dosya yapısı:</h3>
                            <div class="code-preview">
&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"&gt;
  &lt;url&gt;
    &lt;loc&gt;https://www.plazaemlak.com/&lt;/loc&gt;
    &lt;lastmod&gt;<?php echo date('Y-m-d'); ?>&lt;/lastmod&gt;
    &lt;changefreq&gt;daily&lt;/changefreq&gt;
    &lt;priority&gt;1.0&lt;/priority&gt;
  &lt;/url&gt;
  &lt;url&gt;
    &lt;loc&gt;https://www.plazaemlak.com/pages/satilik.php&lt;/loc&gt;
    &lt;lastmod&gt;<?php echo date('Y-m-d'); ?>&lt;/lastmod&gt;
    &lt;changefreq&gt;daily&lt;/changefreq&gt;
    &lt;priority&gt;0.9&lt;/priority&gt;
  &lt;/url&gt;
  &lt;!-- Ve diğer sayfalar... --&gt;
&lt;/urlset&gt;
                            </div>
                        </div>
                        
                        <?php if (file_exists($sitemap_file)): ?>
                            <div style="margin-top: 20px;">
                                <a href="<?php echo $site_url; ?>/sitemap.xml" target="_blank" class="btn-generate" style="display: inline-block; text-decoration: none;">
                                    👁️ Mevcut Sitemap'i Görüntüle
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Google Search Console Talimatları -->
                    <div class="info-card">
                        <h2>🔍 Google Search Console'a Nasıl Eklenir?</h2>
                        
                        <ol style="line-height: 1.8;">
                            <li><strong>Google Search Console</strong>'a giriş yapın</li>
                            <li>Sol menüden <strong>"Sitemaps"</strong> seçeneğine tıklayın</li>
                            <li>URL kutusuna <strong>sitemap.xml</strong> yazın</li>
                            <li><strong>"Submit"</strong> (Gönder) butonuna basın</li>
                            <li>Google sitenizi taramaya başlayacaktır</li>
                        </ol>
                        
                        <div class="instructions" style="margin-top: 20px;">
                            <strong>💡 İpucu:</strong> Sitemap'i her hafta veya yeni ilan eklediğinizde yeniden oluşturmanız önerilir.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>