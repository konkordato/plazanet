<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';

// Tarih filtreleri - varsayılan son 30 gün
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

if(isset($_GET['period'])) {
    switch($_GET['period']) {
        case 'week':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'month':
            $start_date = date('Y-m-01');
            break;
        case 'quarter':
            $start_date = date('Y-m-d', strtotime('-3 months'));
            break;
        case 'year':
            $start_date = date('Y-01-01');
            break;
    }
}

// Özel tarih aralığı
if(isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

$where_user = ($current_user_role != 'admin') ? " AND ekleyen_user_id = $current_user_id" : "";

// VERİMLİLİK ANALİZİ
// Müşteri dönüşüm oranı (görüşme yapılan müşteriler)
$sql = "SELECT 
    (SELECT COUNT(DISTINCT musteri_id) FROM crm_gorusme_notlari 
     WHERE musteri_tipi = 'alici' AND DATE(gorusme_tarihi) BETWEEN :start AND :end) as gorusulen_alici,
    (SELECT COUNT(*) FROM crm_alici_musteriler WHERE 1=1 $where_user) as toplam_alici,
    (SELECT COUNT(DISTINCT musteri_id) FROM crm_gorusme_notlari 
     WHERE musteri_tipi = 'satici' AND DATE(gorusme_tarihi) BETWEEN :start AND :end) as gorusulen_satici,
    (SELECT COUNT(*) FROM crm_satici_musteriler WHERE 1=1 $where_user) as toplam_satici";

$stmt = $db->prepare($sql);
$stmt->execute([':start' => $start_date, ':end' => $end_date]);
$verimlilik = $stmt->fetch(PDO::FETCH_ASSOC);

$alici_donusum = $verimlilik['toplam_alici'] > 0 ? 
    round(($verimlilik['gorusulen_alici'] / $verimlilik['toplam_alici']) * 100, 1) : 0;
$satici_donusum = $verimlilik['toplam_satici'] > 0 ? 
    round(($verimlilik['gorusulen_satici'] / $verimlilik['toplam_satici']) * 100, 1) : 0;

// HAFTALIK AKTİVİTE
$haftalik_aktivite = [];
for($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($day));
    
    $sql = "SELECT 
        (SELECT COUNT(*) FROM crm_alici_musteriler WHERE DATE(ekleme_tarihi) = :day $where_user) as yeni_alici,
        (SELECT COUNT(*) FROM crm_satici_musteriler WHERE DATE(ekleme_tarihi) = :day $where_user) as yeni_satici,
        (SELECT COUNT(*) FROM crm_gorusme_notlari WHERE DATE(gorusme_tarihi) = :day) as gorusme";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':day' => $day]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $haftalik_aktivite[] = [
        'gun' => $day_name,
        'tarih' => date('d.m', strtotime($day)),
        'yeni_alici' => $result['yeni_alici'],
        'yeni_satici' => $result['yeni_satici'],
        'gorusme' => $result['gorusme']
    ];
}

// BÜTÇE DAĞILIMI
$sql = "SELECT 
    CASE 
        WHEN max_butce < 500000 THEN '0-500K'
        WHEN max_butce < 1000000 THEN '500K-1M'
        WHEN max_butce < 2000000 THEN '1M-2M'
        WHEN max_butce < 5000000 THEN '2M-5M'
        ELSE '5M+'
    END as butce_araligi,
    COUNT(*) as sayi
    FROM crm_alici_musteriler 
    WHERE 1=1 $where_user
    GROUP BY butce_araligi
    ORDER BY max_butce";

$stmt = $db->query($sql);
$butce_dagilimi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TAŞINMAZ TİPİ DAĞILIMI
$sql = "SELECT 
    aranan_tasinmaz as tip, 
    COUNT(*) as sayi,
    ROUND(AVG(max_butce)) as ort_butce
    FROM crm_alici_musteriler 
    WHERE 1=1 $where_user
    GROUP BY aranan_tasinmaz";

$stmt = $db->query($sql);
$tasinmaz_dagilimi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EN ÇOK ARANAN BÖLGELER
$sql = "SELECT 
    CONCAT(aranan_il, '/', IFNULL(aranan_ilce, '')) as bolge,
    COUNT(*) as talep_sayisi,
    ROUND(AVG(min_butce)) as min_ort,
    ROUND(AVG(max_butce)) as max_ort
    FROM crm_alici_musteriler 
    WHERE aranan_il IS NOT NULL AND aranan_il != '' $where_user
    GROUP BY aranan_il, aranan_ilce
    ORDER BY talep_sayisi DESC
    LIMIT 15";

