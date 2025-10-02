<<<<<<< HEAD
<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';
$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';

// Parametreleri al
$musteri_tip = $_GET['tip'] ?? '';
$musteri_id = $_GET['id'] ?? 0;

if(!$musteri_tip || !$musteri_id) {
    header("Location: index.php");
    exit();
}

// Müşteri bilgilerini çek
if($musteri_tip == 'alici') {
    $sql = "SELECT * FROM crm_alici_musteriler WHERE id = :id";
    if($current_user_role != 'admin') {
        $sql .= " AND ekleyen_user_id = :user_id";
    }
} else {
    $sql = "SELECT * FROM crm_satici_musteriler WHERE id = :id";
    if($current_user_role != 'admin') {
        $sql .= " AND ekleyen_user_id = :user_id";
    }
}

$stmt = $db->prepare($sql);
$params = [':id' => $musteri_id];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$musteri = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$musteri) {
    header("Location: index.php");
    exit();
}

// Görüşme notlarını çek
$notes_sql = "SELECT * FROM crm_gorusme_notlari 
              WHERE musteri_tipi = :tip AND musteri_id = :id 
              ORDER BY gorusme_tarihi DESC";
$notes_stmt = $db->prepare($notes_sql);
$notes_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
$gorusme_notlari = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);

// SMS kayıtlarını çek
$sms_sql = "SELECT * FROM crm_sms_kayitlari 
            WHERE musteri_tipi = :tip AND musteri_id = :id 
            ORDER BY gonderim_tarihi DESC";
