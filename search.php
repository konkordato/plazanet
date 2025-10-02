<<<<<<< HEAD
<?php
require_once 'config/database.php';

// Arama parametrelerini al
$kategori = $_GET['kategori'] ?? '';
$emlak_tipi = $_GET['emlak_tipi'] ?? '';
$il = $_GET['il'] ?? '';
$ilce = $_GET['ilce'] ?? '';
// Bütçe parametrelerini al
$min_butce = $_GET['min_butce'] ?? '';
$max_butce = $_GET['max_butce'] ?? '';

// SQL sorgusu oluştur
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.durum = 'aktif'";

$params = [];

// Kategori filtresi
if(!empty($kategori)) {
    $query .= " AND p.kategori = :kategori";
    $params[':kategori'] = $kategori;
}

// Emlak tipi filtresi
if(!empty($emlak_tipi)) {
    $query .= " AND p.emlak_tipi = :emlak_tipi";
    $params[':emlak_tipi'] = $emlak_tipi;
}

// İl filtresi
if(!empty($il)) {
    $query .= " AND p.il LIKE :il";
    $params[':il'] = '%' . $il . '%';
}

// İlçe filtresi
if(!empty($ilce)) {
    $query .= " AND p.ilce LIKE :ilce";
    $params[':ilce'] = '%' . $ilce . '%';
}
// Min Bütçe filtresi
if(!empty($min_butce) && is_numeric($min_butce)) {
    $query .= " AND p.fiyat >= :min_butce";
    $params[':min_butce'] = $min_butce;
}

// Max Bütçe filtresi  
if(!empty($max_butce) && is_numeric($max_butce)) {
    $query .= " AND p.fiyat <= :max_butce";
    $params[':max_butce'] = $max_butce;
}
$query .= " ORDER BY p.created_at DESC";

