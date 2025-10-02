<?php
<<<<<<< HEAD
error_reporting(E_ALL);
ini_set('display_errors', 1);
=======
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';

<<<<<<< HEAD
// POST kontrolü
=======
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['save_property'])) {
    header("Location: ../add-step1.php");
    exit();
}

try {
    // İlan no oluştur
    $ilan_no = 'PLZ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $data = $_POST;

<<<<<<< HEAD
    // Kullanıcı ID
    $current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;

    // İLANI KAYDET
=======
    // Kullanıcı ID'sini al
    $current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;

    // İlanı kaydet - user_id EKLENDİ
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
    $sql = "INSERT INTO properties (
        ilan_no, ilan_tarihi, baslik, aciklama, fiyat, metrekare_fiyat,
        emlak_tipi, kategori, oda_sayisi, brut_metrekare, net_metrekare,
        bina_yasi, bulundugu_kat, kat_sayisi, isitma, banyo_sayisi, balkon,
<<<<<<< HEAD
        esyali, kullanim_durumu, site_icerisinde, aidat, mutfak, asansor, otopark, site_adi,
        imar_durumu, ada_no, parsel_no, pafta_no, kaks_emsal, gabari,
        tapu_durumu, krediye_uygun, takas,
        il, ilce, mahalle, adres, latitude, longitude, durum, kimden,
=======
        esyali, kullanim_durumu, site_icerisinde, aidat,
        imar_durumu, ada_no, parsel_no, pafta_no, kaks_emsal, gabari,
        tapu_durumu, krediye_uygun, takas,
        il, ilce, mahalle, adres,latitude, longitude, durum, kimden,
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        anahtar_no, mulk_sahibi_tel, danisman_notu,
        ekleyen_admin_id, user_id, created_at
    ) VALUES (
        :ilan_no, CURDATE(), :baslik, :aciklama, :fiyat, :metrekare_fiyat,
        :emlak_tipi, :kategori, :oda_sayisi, :brut_metrekare, :net_metrekare,
        :bina_yasi, :bulundugu_kat, :kat_sayisi, :isitma, :banyo_sayisi, :balkon,
<<<<<<< HEAD
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat, :mutfak, :asansor, :otopark, :site_adi,
=======
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat,
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        :imar_durumu, :ada_no, :parsel_no, :pafta_no, :kaks_emsal, :gabari,
        :tapu_durumu, :krediye_uygun, :takas,
        :il, :ilce, :mahalle, :adres, :latitude, :longitude, 'aktif', 'Ofisten',
        :anahtar_no, :mulk_sahibi_tel, :danisman_notu,
        :admin_id, :user_id, NOW()
    )";

    $stmt = $db->prepare($sql);
<<<<<<< HEAD
    echo "SQL ÇALIŞIYOR...<br>";
    var_dump($ilan_no);
    var_dump($current_user_id);
    die("TEST");
