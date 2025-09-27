<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['save_property'])) {
    header("Location: ../add-step1.php");
    exit();
}

try {
    // İlan no oluştur
    $ilan_no = 'PLZ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $data = $_POST;

    // Kullanıcı ID'sini al
    $current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;

    // İlanı kaydet - user_id EKLENDİ
    $sql = "INSERT INTO properties (
        ilan_no, ilan_tarihi, baslik, aciklama, fiyat, metrekare_fiyat,
        emlak_tipi, kategori, oda_sayisi, brut_metrekare, net_metrekare,
        bina_yasi, bulundugu_kat, kat_sayisi, isitma, banyo_sayisi, balkon,
        esyali, kullanim_durumu, site_icerisinde, aidat,
        imar_durumu, ada_no, parsel_no, pafta_no, kaks_emsal, gabari,
        tapu_durumu, krediye_uygun, takas,
        il, ilce, mahalle, adres,latitude, longitude, durum, kimden,
        anahtar_no, mulk_sahibi_tel, danisman_notu,
        ekleyen_admin_id, user_id, created_at
    ) VALUES (
        :ilan_no, CURDATE(), :baslik, :aciklama, :fiyat, :metrekare_fiyat,
        :emlak_tipi, :kategori, :oda_sayisi, :brut_metrekare, :net_metrekare,
        :bina_yasi, :bulundugu_kat, :kat_sayisi, :isitma, :banyo_sayisi, :balkon,
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat,
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
        ':imar_durumu' => $data['imar_durumu'] ?? null,
        ':ada_no' => $data['ada_no'] ?? null,
        ':parsel_no' => $data['parsel_no'] ?? null,
        ':pafta_no' => $data['pafta_no'] ?? null,
        ':kaks_emsal' => $data['kaks_emsal'] ?? null,
        ':gabari' => $data['gabari'] ?? null,
        ':tapu_durumu' => $data['tapu_durumu'] ?? null,
        ':krediye_uygun' => $data['krediye_uygun'] ?? null,
        ':takas' => $data['takas'] ?? null,
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
    // Session temizle
    unset($_SESSION['temp_photos']);
    unset($_SESSION['property_data']);
    unset($_SESSION['emlak_tipi']);
    unset($_SESSION['kategori']);
    unset($_SESSION['alt_kategori']);

    // Başarı
    $_SESSION['new_property_id'] = $property_id;
    $_SESSION['new_property_no'] = $ilan_no;
    $_SESSION['success'] = "İlan başarıyla eklendi! İlan No: " . $ilan_no;
    // ============ SMS SİSTEMİ BAŞLANGIÇ ============
    try {
        // SMS sistemi aktifse devam et
        $sms_check = $db->query("SELECT is_active, test_mode FROM sms_settings WHERE id = 1");
        $sms_settings = $sms_check->fetch(PDO::FETCH_ASSOC);

        if ($sms_settings && $sms_settings['is_active'] == 1) {
            // NetGSM sınıfını dahil et
            require_once '../../../classes/NetGSM.php';

            // İlan linkini oluştur
            $base_url = "http://localhost/plazanet"; // Canlıda değiştirin
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
                $sms_mesaj = "Plaza Emlak Yeni İlan: " . $data['baslik'] . " - " . number_format($data['fiyat'], 0, ',', '.') . " TL";

                $netgsm->sendSMS($danisman['mobile'], $sms_mesaj, 'yeni_ilan', $user_id, [
                    'type' => 'danisman',
                    'id' => $danisman['id'],
                    'name' => $danisman['full_name'],
                    'property_id' => $property_id
                ]);
            }

            error_log("İlan #" . $property_id . " için SMS gönderimi tamamlandı.");
        }
    } catch (Exception $e) {
        // SMS hatası ilan eklemeyi engellemesin
        error_log("SMS gönderim hatası: " . $e->getMessage());
    }
    // ============ SMS SİSTEMİ BİTİŞ ============
    // Herkes için tebrikler sayfasına git
    header("Location: ../add-step4.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    header("Location: ../add-step3.php");
    exit();
}
