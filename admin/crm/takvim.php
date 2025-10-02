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

// Ay ve yıl parametreleri
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Ayın ilk ve son günleri
$first_day = mktime(0, 0, 0, $month, 1, $year);
$last_day = mktime(0, 0, 0, $month + 1, 0, $year);
$days_in_month = date('t', $first_day);
$first_weekday = date('N', $first_day); // 1 = Pazartesi, 7 = Pazar

// Önceki ve sonraki ay
$prev_month = $month - 1;
$prev_year = $year;
if($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Ay adları
$months = array(
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
);

// Bu aydaki etkinlikleri çek
$start_date = "$year-$month-01 00:00:00";
$end_date = date('Y-m-d 23:59:59', $last_day);

$sql = "SELECT e.*, 
        CASE 
            WHEN e.musteri_tipi = 'alici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_alici_musteriler WHERE id = e.musteri_id)
            WHEN e.musteri_tipi = 'satici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_satici_musteriler WHERE id = e.musteri_id)
            ELSE NULL
        END as musteri_adi
        FROM crm_takvim_etkinlikler e
        WHERE (e.baslangic_tarih BETWEEN :start_date AND :end_date
        OR e.bitis_tarih BETWEEN :start_date AND :end_date)";

if($current_user_role != 'admin') {
    $sql .= " AND (e.olusturan_user_id = :user_id OR e.atanan_user_id = :user_id)";
}

$sql .= " ORDER BY e.baslangic_tarih";

$stmt = $db->prepare($sql);
$params = [':start_date' => $start_date, ':end_date' => $end_date];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$etkinlikler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Etkinlikleri günlere göre grupla
$events_by_day = [];
foreach($etkinlikler as $etkinlik) {
    $day = date('j', strtotime($etkinlik['baslangic_tarih']));
    if(!isset($events_by_day[$day])) {
        $events_by_day[$day] = [];
    }
    $events_by_day[$day][] = $etkinlik;
}

// Bugünkü hatırlatmaları çek
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as count FROM crm_hatirlatmalar 
        WHERE user_id = :user_id 
        AND DATE(hatirlatma_tarihi) = :today 
        AND durum = 'aktif' 
        AND okundu = 0";
$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $current_user_id, ':today' => $today]);
$bekleyen_hatirlatma = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Bu haftaki etkinlik sayısı
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$sql = "SELECT COUNT(*) as count FROM crm_takvim_etkinlikler 
        WHERE DATE(baslangic_tarih) BETWEEN :start AND :end";
