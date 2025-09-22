<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';

// Form gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $telefon = preg_replace('/[^0-9]/', '', $_POST['telefon']);
    
    // Telefon başında 0 varsa kaldır
    if(substr($telefon, 0, 1) == '0') {
        $telefon = substr($telefon, 1);
    }
    
    $adres = trim($_POST['adres']);
    $email = trim($_POST['email']);
    $facebook = trim($_POST['facebook']);
    $instagram = trim($_POST['instagram']);
    $twitter = trim($_POST['twitter']);
    $tasinmaz_adresi = trim($_POST['tasinmaz_adresi']);
    $ada = trim($_POST['ada']);
    $parsel = trim($_POST['parsel']);
    $tasinmaz_cinsi = trim($_POST['tasinmaz_cinsi']);
    $sahibinden_link = trim($_POST['sahibinden_link']);
    $sahibinden_no = trim($_POST['sahibinden_no']);
    $dusunceler = trim($_POST['dusunceler']);
    $notlar = trim($_POST['notlar']);
    
    try {
        $sql = "INSERT INTO crm_satici_musteriler (
            ad, soyad, telefon, adres, email,
            facebook, instagram, twitter,
            tasinmaz_adresi, ada, parsel, tasinmaz_cinsi,
            sahibinden_link, sahibinden_no, dusunceler, notlar,
            ekleyen_user_id, ekleyen_user_adi
        ) VALUES (
            :ad, :soyad, :telefon, :adres, :email,
            :facebook, :instagram, :twitter,
            :tasinmaz_adresi, :ada, :parsel, :tasinmaz_cinsi,
            :sahibinden_link, :sahibinden_no, :dusunceler, :notlar,
            :ekleyen_user_id, :ekleyen_user_adi
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':ad' => $ad,
            ':soyad' => $soyad,
            ':telefon' => $telefon,
            ':adres' => $adres,
            ':email' => $email,
            ':facebook' => $facebook,
            ':instagram' => $instagram,
            ':twitter' => $twitter,
            ':tasinmaz_adresi' => $tasinmaz_adresi,
            ':ada' => $ada,
            ':parsel' => $parsel,
            ':tasinmaz_cinsi' => $tasinmaz_cinsi,
            ':sahibinden_link' => $sahibinden_link,
            ':sahibinden_no' => $sahibinden_no,
            ':dusunceler' => $dusunceler,
            ':notlar' => $notlar,
            ':ekleyen_user_id' => $current_user_id,
            ':ekleyen_user_adi' => $current_user_name
        ]);
        
        // Arama sayısını 1 olarak başlat
        $musteri_id = $db->lastInsertId();
        $db->exec("UPDATE crm_satici_musteriler SET arama_sayisi = 1 WHERE id = $musteri_id");
        
        $_SESSION['success_message'] = "Satıcı müşteri başarıyla eklendi!";
        header("Location: satici-liste.php");
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
    <title>Satıcı Müşteri Ekle - CRM</title>
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
            border-top: 2px solid #e67e22;
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
            background: #e67e22;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .required {
            color: red;
        }
        .phone-format {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .info-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h4 {
            margin-top: 0;
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
            <div class="form-container">
                <h2>🏠 Satıcı Müşteri Ekle</h2>
                
                <div class="info-box">
                    <h4>Satıcı Müşteri Nedir?</h4>
                    <p>Fizbo araması yapılan ve konutunu satmak isteyen müşterilerdir. Bu form ile potansiyel satıcıları sisteme kaydedebilirsiniz.</p>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <!-- Kişisel Bilgiler -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Ad <span class="required">*</span></label>
                            <input type="text" name="ad" required>
                        </div>
                        <div class="form-group">
                            <label>Soyad <span class="required">*</span></label>
                            <input type="text" name="soyad" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Telefon <span class="required">*</span></label>
                            <input type="tel" name="telefon" placeholder="5551234567" required>
                            <div class="phone-format">Başında 0 olmadan yazın</div>
                        </div>
                        <div class="form-group">
                            <label>E-posta</label>
                            <input type="email" name="email">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Adres</label>
                        <textarea name="adres"></textarea>
                    </div>
                    
                    <!-- Sosyal Medya -->
                    <div class="form-section">
                        <h3>📱 Sosyal Medya</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Facebook</label>
                                <input type="text" name="facebook" placeholder="facebook.com/kullanici">
                            </div>
                            <div class="form-group">
                                <label>Instagram</label>
                                <input type="text" name="instagram" placeholder="@kullanici">
                            </div>
                            <div class="form-group">
                                <label>Twitter</label>
                                <input type="text" name="twitter" placeholder="@kullanici">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Taşınmaz Bilgileri -->
                    <div class="form-section">
                        <h3>🏘️ Taşınmaz Bilgileri</h3>
                        
                        <div class="form-group">
                            <label>Taşınmaz Adresi</label>
                            <textarea name="tasinmaz_adresi" placeholder="Taşınmazın tam adresi..."></textarea>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Ada</label>
                                <input type="text" name="ada" placeholder="Ada numarası">
                            </div>
                            <div class="form-group">
                                <label>Parsel</label>
                                <input type="text" name="parsel" placeholder="Parsel numarası">
                            </div>
                            <div class="form-group">
                                <label>Taşınmaz Cinsi</label>
                                <select name="tasinmaz_cinsi">
                                    <option value="">Seçiniz</option>
                                    <option value="Daire">Daire</option>
                                    <option value="Müstakil Ev">Müstakil Ev</option>
                                    <option value="Villa">Villa</option>
                                    <option value="Arsa">Arsa</option>
                                    <option value="Tarla">Tarla</option>
                                    <option value="Dükkan">Dükkan</option>
                                    <option value="Ofis">Ofis</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Sahibinden.com İlan Linki</label>
                                <input type="url" name="sahibinden_link" placeholder="https://www.sahibinden.com/ilan/...">
                            </div>
                            <div class="form-group">
                                <label>Sahibinden İlan No</label>
                                <input type="text" name="sahibinden_no" placeholder="İlan numarası">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Görüşme Notları -->
                    <div class="form-section">
                        <h3>📝 Görüşme Bilgileri</h3>
                        
                        <div class="form-group">
                            <label>Müşteri Düşünceleri</label>
                            <textarea name="dusunceler" placeholder="Müşterinin satış hakkındaki düşünceleri, fiyat beklentisi vb..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Danışman Notları</label>
                            <textarea name="notlar" placeholder="Müşteri hakkında özel notlar, dikkat edilmesi gerekenler..."></textarea>
                        </div>
                    </div>
                    
                    <div class="submit-buttons">
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                        <a href="index.php" class="btn btn-secondary">İptal</a>
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
    </script>
</body>
</html>