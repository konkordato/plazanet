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

// Filtreler
$filter = $_GET['filter'] ?? 'bekleyen';
$date_filter = $_GET['date'] ?? '';

// Hatırlatmaları okundu olarak işaretle
if(isset($_GET['okundu']) && $_GET['id']) {
    $update_sql = "UPDATE crm_hatirlatmalar SET okundu = 1 WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($update_sql);
    $stmt->execute([':id' => $_GET['id'], ':user_id' => $current_user_id]);
    header("Location: hatirlatmalar.php");
    exit();
}

// Hatırlatmayı tamamla
if(isset($_GET['tamamla']) && $_GET['id']) {
    $update_sql = "UPDATE crm_hatirlatmalar SET durum = 'tamamlandi' WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($update_sql);
    $stmt->execute([':id' => $_GET['id'], ':user_id' => $current_user_id]);
    header("Location: hatirlatmalar.php");
    exit();
}

// Hatırlatmaları çek
$sql = "SELECT h.*, 
        CASE 
            WHEN h.musteri_tipi = 'alici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_alici_musteriler WHERE id = h.musteri_id)
            WHEN h.musteri_tipi = 'satici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_satici_musteriler WHERE id = h.musteri_id)
            ELSE NULL
        END as musteri_adi,
        e.baslik as etkinlik_baslik
        FROM crm_hatirlatmalar h
        LEFT JOIN crm_takvim_etkinlikler e ON h.etkinlik_id = e.id
        WHERE h.user_id = :user_id";

// Filtreleme
if($filter == 'bekleyen') {
    $sql .= " AND h.durum = 'aktif' AND h.hatirlatma_tarihi >= CURDATE()";
} elseif($filter == 'gecmis') {
    $sql .= " AND h.hatirlatma_tarihi < CURDATE() AND h.durum = 'aktif'";
} elseif($filter == 'tamamlanan') {
    $sql .= " AND h.durum = 'tamamlandi'";
} elseif($filter == 'okunmamis') {
    $sql .= " AND h.okundu = 0 AND h.durum = 'aktif'";
}

if($date_filter) {
    $sql .= " AND DATE(h.hatirlatma_tarihi) = :date_filter";
}

$sql .= " ORDER BY h.hatirlatma_tarihi ASC";

$stmt = $db->prepare($sql);
$params = [':user_id' => $current_user_id];
if($date_filter) {
    $params[':date_filter'] = $date_filter;
}
$stmt->execute($params);
$hatirlatmalar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats_sql = "SELECT 
    COUNT(CASE WHEN durum = 'aktif' AND hatirlatma_tarihi >= CURDATE() THEN 1 END) as bekleyen,
    COUNT(CASE WHEN hatirlatma_tarihi < CURDATE() AND durum = 'aktif' THEN 1 END) as gecmis,
    COUNT(CASE WHEN durum = 'tamamlandi' THEN 1 END) as tamamlanan,
    COUNT(CASE WHEN okundu = 0 AND durum = 'aktif' THEN 1 END) as okunmamis
    FROM crm_hatirlatmalar 
    WHERE user_id = :user_id";

