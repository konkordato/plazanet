<?php
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Sadece normal kullanıcılar bu sayfayı kullanabilir
if ($_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_fullname'];

// İlan ID kontrolü
$property_id = $_GET['id'] ?? 0;
if (!$property_id) {
    $_SESSION['error'] = "Geçersiz ilan ID";
    header("Location: my-properties.php");
    exit();
}

// İlanın kullanıcıya ait olduğunu kontrol et
$stmt = $db->prepare("SELECT * FROM properties WHERE id = :id AND user_id = :user_id");
$stmt->execute([':id' => $property_id, ':user_id' => $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    $_SESSION['error'] = "Bu ilan size ait değil veya bulunamadı!";
    header("Location: my-properties.php");
    exit();
}

// İlçeleri çek (İstanbul için)
$ilceler = $db->query("SELECT DISTINCT ilce FROM properties ORDER BY ilce")->fetchAll(PDO::FETCH_COLUMN);

// Mevcut resimleri çek
$images_stmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY is_main DESC, id ASC");
$images_stmt->execute([':id' => $property_id]);
$existing_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderilmişse güncelle
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        // Fiyat değerini temizle (virgül ve nokta kontrolü)
        $fiyat = str_replace(['.', ','], ['', '.'], $_POST['fiyat']);

        // İlan bilgilerini güncelle
        $sql = "UPDATE properties SET 
                baslik = :baslik,
                aciklama = :aciklama,
                fiyat = :fiyat,
                kategori = :kategori,
                emlak_tipi = :emlak_tipi,
                oda_sayisi = :oda_sayisi,
                brut_metrekare = :brut_metrekare,
                net_metrekare = :net_metrekare,
                bina_yasi = :bina_yasi,
                bulundugu_kat = :bulundugu_kat,
                kat_sayisi = :kat_sayisi,
                isitma = :isitma,
                banyo_sayisi = :banyo_sayisi,
                balkon = :balkon,
                mutfak = :mutfak,
                asansor = :asansor,
                otopark = :otopark,
                site_adi = :site_adi,
                esyali = :esyali,
                kullanim_durumu = :kullanim_durumu,
                site_icerisinde = :site_icerisinde,
                aidat = :aidat,
                ilce = :ilce,
                mahalle = :mahalle,
                adres = :adres,
                takas = :takas,
                krediye_uygun = :krediye_uygun
                WHERE id = :id AND user_id = :user_id";

        $update_stmt = $db->prepare($sql);
        $update_stmt->execute([
            ':baslik' => $_POST['baslik'],
            ':aciklama' => $_POST['aciklama'],
            ':fiyat' => $fiyat,
            ':kategori' => $_POST['kategori'],
            ':emlak_tipi' => $_POST['emlak_tipi'] ?? $property['emlak_tipi'],
            ':oda_sayisi' => $_POST['oda_sayisi'] ?? null,
            ':brut_metrekare' => $_POST['brut_metrekare'] ?? $_POST['metrekare'] ?? null,
            ':net_metrekare' => $_POST['net_metrekare'] ?? null,
            ':bina_yasi' => $_POST['bina_yasi'] ?? null,
            ':bulundugu_kat' => $_POST['bulundugu_kat'] ?? null,
            ':kat_sayisi' => $_POST['kat_sayisi'] ?? null,
            ':isitma' => $_POST['isitma'] ?? null,
            ':banyo_sayisi' => $_POST['banyo_sayisi'] ?? null,
            ':balkon' => $_POST['balkon'] ?? 'Hayır',
            ':mutfak' => $_POST['mutfak'] ?? null,
            ':asansor' => $_POST['asansor'] ?? null,
            ':otopark' => $_POST['otopark'] ?? null,
            ':site_adi' => $_POST['site_adi'] ?? null,
            ':esyali' => $_POST['esyali'] ?? 'Hayır',
            ':kullanim_durumu' => $_POST['kullanim_durumu'] ?? 'Boş',
            ':site_icerisinde' => $_POST['site_icerisinde'] ?? 'Hayır',
            ':aidat' => $_POST['aidat'] ?? null,
            ':ilce' => $_POST['ilce'],
            ':mahalle' => $_POST['mahalle'],
            ':adres' => $_POST['adres'] ?? '',
            ':takas' => $_POST['takas'] ?? 'Hayır',
            ':krediye_uygun' => $_POST['krediye_uygun'] ?? 'Evet',
            ':id' => $property_id,
            ':user_id' => $user_id
        ]);

        // Yeni resim yükleme
        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $upload_dir = '../uploads/properties/';
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

            foreach ($_FILES['new_images']['name'] as $key => $filename) {
                if (empty($filename)) continue;

                $file_type = $_FILES['new_images']['type'][$key];
                $file_tmp = $_FILES['new_images']['tmp_name'][$key];
                $file_size = $_FILES['new_images']['size'][$key];

                // Dosya tipi kontrolü
                if (!in_array($file_type, $allowed_types)) {
                    continue;
                }

                // Dosya boyutu kontrolü (5MB)
                if ($file_size > 5242880) {
                    continue;
                }

                // Benzersiz dosya adı oluştur
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $new_filename = 'property_' . $property_id . '_' . uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Veritabanına kaydet
                    $image_path = 'uploads/properties/' . $new_filename;
                    $insert_img = $db->prepare("INSERT INTO property_images (property_id, image_path, is_main) VALUES (:pid, :path, 0)");
                    $insert_img->execute([':pid' => $property_id, ':path' => $image_path]);
                }
            }
        }

        // Ana resim değişikliği
        if (isset($_POST['main_image_id'])) {
            // Önce tüm resimlerin is_main değerini 0 yap
            $db->prepare("UPDATE property_images SET is_main = 0 WHERE property_id = :id")
                ->execute([':id' => $property_id]);

            // Seçilen resmi ana resim yap
            $db->prepare("UPDATE property_images SET is_main = 1 WHERE id = :img_id AND property_id = :pid")
                ->execute([':img_id' => $_POST['main_image_id'], ':pid' => $property_id]);
        }

        // Resim silme işlemi
        if (isset($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $img_id) {
                // Resim bilgisini al
                $img_stmt = $db->prepare("SELECT image_path FROM property_images WHERE id = :id AND property_id = :pid");
                $img_stmt->execute([':id' => $img_id, ':pid' => $property_id]);
                $img = $img_stmt->fetch();

                if ($img) {
                    // Dosyayı sil
                    $file_path = '../' . $img['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }

                    // Veritabanından sil
                    $db->prepare("DELETE FROM property_images WHERE id = :id")
                        ->execute([':id' => $img_id]);
                }
            }
        }

        $db->commit();
        $_SESSION['success'] = "İlan başarıyla güncellendi!";
        header("Location: my-property-edit.php?id=" . $property_id);
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Güncelleme sırasında hata oluştu: " . $e->getMessage();
    }
}

