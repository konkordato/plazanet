<?php
// C:\xampp\htdocs\plazanet\admin\sms\settings.php
// SMS Sistem Ayarları Sayfası

// Session kontrolü - eğer başlatılmamışsa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../classes/NetGSM.php';

// Mevcut ayarları çek
$stmt = $db->query("SELECT * FROM sms_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Form gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['save_settings'])) {
        // Ayarları kaydet
        $api_key = trim($_POST['api_key']);
        $api_password = trim($_POST['api_password']);
        $sender_name = trim($_POST['sender_name']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $test_mode = isset($_POST['test_mode']) ? 1 : 0;
        
        $sql = "UPDATE sms_settings SET 
                api_key = :api_key,
                api_password = :api_password,
                sender_name = :sender_name,
                is_active = :is_active,
                test_mode = :test_mode
                WHERE id = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':api_key' => $api_key,
            ':api_password' => $api_password,
            ':sender_name' => $sender_name,
            ':is_active' => $is_active,
            ':test_mode' => $test_mode
        ]);
        
        $success_message = "Ayarlar başarıyla güncellendi!";
        
        // Ayarları yeniden çek
        $stmt = $db->query("SELECT * FROM sms_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Bakiye sorgula
    if(isset($_POST['check_balance'])) {
        try {
            $netgsm = new NetGSM($db);
            $balance = $netgsm->checkBalance();
            if($balance['status']) {
                $balance_message = "Kalan SMS Kredisi: " . $balance['balance'];
            } else {
                $balance_error = "Bakiye sorgulanamadı!";
            }
        } catch(Exception $e) {
            $balance_error = $e->getMessage();
        }
    }
    
    // Test SMS gönder
    if(isset($_POST['send_test'])) {
        $test_phone = $_POST['test_phone'];
        try {
            $netgsm = new NetGSM($db);
            $result = $netgsm->sendSMS($test_phone, "Plaza Emlak SMS Test Mesajı - " . date('d.m.Y H:i'));
            if($result['status']) {
                $test_success = "Test SMS başarıyla gönderildi!";
            } else {
                $test_error = $result['message'];
            }
        } catch(Exception $e) {
            $test_error = $e->getMessage();
        }
    }
}

// SMS istatistikleri
$today = date('Y-m-d');
$stats = [];

// Bugün gönderilen SMS
$stmt = $db->prepare("SELECT COUNT(*) as total FROM sms_logs WHERE DATE(created_at) = :today");
$stmt->execute([':today' => $today]);
$stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Bu ay gönderilen SMS
$stmt = $db->prepare("SELECT COUNT(*) as total FROM sms_logs WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stmt->execute();
$stats['month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Başarılı/Başarısız
$stmt = $db->query("SELECT status, COUNT(*) as total FROM sms_logs GROUP BY status");
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Ayarları - Plazanet Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .sms-settings {
            padding: 20px;
            background: #f5f5f5;
            min-height: 100vh;
            margin-left: 250px;
        }
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .card-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
        }
        .switch-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .switch {
            position: relative;
            width: 50px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #27ae60;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
                <li><a href="../crm/index.php">📊 CRM Sistemi</a></li>
                <li><a href="settings.php" class="active">📱 SMS Sistemi</a></li>
                <li><a href="logs.php">📋 SMS Logları</a></li>
                <li><a href="../logout.php">🚪 Çıkış</a></li>
            </ul>
        </nav>
        
        <div class="sms-settings">
            <h1>📱 SMS Sistem Ayarları</h1>
            
            <!-- Bildirimler -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($balance_message)): ?>
            <div class="alert alert-info"><?php echo $balance_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($balance_error)): ?>
            <div class="alert alert-error"><?php echo $balance_error; ?></div>
            <?php endif; ?>
            
            <!-- İstatistikler -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $stats['today']; ?></div>
                    <div class="stat-label">Bugün Gönderilen</div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stat-value"><?php echo $stats['month']; ?></div>
                    <div class="stat-label">Bu Ay Gönderilen</div>
                </div>
            </div>
            
            <!-- NETGSM Ayarları -->
            <div class="settings-card">
                <div class="card-header">
                    <h2 class="card-title">NETGSM API Ayarları</h2>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>API Kullanıcı Adı</label>
                            <input type="text" name="api_key" value="<?php echo htmlspecialchars($settings['api_key'] ?? ''); ?>" required>
                            <small>NETGSM panel kullanıcı adınız</small>
                        </div>
                        
                        <div class="form-group">
                            <label>API Şifre</label>
                            <input type="password" name="api_password" value="<?php echo htmlspecialchars($settings['api_password'] ?? ''); ?>" required>
                            <small>NETGSM panel şifreniz</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Gönderici Adı (Başlık)</label>
                        <input type="text" name="sender_name" value="<?php echo htmlspecialchars($settings['sender_name'] ?? 'PLAZAEMLAK'); ?>" maxlength="11">
                        <small>SMS'lerde görünecek başlık (Max 11 karakter)</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMS Sistemi</label>
                            <div class="switch-group">
                                <label class="switch">
                                    <input type="checkbox" name="is_active" <?php echo ($settings['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span><?php echo ($settings['is_active'] ?? 0) ? 'Aktif' : 'Pasif'; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Test Modu</label>
                            <div class="switch-group">
                                <label class="switch">
                                    <input type="checkbox" name="test_mode" <?php echo ($settings['test_mode'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span><?php echo ($settings['test_mode'] ?? 1) ? 'Açık (SMS gönderilmez)' : 'Kapalı'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" name="save_settings" class="btn btn-success">
                            💾 Ayarları Kaydet
                        </button>
                        <button type="submit" name="check_balance" class="btn btn-primary">
                            💰 Bakiye Sorgula
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Test SMS -->
            <div class="settings-card">
                <div class="card-header">
                    <h2 class="card-title">Test SMS Gönder</h2>
                </div>
                
                <?php if(isset($test_success)): ?>
                <div class="alert alert-success"><?php echo $test_success; ?></div>
                <?php endif; ?>
                
                <?php if(isset($test_error)): ?>
                <div class="alert alert-error"><?php echo $test_error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Test Telefon Numarası</label>
                        <input type="tel" name="test_phone" placeholder="05XX XXX XX XX" required>
                        <small>Test mesajı gönderilecek telefon numarası</small>
                    </div>
                    
                    <button type="submit" name="send_test" class="btn btn-warning">
                        📤 Test SMS Gönder
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>