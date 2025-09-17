<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['save_property'])) {
    header("Location: ../add-step1.php");
    exit();
}

try {
    // İlan no oluştur
    $ilan_no = 'PLZ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $data = $_POST;
    
    // İlanı kaydet
    $sql = "INSERT INTO properties (
        ilan_no, ilan_tarihi, baslik, aciklama, fiyat, metrekare_fiyat,
        emlak_tipi, kategori, oda_sayisi, brut_metrekare, net_metrekare,
        bina_yasi, bulundugu_kat, kat_sayisi, isitma, banyo_sayisi, balkon,
        esyali, kullanim_durumu, site_icerisinde, aidat,
        imar_durumu, ada_no, parsel_no, pafta_no, kaks_emsal, gabari,
        tapu_durumu, krediye_uygun, takas,
        il, ilce, mahalle, adres, durum, kimden,
        anahtar_no, mulk_sahibi_tel, danisman_notu,
        ekleyen_admin_id, created_at
    ) VALUES (
        :ilan_no, CURDATE(), :baslik, :aciklama, :fiyat, :metrekare_fiyat,
        :emlak_tipi, :kategori, :oda_sayisi, :brut_metrekare, :net_metrekare,
        :bina_yasi, :bulundugu_kat, :kat_sayisi, :isitma, :banyo_sayisi, :balkon,
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat,
        :imar_durumu, :ada_no, :parsel_no, :pafta_no, :kaks_emsal, :gabari,
        :tapu_durumu, :krediye_uygun, :takas,
        :il, :ilce, :mahalle, :adres, 'aktif', 'Ofisten',
        :anahtar_no, :mulk_sahibi_tel, :danisman_notu,
        :admin_id, NOW()
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
        ':anahtar_no' => $data['anahtar_no'] ?? null,
        ':mulk_sahibi_tel' => $data['mulk_sahibi_tel'] ?? null,
        ':danisman_notu' => $data['danisman_notu'] ?? null,
        ':admin_id' => $_SESSION['admin_id'] ?? 1
    ]);
    
    $property_id = $db->lastInsertId();
    
    // FOTOĞRAF YÜKLEME - BASİT ÇÖZÜM
    $uploadPath = dirname(__FILE__) . '/../../../assets/uploads/properties/';
    $uploadPath = str_replace('\\', '/', $uploadPath);
    
    // Klasör yoksa oluştur
    if (!is_dir($uploadPath)) {
        @mkdir($uploadPath, 0777, true);
    }
    
    // Session'daki temp fotoğrafları kontrol et
    if(isset($_SESSION['temp_photos']) && !empty($_SESSION['temp_photos'])) {
        
        $successCount = 0;
        
        foreach($_SESSION['temp_photos'] as $i => $file) {
            
            // Dosya yolu Windows uyumlu yap
            $tempPath = str_replace('\\', '/', $file['path']);
            
            // Dosya gerçekten var mı kontrol et
            if(file_exists($tempPath)) {
                
                // Yeni dosya adı
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newName = 'img_' . $property_id . '_' . time() . '_' . $i . '.' . $ext;
                $targetPath = $uploadPath . $newName;
                
                // Kopyala
                if(@copy($tempPath, $targetPath)) {
                    
                    // Veritabanına ekle
                    $dbPath = 'assets/uploads/properties/' . $newName;
                    $isMain = ($i == 0) ? 1 : 0;
                    
                    $stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, image_name, is_main) 
                                         VALUES (:pid, :path, :name, :main)");
                    $stmt->execute([
                        ':pid' => $property_id,
                        ':path' => $dbPath,
                        ':name' => $file['name'],
                        ':main' => $isMain
                    ]);
                    
                    $successCount++;
                    
                    // Temp dosyayı sil
                    @unlink($tempPath);
                }
            }
        }
        
        // Log tut
        if($successCount > 0) {
            error_log("Upload başarılı: " . $successCount . " fotoğraf yüklendi.");
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
    
    header("Location: ../add-step4.php");
    exit();
    
} catch(Exception $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    header("Location: ../add-step3.php");
    exit();
}
?>