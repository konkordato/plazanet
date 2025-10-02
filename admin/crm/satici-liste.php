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

// Filtreleme parametreleri
$search = $_GET['search'] ?? '';
$filter_tasinmaz = $_GET['tasinmaz'] ?? '';

// Sorgu oluştur
$sql = "SELECT * FROM crm_satici_musteriler WHERE 1=1";
$params = [];

// Admin değilse sadece kendi müşterilerini görsün
if($current_user_role != 'admin') {
    $sql .= " AND ekleyen_user_id = :user_id";
    $params[':user_id'] = $current_user_id;
}

// Arama filtresi
if($search) {
    $sql .= " AND (ad LIKE :search OR soyad LIKE :search OR telefon LIKE :search OR sahibinden_no LIKE :search)";
    $params[':search'] = "%$search%";
}

// Taşınmaz cinsi filtresi
if($filter_tasinmaz) {
    $sql .= " AND tasinmaz_cinsi = :tasinmaz";
    $params[':tasinmaz'] = $filter_tasinmaz;
}

$sql .= " ORDER BY ekleme_tarihi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats_sql = "SELECT 
    COUNT(*) as toplam,
    SUM(arama_sayisi) as toplam_arama
    FROM crm_satici_musteriler";
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

// Başarı mesajı kontrolü
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satıcı Müşteriler - CRM</title>
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
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
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
            background: #e67e22;
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
        .sahibinden-link {
            color: #e67e22;
            text-decoration: none;
            font-size: 12px;
        }
        .sahibinden-link:hover {
            text-decoration: underline;
        }
        .arama-badge {
            background: #fff3e0;
            color: #e65100;
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
        .btn-call {
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
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
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
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
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
            <div class="container">
                <h1>🏠 Satıcı Müşteri Listesi</h1>
                
                <?php if($success_message): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <!-- İstatistikler -->
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['toplam']; ?></span>
                        <span class="stat-label">Toplam Satıcı</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['toplam_arama'] ?? 0; ?></span>
                        <span class="stat-label">Toplam Arama</span>
                    </div>
                </div>
                
                <!-- Yeni Ekle Butonu -->
                <a href="satici-ekle.php" class="add-new-btn">➕ Yeni Satıcı Müşteri Ekle</a>
                
                <!-- Filtreler -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label>Ara</label>
                            <input type="text" name="search" placeholder="Ad, soyad, telefon veya ilan no..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Taşınmaz Cinsi</label>
                            <select name="tasinmaz">
                                <option value="">Tümü</option>
                                <option value="Daire" <?php echo $filter_tasinmaz == 'Daire' ? 'selected' : ''; ?>>Daire</option>
                                <option value="Müstakil Ev" <?php echo $filter_tasinmaz == 'Müstakil Ev' ? 'selected' : ''; ?>>Müstakil Ev</option>
                                <option value="Villa" <?php echo $filter_tasinmaz == 'Villa' ? 'selected' : ''; ?>>Villa</option>
                                <option value="Arsa" <?php echo $filter_tasinmaz == 'Arsa' ? 'selected' : ''; ?>>Arsa</option>
                                <option value="Tarla" <?php echo $filter_tasinmaz == 'Tarla' ? 'selected' : ''; ?>>Tarla</option>
                                <option value="Dükkan" <?php echo $filter_tasinmaz == 'Dükkan' ? 'selected' : ''; ?>>Dükkan</option>
                                <option value="Ofis" <?php echo $filter_tasinmaz == 'Ofis' ? 'selected' : ''; ?>>Ofis</option>
                                <option value="Diğer" <?php echo $filter_tasinmaz == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrele</button>
                        <a href="satici-liste.php" class="btn btn-secondary">🔄 Temizle</a>
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
                                <th>Taşınmaz</th>
                                <th>Ada/Parsel</th>
                                <th>Sahibinden</th>
                                <th>Arama</th>
                                <?php if($current_user_role == 'admin'): ?>
                                <th>Danışman</th>
                                <?php endif; ?>
                                <th>Kayıt</th>
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
                                <td><?php echo htmlspecialchars($musteri['tasinmaz_cinsi'] ?: '-'); ?></td>
                                <td>
                                    <?php 
                                    if($musteri['ada'] || $musteri['parsel']) {
                                        echo $musteri['ada'] . '/' . $musteri['parsel'];
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($musteri['sahibinden_link']): ?>
                                        <a href="<?php echo $musteri['sahibinden_link']; ?>" target="_blank" class="sahibinden-link">
                                            🔗 <?php echo $musteri['sahibinden_no'] ?: 'İlan Görüntüle'; ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="arama-badge">
                                        📞 <?php echo $musteri['arama_sayisi']; ?> kez
                                    </span>
                                </td>
                                <?php if($current_user_role == 'admin'): ?>
                                <td><?php echo htmlspecialchars($musteri['ekleyen_user_adi']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d.m.Y', strtotime($musteri['ekleme_tarihi'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="musteri-detay.php?tip=satici&id=<?php echo $musteri['id']; ?>" 
                                           class="btn-action btn-detail">Detay</a>
                                        <a href="satici-duzenle.php?id=<?php echo $musteri['id']; ?>" 
                                           class="btn-action btn-edit">Düzenle</a>
                                        <button onclick="aramaKaydet(<?php echo $musteri['id']; ?>)" 
                                                class="btn-action btn-call">Arandı</button>
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
                    <h3>Henüz satıcı müşteri eklenmemiş</h3>
                    <p>Hemen ilk satıcı müşterinizi ekleyin!</p>
                    <a href="satici-ekle.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ İlk Satıcıyı Ekle
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function aramaKaydet(id) {
        if(confirm('Müşteriyi aradınız mı?')) {
            // AJAX ile arama sayısını artır
            fetch('ajax/arama-kaydet.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'musteri_id=' + id + '&tip=satici'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Arama kaydedildi!');
                    location.reload();
                }
            });
        }
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

// Filtreleme parametreleri
$search = $_GET['search'] ?? '';
$filter_tasinmaz = $_GET['tasinmaz'] ?? '';

// Sorgu oluştur
$sql = "SELECT * FROM crm_satici_musteriler WHERE 1=1";
$params = [];

// Admin değilse sadece kendi müşterilerini görsün
if($current_user_role != 'admin') {
    $sql .= " AND ekleyen_user_id = :user_id";
    $params[':user_id'] = $current_user_id;
}

// Arama filtresi
if($search) {
    $sql .= " AND (ad LIKE :search OR soyad LIKE :search OR telefon LIKE :search OR sahibinden_no LIKE :search)";
    $params[':search'] = "%$search%";
}

// Taşınmaz cinsi filtresi
if($filter_tasinmaz) {
    $sql .= " AND tasinmaz_cinsi = :tasinmaz";
    $params[':tasinmaz'] = $filter_tasinmaz;
}

$sql .= " ORDER BY ekleme_tarihi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats_sql = "SELECT 
    COUNT(*) as toplam,
    SUM(arama_sayisi) as toplam_arama
    FROM crm_satici_musteriler";
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

// Başarı mesajı kontrolü
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satıcı Müşteriler - CRM</title>
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
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
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
            background: #e67e22;
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
        .sahibinden-link {
            color: #e67e22;
            text-decoration: none;
            font-size: 12px;
        }
        .sahibinden-link:hover {
            text-decoration: underline;
        }
        .arama-badge {
            background: #fff3e0;
            color: #e65100;
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
        .btn-call {
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
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
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
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
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
            <div class="container">
                <h1>🏠 Satıcı Müşteri Listesi</h1>
                
                <?php if($success_message): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <!-- İstatistikler -->
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['toplam']; ?></span>
                        <span class="stat-label">Toplam Satıcı</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['toplam_arama'] ?? 0; ?></span>
                        <span class="stat-label">Toplam Arama</span>
                    </div>
                </div>
                
                <!-- Yeni Ekle Butonu -->
                <a href="satici-ekle.php" class="add-new-btn">➕ Yeni Satıcı Müşteri Ekle</a>
                
                <!-- Filtreler -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label>Ara</label>
                            <input type="text" name="search" placeholder="Ad, soyad, telefon veya ilan no..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Taşınmaz Cinsi</label>
                            <select name="tasinmaz">
                                <option value="">Tümü</option>
                                <option value="Daire" <?php echo $filter_tasinmaz == 'Daire' ? 'selected' : ''; ?>>Daire</option>
                                <option value="Müstakil Ev" <?php echo $filter_tasinmaz == 'Müstakil Ev' ? 'selected' : ''; ?>>Müstakil Ev</option>
                                <option value="Villa" <?php echo $filter_tasinmaz == 'Villa' ? 'selected' : ''; ?>>Villa</option>
                                <option value="Arsa" <?php echo $filter_tasinmaz == 'Arsa' ? 'selected' : ''; ?>>Arsa</option>
                                <option value="Tarla" <?php echo $filter_tasinmaz == 'Tarla' ? 'selected' : ''; ?>>Tarla</option>
                                <option value="Dükkan" <?php echo $filter_tasinmaz == 'Dükkan' ? 'selected' : ''; ?>>Dükkan</option>
                                <option value="Ofis" <?php echo $filter_tasinmaz == 'Ofis' ? 'selected' : ''; ?>>Ofis</option>
                                <option value="Diğer" <?php echo $filter_tasinmaz == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrele</button>
                        <a href="satici-liste.php" class="btn btn-secondary">🔄 Temizle</a>
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
                                <th>Taşınmaz</th>
                                <th>Ada/Parsel</th>
                                <th>Sahibinden</th>
                                <th>Arama</th>
                                <?php if($current_user_role == 'admin'): ?>
                                <th>Danışman</th>
                                <?php endif; ?>
                                <th>Kayıt</th>
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
                                <td><?php echo htmlspecialchars($musteri['tasinmaz_cinsi'] ?: '-'); ?></td>
                                <td>
                                    <?php 
                                    if($musteri['ada'] || $musteri['parsel']) {
                                        echo $musteri['ada'] . '/' . $musteri['parsel'];
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($musteri['sahibinden_link']): ?>
                                        <a href="<?php echo $musteri['sahibinden_link']; ?>" target="_blank" class="sahibinden-link">
                                            🔗 <?php echo $musteri['sahibinden_no'] ?: 'İlan Görüntüle'; ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="arama-badge">
                                        📞 <?php echo $musteri['arama_sayisi']; ?> kez
                                    </span>
                                </td>
                                <?php if($current_user_role == 'admin'): ?>
                                <td><?php echo htmlspecialchars($musteri['ekleyen_user_adi']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date('d.m.Y', strtotime($musteri['ekleme_tarihi'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="musteri-detay.php?tip=satici&id=<?php echo $musteri['id']; ?>" 
                                           class="btn-action btn-detail">Detay</a>
                                        <a href="satici-duzenle.php?id=<?php echo $musteri['id']; ?>" 
                                           class="btn-action btn-edit">Düzenle</a>
                                        <button onclick="aramaKaydet(<?php echo $musteri['id']; ?>)" 
                                                class="btn-action btn-call">Arandı</button>
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
                    <h3>Henüz satıcı müşteri eklenmemiş</h3>
                    <p>Hemen ilk satıcı müşterinizi ekleyin!</p>
                    <a href="satici-ekle.php" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ İlk Satıcıyı Ekle
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function aramaKaydet(id) {
        if(confirm('Müşteriyi aradınız mı?')) {
            // AJAX ile arama sayısını artır
            fetch('ajax/arama-kaydet.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'musteri_id=' + id + '&tip=satici'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Arama kaydedildi!');
                    location.reload();
                }
            });
        }
    }
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>