<?php
// admin/seo/generate-seo.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/SeoHelper.php';

$seoHelper = new SeoHelper($db);

// Form gönderildiyse işlemi başlat
if (isset($_POST['generate'])) {
    $success_count = 0;
    $error_count = 0;
    
    try {
        // Tüm aktif ilanları çek
        $stmt = $db->query("SELECT * FROM properties WHERE durum = 'aktif'");
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($properties as $property) {
            try {
                // Türkçe karakterleri İngilizce yap
                $turkce = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
                $english = ['s','s','i','i','g','g','u','u','o','o','c','c'];
                
                // URL için slug oluştur
                $slug_text = $property['kategori'] . "-" . 
                            $property['emlak_tipi'] . "-" . 
                            $property['ilce'] . "-" . 
                            $property['id'];
                $slug_text = str_replace($turkce, $english, $slug_text);
                $slug_text = strtolower($slug_text);
                $slug_text = preg_replace('/[^a-z0-9-]/', '-', $slug_text);
                $slug_text = preg_replace('/-+/', '-', $slug_text);
                $slug = trim($slug_text, '-');
                
                // Meta title oluştur (160 karakter sınırı)
                $meta_title = $property['kategori'] . " " . 
                             $property['emlak_tipi'] . " - " . 
                             $property['ilce'];
                if (!empty($property['mahalle'])) {
                    $meta_title .= ", " . $property['mahalle'];
                }
                $meta_title .= " - " . number_format($property['fiyat'], 0, ',', '.') . " TL";
                $meta_title = mb_substr($meta_title, 0, 160);
                
                // Meta description oluştur (300 karakter sınırı)
                $meta_description = $property['ilce'];
                if (!empty($property['mahalle'])) {
                    $meta_description .= " " . $property['mahalle'];
                }
                $meta_description .= " bölgesinde " . $property['kategori'] . " ";
                
                if (!empty($property['brut_metrekare'])) {
                    $meta_description .= $property['brut_metrekare'] . "m² ";
                }
                
                if (!empty($property['oda_sayisi'])) {
                    $meta_description .= $property['oda_sayisi'] . " ";
                }
                
                $meta_description .= $property['emlak_tipi'] . ". ";
                $meta_description .= "Fiyat: " . number_format($property['fiyat'], 0, ',', '.') . " TL. ";
                
                // Açıklamadan biraz ekle
                if (!empty($property['aciklama'])) {
                    $clean_desc = strip_tags($property['aciklama']);
                    $clean_desc = str_replace(["\r", "\n", "\t"], ' ', $clean_desc);
                    $clean_desc = preg_replace('/\s+/', ' ', $clean_desc);
                    $meta_description .= mb_substr($clean_desc, 0, 100) . "...";
                }
                
                $meta_description = mb_substr($meta_description, 0, 300);
                
                // Meta keywords oluştur
                $keywords = [
                    $property['il'] . " " . $property['kategori'],
                    $property['ilce'] . " " . $property['kategori'] . " " . $property['emlak_tipi'],
                    $property['kategori'] . " " . $property['emlak_tipi'],
                    "afyon " . $property['kategori'],
                    "plaza emlak " . $property['ilce']
                ];
                
                if (!empty($property['mahalle'])) {
                    $keywords[] = $property['mahalle'] . " emlak";
                }
                
                if (!empty($property['oda_sayisi'])) {
                    $keywords[] = $property['oda_sayisi'] . " daire";
                }
                
                $meta_keywords = implode(', ', $keywords);
                
                // Canonical URL
                $canonical_url = "https://www.plazaemlak.com/ilan/" . $slug;
                
                // Önce kontrol et, var mı?
                $check = $db->prepare("SELECT id FROM property_seo WHERE property_id = :pid");
                $check->execute([':pid' => $property['id']]);
                
                if ($check->rowCount() > 0) {
                    // Güncelle
                    $update = $db->prepare("
                        UPDATE property_seo SET 
                            slug = :slug,
                            meta_title = :meta_title,
                            meta_description = :meta_description,
                            meta_keywords = :meta_keywords,
                            canonical_url = :canonical_url
                        WHERE property_id = :pid
                    ");
                    
                    $update->execute([
                        ':slug' => $slug,
                        ':meta_title' => $meta_title,
                        ':meta_description' => $meta_description,
                        ':meta_keywords' => $meta_keywords,
                        ':canonical_url' => $canonical_url,
                        ':pid' => $property['id']
                    ]);
                } else {
                    // Yeni ekle
                    $insert = $db->prepare("
                        INSERT INTO property_seo (
                            property_id, slug, meta_title, meta_description, 
                            meta_keywords, canonical_url
                        ) VALUES (
                            :pid, :slug, :meta_title, :meta_description, 
                            :meta_keywords, :canonical_url
                        )
                    ");
                    
                    $insert->execute([
                        ':pid' => $property['id'],
                        ':slug' => $slug,
                        ':meta_title' => $meta_title,
                        ':meta_description' => $meta_description,
                        ':meta_keywords' => $meta_keywords,
                        ':canonical_url' => $canonical_url
                    ]);
                }
                
                $success_count++;
                
            } catch (Exception $e) {
                $error_count++;
            }
        }
        
        $_SESSION['success'] = "İşlem tamamlandı! $success_count ilan için SEO oluşturuldu.";
        if ($error_count > 0) {
            $_SESSION['error'] = "$error_count ilan için hata oluştu.";
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "İşlem hatası: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit();
}

// İstatistikleri al
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM properties WHERE durum = 'aktif') as total_properties,
        (SELECT COUNT(*) FROM property_seo) as seo_properties,
        (SELECT COUNT(*) FROM properties p 
         WHERE durum = 'aktif' 
         AND NOT EXISTS (SELECT 1 FROM property_seo ps WHERE ps.property_id = p.id)) as missing_seo
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Oluşturucu - Plaza Emlak</title>
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
        
        .generator-box {
            max-width: 800px;
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
            grid-template-columns: repeat(3, 1fr);
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
        
        .stat-box.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-box.success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
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
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 30px;
        }
        
        .feature-list li:before {
            content: "✅";
            position: absolute;
            left: 0;
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
            margin-right: 10px;
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
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Otomatik SEO Oluşturucu</h3>
                </div>
                <div class="navbar-right">
                    <span>Hoş geldiniz, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>
            
            <div class="content">
                <div class="generator-box">
                    <!-- İstatistikler -->
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['total_properties']; ?></div>
                            <div class="stat-label">Toplam Aktif İlan</div>
                        </div>
                        
                        <div class="stat-box success">
                            <div class="stat-number"><?php echo $stats['seo_properties']; ?></div>
                            <div class="stat-label">SEO Tanımlı İlan</div>
                        </div>
                        
                        <div class="stat-box warning">
                            <div class="stat-number"><?php echo $stats['missing_seo']; ?></div>
                            <div class="stat-label">SEO Bekleyen İlan</div>
                        </div>
                    </div>
                    
                    <!-- Ana Bilgi Kutusu -->
                    <div class="info-card">
                        <h2>SEO Oluşturma İşlemi</h2>
                        
                        <?php if ($stats['missing_seo'] > 0): ?>
                            <div class="warning-box">
                                ⚠️ <strong><?php echo $stats['missing_seo']; ?> adet ilan</strong> için SEO bilgileri oluşturulacak.
                            </div>
                        <?php endif; ?>
                        
                        <p>Bu işlem ile tüm ilanlarınız için otomatik olarak:</p>
                        
                        <ul class="feature-list">
                            <li>SEO dostu URL (slug) oluşturulacak</li>
                            <li>Google'da görünecek başlık ayarlanacak</li>
                            <li>Arama sonuçlarında görünecek açıklama yazılacak</li>
                            <li>Anahtar kelimeler belirlenecek</li>
                            <li>Siteniz Google'da üst sıralara çıkacak</li>
                            <li>Daha fazla müşteri ilanlarınızı görecek</li>
                        </ul>
                        
                        <?php if ($stats['missing_seo'] > 0): ?>
                            <form method="POST" onsubmit="return confirm('<?php echo $stats['missing_seo']; ?> ilan için SEO oluşturulacak. Devam etmek istiyor musunuz?');">
                                <button type="submit" name="generate" class="btn-generate">
                                    🚀 SEO Oluşturmayı Başlat
                                </button>
                                <a href="index.php" class="btn-back">
                                    ← Geri Dön
                                </a>
                            </form>
                        <?php else: ?>
                            <div style="background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;">
                                ✅ Tüm ilanlarınız için SEO tanımlamaları yapılmış durumda!
                            </div>
                            <br>
                            <a href="index.php" class="btn-back">← Geri Dön</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>