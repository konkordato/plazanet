<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';

// Filtreleme parametreleri
$search = $_GET['search'] ?? '';
$filter_il = $_GET['il'] ?? '';
$filter_ilce = $_GET['ilce'] ?? '';

// Sorgu oluştur
$sql = "SELECT * FROM crm_alici_musteriler WHERE 1=1";
$params = [];

// Admin değilse sadece kendi müşterilerini görsün
if($current_user_role != 'admin') {
    $sql .= " AND ekleyen_user_id = :user_id";
    $params[':user_id'] = $current_user_id;
}

// Arama filtresi
if($search) {
    $sql .= " AND (ad LIKE :search OR soyad LIKE :search OR telefon LIKE :search)";
    $params[':search'] = "%$search%";
}

// İl filtresi
if($filter_il) {
    $sql .= " AND aranan_il = :il";
    $params[':il'] = $filter_il;
}

// İlçe filtresi
if($filter_ilce) {
    $sql .= " AND aranan_ilce = :ilce";
    $params[':ilce'] = $filter_ilce;
}

$sql .= " ORDER BY ekleme_tarihi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats_sql = "SELECT 
    COUNT(*) as toplam,
    COUNT(DISTINCT ekleyen_user_id) as toplam_danisman
    FROM crm_alici_musteriler";
if($current_user_role != 'admin') {
    $stats_sql .= " WHERE ekleyen_user_id = :user_id";
}

$stats_stmt = $db->prepare($stats_sql);
if($current_user_role != 'admin') {
    $stats_stmt->execute([':user_id' => $current_user_id]);
} else {
    $stats_stmt->execute();
}
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alıcı Müşteriler - CRM</title>
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
        }
        
        .container {
            padding: 20px;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .customer-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .customer-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .customer-table th {
            background: #34495e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        .customer-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .customer-table tr:hover {
            background: #f8f9fa;
        }
        .phone-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .phone-link:hover {
            text-decoration: underline;
        }
        .budget-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-detail {
            background: #3498db;
            color: white;
        }
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        .btn-sms {
            background: #27ae60;
            color: white;
        }
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .add-new-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #7f8c8d;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
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
            <div class="container" style="padding: 20px;">
                <h1>👥 Alıcı Müşteri Listesi</h1>
                
                <!-- İstatistikler -->
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['toplam']; ?></span>
                        <span class="stat-label">Toplam Müşteri</span>
                    </div>
                    <?php if($current_user_role == 'admin'): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['toplam_danisman']; ?></span>
                        <span class="stat-label">Aktif Danışman</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Yeni Ekle Butonu -->
                <a href="alici-ekle.php" class="add-new-btn">➕ Yeni Alıcı Müşteri Ekle</a>
                
                <!-- Filtreler -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label>Ara</label>
                            <input type="text" name="search" placeholder="Ad, soyad veya telefon..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label>İl</label>
                            <input type="text" name="il" placeholder="İl adı..." 
                                   value="<?php echo htmlspecialchars($filter_il); ?>">
                        </div>
                        <div class="filter-group">
                            <label>İlçe</label>
                            <input type="text" name="ilce" placeholder="İlçe adı..." 
                                   value="<?php echo htmlspecialchars($filter_ilce); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrele</button>
                        <a href="alici-liste.php" class="btn btn-secondary">🔄 Temizle</a>
                    </form>
                </div>
                
                <!-- Müşteri Tablosu -->
                <?php if(count($musteriler) > 0): ?>
                <div class="customer-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>Telefon</th>
                                <th>Aradığı Bölge</th>
                                <th>Bütçe</th>
                                <th>Taşınmaz Tipi</th>
                                <?php if($current_user_role == 'admin'): ?>
                                <th>Danışman</th>
                                <?php endif; ?>
                                <th>Kayıt Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($musteriler as $musteri): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?></strong>
                                </td>
                                <td>
                                    <a href="tel:0<?php echo $musteri['telefon']; ?>" class="phone-link">
                                        📱 0<?php echo $musteri['telefon']; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $bolge = [];
                                    if($musteri['aranan_il']) $bolge[] = $musteri['aranan_il'];
                                    if($musteri['aranan_ilce']) $bolge[] = $musteri['aranan_ilce'];
                                    if($musteri['aranan_mahalle']) $bolge[] = $musteri['aranan_mahalle'];
                                    echo implode(', ', $bolge) ?: '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="budget-badge">
                                        <?php 
                                        echo number_format($musteri['min_butce'], 0, ',', '.') . ' - ' . 
                                             number_format($musteri['max_butce'], 0, ',', '.') . ' ₺';
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $musteri['aranan_tasinmaz']; ?></td>
                                <?php if($current_user_role == 'admin'): ?>
                                <td><?php echo htmlspecialchars($musteri['ekleyen_user_adi']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d.m.Y', strtotime($musteri['ekleme_tarihi'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="musteri-detay.php?tip=alici&id=<?php echo $musteri['id']; ?>" 
                                           class="btn-action btn-detail">Detay</a>
                                        <a href="alici-duzenle.php?id=<?php echo $musteri['id']; ?>" 
                                           class="btn-action btn-edit">Düzenle</a>
                                        <button onclick="sendSMS(<?php echo $musteri['id']; ?>)" 
                                                class="btn-action btn-sms">SMS</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Henüz alıcı müşteri eklenmemiş</h3>
                    <p>Hemen ilk müşterinizi ekleyin!</p>
                    <a href="alici-ekle.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ İlk Müşteriyi Ekle
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function sendSMS(id) {
        alert('SMS gönderme sistemi yakında aktif olacak!');
        // İlerleyen aşamada SMS entegrasyonu eklenecek
    }
    </script>
</body>
</html>