if($current_user_role != 'admin') {
    $sql .= " AND (olusturan_user_id = :user_id OR atanan_user_id = :user_id)";
}
$stmt = $db->prepare($sql);
$params = [':start' => $week_start, ':end' => $week_end];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$haftalik_etkinlik = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Takvim ve Planlama - CRM</title>
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
        
        /* Header Stats */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Calendar Container */
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        /* Calendar Header */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .calendar-title {
            font-size: 24px;
            color: #2c3e50;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .nav-btn {
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .nav-btn:hover {
            background: #2980b9;
        }
        
        .today-btn {
            background: #27ae60;
        }
        
        .today-btn:hover {
            background: #229954;
        }
        
        /* Calendar Grid */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ecf0f1;
            border: 1px solid #ecf0f1;
        }
        
        .calendar-weekday {
            background: #34495e;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .calendar-day {
            background: white;
            min-height: 100px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .calendar-day:hover {
            background: #f8f9fa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .calendar-day.other-month {
            background: #f5f5f5;
            color: #95a5a6;
        }
        
        .calendar-day.today {
            background: #e8f6f3;
            border: 2px solid #27ae60;
        }
        
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .calendar-day.today .day-number {
            color: #27ae60;
        }
        
        /* Events in Calendar */
        .day-events {
            font-size: 11px;
        }
        
        .event-item {
            background: #3498db;
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        
        .event-item.gorusme { background: #3498db; }
        .event-item.arama { background: #27ae60; }
        .event-item.ziyaret { background: #e67e22; }
        .event-item.diger { background: #9b59b6; }
        
        .event-count {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            margin-top: 5px;
            display: inline-block;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
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
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        
        /* Upcoming Events */
        .upcoming-events {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .event-list {
            margin-top: 15px;
        }
        
        .event-list-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s;
        }
        
        .event-list-item:hover {
            background: #f8f9fa;
        }
        
        .event-time {
            font-weight: bold;
            color: #2c3e50;
            min-width: 60px;
        }
        
        .event-title {
            flex: 1;
            margin-left: 15px;
        }
        
        .event-customer {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .event-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-beklemede { background: #fff3cd; color: #856404; }
        .status-tamamlandi { background: #d4edda; color: #155724; }
        .status-iptal { background: #f8d7da; color: #721c24; }
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
            <h1>📅 Takvim ve Planlama</h1>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="etkinlik-ekle.php" class="action-btn btn-primary">
                    ➕ Yeni Etkinlik
                </a>
                <a href="hatirlatma-ekle.php" class="action-btn btn-success">
                    🔔 Hatırlatma Ekle
                </a>
                <a href="hatirlatmalar.php" class="action-btn btn-warning">
                    📋 Hatırlatmalarım
                    <?php if($bekleyen_hatirlatma > 0): ?>
                    <span class="notification-badge"><?php echo $bekleyen_hatirlatma; ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php" class="action-btn" style="background: #95a5a6; color: white;">
                    ← CRM Ana Sayfa
                </a>
            </div>
            
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-box">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?php echo count($etkinlikler); ?></div>
                    <div class="stat-label">Bu Ayki Etkinlik</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">📆</div>
                    <div class="stat-value"><?php echo $haftalik_etkinlik; ?></div>
                    <div class="stat-label">Bu Haftaki Etkinlik</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-value"><?php echo $bekleyen_hatirlatma; ?></div>
                    <div class="stat-label">Bekleyen Hatırlatma</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">📍</div>
                    <div class="stat-value"><?php echo date('d'); ?></div>
                    <div class="stat-label"><?php echo $months[date('n')] . ' ' . date('Y'); ?></div>
                </div>
            </div>
            
            <!-- Calendar -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <h2 class="calendar-title">
                        <?php echo $months[$month] . ' ' . $year; ?>
                    </h2>
                    <div class="calendar-nav">
                        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">
                            ← Önceki
                        </a>
                        <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="nav-btn today-btn">
                            Bugün
                        </a>
                        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">
                            Sonraki →
                        </a>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <!-- Weekdays -->
                    <div class="calendar-weekday">Pzt</div>
                    <div class="calendar-weekday">Sal</div>
                    <div class="calendar-weekday">Çar</div>
                    <div class="calendar-weekday">Per</div>
                    <div class="calendar-weekday">Cum</div>
                    <div class="calendar-weekday">Cmt</div>
                    <div class="calendar-weekday">Paz</div>
                    
                    <?php
                    // Önceki ayın günleri
                    for($i = 1; $i < $first_weekday; $i++) {
                        echo '<div class="calendar-day other-month"></div>';
                    }
                    
                    // Bu ayın günleri
                    for($day = 1; $day <= $days_in_month; $day++) {
                        $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                        $class = $is_today ? 'calendar-day today' : 'calendar-day';
                        
                        echo '<div class="' . $class . '" onclick="goToDay(' . $day . ')">';
                        echo '<div class="day-number">' . $day . '</div>';
                        echo '<div class="day-events">';
                        
                        if(isset($events_by_day[$day])) {
                            $count = 0;
                            foreach($events_by_day[$day] as $event) {
                                if($count < 3) {
                                    $time = date('H:i', strtotime($event['baslangic_tarih']));
                                    $title = htmlspecialchars($event['baslik']);
                                    echo '<div class="event-item ' . $event['etkinlik_tipi'] . '" title="' . $time . ' - ' . $title . '">';
                                    echo substr($time, 0, 5) . ' ' . substr($title, 0, 15) . '...';
                                    echo '</div>';
                                }
                                $count++;
                            }
                            
                            if($count > 3) {
                                $more = $count - 3;
                                echo '<div class="event-count">+' . $more . ' daha</div>';
                            }
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    // Sonraki ayın günleri
                    $remaining_days = 7 - (($days_in_month + $first_weekday - 1) % 7);
                    if($remaining_days < 7) {
                        for($i = 0; $i < $remaining_days; $i++) {
                            echo '<div class="calendar-day other-month"></div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="upcoming-events">
                <h3>📅 Yaklaşan Etkinlikler</h3>
                <div class="event-list">
                    <?php
                    $upcoming_sql = "SELECT e.*, 
                        CASE 
                            WHEN e.musteri_tipi = 'alici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_alici_musteriler WHERE id = e.musteri_id)
                            WHEN e.musteri_tipi = 'satici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_satici_musteriler WHERE id = e.musteri_id)
                            ELSE NULL
                        END as musteri_adi
                        FROM crm_takvim_etkinlikler e
                        WHERE e.baslangic_tarih >= NOW()
                        AND e.durum = 'beklemede'";
                    
                    if($current_user_role != 'admin') {
                        $upcoming_sql .= " AND (e.olusturan_user_id = :user_id OR e.atanan_user_id = :user_id)";
                    }
                    
                    $upcoming_sql .= " ORDER BY e.baslangic_tarih LIMIT 10";
                    
                    $stmt = $db->prepare($upcoming_sql);
                    if($current_user_role != 'admin') {
                        $stmt->execute([':user_id' => $current_user_id]);
                    } else {
                        $stmt->execute();
                    }
                    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($upcoming_events) > 0):
                        foreach($upcoming_events as $event):
                    ?>
                    <div class="event-list-item">
                        <div class="event-time">
                            <?php echo date('d.m H:i', strtotime($event['baslangic_tarih'])); ?>
                        </div>
                        <div class="event-title">
                            <strong><?php echo htmlspecialchars($event['baslik']); ?></strong>
                            <?php if($event['musteri_adi']): ?>
                            <div class="event-customer">
                                👤 <?php echo htmlspecialchars($event['musteri_adi']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="event-status status-<?php echo $event['durum']; ?>">
                            <?php echo ucfirst($event['durum']); ?>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                        Yaklaşan etkinlik bulunmuyor.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function goToDay(day) {
        window.location.href = 'etkinlik-ekle.php?day=' + day + '&month=<?php echo $month; ?>&year=<?php echo $year; ?>';
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

// Ay ve yıl parametreleri
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Ayın ilk ve son günleri
$first_day = mktime(0, 0, 0, $month, 1, $year);
$last_day = mktime(0, 0, 0, $month + 1, 0, $year);
$days_in_month = date('t', $first_day);
$first_weekday = date('N', $first_day); // 1 = Pazartesi, 7 = Pazar

// Önceki ve sonraki ay
$prev_month = $month - 1;
$prev_year = $year;
if($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Ay adları
$months = array(
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
);

// Bu aydaki etkinlikleri çek
$start_date = "$year-$month-01 00:00:00";
$end_date = date('Y-m-d 23:59:59', $last_day);

$sql = "SELECT e.*, 
        CASE 
            WHEN e.musteri_tipi = 'alici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_alici_musteriler WHERE id = e.musteri_id)
            WHEN e.musteri_tipi = 'satici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_satici_musteriler WHERE id = e.musteri_id)
            ELSE NULL
        END as musteri_adi
        FROM crm_takvim_etkinlikler e
        WHERE (e.baslangic_tarih BETWEEN :start_date AND :end_date
        OR e.bitis_tarih BETWEEN :start_date AND :end_date)";

if($current_user_role != 'admin') {
    $sql .= " AND (e.olusturan_user_id = :user_id OR e.atanan_user_id = :user_id)";
}

$sql .= " ORDER BY e.baslangic_tarih";

$stmt = $db->prepare($sql);
$params = [':start_date' => $start_date, ':end_date' => $end_date];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$etkinlikler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Etkinlikleri günlere göre grupla
$events_by_day = [];
foreach($etkinlikler as $etkinlik) {
    $day = date('j', strtotime($etkinlik['baslangic_tarih']));
    if(!isset($events_by_day[$day])) {
        $events_by_day[$day] = [];
    }
    $events_by_day[$day][] = $etkinlik;
}

// Bugünkü hatırlatmaları çek
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as count FROM crm_hatirlatmalar 
        WHERE user_id = :user_id 
        AND DATE(hatirlatma_tarihi) = :today 
        AND durum = 'aktif' 
        AND okundu = 0";
$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $current_user_id, ':today' => $today]);
$bekleyen_hatirlatma = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Bu haftaki etkinlik sayısı
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$sql = "SELECT COUNT(*) as count FROM crm_takvim_etkinlikler 
        WHERE DATE(baslangic_tarih) BETWEEN :start AND :end";
if($current_user_role != 'admin') {
    $sql .= " AND (olusturan_user_id = :user_id OR atanan_user_id = :user_id)";
}
$stmt = $db->prepare($sql);
$params = [':start' => $week_start, ':end' => $week_end];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$haftalik_etkinlik = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Takvim ve Planlama - CRM</title>
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
        
        /* Header Stats */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Calendar Container */
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        /* Calendar Header */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .calendar-title {
            font-size: 24px;
            color: #2c3e50;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .nav-btn {
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .nav-btn:hover {
            background: #2980b9;
        }
        
        .today-btn {
            background: #27ae60;
        }
        
        .today-btn:hover {
            background: #229954;
        }
        
        /* Calendar Grid */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ecf0f1;
            border: 1px solid #ecf0f1;
        }
        
        .calendar-weekday {
            background: #34495e;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .calendar-day {
            background: white;
            min-height: 100px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .calendar-day:hover {
            background: #f8f9fa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .calendar-day.other-month {
            background: #f5f5f5;
            color: #95a5a6;
        }
        
        .calendar-day.today {
            background: #e8f6f3;
            border: 2px solid #27ae60;
        }
        
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .calendar-day.today .day-number {
            color: #27ae60;
        }
        
        /* Events in Calendar */
        .day-events {
            font-size: 11px;
        }
        
        .event-item {
            background: #3498db;
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        
        .event-item.gorusme { background: #3498db; }
        .event-item.arama { background: #27ae60; }
        .event-item.ziyaret { background: #e67e22; }
        .event-item.diger { background: #9b59b6; }
        
        .event-count {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            margin-top: 5px;
            display: inline-block;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
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
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        
        /* Upcoming Events */
        .upcoming-events {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .event-list {
            margin-top: 15px;
        }
        
        .event-list-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s;
        }
        
        .event-list-item:hover {
            background: #f8f9fa;
        }
        
        .event-time {
            font-weight: bold;
            color: #2c3e50;
            min-width: 60px;
        }
        
        .event-title {
            flex: 1;
            margin-left: 15px;
        }
        
        .event-customer {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .event-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-beklemede { background: #fff3cd; color: #856404; }
        .status-tamamlandi { background: #d4edda; color: #155724; }
        .status-iptal { background: #f8d7da; color: #721c24; }
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
            <h1>📅 Takvim ve Planlama</h1>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="etkinlik-ekle.php" class="action-btn btn-primary">
                    ➕ Yeni Etkinlik
                </a>
                <a href="hatirlatma-ekle.php" class="action-btn btn-success">
                    🔔 Hatırlatma Ekle
                </a>
                <a href="hatirlatmalar.php" class="action-btn btn-warning">
                    📋 Hatırlatmalarım
                    <?php if($bekleyen_hatirlatma > 0): ?>
                    <span class="notification-badge"><?php echo $bekleyen_hatirlatma; ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php" class="action-btn" style="background: #95a5a6; color: white;">
                    ← CRM Ana Sayfa
                </a>
            </div>
            
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-box">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?php echo count($etkinlikler); ?></div>
                    <div class="stat-label">Bu Ayki Etkinlik</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">📆</div>
                    <div class="stat-value"><?php echo $haftalik_etkinlik; ?></div>
                    <div class="stat-label">Bu Haftaki Etkinlik</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-value"><?php echo $bekleyen_hatirlatma; ?></div>
                    <div class="stat-label">Bekleyen Hatırlatma</div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">📍</div>
                    <div class="stat-value"><?php echo date('d'); ?></div>
                    <div class="stat-label"><?php echo $months[date('n')] . ' ' . date('Y'); ?></div>
                </div>
            </div>
            
            <!-- Calendar -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <h2 class="calendar-title">
                        <?php echo $months[$month] . ' ' . $year; ?>
                    </h2>
                    <div class="calendar-nav">
                        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">
                            ← Önceki
                        </a>
                        <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="nav-btn today-btn">
                            Bugün
                        </a>
                        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">
                            Sonraki →
                        </a>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <!-- Weekdays -->
                    <div class="calendar-weekday">Pzt</div>
                    <div class="calendar-weekday">Sal</div>
                    <div class="calendar-weekday">Çar</div>
                    <div class="calendar-weekday">Per</div>
                    <div class="calendar-weekday">Cum</div>
                    <div class="calendar-weekday">Cmt</div>
                    <div class="calendar-weekday">Paz</div>
                    
                    <?php
                    // Önceki ayın günleri
                    for($i = 1; $i < $first_weekday; $i++) {
                        echo '<div class="calendar-day other-month"></div>';
                    }
                    
                    // Bu ayın günleri
                    for($day = 1; $day <= $days_in_month; $day++) {
                        $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                        $class = $is_today ? 'calendar-day today' : 'calendar-day';
                        
                        echo '<div class="' . $class . '" onclick="goToDay(' . $day . ')">';
                        echo '<div class="day-number">' . $day . '</div>';
                        echo '<div class="day-events">';
                        
                        if(isset($events_by_day[$day])) {
                            $count = 0;
                            foreach($events_by_day[$day] as $event) {
                                if($count < 3) {
                                    $time = date('H:i', strtotime($event['baslangic_tarih']));
                                    $title = htmlspecialchars($event['baslik']);
                                    echo '<div class="event-item ' . $event['etkinlik_tipi'] . '" title="' . $time . ' - ' . $title . '">';
                                    echo substr($time, 0, 5) . ' ' . substr($title, 0, 15) . '...';
                                    echo '</div>';
                                }
                                $count++;
                            }
                            
                            if($count > 3) {
                                $more = $count - 3;
                                echo '<div class="event-count">+' . $more . ' daha</div>';
                            }
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    // Sonraki ayın günleri
                    $remaining_days = 7 - (($days_in_month + $first_weekday - 1) % 7);
                    if($remaining_days < 7) {
                        for($i = 0; $i < $remaining_days; $i++) {
                            echo '<div class="calendar-day other-month"></div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="upcoming-events">
                <h3>📅 Yaklaşan Etkinlikler</h3>
                <div class="event-list">
                    <?php
                    $upcoming_sql = "SELECT e.*, 
                        CASE 
                            WHEN e.musteri_tipi = 'alici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_alici_musteriler WHERE id = e.musteri_id)
                            WHEN e.musteri_tipi = 'satici' THEN (SELECT CONCAT(ad, ' ', soyad) FROM crm_satici_musteriler WHERE id = e.musteri_id)
                            ELSE NULL
                        END as musteri_adi
                        FROM crm_takvim_etkinlikler e
                        WHERE e.baslangic_tarih >= NOW()
                        AND e.durum = 'beklemede'";
                    
                    if($current_user_role != 'admin') {
                        $upcoming_sql .= " AND (e.olusturan_user_id = :user_id OR e.atanan_user_id = :user_id)";
                    }
                    
                    $upcoming_sql .= " ORDER BY e.baslangic_tarih LIMIT 10";
                    
                    $stmt = $db->prepare($upcoming_sql);
                    if($current_user_role != 'admin') {
                        $stmt->execute([':user_id' => $current_user_id]);
                    } else {
                        $stmt->execute();
                    }
                    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($upcoming_events) > 0):
                        foreach($upcoming_events as $event):
                    ?>
                    <div class="event-list-item">
                        <div class="event-time">
                            <?php echo date('d.m H:i', strtotime($event['baslangic_tarih'])); ?>
                        </div>
                        <div class="event-title">
                            <strong><?php echo htmlspecialchars($event['baslik']); ?></strong>
                            <?php if($event['musteri_adi']): ?>
                            <div class="event-customer">
                                👤 <?php echo htmlspecialchars($event['musteri_adi']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="event-status status-<?php echo $event['durum']; ?>">
                            <?php echo ucfirst($event['durum']); ?>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                        Yaklaşan etkinlik bulunmuyor.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function goToDay(day) {
        window.location.href = 'etkinlik-ekle.php?day=' + day + '&month=<?php echo $month; ?>&year=<?php echo $year; ?>';
    }
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>