$stmt = $db->query($sql);
$populer_bolgeler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DANIŞMAN GÜNLÜK PERFORMANSI (Son 7 gün)
$danisman_gunluk = [];
if($current_user_role == 'admin') {
    $sql = "SELECT 
        u.full_name,
        u.username,
        (SELECT COUNT(*) FROM crm_alici_musteriler 
         WHERE ekleyen_user_id = u.id AND DATE(ekleme_tarihi) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as haftalik_alici,
        (SELECT COUNT(*) FROM crm_satici_musteriler 
         WHERE ekleyen_user_id = u.id AND DATE(ekleme_tarihi) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as haftalik_satici,
        (SELECT COUNT(*) FROM crm_gorusme_notlari 
         WHERE gorusen_user_id = u.id AND DATE(gorusme_tarihi) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as haftalik_gorusme,
        (SELECT COUNT(*) FROM crm_gorusme_notlari 
         WHERE gorusen_user_id = u.id AND DATE(gorusme_tarihi) = CURDATE()) as bugunki_gorusme
        FROM users u 
        WHERE u.status = 'active'
        ORDER BY (haftalik_alici + haftalik_satici) DESC";
    
    $stmt = $db->query($sql);
    $danisman_gunluk = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// SON AKTİVİTELER
$sql = "SELECT * FROM (
    SELECT 'alici' as tip, CONCAT(ad, ' ', soyad) as isim, ekleme_tarihi as tarih, 
           CONCAT('Yeni alıcı eklendi - Bütçe: ', FORMAT(max_butce, 0), ' TL') as aciklama
    FROM crm_alici_musteriler WHERE 1=1 $where_user
    UNION ALL
    SELECT 'satici' as tip, CONCAT(ad, ' ', soyad) as isim, ekleme_tarihi as tarih,
           CONCAT('Yeni satıcı eklendi - ', IFNULL(tasinmaz_cinsi, 'Belirtilmemiş')) as aciklama
    FROM crm_satici_musteriler WHERE 1=1 $where_user
    UNION ALL
    SELECT 'gorusme' as tip, gorusen_user_adi as isim, gorusme_tarihi as tarih,
           SUBSTRING(gorusme_notu, 1, 100) as aciklama
    FROM crm_gorusme_notlari WHERE 1=1
) as aktiviteler
ORDER BY tarih DESC
LIMIT 20";

$stmt = $db->query($sql);
$son_aktiviteler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaylı CRM Raporu - Plazanet Emlak</title>
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
        
        /* Header */
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .report-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .report-subtitle {
            opacity: 0.9;
        }
        
        /* Period Selector */
        .period-selector {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .period-btn:hover,
        .period-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        /* Grid Layout */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        /* Cards */
        .report-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .card-badge {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        /* Progress Bars */
        .progress-item {
            margin-bottom: 20px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .progress-bar {
            height: 25px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 500;
            transition: width 1s ease;
        }
        
        /* Activity Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ecf0f1;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-dot {
            position: absolute;
            left: -25px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 3px solid #3498db;
        }
        
        .timeline-dot.alici { border-color: #27ae60; }
        .timeline-dot.satici { border-color: #e67e22; }
        .timeline-dot.gorusme { border-color: #9b59b6; }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        
        .timeline-time {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        /* Data Table */
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
        
        /* Mini Charts */
        .mini-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 100px;
            margin-top: 15px;
        }
        
        .mini-bar {
            flex: 1;
            background: #3498db;
            border-radius: 3px 3px 0 0;
            margin: 0 2px;
            position: relative;
            min-height: 5px;
        }
        
        .mini-bar:hover {
            opacity: 0.8;
        }
        
        .mini-bar-label {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: #7f8c8d;
        }
        
        .mini-bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        /* Performance Metrics */
        .metric-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .metric-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .metric-change {
            font-size: 14px;
            margin-top: 5px;
        }
        
        .metric-change.up { color: #27ae60; }
        .metric-change.down { color: #e74c3c; }
        
        /* Badge Colors */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        /* Pie Chart */
        .pie-chart {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        
        .pie-chart-container {
            position: relative;
            width: 200px;
            height: 200px;
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .period-selector, .btn { display: none; }
            .admin-content { margin-left: 0; }
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
            <!-- Report Header -->
            <div class="report-header">
                <h1 class="report-title">📊 Detaylı CRM Performans Raporu</h1>
                <p class="report-subtitle">
                    <?php echo date('d.m.Y', strtotime($start_date)); ?> - <?php echo date('d.m.Y', strtotime($end_date)); ?> 
                    dönemi analiz raporu
                </p>
            </div>
            
            <!-- Period Selector -->
            <div class="period-selector">
                <a href="?period=week" class="period-btn <?php echo (isset($_GET['period']) && $_GET['period'] == 'week') ? 'active' : ''; ?>">
                    Son 7 Gün
                </a>
                <a href="?period=month" class="period-btn <?php echo (isset($_GET['period']) && $_GET['period'] == 'month') ? 'active' : ''; ?>">
                    Bu Ay
                </a>
                <a href="?period=quarter" class="period-btn <?php echo (isset($_GET['period']) && $_GET['period'] == 'quarter') ? 'active' : ''; ?>">
                    Son 3 Ay
                </a>
                <a href="?period=year" class="period-btn <?php echo (isset($_GET['period']) && $_GET['period'] == 'year') ? 'active' : ''; ?>">
                    Bu Yıl
                </a>
                <a href="raporlar.php" class="period-btn">
                    ← Geri
                </a>
                <button onclick="window.print()" class="period-btn">
                    🖨️ Yazdır
                </button>
            </div>
            
            <!-- Verimlilik Analizi -->
            <div class="report-grid">
                <div class="report-card">
                    <div class="card-header">
                        <h3 class="card-title">📈 Müşteri Dönüşüm Oranları</h3>
                        <span class="card-badge">Verimlilik</span>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Alıcı Müşteri Görüşme Oranı</span>
                            <span><?php echo $alici_donusum; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $alici_donusum; ?>%;">
                                <?php echo $verimlilik['gorusulen_alici']; ?> / <?php echo $verimlilik['toplam_alici']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Satıcı Müşteri Görüşme Oranı</span>
                            <span><?php echo $satici_donusum; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $satici_donusum; ?>%; background: linear-gradient(90deg, #e67e22, #d35400);">
                                <?php echo $verimlilik['gorusulen_satici']; ?> / <?php echo $verimlilik['toplam_satici']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Haftalık Aktivite -->
                <div class="report-card">
                    <div class="card-header">
                        <h3 class="card-title">📅 Son 7 Günlük Aktivite</h3>
                        <span class="card-badge">Trend</span>
                    </div>
                    
                    <div class="mini-chart">
                        <?php 
                        $max_aktivite = 1;
                        foreach($haftalik_aktivite as $gun) {
                            $toplam = $gun['yeni_alici'] + $gun['yeni_satici'] + $gun['gorusme'];
                            $max_aktivite = max($max_aktivite, $toplam);
                        }
                        
                        foreach($haftalik_aktivite as $gun): 
                            $toplam = $gun['yeni_alici'] + $gun['yeni_satici'] + $gun['gorusme'];
                            $height = ($toplam / $max_aktivite) * 80;
                        ?>
                        <div class="mini-bar" style="height: <?php echo $height; ?>px;" 
                             title="<?php echo $gun['tarih']; ?>: <?php echo $toplam; ?> aktivite">
                            <span class="mini-bar-value"><?php echo $toplam ?: ''; ?></span>
                            <span class="mini-bar-label"><?php echo $gun['gun']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Bütçe ve Taşınmaz Analizi -->
            <div class="report-grid">
                <div class="report-card">
                    <div class="card-header">
                        <h3 class="card-title">💰 Bütçe Dağılımı</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bütçe Aralığı</th>
                                <th>Müşteri Sayısı</th>
                                <th>Oran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $toplam_butce_musteri = array_sum(array_column($butce_dagilimi, 'sayi'));
                            foreach($butce_dagilimi as $butce): 
                                $oran = $toplam_butce_musteri > 0 ? round(($butce['sayi'] / $toplam_butce_musteri) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo $butce['butce_araligi']; ?></td>
                                <td><?php echo $butce['sayi']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div style="width: 100px; height: 20px; background: #ecf0f1; border-radius: 10px; margin-right: 10px;">
                                            <div style="width: <?php echo $oran; ?>%; height: 100%; background: #3498db; border-radius: 10px;"></div>
                                        </div>
                                        <?php echo $oran; ?>%
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-card">
                    <div class="card-header">
                        <h3 class="card-title">🏠 Taşınmaz Tipi Dağılımı</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tip</th>
                                <th>Talep</th>
                                <th>Ort. Bütçe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tasinmaz_dagilimi as $tip): ?>
                            <tr>
                                <td><?php echo $tip['tip']; ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $tip['sayi']; ?></span>
                                </td>
                                <td><?php echo number_format($tip['ort_butce'], 0, ',', '.'); ?>₺</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Popüler Bölgeler -->
            <div class="report-card full-width">
                <div class="card-header">
                    <h3 class="card-title">📍 En Çok Talep Gören Bölgeler</h3>
                    <span class="card-badge">Top 15</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>Bölge</th>
                            <th>Talep Sayısı</th>
                            <th>Min Bütçe Ort.</th>
                            <th>Max Bütçe Ort.</th>
                            <th>Talep Yoğunluğu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sira = 1;
                        $max_talep = $populer_bolgeler[0]['talep_sayisi'] ?? 1;
                        foreach($populer_bolgeler as $bolge): 
                            $yogunluk = round(($bolge['talep_sayisi'] / $max_talep) * 100);
                        ?>
                        <tr>
                            <td><?php echo $sira++; ?></td>
                            <td><strong><?php echo $bolge['bolge']; ?></strong></td>
                            <td>
                                <span class="badge badge-success"><?php echo $bolge['talep_sayisi']; ?></span>
                            </td>
                            <td><?php echo number_format($bolge['min_ort'], 0, ',', '.'); ?>₺</td>
                            <td><?php echo number_format($bolge['max_ort'], 0, ',', '.'); ?>₺</td>
                            <td>
                                <div style="width: 150px; height: 20px; background: #ecf0f1; border-radius: 10px;">
                                    <div style="width: <?php echo $yogunluk; ?>%; height: 100%; background: linear-gradient(90deg, #27ae60, #2ecc71); border-radius: 10px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($current_user_role == 'admin' && count($danisman_gunluk) > 0): ?>
            <!-- Danışman Performansı -->
            <div class="report-card full-width">
                <div class="card-header">
                    <h3 class="card-title">👥 Danışman Haftalık Performans Detayı</h3>
                    <span class="card-badge">Son 7 Gün</span>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Danışman</th>
                            <th>Alıcı</th>
                            <th>Satıcı</th>
                            <th>Görüşme</th>
                            <th>Bugün</th>
                            <th>Toplam Puan</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($danisman_gunluk as $danisman): 
                            $puan = ($danisman['haftalik_alici'] * 3) + ($danisman['haftalik_satici'] * 2) + $danisman['haftalik_gorusme'];
                            $durum = $puan > 30 ? 'success' : ($puan > 15 ? 'warning' : 'danger');
                            $durum_text = $puan > 30 ? 'Mükemmel' : ($puan > 15 ? 'Normal' : 'Düşük');
                        ?>
                        <tr>
                            <td><strong><?php echo $danisman['full_name'] ?: $danisman['username']; ?></strong></td>
                            <td><?php echo $danisman['haftalik_alici']; ?></td>
                            <td><?php echo $danisman['haftalik_satici']; ?></td>
                            <td><?php echo $danisman['haftalik_gorusme']; ?></td>
                            <td>
                                <?php if($danisman['bugunki_gorusme'] > 0): ?>
                                    <span class="badge badge-success"><?php echo $danisman['bugunki_gorusme']; ?> görüşme</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">-</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $puan; ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $durum; ?>"><?php echo $durum_text; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Son Aktiviteler -->
            <div class="report-card full-width">
                <div class="card-header">
                    <h3 class="card-title">🕐 Son Aktiviteler</h3>
                    <span class="card-badge">Son 20</span>
                </div>
                
                <div class="timeline">
                    <?php foreach($son_aktiviteler as $aktivite): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $aktivite['tip']; ?>"></div>
                        <div class="timeline-content">
                            <div class="timeline-time">
                                📅 <?php echo date('d.m.Y H:i', strtotime($aktivite['tarih'])); ?>
                            </div>
                            <strong><?php echo htmlspecialchars($aktivite['isim']); ?></strong> - 
                            <?php echo htmlspecialchars($aktivite['aciklama']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>