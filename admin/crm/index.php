<?php
<<<<<<< HEAD

session_start();

// Admin/Kullanıcı girişi kontrolü

if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {

    header("Location: ../index.php");

    exit();
}



require_once '../../config/database.php';



// Kullanıcı bilgilerini al

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;

$current_user_role = $_SESSION['user_role'] ?? 'user';

$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';



// İstatistikler

// Admin tüm müşterileri görür, kullanıcılar sadece kendi eklediklerini

$where_clause = ($current_user_role == 'admin') ? "" : " WHERE ekleyen_user_id = :user_id";



// Alıcı müşteri sayısı

$sql = "SELECT COUNT(*) as total FROM crm_alici_musteriler" . $where_clause;

$stmt = $db->prepare($sql);

if ($current_user_role != 'admin') $stmt->execute([':user_id' => $current_user_id]);

else $stmt->execute();

$alici_sayisi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



// Satıcı müşteri sayısı

$sql = "SELECT COUNT(*) as total FROM crm_satici_musteriler" . $where_clause;

$stmt = $db->prepare($sql);

if ($current_user_role != 'admin') $stmt->execute([':user_id' => $current_user_id]);

else $stmt->execute();

$satici_sayisi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



// Bugünkü görüşmeler

$sql = "SELECT COUNT(*) as total FROM crm_gorusme_notlari WHERE DATE(gorusme_tarihi) = CURDATE()";

if ($current_user_role != 'admin') $sql .= " AND gorusen_user_id = :user_id";

$stmt = $db->prepare($sql);

if ($current_user_role != 'admin') $stmt->execute([':user_id' => $current_user_id]);

else $stmt->execute();

$bugun_gorusme = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



// Son eklenen 5 müşteri

$sql = "SELECT 'alici' as tip, id, ad, soyad, telefon, ekleme_tarihi FROM crm_alici_musteriler";

if ($current_user_role != 'admin') $sql .= " WHERE ekleyen_user_id = :user_id";

$sql .= " UNION ALL SELECT 'satici' as tip, id, ad, soyad, telefon, ekleme_tarihi FROM crm_satici_musteriler";

if ($current_user_role != 'admin') $sql .= " WHERE ekleyen_user_id = :user_id";

$sql .= " ORDER BY ekleme_tarihi DESC LIMIT 5";



$stmt = $db->prepare($sql);

if ($current_user_role != 'admin') {

    $stmt->execute([':user_id' => $current_user_id]);
} else {

    $stmt->execute();
}

