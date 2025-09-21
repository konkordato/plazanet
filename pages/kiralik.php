<?php
require_once '../config/database.php';

// Kiralık ilanları çek - BÜYÜK HARF İLE KONTROL
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.durum = 'aktif' AND (p.kategori = 'Kiralık' OR p.kategori = 'kiralik')
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiralık İlanlar - Plaza Emlak & Yatırım</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo-area">
                    <a href="../index.php" class="logo-link">
                        <img src="../assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Ana Sayfa</a></li>
                    <li><a href="satilik.php">Satılık</a></li>
                    <li><a href="kiralik.php" class="active">Kiralık</a></li>
                    <li><a href="hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="iletisim.php">İletişim</a></li>
                    <li><a href="../admin/" class="admin-btn">Yönetim</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Kiralık İlanlar</h1>
            <p>Tüm kiralık gayrimenkul portföyümüz</p>
        </div>
    </section>

    <!-- Filtreler -->
    <section class="filters-section">
        <div class="container">
            <form method="GET" action="" class="filter-form">
                <select name="emlak_tipi">
                    <option value="">Emlak Tipi</option>
                    <option value="konut">Konut</option>
                    <option value="isyeri">İşyeri</option>
                </select>
                <select name="ilce">
                    <option value="">İlçe Seçin</option>
                    <option value="merkez">Merkez</option>
                    <option value="bolvadin">Bolvadin</option>
                    <option value="dinar">Dinar</option>
                    <option value="emirdag">Emirdağ</option>
                    <option value="sandikli">Sandıklı</option>
                </select>
                <input type="number" name="min_kira" placeholder="Min Kira">
                <input type="number" name="max_kira" placeholder="Max Kira">
                <input type="number" name="min_m2" placeholder="Min m²">
                <input type="number" name="max_m2" placeholder="Max m²">
                <button type="submit" class="filter-btn">Filtrele</button>
            </form>
        </div>
    </section>

    <!-- İlanlar -->
    <section class="properties">
        <div class="container">
            <div class="results-info">
                <p>Toplam <strong><?php echo count($properties); ?></strong> kiralık ilan bulundu</p>
            </div>
            
            <div class="property-grid">
                <?php if(count($properties) > 0): ?>
                    <?php foreach($properties as $property): ?>
                    <div class="property-card">
                        <a href="detail.php?id=<?php echo $property['id']; ?>">
                            <div class="property-image">
                                <?php if($property['image_path']): ?>
                                    <img src="../<?php echo $property['image_path']; ?>" alt="<?php echo htmlspecialchars($property['baslik']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <span>📷</span>
                                        <p>Fotoğraf Bekleniyor</p>
                                    </div>
                                <?php endif; ?>
                                <span class="property-badge rent">Kiralık</span>
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
                                        <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺/Ay
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
                        <p>Henüz kiralık ilan bulunmamaktadır.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>