=======
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
    $stmt->execute([
        ':ilan_no' => $ilan_no,
        ':baslik' => $data['baslik'] ?? '',
        ':aciklama' => $data['aciklama'] ?? '',
        ':fiyat' => $data['fiyat'] ?? 0,
        ':metrekare_fiyat' => $data['metrekare_fiyat'] ?? null,
        ':emlak_tipi' => $data['emlak_tipi'] ?? '',
        ':kategori' => $data['kategori'] ?? '',
        ':oda_sayisi' => $data['oda_sayisi'] ?? null,
        ':brut_metrekare' => $data['brut_metrekare'] ?? null,
        ':net_metrekare' => $data['net_metrekare'] ?? null,
        ':bina_yasi' => $data['bina_yasi'] ?? null,
        ':bulundugu_kat' => $data['bulundugu_kat'] ?? null,
        ':kat_sayisi' => $data['kat_sayisi'] ?? null,
        ':isitma' => $data['isitma'] ?? null,
        ':banyo_sayisi' => $data['banyo_sayisi'] ?? null,
        ':balkon' => $data['balkon'] ?? null,
        ':esyali' => $data['esyali'] ?? 'Hayır',
        ':kullanim_durumu' => $data['kullanim_durumu'] ?? 'Boş',
        ':site_icerisinde' => $data['site_icerisinde'] ?? 'Hayır',
        ':aidat' => $data['aidat'] ?? null,
<<<<<<< HEAD
        ':mutfak' => $data['mutfak'] ?? null,
        ':asansor' => $data['asansor'] ?? null,
        ':otopark' => $data['otopark'] ?? null,
        ':site_adi' => $data['site_adi'] ?? null,
=======
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        ':imar_durumu' => $data['imar_durumu'] ?? null,
        ':ada_no' => $data['ada_no'] ?? null,
        ':parsel_no' => $data['parsel_no'] ?? null,
        ':pafta_no' => $data['pafta_no'] ?? null,
        ':kaks_emsal' => $data['kaks_emsal'] ?? null,
        ':gabari' => $data['gabari'] ?? null,
        ':tapu_durumu' => $data['tapu_durumu'] ?? null,
        ':krediye_uygun' => $data['krediye_uygun'] ?? null,
<<<<<<< HEAD
        ':takas' => $data['takas'] ?? 'Hayır',
=======
        ':takas' => $data['takas'] ?? null,
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        ':il' => $data['il'] ?? 'Afyonkarahisar',
        ':ilce' => $data['ilce'] ?? '',
        ':mahalle' => $data['mahalle'] ?? null,
        ':adres' => $data['adres'] ?? null,
        ':latitude' => !empty($data['latitude']) ? floatval($data['latitude']) : null,
        ':longitude' => !empty($data['longitude']) ? floatval($data['longitude']) : null,
        ':anahtar_no' => $data['anahtar_no'] ?? null,
        ':mulk_sahibi_tel' => $data['mulk_sahibi_tel'] ?? null,
        ':danisman_notu' => $data['danisman_notu'] ?? null,
        ':admin_id' => $current_user_id,
        ':user_id' => $current_user_id
    ]);

    // Property ID'yi al
    $property_id = $db->lastInsertId();

