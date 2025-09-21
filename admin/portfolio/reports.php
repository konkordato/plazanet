<?php
session_start();

// Admin kontrolü
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$user_name = $_SESSION['user_fullname'];
$current_year = date('Y');
$current_month = date('n');
$current_week = date('W');

// Filtreler
$selected_year = $_GET['year'] ?? $current_year;
$selected_month = $_GET['month'] ?? $current_month;
$selected_advisor = $_GET['advisor'] ?? '';
$view_type = $_GET['view'] ?? 'monthly'; // monthly, weekly, yearly

// Danışmanları çek
$advisors = $db->query("
    SELECT DISTINCT u.id, u.full_name 
    FROM users u
    WHERE u.role = 'user' AND u.status = 'active'
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

// Prim barajlarını çek
$thresholds = $db->prepare("
    SELECT * FROM commission_thresholds 
    WHERE year = :year AND is_active = 1
    ORDER BY threshold_level
");
$thresholds->execute([':year' => $selected_year]);
$commission_thresholds = $thresholds->fetchAll(PDO::FETCH_ASSOC);

// Ana rapor sorgusu
function getReportData($db, $year, $month = null, $week = null, $advisor_id = null) {
    $sql = "
        SELECT 
            pc.closing_date,
            pc.total_amount,
            pc.office_share,
            pc.property_title,
            pc.closing_type,
            pc.created_by,
            u.full_name as created_by_name,
            
            -- Danışman detayları
            ca.id as customer_advisor_id,
            ca.full_name as customer_advisor_name,
            pc.customer_advisor_share,
            
            pa.id as portfolio_advisor_id,
            pa.full_name as portfolio_advisor_name,
            pc.portfolio_advisor_share,
            
            ra.id as referral_advisor_id,
            ra.full_name as referral_advisor_name,
            pc.referral_advisor_share
            
        FROM portfolio_closings pc
        LEFT JOIN users u ON pc.created_by = u.id
        LEFT JOIN users ca ON pc.customer_advisor_id = ca.id
        LEFT JOIN users pa ON pc.portfolio_advisor_id = pa.id
        LEFT JOIN users ra ON pc.referral_advisor_id = ra.id
        WHERE YEAR(pc.closing_date) = :year
    ";
    
    $params = [':year' => $year];
    
    if($month) {
        $sql .= " AND MONTH(pc.closing_date) = :month";
        $params[':month'] = $month;
    }
    
    if($week) {
        $sql .= " AND WEEK(pc.closing_date, 1) = :week";
        $params[':week'] = $week;
    }
    
    if($advisor_id) {
        $sql .= " AND (pc.customer_advisor_id = :adv1 OR pc.portfolio_advisor_id = :adv2 OR pc.referral_advisor_id = :adv3)";
        $params[':adv1'] = $advisor_id;
        $params[':adv2'] = $advisor_id;
        $params[':adv3'] = $advisor_id;
    }
    
    $sql .= " ORDER BY pc.closing_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Danışman bazlı özet hesaplama
function calculateAdvisorSummary($data) {
    $summary = [];
    
    foreach($data as $closing) {
        // Müşteri danışmanı
        if($closing['customer_advisor_id']) {
            $id = $closing['customer_advisor_id'];
            if(!isset($summary[$id])) {
                $summary[$id] = [
                    'name' => $closing['customer_advisor_name'],
                    'total_revenue' => 0,
                    'total_turnover' => 0,
                    'closing_count' => 0,
                    'as_customer' => 0,
                    'as_portfolio' => 0,
                    'as_referral' => 0
                ];
            }
            $summary[$id]['total_revenue'] += $closing['customer_advisor_share'];
            $summary[$id]['total_turnover'] += $closing['total_amount'];
            $summary[$id]['closing_count']++;
            $summary[$id]['as_customer']++;
        }
        
        // Portföy danışmanı
        if($closing['portfolio_advisor_id']) {
            $id = $closing['portfolio_advisor_id'];
            if(!isset($summary[$id])) {
                $summary[$id] = [
                    'name' => $closing['portfolio_advisor_name'],
                    'total_revenue' => 0,
                    'total_turnover' => 0,
                    'closing_count' => 0,
                    'as_customer' => 0,
                    'as_portfolio' => 0,
                    'as_referral' => 0
                ];
            }
            $summary[$id]['total_revenue'] += $closing['portfolio_advisor_share'];
            if($closing['customer_advisor_id'] != $id) {
                $summary[$id]['total_turnover'] += $closing['total_amount'];
                $summary[$id]['closing_count']++;
            }
            $summary[$id]['as_portfolio']++;
        }
        
        // Referans danışmanı
        if($closing['referral_advisor_id']) {
            $id = $closing['referral_advisor_id'];
            if(!isset($summary[$id])) {
                $summary[$id] = [
                    'name' => $closing['referral_advisor_name'],
                    'total_revenue' => 0,
                    'total_turnover' => 0,
                    'closing_count' => 0,
                    'as_customer' => 0,
                    'as_portfolio' => 0,
                    'as_referral' => 0
                ];
            }
            $summary[$id]['total_revenue'] += $closing['referral_advisor_share'];
            if($closing['customer_advisor_id'] != $id && $closing['portfolio_advisor_id'] != $id) {
                $summary[$id]['total_turnover'] += $closing['total_amount'] * 0.5; // Referans ciro %50
                $summary[$id]['closing_count']++;
            }
            $summary[$id]['as_referral']++;
        }
    }
    
    // Sıralama - ciro bazında
    uasort($summary, function($a, $b) {
        return $b['total_turnover'] - $a['total_turnover'];
    });
    
    return $summary;
}

// Rapor verilerini al
$report_data = getReportData($db, $selected_year, 
    ($view_type == 'monthly' ? $selected_month : null),
    ($view_type == 'weekly' ? $current_week : null),
    $selected_advisor
);

$advisor_summary = calculateAdvisorSummary($report_data);

// Genel istatistikler
$total_turnover = array_sum(array_column($report_data, 'total_amount'));
$total_office_share = array_sum(array_column($report_data, 'office_share'));
$total_closings = count($report_data);

// Haftalık görünüm için haftaları hesapla
$weeks_in_month = [];
if($view_type == 'weekly') {
    $first_day = mktime(0, 0, 0, $selected_month, 1, $selected_year);
    $last_day = mktime(0, 0, 0, $selected_month + 1, 0, $selected_year);
    
    for($day = $first_day; $day <= $last_day; $day = strtotime('+1 week', $day)) {
        $week_num = date('W', $day);
        $weeks_in_month[$week_num] = [
            'week' => $week_num,
            'start' => date('d.m', $day),
            'end' => date('d.m', strtotime('+6 days', $day))
        ];
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
    <title>Satış Raporları - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .report-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            font-size: 16px;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea, #764ba2);
        }
        
        .summary-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .summary-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .summary-label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .advisor-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .advisor-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .advisor-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .advisor-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .advisor-table tr:hover {
            background: #f8f9fa;
        }
        
        .top-performer {
            background: #fff3cd;
        }
        
        .performance-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-gold {
            background: #ffc107;
            color: #000;
        }
        
        .badge-silver {
            background: #6c757d;
            color: white;
        }
        
        .badge-bronze {
            background: #fd7e14;
            color: white;
        }
        
        .details-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .closing-list {
            margin-top: 20px;
        }
        
        .closing-item {
            padding: 15px;
            border-left: 3px solid #3498db;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .closing-date {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .closing-details {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        
        .amount-highlight {
            color: #27ae60;
            font-weight: bold;
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
        
        .btn-export {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-export:hover {
            background: #229954;
        }
        
        .commission-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .commission-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .threshold-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
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
                <li><a href="../dashboard.php">🏠 Ana Sayfa</a></li>
                <li><a href="../properties/list.php">🏢 İlanlar</a></li>
                <li><a href="closing.php">💰 Portföy Kapatma</a></li>
                <li><a href="reports.php" class="active">📊 Satış Raporları</a></li>
                <li><a href="commission-settings.php">⚙️ Prim Ayarları</a></li>
                <li><a href="closed-properties.php">🔒 Kapatılan İlanlar</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Satış Raporları</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Rapor Başlığı -->
                <div class="report-header">
                    <h1 class="report-title">
                        📊 <?php echo $selected_year; ?> Yılı 
                        <?php if($view_type == 'monthly'): ?>
                            <?php echo $months[$selected_month]; ?> Ayı
                        <?php elseif($view_type == 'weekly'): ?>
                            Haftalık
                        <?php else: ?>
                            Yıllık
                        <?php endif; ?>
                        Satış Raporu
                    </h1>
                    <div class="report-subtitle">
                        Toplam <?php echo $total_closings; ?> kapatma | 
                        <?php echo number_format($total_turnover, 2, ',', '.'); ?> TL ciro
                    </div>
                </div>

                <!-- Görünüm Sekmeleri -->
                <div class="view-tabs">
                    <a href="?view=monthly&year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" 
                       class="view-tab <?php echo $view_type == 'monthly' ? 'active' : ''; ?>">Aylık</a>
                    <a href="?view=weekly&year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>" 
                       class="view-tab <?php echo $view_type == 'weekly' ? 'active' : ''; ?>">Haftalık</a>
                    <a href="?view=yearly&year=<?php echo $selected_year; ?>" 
                       class="view-tab <?php echo $view_type == 'yearly' ? 'active' : ''; ?>">Yıllık</a>
                </div>

                <!-- Filtreler -->
                <div class="filter-section">
                    <form method="GET" action="">
                        <input type="hidden" name="view" value="<?php echo $view_type; ?>">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Yıl</label>
                                <select name="year" onchange="this.form.submit()">
                                    <?php for($y = 2024; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <?php if($view_type != 'yearly'): ?>
                            <div class="form-group">
                                <label>Ay</label>
                                <select name="month" onchange="this.form.submit()">
                                    <?php foreach($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo $selected_month == $num ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Danışman</label>
                                <select name="advisor" onchange="this.form.submit()">
                                    <option value="">Tüm Danışmanlar</option>
                                    <?php foreach($advisors as $advisor): ?>
                                        <option value="<?php echo $advisor['id']; ?>" 
                                                <?php echo $selected_advisor == $advisor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($advisor['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <a href="export-report.php?year=<?php echo $selected_year; ?>&month=<?php echo $selected_month; ?>&type=<?php echo $view_type; ?>" 
                                   class="btn-export">
                                    📥 Excel İndir
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Özet Kartlar -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-icon">💰</div>
                        <div class="summary-value">
                            <?php echo number_format($total_turnover, 0, ',', '.'); ?> TL
                        </div>
                        <div class="summary-label">Toplam Ciro</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-icon">🏢</div>
                        <div class="summary-value">
                            <?php echo number_format($total_office_share, 0, ',', '.'); ?> TL
                        </div>
                        <div class="summary-label">Ofis Payı</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-icon">📊</div>
                        <div class="summary-value"><?php echo $total_closings; ?></div>
                        <div class="summary-label">Toplam Kapatma</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-icon">👥</div>
                        <div class="summary-value"><?php echo count($advisor_summary); ?></div>
                        <div class="summary-label">Aktif Danışman</div>
                    </div>
                </div>

                <!-- Prim Barajları Bilgisi -->
                <?php if(!empty($commission_thresholds)): ?>
                <div class="commission-info">
                    <div class="commission-title">📈 <?php echo $selected_year; ?> Yılı Prim Barajları</div>
                    <?php foreach($commission_thresholds as $threshold): ?>
                        <div class="threshold-item">
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
                                Ofis: %<?php echo $threshold['office_percentage']; ?> | 
                                Danışman: %<?php echo $threshold['advisor_percentage']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Danışman Performans Tablosu -->
                <div class="advisor-table">
                    <h3 style="padding: 20px 20px 0;">👥 Danışman Performansları</h3>
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">Sıra</th>
                                <th width="25%">Danışman</th>
                                <th width="15%">Toplam Ciro</th>
                                <th width="15%">Kazanç</th>
                                <th width="10%">Kapatma</th>
                                <th width="10%">Müşteri D.</th>
                                <th width="10%">Portföy D.</th>
                                <th width="10%">Referans</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach($advisor_summary as $advisor_id => $data): 
                                $row_class = '';
                                $badge = '';
                                
                                if($rank == 1) {
                                    $row_class = 'top-performer';
                                    $badge = '<span class="performance-badge badge-gold">🥇</span>';
                                } elseif($rank == 2) {
                                    $badge = '<span class="performance-badge badge-silver">🥈</span>';
                                } elseif($rank == 3) {
                                    $badge = '<span class="performance-badge badge-bronze">🥉</span>';
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo $rank; ?>. <?php echo $badge; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($data['name']); ?></strong>
                                </td>
                                <td class="amount-highlight">
                                    <?php echo number_format($data['total_turnover'], 2, ',', '.'); ?> TL
                                </td>
                                <td>
                                    <?php echo number_format($data['total_revenue'], 2, ',', '.'); ?> TL
                                </td>
                                <td><?php echo $data['closing_count']; ?></td>
                                <td><?php echo $data['as_customer']; ?></td>
                                <td><?php echo $data['as_portfolio']; ?></td>
                                <td><?php echo $data['as_referral']; ?></td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                            
                            <?php if(empty($advisor_summary)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    Bu dönemde kapatma kaydı bulunmuyor.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Detaylı Kapatma Listesi -->
                <div class="details-section">
                    <h3>📋 Kapatma Detayları</h3>
                    
                    <div class="closing-list">
                        <?php foreach($report_data as $closing): ?>
                        <div class="closing-item">
                            <div class="closing-date">
                                📅 <?php echo date('d.m.Y', strtotime($closing['closing_date'])); ?> - 
                                <?php echo htmlspecialchars($closing['property_title']); ?>
                            </div>
                            <div class="closing-details">
                                <strong>Tip:</strong> <?php echo ucfirst($closing['closing_type']); ?> | 
                                <strong>Toplam:</strong> <span class="amount-highlight"><?php echo number_format($closing['total_amount'], 2, ',', '.'); ?> TL</span> | 
                                <strong>Ofis:</strong> <?php echo number_format($closing['office_share'], 2, ',', '.'); ?> TL<br>
                                
                                <?php if($closing['customer_advisor_name']): ?>
                                    <strong>Müşteri D.:</strong> <?php echo htmlspecialchars($closing['customer_advisor_name']); ?> 
                                    (<?php echo number_format($closing['customer_advisor_share'], 2, ',', '.'); ?> TL)
                                <?php endif; ?>
                                
                                <?php if($closing['portfolio_advisor_name']): ?>
                                    | <strong>Portföy D.:</strong> <?php echo htmlspecialchars($closing['portfolio_advisor_name']); ?> 
                                    (<?php echo number_format($closing['portfolio_advisor_share'], 2, ',', '.'); ?> TL)
                                <?php endif; ?>
                                
                                <?php if($closing['referral_advisor_name']): ?>
                                    | <strong>Referans:</strong> <?php echo htmlspecialchars($closing['referral_advisor_name']); ?> 
                                    (<?php echo number_format($closing['referral_advisor_share'], 2, ',', '.'); ?> TL)
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($report_data)): ?>
                        <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                            Bu kriterlere uygun kapatma bulunamadı.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>