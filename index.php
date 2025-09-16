<?php
require_once 'config/database.php';

// İlanları çek
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.durum = 'aktif' 
          ORDER BY p.created_at DESC 
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plaza Emlak & Yatırım - Ahmet Karaman</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Ana Sayfa Özel Stilleri */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
            position: relative;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.95;
        }
        
        .features {
            background: #fff;
            padding: 4rem 0;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .property-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .property-view {
            color: #3498db;
            font-weight: 500;
        }
        
        .no-image {
            height: 200px;
            background: #f5f5f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        .no-image span {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Üst Menü -->
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo-area">
                    <a href="index.php" class="logo-link">
                        <img src="assets/images/plaza-logo.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Ana Sayfa</a></li>
                    <li><a href="pages/satilik.php">Satılık</a></li>
                    <li><a href="pages/kiralik.php">Kiralık</a></li>
                    <li><a href="pages/hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="pages/iletisim.php">İletişim</a></li>
                    <li><a href="admin/" class="admin-btn">Yönetim</a></li>
                </ul>
                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Bölümü -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">PLAZA EMLAK & YATIRIM</h1>
            <p class="hero-subtitle">Ahmet Karaman</p>
            <p class="hero-description">Hayalinizdeki gayrimenkulü bulmanız için profesyonel çözümler</p>
            
            <!-- Arama Formu -->
            <div class="search-box">
                <form method="GET" action="search.php">
                    <div class="search-row">
                        <select name="kategori" class="search-select">
                            <option value="">İlan Tipi</option>
                            <option value="Satılık">Satılık</option>
                            <option value="Kiralık">Kiralık</option>
                            <option value="Devren">Devren</option>
                            <option value="Devren Kiralık">Devren Kiralık</option>
                        </select>
                        <select name="emlak_tipi" class="search-select">
                            <option value="">Emlak Tipi</option>
                            <option value="Konut">Konut</option>
                            <option value="İşyeri">İşyeri</option>
                            <option value="Arsa">Arsa</option>
                            <option value="Bina">Bina</option>
                            <option value="Turistik Tesis">Turistik Tesis</option>
                        </select>
                        <input type="text" name="il" placeholder="İl" class="search-input">
                        <input type="text" name="ilce" placeholder="İlçe" class="search-input">
                        <button type="submit" class="search-btn">
                            <span>ARA</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Özellikler Bölümü -->
    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3>Güvenilir Hizmet</h3>
                    <p>20 yıllık tecrübemizle yanınızdayız</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📍</div>
                    <h3>En İyi Lokasyonlar</h3>
                    <p>Şehrin en değerli bölgelerinde</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💎</div>
                    <h3>Kaliteli Portföy</h3>
                    <p>Özenle seçilmiş gayrimenkuller</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤝</div>
                    <h3>Profesyonel Destek</h3>
                    <p>Alım-satım sürecinde yanınızdayız</p>
                </div>
            </div>
        </div>
    </section>

    <!-- İlanlar Bölümü -->
    <section class="properties">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">ÖNE ÇIKAN İLANLAR</h2>
                <p class="section-subtitle">En yeni ve özel gayrimenkullerimiz</p>
            </div>
            
            <div class="property-grid">
                <?php if(count($properties) > 0): ?>
                    <?php foreach($properties as $property): ?>
                    <div class="property-card">
                        <a href="pages/detail.php?id=<?php echo $property['id']; ?>">
                            <div class="property-image">
                                <?php if($property['image_path']): ?>
                                    <img src="<?php echo $property['image_path']; ?>" alt="<?php echo htmlspecialchars($property['baslik']); ?>">
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
                    <div class="no-results">
                        <p style="font-size: 1.2rem; margin-bottom: 10px;">📢 Henüz ilan eklenmemiş.</p>
                        <p style="color: #6B7280;">Admin panelinden ilk ilanınızı ekleyebilirsiniz.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>