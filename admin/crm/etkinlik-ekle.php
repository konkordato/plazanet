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

// Tarih parametreleri (takvimden gelenler)
$selected_day = $_GET['day'] ?? date('j');
$selected_month = $_GET['month'] ?? date('n');
$selected_year = $_GET['year'] ?? date('Y');
$selected_date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $selected_day);

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

// Kullanıcı listesi (admin için)
$users = [];
if($current_user_role == 'admin') {
    $stmt = $db->query("SELECT id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Form gönderildi mi?
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = trim($_POST['baslik']);
    $aciklama = trim($_POST['aciklama']);
    $etkinlik_tipi = $_POST['etkinlik_tipi'];
    $musteri_tipi = $_POST['musteri_tipi'] ?? 'genel';
    $musteri_id = $_POST['musteri_id'] ?? null;
    $baslangic_tarih = $_POST['baslangic_tarih'];
    $baslangic_saat = $_POST['baslangic_saat'];
    $bitis_tarih = $_POST['bitis_tarih'] ?? null;
    $bitis_saat = $_POST['bitis_saat'] ?? null;
    $tum_gun = isset($_POST['tum_gun']) ? 1 : 0;
    $konum = trim($_POST['konum']);
    $renk = $_POST['renk'];
    $atanan_user_id = $_POST['atanan_user_id'] ?? $current_user_id;
    $hatirlatma = isset($_POST['hatirlatma']) ? 1 : 0;
    $hatirlatma_zaman = $_POST['hatirlatma_zaman'] ?? 30;
    
    // Tarih ve saat birleştir
    $baslangic_datetime = $baslangic_tarih . ' ' . ($tum_gun ? '00:00:00' : $baslangic_saat . ':00');
    $bitis_datetime = null;
    if($bitis_tarih) {
        $bitis_datetime = $bitis_tarih . ' ' . ($tum_gun ? '23:59:59' : ($bitis_saat ? $bitis_saat . ':00' : '23:59:59'));
    }
    
    try {
        // Etkinliği kaydet
        $sql = "INSERT INTO crm_takvim_etkinlikler (
            baslik, aciklama, etkinlik_tipi, musteri_tipi, musteri_id,
            baslangic_tarih, bitis_tarih, tum_gun, konum, renk,
            olusturan_user_id, olusturan_user_adi, atanan_user_id, durum
        ) VALUES (
            :baslik, :aciklama, :etkinlik_tipi, :musteri_tipi, :musteri_id,
            :baslangic_tarih, :bitis_tarih, :tum_gun, :konum, :renk,
            :olusturan_user_id, :olusturan_user_adi, :atanan_user_id, 'beklemede'
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':baslik' => $baslik,
            ':aciklama' => $aciklama,
            ':etkinlik_tipi' => $etkinlik_tipi,
            ':musteri_tipi' => $musteri_tipi,
            ':musteri_id' => $musteri_id,
            ':baslangic_tarih' => $baslangic_datetime,
            ':bitis_tarih' => $bitis_datetime,
            ':tum_gun' => $tum_gun,
            ':konum' => $konum,
            ':renk' => $renk,
            ':olusturan_user_id' => $current_user_id,
            ':olusturan_user_adi' => $current_user_name,
            ':atanan_user_id' => $atanan_user_id
        ]);
        
        $etkinlik_id = $db->lastInsertId();
        
        // Hatırlatma ekle
        if($hatirlatma) {
            $hatirlatma_tarihi = date('Y-m-d H:i:s', strtotime($baslangic_datetime) - ($hatirlatma_zaman * 60));
            
            $hat_sql = "INSERT INTO crm_hatirlatmalar (
                baslik, mesaj, hatirlatma_tipi, musteri_tipi, musteri_id,
                hatirlatma_tarihi, user_id, user_adi, etkinlik_id, oncelik
            ) VALUES (
                :baslik, :mesaj, :tip, :musteri_tipi, :musteri_id,
                :hatirlatma_tarihi, :user_id, :user_adi, :etkinlik_id, 'normal'
            )";
            
            $mesaj = "Yaklaşan etkinlik: $baslik";
            
            $stmt = $db->prepare($hat_sql);
            $stmt->execute([
                ':baslik' => "Hatırlatma: $baslik",
                ':mesaj' => $mesaj,
                ':tip' => $etkinlik_tipi == 'gorusme' ? 'gorusme' : 'diger',
                ':musteri_tipi' => $musteri_tipi,
                ':musteri_id' => $musteri_id,
                ':hatirlatma_tarihi' => $hatirlatma_tarihi,
                ':user_id' => $atanan_user_id,
                ':user_adi' => $current_user_name,
                ':etkinlik_id' => $etkinlik_id
            ]);
        }
        
        $_SESSION['success_message'] = "Etkinlik başarıyla eklendi!";
        header("Location: takvim.php");
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
    <title>Etkinlik Ekle - CRM</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3498db;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .color-picker {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .color-option:hover {
            transform: scale(1.1);
        }
        
        .color-option.selected {
            border-color: #2c3e50;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        
        .form-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
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
            background: #3498db;
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
            background: #e8f6f3;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .info-box p {
            margin: 0;
            color: #27ae60;
        }
        
        .customer-select-container {
            display: none;
        }
        
        .customer-select-container.show {
            display: block;
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
                    <h2>📅 Yeni Etkinlik Ekle</h2>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="info-box">
                    <p>📍 Seçili tarih: <?php echo date('d.m.Y', strtotime($selected_date)); ?></p>
                </div>
                
                <form method="POST" action="">
                    <!-- Temel Bilgiler -->
                    <div class="form-group">
                        <label>Etkinlik Başlığı *</label>
                        <input type="text" name="baslik" required placeholder="Örn: Müşteri görüşmesi, Saha ziyareti...">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Etkinlik Tipi</label>
                            <select name="etkinlik_tipi">
                                <option value="gorusme">👥 Görüşme</option>
                                <option value="arama">📞 Arama</option>
                                <option value="ziyaret">🏠 Saha Ziyareti</option>
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
                            <label>Alıcı Müşteri Seçin</label>
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
                            <label>Satıcı Müşteri Seçin</label>
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
                    <div class="form-section">
                        <h3>📅 Tarih ve Saat</h3>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="tum_gun" id="tum_gun" onchange="toggleTimeInputs()">
                            <label for="tum_gun">Tüm gün etkinliği</label>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Başlangıç Tarihi *</label>
                                <input type="date" name="baslangic_tarih" value="<?php echo $selected_date; ?>" required>
                            </div>
                            
                            <div class="form-group" id="baslangic_saat_group">
                                <label>Başlangıç Saati *</label>
                                <input type="time" name="baslangic_saat" value="09:00" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Bitiş Tarihi</label>
                                <input type="date" name="bitis_tarih" value="<?php echo $selected_date; ?>">
                            </div>
                            
                            <div class="form-group" id="bitis_saat_group">
                                <label>Bitiş Saati</label>
                                <input type="time" name="bitis_saat" value="10:00">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detaylar -->
                    <div class="form-section">
                        <h3>📝 Detaylar</h3>
                        
                        <div class="form-group">
                            <label>Açıklama</label>
                            <textarea name="aciklama" placeholder="Etkinlik hakkında notlar..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Konum</label>
                            <input type="text" name="konum" placeholder="Örn: Ofis, Müşteri evi, Arsa lokasyonu...">
                        </div>
                        
                        <?php if($current_user_role == 'admin' && count($users) > 0): ?>
                        <div class="form-group">
                            <label>Atanan Danışman</label>
                            <select name="atanan_user_id">
                                <option value="<?php echo $current_user_id; ?>">Kendim</option>
                                <?php foreach($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo $user['full_name'] ?: $user['username']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Renk Seçimi</label>
                            <div class="color-picker">
                                <div class="color-option selected" style="background: #3498db;" onclick="selectColor('#3498db')"></div>
                                <div class="color-option" style="background: #27ae60;" onclick="selectColor('#27ae60')"></div>
                                <div class="color-option" style="background: #e67e22;" onclick="selectColor('#e67e22')"></div>
                                <div class="color-option" style="background: #9b59b6;" onclick="selectColor('#9b59b6')"></div>
                                <div class="color-option" style="background: #e74c3c;" onclick="selectColor('#e74c3c')"></div>
                                <div class="color-option" style="background: #f39c12;" onclick="selectColor('#f39c12')"></div>
                                <div class="color-option" style="background: #34495e;" onclick="selectColor('#34495e')"></div>
                            </div>
                            <input type="hidden" name="renk" id="renk" value="#3498db">
                        </div>
                    </div>
                    
                    <!-- Hatırlatma -->
                    <div class="form-section">
                        <h3>🔔 Hatırlatma</h3>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="hatirlatma" id="hatirlatma" checked>
                            <label for="hatirlatma">Hatırlatma oluştur</label>
                        </div>
                        
                        <div class="form-group" id="hatirlatma_group">
                            <label>Ne kadar önce hatırlatılsın?</label>
                            <select name="hatirlatma_zaman">
                                <option value="15">15 dakika önce</option>
                                <option value="30" selected>30 dakika önce</option>
                                <option value="60">1 saat önce</option>
                                <option value="120">2 saat önce</option>
                                <option value="1440">1 gün önce</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="submit-buttons">
                        <button type="submit" class="btn btn-primary">💾 Kaydet</button>
                        <a href="takvim.php" class="btn btn-secondary">İptal</a>
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
    
    function toggleTimeInputs() {
        const tumGun = document.getElementById('tum_gun').checked;
        const baslangicSaat = document.getElementById('baslangic_saat_group');
        const bitisSaat = document.getElementById('bitis_saat_group');
        
        if(tumGun) {
            baslangicSaat.style.display = 'none';
            bitisSaat.style.display = 'none';
            baslangicSaat.querySelector('input').removeAttribute('required');
        } else {
            baslangicSaat.style.display = 'block';
            bitisSaat.style.display = 'block';
            baslangicSaat.querySelector('input').setAttribute('required', 'required');
        }
    }
    
    function selectColor(color) {
        // Tüm renklerin seçimini kaldır
        document.querySelectorAll('.color-option').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Seçilen rengi işaretle
        event.target.classList.add('selected');
        document.getElementById('renk').value = color;
    }
    
    // Hatırlatma checkbox kontrolü
    document.getElementById('hatirlatma').addEventListener('change', function() {
        const hatirlatmaGroup = document.getElementById('hatirlatma_group');
        if(this.checked) {
            hatirlatmaGroup.style.display = 'block';
        } else {
            hatirlatmaGroup.style.display = 'none';
        }
    });
    </script>
</body>
</html>