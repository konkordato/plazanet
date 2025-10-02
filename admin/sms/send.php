<<<<<<< HEAD
<?php
// C:\xampp\htdocs\plazanet\admin\sms\send.php
// SMS Gönderme Sayfası

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../classes/NetGSM.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';
$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';

// CRM'den müşterileri çek
// Alıcı müşteriler
$alici_sql = "SELECT id, ad, soyad, telefon, sms_permission 
              FROM crm_alici_musteriler 
              WHERE durum = 'aktif' AND telefon IS NOT NULL AND telefon != ''";

if($current_user_role != 'admin') {
    $alici_sql .= " AND ekleyen_user_id = " . $current_user_id;
}
$alici_sql .= " ORDER BY ad, soyad";

$stmt = $db->query($alici_sql);
$alici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Satıcı müşteriler
$satici_sql = "SELECT id, ad, soyad, telefon, sms_permission 
               FROM crm_satici_musteriler 
               WHERE durum = 'aktif' AND telefon IS NOT NULL AND telefon != ''";

if($current_user_role != 'admin') {
    $satici_sql .= " AND ekleyen_user_id = " . $current_user_id;
}
$satici_sql .= " ORDER BY ad, soyad";

$stmt = $db->query($satici_sql);
$satici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Danışmanları çek (Admin için)
$danismanlar = [];
if($current_user_role == 'admin') {
    $stmt = $db->query("SELECT id, username, full_name, mobile, sms_permission 
                        FROM users 
                        WHERE status = 'active' 
                        ORDER BY full_name");
    $danismanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// SMS Şablonları
$stmt = $db->query("SELECT * FROM sms_templates WHERE is_active = 1 ORDER BY template_name");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildi mi?
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_sms'])) {
    $sms_type = $_POST['sms_type'];
    $message = trim($_POST['message']);
    $success_count = 0;
    $fail_count = 0;
    $results = [];
    
    try {
        $netgsm = new NetGSM($db);
        
        if($sms_type == 'single') {
            // Tekli gönderim
            $phone = $_POST['single_phone'];
            $result = $netgsm->sendSMS($phone, $message, 'manuel', $current_user_id, [
                'sender_name' => $current_user_name,
                'type' => 'manuel'
            ]);
            
            if($result['status']) {
                $success_message = "SMS başarıyla gönderildi!";
                $success_count = 1;
            } else {
                $error_message = "SMS gönderilemedi: " . $result['message'];
                $fail_count = 1;
            }
            
        } elseif($sms_type == 'group') {
            // Grup gönderimi
            $selected_phones = $_POST['phones'] ?? [];
            
            if(empty($selected_phones)) {
                $error_message = "Lütfen en az bir alıcı seçin!";
            } else {
                foreach($selected_phones as $phone_data) {
                    // Format: type_id (örn: alici_5, satici_3, danisman_2)
                    list($type, $id) = explode('_', $phone_data);
                    
                    // Telefon numarasını ve ismi bul
                    $phone = '';
                    $name = '';
                    
                    if($type == 'alici') {
                        foreach($alici_musteriler as $musteri) {
                            if($musteri['id'] == $id) {
                                $phone = $musteri['telefon'];
                                $name = $musteri['ad'] . ' ' . $musteri['soyad'];
                                break;
                            }
                        }
                        $recipient_type = 'alici';
                    } elseif($type == 'satici') {
                        foreach($satici_musteriler as $musteri) {
                            if($musteri['id'] == $id) {
                                $phone = $musteri['telefon'];
                                $name = $musteri['ad'] . ' ' . $musteri['soyad'];
                                break;
                            }
                        }
                        $recipient_type = 'satici';
                    } elseif($type == 'danisman') {
                        foreach($danismanlar as $danisman) {
                            if($danisman['id'] == $id) {
                                $phone = $danisman['mobile'];
                                $name = $danisman['full_name'] ?? $danisman['username'];
                                break;
                            }
                        }
                        $recipient_type = 'danisman';
                    }
                    
                    if($phone) {
                        $result = $netgsm->sendSMS($phone, $message, 'manuel', $current_user_id, [
                            'sender_name' => $current_user_name,
                            'type' => $recipient_type,
                            'recipient_id' => $id,
                            'recipient_name' => $name
                        ]);
                        
                        if($result['status']) {
                            $success_count++;
                        } else {
                            $fail_count++;
                        }
                        
                        $results[] = [
                            'name' => $name,
                            'phone' => $phone,
                            'status' => $result['status'],
                            'message' => $result['message']
                        ];
                    }
                }
                
                if($success_count > 0) {
                    $success_message = "{$success_count} SMS başarıyla gönderildi!";
                }
                if($fail_count > 0) {
                    $error_message = "{$fail_count} SMS gönderilemedi!";
                }
            }
        }
        
    } catch(Exception $e) {
        $error_message = "Sistem hatası: " . $e->getMessage();
    }
}

// Bugünkü SMS sayısı
$stmt = $db->prepare("SELECT COUNT(*) as total FROM sms_logs 
                      WHERE DATE(created_at) = CURDATE() 
                      AND sender_user_id = :user_id");
$stmt->execute([':user_id' => $current_user_id]);
$today_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Gönder - Plazanet Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Ana wrapper */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .content-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .content-body {
            padding: 0 30px 30px;
        }
        
        /* İstatistik */
        .today-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .today-stat .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .today-stat .value {
            font-size: 32px;
            font-weight: bold;
        }
        
        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #ecf0f1;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: #3498db;
            color: white;
        }
        
        .tab-btn:hover {
            background: #34495e;
            color: white;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Card */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        /* Karakter sayacı */
        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .char-counter.warning {
            color: #e74c3c;
        }
        
        /* MÜŞTERİ LİSTESİ YENİ TASARIM */
        .customer-select-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            max-height: 450px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .customer-group {
            margin-bottom: 25px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .customer-group-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .customer-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            transition: all 0.3s;
            background: white;
        }
        
        .customer-item:hover {
            background: #f1f8ff;
            border-color: #3498db;
            transform: translateX(5px);
        }
        
        .customer-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .customer-item label {
            flex: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0;
            width: 100%;
        }
        
        .customer-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .customer-name {
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .customer-phone {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .sms-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .sms-allowed {
            background: #d4edda;
            color: #155724;
        }
        
        .sms-denied {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Seçim butonları */
        .select-buttons {
            margin: 15px 0;
            display: flex;
            gap: 10px;
        }
        
        .select-btn {
            padding: 6px 12px;
            background: #ecf0f1;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .select-btn:hover {
            background: #bdc3c7;
        }
        
        /* Şablon seçici */
        .template-selector {
            margin-bottom: 15px;
        }
        
        .template-selector select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
        
        /* Gönder butonu */
        .btn-send {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-send:hover {
            background: #229954;
        }
        
        .btn-send:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        /* Alert mesajları */
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
        
        /* Sonuç listesi */
        .results-list {
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }
        
        .result-item {
            padding: 5px;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .result-success {
            color: #155724;
        }
        
        .result-fail {
            color: #721c24;
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
                    <a href="../crm/index.php">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <li>
                    <a href="send.php" class="active">
                        <span class="icon">📤</span>
                        <span>SMS Gönder</span>
                    </a>
                </li>
                <li>
                    <a href="logs.php">
                        <span class="icon">📋</span>
                        <span>SMS Logları</span>
                    </a>
                </li>
                <?php if($current_user_role == 'admin'): ?>
                <li>
                    <a href="settings.php">
                        <span class="icon">⚙️</span>
                        <span>SMS Ayarları</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="../logout.php">
                        <span class="icon">🚪</span>
                        <span>Çıkış</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>📤 SMS Gönder</h1>
            </div>
            
            <div class="content-body">
                <!-- Bugünkü SMS sayısı -->
                <div class="today-stat">
                    <div>
                        <div class="label">Bugün Gönderilen SMS</div>
                        <div class="value"><?php echo $today_count; ?></div>
                    </div>
                    <div style="font-size: 48px; opacity: 0.5;">📱</div>
                </div>
                
                <!-- Bildirimler -->
                <?php if(isset($success_message)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('single')">
                        📱 Tekli SMS
                    </button>
                    <button class="tab-btn" onclick="switchTab('group')">
                        👥 Toplu SMS
                    </button>
                </div>
                
                <!-- Tekli SMS Tab -->
                <div id="single-tab" class="tab-content active">
                    <div class="form-card">
                        <form method="POST" id="singleSmsForm">
                            <input type="hidden" name="sms_type" value="single">
                            
                            <div class="form-group">
                                <label>Telefon Numarası</label>
                                <input type="tel" 
                                       name="single_phone" 
                                       placeholder="05XX XXX XX XX" 
                                       required
                                       pattern="[0-9]{10,11}">
                            </div>
                            
                            <!-- Şablon Seçici -->
                            <?php if(!empty($templates)): ?>
                            <div class="form-group">
                                <label>Şablon Kullan (Opsiyonel)</label>
                                <select onchange="useTemplate(this.value, 'single')">
                                    <option value="">-- Şablon Seçin --</option>
                                    <?php foreach($templates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['message_template']); ?>">
                                        <?php echo htmlspecialchars($template['template_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Mesaj Metni</label>
                                <textarea name="message" 
                                          id="single-message" 
                                          required
                                          maxlength="760"
                                          onkeyup="countChars('single')"></textarea>
                                <div class="char-counter" id="single-counter">0 / 760</div>
                            </div>
                            
                            <button type="submit" name="send_sms" class="btn-send">
                                📤 SMS Gönder
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Toplu SMS Tab -->
                <div id="group-tab" class="tab-content">
                    <div class="form-card">
                        <form method="POST" id="groupSmsForm">
                            <input type="hidden" name="sms_type" value="group">
                            
                            <div class="form-group">
                                <label>Alıcıları Seçin</label>
                                
                                <div class="select-buttons">
                                    <button type="button" class="select-btn" onclick="selectAll()">Tümünü Seç</button>
                                    <button type="button" class="select-btn" onclick="selectNone()">Hiçbirini Seçme</button>
                                    <button type="button" class="select-btn" onclick="selectSmsAllowed()">SMS İzinli Olanlar</button>
                                </div>
                                
                                <div class="customer-select-box">
                                    <!-- Alıcı Müşteriler -->
                                    <?php if(!empty($alici_musteriler)): ?>
                                    <div class="customer-group">
                                        <div class="customer-group-title">
                                            👥 Alıcı Müşteriler
                                        </div>
                                        <?php foreach($alici_musteriler as $musteri): ?>
                                        <div class="customer-item">
                                            <input type="checkbox" 
                                                   name="phones[]" 
                                                   value="alici_<?php echo $musteri['id']; ?>"
                                                   id="alici_<?php echo $musteri['id']; ?>"
                                                   data-sms="<?php echo $musteri['sms_permission']; ?>">
                                            <label for="alici_<?php echo $musteri['id']; ?>">
                                                <div class="customer-info">
                                                    <div class="customer-name">
                                                        <?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?>
                                                    </div>
                                                    <div class="customer-phone">
                                                        0<?php echo $musteri['telefon']; ?>
                                                    </div>
                                                </div>
                                                <?php if($musteri['sms_permission']): ?>
                                                <span class="sms-badge sms-allowed">SMS ✓</span>
                                                <?php else: ?>
                                                <span class="sms-badge sms-denied">SMS ✗</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Satıcı Müşteriler -->
                                    <?php if(!empty($satici_musteriler)): ?>
                                    <div class="customer-group">
                                        <div class="customer-group-title">
                                            🏠 Satıcı Müşteriler
                                        </div>
                                        <?php foreach($satici_musteriler as $musteri): ?>
                                        <div class="customer-item">
                                            <input type="checkbox" 
                                                   name="phones[]" 
                                                   value="satici_<?php echo $musteri['id']; ?>"
                                                   id="satici_<?php echo $musteri['id']; ?>"
                                                   data-sms="<?php echo $musteri['sms_permission']; ?>">
                                            <label for="satici_<?php echo $musteri['id']; ?>">
                                                <div class="customer-info">
                                                    <div class="customer-name">
                                                        <?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?>
                                                    </div>
                                                    <div class="customer-phone">
                                                        0<?php echo $musteri['telefon']; ?>
                                                    </div>
                                                </div>
                                                <?php if($musteri['sms_permission']): ?>
                                                <span class="sms-badge sms-allowed">SMS ✓</span>
                                                <?php else: ?>
                                                <span class="sms-badge sms-denied">SMS ✗</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Danışmanlar (Admin için) -->
                                    <?php if($current_user_role == 'admin' && !empty($danismanlar)): ?>
                                    <div class="customer-group">
                                        <div class="customer-group-title">
                                            👨‍💼 Danışmanlar
                                        </div>
                                        <?php foreach($danismanlar as $danisman): ?>
                                        <?php if($danisman['mobile']): ?>
                                        <div class="customer-item">
                                            <input type="checkbox" 
                                                   name="phones[]" 
                                                   value="danisman_<?php echo $danisman['id']; ?>"
                                                   id="danisman_<?php echo $danisman['id']; ?>"
                                                   data-sms="<?php echo $danisman['sms_permission']; ?>">
                                            <label for="danisman_<?php echo $danisman['id']; ?>">
                                                <div class="customer-info">
                                                    <div class="customer-name">
                                                        <?php echo htmlspecialchars($danisman['full_name'] ?? $danisman['username']); ?>
                                                    </div>
                                                    <div class="customer-phone">
                                                        0<?php echo $danisman['mobile']; ?>
                                                    </div>
                                                </div>
                                                <?php if($danisman['sms_permission']): ?>
                                                <span class="sms-badge sms-allowed">SMS ✓</span>
                                                <?php else: ?>
                                                <span class="sms-badge sms-denied">SMS ✗</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Eğer hiç müşteri yoksa -->
                                    <?php if(empty($alici_musteriler) && empty($satici_musteriler) && empty($danismanlar)): ?>
                                    <div style="text-align: center; padding: 40px; color: #95a5a6;">
                                        <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                        <div>Henüz müşteri kaydı bulunmuyor.</div>
                                        <div style="margin-top: 10px;">
                                            <a href="../crm/alici-ekle.php" style="color: #3498db;">
                                                Müşteri Ekle →
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="margin-top: 10px; color: #7f8c8d; font-size: 13px;">
                                    Seçilen: <span id="selected-count">0</span> kişi
                                </div>
                            </div>
                            
                            <!-- Şablon Seçici -->
                            <?php if(!empty($templates)): ?>
                            <div class="form-group">
                                <label>Şablon Kullan (Opsiyonel)</label>
                                <select onchange="useTemplate(this.value, 'group')">
                                    <option value="">-- Şablon Seçin --</option>
                                    <?php foreach($templates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['message_template']); ?>">
                                        <?php echo htmlspecialchars($template['template_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Mesaj Metni</label>
                                <textarea name="message" 
                                          id="group-message" 
                                          required
                                          maxlength="760"
                                          onkeyup="countChars('group')"></textarea>
                                <div class="char-counter" id="group-counter">0 / 760</div>
                            </div>
                            
                            <button type="submit" name="send_sms" class="btn-send">
                                📤 Toplu SMS Gönder
                            </button>
                        </form>
                        
                        <!-- Gönderim sonuçları -->
                        <?php if(!empty($results)): ?>
                        <div class="results-list">
                            <h4>Gönderim Sonuçları:</h4>
                            <?php foreach($results as $result): ?>
                            <div class="result-item <?php echo $result['status'] ? 'result-success' : 'result-fail'; ?>">
                                <?php echo $result['status'] ? '✅' : '❌'; ?>
                                <?php echo htmlspecialchars($result['name']); ?> - 
                                <?php echo $result['status'] ? 'Gönderildi' : 'Başarısız'; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Tab değiştirme
    function switchTab(tab) {
        // Tüm tabları gizle
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Seçili tabı göster
        document.getElementById(tab + '-tab').classList.add('active');
        event.target.classList.add('active');
    }
    
    // Karakter sayacı
    function countChars(type) {
        const textarea = document.getElementById(type + '-message');
        const counter = document.getElementById(type + '-counter');
        const length = textarea.value.length;
        
        counter.textContent = length + ' / 760';
        
        if(length > 700) {
            counter.classList.add('warning');
        } else {
            counter.classList.remove('warning');
        }
        
        // SMS sayısını hesapla (160 karakter = 1 SMS)
        const smsCount = Math.ceil(length / 160);
        if(smsCount > 1) {
            counter.textContent += ' (' + smsCount + ' SMS)';
        }
    }
    
    // Tümünü seç
    function selectAll() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.checked = true;
        });
        updateSelectedCount();
    }
    
    // Hiçbirini seçme
    function selectNone() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        updateSelectedCount();
    }
    
    // SMS izinli olanları seç
    function selectSmsAllowed() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.checked = cb.dataset.sms == '1';
        });
        updateSelectedCount();
    }
    
    // Seçilen sayısını güncelle
    function updateSelectedCount() {
        const count = document.querySelectorAll('.customer-select-box input[type="checkbox"]:checked').length;
        document.getElementById('selected-count').textContent = count;
    }
    
    // Checkbox değişimlerini dinle
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });
    });
    
    // Şablon kullan
    function useTemplate(template, type) {
        if(template) {
            document.getElementById(type + '-message').value = template;
            countChars(type);
        }
    }
    
    // Form gönderim onayı
    document.getElementById('singleSmsForm').addEventListener('submit', function(e) {
        if(!confirm('SMS göndermek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });
    
    document.getElementById('groupSmsForm').addEventListener('submit', function(e) {
        const selectedCount = document.querySelectorAll('.customer-select-box input[type="checkbox"]:checked').length;
        if(selectedCount === 0) {
            alert('Lütfen en az bir alıcı seçin!');
            e.preventDefault();
            return;
        }
        
        if(!confirm(selectedCount + ' kişiye SMS göndermek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });
    </script>
</body>
=======
<?php
// C:\xampp\htdocs\plazanet\admin\sms\send.php
// SMS Gönderme Sayfası

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../classes/NetGSM.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';
$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';

// CRM'den müşterileri çek
// Alıcı müşteriler
$alici_sql = "SELECT id, ad, soyad, telefon, sms_permission 
              FROM crm_alici_musteriler 
              WHERE durum = 'aktif' AND telefon IS NOT NULL AND telefon != ''";

if($current_user_role != 'admin') {
    $alici_sql .= " AND ekleyen_user_id = " . $current_user_id;
}
$alici_sql .= " ORDER BY ad, soyad";

$stmt = $db->query($alici_sql);
$alici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Satıcı müşteriler
$satici_sql = "SELECT id, ad, soyad, telefon, sms_permission 
               FROM crm_satici_musteriler 
               WHERE durum = 'aktif' AND telefon IS NOT NULL AND telefon != ''";

if($current_user_role != 'admin') {
    $satici_sql .= " AND ekleyen_user_id = " . $current_user_id;
}
$satici_sql .= " ORDER BY ad, soyad";

$stmt = $db->query($satici_sql);
$satici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Danışmanları çek (Admin için)
$danismanlar = [];
if($current_user_role == 'admin') {
    $stmt = $db->query("SELECT id, username, full_name, mobile, sms_permission 
                        FROM users 
                        WHERE status = 'active' 
                        ORDER BY full_name");
    $danismanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// SMS Şablonları
$stmt = $db->query("SELECT * FROM sms_templates WHERE is_active = 1 ORDER BY template_name");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildi mi?
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_sms'])) {
    $sms_type = $_POST['sms_type'];
    $message = trim($_POST['message']);
    $success_count = 0;
    $fail_count = 0;
    $results = [];
    
    try {
        $netgsm = new NetGSM($db);
        
        if($sms_type == 'single') {
            // Tekli gönderim
            $phone = $_POST['single_phone'];
            $result = $netgsm->sendSMS($phone, $message, 'manuel', $current_user_id, [
                'sender_name' => $current_user_name,
                'type' => 'manuel'
            ]);
            
            if($result['status']) {
                $success_message = "SMS başarıyla gönderildi!";
                $success_count = 1;
            } else {
                $error_message = "SMS gönderilemedi: " . $result['message'];
                $fail_count = 1;
            }
            
        } elseif($sms_type == 'group') {
            // Grup gönderimi
            $selected_phones = $_POST['phones'] ?? [];
            
            if(empty($selected_phones)) {
                $error_message = "Lütfen en az bir alıcı seçin!";
            } else {
                foreach($selected_phones as $phone_data) {
                    // Format: type_id (örn: alici_5, satici_3, danisman_2)
                    list($type, $id) = explode('_', $phone_data);
                    
                    // Telefon numarasını ve ismi bul
                    $phone = '';
                    $name = '';
                    
                    if($type == 'alici') {
                        foreach($alici_musteriler as $musteri) {
                            if($musteri['id'] == $id) {
                                $phone = $musteri['telefon'];
                                $name = $musteri['ad'] . ' ' . $musteri['soyad'];
                                break;
                            }
                        }
                        $recipient_type = 'alici';
                    } elseif($type == 'satici') {
                        foreach($satici_musteriler as $musteri) {
                            if($musteri['id'] == $id) {
                                $phone = $musteri['telefon'];
                                $name = $musteri['ad'] . ' ' . $musteri['soyad'];
                                break;
                            }
                        }
                        $recipient_type = 'satici';
                    } elseif($type == 'danisman') {
                        foreach($danismanlar as $danisman) {
                            if($danisman['id'] == $id) {
                                $phone = $danisman['mobile'];
                                $name = $danisman['full_name'] ?? $danisman['username'];
                                break;
                            }
                        }
                        $recipient_type = 'danisman';
                    }
                    
                    if($phone) {
                        $result = $netgsm->sendSMS($phone, $message, 'manuel', $current_user_id, [
                            'sender_name' => $current_user_name,
                            'type' => $recipient_type,
                            'recipient_id' => $id,
                            'recipient_name' => $name
                        ]);
                        
                        if($result['status']) {
                            $success_count++;
                        } else {
                            $fail_count++;
                        }
                        
                        $results[] = [
                            'name' => $name,
                            'phone' => $phone,
                            'status' => $result['status'],
                            'message' => $result['message']
                        ];
                    }
                }
                
                if($success_count > 0) {
                    $success_message = "{$success_count} SMS başarıyla gönderildi!";
                }
                if($fail_count > 0) {
                    $error_message = "{$fail_count} SMS gönderilemedi!";
                }
            }
        }
        
    } catch(Exception $e) {
        $error_message = "Sistem hatası: " . $e->getMessage();
    }
}

// Bugünkü SMS sayısı
$stmt = $db->prepare("SELECT COUNT(*) as total FROM sms_logs 
                      WHERE DATE(created_at) = CURDATE() 
                      AND sender_user_id = :user_id");
$stmt->execute([':user_id' => $current_user_id]);
$today_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Gönder - Plazanet Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Ana wrapper */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .content-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .content-body {
            padding: 0 30px 30px;
        }
        
        /* İstatistik */
        .today-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .today-stat .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .today-stat .value {
            font-size: 32px;
            font-weight: bold;
        }
        
        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #ecf0f1;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: #3498db;
            color: white;
        }
        
        .tab-btn:hover {
            background: #34495e;
            color: white;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Card */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        /* Karakter sayacı */
        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .char-counter.warning {
            color: #e74c3c;
        }
        
        /* MÜŞTERİ LİSTESİ YENİ TASARIM */
        .customer-select-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            max-height: 450px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .customer-group {
            margin-bottom: 25px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .customer-group-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .customer-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            transition: all 0.3s;
            background: white;
        }
        
        .customer-item:hover {
            background: #f1f8ff;
            border-color: #3498db;
            transform: translateX(5px);
        }
        
        .customer-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 15px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .customer-item label {
            flex: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0;
            width: 100%;
        }
        
        .customer-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .customer-name {
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .customer-phone {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .sms-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .sms-allowed {
            background: #d4edda;
            color: #155724;
        }
        
        .sms-denied {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Seçim butonları */
        .select-buttons {
            margin: 15px 0;
            display: flex;
            gap: 10px;
        }
        
        .select-btn {
            padding: 6px 12px;
            background: #ecf0f1;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .select-btn:hover {
            background: #bdc3c7;
        }
        
        /* Şablon seçici */
        .template-selector {
            margin-bottom: 15px;
        }
        
        .template-selector select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
        
        /* Gönder butonu */
        .btn-send {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-send:hover {
            background: #229954;
        }
        
        .btn-send:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        /* Alert mesajları */
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
        
        /* Sonuç listesi */
        .results-list {
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }
        
        .result-item {
            padding: 5px;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .result-success {
            color: #155724;
        }
        
        .result-fail {
            color: #721c24;
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
                    <a href="../crm/index.php">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <li>
                    <a href="send.php" class="active">
                        <span class="icon">📤</span>
                        <span>SMS Gönder</span>
                    </a>
                </li>
                <li>
                    <a href="logs.php">
                        <span class="icon">📋</span>
                        <span>SMS Logları</span>
                    </a>
                </li>
                <?php if($current_user_role == 'admin'): ?>
                <li>
                    <a href="settings.php">
                        <span class="icon">⚙️</span>
                        <span>SMS Ayarları</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="../logout.php">
                        <span class="icon">🚪</span>
                        <span>Çıkış</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>📤 SMS Gönder</h1>
            </div>
            
            <div class="content-body">
                <!-- Bugünkü SMS sayısı -->
                <div class="today-stat">
                    <div>
                        <div class="label">Bugün Gönderilen SMS</div>
                        <div class="value"><?php echo $today_count; ?></div>
                    </div>
                    <div style="font-size: 48px; opacity: 0.5;">📱</div>
                </div>
                
                <!-- Bildirimler -->
                <?php if(isset($success_message)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('single')">
                        📱 Tekli SMS
                    </button>
                    <button class="tab-btn" onclick="switchTab('group')">
                        👥 Toplu SMS
                    </button>
                </div>
                
                <!-- Tekli SMS Tab -->
                <div id="single-tab" class="tab-content active">
                    <div class="form-card">
                        <form method="POST" id="singleSmsForm">
                            <input type="hidden" name="sms_type" value="single">
                            
                            <div class="form-group">
                                <label>Telefon Numarası</label>
                                <input type="tel" 
                                       name="single_phone" 
                                       placeholder="05XX XXX XX XX" 
                                       required
                                       pattern="[0-9]{10,11}">
                            </div>
                            
                            <!-- Şablon Seçici -->
                            <?php if(!empty($templates)): ?>
                            <div class="form-group">
                                <label>Şablon Kullan (Opsiyonel)</label>
                                <select onchange="useTemplate(this.value, 'single')">
                                    <option value="">-- Şablon Seçin --</option>
                                    <?php foreach($templates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['message_template']); ?>">
                                        <?php echo htmlspecialchars($template['template_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Mesaj Metni</label>
                                <textarea name="message" 
                                          id="single-message" 
                                          required
                                          maxlength="760"
                                          onkeyup="countChars('single')"></textarea>
                                <div class="char-counter" id="single-counter">0 / 760</div>
                            </div>
                            
                            <button type="submit" name="send_sms" class="btn-send">
                                📤 SMS Gönder
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Toplu SMS Tab -->
                <div id="group-tab" class="tab-content">
                    <div class="form-card">
                        <form method="POST" id="groupSmsForm">
                            <input type="hidden" name="sms_type" value="group">
                            
                            <div class="form-group">
                                <label>Alıcıları Seçin</label>
                                
                                <div class="select-buttons">
                                    <button type="button" class="select-btn" onclick="selectAll()">Tümünü Seç</button>
                                    <button type="button" class="select-btn" onclick="selectNone()">Hiçbirini Seçme</button>
                                    <button type="button" class="select-btn" onclick="selectSmsAllowed()">SMS İzinli Olanlar</button>
                                </div>
                                
                                <div class="customer-select-box">
                                    <!-- Alıcı Müşteriler -->
                                    <?php if(!empty($alici_musteriler)): ?>
                                    <div class="customer-group">
                                        <div class="customer-group-title">
                                            👥 Alıcı Müşteriler
                                        </div>
                                        <?php foreach($alici_musteriler as $musteri): ?>
                                        <div class="customer-item">
                                            <input type="checkbox" 
                                                   name="phones[]" 
                                                   value="alici_<?php echo $musteri['id']; ?>"
                                                   id="alici_<?php echo $musteri['id']; ?>"
                                                   data-sms="<?php echo $musteri['sms_permission']; ?>">
                                            <label for="alici_<?php echo $musteri['id']; ?>">
                                                <div class="customer-info">
                                                    <div class="customer-name">
                                                        <?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?>
                                                    </div>
                                                    <div class="customer-phone">
                                                        0<?php echo $musteri['telefon']; ?>
                                                    </div>
                                                </div>
                                                <?php if($musteri['sms_permission']): ?>
                                                <span class="sms-badge sms-allowed">SMS ✓</span>
                                                <?php else: ?>
                                                <span class="sms-badge sms-denied">SMS ✗</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Satıcı Müşteriler -->
                                    <?php if(!empty($satici_musteriler)): ?>
                                    <div class="customer-group">
                                        <div class="customer-group-title">
                                            🏠 Satıcı Müşteriler
                                        </div>
                                        <?php foreach($satici_musteriler as $musteri): ?>
                                        <div class="customer-item">
                                            <input type="checkbox" 
                                                   name="phones[]" 
                                                   value="satici_<?php echo $musteri['id']; ?>"
                                                   id="satici_<?php echo $musteri['id']; ?>"
                                                   data-sms="<?php echo $musteri['sms_permission']; ?>">
                                            <label for="satici_<?php echo $musteri['id']; ?>">
                                                <div class="customer-info">
                                                    <div class="customer-name">
                                                        <?php echo htmlspecialchars($musteri['ad'] . ' ' . $musteri['soyad']); ?>
                                                    </div>
                                                    <div class="customer-phone">
                                                        0<?php echo $musteri['telefon']; ?>
                                                    </div>
                                                </div>
                                                <?php if($musteri['sms_permission']): ?>
                                                <span class="sms-badge sms-allowed">SMS ✓</span>
                                                <?php else: ?>
                                                <span class="sms-badge sms-denied">SMS ✗</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Danışmanlar (Admin için) -->
                                    <?php if($current_user_role == 'admin' && !empty($danismanlar)): ?>
                                    <div class="customer-group">
                                        <div class="customer-group-title">
                                            👨‍💼 Danışmanlar
                                        </div>
                                        <?php foreach($danismanlar as $danisman): ?>
                                        <?php if($danisman['mobile']): ?>
                                        <div class="customer-item">
                                            <input type="checkbox" 
                                                   name="phones[]" 
                                                   value="danisman_<?php echo $danisman['id']; ?>"
                                                   id="danisman_<?php echo $danisman['id']; ?>"
                                                   data-sms="<?php echo $danisman['sms_permission']; ?>">
                                            <label for="danisman_<?php echo $danisman['id']; ?>">
                                                <div class="customer-info">
                                                    <div class="customer-name">
                                                        <?php echo htmlspecialchars($danisman['full_name'] ?? $danisman['username']); ?>
                                                    </div>
                                                    <div class="customer-phone">
                                                        0<?php echo $danisman['mobile']; ?>
                                                    </div>
                                                </div>
                                                <?php if($danisman['sms_permission']): ?>
                                                <span class="sms-badge sms-allowed">SMS ✓</span>
                                                <?php else: ?>
                                                <span class="sms-badge sms-denied">SMS ✗</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Eğer hiç müşteri yoksa -->
                                    <?php if(empty($alici_musteriler) && empty($satici_musteriler) && empty($danismanlar)): ?>
                                    <div style="text-align: center; padding: 40px; color: #95a5a6;">
                                        <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                        <div>Henüz müşteri kaydı bulunmuyor.</div>
                                        <div style="margin-top: 10px;">
                                            <a href="../crm/alici-ekle.php" style="color: #3498db;">
                                                Müşteri Ekle →
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="margin-top: 10px; color: #7f8c8d; font-size: 13px;">
                                    Seçilen: <span id="selected-count">0</span> kişi
                                </div>
                            </div>
                            
                            <!-- Şablon Seçici -->
                            <?php if(!empty($templates)): ?>
                            <div class="form-group">
                                <label>Şablon Kullan (Opsiyonel)</label>
                                <select onchange="useTemplate(this.value, 'group')">
                                    <option value="">-- Şablon Seçin --</option>
                                    <?php foreach($templates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['message_template']); ?>">
                                        <?php echo htmlspecialchars($template['template_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Mesaj Metni</label>
                                <textarea name="message" 
                                          id="group-message" 
                                          required
                                          maxlength="760"
                                          onkeyup="countChars('group')"></textarea>
                                <div class="char-counter" id="group-counter">0 / 760</div>
                            </div>
                            
                            <button type="submit" name="send_sms" class="btn-send">
                                📤 Toplu SMS Gönder
                            </button>
                        </form>
                        
                        <!-- Gönderim sonuçları -->
                        <?php if(!empty($results)): ?>
                        <div class="results-list">
                            <h4>Gönderim Sonuçları:</h4>
                            <?php foreach($results as $result): ?>
                            <div class="result-item <?php echo $result['status'] ? 'result-success' : 'result-fail'; ?>">
                                <?php echo $result['status'] ? '✅' : '❌'; ?>
                                <?php echo htmlspecialchars($result['name']); ?> - 
                                <?php echo $result['status'] ? 'Gönderildi' : 'Başarısız'; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Tab değiştirme
    function switchTab(tab) {
        // Tüm tabları gizle
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Seçili tabı göster
        document.getElementById(tab + '-tab').classList.add('active');
        event.target.classList.add('active');
    }
    
    // Karakter sayacı
    function countChars(type) {
        const textarea = document.getElementById(type + '-message');
        const counter = document.getElementById(type + '-counter');
        const length = textarea.value.length;
        
        counter.textContent = length + ' / 760';
        
        if(length > 700) {
            counter.classList.add('warning');
        } else {
            counter.classList.remove('warning');
        }
        
        // SMS sayısını hesapla (160 karakter = 1 SMS)
        const smsCount = Math.ceil(length / 160);
        if(smsCount > 1) {
            counter.textContent += ' (' + smsCount + ' SMS)';
        }
    }
    
    // Tümünü seç
    function selectAll() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.checked = true;
        });
        updateSelectedCount();
    }
    
    // Hiçbirini seçme
    function selectNone() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        updateSelectedCount();
    }
    
    // SMS izinli olanları seç
    function selectSmsAllowed() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.checked = cb.dataset.sms == '1';
        });
        updateSelectedCount();
    }
    
    // Seçilen sayısını güncelle
    function updateSelectedCount() {
        const count = document.querySelectorAll('.customer-select-box input[type="checkbox"]:checked').length;
        document.getElementById('selected-count').textContent = count;
    }
    
    // Checkbox değişimlerini dinle
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.customer-select-box input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });
    });
    
    // Şablon kullan
    function useTemplate(template, type) {
        if(template) {
            document.getElementById(type + '-message').value = template;
            countChars(type);
        }
    }
    
    // Form gönderim onayı
    document.getElementById('singleSmsForm').addEventListener('submit', function(e) {
        if(!confirm('SMS göndermek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });
    
    document.getElementById('groupSmsForm').addEventListener('submit', function(e) {
        const selectedCount = document.querySelectorAll('.customer-select-box input[type="checkbox"]:checked').length;
        if(selectedCount === 0) {
            alert('Lütfen en az bir alıcı seçin!');
            e.preventDefault();
            return;
        }
        
        if(!confirm(selectedCount + ' kişiye SMS göndermek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>