<<<<<<< HEAD
    if (!$property_id) {
        throw new Exception("İlan ID alınamadı!");
    }

    // FOTOĞRAF YÜKLEME - DOĞRUDAN SESSION'DAN
    $uploadSuccess = 0;
    $uploadErrors = [];

    // Upload dizini
    $uploadDir = realpath(dirname(__FILE__) . '/../../../assets/uploads/properties/');
    if (!$uploadDir) {
        $uploadDir = dirname(__FILE__) . '/../../../assets/uploads/properties/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
            @chmod($uploadDir, 0777);
        }
        $uploadDir = realpath($uploadDir);
    }

    // Session'daki temp fotoğrafları işle
    if (isset($_SESSION['temp_photos']) && is_array($_SESSION['temp_photos'])) {
        foreach ($_SESSION['temp_photos'] as $index => $tempFile) {
            try {
                // PATH DÜZELTME - ÇOK ÖNEMLİ
                $realPath = $tempFile['path'];
                if (strpos($realPath, '/home/') === 0) {
                    // Tam yol ise olduğu gibi kullan
                    $checkPath = $realPath;
                } else {
                    // Göreceli yol ise düzelt
                    $checkPath = realpath($tempFile['path']);
                }

                if (file_exists($checkPath)) {                    // Dosya uzantısını al
                    $ext = strtolower(pathinfo($tempFile['name'], PATHINFO_EXTENSION));

                    // Güvenlik kontrolü
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $allowedTypes)) {
                        $uploadErrors[] = "Geçersiz dosya tipi: " . $tempFile['name'];
                        continue;
                    }

                    // Yeni dosya adı
                    $newFileName = 'prop_' . $property_id . '_' . time() . '_' . $index . '.' . $ext;
                    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                    // Dosyayı kopyala
                    if (copy($checkPath, $targetPath)) {
                        // Veritabanı için path
                        $dbPath = 'assets/uploads/properties/' . $newFileName;

                        // İlk fotoğraf ana fotoğraf
                        $isMain = ($uploadSuccess == 0) ? 1 : 0;

                        // Veritabanına ekle
                        $imgStmt = $db->prepare("INSERT INTO property_images 
                            (property_id, image_path, image_name, is_main, display_order) 
                            VALUES (:pid, :path, :name, :main, :order)");

                        $imgStmt->execute([
                            ':pid' => $property_id,
                            ':path' => $dbPath,
                            ':name' => $tempFile['name'],
                            ':main' => $isMain,
                            ':order' => $index
                        ]);

                        $uploadSuccess++;

                        // Temp dosyayı sil
                        @unlink($tempFile['path']);
                    } else {
                        $uploadErrors[] = "Kopyalanamadı: " . $tempFile['name'];
                    }
                }
            } catch (Exception $e) {
                $uploadErrors[] = "Hata: " . $e->getMessage();
            }
        }

        // Temp klasörünü temizle
        if (isset($_SESSION['temp_photos'])) {
            foreach ($_SESSION['temp_photos'] as $temp) {
                if (isset($temp['path']) && file_exists($temp['path'])) {
                    @unlink($temp['path']);
                }
            }
        }
    }

    // Lokasyon önerilerine ekle/güncelle
    if (!empty($data['il']) && !empty($data['ilce'])) {
        try {
            $il_formatted = ucwords(strtolower(trim($data['il'])));
            $ilce_formatted = ucwords(strtolower(trim($data['ilce'])));
            $mahalle_formatted = !empty($data['mahalle']) ? ucwords(strtolower(trim($data['mahalle']))) : null;

            // Önce kontrol et
            $check = $db->prepare("SELECT id FROM lokasyon_onerileri 
                WHERE il = :il AND ilce = :ilce 
                AND (mahalle = :mahalle OR (:mahalle IS NULL AND mahalle IS NULL))");
            $check->execute([
                ':il' => $il_formatted,
                ':ilce' => $ilce_formatted,
                ':mahalle' => $mahalle_formatted
            ]);

            if ($check->rowCount() > 0) {
                // Varsa güncelle
                $update = $db->prepare("UPDATE lokasyon_onerileri 
                    SET kullanim_sayisi = kullanim_sayisi + 1 
                    WHERE il = :il AND ilce = :ilce 
                    AND (mahalle = :mahalle OR (:mahalle IS NULL AND mahalle IS NULL))");
                $update->execute([
                    ':il' => $il_formatted,
                    ':ilce' => $ilce_formatted,
                    ':mahalle' => $mahalle_formatted
                ]);
            } else {
                // Yoksa ekle
                $insert = $db->prepare("INSERT INTO lokasyon_onerileri 
                    (il, ilce, mahalle, kullanim_sayisi) 
                    VALUES (:il, :ilce, :mahalle, 1)");
                $insert->execute([
                    ':il' => $il_formatted,
                    ':ilce' => $ilce_formatted,
                    ':mahalle' => $mahalle_formatted
                ]);
            }
        } catch (Exception $e) {
            // Lokasyon hatası ilanı etkilemesin
            error_log("Lokasyon kayıt hatası: " . $e->getMessage());
        }
    }

=======
    // ID alamazsa manuel çek
    if (!$property_id) {
        $stmt = $db->prepare("SELECT id FROM properties WHERE ilan_no = :ilan_no");
        $stmt->execute([':ilan_no' => $ilan_no]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $property_id = $row['id'];
    }

    // FOTOĞRAF YÜKLEME - BASİT ÇÖZÜM
    $uploadPath = dirname(__FILE__) . '/../../../assets/uploads/properties/';
    $uploadPath = str_replace('\\', '/', $uploadPath);

    // Klasör yoksa oluştur
    if (!is_dir($uploadPath)) {
        @mkdir($uploadPath, 0777, true);
    }

    // Session'daki temp fotoğrafları kontrol et
    if (isset($_SESSION['temp_photos']) && !empty($_SESSION['temp_photos'])) {

        $successCount = 0;

        foreach ($_SESSION['temp_photos'] as $i => $file) {

            // Dosya yolu Windows uyumlu yap
            $tempPath = str_replace('\\', '/', $file['path']);

            // Dosya gerçekten var mı kontrol et
            if (file_exists($tempPath)) {

                // Yeni dosya adı
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newName = 'img_' . $property_id . '_' . time() . '_' . $i . '.' . $ext;
                $targetPath = $uploadPath . $newName;

                // Kopyala
                if (@copy($tempPath, $targetPath)) {

                    // Veritabanına ekle
                    $dbPath = 'assets/uploads/properties/' . $newName;
                    $isMain = ($i == 0) ? 1 : 0;

                    try {
                        $stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, image_name, is_main) 
                                             VALUES (:pid, :path, :name, :main)");
                        $stmt->execute([
                            ':pid' => $property_id,
                            ':path' => $dbPath,
                            ':name' => $file['name'],
                            ':main' => $isMain
                        ]);

                        $successCount++;
                    } catch (Exception $imgError) {
                        // Fotoğraf hatası olursa devam et
                        error_log("Fotoğraf ekleme hatası: " . $imgError->getMessage());
                    }

                    // Temp dosyayı sil
                    @unlink($tempPath);
                }
            }
        }

        // Log tut
        if ($successCount > 0) {
            error_log("Upload başarılı: " . $successCount . " fotoğraf yüklendi.");
        }
    }
    
    // Lokasyon önerilerine ekle/güncelle
    if (!empty($data['il']) && !empty($data['ilce'])) {
        $il_formatted = ucwords(strtolower(trim($data['il'])));
        $ilce_formatted = ucwords(strtolower(trim($data['ilce'])));
        $mahalle_formatted = !empty($data['mahalle']) ? ucwords(strtolower(trim($data['mahalle']))) : null;

        try {
            $check = $db->prepare("SELECT id FROM lokasyon_onerileri WHERE il = :il AND ilce = :ilce AND (mahalle = :mahalle OR (:mahalle IS NULL AND mahalle IS NULL))");
            $check->execute([':il' => $il_formatted, ':ilce' => $ilce_formatted, ':mahalle' => $mahalle_formatted]);

            if ($check->rowCount() > 0) {
                // Varsa kullanım sayısını artır
                $update = $db->prepare("UPDATE lokasyon_onerileri SET kullanim_sayisi = kullanim_sayisi + 1 
                                       WHERE il = :il AND ilce = :ilce AND (mahalle = :mahalle OR (:mahalle IS NULL AND mahalle IS NULL))");
                $update->execute([':il' => $il_formatted, ':ilce' => $ilce_formatted, ':mahalle' => $mahalle_formatted]);
            } else {
                // Yoksa yeni ekle
                $insert = $db->prepare("INSERT INTO lokasyon_onerileri (il, ilce, mahalle) VALUES (:il, :ilce, :mahalle)");
                $insert->execute([':il' => $il_formatted, ':ilce' => $ilce_formatted, ':mahalle' => $mahalle_formatted]);
            }
        } catch (Exception $e) {
            // Hata olsa bile devam et
            error_log("Lokasyon kayıt hatası: " . $e->getMessage());
        }
    }
    
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
    // Session temizle
    unset($_SESSION['temp_photos']);
    unset($_SESSION['property_data']);
    unset($_SESSION['emlak_tipi']);
    unset($_SESSION['kategori']);
    unset($_SESSION['alt_kategori']);

<<<<<<< HEAD
    // Başarı mesajları
    $_SESSION['new_property_id'] = $property_id;
    $_SESSION['new_property_no'] = $ilan_no;
    $_SESSION['success'] = "İlan başarıyla eklendi! İlan No: " . $ilan_no;

    if ($uploadSuccess > 0) {
        $_SESSION['success'] .= " (" . $uploadSuccess . " fotoğraf yüklendi)";
    }

    if (!empty($uploadErrors)) {
        $_SESSION['warning'] = "Bazı fotoğraflar yüklenemedi: " . implode(', ', $uploadErrors);
    }

    // SMS SİSTEMİ
=======
    // Başarı
    $_SESSION['new_property_id'] = $property_id;
    $_SESSION['new_property_no'] = $ilan_no;
    $_SESSION['success'] = "İlan başarıyla eklendi! İlan No: " . $ilan_no;
    
    // ============ SMS SİSTEMİ BAŞLANGIÇ ============
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
    try {
        // SMS sistemi aktifse devam et
        $sms_check = $db->query("SELECT is_active, test_mode FROM sms_settings WHERE id = 1");
        $sms_settings = $sms_check->fetch(PDO::FETCH_ASSOC);

        if ($sms_settings && $sms_settings['is_active'] == 1) {
            // NetGSM sınıfını dahil et
<<<<<<< HEAD
            if (file_exists('../../../classes/NetGSM.php')) {
                require_once '../../../classes/NetGSM.php';

                // İlan linkini oluştur
                $base_url = "https://www.plazaemlak.net";
                $ilan_link = $base_url . "/pages/detail.php?id=" . $property_id;

                // Kullanıcı bilgilerini al
                $user_name = $_SESSION['user_fullname'] ?? $_SESSION['admin_username'] ?? 'Plaza Emlak';

                // NetGSM'i başlat
                $netgsm = new NetGSM($db);

                // Danışmanlara SMS gönder
                $danisman_sql = "SELECT id, full_name, mobile, sms_permission 
                            FROM users 
                            WHERE status = 'active' 
                            AND mobile IS NOT NULL 
                            AND mobile != ''
                            AND sms_permission = 1";

                $danisman_stmt = $db->query($danisman_sql);
                $danismanlar = $danisman_stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($danismanlar as $danisman) {
                    // SMS mesajını hazırla
                    $sms_mesaj = "YENİ İLAN: " . mb_substr($data['baslik'], 0, 40) . "\n";
                    $sms_mesaj .= number_format($data['fiyat'], 0, ',', '.') . " TL\n";

                    if (!empty($data['mahalle'])) {
                        $sms_mesaj .= $data['ilce'] . "/" . $data['mahalle'] . "\n";
                    } else {
                        $sms_mesaj .= $data['ilce'] . "\n";
                    }

                    $sms_mesaj .= "Detay: " . $ilan_link . "\n";
                    $sms_mesaj .= "Plaza Emlak";

                    // SMS'i gönder
                    $netgsm->sendSMS($danisman['mobile'], $sms_mesaj, 'yeni_ilan', $current_user_id, [
                        'type' => 'danisman',
                        'id' => $danisman['id'],
                        'name' => $danisman['full_name'],
                        'property_id' => $property_id
                    ]);
                }

                // Bütçeye uygun müşterilere SMS (Opsiyonel)
                if (isset($data['fiyat']) && $data['fiyat'] > 0) {
                    $musteri_sql = "SELECT id, ad, soyad, telefon 
                                   FROM crm_alici_musteriler 
                                   WHERE durum = 'aktif' 
                                   AND sms_permission = 1
                                   AND mersis_permission = 1
                                   AND telefon IS NOT NULL 
                                   AND telefon != ''
                                   AND :fiyat BETWEEN min_butce AND max_butce";

                    $musteri_stmt = $db->prepare($musteri_sql);
                    $musteri_stmt->execute([':fiyat' => $data['fiyat']]);
                    $musteriler = $musteri_stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($musteriler as $musteri) {
                        $musteri_sms = "Sayın " . $musteri['ad'] . " " . mb_substr($musteri['soyad'], 0, 1) . ".\n";
                        $musteri_sms .= "Bütçenize uygun:\n";
                        $musteri_sms .= mb_substr($data['baslik'], 0, 35) . "\n";
                        $musteri_sms .= number_format($data['fiyat'], 0, ',', '.') . " TL\n";
                        $musteri_sms .= $ilan_link . "\n";
                        $musteri_sms .= "Plaza Emlak";

                        $netgsm->sendSMS($musteri['telefon'], $musteri_sms, 'musteri_bilgi', $current_user_id, [
                            'type' => 'alici',
                            'id' => $musteri['id'],
                            'name' => $musteri['ad'] . ' ' . $musteri['soyad'],
                            'property_id' => $property_id
                        ]);
                    }
                }

                error_log("İlan #" . $property_id . " için SMS gönderimi tamamlandı.");
            }
=======
            require_once '../../../classes/NetGSM.php';

            // İlan linkini oluştur - GERÇEK DOMAIN
            $base_url = "https://www.plazaemlak.net"; // Canlı site adresi
            $ilan_link = $base_url . "/pages/detail.php?id=" . $property_id;

            // Kullanıcı bilgilerini al
            $user_name = $_SESSION['user_fullname'] ?? $_SESSION['admin_username'] ?? 'Plaza Emlak';

            // NetGSM'i başlat
            $netgsm = new NetGSM($db);

            // 1. TÜM DANIŞMANLARA SMS GÖNDER
            $danisman_sql = "SELECT id, full_name, mobile, sms_permission 
                        FROM users 
                        WHERE status = 'active' 
                        AND mobile IS NOT NULL 
                        AND mobile != ''
                        AND sms_permission = 1";

            $danisman_stmt = $db->query($danisman_sql);
            $danismanlar = $danisman_stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($danismanlar as $danisman) {
                // SMS mesajını hazırla - İLAN LİNKİ EKLENDİ
                $sms_mesaj = "YENİ İLAN: " . mb_substr($data['baslik'], 0, 40) . "\n";
                $sms_mesaj .= number_format($data['fiyat'], 0, ',', '.') . " TL\n";
                
                // İlçe ve mahalle bilgisi
                if (!empty($data['mahalle'])) {
                    $sms_mesaj .= $data['ilce'] . "/" . $data['mahalle'] . "\n";
                } else {
                    $sms_mesaj .= $data['ilce'] . "\n";
                }
                
                // İlan linki
                $sms_mesaj .= "Detay: " . $ilan_link . "\n";
                $sms_mesaj .= "Plaza Emlak";

                // SMS'i gönder
                $netgsm->sendSMS($danisman['mobile'], $sms_mesaj, 'yeni_ilan', $current_user_id, [
                    'type' => 'danisman',
                    'id' => $danisman['id'],
                    'name' => $danisman['full_name'],
                    'property_id' => $property_id
                ]);
            }

            // 2. BÜTÇEYE UYGUN MÜŞTERİLERE SMS (Opsiyonel)
            if (isset($data['fiyat']) && $data['fiyat'] > 0) {
                $musteri_sql = "SELECT id, ad, soyad, telefon 
                               FROM crm_alici_musteriler 
                               WHERE durum = 'aktif' 
                               AND sms_permission = 1
                               AND mersis_permission = 1
                               AND telefon IS NOT NULL 
                               AND telefon != ''
                               AND :fiyat BETWEEN min_butce AND max_butce";

                $musteri_stmt = $db->prepare($musteri_sql);
                $musteri_stmt->execute([':fiyat' => $data['fiyat']]);
                $musteriler = $musteri_stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($musteriler as $musteri) {
                    // Müşteri SMS mesajı
                    $musteri_sms = "Sayın " . $musteri['ad'] . " " . mb_substr($musteri['soyad'], 0, 1) . ".\n";
                    $musteri_sms .= "Bütçenize uygun:\n";
                    $musteri_sms .= mb_substr($data['baslik'], 0, 35) . "\n";
                    $musteri_sms .= number_format($data['fiyat'], 0, ',', '.') . " TL\n";
                    $musteri_sms .= $ilan_link . "\n";
                    $musteri_sms .= "Plaza Emlak";

                    $netgsm->sendSMS($musteri['telefon'], $musteri_sms, 'musteri_bilgi', $current_user_id, [
                        'type' => 'alici',
                        'id' => $musteri['id'],
                        'name' => $musteri['ad'] . ' ' . $musteri['soyad'],
                        'property_id' => $property_id
                    ]);
                }
            }

            error_log("İlan #" . $property_id . " için SMS gönderimi tamamlandı.");
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        }
    } catch (Exception $e) {
        // SMS hatası ilan eklemeyi engellemesin
        error_log("SMS gönderim hatası: " . $e->getMessage());
    }
<<<<<<< HEAD

    // Log tut
    error_log("İlan eklendi: ID=" . $property_id . ", No=" . $ilan_no . ", Fotoğraf=" . $uploadSuccess);

    // Tebrikler sayfasına yönlendir
    header("Location: ../add-step4.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    error_log("İlan ekleme hatası: " . $e->getMessage());
    header("Location: ../add-step3.php");
    exit();
}
=======
    // ============ SMS SİSTEMİ BİTİŞ ============
    
    // Herkes için tebrikler sayfasına git
    header("Location: ../add-step4.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    header("Location: ../add-step3.php");
    exit();
}
?>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