$son_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CRM Sistemi - Plazanet Emlak</title>

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



        .crm-dashboard {
        /* Navbar stilleri */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-left h3 {
            margin: 0;
            color: #2c3e50;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #c0392b;
        }

            padding: 20px;

        }

        .stats-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;

            margin-bottom: 30px;

        }

        .stat-card {

            background: white;

            padding: 20px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

            text-align: center;

            transition: transform 0.3s;

        }

        .stat-card:hover {

            transform: translateY(-5px);

        }

        .stat-card .icon {

            font-size: 48px;

            margin-bottom: 10px;

        }

        .stat-card .number {

            font-size: 36px;

            font-weight: bold;

            color: #2c3e50;

        }

        .stat-card .label {

            color: #7f8c8d;

            margin-top: 5px;

        }

        .quick-actions {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

            gap: 15px;

            margin: 30px 0;

        }

        .action-btn {

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            color: white;

            padding: 15px;

            border-radius: 8px;

            text-decoration: none;

            text-align: center;

            font-weight: 500;

            transition: all 0.3s;

        }

        .action-btn:hover {

            transform: scale(1.05);

            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);

        }

        .recent-customers {

            background: white;

            padding: 20px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

        }

        .customer-list {

            margin-top: 15px;

        }

        .customer-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 10px;

            border-bottom: 1px solid #ecf0f1;

        }

        .customer-item:hover {

            background: #f8f9fa;

        }

        .customer-badge {

            display: inline-block;

            padding: 3px 8px;

            border-radius: 4px;

            font-size: 12px;

            font-weight: 500;

        }

        .badge-alici {

            background: #e8f5e9;

            color: #2e7d32;

        }

        .badge-satici {

            background: #fff3e0;

            color: #e65100;

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
                    <a href="../portfolio/closing.php">
                        <span class="icon">💰</span>
                        <span>Portföy Kapatma</span>
                    </a>
                </li>
                <li>
                    <a href="../portfolio/my-reports.php">
                        <span class="icon">📊</span>
                        <span>Satış Raporlarım</span>
                    </a>
                </li>
                <li>

                <li>

                    <a href="index.php" class="active">

                        <span class="icon">📊</span>

                        <span>CRM Sistemi</span>

                    </a>

                </li>

            </ul>

        </nav>



        <div class="admin-content">
            <!-- ÜST NAVBAR EKLE -->
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>CRM Sistemi</h3>
                </div>
                <div class="navbar-right">
                    <span><?php echo htmlspecialchars($_SESSION['user_fullname'] ?? $_SESSION['admin_username'] ?? 'Kullanıcı'); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="crm-dashboard">

                <h1>📊 CRM Sistemi</h1>

                <p>Hoşgeldiniz, <?php echo $current_user_name; ?>!</p>



                <!-- İstatistikler -->

                <div class="stats-grid">

                    <div class="stat-card">

                        <div class="icon">👥</div>

                        <div class="number"><?php echo $alici_sayisi; ?></div>

                        <div class="label">Alıcı Müşteri</div>

                    </div>

                    <div class="stat-card">

                        <div class="icon">🏠</div>

                        <div class="number"><?php echo $satici_sayisi; ?></div>

                        <div class="label">Satıcı Müşteri</div>

                    </div>

                    <div class="stat-card">

                        <div class="icon">📞</div>

                        <div class="number"><?php echo $bugun_gorusme; ?></div>

                        <div class="label">Bugünkü Görüşme</div>

                    </div>

                    <div class="stat-card">

                        <div class="icon">📊</div>

                        <div class="number"><?php echo $alici_sayisi + $satici_sayisi; ?></div>

                        <div class="label">Toplam Müşteri</div>

                    </div>

                </div>



                <!-- Hızlı İşlemler -->

                <div class="quick-actions">

                    <a href="alici-ekle.php" class="action-btn">➕ Alıcı Müşteri Ekle</a>

                    <a href="satici-ekle.php" class="action-btn">➕ Satıcı Müşteri Ekle</a>

                    <a href="alici-liste.php" class="action-btn">📋 Alıcı Listesi</a>

                    <a href="satici-liste.php" class="action-btn">📋 Satıcı Listesi</a>

                    <a href="raporlar.php" class="action-btn">📊 Raporlar</a>

                    <a href="takvim.php" class="action-btn">📅 Takvim</a>

                </div>



                <!-- Son Eklenen Müşteriler -->

                <div class="recent-customers">

                    <h3>🕒 Son Eklenen Müşteriler</h3>

                    <div class="customer-list">

                        <?php foreach ($son_musteriler as $musteri): ?>

                            <div class="customer-item">

                                <div>

                                    <strong><?php echo $musteri['ad'] . ' ' . $musteri['soyad']; ?></strong>

                                    <span class="customer-badge <?php echo $musteri['tip'] == 'alici' ? 'badge-alici' : 'badge-satici'; ?>">

                                        <?php echo $musteri['tip'] == 'alici' ? 'Alıcı' : 'Satıcı'; ?>

                                    </span>

                                </div>

                                <div>

                                    📱 0<?php echo $musteri['telefon']; ?>

                                </div>

                                <div>

                                    <a href="musteri-detay.php?tip=<?php echo $musteri['tip']; ?>&id=<?php echo $musteri['id']; ?>">Detay</a>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

=======
session_start();
// Admin/Kullanıcı girişi kontrolü
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Kullanıcı bilgilerini al
$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';
$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';

// İstatistikler
// Admin tüm müşterileri görür, kullanıcılar sadece kendi eklediklerini
$where_clause = ($current_user_role == 'admin') ? "" : " WHERE ekleyen_user_id = :user_id";

// Alıcı müşteri sayısı
$sql = "SELECT COUNT(*) as total FROM crm_alici_musteriler" . $where_clause;
$stmt = $db->prepare($sql);
if($current_user_role != 'admin') $stmt->execute([':user_id' => $current_user_id]);
else $stmt->execute();
$alici_sayisi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Satıcı müşteri sayısı
$sql = "SELECT COUNT(*) as total FROM crm_satici_musteriler" . $where_clause;
$stmt = $db->prepare($sql);
if($current_user_role != 'admin') $stmt->execute([':user_id' => $current_user_id]);
else $stmt->execute();
$satici_sayisi = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Bugünkü görüşmeler
$sql = "SELECT COUNT(*) as total FROM crm_gorusme_notlari WHERE DATE(gorusme_tarihi) = CURDATE()";
if($current_user_role != 'admin') $sql .= " AND gorusen_user_id = :user_id";
$stmt = $db->prepare($sql);
if($current_user_role != 'admin') $stmt->execute([':user_id' => $current_user_id]);
else $stmt->execute();
$bugun_gorusme = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Son eklenen 5 müşteri
$sql = "SELECT 'alici' as tip, id, ad, soyad, telefon, ekleme_tarihi FROM crm_alici_musteriler";
if($current_user_role != 'admin') $sql .= " WHERE ekleyen_user_id = :user_id";
$sql .= " UNION ALL SELECT 'satici' as tip, id, ad, soyad, telefon, ekleme_tarihi FROM crm_satici_musteriler";
if($current_user_role != 'admin') $sql .= " WHERE ekleyen_user_id = :user_id";
$sql .= " ORDER BY ekleme_tarihi DESC LIMIT 5";

$stmt = $db->prepare($sql);
if($current_user_role != 'admin') {
    $stmt->execute([':user_id' => $current_user_id]);
} else {
    $stmt->execute();
}
$son_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Sistemi - Plazanet Emlak</title>
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
        
        .crm-dashboard {
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-card .label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s;
        }
        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .recent-customers {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .customer-list {
            margin-top: 15px;
        }
        .customer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        .customer-item:hover {
            background: #f8f9fa;
        }
        .customer-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-alici {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .badge-satici {
            background: #fff3e0;
            color: #e65100;
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
            <div class="crm-dashboard">
                <h1>📊 CRM Sistemi</h1>
                <p>Hoşgeldiniz, <?php echo $current_user_name; ?>!</p>
                
                <!-- İstatistikler -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">👥</div>
                        <div class="number"><?php echo $alici_sayisi; ?></div>
                        <div class="label">Alıcı Müşteri</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">🏠</div>
                        <div class="number"><?php echo $satici_sayisi; ?></div>
                        <div class="label">Satıcı Müşteri</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📞</div>
                        <div class="number"><?php echo $bugun_gorusme; ?></div>
                        <div class="label">Bugünkü Görüşme</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📊</div>
                        <div class="number"><?php echo $alici_sayisi + $satici_sayisi; ?></div>
                        <div class="label">Toplam Müşteri</div>
                    </div>
                </div>
                
                <!-- Hızlı İşlemler -->
                <div class="quick-actions">
                    <a href="alici-ekle.php" class="action-btn">➕ Alıcı Müşteri Ekle</a>
                    <a href="satici-ekle.php" class="action-btn">➕ Satıcı Müşteri Ekle</a>
                    <a href="alici-liste.php" class="action-btn">📋 Alıcı Listesi</a>
                    <a href="satici-liste.php" class="action-btn">📋 Satıcı Listesi</a>
                    <a href="raporlar.php" class="action-btn">📊 Raporlar</a>
                    <a href="takvim.php" class="action-btn">📅 Takvim</a>
                </div>
                
                <!-- Son Eklenen Müşteriler -->
                <div class="recent-customers">
                    <h3>🕒 Son Eklenen Müşteriler</h3>
                    <div class="customer-list">
                        <?php foreach($son_musteriler as $musteri): ?>
                        <div class="customer-item">
                            <div>
                                <strong><?php echo $musteri['ad'] . ' ' . $musteri['soyad']; ?></strong>
                                <span class="customer-badge <?php echo $musteri['tip'] == 'alici' ? 'badge-alici' : 'badge-satici'; ?>">
                                    <?php echo $musteri['tip'] == 'alici' ? 'Alıcı' : 'Satıcı'; ?>
                                </span>
                            </div>
                            <div>
                                📱 0<?php echo $musteri['telefon']; ?>
                            </div>
                            <div>
                                <a href="musteri-detay.php?tip=<?php echo $musteri['tip']; ?>&id=<?php echo $musteri['id']; ?>">Detay</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>