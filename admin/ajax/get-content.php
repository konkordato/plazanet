<?php
// AJAX İçerik Yükleme Dosyası
session_start();

// Giriş kontrolü
if (!isset($_SESSION['admin_logged_in'])) {
    exit('Yetkisiz erişim');
}

// Veritabanı
require_once '../../config/database.php';

// Hangi sayfa isteniyor?
$page = $_GET['page'] ?? 'dashboard';

// Sayfa içeriklerini döndür
switch($page) {
    case 'dashboard':
        // İstatistikleri çek
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM properties");
            $totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE durum = 'aktif'");
            $activeProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Satılık'");
            $forSale = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Kiralık'");
            $forRent = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch(Exception $e) {
            $totalProperties = 0;
            $activeProperties = 0;
            $forSale = 0;
            $forRent = 0;
        }
        ?>
        <h2 class="page-title">Dashboard</h2>
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon blue">🏢</div>
                <div class="stat-value"><?php echo $totalProperties; ?></div>
                <div class="stat-label">Toplam İlan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✓</div>
                <div class="stat-value"><?php echo $activeProperties; ?></div>
                <div class="stat-label">Aktif İlan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">💰</div>
                <div class="stat-value"><?php echo $forSale; ?></div>
                <div class="stat-label">Satılık</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">🔑</div>
                <div class="stat-value"><?php echo $forRent; ?></div>
                <div class="stat-label">Kiralık</div>
            </div>
        </div>
        
        <!-- Son İlanlar -->
        <h3 style="margin-top: 30px; margin-bottom: 15px;">Son Eklenen İlanlar</h3>
        <div class="data-table">
            <?php
            try {
                $stmt = $db->query("SELECT * FROM properties ORDER BY created_at DESC LIMIT 5");
                $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if(count($properties) > 0) {
                    foreach($properties as $prop) {
                        ?>
                        <div class="table-row">
                            <div class="row-info">
                                <h4><?php echo htmlspecialchars($prop['baslik']); ?></h4>
                                <p><?php echo $prop['il'] . ', ' . $prop['ilce']; ?> - <?php echo number_format($prop['fiyat'], 0, ',', '.'); ?> ₺</p>
                            </div>
                            <div class="row-actions">
                                <button class="btn-action" onclick="window.location='properties/edit.php?id=<?php echo $prop['id']; ?>'">Düzenle</button>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <p>Henüz ilan eklenmemiş</p>
                          </div>';
                }
            } catch(Exception $e) {
                echo '<div class="empty-state">Veri yüklenemedi</div>';
            }
            ?>
        </div>
        <?php
        break;
        
    case 'properties':
        ?>
        <h2 class="page-title">İlanlar</h2>
        <div style="margin-bottom: 20px;">
            <button class="btn-action" style="padding: 10px 20px;" onclick="window.location='properties/add.php'">
                ➕ Yeni İlan Ekle
            </button>
        </div>
        <div class="data-table">
            <?php
            try {
                $stmt = $db->query("SELECT * FROM properties ORDER BY created_at DESC");
                $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<div class="table-header">Toplam ' . count($properties) . ' ilan</div>';
                
                foreach($properties as $prop) {
                    ?>
                    <div class="table-row">
                        <div class="row-info">
                            <h4><?php echo htmlspecialchars($prop['baslik']); ?></h4>
                            <p>
                                <span style="color: <?php echo $prop['kategori'] == 'Satılık' ? '#f39c12' : '#e74c3c'; ?>">
                                    <?php echo $prop['kategori']; ?>
                                </span> • 
                                <?php echo $prop['il'] . ', ' . $prop['ilce']; ?> • 
                                <?php echo number_format($prop['fiyat'], 0, ',', '.'); ?> ₺
                            </p>
                        </div>
                        <div class="row-actions">
                            <button class="btn-action">Düzenle</button>
                        </div>
                    </div>
                    <?php
                }
            } catch(Exception $e) {
                echo '<div class="empty-state">Veri yüklenemedi</div>';
            }
            ?>
        </div>
        <?php
        break;
        
    case 'users':
        ?>
        <h2 class="page-title">Kullanıcılar</h2>
        <div style="margin-bottom: 20px;">
            <button class="btn-action" style="padding: 10px 20px;" onclick="window.location='users/add.php'">
                ➕ Yeni Kullanıcı
            </button>
        </div>
        <div class="data-table">
            <?php
            try {
                $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<div class="table-header">Toplam ' . count($users) . ' kullanıcı</div>';
                
                foreach($users as $user) {
                    ?>
                    <div class="table-row">
                        <div class="row-info">
                            <h4><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h4>
                            <p><?php echo $user['role'] ?? 'Kullanıcı'; ?> • <?php echo $user['email'] ?? ''; ?></p>
                        </div>
                        <div class="row-actions">
                            <button class="btn-action">Düzenle</button>
                        </div>
                    </div>
                    <?php
                }
            } catch(Exception $e) {
                echo '<div class="empty-state">Veri yüklenemedi</div>';
            }
            ?>
        </div>
        <?php
        break;
        
    case 'crm':
        ?>
        <h2 class="page-title">CRM Sistemi</h2>
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon blue">👤</div>
                <div class="stat-value">45</div>
                <div class="stat-label">Müşteriler</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📞</div>
                <div class="stat-value">12</div>
                <div class="stat-label">Görüşmeler</div>
            </div>
        </div>
        <?php
        break;
        
    case 'settings':
        ?>
        <h2 class="page-title">Ayarlar</h2>
        <div class="data-table">
            <div class="table-row">
                <div class="row-info">
                    <h4>Site Ayarları</h4>
                    <p>Genel site ayarları</p>
                </div>
            </div>
            <div class="table-row">
                <div class="row-info">
                    <h4>SMS Ayarları</h4>
                    <p>SMS gönderim ayarları</p>
                </div>
            </div>
            <div class="table-row">
                <div class="row-info">
                    <h4>SEO Ayarları</h4>
                    <p>Arama motoru optimizasyonu</p>
                </div>
            </div>
        </div>
        <?php
        break;
        
    default:
        echo '<div class="empty-state">
                <div class="empty-icon">❓</div>
                <p>Sayfa bulunamadı</p>
              </div>';
}
?>