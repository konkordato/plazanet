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

// Müşteri listelerini çek
$alici_sql = "SELECT id, ad, soyad, telefon FROM crm_alici_musteriler WHERE durum = 'aktif'";
if($current_user_role != 'admin') {
    $alici_sql .= " AND ekleyen_user_id = :user_id";
}
$alici_sql .= " ORDER BY ad, soyad";

$stmt = $db->prepare($alici_sql);
if($current_user_role != 'admin') {
    $stmt->execute([':user_id' => $current_user_id]);
} else {
    $stmt->execute();
}
$alici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$satici_sql = "SELECT id, ad, soyad, telefon FROM crm_satici_musteriler WHERE durum = 'aktif'";
if($current_user_role != 'admin') {
    $satici_sql .= " AND ekleyen_user_id = :user_id";
}
$satici_sql .= " ORDER BY ad, soyad";

$stmt = $db->prepare($satici_sql);
if($current_user_role != 'admin') {
    $stmt->execute([':user_id' => $current_user_id]);
} else {
    $stmt->execute();
}
$satici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildi mi?
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = trim($_POST['baslik']);
    $mesaj = trim($_POST['mesaj']);
    $hatirlatma_tipi = $_POST['hatirlatma_tipi'];
    $musteri_tipi = $_POST['musteri_tipi'] ?? 'genel';
    $musteri_id = $_POST['musteri_id'] ?? null;
    $hatirlatma_tarihi = $_POST['hatirlatma_tarihi'];
    $hatirlatma_saati = $_POST['hatirlatma_saati'];
    $oncelik = $_POST['oncelik'];
    
    // Tarih ve saat birleştir
    $hatirlatma_datetime = $hatirlatma_tarihi . ' ' . $hatirlatma_saati . ':00';
    
    try {
        $sql = "INSERT INTO crm_hatirlatmalar (
            baslik, mesaj, hatirlatma_tipi, musteri_tipi, musteri_id,
            hatirlatma_tarihi, oncelik, user_id, user_adi
        ) VALUES (
            :baslik, :mesaj, :hatirlatma_tipi, :musteri_tipi, :musteri_id,
            :hatirlatma_tarihi, :oncelik, :user_id, :user_adi
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':baslik' => $baslik,
            ':mesaj' => $mesaj,
            ':hatirlatma_tipi' => $hatirlatma_tipi,
            ':musteri_tipi' => $musteri_tipi,
            ':musteri_id' => $musteri_id,
            ':hatirlatma_tarihi' => $hatirlatma_datetime,
            ':oncelik' => $oncelik,
            ':user_id' => $current_user_id,
            ':user_adi' => $current_user_name
        ]);
        
        $_SESSION['success_message'] = "Hatırlatma başarıyla eklendi!";
        header("Location: hatirlatmalar.php");
        exit();
        
    } catch(PDOException $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hatırlatma Ekle - CRM</title>
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
        
        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f39c12;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .priority-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .priority-option {
            padding: 10px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .priority-option:hover {
            transform: scale(1.05);
        }
        
        .priority-option.selected {
            border-color: #2c3e50;
            font-weight: bold;
        }
        
        .priority-dusuk { background: #ecf0f1; }
        .priority-normal { background: #3498db; color: white; }
        .priority-yuksek { background: #f39c12; color: white; }
        .priority-acil { background: #e74c3c; color: white; }
        
        .customer-select-container {
            display: none;
        }
        
        .customer-select-container.show {
            display: block;
        }
        
        .quick-templates {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .template-btn {
            padding: 8px 12px;
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .template-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .submit-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #f39c12;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box p {
            margin: 0;
            color: #856404;
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
            <div class="form-container">
                <div class="form-header">
                    <h2>🔔 Yeni Hatırlatma Oluştur</h2>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="info-box">
                    <p>💡 Hatırlatmalar belirlediğiniz tarih ve saatte size bildirim olarak gösterilir.</p>
                </div>
                
                <form method="POST" action="">
                    <!-- Hızlı Şablonlar -->
                    <div class="quick-templates">
                        <button type="button" class="template-btn" onclick="setTemplate('arama')">
                            📞 Müşteri Arama
                        </button>
                        <button type="button" class="template-btn" onclick="setTemplate('gorusme')">
                            👥 Görüşme
                        </button>
                        <button type="button" class="template-btn" onclick="setTemplate('takip')">
                            🔄 Takip
                        </button>
                        <button type="button" class="template-btn" onclick="setTemplate('odeme')">
                            💰 Ödeme
                        </button>
                    </div>
                    
                    <!-- Temel Bilgiler -->
                    <div class="form-group">
                        <label>Hatırlatma Başlığı *</label>
                        <input type="text" name="baslik" id="baslik" required placeholder="Örn: Ahmet Bey'i ara">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hatırlatma Tipi</label>
                            <select name="hatirlatma_tipi" id="hatirlatma_tipi">
                                <option value="arama">📞 Arama</option>
                                <option value="gorusme">👥 Görüşme</option>
                                <option value="odeme">💰 Ödeme</option>
                                <option value="diger">📌 Diğer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Müşteri Tipi</label>
                            <select name="musteri_tipi" id="musteri_tipi" onchange="showCustomerSelect()">
                                <option value="genel">Genel (Müşteri Yok)</option>
                                <option value="alici">Alıcı Müşteri</option>
                                <option value="satici">Satıcı Müşteri</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Müşteri Seçimi -->
                    <div id="alici_container" class="customer-select-container">
                        <div class="form-group">
                            <label>Alıcı Müşteri</label>
                            <select name="alici_musteri_id" id="alici_musteri_id">
                                <option value="">-- Seçiniz --</option>
                                <?php foreach($alici_musteriler as $musteri): ?>
                                <option value="<?php echo $musteri['id']; ?>">
                                    <?php echo $musteri['ad'] . ' ' . $musteri['soyad'] . ' (0' . $musteri['telefon'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="satici_container" class="customer-select-container">
                        <div class="form-group">
                            <label>Satıcı Müşteri</label>
                            <select name="satici_musteri_id" id="satici_musteri_id">
                                <option value="">-- Seçiniz --</option>
                                <?php foreach($satici_musteriler as $musteri): ?>
                                <option value="<?php echo $musteri['id']; ?>">
                                    <?php echo $musteri['ad'] . ' ' . $musteri['soyad'] . ' (0' . $musteri['telefon'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="musteri_id" id="musteri_id">
                    
                    <!-- Tarih ve Saat -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hatırlatma Tarihi *</label>
                            <input type="date" name="hatirlatma_tarihi" min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Hatırlatma Saati *</label>
                            <input type="time" name="hatirlatma_saati" value="09:00" required>
                        </div>
                    </div>
                    
                    <!-- Öncelik -->
                    <div class="form-group">
                        <label>Öncelik Seviyesi</label>
                        <div class="priority-selector">
                            <div class="priority-option priority-dusuk" onclick="selectPriority('dusuk')">
                                Düşük
                            </div>
                            <div class="priority-option priority-normal selected" onclick="selectPriority('normal')">
                                Normal
                            </div>
                            <div class="priority-option priority-yuksek" onclick="selectPriority('yuksek')">
                                Yüksek
                            </div>
                            <div class="priority-option priority-acil" onclick="selectPriority('acil')">
                                Acil
                            </div>
                        </div>
                        <input type="hidden" name="oncelik" id="oncelik" value="normal">
                    </div>
                    
                    <!-- Mesaj -->
                    <div class="form-group">
                        <label>Hatırlatma Mesajı</label>
                        <textarea name="mesaj" id="mesaj" placeholder="Hatırlatma ile ilgili detaylı notlar..."></textarea>
                    </div>
                    
                    <div class="submit-buttons">
                        <button type="submit" class="btn btn-primary">🔔 Hatırlatma Oluştur</button>
                        <a href="hatirlatmalar.php" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function showCustomerSelect() {
        const musteri_tipi = document.getElementById('musteri_tipi').value;
        
        // Tüm konteynerları gizle
        document.getElementById('alici_container').classList.remove('show');
        document.getElementById('satici_container').classList.remove('show');
        
        // Seçime göre göster
        if(musteri_tipi === 'alici') {
            document.getElementById('alici_container').classList.add('show');
            document.getElementById('musteri_id').value = document.getElementById('alici_musteri_id').value;
        } else if(musteri_tipi === 'satici') {
            document.getElementById('satici_container').classList.add('show');
            document.getElementById('musteri_id').value = document.getElementById('satici_musteri_id').value;
        } else {
            document.getElementById('musteri_id').value = '';
        }
    }
    
    // Müşteri seçimi değiştiğinde
    document.getElementById('alici_musteri_id').addEventListener('change', function() {
        if(document.getElementById('musteri_tipi').value === 'alici') {
            document.getElementById('musteri_id').value = this.value;
        }
    });
    
    document.getElementById('satici_musteri_id').addEventListener('change', function() {
        if(document.getElementById('musteri_tipi').value === 'satici') {
            document.getElementById('musteri_id').value = this.value;
        }
    });
    
    function selectPriority(priority) {
        // Tüm önceliklerin seçimini kaldır
        document.querySelectorAll('.priority-option').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Seçilen önceliği işaretle
        event.target.classList.add('selected');
        document.getElementById('oncelik').value = priority;
    }
    
    // Hızlı şablon doldurma
    function setTemplate(type) {
        const templates = {
            'arama': {
                baslik: 'Müşteri arama hatırlatması',
                mesaj: 'Müşteriyi arayıp güncel durumu hakkında bilgi al.',
                tip: 'arama'
            },
            'gorusme': {
                baslik: 'Görüşme hatırlatması',
                mesaj: 'Planlanan görüşme için hazırlık yap.',
                tip: 'gorusme'
            },
            'takip': {
                baslik: 'Takip hatırlatması',
                mesaj: 'Müşteri ile son görüşmeden sonra takip araması yap.',
                tip: 'arama'
            },
            'odeme': {
                baslik: 'Ödeme hatırlatması',
                mesaj: 'Komisyon veya ödeme takibi için kontrol yap.',
                tip: 'odeme'
            }
        };
        
        const template = templates[type];
        if(template) {
            document.getElementById('baslik').value = template.baslik;
            document.getElementById('mesaj').value = template.mesaj;
            document.getElementById('hatirlatma_tipi').value = template.tip;
        }
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

// Müşteri listelerini çek
$alici_sql = "SELECT id, ad, soyad, telefon FROM crm_alici_musteriler WHERE durum = 'aktif'";
if($current_user_role != 'admin') {
    $alici_sql .= " AND ekleyen_user_id = :user_id";
}
$alici_sql .= " ORDER BY ad, soyad";

$stmt = $db->prepare($alici_sql);
if($current_user_role != 'admin') {
    $stmt->execute([':user_id' => $current_user_id]);
} else {
    $stmt->execute();
}
$alici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$satici_sql = "SELECT id, ad, soyad, telefon FROM crm_satici_musteriler WHERE durum = 'aktif'";
if($current_user_role != 'admin') {
    $satici_sql .= " AND ekleyen_user_id = :user_id";
}
$satici_sql .= " ORDER BY ad, soyad";

$stmt = $db->prepare($satici_sql);
if($current_user_role != 'admin') {
    $stmt->execute([':user_id' => $current_user_id]);
} else {
    $stmt->execute();
}
$satici_musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildi mi?
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = trim($_POST['baslik']);
    $mesaj = trim($_POST['mesaj']);
    $hatirlatma_tipi = $_POST['hatirlatma_tipi'];
    $musteri_tipi = $_POST['musteri_tipi'] ?? 'genel';
    $musteri_id = $_POST['musteri_id'] ?? null;
    $hatirlatma_tarihi = $_POST['hatirlatma_tarihi'];
    $hatirlatma_saati = $_POST['hatirlatma_saati'];
    $oncelik = $_POST['oncelik'];
    
    // Tarih ve saat birleştir
    $hatirlatma_datetime = $hatirlatma_tarihi . ' ' . $hatirlatma_saati . ':00';
    
    try {
        $sql = "INSERT INTO crm_hatirlatmalar (
            baslik, mesaj, hatirlatma_tipi, musteri_tipi, musteri_id,
            hatirlatma_tarihi, oncelik, user_id, user_adi
        ) VALUES (
            :baslik, :mesaj, :hatirlatma_tipi, :musteri_tipi, :musteri_id,
            :hatirlatma_tarihi, :oncelik, :user_id, :user_adi
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':baslik' => $baslik,
            ':mesaj' => $mesaj,
            ':hatirlatma_tipi' => $hatirlatma_tipi,
            ':musteri_tipi' => $musteri_tipi,
            ':musteri_id' => $musteri_id,
            ':hatirlatma_tarihi' => $hatirlatma_datetime,
            ':oncelik' => $oncelik,
            ':user_id' => $current_user_id,
            ':user_adi' => $current_user_name
        ]);
        
        $_SESSION['success_message'] = "Hatırlatma başarıyla eklendi!";
        header("Location: hatirlatmalar.php");
        exit();
        
    } catch(PDOException $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hatırlatma Ekle - CRM</title>
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
        
        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f39c12;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .priority-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .priority-option {
            padding: 10px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .priority-option:hover {
            transform: scale(1.05);
        }
        
        .priority-option.selected {
            border-color: #2c3e50;
            font-weight: bold;
        }
        
        .priority-dusuk { background: #ecf0f1; }
        .priority-normal { background: #3498db; color: white; }
        .priority-yuksek { background: #f39c12; color: white; }
        .priority-acil { background: #e74c3c; color: white; }
        
        .customer-select-container {
            display: none;
        }
        
        .customer-select-container.show {
            display: block;
        }
        
        .quick-templates {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .template-btn {
            padding: 8px 12px;
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .template-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .submit-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #f39c12;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box p {
            margin: 0;
            color: #856404;
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
            <div class="form-container">
                <div class="form-header">
                    <h2>🔔 Yeni Hatırlatma Oluştur</h2>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="info-box">
                    <p>💡 Hatırlatmalar belirlediğiniz tarih ve saatte size bildirim olarak gösterilir.</p>
                </div>
                
                <form method="POST" action="">
                    <!-- Hızlı Şablonlar -->
                    <div class="quick-templates">
                        <button type="button" class="template-btn" onclick="setTemplate('arama')">
                            📞 Müşteri Arama
                        </button>
                        <button type="button" class="template-btn" onclick="setTemplate('gorusme')">
                            👥 Görüşme
                        </button>
                        <button type="button" class="template-btn" onclick="setTemplate('takip')">
                            🔄 Takip
                        </button>
                        <button type="button" class="template-btn" onclick="setTemplate('odeme')">
                            💰 Ödeme
                        </button>
                    </div>
                    
                    <!-- Temel Bilgiler -->
                    <div class="form-group">
                        <label>Hatırlatma Başlığı *</label>
                        <input type="text" name="baslik" id="baslik" required placeholder="Örn: Ahmet Bey'i ara">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hatırlatma Tipi</label>
                            <select name="hatirlatma_tipi" id="hatirlatma_tipi">
                                <option value="arama">📞 Arama</option>
                                <option value="gorusme">👥 Görüşme</option>
                                <option value="odeme">💰 Ödeme</option>
                                <option value="diger">📌 Diğer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Müşteri Tipi</label>
                            <select name="musteri_tipi" id="musteri_tipi" onchange="showCustomerSelect()">
                                <option value="genel">Genel (Müşteri Yok)</option>
                                <option value="alici">Alıcı Müşteri</option>
                                <option value="satici">Satıcı Müşteri</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Müşteri Seçimi -->
                    <div id="alici_container" class="customer-select-container">
                        <div class="form-group">
                            <label>Alıcı Müşteri</label>
                            <select name="alici_musteri_id" id="alici_musteri_id">
                                <option value="">-- Seçiniz --</option>
                                <?php foreach($alici_musteriler as $musteri): ?>
                                <option value="<?php echo $musteri['id']; ?>">
                                    <?php echo $musteri['ad'] . ' ' . $musteri['soyad'] . ' (0' . $musteri['telefon'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="satici_container" class="customer-select-container">
                        <div class="form-group">
                            <label>Satıcı Müşteri</label>
                            <select name="satici_musteri_id" id="satici_musteri_id">
                                <option value="">-- Seçiniz --</option>
                                <?php foreach($satici_musteriler as $musteri): ?>
                                <option value="<?php echo $musteri['id']; ?>">
                                    <?php echo $musteri['ad'] . ' ' . $musteri['soyad'] . ' (0' . $musteri['telefon'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="musteri_id" id="musteri_id">
                    
                    <!-- Tarih ve Saat -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hatırlatma Tarihi *</label>
                            <input type="date" name="hatirlatma_tarihi" min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Hatırlatma Saati *</label>
                            <input type="time" name="hatirlatma_saati" value="09:00" required>
                        </div>
                    </div>
                    
                    <!-- Öncelik -->
                    <div class="form-group">
                        <label>Öncelik Seviyesi</label>
                        <div class="priority-selector">
                            <div class="priority-option priority-dusuk" onclick="selectPriority('dusuk')">
                                Düşük
                            </div>
                            <div class="priority-option priority-normal selected" onclick="selectPriority('normal')">
                                Normal
                            </div>
                            <div class="priority-option priority-yuksek" onclick="selectPriority('yuksek')">
                                Yüksek
                            </div>
                            <div class="priority-option priority-acil" onclick="selectPriority('acil')">
                                Acil
                            </div>
                        </div>
                        <input type="hidden" name="oncelik" id="oncelik" value="normal">
                    </div>
                    
                    <!-- Mesaj -->
                    <div class="form-group">
                        <label>Hatırlatma Mesajı</label>
                        <textarea name="mesaj" id="mesaj" placeholder="Hatırlatma ile ilgili detaylı notlar..."></textarea>
                    </div>
                    
                    <div class="submit-buttons">
                        <button type="submit" class="btn btn-primary">🔔 Hatırlatma Oluştur</button>
                        <a href="hatirlatmalar.php" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function showCustomerSelect() {
        const musteri_tipi = document.getElementById('musteri_tipi').value;
        
        // Tüm konteynerları gizle
        document.getElementById('alici_container').classList.remove('show');
        document.getElementById('satici_container').classList.remove('show');
        
        // Seçime göre göster
        if(musteri_tipi === 'alici') {
            document.getElementById('alici_container').classList.add('show');
            document.getElementById('musteri_id').value = document.getElementById('alici_musteri_id').value;
        } else if(musteri_tipi === 'satici') {
            document.getElementById('satici_container').classList.add('show');
            document.getElementById('musteri_id').value = document.getElementById('satici_musteri_id').value;
        } else {
            document.getElementById('musteri_id').value = '';
        }
    }
    
    // Müşteri seçimi değiştiğinde
    document.getElementById('alici_musteri_id').addEventListener('change', function() {
        if(document.getElementById('musteri_tipi').value === 'alici') {
            document.getElementById('musteri_id').value = this.value;
        }
    });
    
    document.getElementById('satici_musteri_id').addEventListener('change', function() {
        if(document.getElementById('musteri_tipi').value === 'satici') {
            document.getElementById('musteri_id').value = this.value;
        }
    });
    
    function selectPriority(priority) {
        // Tüm önceliklerin seçimini kaldır
        document.querySelectorAll('.priority-option').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Seçilen önceliği işaretle
        event.target.classList.add('selected');
        document.getElementById('oncelik').value = priority;
    }
    
    // Hızlı şablon doldurma
    function setTemplate(type) {
        const templates = {
            'arama': {
                baslik: 'Müşteri arama hatırlatması',
                mesaj: 'Müşteriyi arayıp güncel durumu hakkında bilgi al.',
                tip: 'arama'
            },
            'gorusme': {
                baslik: 'Görüşme hatırlatması',
                mesaj: 'Planlanan görüşme için hazırlık yap.',
                tip: 'gorusme'
            },
            'takip': {
                baslik: 'Takip hatırlatması',
                mesaj: 'Müşteri ile son görüşmeden sonra takip araması yap.',
                tip: 'arama'
            },
            'odeme': {
                baslik: 'Ödeme hatırlatması',
                mesaj: 'Komisyon veya ödeme takibi için kontrol yap.',
                tip: 'odeme'
            }
        };
        
        const template = templates[type];
        if(template) {
            document.getElementById('baslik').value = template.baslik;
            document.getElementById('mesaj').value = template.mesaj;
            document.getElementById('hatirlatma_tipi').value = template.tip;
        }
    }
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>