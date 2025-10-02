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

// Tarih filtreleri
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Ayın ilk günü
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Bugün

// GENEL İSTATİSTİKLER
// Toplam müşteri sayıları
$where_user = ($current_user_role != 'admin') ? " AND ekleyen_user_id = $current_user_id" : "";

// Alıcı müşteri istatistikleri
$sql = "SELECT 
    COUNT(*) as toplam_alici,
    SUM(CASE WHEN DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as yeni_alici,
    AVG(max_butce) as ort_butce
    FROM crm_alici_musteriler 
    WHERE 1=1 $where_user";

$stmt = $db->prepare($sql);
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$alici_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Satıcı müşteri istatistikleri
$sql = "SELECT 
    COUNT(*) as toplam_satici,
    SUM(CASE WHEN DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as yeni_satici,
    SUM(arama_sayisi) as toplam_arama
    FROM crm_satici_musteriler 
    WHERE 1=1 $where_user";

$stmt = $db->prepare($sql);
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$satici_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Görüşme istatistikleri
$sql = "SELECT 
    COUNT(*) as toplam_gorusme,
    COUNT(DISTINCT musteri_id) as gorusulen_musteri
    FROM crm_gorusme_notlari 
    WHERE DATE(gorusme_tarihi) BETWEEN :start_date AND :end_date";
if($current_user_role != 'admin') {
    $sql .= " AND gorusen_user_id = :user_id";
}

$stmt = $db->prepare($sql);
$params = [':start_date' => $start_date, ':end_date' => $end_date];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$gorusme_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// DANIŞMAN PERFORMANSI (Admin için)
$danisman_performans = [];
if($current_user_role == 'admin') {
    $sql = "SELECT 
        u.id, u.full_name, u.username,
        (SELECT COUNT(*) FROM crm_alici_musteriler WHERE ekleyen_user_id = u.id) as alici_sayisi,
        (SELECT COUNT(*) FROM crm_satici_musteriler WHERE ekleyen_user_id = u.id) as satici_sayisi,
        (SELECT COUNT(*) FROM crm_gorusme_notlari WHERE gorusen_user_id = u.id 
         AND DATE(gorusme_tarihi) BETWEEN :start_date AND :end_date) as gorusme_sayisi
        FROM users u 
        WHERE u.status = 'active'
        ORDER BY (alici_sayisi + satici_sayisi) DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $danisman_performans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// BÖLGESEL ANALİZ
$sql = "SELECT 
    aranan_il as bolge,
    COUNT(*) as musteri_sayisi,
    AVG(min_butce) as ort_min_butce,
    AVG(max_butce) as ort_max_butce
    FROM crm_alici_musteriler 
    WHERE aranan_il IS NOT NULL AND aranan_il != '' $where_user
    GROUP BY aranan_il 
    ORDER BY musteri_sayisi DESC 
    LIMIT 10";

$stmt = $db->query($sql);
$bolgesel_analiz = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AYLIK TREND (Son 6 ay)
$aylik_trend = [];
for($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-t', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    
    $sql = "SELECT 
        (SELECT COUNT(*) FROM crm_alici_musteriler 
         WHERE DATE(ekleme_tarihi) BETWEEN :start AND :end $where_user) as alici,
        (SELECT COUNT(*) FROM crm_satici_musteriler 
         WHERE DATE(ekleme_tarihi) BETWEEN :start AND :end $where_user) as satici";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':start' => $month_start, ':end' => $month_end]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $aylik_trend[] = [
        'ay' => $month_name,
        'alici' => $result['alici'],
        'satici' => $result['satici']
    ];
}

// EN AKTİF MÜŞTERİLER
$sql = "SELECT 
    CONCAT(ad, ' ', soyad) as musteri,
    'Satıcı' as tip,
    arama_sayisi as aktivite
    FROM crm_satici_musteriler 
    WHERE 1=1 $where_user
    ORDER BY arama_sayisi DESC 
    LIMIT 5";

$stmt = $db->query($sql);
$aktif_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Raporları - Plazanet Emlak</title>
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
        
        /* Filtreler */
        .filter-bar {
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
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        /* İstatistik Kartları */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .stat-card.green { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); }
        .stat-card.blue { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }
        .stat-card.orange { background: linear-gradient(135deg, #fff3e0, #ffe0b2); }
        .stat-card.purple { background: linear-gradient(135deg, #f3e5f5, #e1bee7); }
        
        /* Grafik ve Tablolar */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .report-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        /* Tablo Stilleri */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 500;
        }
        
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Grafik */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 250px;
            padding: 20px 0;
        }
        
        .bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        
        .bars {
            display: flex;
            align-items: flex-end;
            gap: 5px;
            height: 200px;
        }
        
        .bar {
            width: 30px;
            min-height: 10px;
            border-radius: 5px 5px 0 0;
            position: relative;
        }
        
        .bar.alici { background: #3498db; }
        .bar.satici { background: #e67e22; }
        
        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: bold;
        }
        
        .bar-label {
            margin-top: 10px;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .legend-color {
            width: 20px;
            height: 10px;
            border-radius: 2px;
        }
        
        /* Butonlar */
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
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-excel { background: #27ae60; color: white; }
        .btn-pdf { background: #e74c3c; color: white; }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
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
            <h1>📊 CRM Raporları ve Analizler</h1>
            
            <!-- Filtreler -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                    <a href="rapor-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="btn btn-excel">Excel İndir</a>
                </form>
            </div>
            
            <!-- Hızlı Butonlar -->
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">CRM Ana Sayfa</a>
                <a href="rapor-detay.php" class="btn btn-success">Detaylı Rapor</a>
            </div>
            
            <!-- İstatistik Kartları -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="icon">👥</div>
                    <div class="value"><?php echo $alici_stats['toplam_alici'] ?? 0; ?></div>
                    <div class="label">Toplam Alıcı</div>
                </div>
                <div class="stat-card blue">
                    <div class="icon">🏠</div>
                    <div class="value"><?php echo $satici_stats['toplam_satici'] ?? 0; ?></div>
                    <div class="label">Toplam Satıcı</div>
                </div>
                <div class="stat-card orange">
                    <div class="icon">📞</div>
                    <div class="value"><?php echo $satici_stats['toplam_arama'] ?? 0; ?></div>
                    <div class="label">Toplam Arama</div>
                </div>
                <div class="stat-card purple">
                    <div class="icon">💰</div>
                    <div class="value"><?php echo number_format($alici_stats['ort_butce'] ?? 0, 0, ',', '.'); ?>₺</div>
                    <div class="label">Ortalama Bütçe</div>
                </div>
            </div>
            
            <!-- Grafik ve Tablolar -->
            <div class="report-grid">
                <!-- Aylık Trend Grafiği -->
                <div class="report-card">
                    <h3>📈 Aylık Müşteri Trendi</h3>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #3498db;"></div>
                            <span>Alıcı</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #e67e22;"></div>
                            <span>Satıcı</span>
                        </div>
                    </div>
                    <div class="bar-chart">
                        <?php 
                        $max_value = 1;
                        foreach($aylik_trend as $trend) {
                            $max_value = max($max_value, $trend['alici'], $trend['satici']);
                        }
                        foreach($aylik_trend as $trend): 
                            $alici_height = ($trend['alici'] / $max_value) * 180;
                            $satici_height = ($trend['satici'] / $max_value) * 180;
                        ?>
                        <div class="bar-group">
                            <div class="bars">
                                <div class="bar alici" style="height: <?php echo $alici_height; ?>px;">
                                    <span class="bar-value"><?php echo $trend['alici']; ?></span>
                                </div>
                                <div class="bar satici" style="height: <?php echo $satici_height; ?>px;">
                                    <span class="bar-value"><?php echo $trend['satici']; ?></span>
                                </div>
                            </div>
                            <div class="bar-label"><?php echo $trend['ay']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Bölgesel Analiz -->
                <div class="report-card">
                    <h3>📍 Bölgesel Talep Analizi</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bölge</th>
                                <th>Müşteri</th>
                                <th>Min Bütçe</th>
                                <th>Max Bütçe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bolgesel_analiz as $bolge): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bolge['bolge']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $bolge['musteri_sayisi']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($bolge['ort_min_butce'], 0, ',', '.'); ?>₺</td>
                                <td><?php echo number_format($bolge['ort_max_butce'], 0, ',', '.'); ?>₺</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($current_user_role == 'admin'): ?>
            <!-- Danışman Performansı -->
            <div class="report-card">
                <h3>👨‍💼 Danışman Performans Raporu</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Danışman</th>
                            <th>Alıcı</th>
                            <th>Satıcı</th>
                            <th>Görüşme</th>
                            <th>Toplam</th>
                            <th>Performans</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($danisman_performans as $danisman): 
                            $toplam = $danisman['alici_sayisi'] + $danisman['satici_sayisi'];
                            $performans = $toplam > 20 ? 'success' : ($toplam > 10 ? 'warning' : 'info');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($danisman['full_name'] ?: $danisman['username']); ?></strong>
                            </td>
                            <td><?php echo $danisman['alici_sayisi']; ?></td>
                            <td><?php echo $danisman['satici_sayisi']; ?></td>
                            <td><?php echo $danisman['gorusme_sayisi']; ?></td>
                            <td>
                                <strong><?php echo $toplam; ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $performans; ?>">
                                    <?php 
                                    echo $toplam > 20 ? 'Mükemmel' : 
                                         ($toplam > 10 ? 'İyi' : 'Geliştirilmeli');
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Dönem Özeti -->
            <div class="report-card">
                <h3>📅 Dönem Özeti (<?php echo date('d.m.Y', strtotime($start_date)); ?> - <?php echo date('d.m.Y', strtotime($end_date)); ?>)</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="value"><?php echo $alici_stats['yeni_alici'] ?? 0; ?></div>
                        <div class="label">Yeni Alıcı</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $satici_stats['yeni_satici'] ?? 0; ?></div>
                        <div class="label">Yeni Satıcı</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $gorusme_stats['toplam_gorusme'] ?? 0; ?></div>
                        <div class="label">Görüşme</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $gorusme_stats['gorusulen_musteri'] ?? 0; ?></div>
                        <div class="label">Görüşülen Müşteri</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

// Tarih filtreleri
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Ayın ilk günü
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Bugün

// GENEL İSTATİSTİKLER
// Toplam müşteri sayıları
$where_user = ($current_user_role != 'admin') ? " AND ekleyen_user_id = $current_user_id" : "";

// Alıcı müşteri istatistikleri
$sql = "SELECT 
    COUNT(*) as toplam_alici,
    SUM(CASE WHEN DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as yeni_alici,
    AVG(max_butce) as ort_butce
    FROM crm_alici_musteriler 
    WHERE 1=1 $where_user";

$stmt = $db->prepare($sql);
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$alici_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Satıcı müşteri istatistikleri
$sql = "SELECT 
    COUNT(*) as toplam_satici,
    SUM(CASE WHEN DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as yeni_satici,
    SUM(arama_sayisi) as toplam_arama
    FROM crm_satici_musteriler 
    WHERE 1=1 $where_user";

$stmt = $db->prepare($sql);
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$satici_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Görüşme istatistikleri
$sql = "SELECT 
    COUNT(*) as toplam_gorusme,
    COUNT(DISTINCT musteri_id) as gorusulen_musteri
    FROM crm_gorusme_notlari 
    WHERE DATE(gorusme_tarihi) BETWEEN :start_date AND :end_date";
if($current_user_role != 'admin') {
    $sql .= " AND gorusen_user_id = :user_id";
}

$stmt = $db->prepare($sql);
$params = [':start_date' => $start_date, ':end_date' => $end_date];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$gorusme_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// DANIŞMAN PERFORMANSI (Admin için)
$danisman_performans = [];
if($current_user_role == 'admin') {
    $sql = "SELECT 
        u.id, u.full_name, u.username,
        (SELECT COUNT(*) FROM crm_alici_musteriler WHERE ekleyen_user_id = u.id) as alici_sayisi,
        (SELECT COUNT(*) FROM crm_satici_musteriler WHERE ekleyen_user_id = u.id) as satici_sayisi,
        (SELECT COUNT(*) FROM crm_gorusme_notlari WHERE gorusen_user_id = u.id 
         AND DATE(gorusme_tarihi) BETWEEN :start_date AND :end_date) as gorusme_sayisi
        FROM users u 
        WHERE u.status = 'active'
        ORDER BY (alici_sayisi + satici_sayisi) DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $danisman_performans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// BÖLGESEL ANALİZ
$sql = "SELECT 
    aranan_il as bolge,
    COUNT(*) as musteri_sayisi,
    AVG(min_butce) as ort_min_butce,
    AVG(max_butce) as ort_max_butce
    FROM crm_alici_musteriler 
    WHERE aranan_il IS NOT NULL AND aranan_il != '' $where_user
    GROUP BY aranan_il 
    ORDER BY musteri_sayisi DESC 
    LIMIT 10";

$stmt = $db->query($sql);
$bolgesel_analiz = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AYLIK TREND (Son 6 ay)
$aylik_trend = [];
for($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-t', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    
    $sql = "SELECT 
        (SELECT COUNT(*) FROM crm_alici_musteriler 
         WHERE DATE(ekleme_tarihi) BETWEEN :start AND :end $where_user) as alici,
        (SELECT COUNT(*) FROM crm_satici_musteriler 
         WHERE DATE(ekleme_tarihi) BETWEEN :start AND :end $where_user) as satici";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':start' => $month_start, ':end' => $month_end]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $aylik_trend[] = [
        'ay' => $month_name,
        'alici' => $result['alici'],
        'satici' => $result['satici']
    ];
}

// EN AKTİF MÜŞTERİLER
$sql = "SELECT 
    CONCAT(ad, ' ', soyad) as musteri,
    'Satıcı' as tip,
    arama_sayisi as aktivite
    FROM crm_satici_musteriler 
    WHERE 1=1 $where_user
    ORDER BY arama_sayisi DESC 
    LIMIT 5";

$stmt = $db->query($sql);
$aktif_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Raporları - Plazanet Emlak</title>
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
        
        /* Filtreler */
        .filter-bar {
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
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        /* İstatistik Kartları */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .stat-card.green { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); }
        .stat-card.blue { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }
        .stat-card.orange { background: linear-gradient(135deg, #fff3e0, #ffe0b2); }
        .stat-card.purple { background: linear-gradient(135deg, #f3e5f5, #e1bee7); }
        
        /* Grafik ve Tablolar */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .report-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .report-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        /* Tablo Stilleri */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 500;
        }
        
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Grafik */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 250px;
            padding: 20px 0;
        }
        
        .bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        
        .bars {
            display: flex;
            align-items: flex-end;
            gap: 5px;
            height: 200px;
        }
        
        .bar {
            width: 30px;
            min-height: 10px;
            border-radius: 5px 5px 0 0;
            position: relative;
        }
        
        .bar.alici { background: #3498db; }
        .bar.satici { background: #e67e22; }
        
        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: bold;
        }
        
        .bar-label {
            margin-top: 10px;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .legend-color {
            width: 20px;
            height: 10px;
            border-radius: 2px;
        }
        
        /* Butonlar */
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
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-excel { background: #27ae60; color: white; }
        .btn-pdf { background: #e74c3c; color: white; }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
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
            <h1>📊 CRM Raporları ve Analizler</h1>
            
            <!-- Filtreler -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Başlangıç Tarihi</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Bitiş Tarihi</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                    <a href="rapor-excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="btn btn-excel">Excel İndir</a>
                </form>
            </div>
            
            <!-- Hızlı Butonlar -->
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">CRM Ana Sayfa</a>
                <a href="rapor-detay.php" class="btn btn-success">Detaylı Rapor</a>
            </div>
            
            <!-- İstatistik Kartları -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="icon">👥</div>
                    <div class="value"><?php echo $alici_stats['toplam_alici'] ?? 0; ?></div>
                    <div class="label">Toplam Alıcı</div>
                </div>
                <div class="stat-card blue">
                    <div class="icon">🏠</div>
                    <div class="value"><?php echo $satici_stats['toplam_satici'] ?? 0; ?></div>
                    <div class="label">Toplam Satıcı</div>
                </div>
                <div class="stat-card orange">
                    <div class="icon">📞</div>
                    <div class="value"><?php echo $satici_stats['toplam_arama'] ?? 0; ?></div>
                    <div class="label">Toplam Arama</div>
                </div>
                <div class="stat-card purple">
                    <div class="icon">💰</div>
                    <div class="value"><?php echo number_format($alici_stats['ort_butce'] ?? 0, 0, ',', '.'); ?>₺</div>
                    <div class="label">Ortalama Bütçe</div>
                </div>
            </div>
            
            <!-- Grafik ve Tablolar -->
            <div class="report-grid">
                <!-- Aylık Trend Grafiği -->
                <div class="report-card">
                    <h3>📈 Aylık Müşteri Trendi</h3>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #3498db;"></div>
                            <span>Alıcı</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #e67e22;"></div>
                            <span>Satıcı</span>
                        </div>
                    </div>
                    <div class="bar-chart">
                        <?php 
                        $max_value = 1;
                        foreach($aylik_trend as $trend) {
                            $max_value = max($max_value, $trend['alici'], $trend['satici']);
                        }
                        foreach($aylik_trend as $trend): 
                            $alici_height = ($trend['alici'] / $max_value) * 180;
                            $satici_height = ($trend['satici'] / $max_value) * 180;
                        ?>
                        <div class="bar-group">
                            <div class="bars">
                                <div class="bar alici" style="height: <?php echo $alici_height; ?>px;">
                                    <span class="bar-value"><?php echo $trend['alici']; ?></span>
                                </div>
                                <div class="bar satici" style="height: <?php echo $satici_height; ?>px;">
                                    <span class="bar-value"><?php echo $trend['satici']; ?></span>
                                </div>
                            </div>
                            <div class="bar-label"><?php echo $trend['ay']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Bölgesel Analiz -->
                <div class="report-card">
                    <h3>📍 Bölgesel Talep Analizi</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bölge</th>
                                <th>Müşteri</th>
                                <th>Min Bütçe</th>
                                <th>Max Bütçe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bolgesel_analiz as $bolge): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bolge['bolge']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $bolge['musteri_sayisi']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($bolge['ort_min_butce'], 0, ',', '.'); ?>₺</td>
                                <td><?php echo number_format($bolge['ort_max_butce'], 0, ',', '.'); ?>₺</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($current_user_role == 'admin'): ?>
            <!-- Danışman Performansı -->
            <div class="report-card">
                <h3>👨‍💼 Danışman Performans Raporu</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Danışman</th>
                            <th>Alıcı</th>
                            <th>Satıcı</th>
                            <th>Görüşme</th>
                            <th>Toplam</th>
                            <th>Performans</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($danisman_performans as $danisman): 
                            $toplam = $danisman['alici_sayisi'] + $danisman['satici_sayisi'];
                            $performans = $toplam > 20 ? 'success' : ($toplam > 10 ? 'warning' : 'info');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($danisman['full_name'] ?: $danisman['username']); ?></strong>
                            </td>
                            <td><?php echo $danisman['alici_sayisi']; ?></td>
                            <td><?php echo $danisman['satici_sayisi']; ?></td>
                            <td><?php echo $danisman['gorusme_sayisi']; ?></td>
                            <td>
                                <strong><?php echo $toplam; ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $performans; ?>">
                                    <?php 
                                    echo $toplam > 20 ? 'Mükemmel' : 
                                         ($toplam > 10 ? 'İyi' : 'Geliştirilmeli');
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Dönem Özeti -->
            <div class="report-card">
                <h3>📅 Dönem Özeti (<?php echo date('d.m.Y', strtotime($start_date)); ?> - <?php echo date('d.m.Y', strtotime($end_date)); ?>)</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="value"><?php echo $alici_stats['yeni_alici'] ?? 0; ?></div>
                        <div class="label">Yeni Alıcı</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $satici_stats['yeni_satici'] ?? 0; ?></div>
                        <div class="label">Yeni Satıcı</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $gorusme_stats['toplam_gorusme'] ?? 0; ?></div>
                        <div class="label">Görüşme</div>
                    </div>
                    <div class="stat-card">
                        <div class="value"><?php echo $gorusme_stats['gorusulen_musteri'] ?? 0; ?></div>
                        <div class="label">Görüşülen Müşteri</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>