// Sorguyu çalıştır
$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Arama kriterlerini metin olarak hazırla
$searchText = [];
if($kategori) $searchText[] = $kategori;
if($emlak_tipi) $searchText[] = $emlak_tipi;
if($il) $searchText[] = $il;
if($ilce) $searchText[] = $ilce;
if($min_butce) $searchText[] = "Min: " . number_format($min_butce, 0, ',', '.') . " TL";
if($max_butce) $searchText[] = "Max: " . number_format($max_butce, 0, ',', '.') . " TL";
$searchDescription = !empty($searchText) ? implode(', ', $searchText) : 'Tüm İlanlar';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - Plaza Emlak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-results-header {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .search-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-info h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        .result-count {
            color: var(--text-light);
            font-size: 1.1rem;
        }
        .filter-tags {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .filter-tag {
            background: var(--bg-light);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .back-to-search {
            margin: 20px 0;
        }
        .back-to-search a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo-area">
                    <a href="index.php" class="logo-link">
                        <img src="assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak" class="logo-img">
                    </a>
                <!-- SLOGAN BÖLÜMÜ -->
                    <div class="logo-slogan">
                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>
                    </div>    
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Ana Sayfa</a></li>
                    <li><a href="#satilik">Satılık</a></li>
                    <li><a href="#kiralik">Kiralık</a></li>
                    <li><a href="#hakkimizda">Hakkımızda</a></li>
                    <li><a href="#iletisim">İletişim</a></li>
                    <li><a href="admin/" class="admin-btn">Yönetim</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Arama Sonuçları -->
    <div class="container">
        <div class="back-to-search">
            <a href="index.php">← Ana Sayfaya Dön</a>
        </div>

        <div class="search-results-header">
            <div class="search-info">
                <h2>Arama Sonuçları</h2>
                <div class="result-count">
                    <?php echo count($properties); ?> ilan bulundu
                </div>
            </div>
            <div class="filter-tags">
                <?php if($kategori): ?>
                    <span class="filter-tag"><?php echo $kategori; ?></span>
                <?php endif; ?>
                <?php if($emlak_tipi): ?>
                    <span class="filter-tag"><?php echo $emlak_tipi; ?></span>
                <?php endif; ?>
                <?php if($il): ?>
                    <span class="filter-tag"><?php echo $il; ?></span>
                <?php endif; ?>
                <?php if($ilce): ?>
                    <span class="filter-tag"><?php echo $ilce; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- İlanlar -->
        <section class="properties" style="padding-top: 0;">
            <div class="property-grid">
                <?php if(count($properties) > 0): ?>
                    <?php foreach($properties as $property): ?>
                    <div class="property-card">
                        <a href="pages/detail.php?id=<?php echo $property['id']; ?>">
                            <div class="property-image">
                                <?php if($property['image_path']): ?>
                                    <img src="<?php echo $property['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($property['baslik']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <span>📷</span>
                                        <p>Fotoğraf Bekleniyor</p>
                                    </div>
                                <?php endif; ?>
                                <span class="property-badge <?php echo $property['kategori'] == 'Satılık' ? 'sale' : 'rent'; ?>">
                                    <?php echo $property['kategori']; ?>
                                </span>
                            </div>
                            <div class="property-info">
                                <h3><?php echo htmlspecialchars($property['baslik']); ?></h3>
                                <p class="property-location">
                                    📍 <?php echo $property['ilce'] . ', ' . $property['il']; ?>
                                </p>
                                <div class="property-features">
                                    <?php if($property['oda_sayisi']): ?>
                                        <span>🏠 <?php echo $property['oda_sayisi']; ?></span>
                                    <?php endif; ?>
                                    <?php if($property['brut_metrekare']): ?>
                                        <span>📐 <?php echo $property['brut_metrekare']; ?> m²</span>
                                    <?php endif; ?>
                                    <?php if($property['bulundugu_kat']): ?>
                                        <span>🏢 <?php echo $property['bulundugu_kat']; ?>. Kat</span>
                                    <?php endif; ?>
                                </div>
                                <div class="property-footer">
                                    <div class="property-price">
                                        <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺
                                    </div>
                                    <div class="property-view">
                                        Detayları Gör →
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                        <h3 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 10px;">
                            Arama kriterlerinize uygun ilan bulunamadı
                        </h3>
                        <p style="color: var(--text-light); margin-bottom: 20px;">
                            Farklı kriterlerle tekrar arama yapabilirsiniz
                        </p>
                        <a href="index.php" style="background: var(--accent-color); color: white; 
                                                   padding: 10px 30px; border-radius: 5px; 
                                                   text-decoration: none; display: inline-block;">
                            Yeni Arama Yap
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <img src="assets/images/plaza-logo.png" alt="Plaza Emlak" class="footer-logo">
                    <p>Plaza Emlak & Yatırım - Güvenilir gayrimenkul hizmetleri</p>
                </div>
                <div class="footer-section">
                    <h4>Hızlı Linkler</h4>
                    <ul>
                        <li><a href="index.php">Ana Sayfa</a></li>
                        <li><a href="#satilik">Satılık</a></li>
                        <li><a href="#kiralik">Kiralık</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>İletişim</h4>
                    <p>📞 0212 XXX XX XX</p>
                    <p>📱 0532 XXX XX XX</p>
                    <p>✉️ info@plazaemlak.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Plaza Emlak & Yatırım. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
</body>
=======
<?php
require_once 'config/database.php';

// Arama parametrelerini al
$kategori = $_GET['kategori'] ?? '';
$emlak_tipi = $_GET['emlak_tipi'] ?? '';
$il = $_GET['il'] ?? '';
$ilce = $_GET['ilce'] ?? '';
// Bütçe parametrelerini al
$min_butce = $_GET['min_butce'] ?? '';
$max_butce = $_GET['max_butce'] ?? '';

// SQL sorgusu oluştur
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.durum = 'aktif'";

$params = [];

// Kategori filtresi
if(!empty($kategori)) {
    $query .= " AND p.kategori = :kategori";
    $params[':kategori'] = $kategori;
}

// Emlak tipi filtresi
if(!empty($emlak_tipi)) {
    $query .= " AND p.emlak_tipi = :emlak_tipi";
    $params[':emlak_tipi'] = $emlak_tipi;
}

// İl filtresi
if(!empty($il)) {
    $query .= " AND p.il LIKE :il";
    $params[':il'] = '%' . $il . '%';
}

// İlçe filtresi
if(!empty($ilce)) {
    $query .= " AND p.ilce LIKE :ilce";
    $params[':ilce'] = '%' . $ilce . '%';
}
// Min Bütçe filtresi
if(!empty($min_butce) && is_numeric($min_butce)) {
    $query .= " AND p.fiyat >= :min_butce";
    $params[':min_butce'] = $min_butce;
}

// Max Bütçe filtresi  
if(!empty($max_butce) && is_numeric($max_butce)) {
    $query .= " AND p.fiyat <= :max_butce";
    $params[':max_butce'] = $max_butce;
}
$query .= " ORDER BY p.created_at DESC";

// Sorguyu çalıştır
$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Arama kriterlerini metin olarak hazırla
$searchText = [];
if($kategori) $searchText[] = $kategori;
if($emlak_tipi) $searchText[] = $emlak_tipi;
if($il) $searchText[] = $il;
if($ilce) $searchText[] = $ilce;
if($min_butce) $searchText[] = "Min: " . number_format($min_butce, 0, ',', '.') . " TL";
if($max_butce) $searchText[] = "Max: " . number_format($max_butce, 0, ',', '.') . " TL";
$searchDescription = !empty($searchText) ? implode(', ', $searchText) : 'Tüm İlanlar';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - Plaza Emlak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-results-header {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .search-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-info h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        .result-count {
            color: var(--text-light);
            font-size: 1.1rem;
        }
        .filter-tags {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .filter-tag {
            background: var(--bg-light);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .back-to-search {
            margin: 20px 0;
        }
        .back-to-search a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo-area">
                    <a href="index.php" class="logo-link">
                        <img src="assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak" class="logo-img">
                    </a>
                <!-- SLOGAN BÖLÜMÜ -->
                    <div class="logo-slogan">
                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>
                    </div>    
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Ana Sayfa</a></li>
                    <li><a href="#satilik">Satılık</a></li>
                    <li><a href="#kiralik">Kiralık</a></li>
                    <li><a href="#hakkimizda">Hakkımızda</a></li>
                    <li><a href="#iletisim">İletişim</a></li>
                    <li><a href="admin/" class="admin-btn">Yönetim</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Arama Sonuçları -->
    <div class="container">
        <div class="back-to-search">
            <a href="index.php">← Ana Sayfaya Dön</a>
        </div>

        <div class="search-results-header">
            <div class="search-info">
                <h2>Arama Sonuçları</h2>
                <div class="result-count">
                    <?php echo count($properties); ?> ilan bulundu
                </div>
            </div>
            <div class="filter-tags">
                <?php if($kategori): ?>
                    <span class="filter-tag"><?php echo $kategori; ?></span>
                <?php endif; ?>
                <?php if($emlak_tipi): ?>
                    <span class="filter-tag"><?php echo $emlak_tipi; ?></span>
                <?php endif; ?>
                <?php if($il): ?>
                    <span class="filter-tag"><?php echo $il; ?></span>
                <?php endif; ?>
                <?php if($ilce): ?>
                    <span class="filter-tag"><?php echo $ilce; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- İlanlar -->
        <section class="properties" style="padding-top: 0;">
            <div class="property-grid">
                <?php if(count($properties) > 0): ?>
                    <?php foreach($properties as $property): ?>
                    <div class="property-card">
                        <a href="pages/detail.php?id=<?php echo $property['id']; ?>">
                            <div class="property-image">
                                <?php if($property['image_path']): ?>
                                    <img src="<?php echo $property['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($property['baslik']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <span>📷</span>
                                        <p>Fotoğraf Bekleniyor</p>
                                    </div>
                                <?php endif; ?>
                                <span class="property-badge <?php echo $property['kategori'] == 'Satılık' ? 'sale' : 'rent'; ?>">
                                    <?php echo $property['kategori']; ?>
                                </span>
                            </div>
                            <div class="property-info">
                                <h3><?php echo htmlspecialchars($property['baslik']); ?></h3>
                                <p class="property-location">
                                    📍 <?php echo $property['ilce'] . ', ' . $property['il']; ?>
                                </p>
                                <div class="property-features">
                                    <?php if($property['oda_sayisi']): ?>
                                        <span>🏠 <?php echo $property['oda_sayisi']; ?></span>
                                    <?php endif; ?>
                                    <?php if($property['brut_metrekare']): ?>
                                        <span>📐 <?php echo $property['brut_metrekare']; ?> m²</span>
                                    <?php endif; ?>
                                    <?php if($property['bulundugu_kat']): ?>
                                        <span>🏢 <?php echo $property['bulundugu_kat']; ?>. Kat</span>
                                    <?php endif; ?>
                                </div>
                                <div class="property-footer">
                                    <div class="property-price">
                                        <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺
                                    </div>
                                    <div class="property-view">
                                        Detayları Gör →
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                        <h3 style="font-size: 1.5rem; color: var(--text-dark); margin-bottom: 10px;">
                            Arama kriterlerinize uygun ilan bulunamadı
                        </h3>
                        <p style="color: var(--text-light); margin-bottom: 20px;">
                            Farklı kriterlerle tekrar arama yapabilirsiniz
                        </p>
                        <a href="index.php" style="background: var(--accent-color); color: white; 
                                                   padding: 10px 30px; border-radius: 5px; 
                                                   text-decoration: none; display: inline-block;">
                            Yeni Arama Yap
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <img src="assets/images/plaza-logo.png" alt="Plaza Emlak" class="footer-logo">
                    <p>Plaza Emlak & Yatırım - Güvenilir gayrimenkul hizmetleri</p>
                </div>
                <div class="footer-section">
                    <h4>Hızlı Linkler</h4>
                    <ul>
                        <li><a href="index.php">Ana Sayfa</a></li>
                        <li><a href="#satilik">Satılık</a></li>
                        <li><a href="#kiralik">Kiralık</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>İletişim</h4>
                    <p>📞 0212 XXX XX XX</p>
                    <p>📱 0532 XXX XX XX</p>
                    <p>✉️ info@plazaemlak.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Plaza Emlak & Yatırım. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>