// Güncel verileri tekrar çek (form gönderildiyse)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $db->prepare("SELECT * FROM properties WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $property_id, ':user_id' => $user_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    $images_stmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY is_main DESC, id ASC");
    $images_stmt->execute([':id' => $property_id]);
    $existing_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Düzenle - <?php echo htmlspecialchars($property['baslik']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        input[type="tel"],
        select,
        textarea {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

        /* Resim Yönetimi */
        .image-section {
            margin-top: 30px;
        }

        .existing-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .image-item {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }

        .image-item.main-image {
            border-color: #27ae60;
            box-shadow: 0 0 10px rgba(39, 174, 96, 0.3);
        }

        .image-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .image-controls {
            padding: 10px;
            background: #f8f9fa;
        }

        .main-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .delete-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #e74c3c;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #3498db;
        }

        /* Butonlar */
        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        /* Mesajlar */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* Upload alanı */
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .upload-area:hover {
            border-color: #3498db;
            background: #fff;
        }

        .upload-label {
            cursor: pointer;
            color: #3498db;
            font-weight: 500;
        }

        .upload-input {
            display: none;
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
                    <a href="user-dashboard.php">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="my-properties.php">
                        <span class="icon">🏢</span>
                        <span>İlanlarım</span>
                    </a>
                </li>
                <li>
                    <a href="properties/add-step1.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="my-profile.php">
                        <span class="icon">👤</span>
                        <span>Profilim</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>İlan Düzenle</h3>
                </div>
                <div class="navbar-right">
                    <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Mesajlar -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Düzenleme Formu -->
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <!-- Temel Bilgiler -->
                    <div class="form-section">
                        <h2 class="section-title">📝 Temel Bilgiler</h2>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>İlan Başlığı *</label>
                                <input type="text" name="baslik" value="<?php echo htmlspecialchars($property['baslik']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Kategori *</label>
                                <select name="kategori" required>
                                    <option value="Satılık" <?php echo $property['kategori'] == 'Satılık' ? 'selected' : ''; ?>>Satılık</option>
                                    <option value="Kiralık" <?php echo $property['kategori'] == 'Kiralık' ? 'selected' : ''; ?>>Kiralık</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Emlak Tipi</label>
                                <select name="emlak_tipi">
                                    <option value="Daire" <?php echo $property['emlak_tipi'] == 'Daire' ? 'selected' : ''; ?>>Daire</option>
                                    <option value="Villa" <?php echo $property['emlak_tipi'] == 'Villa' ? 'selected' : ''; ?>>Villa</option>
                                    <option value="Ofis" <?php echo $property['emlak_tipi'] == 'Ofis' ? 'selected' : ''; ?>>Ofis</option>
                                    <option value="Dükkan" <?php echo $property['emlak_tipi'] == 'Dükkan' ? 'selected' : ''; ?>>Dükkan</option>
                                    <option value="Arsa" <?php echo $property['emlak_tipi'] == 'Arsa' ? 'selected' : ''; ?>>Arsa</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Fiyat (TL) *</label>
                                <input type="text" name="fiyat" value="<?php echo number_format($property['fiyat'], 0, ',', '.'); ?>" required>
                            </div>

                            <div class="form-group full-width">
                                <label>Açıklama</label>
                                <textarea name="aciklama"><?php echo htmlspecialchars($property['aciklama']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Detay Bilgileri -->
                    <div class="form-section">
                        <h2 class="section-title">🏠 Detay Bilgileri</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Oda Sayısı</label>
                                <input type="text" name="oda_sayisi" value="<?php echo htmlspecialchars($property['oda_sayisi']); ?>">
                            </div>

                            <div class="form-group">
                                <label>Brüt m²</label>
                                <input type="number" name="brut_metrekare" value="<?php echo $property['brut_metrekare'] ?? $property['metrekare']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Net m²</label>
                                <input type="number" name="net_metrekare" value="<?php echo $property['net_metrekare']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Bina Yaşı</label>
                                <input type="number" name="bina_yasi" value="<?php echo $property['bina_yasi']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Bulunduğu Kat</label>
                                <input type="text" name="bulundugu_kat" value="<?php echo $property['bulundugu_kat']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Kat Sayısı</label>
                                <input type="number" name="kat_sayisi" value="<?php echo $property['kat_sayisi']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Isıtma</label>
                                <input type="text" name="isitma" value="<?php echo htmlspecialchars($property['isitma']); ?>">
                            </div>

                            <div class="form-group">
                                <label>Banyo Sayısı</label>
                                <input type="number" name="banyo_sayisi" value="<?php echo $property['banyo_sayisi']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Aidat (TL)</label>
                                <input type="number" name="aidat" value="<?php echo $property['aidat']; ?>">
                            </div>
                            <!-- Aidat alanından sonra eklenecek -->
                            <div class="form-group">
                                <label>Mutfak</label>
                                <select name="mutfak">
                                    <option value="">Seçiniz</option>
                                    <option value="Açık" <?php echo ($property['mutfak'] ?? '') == 'Açık' ? 'selected' : ''; ?>>Açık</option>
                                    <option value="Kapalı" <?php echo ($property['mutfak'] ?? '') == 'Kapalı' ? 'selected' : ''; ?>>Kapalı</option>
                                    <option value="Amerikan" <?php echo ($property['mutfak'] ?? '') == 'Amerikan' ? 'selected' : ''; ?>>Amerikan</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Asansör</label>
                                <select name="asansor">
                                    <option value="">Seçiniz</option>
                                    <option value="Var" <?php echo ($property['asansor'] ?? '') == 'Var' ? 'selected' : ''; ?>>Var</option>
                                    <option value="Yok" <?php echo ($property['asansor'] ?? '') == 'Yok' ? 'selected' : ''; ?>>Yok</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Otopark</label>
                                <select name="otopark">
                                    <option value="">Seçiniz</option>
                                    <option value="Yok" <?php echo ($property['otopark'] ?? 'Yok') == 'Yok' ? 'selected' : ''; ?>>Yok</option>
                                    <option value="Açık" <?php echo ($property['otopark'] ?? '') == 'Açık' ? 'selected' : ''; ?>>Açık Otopark</option>
                                    <option value="Kapalı" <?php echo ($property['otopark'] ?? '') == 'Kapalı' ? 'selected' : ''; ?>>Kapalı Otopark</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Site Adı</label>
                                <input type="text" name="site_adi" value="<?php echo htmlspecialchars($property['site_adi'] ?? ''); ?>" placeholder="Site içindeyse adını yazın">
                            </div>
                        </div>

                        <!-- Checkbox alanları -->
                        <div class="form-grid" style="margin-top: 20px;">
                            <div class="checkbox-group">
                                <input type="checkbox" id="balkon" name="balkon" value="Evet"
                                    <?php echo $property['balkon'] == 'Evet' ? 'checked' : ''; ?>>
                                <label for="balkon">Balkon</label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="esyali" name="esyali" value="Evet"
                                    <?php echo $property['esyali'] == 'Evet' ? 'checked' : ''; ?>>
                                <label for="esyali">Eşyalı</label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="site_icerisinde" name="site_icerisinde" value="Evet"
                                    <?php echo $property['site_icerisinde'] == 'Evet' ? 'checked' : ''; ?>>
                                <label for="site_icerisinde">Site İçerisinde</label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="krediye_uygun" name="krediye_uygun" value="Evet"
                                    <?php echo ($property['krediye_uygun'] ?? 'Evet') == 'Evet' ? 'checked' : ''; ?>>
                                <label for="krediye_uygun">Krediye Uygun</label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="takas" name="takas" value="Evet"
                                    <?php echo $property['takas'] == 'Evet' ? 'checked' : ''; ?>>
                                <label for="takas">Takas</label>
                            </div>
                        </div>
                    </div>

                    <!-- Lokasyon Bilgileri -->
                    <div class="form-section">
                        <h2 class="section-title">📍 Lokasyon Bilgileri</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>İlçe *</label>
                                <select name="ilce" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($ilceler as $ilce): ?>
                                        <option value="<?php echo $ilce; ?>" <?php echo $property['ilce'] == $ilce ? 'selected' : ''; ?>>
                                            <?php echo $ilce; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Mahalle *</label>
                                <input type="text" name="mahalle" value="<?php echo htmlspecialchars($property['mahalle']); ?>" required>
                            </div>

                            <div class="form-group full-width">
                                <label>Adres</label>
                                <textarea name="adres" rows="2"><?php echo htmlspecialchars($property['adres']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Resim Yönetimi -->
                    <div class="form-section image-section">
                        <h2 class="section-title">📷 Resimler</h2>

                        <?php if (!empty($existing_images)): ?>
                            <p style="margin-bottom: 15px; color: #666;">
                                Mevcut resimler (Ana resmi değiştirebilir veya silebilirsiniz):
                            </p>
                            <div class="existing-images">
                                <?php foreach ($existing_images as $img): ?>
                                    <div class="image-item <?php echo $img['is_main'] ? 'main-image' : ''; ?>">
                                        <?php if ($img['is_main']): ?>
                                            <span class="main-badge">ANA RESİM</span>
                                        <?php endif; ?>
                                        <img src="../<?php echo $img['image_path']; ?>" alt="İlan Resmi">
                                        <div class="image-controls">
                                            <label class="radio-label">
                                                <input type="radio" name="main_image_id" value="<?php echo $img['id']; ?>"
                                                    <?php echo $img['is_main'] ? 'checked' : ''; ?>>
                                                Ana Resim Yap
                                            </label>
                                            <label class="delete-checkbox">
                                                <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>">
                                                Sil
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #999; text-align: center; padding: 20px;">
                                Bu ilana henüz resim eklenmemiş.
                            </p>
                        <?php endif; ?>

                        <!-- Yeni Resim Ekleme -->
                        <div class="upload-area" style="margin-top: 20px;">
                            <label for="new_images" class="upload-label">
                                📸 Yeni Resimler Ekle (Maks. 5MB, JPG/PNG)
                                <input type="file" id="new_images" name="new_images[]" multiple accept="image/*" class="upload-input">
                            </label>
                            <p style="margin-top: 10px; color: #999; font-size: 13px;">
                                Birden fazla resim seçebilirsiniz
                            </p>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-success">
                            💾 Değişiklikleri Kaydet
                        </button>
                        <a href="my-properties.php" class="btn btn-secondary">
                            ❌ İptal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>