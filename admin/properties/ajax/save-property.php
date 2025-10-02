<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['save_property'])) {
    header("Location: ../add-step1.php");
    exit();
}

try {
    // İlan no oluştur
    $ilan_no = 'PLZ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $data = $_POST;

    // Kullanıcı ID
    $current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;

    // İLANI KAYDET
    $sql = "INSERT INTO properties (
        ilan_no, ilan_tarihi, baslik, aciklama, fiyat, metrekare_fiyat,
        emlak_tipi, kategori, oda_sayisi, brut_metrekare, net_metrekare,
        bina_yasi, bulundugu_kat, kat_sayisi, isitma, banyo_sayisi, balkon,
        esyali, kullanim_durumu, site_icerisinde, aidat, mutfak, asansor, otopark, site_adi,
        imar_durumu, ada_no, parsel_no, pafta_no, kaks_emsal, gabari,
        tapu_durumu, krediye_uygun, takas,
        il, ilce, mahalle, adres, latitude, longitude, durum, kimden,
        anahtar_no, mulk_sahibi_tel, danisman_notu,
        ekleyen_admin_id, user_id, created_at
    ) VALUES (
        :ilan_no, CURDATE(), :baslik, :aciklama, :fiyat, :metrekare_fiyat,
        :emlak_tipi, :kategori, :oda_sayisi, :brut_metrekare, :net_metrekare,
        :bina_yasi, :bulundugu_kat, :kat_sayisi, :isitma, :banyo_sayisi, :balkon,
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat, :mutfak, :asansor, :otopark, :site_adi,
        :imar_durumu, :ada_no, :parsel_no, :pafta_no, :kaks_emsal, :gabari,
        :tapu_durumu, :krediye_uygun, :takas,
        :il, :ilce, :mahalle, :adres, :latitude, :longitude, 'aktif', 'Ofisten',
        :anahtar_no, :mulk_sahibi_tel, :danisman_notu,
        :admin_id, :user_id, NOW()
    )";

    $stmt = $db->prepare($sql);
    echo "SQL ÇALIŞIYOR...<br>";
    var_dump($ilan_no);
    var_dump($current_user_id);
    die("TEST");
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
        ':mutfak' => $data['mutfak'] ?? null,
        ':asansor' => $data['asansor'] ?? null,
        ':otopark' => $data['otopark'] ?? null,
        ':site_adi' => $data['site_adi'] ?? null,
        ':imar_durumu' => $data['imar_durumu'] ?? null,
        ':ada_no' => $data['ada_no'] ?? null,
        ':parsel_no' => $data['parsel_no'] ?? null,
        ':pafta_no' => $data['pafta_no'] ?? null,
        ':kaks_emsal' => $data['kaks_emsal'] ?? null,
        ':gabari' => $data['gabari'] ?? null,
        ':tapu_durumu' => $data['tapu_durumu'] ?? null,
        ':krediye_uygun' => $data['krediye_uygun'] ?? null,
        ':takas' => $data['takas'] ?? 'Hayır',
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

    // Session temizle
    unset($_SESSION['temp_photos']);
    unset($_SESSION['property_data']);
    unset($_SESSION['emlak_tipi']);
    unset($_SESSION['kategori']);
    unset($_SESSION['alt_kategori']);

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
    try {
        // SMS sistemi aktifse devam et
        $sms_check = $db->query("SELECT is_active, test_mode FROM sms_settings WHERE id = 1");
        $sms_settings = $sms_check->fetch(PDO::FETCH_ASSOC);

        if ($sms_settings && $sms_settings['is_active'] == 1) {
            // NetGSM sınıfını dahil et
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
        }
    } catch (Exception $e) {
        // SMS hatası ilan eklemeyi engellemesin
        error_log("SMS gönderim hatası: " . $e->getMessage());
    }

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
