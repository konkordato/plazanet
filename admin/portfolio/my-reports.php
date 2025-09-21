<?php
session_start();

// Kullanıcı girişi kontrolü
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Sadece normal kullanıcılar (danışmanlar) erişebilir
if($_SESSION['user_role'] !== 'user') {
    header("Location: ../dashboard.php");
    exit();
}

require_once '../../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_fullname'];
$current_year = date('Y');
$current_month = date('n');

// Filtreler
$selected_year = $_GET['year'] ?? $current_year;
$selected_month = $_GET['month'] ?? $current_month;
$view_type = $_GET['view'] ?? 'monthly';

// Prim barajlarını çek
$thresholds = $db->prepare("
    SELECT * FROM commission_thresholds 
    WHERE year = :year AND is_active = 1
    ORDER BY threshold_level
");
$thresholds->execute([':year' => $selected_year]);
$commission_thresholds = $thresholds->fetchAll(PDO::FETCH_ASSOC);

// Danışmanın kapatmalarını çek
function getMyClosings($db, $user_id, $year, $month = null) {
    $sql = "
        SELECT 
            pc.*,
            ca.full_name as customer_advisor_name,
            pa.full_name as portfolio_advisor_name,
            ra.full_name as referral_advisor_name
        FROM portfolio_closings pc
        LEFT JOIN users ca ON pc.customer_advisor_id = ca.id
        LEFT JOIN users pa ON pc.portfolio_advisor_id = pa.id
        LEFT JOIN users ra ON pc.referral_advisor_id = ra.id
        WHERE YEAR(pc.closing_date) = :year
        AND (
            pc.customer_advisor_id = :user_id OR 
            pc.portfolio_advisor_id = :user_id2 OR 
            pc.referral_advisor_id = :user_id3
        )
    ";
    
    $params = [
        ':year' => $year,
        ':user_id' => $user_id,
        ':user_id2' => $user_id,
        ':user_id3' => $user_id
    ];
    
    if($month) {
        $sql .= " AND MONTH(pc.closing_date) = :month";
        $params[':month'] = $month;
    }
    
    $sql .= " ORDER BY pc.closing_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Aylık ciro hesaplama (prim için)
function calculateMonthlyTurnover($closings, $user_id) {
    $turnover = 0;
    
    foreach($closings as $closing) {
        // Müşteri danışmanı ise tam ciro
        if($closing['customer_advisor_id'] == $user_id) {
            $turnover += $closing['total_amount'];
        }
        // Portföy danışmanı ise ve müşteri danışmanı değilse tam ciro
        elseif($closing['portfolio_advisor_id'] == $user_id) {
            $turnover += $closing['total_amount'];
        }
        // Sadece referans ise %50 ciro
        elseif($closing['referral_advisor_id'] == $user_id) {
            $turnover += $closing['total_amount'] * 0.5;
        }
    }
    
    return $turnover;
}

// Kazanç hesaplama
function calculateMyRevenue($closings, $user_id) {
    $revenue = 0;
    
    foreach($closings as $closing) {
        if($closing['customer_advisor_id'] == $user_id) {
            $revenue += $closing['customer_advisor_share'];
        }
        if($closing['portfolio_advisor_id'] == $user_id) {
            $revenue += $closing['portfolio_advisor_share'];
        }
        if($closing['referral_advisor_id'] == $user_id) {
            $revenue += $closing['referral_advisor_share'];
        }
    }
    
    return $revenue;
}

// PRİM HESAPLAMA - AY SONU TOPLAM CİRO ÜZERİNDEN
function calculateEarnedCommission($monthlyTurnover, $thresholds) {
    // İlk barajın danışman yüzdesini temel oran olarak al (genelde %50)
    $baseRate = !empty($thresholds) ? $thresholds[0]['advisor_percentage'] : 50;
    
    // Hangi barajdayız?
    $currentThreshold = null;
    $currentBonusRate = 0;
    
    foreach($thresholds as $threshold) {
        if($monthlyTurnover >= $threshold['min_amount']) {
            if($threshold['max_amount'] === null || $monthlyTurnover <= $threshold['max_amount']) {
                $currentThreshold = $threshold;
                $currentBonusRate = $threshold['advisor_percentage'] - $baseRate;
            }
        }
    }
    
    // Prim hesapla: Toplam ciro x Prim oranı
    $totalCommission = 0;
    if($currentBonusRate > 0) {
        $totalCommission = $monthlyTurnover * ($currentBonusRate / 100);
    }
    
    return [
        'total' => $totalCommission,
        'rate' => $currentBonusRate,
        'threshold' => $currentThreshold,
        'base_rate' => $baseRate
    ];
}

// Detaylı kapatma listesi için prim dağıtımı
function distributeCommissionToClosings($closings, $user_id, $totalCommission, $monthlyTurnover) {
    $details = [];
    
    if($totalCommission <= 0 || $monthlyTurnover <= 0) {
        return $details;
    }
    
    foreach($closings as $closing) {
        $myContribution = 0;
        $myRole = '';
        
        // Bu kapatmadaki ciro katkım
        if($closing['customer_advisor_id'] == $user_id) {
            $myContribution += $closing['total_amount'];
            $myRole = 'Müşteri Danışmanı';
        }
        if($closing['portfolio_advisor_id'] == $user_id) {
            if($closing['customer_advisor_id'] != $user_id) {
                $myContribution += $closing['total_amount'];
            }
            $myRole = !empty($myRole) ? $myRole . ' + Portföy D.' : 'Portföy Danışmanı';
        }
        if($closing['referral_advisor_id'] == $user_id) {
            if($closing['customer_advisor_id'] != $user_id && $closing['portfolio_advisor_id'] != $user_id) {
                $myContribution += $closing['total_amount'] * 0.5; // Referans %50 sayılır
            }
            $myRole = !empty($myRole) ? $myRole . ' + Referans' : 'Referans';
        }
        
        // Bu kapatmanın primdeki payı
        if($myContribution > 0) {
            $commissionShare = ($myContribution / $monthlyTurnover) * $totalCommission;
            
            $details[] = [
                'date' => $closing['closing_date'],
                'property' => $closing['property_title'],
                'role' => $myRole,
                'contribution' => $myContribution,
                'commission_share' => $commissionShare
            ];
        }
    }
    
    return $details;
}

// Prim durumu hesaplama
function calculateCommissionStatus($turnover, $thresholds) {
    $current_threshold = null;
    
    foreach($thresholds as $threshold) {
        if($turnover >= $threshold['min_amount']) {
            if($threshold['max_amount'] === null || $turnover <= $threshold['max_amount']) {
                $current_threshold = $threshold;
            }
        }
    }
    
    return $current_threshold;
}

// Verileri al
$my_closings = getMyClosings($db, $user_id, $selected_year, 
    ($view_type == 'monthly' ? $selected_month : null)
);

$monthly_turnover = calculateMonthlyTurnover($my_closings, $user_id);
$my_revenue = calculateMyRevenue($my_closings, $user_id);
$current_commission_status = calculateCommissionStatus($monthly_turnover, $commission_thresholds);

// PRİM HESAPLAMA - AY SONU TOPLAM ÜZERİNDEN
$commission_calculation = calculateEarnedCommission($monthly_turnover, $commission_thresholds);
$earned_commission = $commission_calculation['total'];
$commission_rate = $commission_calculation['rate'];
$current_threshold = $commission_calculation['threshold'];
$base_rate = $commission_calculation['base_rate'];

// Kapatma detayları için prim dağıtımı
$commission_details = distributeCommissionToClosings($my_closings, $user_id, $earned_commission, $monthly_turnover);

// Toplam kazanç (normal pay + prim)
$total_earnings = $my_revenue + $earned_commission;

// Yıllık özet (tüm aylar)
$yearly_summary = [];
$yearly_commission_total = 0;

if($view_type == 'yearly') {
    for($m = 1; $m <= 12; $m++) {
        $month_closings = getMyClosings($db, $user_id, $selected_year, $m);
        $month_turnover = calculateMonthlyTurnover($month_closings, $user_id);
        $month_revenue = calculateMyRevenue($month_closings, $user_id);
        
        // Ay sonu prim hesaplama
        $month_commission_calc = calculateEarnedCommission($month_turnover, $commission_thresholds);
        $month_commission = $month_commission_calc['total'];
        
        $yearly_summary[$m] = [
            'closings' => count($month_closings),
            'turnover' => $month_turnover,
            'revenue' => $month_revenue,
            'commission' => $month_commission,
            'total_earnings' => $month_revenue + $month_commission
        ];
        
        $yearly_commission_total += $month_commission;
    }
}

// Hedef baraj için kalan tutar
$next_threshold = null;
$remaining_for_next = 0;

foreach($commission_thresholds as $threshold) {
    if($monthly_turnover < $threshold['min_amount']) {
        $next_threshold = $threshold;
        $remaining_for_next = $threshold['min_amount'] - $monthly_turnover;
        break;
    }
}

// Ay isimleri
$months = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satış Raporlarım - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .performance-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .performance-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .welcome-message {
            font-size: 24px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .performance-subtitle {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card.premium {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        
        .stat-card.total-earnings {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card.premium .stat-value,
        .stat-card.total-earnings .stat-value {
            color: white;
        }
        
        .stat-label {
            font-size: 14px;
        }
        
        .stat-card.premium .stat-label,
        .stat-card.total-earnings .stat-label {
            opacity: 0.9;
        }
        
        .commission-status {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .commission-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 15px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .threshold-info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .threshold-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .threshold-item:last-child {
            border-bottom: none;
        }
        
        .threshold-active {
            background: #d4edda;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .commission-breakdown {
            background: #fff5e6;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .commission-breakdown-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #ff6b00;
        }
        
        .commission-detail-item {
            padding: 10px;
            background: white;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .commission-amount {
            font-size: 18px;
            font-weight: bold;
            color: #ff6b00;
        }
        
        .motivation-box {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .motivation-text {
            font-size: 18px;
            font-weight: 600;
        }
        
        .closings-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .closings-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .closings-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .closings-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .role-customer {
            background: #3498db;
            color: white;
        }
        
        .role-portfolio {
            background: #9b59b6;
            color: white;
        }
        
        .role-referral {
            background: #e67e22;
            color: white;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .view-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .view-tab {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .view-tab.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .yearly-chart {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .month-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .month-name {
            width: 80px;
            font-weight: 600;
        }
        
        .month-progress {
            flex: 1;
            height: 25px;
            background: #ecf0f1;
            border-radius: 5px;
            margin: 0 10px;
            overflow: hidden;
        }
        
        .month-fill {
            height: 100%;
            background: #3498db;
            display: flex;
            align-items: center;
            padding-left: 10px;
            color: white;
            font-size: 12px;
        }
        
        .month-value {
            min-width: 100px;
            text-align: right;
            font-weight: bold;
            color: #27ae60;
        }
        
        .info-tooltip {
            display: inline-block;
            background: #333;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 5px;
            cursor: help;
        }
        
        .commission-info-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .commission-info-box strong {
            color: #2e7d32;
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
                <li><a href="../user-dashboard.php">🏠 Ana Sayfa</a></li>
                <li><a href="../my-properties.php">🏢 İlanlarım</a></li>
                <li><a href="closing.php">💰 Portföy Kapatma</a></li>
                <li><a href="my-reports.php" class="active">📊 Satış Raporlarım</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Satış Performansım</h3>
                </div>
                <div class="navbar-right">
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Hoşgeldin Mesajı -->
                <div class="performance-header">
                    <div class="welcome-message">
                        Merhaba <?php echo htmlspecialchars($user_name); ?> 👋
                    </div>
                    <div class="performance-subtitle">
                        <?php echo $selected_year; ?> yılı 
                        <?php echo $view_type == 'monthly' ? $months[$selected_month] . ' ayı' : ''; ?>
                        performans raporun
                    </div>
                </div>

                <!-- Görünüm Sekmeleri -->
                <div class="view-tabs">
                    <a href="?view=monthly&year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" 
                       class="view-tab <?php echo $view_type == 'monthly' ? 'active' : ''; ?>">Aylık</a>
                    <a href="?view=yearly&year=<?php echo $selected_year; ?>" 
                       class="view-tab <?php echo $view_type == 'yearly' ? 'active' : ''; ?>">Yıllık</a>
                </div>

                <!-- Filtreler -->
                <div class="filter-section">
                    <form method="GET" action="">
                        <input type="hidden" name="view" value="<?php echo $view_type; ?>">
                        <div style="display: flex; gap: 15px;">
                            <select name="year" onchange="this.form.submit()">
                                <?php for($y = 2024; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            
                            <?php if($view_type == 'monthly'): ?>
                            <select name="month" onchange="this.form.submit()">
                                <?php foreach($months as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $selected_month == $num ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php if($view_type == 'monthly'): ?>
                <!-- Aylık İstatistikler - GÜNCELLENDİ -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value">
                            <?php echo number_format($monthly_turnover, 0, ',', '.'); ?> TL
                        </div>
                        <div class="stat-label">Aylık Ciro</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💵</div>
                        <div class="stat-value">
                            <?php echo number_format($my_revenue, 0, ',', '.'); ?> TL
                        </div>
                        <div class="stat-label">Normal Kazancım</div>
                    </div>
                    
                    <div class="stat-card premium">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-value">
                            <?php echo number_format($earned_commission, 0, ',', '.'); ?> TL
                        </div>
                        <div class="stat-label">Hak Edilen Prim</div>
                    </div>
                    
                    <div class="stat-card total-earnings">
                        <div class="stat-icon">💎</div>
                        <div class="stat-value">
                            <?php echo number_format($total_earnings, 0, ',', '.'); ?> TL
                        </div>
                        <div class="stat-label">Toplam Kazancım</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-value">
                            <?php echo count($my_closings); ?>
                        </div>
                        <div class="stat-label">Kapatma Sayısı</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-value">
                            %<?php echo $current_commission_status ? $current_commission_status['advisor_percentage'] : '50'; ?>
                        </div>
                        <div class="stat-label">Mevcut Oran</div>
                    </div>
                </div>

                <!-- PRİM DETAYLARI - YENİ -->
                <?php if($earned_commission > 0): ?>
                <div class="commission-breakdown">
                    <div class="commission-breakdown-title">
                        🏆 Prim Detaylarınız
                    </div>
                    
                    <div class="commission-info-box">
                        <strong>📊 Prim Hesaplama Sistemi:</strong><br>
                        Ay sonu toplam cironuza göre prim kazanırsınız:<br><br>
                        <?php 
                        foreach($commission_thresholds as $threshold): 
                            $bonusRate = $threshold['advisor_percentage'] - $base_rate;
                        ?>
                            📍 <strong><?php 
                                echo number_format($threshold['min_amount'], 0, ',', '.');
                                if($threshold['max_amount']) {
                                    echo ' - ' . number_format($threshold['max_amount'], 0, ',', '.');
                                } else {
                                    echo ' TL ve üzeri';
                                }
                            ?></strong><br>
                            &nbsp;&nbsp;&nbsp;&nbsp;→ Danışman payı: %<?php echo $threshold['advisor_percentage']; ?>
                            <?php if($bonusRate > 0): ?>
                                (Toplam cironun <strong>%<?php echo $bonusRate; ?>'i prim</strong>)
                            <?php else: ?>
                                (Prim yok)
                            <?php endif; ?><br><br>
                        <?php endforeach; ?>
                        
                        <div style="background: #fff; padding: 10px; border-radius: 5px; margin-top: 10px;">
                            <strong>Örnek:</strong> 60.000 TL ciro = 3.000 TL prim (%5)<br>
                            <strong>Örnek:</strong> 140.000 TL ciro = 14.000 TL prim (%10)
                        </div>
                    </div>
                    
                    <?php if(!empty($commission_details)): ?>
    <div style="margin-top: 15px;">
        <strong>📋 Kapatmalarınızın Prim Katkıları:</strong>
    </div>
    <?php foreach($commission_details as $detail): ?>
    <div class="commission-detail-item">
        <div>
            <div style="font-weight: 600;">
                <?php echo date('d.m.Y', strtotime($detail['date'])); ?> - 
                <?php echo htmlspecialchars($detail['property']); ?>
            </div>
            <div style="font-size: 12px; color: #666;">
                <?php echo $detail['role']; ?> | 
                Ciro Katkısı: <?php echo number_format($detail['contribution'], 0, ',', '.'); ?> TL
            </div>
        </div>
        <div class="commission-amount">
            +<?php echo number_format($detail['commission_share'], 0, ',', '.'); ?> TL
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #ffc107; text-align: right;">
                        <span style="font-size: 20px; font-weight: bold; color: #ff6b00;">
                            TOPLAM PRİM: <?php echo number_format($earned_commission, 0, ',', '.'); ?> TL
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Motivasyon Mesajı -->
                <?php if($next_threshold && $remaining_for_next > 0): ?>
                <div class="motivation-box">
                    <div class="motivation-text">
                        🎯 Sonraki prim barajına (%<?php echo $next_threshold['advisor_percentage']; ?>) ulaşmak için 
                        <strong><?php echo number_format($remaining_for_next, 0, ',', '.'); ?> TL</strong> 
                        daha ciro yapmalısın! Hadi başarabilirsin! 💪
                    </div>
                </div>
                <?php elseif(!empty($commission_thresholds) && $earned_commission == 0): ?>
                    <?php 
                    // İlk prim barajını bul (temel orandan yüksek olan ilk baraj)
                    $firstBonus = null;
                    foreach($commission_thresholds as $threshold) {
                        if($threshold['advisor_percentage'] > $base_rate) {
                            $firstBonus = $threshold;
                            break;
                        }
                    }
                    if($firstBonus && $monthly_turnover < $firstBonus['min_amount']):
                    ?>
                    <div class="motivation-box">
                        <div class="motivation-text">
                            🎯 İlk prim barajına ulaşmak için 
                            <strong><?php echo number_format($firstBonus['min_amount'] - $monthly_turnover, 0, ',', '.'); ?> TL</strong> 
                            daha ciro yapmalısın! %<?php echo $firstBonus['advisor_percentage']; ?> pay almaya başlayacaksın! 💪
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Prim Durumu -->
                <div class="commission-status">
                    <div class="commission-title">📈 Prim Durumum</div>
                    
                    <?php 
                    $max_threshold = end($commission_thresholds);
                    $max_amount = $max_threshold['min_amount'] ?? 115000;
                    $progress_percentage = min(($monthly_turnover / $max_amount) * 100, 100);
                    ?>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%;">
                            <?php echo number_format($progress_percentage, 1); ?>%
                        </div>
                    </div>
                    
                    <div class="threshold-info">
                        <div class="commission-title" style="font-size: 16px;">Prim Barajları</div>
                        <?php foreach($commission_thresholds as $threshold): ?>
                            <?php 
                            $is_active = false;
                            if($monthly_turnover >= $threshold['min_amount']) {
                                if($threshold['max_amount'] === null || $monthly_turnover <= $threshold['max_amount']) {
                                    $is_active = true;
                                }
                            }
                            ?>
                            <div class="threshold-item <?php echo $is_active ? 'threshold-active' : ''; ?>">
                                <span>
                                    <?php 
                                    if($threshold['max_amount']) {
                                        echo number_format($threshold['min_amount'], 0, ',', '.') . ' - ' . 
                                             number_format($threshold['max_amount'], 0, ',', '.') . ' TL';
                                    } else {
                                        echo number_format($threshold['min_amount'], 0, ',', '.') . ' TL ve üzeri';
                                    }
                                    ?>
                                </span>
                                <span>
                                    <strong>%<?php echo $threshold['advisor_percentage']; ?></strong>
                                    <?php if($is_active): ?> 
                                        ✅ Aktif
                                    <?php endif; ?>
                                    <?php 
                                    $bonus = $threshold['advisor_percentage'] - $base_rate;
                                    if($bonus > 0): 
                                    ?>
                                        <span class="info-tooltip">+%<?php echo $bonus; ?> prim</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Kapatma Detayları -->
                <div class="closings-table">
                    <h3 style="padding: 20px 20px 0;">📋 Kapatma Detaylarım</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Gayrimenkul</th>
                                <th>Tip</th>
                                <th>Toplam İşlem</th>
                                <th>Benim Rolüm</th>
                                <th>Normal Kazanç</th>
                                <th>Prim Payı</th>
                                <th>Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($my_closings as $closing): 
                                $my_share = 0;
                                $my_commission_share = 0;
                                
                                // Normal kazancı hesapla
                                if($closing['customer_advisor_id'] == $user_id) {
                                    $my_share += $closing['customer_advisor_share'];
                                }
                                if($closing['portfolio_advisor_id'] == $user_id) {
                                    $my_share += $closing['portfolio_advisor_share'];
                                }
                                if($closing['referral_advisor_id'] == $user_id) {
                                    $my_share += $closing['referral_advisor_share'];
                                }
                                
                                // Bu kapatmanın primdeki payını bul
                                foreach($commission_details as $detail) {
                                    if($detail['date'] == $closing['closing_date'] && 
                                       $detail['property'] == $closing['property_title']) {
                                        $my_commission_share = $detail['commission_share'];
                                        break;
                                    }
                                }
                                
                                $total_from_this = $my_share + $my_commission_share;
                            ?>
                            <tr>
                                <td><?php echo date('d.m.Y', strtotime($closing['closing_date'])); ?></td>
                                <td><?php echo htmlspecialchars($closing['property_title']); ?></td>
                                <td><?php echo ucfirst($closing['closing_type']); ?></td>
                                <td><?php echo number_format($closing['total_amount'], 0, ',', '.'); ?> TL</td>
                                <td>
                                    <?php if($closing['customer_advisor_id'] == $user_id): ?>
                                        <span class="role-badge role-customer">Müşteri D.</span>
                                    <?php endif; ?>
                                    
                                    <?php if($closing['portfolio_advisor_id'] == $user_id): ?>
                                        <span class="role-badge role-portfolio">Portföy D.</span>
                                    <?php endif; ?>
                                    
                                    <?php if($closing['referral_advisor_id'] == $user_id): ?>
                                        <span class="role-badge role-referral">Referans</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: bold; color: #27ae60;">
                                    <?php echo number_format($my_share, 0, ',', '.'); ?> TL
                                </td>
                                <td style="font-weight: bold; color: #ff6b00;">
                                    <?php if($my_commission_share > 0): ?>
                                        +<?php echo number_format($my_commission_share, 0, ',', '.'); ?> TL
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: bold; color: #2c3e50; background: #f0f8ff;">
                                    <?php echo number_format($total_from_this, 0, ',', '.'); ?> TL
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($my_closings)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    Bu dönemde kapatma kaydın bulunmuyor.
                                </td>
                            </tr>
                            <?php else: ?>
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="5" style="text-align: right;">TOPLAM:</td>
                                <td style="color: #27ae60;"><?php echo number_format($my_revenue, 0, ',', '.'); ?> TL</td>
                                <td style="color: #ff6b00;">
                                    <?php if($earned_commission > 0): ?>
                                        +<?php echo number_format($earned_commission, 0, ',', '.'); ?> TL
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="background: #e8f5e9; color: #2e7d32;">
                                    <?php echo number_format($total_earnings, 0, ',', '.'); ?> TL
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php else: ?>
                <!-- Yıllık Görünüm - GÜNCELLENDİ -->
                <div class="yearly-chart">
                    <h3>📊 <?php echo $selected_year; ?> Yılı Aylık Performansım</h3>
                    
                    <?php 
                    $max_monthly_earnings = max(array_column($yearly_summary, 'total_earnings'));
                    foreach($yearly_summary as $month_num => $data): 
                        $bar_width = $max_monthly_earnings > 0 ? ($data['total_earnings'] / $max_monthly_earnings) * 100 : 0;
                    ?>
                    <div class="month-bar">
                        <div class="month-name"><?php echo $months[$month_num]; ?></div>
                        <div class="month-progress">
                            <div class="month-fill" style="width: <?php echo $bar_width; ?>%; 
                                 background: <?php echo $data['commission'] > 0 ? 'linear-gradient(90deg, #3498db, #ff6b00)' : '#3498db'; ?>;">
                                <?php if($data['closings'] > 0): ?>
                                    <?php echo $data['closings']; ?> kapatma
                                    <?php if($data['commission'] > 0): ?>
                                        | +<?php echo number_format($data['commission'], 0, ',', '.'); ?> TL prim
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="month-value">
                            <?php echo number_format($data['total_earnings'], 0, ',', '.'); ?> TL
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4>Yıllık Toplam</h4>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 15px;">
                            <div>
                                <div style="font-size: 24px; font-weight: bold; color: #27ae60;">
                                    <?php echo number_format(array_sum(array_column($yearly_summary, 'turnover')), 0, ',', '.'); ?> TL
                                </div>
                                <div style="color: #7f8c8d;">Toplam Ciro</div>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: bold; color: #3498db;">
                                    <?php echo number_format(array_sum(array_column($yearly_summary, 'revenue')), 0, ',', '.'); ?> TL
                                </div>
                                <div style="color: #7f8c8d;">Normal Kazanç</div>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: bold; color: #ff6b00;">
                                    +<?php echo number_format($yearly_commission_total, 0, ',', '.'); ?> TL
                                </div>
                                <div style="color: #7f8c8d;">Toplam Prim</div>
                            </div>
                            <div>
                                <div style="font-size: 24px; font-weight: bold; color: #9b59b6;">
                                    <?php echo number_format(
                                        array_sum(array_column($yearly_summary, 'revenue')) + $yearly_commission_total, 
                                        0, ',', '.'
                                    ); ?> TL
                                </div>
                                <div style="color: #7f8c8d;">Yıllık Kazanç</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>