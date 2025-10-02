<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {

    header("Location: ../index.php");

    exit();
}



require_once '../../config/database.php';



// ID kontrolü

$id = $_GET['id'] ?? 0;

if (!$id) {

    $_SESSION['error'] = "Geçersiz ilan ID";

    header("Location: list.php");

    exit();
}



// İlan bilgilerini çek

$stmt = $db->prepare("SELECT * FROM properties WHERE id = :id");

$stmt->execute([':id' => $id]);

$property = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$property) {

    $_SESSION['error'] = "İlan bulunamadı";

    header("Location: list.php");

    exit();
}



// Mevcut fotoğrafları çek

$stmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY is_main DESC, display_order ASC, id ASC");

$stmt->execute([':id' => $id]);

$images = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Form gönderilmişse güncelle

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

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

                il = :il,

                ilce = :ilce,

                mahalle = :mahalle,

                adres = :adres,

                durum = :durum,

                takas = :takas

                WHERE id = :id";



        $stmt = $db->prepare($sql);

        $stmt->execute([

            ':baslik' => $_POST['baslik'],

            ':aciklama' => $_POST['aciklama'],

            ':fiyat' => $_POST['fiyat'],

            ':kategori' => $_POST['kategori'],

            ':emlak_tipi' => $_POST['emlak_tipi'],

            ':oda_sayisi' => $_POST['oda_sayisi'] ?: null,

            ':brut_metrekare' => $_POST['brut_metrekare'],

            ':net_metrekare' => $_POST['net_metrekare'] ?: null,

            ':bina_yasi' => $_POST['bina_yasi'] ?: null,

            ':bulundugu_kat' => $_POST['bulundugu_kat'] ?: null,

            ':kat_sayisi' => $_POST['kat_sayisi'] ?: null,

            ':isitma' => $_POST['isitma'] ?: null,

            ':banyo_sayisi' => $_POST['banyo_sayisi'] ?: null,

            ':balkon' => $_POST['balkon'] ?: null,

            ':mutfak' => $_POST['mutfak'] ?: null,

            ':asansor' => $_POST['asansor'] ?: null,

            ':otopark' => $_POST['otopark'] ?: null,

            ':site_adi' => $_POST['site_adi'] ?: null,

            ':esyali' => $_POST['esyali'],

            ':kullanim_durumu' => $_POST['kullanim_durumu'] ?: null,

            ':site_icerisinde' => $_POST['site_icerisinde'],

            ':aidat' => $_POST['aidat'] ?: null,

            ':il' => $_POST['il'],

            ':ilce' => $_POST['ilce'],

            ':mahalle' => $_POST['mahalle'] ?: null,

            ':adres' => $_POST['adres'] ?: null,

            ':durum' => $_POST['durum'],

            ':takas' => $_POST['takas'],

            ':id' => $id

        ]);



        // YENİ FOTOĞRAF YÜKLEME

        if (isset($_FILES['new_photos']) && !empty($_FILES['new_photos']['name'][0])) {

            $uploadDir = '../../assets/uploads/properties/';



            if (!is_dir($uploadDir)) {

                mkdir($uploadDir, 0777, true);
            }



            $totalPhotos = count($_FILES['new_photos']['name']);

            $currentPhotoCount = count($images);



            for ($i = 0; $i < $totalPhotos; $i++) {

                if ($_FILES['new_photos']['error'][$i] == 0) {

                    $fileName = $_FILES['new_photos']['name'][$i];

                    $tempFile = $_FILES['new_photos']['tmp_name'][$i];

                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));



                    // Dosya tipi kontrolü

                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (!in_array($fileExt, $allowedTypes)) {

                        continue;
                    }



                    // Yeni dosya adı

                    $newFileName = 'prop_' . $id . '_' . time() . '_' . $i . '.' . $fileExt;

                    $uploadPath = $uploadDir . $newFileName;



                    if (move_uploaded_file($tempFile, $uploadPath)) {

                        // Veritabanına ekle

                        $isMain = ($currentPhotoCount == 0 && $i == 0) ? 1 : 0;

                        $dbPath = 'assets/uploads/properties/' . $newFileName;



                        $stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, image_name, is_main, display_order) 

                                            VALUES (:pid, :path, :name, :main, :order)");

                        $stmt->execute([

                            ':pid' => $id,

                            ':path' => $dbPath,

                            ':name' => $fileName,

                            ':main' => $isMain,

                            ':order' => $currentPhotoCount + $i

                        ]);
                    }
                }
            }
        }



        $_SESSION['success'] = "İlan başarıyla güncellendi!";

        header("Location: edit.php?id=" . $id);

        exit();
    } catch (PDOException $e) {

        $error = "Güncelleme hatası: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>

<html lang="tr">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>İlan Düzenle - <?php echo htmlspecialchars($property['baslik']); ?></title>

    <link rel="stylesheet" href="../../assets/css/admin.css">

    <link rel="stylesheet" href="../../assets/css/admin-form.css">

    <style>
        .edit-header {

            background: white;

            padding: 20px;

            margin-bottom: 20px;

            border-radius: 8px;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }



        .photo-section {

            background: #f8f9fa;

            padding: 20px;

            border-radius: 8px;

            margin-bottom: 20px;

        }



        .photo-section h2 {

            color: #333;

            margin-bottom: 20px;

            padding-bottom: 10px;

            border-bottom: 2px solid #3498db;

        }



        .existing-photos {

            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));

            gap: 15px;

            margin-bottom: 20px;

        }



        .photo-item {

            position: relative;

            border: 2px solid #ddd;

            border-radius: 8px;

            overflow: hidden;

            background: white;

        }



        .photo-item img {

            width: 100%;

            height: 120px;

            object-fit: cover;

        }



        .photo-item.main-photo {

            border-color: #27ae60;

        }



        .main-badge {

            position: absolute;

            top: 5px;

            left: 5px;

            background: #27ae60;

            color: white;

            padding: 2px 8px;

            border-radius: 4px;

            font-size: 11px;

        }



        .photo-actions {

            position: absolute;

            top: 5px;

            right: 5px;

            display: flex;

            gap: 5px;

        }



        .btn-photo {

            width: 30px;

            height: 30px;

            border: none;

            border-radius: 4px;

            cursor: pointer;

            font-size: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

        }



        .btn-delete-photo {

            background: rgba(231, 76, 60, 0.9);

            color: white;

        }



        .btn-main-photo {

            background: rgba(52, 152, 219, 0.9);

            color: white;

        }



        .btn-photo:hover {

            transform: scale(1.1);

        }



        .upload-new-photos {

            border: 2px dashed #3498db;

            padding: 30px;

            text-align: center;

            border-radius: 8px;

            background: white;

            cursor: pointer;

        }



        .upload-new-photos:hover {

            background: #f0f8ff;

        }



        .photo-count-info {

            background: #e8f4f8;

            padding: 10px;

            border-radius: 5px;

            margin-bottom: 15px;

            color: #2c3e50;

        }



        .no-photos {

            text-align: center;

            padding: 40px;

            color: #999;

            background: white;

            border-radius: 8px;

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

                <li><a href="../dashboard.php"><span class="icon">🏠</span><span>Ana Sayfa</span></a></li>

                <li><a href="list.php" class="active"><span class="icon">🏢</span><span>İlanlar</span></a></li>

                <li><a href="add-step1.php"><span class="icon">➕</span><span>İlan Ekle</span></a></li>

            </ul>

        </nav>



        <!-- Main Content -->

        <div class="main-content">

            <div class="top-navbar">

                <div class="navbar-left">

                    <h3>İlan Düzenle</h3>

                </div>

                <div class="navbar-right">

                    <a href="../logout.php" class="btn-logout">Çıkış</a>

                </div>

            </div>



            <div class="content">

                <?php if (isset($_SESSION['success'])): ?>

                    <div class="alert alert-success">

                        <?php echo $_SESSION['success'];

                        unset($_SESSION['success']); ?>

                    </div>

                <?php endif; ?>



                <?php if (isset($error)): ?>

                    <div class="alert alert-error"><?php echo $error; ?></div>

                <?php endif; ?>



                <!-- FOTOĞRAF YÖNETİMİ -->

                <div class="photo-section">

                    <h2>📷 Fotoğraf Yönetimi</h2>



                    <div class="photo-count-info">

                        💡 Mevcut fotoğraf sayısı: <strong><?php echo count($images); ?></strong> / 50

                    </div>



                    <?php if (count($images) > 0): ?>

                        <div class="existing-photos">

                            <?php foreach ($images as $img): ?>

                                <div class="photo-item <?php echo $img['is_main'] ? 'main-photo' : ''; ?>">

                                    <img src="../../<?php echo $img['image_path']; ?>" alt="Fotoğraf">

                                    <?php if ($img['is_main']): ?>

                                        <span class="main-badge">Ana Foto</span>

                                    <?php endif; ?>

                                    <div class="photo-actions">

                                        <?php if (!$img['is_main']): ?>

                                            <button onclick="setMainPhoto(<?php echo $img['id']; ?>)" class="btn-photo btn-main-photo" title="Ana fotoğraf yap">★</button>

                                        <?php endif; ?>

                                        <button onclick="deletePhoto(<?php echo $img['id']; ?>)" class="btn-photo btn-delete-photo" title="Sil">×</button>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="no-photos">

                            📷 Henüz fotoğraf eklenmemiş

                        </div>

                    <?php endif; ?>



                    <!-- Yeni Fotoğraf Ekleme -->

                    <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">

                        <div class="upload-new-photos">

                            <input type="file" name="new_photos[]" id="new_photos" multiple accept="image/*" style="display: none;">

                            <label for="new_photos" style="cursor: pointer;">

                                <div style="font-size: 48px;">📤</div>

                                <h3>Yeni Fotoğraf Ekle</h3>

                                <p>Buraya tıklayın veya dosyaları sürükleyin</p>

                                <small>Maksimum 50 fotoğraf, her biri en fazla 10MB</small>

                            </label>

                        </div>

                </div>



                <!-- İLAN BİLGİLERİ FORMU -->

                <form method="POST" action="" enctype="multipart/form-data">

                    <!-- Temel Bilgiler -->

                    <div class="form-section">

                        <h2 class="section-title">Temel Bilgiler</h2>



                        <div class="form-group">

                            <label class="required">İlan Başlığı</label>

                            <input type="text" name="baslik" value="<?php echo htmlspecialchars($property['baslik']); ?>" required>

                        </div>



                        <div class="form-group">

                            <label class="required">Açıklama</label>

                            <textarea name="aciklama" rows="6" required><?php echo htmlspecialchars($property['aciklama']); ?></textarea>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label class="required">Kategori</label>

                                <select name="kategori" required>

                                    <option value="Satılık" <?php echo $property['kategori'] == 'Satılık' ? 'selected' : ''; ?>>Satılık</option>

                                    <option value="Kiralık" <?php echo $property['kategori'] == 'Kiralık' ? 'selected' : ''; ?>>Kiralık</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label class="required">Emlak Tipi</label>

                                <select name="emlak_tipi" required>

                                    <option value="konut" <?php echo $property['emlak_tipi'] == 'konut' ? 'selected' : ''; ?>>Konut</option>

                                    <option value="isyeri" <?php echo $property['emlak_tipi'] == 'isyeri' ? 'selected' : ''; ?>>İşyeri</option>

                                    <option value="arsa" <?php echo $property['emlak_tipi'] == 'arsa' ? 'selected' : ''; ?>>Arsa</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label class="required">Fiyat</label>

                                <input type="number" name="fiyat" value="<?php echo $property['fiyat']; ?>" required>

                            </div>

                        </div>



                        <!-- Diğer alanlar önceki gibi devam ediyor... -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>Oda Sayısı</label>

                                <select name="oda_sayisi">

                                    <option value="">Seçiniz</option>

                                    <?php

                                    $odalar = ['1+0', '1+1', '2+1', '3+1', '4+1', '5+1'];

                                    foreach ($odalar as $oda): ?>

                                        <option value="<?php echo $oda; ?>" <?php echo $property['oda_sayisi'] == $oda ? 'selected' : ''; ?>><?php echo $oda; ?></option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="form-group">

                                <label class="required">Brüt m²</label>

                                <input type="number" name="brut_metrekare" value="<?php echo $property['brut_metrekare']; ?>" required>

                            </div>

                            <div class="form-group">

                                <label>Net m²</label>

                                <input type="number" name="net_metrekare" value="<?php echo $property['net_metrekare']; ?>">

                            </div>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label>Bina Yaşı</label>

                                <input type="text" name="bina_yasi" value="<?php echo $property['bina_yasi']; ?>">

                            </div>

                            <div class="form-group">

                                <label>Bulunduğu Kat</label>

                                <input type="text" name="bulundugu_kat" value="<?php echo $property['bulundugu_kat']; ?>">

                            </div>

                            <div class="form-group">

                                <label>Kat Sayısı</label>

                                <input type="text" name="kat_sayisi" value="<?php echo $property['kat_sayisi']; ?>">

                            </div>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label>Eşyalı</label>

                                <select name="esyali">

                                    <option value="Hayır" <?php echo $property['esyali'] == 'Hayır' ? 'selected' : ''; ?>>Hayır</option>

                                    <option value="Evet" <?php echo $property['esyali'] == 'Evet' ? 'selected' : ''; ?>>Evet</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Isıtma</label>

                                <input type="text" name="isitma" value="<?php echo $property['isitma']; ?>">

                            </div>

                            <div class="form-group">

                                <label>Balkon</label>

                                <select name="balkon">

                                    <option value="">Seçiniz</option>

                                    <option value="Var" <?php echo $property['balkon'] == 'Var' ? 'selected' : ''; ?>>Var</option>

                                    <option value="Yok" <?php echo $property['balkon'] == 'Yok' ? 'selected' : ''; ?>>Yok</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Mutfak</label>

                                <select name="mutfak">

                                    <option value="">Seçiniz</option>

                                    <option value="Açık" <?php echo ($property['mutfak'] ?? '') == 'Açık' ? 'selected' : ''; ?>>Açık</option>

                                    <option value="Kapalı" <?php echo ($property['mutfak'] ?? '') == 'Kapalı' ? 'selected' : ''; ?>>Kapalı</option>

                                    <option value="Amerikan" <?php echo ($property['mutfak'] ?? '') == 'Amerikan' ? 'selected' : ''; ?>>Amerikan</option>

                                </select>

                            </div>

                        </div>

                        <!-- Yeni satır - Asansör, Otopark, Site Adı -->

                        <div class="form-row">

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

                        <div class="form-row">

                            <div class="form-group">

                                <label>Site İçerisinde</label>

                                <select name="site_icerisinde">

                                    <option value="Hayır" <?php echo $property['site_icerisinde'] == 'Hayır' ? 'selected' : ''; ?>>Hayır</option>

                                    <option value="Evet" <?php echo $property['site_icerisinde'] == 'Evet' ? 'selected' : ''; ?>>Evet</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Takas</label>

                                <select name="takas">

                                    <option value="Hayır" <?php echo $property['takas'] == 'Hayır' ? 'selected' : ''; ?>>Hayır</option>

                                    <option value="Evet" <?php echo $property['takas'] == 'Evet' ? 'selected' : ''; ?>>Evet</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Durum</label>

                                <select name="durum">

                                    <option value="aktif" <?php echo $property['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>

                                    <option value="pasif" <?php echo $property['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>

                                </select>

                            </div>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label>Banyo Sayısı</label>

                                <input type="text" name="banyo_sayisi" value="<?php echo $property['banyo_sayisi']; ?>">

                            </div>

                            <div class="form-group">

                                <label>Kullanım Durumu</label>

                                <input type="text" name="kullanim_durumu" value="<?php echo $property['kullanim_durumu']; ?>">

                            </div>

                            <div class="form-group">

                                <label>Aidat</label>

                                <input type="number" name="aidat" value="<?php echo $property['aidat']; ?>">

                            </div>

                        </div>

                    </div>



                    <!-- Adres Bilgileri -->

                    <div class="form-section">

                        <h2 class="section-title">Adres Bilgileri</h2>



                        <div class="form-row">

                            <div class="form-group">

                                <label class="required">İl</label>

                                <input type="text" name="il" value="<?php echo htmlspecialchars($property['il']); ?>" required

                                    placeholder="İl adını yazın">

                            </div>

                            <div class="form-group">

                                <label class="required">İlçe</label>

                                <input type="text" name="ilce" value="<?php echo htmlspecialchars($property['ilce']); ?>" required

                                    placeholder="İlçe adını yazın">

                            </div>

                            <div class="form-group">

                                <label>Mahalle</label>

                                <input type="text" name="mahalle" value="<?php echo htmlspecialchars($property['mahalle'] ?? ''); ?>"

                                    placeholder="Mahalle adını yazın">

                            </div>

                        </div>



                        <div class="form-group">

                            <label>Açık Adres</label>

                            <textarea name="adres" rows="3"><?php echo htmlspecialchars($property['adres']); ?></textarea>

                        </div>

                    </div>



                    <!-- Butonlar -->

                    <div class="buttons">

                        <a href="list.php" class="btn btn-back">← İptal</a>

                        <button type="submit" class="btn btn-save">✓ Güncelle</button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script>
        // Ana fotoğraf yapma

        function setMainPhoto(photoId) {

            if (confirm('Bu fotoğrafı ana fotoğraf yapmak istiyor musunuz?')) {

                window.location.href = 'ajax/set-main-photo.php?id=' + photoId + '&property_id=<?php echo $id; ?>';

            }

        }



        // Fotoğraf silme

        function deletePhoto(photoId) {

            if (confirm('Bu fotoğrafı silmek istediğinize emin misiniz?')) {

                window.location.href = 'ajax/delete-photo.php?id=' + photoId + '&property_id=<?php echo $id; ?>';

            }

        }



        // Dosya seçildiğinde önizleme

        document.getElementById('new_photos').addEventListener('change', function(e) {

            const fileCount = e.target.files.length;

            const currentCount = <?php echo count($images); ?>;

            const totalCount = currentCount + fileCount;



            if (totalCount > 50) {

                alert('Toplam fotoğraf sayısı 50\'yi geçemez! Mevcut: ' + currentCount + ', Eklemeye çalıştığınız: ' + fileCount);

                e.target.value = '';

                return;

            }



            if (fileCount > 0) {

                if (confirm(fileCount + ' adet fotoğraf yüklenecek. Devam etmek istiyor musunuz?')) {

                    // Formu otomatik gönder

                    this.form.submit();

                }

            }

        });
    </script>

</body>



</html>