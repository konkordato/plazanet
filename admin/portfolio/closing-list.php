<?php
session_start();

// Sadece admin erişebilir
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$user_name = $_SESSION['user_fullname'];

// Mesajları al
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Filtreleme
$filter_month = $_GET['month'] ?? date('n');
$filter_year = $_GET['year'] ?? date('Y');
$filter_advisor = $_GET['advisor'] ?? '';

// Tüm kapatmaları çek
$sql = "
    SELECT 
        pc.*,
        u1.full_name as customer_advisor_name,
        u2.full_name as portfolio_advisor_name,
        u3.full_name as referral_advisor_name,
        u4.full_name as created_by_name,
        p.baslik as property_name,
        p.durum as property_status
    FROM portfolio_closings pc
    LEFT JOIN users u1 ON pc.customer_advisor_id = u1.id
    LEFT JOIN users u2 ON pc.portfolio_advisor_id = u2.id
    LEFT JOIN users u3 ON pc.referral_advisor_id = u3.id
    LEFT JOIN users u4 ON pc.created_by = u4.id
    LEFT JOIN properties p ON pc.property_id = p.id
    WHERE 1=1
";

$params = [];

if($filter_month && $filter_year) {
    $sql .= " AND MONTH(pc.closing_date) = :month AND YEAR(pc.closing_date) = :year";
    $params[':month'] = $filter_month;
    $params[':year'] = $filter_year;
}

if($filter_advisor) {
    $sql .= " AND (pc.customer_advisor_id = :adv OR pc.portfolio_advisor_id = :adv2 OR pc.referral_advisor_id = :adv3)";
    $params[':adv'] = $filter_advisor;
    $params[':adv2'] = $filter_advisor;
    $params[':adv3'] = $filter_advisor;
}

$sql .= " ORDER BY pc.closing_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$closings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Danışmanları çek
$advisors = $db->query("SELECT id, full_name FROM users WHERE role = 'user' AND status = 'active' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Tüm Kapatmalar - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .closings-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .closings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .closings-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .closings-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .closings-table tr:hover {
            background: #f8f9fa;
        }
        
        .amount-cell {
            font-weight: bold;
            color: #27ae60;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-view {
            background: #95a5a6;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-changed {
            background: #ffc107;
            color: #000;
        }
        
        .status-manual {
            background: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
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
                <li><a href="closing-list.php" class="active">📋 Kapatma Listesi</a></li>
                <li><a href="reports.php">📊 Satış Raporları</a></li>
                <li><a href="commission-settings.php">⚙️ Prim Ayarları</a></li>
                <li><a href="closed-properties.php">🔒 Kapatılan İlanlar</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Tüm Kapatmalar</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Filtreler -->
                <div class="filter-section">
                    <h3>🔍 Filtrele</h3>
                    <form method="GET">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Yıl</label>
                                <select name="year" onchange="this.form.submit()">
                                    <?php for($y = 2024; $y <= date('Y'); $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Ay</label>
                                <select name="month" onchange="this.form.submit()">
                                    <option value="">Tümü</option>
                                    <?php foreach($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php echo $filter_month == $num ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Danışman</label>
                                <select name="advisor" onchange="this.form.submit()">
                                    <option value="">Tümü</option>
                                    <?php foreach($advisors as $advisor): ?>
                                        <option value="<?php echo $advisor['id']; ?>" <?php echo $filter_advisor == $advisor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($advisor['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Özet Kartlar -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-value"><?php echo count($closings); ?></div>
                        <div class="summary-label">Toplam Kapatma</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-value">
                            <?php echo number_format(array_sum(array_column($closings, 'total_amount')), 0, ',', '.'); ?> TL
                        </div>
                        <div class="summary-label">Toplam Ciro</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-value">
                            <?php echo number_format(array_sum(array_column($closings, 'office_share')), 0, ',', '.'); ?> TL
                        </div>
                        <div class="summary-label">Ofis Payı</div>
                    </div>
                </div>

                <!-- Kapatma Listesi -->
                <div class="closings-container">
                    <h3>📋 Kapatma Kayıtları</h3>
                    
                    <table class="closings-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="10%">Tarih</th>
                                <th width="20%">Gayrimenkul</th>
                                <th width="8%">Tip</th>
                                <th width="10%">Toplam</th>
                                <th width="10%">Ofis</th>
                                <th width="15%">Danışmanlar</th>
                                <th width="8%">Durum</th>
                                <th width="8%">Kaydeden</th>
                                <th width="6%">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($closings as $closing): ?>
                            <tr>
                                <td>#<?php echo $closing['id']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($closing['closing_date'])); ?></td>
                                <td><?php echo htmlspecialchars($closing['property_title']); ?></td>
                                <td>
                                    <span class="badge <?php echo $closing['closing_type'] == 'kiralik' ? 'badge-info' : 'badge-success'; ?>">
                                        <?php echo ucfirst($closing['closing_type']); ?>
                                    </span>
                                </td>
                                <td class="amount-cell">
                                    <?php echo number_format($closing['total_amount'], 0, ',', '.'); ?> TL
                                </td>
                                <td><?php echo number_format($closing['office_share'], 0, ',', '.'); ?> TL</td>
                                <td style="font-size: 12px;">
                                    <?php 
                                    $advisors_list = [];
                                    if($closing['customer_advisor_name']) {
                                        $advisors_list[] = "M: " . $closing['customer_advisor_name'];
                                    }
                                    if($closing['portfolio_advisor_name']) {
                                        $advisors_list[] = "P: " . $closing['portfolio_advisor_name'];
                                    }
                                    if($closing['referral_advisor_name']) {
                                        $advisors_list[] = "R: " . $closing['referral_advisor_name'];
                                    }
                                    echo implode('<br>', $advisors_list);
                                    ?>
                                </td>
                                <td>
                                    <?php if($closing['property_status_changed']): ?>
                                        <span class="status-badge status-changed">İlan Kapatıldı</span>
                                    <?php else: ?>
                                        <span class="status-badge status-manual">Manuel</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($closing['created_by_name']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" action="delete-closing.php" style="display: inline;" 
                                              onsubmit="return confirm('Bu kapatma kaydını silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="closing_id" value="<?php echo $closing['id']; ?>">
                                            <button type="submit" name="delete_closing" class="btn-delete">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($closings)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px;">
                                    Bu kriterlere uygun kapatma kaydı bulunamadı.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>