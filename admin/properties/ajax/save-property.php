<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    http_response_code(401);
    exit('Yetkisiz erişim');
}

require_once '../../../config/database.php';

try {
    $db->beginTransaction();

    // İlan numarası oluştur - PLZ öneki ile
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
    $stmt->execute([
        ':ilan_no' => $ilan_no,
        ':baslik' => $data['baslik'] ?? '',
        ':aciklama' => $data['aciklama'] ?? '',
        ':fiyat' => floatval(str_replace(',', '', $data['fiyat'] ?? 0)), // Virgülleri temizle
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
        ':balkon' => $data['balkon'] ?? 'Hayır',
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
        ':krediye_uygun' => $data['krediye_uygun'] ?? 'Evet',
        ':takas' => $data['takas'] ?? 'Hayır',
        ':il' => $data['il'] ?? 'Afyonkarahisar',
        ':ilce' => $data['ilce'] ?? '',
        ':mahalle' => $data['mahalle'] ?? '',
        ':adres' => $data['adres'] ?? '',
        ':latitude' => $data['latitude'] ?? null,
        ':longitude' => $data['longitude'] ?? null,
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

    // FOTOĞRAF YÜKLEME
    $uploadSuccess = 0;
    $uploadErrors = [];

    // Upload dizini
    $baseDir = dirname(__FILE__, 4); // 4 seviye yukarı = root
    $uploadDir = $baseDir . '/assets/uploads/properties';
    
    // Klasör yoksa oluştur
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Session'daki temp fotoğrafları işle
    if (isset($_SESSION['temp_photos']) && is_array($_SESSION['temp_photos'])) {
        foreach ($_SESSION['temp_photos'] as $index => $tempFile) {
            try {
                // Temp dosya yolu
                $tempPath = $tempFile['path'];
                
                // Alternatif yolları kontrol et
                if (!file_exists($tempPath)) {
                    $altPaths = [
                        $baseDir . '/' . $tempPath,
                        $_SERVER['DOCUMENT_ROOT'] . '/plazanet/' . $tempPath,
                        dirname(__FILE__) . '/../../../' . $tempPath
                    ];
                    
                    foreach ($altPaths as $altPath) {
                        if (file_exists($altPath)) {
                            $tempPath = $altPath;
                            break;
                        }
                    }
                }
                
                if (!file_exists($tempPath)) {
                    $uploadErrors[] = "Dosya bulunamadı: " . basename($tempFile['name']);
                    continue;
                }

                // Dosya uzantısını al
                $ext = strtolower(pathinfo($tempFile['name'], PATHINFO_EXTENSION));

                // Güvenlik kontrolü
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    continue;
                }

                // Yeni dosya adı
                $newFileName = 'prop_' . $property_id . '_' . time() . '_' . $index . '.' . $ext;
                $targetPath = $uploadDir . '/' . $newFileName;

                // Dosyayı kopyala
                if (copy($tempPath, $targetPath)) {
                    // Veritabanı için yol
                    $dbPath = 'assets/uploads/properties/' . $newFileName;

                    // İlk fotoğraf ana fotoğraf
                    $isMain = ($uploadSuccess == 0) ? 1 : 0;

                    // Veritabanına kaydet
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
                    @unlink($tempPath);
                }
            } catch (Exception $e) {
                $uploadErrors[] = $e->getMessage();
            }
        }
    }

    // Session'daki temp fotoğrafları temizle
    unset($_SESSION['temp_photos']);

    // Commit
    $db->commit();

    // SESSION'A BİLGİLERİ EKLE - ÖNEMLİ!
    $_SESSION['new_property_id'] = $property_id;
    $_SESSION['new_property_no'] = $ilan_no;
    $_SESSION['last_property_id'] = $property_id;
    $_SESSION['last_property_no'] = $ilan_no;
    $_SESSION['success'] = "İlan başarıyla eklendi!";
    
    // Log
    error_log("İlan eklendi: ID=$property_id, No=$ilan_no, Fotoğraf=$uploadSuccess");

    // Tebrikler sayfasına yönlendir
    header("Location: /plazanet/admin/properties/add-step4.php");
    exit();

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    error_log("İlan ekleme hatası: " . $e->getMessage());
    
    header("Location: /plazanet/admin/properties/add-step3.php");
    exit();
}
?>