$sms_stmt = $db->prepare($sms_sql);
$sms_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
$sms_kayitlari = $sms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Eğer alıcı müşteri ise, bütçesine uygun ilanları çek
$uygun_ilanlar = [];
if($musteri_tip == 'alici') {
    $ilan_sql = "SELECT p.*, pi.image_path 
                 FROM properties p 
                 LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
                 WHERE p.durum = 'aktif' 
                 AND p.fiyat BETWEEN :min_butce AND :max_butce";
    
    // Lokasyon filtresi
    $ilan_params = [
        ':min_butce' => $musteri['min_butce'],
        ':max_butce' => $musteri['max_butce']
    ];
    
    if($musteri['aranan_il']) {
        $ilan_sql .= " AND p.il = :il";
        $ilan_params[':il'] = $musteri['aranan_il'];
    }
    
    $ilan_sql .= " ORDER BY p.fiyat ASC LIMIT 10";
    
    $ilan_stmt = $db->prepare($ilan_sql);
    $ilan_stmt->execute($ilan_params);
    $uygun_ilanlar = $ilan_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Görüşme notu ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gorusme_notu'])) {
    $gorusme_notu = trim($_POST['gorusme_notu']);
    $gorusme_tarihi = $_POST['gorusme_tarihi'] ?? date('Y-m-d H:i:s');
    
    $not_sql = "INSERT INTO crm_gorusme_notlari 
                (musteri_tipi, musteri_id, gorusme_tarihi, gorusme_notu, gorusen_user_id, gorusen_user_adi) 
                VALUES (:tip, :id, :tarih, :not, :user_id, :user_adi)";
    
    $not_stmt = $db->prepare($not_sql);
    $not_stmt->execute([
        ':tip' => $musteri_tip,
        ':id' => $musteri_id,
        ':tarih' => $gorusme_tarihi,
        ':not' => $gorusme_notu,
        ':user_id' => $current_user_id,
        ':user_adi' => $current_user_name
    ]);
    
    // Sayfayı yenile
    header("Location: musteri-detay.php?tip=$musteri_tip&id=$musteri_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $musteri['ad'] . ' ' . $musteri['soyad']; ?> - Müşteri Kartı</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Layout Düzeltmeleri */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .admin-content {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .customer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .customer-name {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .customer-type {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 14px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-item {
            display: flex;
            padding: 8px 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #7f8c8d;
            min-width: 120px;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .notes-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .note-item {
            border-left: 3px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        
        .note-date {
            color: #7f8c8d;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .note-text {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .note-author {
            color: #95a5a6;
            font-size: 12px;
            font-style: italic;
        }
        
        .add-note-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 100px;
        }
        
        .properties-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .property-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .property-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .property-info {
            padding: 15px;
        }
        
        .property-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .property-price {
            color: #27ae60;
            font-size: 18px;
            font-weight: bold;
        }
        
        .property-location {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-back {
            background: #95a5a6;
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .social-link {
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-size: 12px;
        }
        
        .social-link:hover {
            background: #e0e0e0;
        }
    </style>
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
                    <a href="../dashboard.php">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="../properties/list.php">
                        <span class="icon">🏢</span>
                        <span>İlanlar</span>
                    </a>
                </li>
                <li>
                    <a href="../properties/add-step1.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="index.php" class="active">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <li>
                    <a href="../users/list.php">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li>
                    <a href="../settings.php">
                        <span class="icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <span class="icon">🚪</span>
                        <span>Çıkış</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <!-- Müşteri Başlık -->
            <div class="customer-header">
                <div class="customer-name">
                    <?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?>
                </div>
                <span class="customer-type">
                    <?php echo $musteri_tip == 'alici' ? '👥 Alıcı Müşteri' : '🏠 Satıcı Müşteri'; ?>
                </span>
            </div>
            
            <!-- Aksiyonlar -->
            <div class="action-buttons">
                <a href="<?php echo $musteri_tip; ?>-duzenle.php?id=<?php echo $musteri_id; ?>" class="btn btn-warning">
                    ✏️ Düzenle
                </a>
                <a href="tel:0<?php echo $musteri['telefon']; ?>" class="btn btn-success">
                    📞 Ara: 0<?php echo $musteri['telefon']; ?>
                </a>
                <button onclick="sendSMS()" class="btn btn-primary">
                    📱 SMS Gönder
                </button>
                <a href="<?php echo $musteri_tip; ?>-liste.php" class="btn btn-back">
                    ← Listeye Dön
                </a>
            </div>
            
            <!-- Bilgi Kartları -->
            <div class="info-grid">
                <!-- İletişim Bilgileri -->
                <div class="info-card">
                    <h3>📞 İletişim Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Telefon:</span>
                        <span class="info-value">0<?php echo $musteri['telefon']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">E-posta:</span>
                        <span class="info-value"><?php echo $musteri['email'] ?: '-'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adres:</span>
                        <span class="info-value"><?php echo $musteri['adres'] ?: '-'; ?></span>
                    </div>
                    <?php if($musteri_tip == 'alici'): ?>
                    <div class="info-item">
                        <span class="info-label">İş:</span>
                        <span class="info-value"><?php echo $musteri['is_bilgisi'] ?: '-'; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($musteri['facebook'] || $musteri['instagram'] || $musteri['twitter']): ?>
                    <div class="social-links">
                        <?php if($musteri['facebook']): ?>
                        <a href="https://<?php echo $musteri['facebook']; ?>" target="_blank" class="social-link">
                            Facebook
                        </a>
                        <?php endif; ?>
                        <?php if($musteri['instagram']): ?>
                        <a href="https://instagram.com/<?php echo $musteri['instagram']; ?>" target="_blank" class="social-link">
                            Instagram
                        </a>
                        <?php endif; ?>
                        <?php if($musteri['twitter']): ?>
                        <a href="https://twitter.com/<?php echo $musteri['twitter']; ?>" target="_blank" class="social-link">
                            Twitter
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Taşınmaz/Arama Bilgileri -->
                <div class="info-card">
                    <?php if($musteri_tip == 'alici'): ?>
                    <h3>🔍 Arama Kriterleri</h3>
                    <div class="info-item">
                        <span class="info-label">Taşınmaz:</span>
                        <span class="info-value"><?php echo $musteri['aranan_tasinmaz']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Bölge:</span>
                        <span class="info-value">
                            <?php 
                            $bolge = [];
                            if($musteri['aranan_il']) $bolge[] = $musteri['aranan_il'];
                            if($musteri['aranan_ilce']) $bolge[] = $musteri['aranan_ilce'];
                            if($musteri['aranan_mahalle']) $bolge[] = $musteri['aranan_mahalle'];
                            if($musteri['aranan_koy']) $bolge[] = $musteri['aranan_koy'];
                            echo implode(', ', $bolge) ?: '-';
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Bütçe:</span>
                        <span class="info-value">
                            <?php echo number_format($musteri['min_butce'], 0, ',', '.'); ?> - 
                            <?php echo number_format($musteri['max_butce'], 0, ',', '.'); ?> ₺
                        </span>
                    </div>
                    <?php else: ?>
                    <h3>🏘️ Taşınmaz Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Taşınmaz Cinsi:</span>
                        <span class="info-value"><?php echo $musteri['tasinmaz_cinsi'] ?: '-'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adres:</span>
                        <span class="info-value"><?php echo $musteri['tasinmaz_adresi'] ?: '-'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ada/Parsel:</span>
                        <span class="info-value"><?php echo ($musteri['ada'] ?: '-') . ' / ' . ($musteri['parsel'] ?: '-'); ?></span>
                    </div>
                    <?php if($musteri['sahibinden_link']): ?>
                    <div class="info-item">
                        <span class="info-label">Sahibinden:</span>
                        <span class="info-value">
                            <a href="<?php echo $musteri['sahibinden_link']; ?>" target="_blank" style="color: #e67e22;">
                                🔗 <?php echo $musteri['sahibinden_no'] ?: 'İlanı Görüntüle'; ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Arama Sayısı:</span>
                        <span class="info-value"><?php echo $musteri['arama_sayisi'] ?? 0; ?> kez</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Görüşme Notları -->
            <div class="notes-section">
                <h3>📝 Görüşme Notları</h3>
                
                <?php if(count($gorusme_notlari) > 0): ?>
                    <?php foreach($gorusme_notlari as $not): ?>
                    <div class="note-item">
                        <div class="note-date">
                            📅 <?php echo date('d.m.Y H:i', strtotime($not['gorusme_tarihi'])); ?>
                        </div>
                        <div class="note-text">
                            <?php echo nl2br(htmlspecialchars($not['gorusme_notu'])); ?>
                        </div>
                        <div class="note-author">
                            - <?php echo htmlspecialchars($not['gorusen_user_adi']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8c8d;">Henüz görüşme notu eklenmemiş.</p>
                <?php endif; ?>
                
                <!-- Not Ekleme Formu -->
                <div class="add-note-form">
                    <h4>Yeni Not Ekle</h4>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Görüşme Notu:</label>
                            <textarea name="gorusme_notu" required placeholder="Müşteriyle yapılan görüşme detayları..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Görüşme Tarihi:</label>
                            <input type="datetime-local" name="gorusme_tarihi" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Not Ekle</button>
                    </form>
                </div>
            </div>
            
            <!-- Alıcıya Uygun İlanlar -->
            <?php if($musteri_tip == 'alici' && count($uygun_ilanlar) > 0): ?>
            <div class="properties-section">
                <h3>🏠 Bütçesine Uygun İlanlar</h3>
                <div class="property-grid">
                    <?php foreach($uygun_ilanlar as $ilan): ?>
                    <div class="property-card">
                        <?php if($ilan['image_path']): ?>
                        <img src="../../<?php echo $ilan['image_path']; ?>" class="property-image">
                        <?php else: ?>
                        <div class="property-image" style="display: flex; align-items: center; justify-content: center;">
                            📷 Resim Yok
                        </div>
                        <?php endif; ?>
                        <div class="property-info">
                            <div class="property-title">
                                <?php echo htmlspecialchars($ilan['baslik'] ?? $ilan['title'] ?? ''); ?>
                            </div>
                            <div class="property-price">
                                <?php echo number_format($ilan['fiyat'] ?? $ilan['price'] ?? 0, 0, ',', '.'); ?> ₺
                            </div>
                            <div class="property-location">
                                📍 <?php echo ($ilan['ilce'] ?? '') . ', ' . ($ilan['mahalle'] ?? ''); ?>
                            </div>
                            <div style="margin-top: 10px;">
                                <a href="../../pages/detail.php?id=<?php echo $ilan['id']; ?>" 
                                   target="_blank" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">
                                    Detay
                                </a>
                                <button onclick="sendPropertySMS(<?php echo $ilan['id']; ?>)" 
                                        class="btn btn-success" style="font-size: 12px; padding: 5px 10px;">
                                    SMS Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function sendSMS() {
        alert('SMS gönderme sistemi yakında aktif olacak!');
    }
    
    function sendPropertySMS(propertyId) {
        alert('İlan ID ' + propertyId + ' için SMS gönderilecek. (Yakında aktif)');
    }
    </script>
</body>
=======
<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';
$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';

// Parametreleri al
$musteri_tip = $_GET['tip'] ?? '';
$musteri_id = $_GET['id'] ?? 0;

if(!$musteri_tip || !$musteri_id) {
    header("Location: index.php");
    exit();
}

// Müşteri bilgilerini çek
if($musteri_tip == 'alici') {
    $sql = "SELECT * FROM crm_alici_musteriler WHERE id = :id";
    if($current_user_role != 'admin') {
        $sql .= " AND ekleyen_user_id = :user_id";
    }
} else {
    $sql = "SELECT * FROM crm_satici_musteriler WHERE id = :id";
    if($current_user_role != 'admin') {
        $sql .= " AND ekleyen_user_id = :user_id";
    }
}

$stmt = $db->prepare($sql);
$params = [':id' => $musteri_id];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$musteri = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$musteri) {
    header("Location: index.php");
    exit();
}

// Görüşme notlarını çek
$notes_sql = "SELECT * FROM crm_gorusme_notlari 
              WHERE musteri_tipi = :tip AND musteri_id = :id 
              ORDER BY gorusme_tarihi DESC";
$notes_stmt = $db->prepare($notes_sql);
$notes_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
$gorusme_notlari = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);

// SMS kayıtlarını çek
$sms_sql = "SELECT * FROM crm_sms_kayitlari 
            WHERE musteri_tipi = :tip AND musteri_id = :id 
            ORDER BY gonderim_tarihi DESC";
$sms_stmt = $db->prepare($sms_sql);
$sms_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
$sms_kayitlari = $sms_stmt->fetchAll(PDO::FETCH_ASSOC);

// Eğer alıcı müşteri ise, bütçesine uygun ilanları çek
$uygun_ilanlar = [];
if($musteri_tip == 'alici') {
    $ilan_sql = "SELECT p.*, pi.image_path 
                 FROM properties p 
                 LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
                 WHERE p.durum = 'aktif' 
                 AND p.fiyat BETWEEN :min_butce AND :max_butce";
    
    // Lokasyon filtresi
    $ilan_params = [
        ':min_butce' => $musteri['min_butce'],
        ':max_butce' => $musteri['max_butce']
    ];
    
    if($musteri['aranan_il']) {
        $ilan_sql .= " AND p.il = :il";
        $ilan_params[':il'] = $musteri['aranan_il'];
    }
    
    $ilan_sql .= " ORDER BY p.fiyat ASC LIMIT 10";
    
    $ilan_stmt = $db->prepare($ilan_sql);
    $ilan_stmt->execute($ilan_params);
    $uygun_ilanlar = $ilan_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Görüşme notu ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gorusme_notu'])) {
    $gorusme_notu = trim($_POST['gorusme_notu']);
    $gorusme_tarihi = $_POST['gorusme_tarihi'] ?? date('Y-m-d H:i:s');
    
    $not_sql = "INSERT INTO crm_gorusme_notlari 
                (musteri_tipi, musteri_id, gorusme_tarihi, gorusme_notu, gorusen_user_id, gorusen_user_adi) 
                VALUES (:tip, :id, :tarih, :not, :user_id, :user_adi)";
    
    $not_stmt = $db->prepare($not_sql);
    $not_stmt->execute([
        ':tip' => $musteri_tip,
        ':id' => $musteri_id,
        ':tarih' => $gorusme_tarihi,
        ':not' => $gorusme_notu,
        ':user_id' => $current_user_id,
        ':user_adi' => $current_user_name
    ]);
    
    // Sayfayı yenile
    header("Location: musteri-detay.php?tip=$musteri_tip&id=$musteri_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $musteri['ad'] . ' ' . $musteri['soyad']; ?> - Müşteri Kartı</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Layout Düzeltmeleri */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .admin-content {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .customer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .customer-name {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .customer-type {
            display: inline-block;
            padding: 5px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 14px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-item {
            display: flex;
            padding: 8px 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #7f8c8d;
            min-width: 120px;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .notes-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .note-item {
            border-left: 3px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        
        .note-date {
            color: #7f8c8d;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .note-text {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .note-author {
            color: #95a5a6;
            font-size: 12px;
            font-style: italic;
        }
        
        .add-note-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 100px;
        }
        
        .properties-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .property-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .property-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .property-info {
            padding: 15px;
        }
        
        .property-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .property-price {
            color: #27ae60;
            font-size: 18px;
            font-weight: bold;
        }
        
        .property-location {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-back {
            background: #95a5a6;
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .social-link {
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-size: 12px;
        }
        
        .social-link:hover {
            background: #e0e0e0;
        }
    </style>
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
                    <a href="../dashboard.php">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="../properties/list.php">
                        <span class="icon">🏢</span>
                        <span>İlanlar</span>
                    </a>
                </li>
                <li>
                    <a href="../properties/add-step1.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="index.php" class="active">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <li>
                    <a href="../users/list.php">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li>
                    <a href="../settings.php">
                        <span class="icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <span class="icon">🚪</span>
                        <span>Çıkış</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <!-- Müşteri Başlık -->
            <div class="customer-header">
                <div class="customer-name">
                    <?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?>
                </div>
                <span class="customer-type">
                    <?php echo $musteri_tip == 'alici' ? '👥 Alıcı Müşteri' : '🏠 Satıcı Müşteri'; ?>
                </span>
            </div>
            
            <!-- Aksiyonlar -->
            <div class="action-buttons">
                <a href="<?php echo $musteri_tip; ?>-duzenle.php?id=<?php echo $musteri_id; ?>" class="btn btn-warning">
                    ✏️ Düzenle
                </a>
                <a href="tel:0<?php echo $musteri['telefon']; ?>" class="btn btn-success">
                    📞 Ara: 0<?php echo $musteri['telefon']; ?>
                </a>
                <button onclick="sendSMS()" class="btn btn-primary">
                    📱 SMS Gönder
                </button>
                <a href="<?php echo $musteri_tip; ?>-liste.php" class="btn btn-back">
                    ← Listeye Dön
                </a>
            </div>
            
            <!-- Bilgi Kartları -->
            <div class="info-grid">
                <!-- İletişim Bilgileri -->
                <div class="info-card">
                    <h3>📞 İletişim Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Telefon:</span>
                        <span class="info-value">0<?php echo $musteri['telefon']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">E-posta:</span>
                        <span class="info-value"><?php echo $musteri['email'] ?: '-'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adres:</span>
                        <span class="info-value"><?php echo $musteri['adres'] ?: '-'; ?></span>
                    </div>
                    <?php if($musteri_tip == 'alici'): ?>
                    <div class="info-item">
                        <span class="info-label">İş:</span>
                        <span class="info-value"><?php echo $musteri['is_bilgisi'] ?: '-'; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($musteri['facebook'] || $musteri['instagram'] || $musteri['twitter']): ?>
                    <div class="social-links">
                        <?php if($musteri['facebook']): ?>
                        <a href="https://<?php echo $musteri['facebook']; ?>" target="_blank" class="social-link">
                            Facebook
                        </a>
                        <?php endif; ?>
                        <?php if($musteri['instagram']): ?>
                        <a href="https://instagram.com/<?php echo $musteri['instagram']; ?>" target="_blank" class="social-link">
                            Instagram
                        </a>
                        <?php endif; ?>
                        <?php if($musteri['twitter']): ?>
                        <a href="https://twitter.com/<?php echo $musteri['twitter']; ?>" target="_blank" class="social-link">
                            Twitter
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Taşınmaz/Arama Bilgileri -->
                <div class="info-card">
                    <?php if($musteri_tip == 'alici'): ?>
                    <h3>🔍 Arama Kriterleri</h3>
                    <div class="info-item">
                        <span class="info-label">Taşınmaz:</span>
                        <span class="info-value"><?php echo $musteri['aranan_tasinmaz']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Bölge:</span>
                        <span class="info-value">
                            <?php 
                            $bolge = [];
                            if($musteri['aranan_il']) $bolge[] = $musteri['aranan_il'];
                            if($musteri['aranan_ilce']) $bolge[] = $musteri['aranan_ilce'];
                            if($musteri['aranan_mahalle']) $bolge[] = $musteri['aranan_mahalle'];
                            if($musteri['aranan_koy']) $bolge[] = $musteri['aranan_koy'];
                            echo implode(', ', $bolge) ?: '-';
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Bütçe:</span>
                        <span class="info-value">
                            <?php echo number_format($musteri['min_butce'], 0, ',', '.'); ?> - 
                            <?php echo number_format($musteri['max_butce'], 0, ',', '.'); ?> ₺
                        </span>
                    </div>
                    <?php else: ?>
                    <h3>🏘️ Taşınmaz Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Taşınmaz Cinsi:</span>
                        <span class="info-value"><?php echo $musteri['tasinmaz_cinsi'] ?: '-'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adres:</span>
                        <span class="info-value"><?php echo $musteri['tasinmaz_adresi'] ?: '-'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ada/Parsel:</span>
                        <span class="info-value"><?php echo ($musteri['ada'] ?: '-') . ' / ' . ($musteri['parsel'] ?: '-'); ?></span>
                    </div>
                    <?php if($musteri['sahibinden_link']): ?>
                    <div class="info-item">
                        <span class="info-label">Sahibinden:</span>
                        <span class="info-value">
                            <a href="<?php echo $musteri['sahibinden_link']; ?>" target="_blank" style="color: #e67e22;">
                                🔗 <?php echo $musteri['sahibinden_no'] ?: 'İlanı Görüntüle'; ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Arama Sayısı:</span>
                        <span class="info-value"><?php echo $musteri['arama_sayisi'] ?? 0; ?> kez</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Görüşme Notları -->
            <div class="notes-section">
                <h3>📝 Görüşme Notları</h3>
                
                <?php if(count($gorusme_notlari) > 0): ?>
                    <?php foreach($gorusme_notlari as $not): ?>
                    <div class="note-item">
                        <div class="note-date">
                            📅 <?php echo date('d.m.Y H:i', strtotime($not['gorusme_tarihi'])); ?>
                        </div>
                        <div class="note-text">
                            <?php echo nl2br(htmlspecialchars($not['gorusme_notu'])); ?>
                        </div>
                        <div class="note-author">
                            - <?php echo htmlspecialchars($not['gorusen_user_adi']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8c8d;">Henüz görüşme notu eklenmemiş.</p>
                <?php endif; ?>
                
                <!-- Not Ekleme Formu -->
                <div class="add-note-form">
                    <h4>Yeni Not Ekle</h4>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Görüşme Notu:</label>
                            <textarea name="gorusme_notu" required placeholder="Müşteriyle yapılan görüşme detayları..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Görüşme Tarihi:</label>
                            <input type="datetime-local" name="gorusme_tarihi" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Not Ekle</button>
                    </form>
                </div>
            </div>
            
            <!-- Alıcıya Uygun İlanlar -->
            <?php if($musteri_tip == 'alici' && count($uygun_ilanlar) > 0): ?>
            <div class="properties-section">
                <h3>🏠 Bütçesine Uygun İlanlar</h3>
                <div class="property-grid">
                    <?php foreach($uygun_ilanlar as $ilan): ?>
                    <div class="property-card">
                        <?php if($ilan['image_path']): ?>
                        <img src="../../<?php echo $ilan['image_path']; ?>" class="property-image">
                        <?php else: ?>
                        <div class="property-image" style="display: flex; align-items: center; justify-content: center;">
                            📷 Resim Yok
                        </div>
                        <?php endif; ?>
                        <div class="property-info">
                            <div class="property-title">
                                <?php echo htmlspecialchars($ilan['baslik'] ?? $ilan['title'] ?? ''); ?>
                            </div>
                            <div class="property-price">
                                <?php echo number_format($ilan['fiyat'] ?? $ilan['price'] ?? 0, 0, ',', '.'); ?> ₺
                            </div>
                            <div class="property-location">
                                📍 <?php echo ($ilan['ilce'] ?? '') . ', ' . ($ilan['mahalle'] ?? ''); ?>
                            </div>
                            <div style="margin-top: 10px;">
                                <a href="../../pages/detail.php?id=<?php echo $ilan['id']; ?>" 
                                   target="_blank" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">
                                    Detay
                                </a>
                                <button onclick="sendPropertySMS(<?php echo $ilan['id']; ?>)" 
                                        class="btn btn-success" style="font-size: 12px; padding: 5px 10px;">
                                    SMS Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function sendSMS() {
        alert('SMS gönderme sistemi yakında aktif olacak!');
    }
    
    function sendPropertySMS(propertyId) {
        alert('İlan ID ' + propertyId + ' için SMS gönderilecek. (Yakında aktif)');
    }
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>