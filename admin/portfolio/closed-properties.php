<<<<<<< HEAD
<?php
session_start();

// Admin kontrolü - SADECE ADMİN ERİŞEBİLİR
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$user_name = $_SESSION['user_fullname'];
$success_msg = '';
$error_msg = '';

// İlanı tekrar aktif etme işlemi
if(isset($_POST['reactivate_property'])) {
    try {
        $property_id = $_POST['property_id'];
        
        // İlanı aktif yap ve kapatma bilgilerini temizle
        $stmt = $db->prepare("
            UPDATE properties 
            SET durum = 'aktif',
                closed_by = NULL,
                closed_at = NULL,
                closing_id = NULL
            WHERE id = :id
        ");
        $stmt->execute([':id' => $property_id]);
        
        // İlgili portfolio_closing kaydını güncelle
        $update_closing = $db->prepare("
            UPDATE portfolio_closings 
            SET property_status_changed = FALSE 
            WHERE property_id = :property_id
        ");
        $update_closing->execute([':property_id' => $property_id]);
        
        $success_msg = "İlan başarıyla aktif edildi!";
        
    } catch(Exception $e) {
        $error_msg = "Hata: " . $e->getMessage();
    }
}

// Filtreler
$filter_type = $_GET['filter_type'] ?? '';
$filter_advisor = $_GET['filter_advisor'] ?? '';
$filter_date_start = $_GET['filter_date_start'] ?? '';
$filter_date_end = $_GET['filter_date_end'] ?? '';

// Kapatılan ilanları çek
$sql = "
    SELECT 
        p.*,
        pc.closing_date,
        pc.total_amount,
        pc.closing_type as pc_closing_type,
        pc.office_share,
        pc.customer_advisor_share,
        pc.portfolio_advisor_share,
        pc.referral_advisor_share,
        u1.full_name as closed_by_name,
        u2.full_name as property_owner,
        pi.image_path,
        ca.full_name as customer_advisor_name,
        pa.full_name as portfolio_advisor_name,
        ra.full_name as referral_advisor_name
    FROM properties p
    LEFT JOIN portfolio_closings pc ON p.closing_id = pc.id
    LEFT JOIN users u1 ON p.closed_by = u1.id
    LEFT JOIN users u2 ON p.user_id = u2.id
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
    LEFT JOIN users ca ON pc.customer_advisor_id = ca.id
    LEFT JOIN users pa ON pc.portfolio_advisor_id = pa.id
    LEFT JOIN users ra ON pc.referral_advisor_id = ra.id
    WHERE p.durum IN ('satilik_kapandi', 'kiralik_kapandi')
";

$params = [];

// Filtreleri uygula
if($filter_type) {
    $sql .= " AND p.durum = :filter_type";
    $params[':filter_type'] = $filter_type;
}

if($filter_advisor) {
    $sql .= " AND (pc.customer_advisor_id = :advisor OR pc.portfolio_advisor_id = :advisor2 OR pc.referral_advisor_id = :advisor3)";
    $params[':advisor'] = $filter_advisor;
    $params[':advisor2'] = $filter_advisor;
    $params[':advisor3'] = $filter_advisor;
}

if($filter_date_start) {
    $sql .= " AND pc.closing_date >= :date_start";
    $params[':date_start'] = $filter_date_start;
}

if($filter_date_end) {
    $sql .= " AND pc.closing_date <= :date_end";
    $params[':date_end'] = $filter_date_end;
}

$sql .= " ORDER BY p.closed_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$closed_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN durum = 'satilik_kapandi' THEN 1 ELSE 0 END) as satilik,
        SUM(CASE WHEN durum = 'kiralik_kapandi' THEN 1 ELSE 0 END) as kiralik
    FROM properties 
    WHERE durum IN ('satilik_kapandi', 'kiralik_kapandi')
")->fetch(PDO::FETCH_ASSOC);

// Danışmanları çek (filtre için)
$advisors = $db->query("
    SELECT DISTINCT u.id, u.full_name 
    FROM users u
    JOIN portfolio_closings pc ON (
        u.id = pc.customer_advisor_id OR 
        u.id = pc.portfolio_advisor_id OR 
        u.id = pc.referral_advisor_id
    )
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kapatılan İlanlar - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .stats-container {
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
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .properties-grid {
            display: grid;
            gap: 20px;
        }
        
        .property-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            transition: transform 0.3s;
        }
        
        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .property-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .no-image {
            width: 200px;
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 48px;
        }
        
        .property-details {
            flex: 1;
            padding: 20px;
        }
        
        .property-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .property-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            font-size: 14px;
            color: #666;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .closing-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .advisor-list {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .amount-display {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
            margin: 10px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-reactivate {
            background: #27ae60;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-reactivate:hover {
            background: #229954;
        }
        
        .btn-details {
            background: #3498db;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-details:hover {
            background: #2980b9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-satilik {
            background: #e74c3c;
            color: white;
        }
        
        .badge-kiralik {
            background: #f39c12;
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
        
        .btn-filter {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-reset {
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .empty-text {
            color: #7f8c8d;
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
                <li><a href="reports.php">📊 Satış Raporları</a></li>
                <li><a href="commission-settings.php">⚙️ Prim Ayarları</a></li>
                <li><a href="closed-properties.php" class="active">🔒 Kapatılan İlanlar</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Kapatılan İlanlar Yönetimi</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <?php if($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <?php if($error_msg): ?>
                    <div class="alert alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- İstatistikler -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="stat-label">Toplam Kapatılan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['satilik'] ?? 0; ?></div>
                        <div class="stat-label">Satılık Kapatılan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['kiralik'] ?? 0; ?></div>
                        <div class="stat-label">Kiralık Kapatılan</div>
                    </div>
                </div>

                <!-- Filtreler -->
                <div class="filter-section">
                    <h3>🔍 Filtrele</h3>
                    <form method="GET" action="">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Kapatma Tipi</label>
                                <select name="filter_type">
                                    <option value="">Tümü</option>
                                    <option value="satilik_kapandi" <?php echo $filter_type == 'satilik_kapandi' ? 'selected' : ''; ?>>
                                        Satılık Kapatılan
                                    </option>
                                    <option value="kiralik_kapandi" <?php echo $filter_type == 'kiralik_kapandi' ? 'selected' : ''; ?>>
                                        Kiralık Kapatılan
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Danışman</label>
                                <select name="filter_advisor">
                                    <option value="">Tümü</option>
                                    <?php foreach($advisors as $advisor): ?>
                                        <option value="<?php echo $advisor['id']; ?>" 
                                                <?php echo $filter_advisor == $advisor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($advisor['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Başlangıç Tarihi</label>
                                <input type="date" name="filter_date_start" value="<?php echo $filter_date_start; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Bitiş Tarihi</label>
                                <input type="date" name="filter_date_end" value="<?php echo $filter_date_end; ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn-filter">Filtrele</button>
                                <a href="closed-properties.php" class="btn-reset">Temizle</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- İlan Listesi -->
                <div class="properties-grid">
                    <?php if(empty($closed_properties)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div class="empty-title">Kapatılan İlan Bulunamadı</div>
                            <div class="empty-text">Henüz kapatılmış bir ilan bulunmuyor veya filtrenize uygun sonuç yok.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach($closed_properties as $property): ?>
                            <div class="property-card">
                                <?php if($property['image_path']): ?>
                                    <img src="../../<?php echo $property['image_path']; ?>" class="property-image" alt="İlan">
                                <?php else: ?>
                                    <div class="no-image">🏢</div>
                                <?php endif; ?>
                                
                                <div class="property-details">
                                    <div class="property-title">
                                        <?php echo htmlspecialchars($property['baslik']); ?>
                                        <span class="status-badge <?php echo $property['durum'] == 'satilik_kapandi' ? 'badge-satilik' : 'badge-kiralik'; ?>">
                                            <?php echo $property['durum'] == 'satilik_kapandi' ? 'SATILDI' : 'KİRALANDI'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="property-info">
                                        <div class="info-item">
                                            <span class="info-label">İlan Sahibi:</span> 
                                            <?php echo htmlspecialchars($property['property_owner']); ?>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Konum:</span> 
                                            <?php echo htmlspecialchars($property['ilce'] . ', ' . $property['sehir']); ?>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Kapatma Tarihi:</span> 
                                            <?php echo $property['closing_date'] ? date('d.m.Y', strtotime($property['closing_date'])) : '-'; ?>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Kapatan:</span> 
                                            <?php echo htmlspecialchars($property['closed_by_name']); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if($property['total_amount']): ?>
                                        <div class="closing-info">
                                            <div class="amount-display">
                                                Hizmet Bedeli: <?php echo number_format($property['total_amount'], 2, ',', '.'); ?> TL
                                            </div>
                                            
                                            <div class="advisor-list">
                                                <strong>Paylaşım:</strong><br>
                                                📍 Ofis: <?php echo number_format($property['office_share'], 2, ',', '.'); ?> TL<br>
                                                
                                                <?php if($property['customer_advisor_name']): ?>
                                                    👤 Müşteri D.: <?php echo htmlspecialchars($property['customer_advisor_name']); ?> 
                                                    (<?php echo number_format($property['customer_advisor_share'], 2, ',', '.'); ?> TL)<br>
                                                <?php endif; ?>
                                                
                                                <?php if($property['portfolio_advisor_name']): ?>
                                                    📂 Portföy D.: <?php echo htmlspecialchars($property['portfolio_advisor_name']); ?> 
                                                    (<?php echo number_format($property['portfolio_advisor_share'], 2, ',', '.'); ?> TL)<br>
                                                <?php endif; ?>
                                                
                                                <?php if($property['referral_advisor_name']): ?>
                                                    🔗 Referans D.: <?php echo htmlspecialchars($property['referral_advisor_name']); ?> 
                                                    (<?php echo number_format($property['referral_advisor_share'], 2, ',', '.'); ?> TL)
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                            <button type="submit" name="reactivate_property" class="btn-reactivate" 
                                                    onclick="return confirm('Bu ilanı tekrar aktif etmek istediğinize emin misiniz?');">
                                                ✅ Tekrar Aktif Et
                                            </button>
                                        </form>
                                        
                                        <a href="../properties/edit.php?id=<?php echo $property['id']; ?>" class="btn-details">
                                            ✏️ Düzenle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
=======
<?php
session_start();

// Admin kontrolü - SADECE ADMİN ERİŞEBİLİR
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$user_name = $_SESSION['user_fullname'];
$success_msg = '';
$error_msg = '';

// İlanı tekrar aktif etme işlemi
if(isset($_POST['reactivate_property'])) {
    try {
        $property_id = $_POST['property_id'];
        
        // İlanı aktif yap ve kapatma bilgilerini temizle
        $stmt = $db->prepare("
            UPDATE properties 
            SET durum = 'aktif',
                closed_by = NULL,
                closed_at = NULL,
                closing_id = NULL
            WHERE id = :id
        ");
        $stmt->execute([':id' => $property_id]);
        
        // İlgili portfolio_closing kaydını güncelle
        $update_closing = $db->prepare("
            UPDATE portfolio_closings 
            SET property_status_changed = FALSE 
            WHERE property_id = :property_id
        ");
        $update_closing->execute([':property_id' => $property_id]);
        
        $success_msg = "İlan başarıyla aktif edildi!";
        
    } catch(Exception $e) {
        $error_msg = "Hata: " . $e->getMessage();
    }
}

// Filtreler
$filter_type = $_GET['filter_type'] ?? '';
$filter_advisor = $_GET['filter_advisor'] ?? '';
$filter_date_start = $_GET['filter_date_start'] ?? '';
$filter_date_end = $_GET['filter_date_end'] ?? '';

// Kapatılan ilanları çek
$sql = "
    SELECT 
        p.*,
        pc.closing_date,
        pc.total_amount,
        pc.closing_type as pc_closing_type,
        pc.office_share,
        pc.customer_advisor_share,
        pc.portfolio_advisor_share,
        pc.referral_advisor_share,
        u1.full_name as closed_by_name,
        u2.full_name as property_owner,
        pi.image_path,
        ca.full_name as customer_advisor_name,
        pa.full_name as portfolio_advisor_name,
        ra.full_name as referral_advisor_name
    FROM properties p
    LEFT JOIN portfolio_closings pc ON p.closing_id = pc.id
    LEFT JOIN users u1 ON p.closed_by = u1.id
    LEFT JOIN users u2 ON p.user_id = u2.id
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
    LEFT JOIN users ca ON pc.customer_advisor_id = ca.id
    LEFT JOIN users pa ON pc.portfolio_advisor_id = pa.id
    LEFT JOIN users ra ON pc.referral_advisor_id = ra.id
    WHERE p.durum IN ('satilik_kapandi', 'kiralik_kapandi')
";

$params = [];

// Filtreleri uygula
if($filter_type) {
    $sql .= " AND p.durum = :filter_type";
    $params[':filter_type'] = $filter_type;
}

if($filter_advisor) {
    $sql .= " AND (pc.customer_advisor_id = :advisor OR pc.portfolio_advisor_id = :advisor2 OR pc.referral_advisor_id = :advisor3)";
    $params[':advisor'] = $filter_advisor;
    $params[':advisor2'] = $filter_advisor;
    $params[':advisor3'] = $filter_advisor;
}

if($filter_date_start) {
    $sql .= " AND pc.closing_date >= :date_start";
    $params[':date_start'] = $filter_date_start;
}

if($filter_date_end) {
    $sql .= " AND pc.closing_date <= :date_end";
    $params[':date_end'] = $filter_date_end;
}

$sql .= " ORDER BY p.closed_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$closed_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN durum = 'satilik_kapandi' THEN 1 ELSE 0 END) as satilik,
        SUM(CASE WHEN durum = 'kiralik_kapandi' THEN 1 ELSE 0 END) as kiralik
    FROM properties 
    WHERE durum IN ('satilik_kapandi', 'kiralik_kapandi')
")->fetch(PDO::FETCH_ASSOC);

// Danışmanları çek (filtre için)
$advisors = $db->query("
    SELECT DISTINCT u.id, u.full_name 
    FROM users u
    JOIN portfolio_closings pc ON (
        u.id = pc.customer_advisor_id OR 
        u.id = pc.portfolio_advisor_id OR 
        u.id = pc.referral_advisor_id
    )
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kapatılan İlanlar - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .stats-container {
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
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .properties-grid {
            display: grid;
            gap: 20px;
        }
        
        .property-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            transition: transform 0.3s;
        }
        
        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .property-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .no-image {
            width: 200px;
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 48px;
        }
        
        .property-details {
            flex: 1;
            padding: 20px;
        }
        
        .property-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .property-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            font-size: 14px;
            color: #666;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .closing-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .advisor-list {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .amount-display {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
            margin: 10px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-reactivate {
            background: #27ae60;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-reactivate:hover {
            background: #229954;
        }
        
        .btn-details {
            background: #3498db;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-details:hover {
            background: #2980b9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-satilik {
            background: #e74c3c;
            color: white;
        }
        
        .badge-kiralik {
            background: #f39c12;
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
        
        .btn-filter {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-reset {
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .empty-text {
            color: #7f8c8d;
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
                <li><a href="reports.php">📊 Satış Raporları</a></li>
                <li><a href="commission-settings.php">⚙️ Prim Ayarları</a></li>
                <li><a href="closed-properties.php" class="active">🔒 Kapatılan İlanlar</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Kapatılan İlanlar Yönetimi</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <?php if($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <?php if($error_msg): ?>
                    <div class="alert alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- İstatistikler -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="stat-label">Toplam Kapatılan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['satilik'] ?? 0; ?></div>
                        <div class="stat-label">Satılık Kapatılan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['kiralik'] ?? 0; ?></div>
                        <div class="stat-label">Kiralık Kapatılan</div>
                    </div>
                </div>

                <!-- Filtreler -->
                <div class="filter-section">
                    <h3>🔍 Filtrele</h3>
                    <form method="GET" action="">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label>Kapatma Tipi</label>
                                <select name="filter_type">
                                    <option value="">Tümü</option>
                                    <option value="satilik_kapandi" <?php echo $filter_type == 'satilik_kapandi' ? 'selected' : ''; ?>>
                                        Satılık Kapatılan
                                    </option>
                                    <option value="kiralik_kapandi" <?php echo $filter_type == 'kiralik_kapandi' ? 'selected' : ''; ?>>
                                        Kiralık Kapatılan
                                    </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Danışman</label>
                                <select name="filter_advisor">
                                    <option value="">Tümü</option>
                                    <?php foreach($advisors as $advisor): ?>
                                        <option value="<?php echo $advisor['id']; ?>" 
                                                <?php echo $filter_advisor == $advisor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($advisor['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Başlangıç Tarihi</label>
                                <input type="date" name="filter_date_start" value="<?php echo $filter_date_start; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Bitiş Tarihi</label>
                                <input type="date" name="filter_date_end" value="<?php echo $filter_date_end; ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn-filter">Filtrele</button>
                                <a href="closed-properties.php" class="btn-reset">Temizle</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- İlan Listesi -->
                <div class="properties-grid">
                    <?php if(empty($closed_properties)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div class="empty-title">Kapatılan İlan Bulunamadı</div>
                            <div class="empty-text">Henüz kapatılmış bir ilan bulunmuyor veya filtrenize uygun sonuç yok.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach($closed_properties as $property): ?>
                            <div class="property-card">
                                <?php if($property['image_path']): ?>
                                    <img src="../../<?php echo $property['image_path']; ?>" class="property-image" alt="İlan">
                                <?php else: ?>
                                    <div class="no-image">🏢</div>
                                <?php endif; ?>
                                
                                <div class="property-details">
                                    <div class="property-title">
                                        <?php echo htmlspecialchars($property['baslik']); ?>
                                        <span class="status-badge <?php echo $property['durum'] == 'satilik_kapandi' ? 'badge-satilik' : 'badge-kiralik'; ?>">
                                            <?php echo $property['durum'] == 'satilik_kapandi' ? 'SATILDI' : 'KİRALANDI'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="property-info">
                                        <div class="info-item">
                                            <span class="info-label">İlan Sahibi:</span> 
                                            <?php echo htmlspecialchars($property['property_owner']); ?>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Konum:</span> 
                                            <?php echo htmlspecialchars($property['ilce'] . ', ' . $property['sehir']); ?>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Kapatma Tarihi:</span> 
                                            <?php echo $property['closing_date'] ? date('d.m.Y', strtotime($property['closing_date'])) : '-'; ?>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Kapatan:</span> 
                                            <?php echo htmlspecialchars($property['closed_by_name']); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if($property['total_amount']): ?>
                                        <div class="closing-info">
                                            <div class="amount-display">
                                                Hizmet Bedeli: <?php echo number_format($property['total_amount'], 2, ',', '.'); ?> TL
                                            </div>
                                            
                                            <div class="advisor-list">
                                                <strong>Paylaşım:</strong><br>
                                                📍 Ofis: <?php echo number_format($property['office_share'], 2, ',', '.'); ?> TL<br>
                                                
                                                <?php if($property['customer_advisor_name']): ?>
                                                    👤 Müşteri D.: <?php echo htmlspecialchars($property['customer_advisor_name']); ?> 
                                                    (<?php echo number_format($property['customer_advisor_share'], 2, ',', '.'); ?> TL)<br>
                                                <?php endif; ?>
                                                
                                                <?php if($property['portfolio_advisor_name']): ?>
                                                    📂 Portföy D.: <?php echo htmlspecialchars($property['portfolio_advisor_name']); ?> 
                                                    (<?php echo number_format($property['portfolio_advisor_share'], 2, ',', '.'); ?> TL)<br>
                                                <?php endif; ?>
                                                
                                                <?php if($property['referral_advisor_name']): ?>
                                                    🔗 Referans D.: <?php echo htmlspecialchars($property['referral_advisor_name']); ?> 
                                                    (<?php echo number_format($property['referral_advisor_share'], 2, ',', '.'); ?> TL)
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                            <button type="submit" name="reactivate_property" class="btn-reactivate" 
                                                    onclick="return confirm('Bu ilanı tekrar aktif etmek istediğinize emin misiniz?');">
                                                ✅ Tekrar Aktif Et
                                            </button>
                                        </form>
                                        
                                        <a href="../properties/edit.php?id=<?php echo $property['id']; ?>" class="btn-details">
                                            ✏️ Düzenle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>