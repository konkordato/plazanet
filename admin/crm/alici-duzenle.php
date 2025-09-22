<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';

$musteri_id = $_GET['id'] ?? 0;
if(!$musteri_id) {
    header("Location: alici-liste.php");
    exit();
}

// Müşteri bilgilerini çek
$sql = "SELECT * FROM crm_alici_musteriler WHERE id = :id";
if($current_user_role != 'admin') {
    $sql .= " AND ekleyen_user_id = :user_id";
}

$stmt = $db->prepare($sql);
$params = [':id' => $musteri_id];
if($current_user_role != 'admin') {
    $params[':user_id'] = $current_user_id;
}
$stmt->execute($params);
$musteri = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$musteri) {
    header("Location: alici-liste.php");
    exit();
}

// Form gönderildiyse güncelle
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $telefon = preg_replace('/[^0-9]/', '', $_POST['telefon']);
    
    if(substr($telefon, 0, 1) == '0') {
        $telefon = substr($telefon, 1);
    }
    
    $adres = trim($_POST['adres']);
    $email = trim($_POST['email']);
    $is_bilgisi = trim($_POST['is_bilgisi']);
    $facebook = trim($_POST['facebook']);
    $instagram = trim($_POST['instagram']);
    $twitter = trim($_POST['twitter']);
    $aranan_tasinmaz = $_POST['aranan_tasinmaz'];
    $aranan_il = trim($_POST['aranan_il']);
    $aranan_ilce = trim($_POST['aranan_ilce']);
    $aranan_mahalle = trim($_POST['aranan_mahalle']);
    $aranan_koy = trim($_POST['aranan_koy']);
    $min_butce = str_replace(['.', ','], ['', '.'], $_POST['min_butce']);
    $max_butce = str_replace(['.', ','], ['', '.'], $_POST['max_butce']);
    $notlar = trim($_POST['notlar']);
    $durum = $_POST['durum'] ?? 'aktif';
    
    try {
        $update_sql = "UPDATE crm_alici_musteriler SET 
            ad = :ad, soyad = :soyad, telefon = :telefon,
            adres = :adres, email = :email, is_bilgisi = :is_bilgisi,
            facebook = :facebook, instagram = :instagram, twitter = :twitter,
            aranan_tasinmaz = :aranan_tasinmaz, aranan_il = :aranan_il,
            aranan_ilce = :aranan_ilce, aranan_mahalle = :aranan_mahalle,
            aranan_koy = :aranan_koy, min_butce = :min_butce,
            max_butce = :max_butce, notlar = :notlar, durum = :durum,
            guncelleme_tarihi = NOW()
            WHERE id = :id";
        
        if($current_user_role != 'admin') {
            $update_sql .= " AND ekleyen_user_id = :user_id";
        }
        
        $update_stmt = $db->prepare($update_sql);
        $update_params = [
            ':ad' => $ad,
            ':soyad' => $soyad,
            ':telefon' => $telefon,
            ':adres' => $adres,
            ':email' => $email,
            ':is_bilgisi' => $is_bilgisi,
            ':facebook' => $facebook,
            ':instagram' => $instagram,
            ':twitter' => $twitter,
            ':aranan_tasinmaz' => $aranan_tasinmaz,
            ':aranan_il' => $aranan_il,
            ':aranan_ilce' => $aranan_ilce,
            ':aranan_mahalle' => $aranan_mahalle,
            ':aranan_koy' => $aranan_koy,
            ':min_butce' => $min_butce,
            ':max_butce' => $max_butce,
            ':notlar' => $notlar,
            ':durum' => $durum,
            ':id' => $musteri_id
        ];
        
        if($current_user_role != 'admin') {
            $update_params[':user_id'] = $current_user_id;
        }
        
        $update_stmt->execute($update_params);
        
        $_SESSION['success_message'] = "Alıcı müşteri başarıyla güncellendi!";
        header("Location: alici-liste.php");
        exit();
        
    } catch(PDOException $e) {
        $error_message = "Güncelleme hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alıcı Müşteri Düzenle - CRM</title>
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
            padding: 20px;
        }
        
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .form-section {
            border-top: 2px solid #3498db;
            padding-top: 20px;
            margin-top: 30px;
        }
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
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
        }
        .btn-primary {
            background: #27ae60;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
            float: right;
        }
        .required {
            color: red;
        }
        .phone-format {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .status-select {
            padding: 8px;
            border-radius: 5px;
            border: 2px solid #ddd;
        }
        .status-select.aktif {
            border-color: #27ae60;
            background: #e8f5e9;
        }
        .status-select.pasif {
            border-color: #e74c3c;
            background: #ffebee;
        }
        .info-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            font-size: 12px;
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
                    <h2>✏️ Alıcı Müşteri Düzenle</h2>
                    <span class="info-badge">
                        Ekleyen: <?php echo $musteri['ekleyen_user_adi']; ?> | 
                        <?php echo date('d.m.Y', strtotime($musteri['ekleme_tarihi'])); ?>
                    </span>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <!-- Durum -->
                    <div class="form-group">
                        <label>Durum</label>
                        <select name="durum" class="status-select <?php echo $musteri['durum']; ?>">
                            <option value="aktif" <?php echo $musteri['durum'] == 'aktif' ? 'selected' : ''; ?>>✅ Aktif</option>
                            <option value="pasif" <?php echo $musteri['durum'] == 'pasif' ? 'selected' : ''; ?>>❌ Pasif</option>
                        </select>
                    </div>
                    
                    <!-- Kişisel Bilgiler -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Ad <span class="required">*</span></label>
                            <input type="text" name="ad" value="<?php echo htmlspecialchars($musteri['ad']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Soyad <span class="required">*</span></label>
                            <input type="text" name="soyad" value="<?php echo htmlspecialchars($musteri['soyad']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Telefon <span class="required">*</span></label>
                            <input type="tel" name="telefon" value="<?php echo $musteri['telefon']; ?>" required>
                            <div class="phone-format">Başında 0 olmadan yazın</div>
                        </div>
                        <div class="form-group">
                            <label>E-posta</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($musteri['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Adres</label>
                        <textarea name="adres"><?php echo htmlspecialchars($musteri['adres']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>İş Bilgisi</label>
                        <input type="text" name="is_bilgisi" value="<?php echo htmlspecialchars($musteri['is_bilgisi']); ?>">
                    </div>
                    
                    <!-- Sosyal Medya -->
                    <div class="form-section">
                        <h3>📱 Sosyal Medya</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Facebook</label>
                                <input type="text" name="facebook" value="<?php echo htmlspecialchars($musteri['facebook']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Instagram</label>
                                <input type="text" name="instagram" value="<?php echo htmlspecialchars($musteri['instagram']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Twitter</label>
                                <input type="text" name="twitter" value="<?php echo htmlspecialchars($musteri['twitter']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aradığı Taşınmaz -->
                    <div class="form-section">
                        <h3>🏠 Aradığı Taşınmaz Bilgileri</h3>
                        <div class="form-group">
                            <label>Taşınmaz Cinsi</label>
                            <select name="aranan_tasinmaz">
                                <option value="KONUT" <?php echo $musteri['aranan_tasinmaz'] == 'KONUT' ? 'selected' : ''; ?>>Konut</option>
                                <option value="ARSA" <?php echo $musteri['aranan_tasinmaz'] == 'ARSA' ? 'selected' : ''; ?>>Arsa</option>
                                <option value="TARLA" <?php echo $musteri['aranan_tasinmaz'] == 'TARLA' ? 'selected' : ''; ?>>Tarla</option>
                                <option value="DİĞER" <?php echo $musteri['aranan_tasinmaz'] == 'DİĞER' ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>İl</label>
                                <input type="text" name="aranan_il" value="<?php echo htmlspecialchars($musteri['aranan_il']); ?>">
                            </div>
                            <div class="form-group">
                                <label>İlçe</label>
                                <input type="text" name="aranan_ilce" value="<?php echo htmlspecialchars($musteri['aranan_ilce']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Mahalle</label>
                                <input type="text" name="aranan_mahalle" value="<?php echo htmlspecialchars($musteri['aranan_mahalle']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Köy</label>
                                <input type="text" name="aranan_koy" value="<?php echo htmlspecialchars($musteri['aranan_koy']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Min Bütçe (TL)</label>
                                <input type="text" name="min_butce" value="<?php echo number_format($musteri['min_butce'], 0, ',', '.'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Max Bütçe (TL)</label>
                                <input type="text" name="max_butce" value="<?php echo number_format($musteri['max_butce'], 0, ',', '.'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notlar -->
                    <div class="form-section">
                        <h3>📝 Notlar</h3>
                        <div class="form-group">
                            <textarea name="notlar"><?php echo htmlspecialchars($musteri['notlar']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="submit-buttons">
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                        <a href="alici-liste.php" class="btn btn-secondary">İptal</a>
                        <?php if($current_user_role == 'admin'): ?>
                        <button type="button" onclick="deleteCustomer()" class="btn btn-danger">Sil</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Telefon formatı kontrolü
    document.querySelector('input[name="telefon"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // Bütçe formatı
    document.querySelectorAll('input[name="min_butce"], input[name="max_butce"]').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if(value) {
                this.value = new Intl.NumberFormat('tr-TR').format(value);
            }
        });
    });
    
    // Durum değişikliği
    document.querySelector('select[name="durum"]').addEventListener('change', function() {
        this.className = 'status-select ' + this.value;
    });
    
    <?php if($current_user_role == 'admin'): ?>
    function deleteCustomer() {
        if(confirm('Bu müşteriyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
            if(confirm('Tüm görüşme notları ve kayıtlar silinecek. Devam etmek istiyor musunuz?')) {
                window.location.href = 'musteri-sil.php?tip=alici&id=<?php echo $musteri_id; ?>';
            }
        }
    }
    <?php endif; ?>
    </script>
</body>
</html>