$stmt = $db->prepare($stats_sql);
$stmt->execute([':user_id' => $current_user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hatırlatmalarım - CRM</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Layout */
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            background: #f8f9fa;
        }
        
        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        /* Reminders List */
        .reminders-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .reminder-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s;
        }
        
        .reminder-item:hover {
            background: #f8f9fa;
        }
        
        .reminder-item.unread {
            background: #fff9e6;
            border-left: 4px solid #f39c12;
        }
        
        .reminder-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }
        
        .icon-arama { background: #e8f5e9; color: #27ae60; }
        .icon-gorusme { background: #e3f2fd; color: #3498db; }
        .icon-odeme { background: #fff3e0; color: #f39c12; }
        .icon-diger { background: #f3e5f5; color: #9b59b6; }
        
        .reminder-content {
            flex: 1;
        }
        
        .reminder-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .reminder-message {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .reminder-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #95a5a6;
        }
        
        .reminder-time {
            font-weight: 600;
            color: #2c3e50;
            margin-right: 15px;
        }
        
        .reminder-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-complete {
            background: #27ae60;
            color: white;
        }
        
        .btn-mark-read {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        /* Priority Badge */
        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .priority-acil { background: #e74c3c; color: white; }
        .priority-yuksek { background: #f39c12; color: white; }
        .priority-normal { background: #3498db; color: white; }
        .priority-dusuk { background: #95a5a6; color: white; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Time Badge */
        .time-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .time-today { background: #e8f5e9; color: #27ae60; }
        .time-tomorrow { background: #fff3cd; color: #856404; }
        .time-overdue { background: #f8d7da; color: #721c24; }
        .time-future { background: #d1ecf1; color: #0c5460; }
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
            <h1>🔔 Hatırlatmalarım</h1>
            
            <!-- Quick Actions -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <a href="hatirlatma-ekle.php" class="action-btn btn-complete">
                    ➕ Yeni Hatırlatma
                </a>
                <a href="takvim.php" class="action-btn" style="background: #3498db; color: white;">
                    📅 Takvime Dön
                </a>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <a href="?filter=bekleyen" class="stat-card <?php echo $filter == 'bekleyen' ? 'active' : ''; ?>">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-value"><?php echo $stats['bekleyen']; ?></div>
                    <div class="stat-label">Bekleyen</div>
                </a>
                <a href="?filter=gecmis" class="stat-card <?php echo $filter == 'gecmis' ? 'active' : ''; ?>">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-value"><?php echo $stats['gecmis']; ?></div>
                    <div class="stat-label">Gecikmiş</div>
                </a>
                <a href="?filter=okunmamis" class="stat-card <?php echo $filter == 'okunmamis' ? 'active' : ''; ?>">
                    <div class="stat-icon">📬</div>
                    <div class="stat-value"><?php echo $stats['okunmamis']; ?></div>
                    <div class="stat-label">Okunmamış</div>
                </a>
                <a href="?filter=tamamlanan" class="stat-card <?php echo $filter == 'tamamlanan' ? 'active' : ''; ?>">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $stats['tamamlanan']; ?></div>
                    <div class="stat-label">Tamamlanan</div>
                </a>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="date" id="date_filter" value="<?php echo $date_filter; ?>" class="filter-btn">
                <button onclick="filterByDate()" class="filter-btn">Tarihe Göre Filtrele</button>
                <a href="hatirlatmalar.php" class="filter-btn">Tüm Hatırlatmalar</a>
            </div>
            
            <!-- Reminders List -->
            <div class="reminders-container">
                <?php if(count($hatirlatmalar) > 0): ?>
                    <?php foreach($hatirlatmalar as $hatirlatma): 
                        $tarih = strtotime($hatirlatma['hatirlatma_tarihi']);
                        $bugun = strtotime(date('Y-m-d'));
                        $yarin = strtotime(date('Y-m-d', strtotime('+1 day')));
                        
                        $time_class = 'time-future';
                        $time_text = date('d.m.Y H:i', $tarih);
                        
                        if(date('Y-m-d', $tarih) == date('Y-m-d')) {
                            $time_class = 'time-today';
                            $time_text = 'Bugün ' . date('H:i', $tarih);
                        } elseif(date('Y-m-d', $tarih) == date('Y-m-d', $yarin)) {
                            $time_class = 'time-tomorrow';
                            $time_text = 'Yarın ' . date('H:i', $tarih);
                        } elseif($tarih < $bugun) {
                            $time_class = 'time-overdue';
                        }
                    ?>
                    <div class="reminder-item <?php echo $hatirlatma['okundu'] == 0 ? 'unread' : ''; ?>">
                        <div class="reminder-icon icon-<?php echo $hatirlatma['hatirlatma_tipi']; ?>">
                            <?php
                            $icons = [
                                'arama' => '📞',
                                'gorusme' => '👥',
                                'odeme' => '💰',
                                'diger' => '📌'
                            ];
                            echo $icons[$hatirlatma['hatirlatma_tipi']] ?? '📌';
                            ?>
                        </div>
                        
                        <div class="reminder-content">
                            <div class="reminder-title">
                                <?php echo htmlspecialchars($hatirlatma['baslik']); ?>
                                <span class="priority-badge priority-<?php echo $hatirlatma['oncelik']; ?>">
                                    <?php echo ucfirst($hatirlatma['oncelik']); ?>
                                </span>
                            </div>
                            
                            <div class="reminder-message">
                                <?php echo htmlspecialchars($hatirlatma['mesaj']); ?>
                            </div>
                            
                            <div class="reminder-meta">
                                <?php if($hatirlatma['musteri_adi']): ?>
                                <span>👤 <?php echo htmlspecialchars($hatirlatma['musteri_adi']); ?></span>
                                <?php endif; ?>
                                
                                <?php if($hatirlatma['etkinlik_baslik']): ?>
                                <span>📅 <?php echo htmlspecialchars($hatirlatma['etkinlik_baslik']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="reminder-time">
                            <span class="time-badge <?php echo $time_class; ?>">
                                <?php echo $time_text; ?>
                            </span>
                        </div>
                        
                        <div class="reminder-actions">
                            <?php if($hatirlatma['durum'] == 'aktif'): ?>
                                <?php if($hatirlatma['okundu'] == 0): ?>
                                <a href="?okundu=1&id=<?php echo $hatirlatma['id']; ?>" class="action-btn btn-mark-read">
                                    Okundu
                                </a>
                                <?php endif; ?>
                                <a href="?tamamla=1&id=<?php echo $hatirlatma['id']; ?>" class="action-btn btn-complete">
                                    Tamamla
                                </a>
                            <?php else: ?>
                                <span style="color: #27ae60; font-size: 12px;">✅ Tamamlandı</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔔</div>
                        <h3>Hatırlatma Bulunmuyor</h3>
                        <p>Bu kriterlere uygun hatırlatma bulunmamaktadır.</p>
                        <a href="hatirlatma-ekle.php" class="action-btn btn-complete" style="margin-top: 20px; display: inline-block;">
                            Yeni Hatırlatma Ekle
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function filterByDate() {
        const date = document.getElementById('date_filter').value;
        if(date) {
            window.location.href = '?filter=<?php echo $filter; ?>&date=' + date;
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

// Filtreler
$filter = $_GET['filter'] ?? 'bekleyen';
$date_filter = $_GET['date'] ?? '';

// Hatırlatmaları okundu olarak işaretle
if(isset($_GET['okundu']) && $_GET['id']) {
    $update_sql = "UPDATE crm_hatirlatmalar SET okundu = 1 WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($update_sql);
    $stmt->execute([':id' => $_GET['id'], ':user_id' => $current_user_id]);
    header("Location: hatirlatmalar.php");
    exit();
}

// Hatırlatmayı tamamla
if(isset($_GET['tamamla']) && $_GET['id']) {
    $update_sql = "UPDATE crm_hatirlatmalar SET durum = 'tamamlandi' WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($update_sql);
    $stmt->execute([':id' => $_GET['id'], ':user_id' => $current_user_id]);
    header("Location: hatirlatmalar.php");
    exit();
}

// Hatırlatmaları çek
$sql = "SELECT h.*, 
        CASE 
            WHEN h.musteri_tipi = 'alici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_alici_musteriler WHERE id = h.musteri_id)
            WHEN h.musteri_tipi = 'satici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_satici_musteriler WHERE id = h.musteri_id)
            ELSE NULL
        END as musteri_adi,
        e.baslik as etkinlik_baslik
        FROM crm_hatirlatmalar h
        LEFT JOIN crm_takvim_etkinlikler e ON h.etkinlik_id = e.id
        WHERE h.user_id = :user_id";

// Filtreleme
if($filter == 'bekleyen') {
    $sql .= " AND h.durum = 'aktif' AND h.hatirlatma_tarihi >= CURDATE()";
} elseif($filter == 'gecmis') {
    $sql .= " AND h.hatirlatma_tarihi < CURDATE() AND h.durum = 'aktif'";
} elseif($filter == 'tamamlanan') {
    $sql .= " AND h.durum = 'tamamlandi'";
} elseif($filter == 'okunmamis') {
    $sql .= " AND h.okundu = 0 AND h.durum = 'aktif'";
}

if($date_filter) {
    $sql .= " AND DATE(h.hatirlatma_tarihi) = :date_filter";
}

$sql .= " ORDER BY h.hatirlatma_tarihi ASC";

$stmt = $db->prepare($sql);
$params = [':user_id' => $current_user_id];
if($date_filter) {
    $params[':date_filter'] = $date_filter;
}
$stmt->execute($params);
$hatirlatmalar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats_sql = "SELECT 
    COUNT(CASE WHEN durum = 'aktif' AND hatirlatma_tarihi >= CURDATE() THEN 1 END) as bekleyen,
    COUNT(CASE WHEN hatirlatma_tarihi < CURDATE() AND durum = 'aktif' THEN 1 END) as gecmis,
    COUNT(CASE WHEN durum = 'tamamlandi' THEN 1 END) as tamamlanan,
    COUNT(CASE WHEN okundu = 0 AND durum = 'aktif' THEN 1 END) as okunmamis
    FROM crm_hatirlatmalar 
    WHERE user_id = :user_id";

$stmt = $db->prepare($stats_sql);
$stmt->execute([':user_id' => $current_user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hatırlatmalarım - CRM</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Layout */
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            background: #f8f9fa;
        }
        
        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        /* Reminders List */
        .reminders-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .reminder-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s;
        }
        
        .reminder-item:hover {
            background: #f8f9fa;
        }
        
        .reminder-item.unread {
            background: #fff9e6;
            border-left: 4px solid #f39c12;
        }
        
        .reminder-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }
        
        .icon-arama { background: #e8f5e9; color: #27ae60; }
        .icon-gorusme { background: #e3f2fd; color: #3498db; }
        .icon-odeme { background: #fff3e0; color: #f39c12; }
        .icon-diger { background: #f3e5f5; color: #9b59b6; }
        
        .reminder-content {
            flex: 1;
        }
        
        .reminder-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .reminder-message {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .reminder-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #95a5a6;
        }
        
        .reminder-time {
            font-weight: 600;
            color: #2c3e50;
            margin-right: 15px;
        }
        
        .reminder-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-complete {
            background: #27ae60;
            color: white;
        }
        
        .btn-mark-read {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        /* Priority Badge */
        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .priority-acil { background: #e74c3c; color: white; }
        .priority-yuksek { background: #f39c12; color: white; }
        .priority-normal { background: #3498db; color: white; }
        .priority-dusuk { background: #95a5a6; color: white; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Time Badge */
        .time-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .time-today { background: #e8f5e9; color: #27ae60; }
        .time-tomorrow { background: #fff3cd; color: #856404; }
        .time-overdue { background: #f8d7da; color: #721c24; }
        .time-future { background: #d1ecf1; color: #0c5460; }
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
            <h1>🔔 Hatırlatmalarım</h1>
            
            <!-- Quick Actions -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <a href="hatirlatma-ekle.php" class="action-btn btn-complete">
                    ➕ Yeni Hatırlatma
                </a>
                <a href="takvim.php" class="action-btn" style="background: #3498db; color: white;">
                    📅 Takvime Dön
                </a>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <a href="?filter=bekleyen" class="stat-card <?php echo $filter == 'bekleyen' ? 'active' : ''; ?>">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-value"><?php echo $stats['bekleyen']; ?></div>
                    <div class="stat-label">Bekleyen</div>
                </a>
                <a href="?filter=gecmis" class="stat-card <?php echo $filter == 'gecmis' ? 'active' : ''; ?>">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-value"><?php echo $stats['gecmis']; ?></div>
                    <div class="stat-label">Gecikmiş</div>
                </a>
                <a href="?filter=okunmamis" class="stat-card <?php echo $filter == 'okunmamis' ? 'active' : ''; ?>">
                    <div class="stat-icon">📬</div>
                    <div class="stat-value"><?php echo $stats['okunmamis']; ?></div>
                    <div class="stat-label">Okunmamış</div>
                </a>
                <a href="?filter=tamamlanan" class="stat-card <?php echo $filter == 'tamamlanan' ? 'active' : ''; ?>">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $stats['tamamlanan']; ?></div>
                    <div class="stat-label">Tamamlanan</div>
                </a>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="date" id="date_filter" value="<?php echo $date_filter; ?>" class="filter-btn">
                <button onclick="filterByDate()" class="filter-btn">Tarihe Göre Filtrele</button>
                <a href="hatirlatmalar.php" class="filter-btn">Tüm Hatırlatmalar</a>
            </div>
            
            <!-- Reminders List -->
            <div class="reminders-container">
                <?php if(count($hatirlatmalar) > 0): ?>
                    <?php foreach($hatirlatmalar as $hatirlatma): 
                        $tarih = strtotime($hatirlatma['hatirlatma_tarihi']);
                        $bugun = strtotime(date('Y-m-d'));
                        $yarin = strtotime(date('Y-m-d', strtotime('+1 day')));
                        
                        $time_class = 'time-future';
                        $time_text = date('d.m.Y H:i', $tarih);
                        
                        if(date('Y-m-d', $tarih) == date('Y-m-d')) {
                            $time_class = 'time-today';
                            $time_text = 'Bugün ' . date('H:i', $tarih);
                        } elseif(date('Y-m-d', $tarih) == date('Y-m-d', $yarin)) {
                            $time_class = 'time-tomorrow';
                            $time_text = 'Yarın ' . date('H:i', $tarih);
                        } elseif($tarih < $bugun) {
                            $time_class = 'time-overdue';
                        }
                    ?>
                    <div class="reminder-item <?php echo $hatirlatma['okundu'] == 0 ? 'unread' : ''; ?>">
                        <div class="reminder-icon icon-<?php echo $hatirlatma['hatirlatma_tipi']; ?>">
                            <?php
                            $icons = [
                                'arama' => '📞',
                                'gorusme' => '👥',
                                'odeme' => '💰',
                                'diger' => '📌'
                            ];
                            echo $icons[$hatirlatma['hatirlatma_tipi']] ?? '📌';
                            ?>
                        </div>
                        
                        <div class="reminder-content">
                            <div class="reminder-title">
                                <?php echo htmlspecialchars($hatirlatma['baslik']); ?>
                                <span class="priority-badge priority-<?php echo $hatirlatma['oncelik']; ?>">
                                    <?php echo ucfirst($hatirlatma['oncelik']); ?>
                                </span>
                            </div>
                            
                            <div class="reminder-message">
                                <?php echo htmlspecialchars($hatirlatma['mesaj']); ?>
                            </div>
                            
                            <div class="reminder-meta">
                                <?php if($hatirlatma['musteri_adi']): ?>
                                <span>👤 <?php echo htmlspecialchars($hatirlatma['musteri_adi']); ?></span>
                                <?php endif; ?>
                                
                                <?php if($hatirlatma['etkinlik_baslik']): ?>
                                <span>📅 <?php echo htmlspecialchars($hatirlatma['etkinlik_baslik']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="reminder-time">
                            <span class="time-badge <?php echo $time_class; ?>">
                                <?php echo $time_text; ?>
                            </span>
                        </div>
                        
                        <div class="reminder-actions">
                            <?php if($hatirlatma['durum'] == 'aktif'): ?>
                                <?php if($hatirlatma['okundu'] == 0): ?>
                                <a href="?okundu=1&id=<?php echo $hatirlatma['id']; ?>" class="action-btn btn-mark-read">
                                    Okundu
                                </a>
                                <?php endif; ?>
                                <a href="?tamamla=1&id=<?php echo $hatirlatma['id']; ?>" class="action-btn btn-complete">
                                    Tamamla
                                </a>
                            <?php else: ?>
                                <span style="color: #27ae60; font-size: 12px;">✅ Tamamlandı</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔔</div>
                        <h3>Hatırlatma Bulunmuyor</h3>
                        <p>Bu kriterlere uygun hatırlatma bulunmamaktadır.</p>
                        <a href="hatirlatma-ekle.php" class="action-btn btn-complete" style="margin-top: 20px; display: inline-block;">
                            Yeni Hatırlatma Ekle
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function filterByDate() {
        const date = document.getElementById('date_filter').value;
        if(date) {
            window.location.href = '?filter=<?php echo $filter; ?>&date=' + date;
        }
    }
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>