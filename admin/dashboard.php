<?php
// Oturum başlat
session_start();

// Admin girişi kontrolü
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Veritabanı bağlantısı
require_once '../config/database.php';

// Admin bilgilerini al
$adminInfo = [
    'id' => $_SESSION['admin_id'] ?? null,
    'username' => $_SESSION['admin_username'] ?? ''
];

// İstatistikleri çek - Türkçe sütun adlarına uygun
// Toplam ilan sayısı
$stmt = $db->query("SELECT COUNT(*) as total FROM properties");
$totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Aktif ilan sayısı
$stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE durum = 'aktif'");
$activeProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Satılık ilan sayısı
$stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Satılık' AND durum = 'aktif'");
$forSale = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Kiralık ilan sayısı
$stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Kiralık' AND durum = 'aktif'");
$forRent = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Son eklenen 5 ilan
$stmt = $db->query("SELECT id, baslik, fiyat, kategori, created_at 
                    FROM properties 
                    ORDER BY created_at DESC 
                    LIMIT 5");
$recentProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - Plazanet Emlak</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>PLAZANET</h2>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="properties/list.php">
                        <span class="icon">🏢</span>
                        <span>İlanlar</span>
                    </a>
                </li>
                <li>
                    <a href="properties/add.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="users/list.php">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <span class="icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </li>
                <li>
    <a href="portfolio/closing.php">
        <span class="icon">💰</span>
        <span>Portföy Kapatma</span>
    </a>
</li>
<li>
    <a href="portfolio/closing-list.php">
        <span class="icon">📋</span>
        <span>Kapatma Listesi</span>
    </a>
</li>
<li>
    <a href="portfolio/reports.php">
        <span class="icon">📊</span>
        <span>Satış Raporları</span>
    </a>
</li>
<?php if($_SESSION['user_role'] === 'admin'): ?>
<li>
    <a href="portfolio/commission-settings.php">
        <span class="icon">⚙️</span>
        <span>Prim Ayarları</span>
    </a>
</li>
<?php endif; ?>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Yönetim Paneli</h3>
                </div>
                <div class="navbar-right">
                    <div class="admin-info">
                        <span>Hoş geldin, <strong><?php echo htmlspecialchars($adminInfo['username']); ?></strong></span>
                    </div>
                    <a href="logout.php" class="btn-logout">Çıkış Yap</a>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- İstatistik Kartları -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon blue">🏢</div>
                        <h4>Toplam İlan</h4>
                        <div class="number"><?php echo $totalProperties; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-icon green">✓</div>
                        <h4>Aktif İlan</h4>
                        <div class="number"><?php echo $activeProperties; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-icon orange">💰</div>
                        <h4>Satılık</h4>
                        <div class="number"><?php echo $forSale; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-icon red">🔑</div>
                        <h4>Kiralık</h4>
                        <div class="number"><?php echo $forRent; ?></div>
                    </div>
                </div>

                <!-- Son Eklenen İlanlar -->
                <div class="card">
                    <h3 style="margin-bottom: 20px;">Son Eklenen İlanlar</h3>
                    <?php if(count($recentProperties) > 0): ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; text-align: left;">Başlık</th>
                                    <th style="padding: 10px; text-align: left;">Fiyat</th>
                                    <th style="padding: 10px; text-align: left;">Tip</th>
                                    <th style="padding: 10px; text-align: left;">Tarih</th>
                                    <th style="padding: 10px; text-align: center;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentProperties as $property): ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 10px;">
                                        <?php echo htmlspecialchars($property['baslik']); ?>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺
                                    </td>
                                    <td style="padding: 10px;">
                                        <span style="background: <?php echo $property['kategori'] == 'Satılık' ? '#f39c12' : '#e74c3c'; ?>; 
                                                     color: white; 
                                                     padding: 3px 8px; 
                                                     border-radius: 3px; 
                                                     font-size: 0.85rem;">
                                            <?php echo $property['kategori']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px;">
                                        <?php echo date('d.m.Y', strtotime($property['created_at'])); ?>
                                    </td>
                                    <td style="padding: 10px; text-align: center;">
                                        <a href="properties/edit.php?id=<?php echo $property['id']; ?>" 
                                           style="color: #3498db; text-decoration: none; margin-right: 10px;">
                                            ✏️ Düzenle
                                        </a>
                                        <a href="../pages/detail.php?id=<?php echo $property['id']; ?>" 
                                           target="_blank"
                                           style="color: #27ae60; text-decoration: none;">
                                            👁️ Görüntüle
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #7f8c8d; text-align: center; padding: 20px;">
                            Henüz ilan eklenmemiş.
                        </p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="properties/add.php" class="btn btn-primary">Yeni İlan Ekle</a>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="margin-bottom: 20px;">Hızlı İşlemler</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="properties/add-step1.php" style="background: #3498db; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            ➕ Yeni İlan Ekle
                        </a>
                        <a href="properties/list.php" style="background: #27ae60; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            📋 Tüm İlanları Gör
                        </a>
                        <a href="../index.php" target="_blank" style="background: #9b59b6; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            🌐 Siteyi Görüntüle
                        </a>
                        <a href="settings.php" style="background: #34495e; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            ⚙️ Ayarlar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>