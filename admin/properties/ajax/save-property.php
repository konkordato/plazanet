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
        ilan_no, ilan_tarihi, baslik, aciklama, fiyat, 
        emlak_tipi, kategori, oda_sayisi, brut_metrekare, net_metrekare,
        bina_yasi, bulundugu_kat, kat_sayisi, isitma, banyo_sayisi, balkon,
        esyali, kullanim_durumu, site_icerisinde, aidat,
        il, ilce, mahalle, adres, durum, kimden,
        anahtar_no, mulk_sahibi_tel, danisman_notu,
        ekleyen_admin_id, created_at
    ) VALUES (
        :ilan_no, CURDATE(), :baslik, :aciklama, :fiyat,
        :emlak_tipi, :kategori, :oda_sayisi, :brut_metrekare, :net_metrekare,
        :bina_yasi, :bulundugu_kat, :kat_sayisi, :isitma, :banyo_sayisi, :balkon,
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat,
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
    
    // FOTOĞRAF YÜKLEME - SESSION'DAN AL
    $uploadPath = realpath(dirname(__FILE__) . '/../../../assets/uploads/properties/') . '/';
    
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }
    
    // Session'daki temp fotoğrafları işle
    if(isset($_SESSION['temp_photos']) && !empty($_SESSION['temp_photos'])) {
        
        foreach($_SESSION['temp_photos'] as $i => $file) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newName = 'img_' . $property_id . '_' . time() . '_' . $i . '.' . $ext;
            $target = $uploadPath . $newName;
            
            // Temp dosyayı hedef klasöre kopyala
            if(file_exists($file['path'])) {
                if(copy($file['path'], $target)) {
                    // Temp dosyayı sil
                    @unlink($file['path']);
                    
                    // Veritabanına ekle
                    $dbPath = 'assets/uploads/properties/' . $newName;
                    $isMain = ($i == 0) ? 1 : 0;
                    
                    try {
                        $stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, image_name, is_main) 
                                             VALUES (?, ?, ?, ?)");
                        $stmt->execute([$property_id, $dbPath, $file['name'], $isMain]);
                    } catch(Exception $e) {
                        // Hata olsa bile devam et
                        error_log("Resim veritabanı hatası: " . $e->getMessage());
                    }
                } else {
                    error_log("Dosya kopyalanamadı: " . $file['path'] . " -> " . $target);
                }
            } else {
                error_log("Temp dosya bulunamadı: " . $file['path']);
            }
        }
        
        // Temp klasörü temizle
        $tempDir = sys_get_temp_dir() . '/plaza_temp_' . session_id() . '/';
        if(is_dir($tempDir)) {
            array_map('unlink', glob("$tempDir/*"));
            @rmdir($tempDir);
        }
        
        // Session'dan temp fotoğrafları temizle
        unset($_SESSION['temp_photos']);
    }
    
    // Başarı
    $_SESSION['new_property_id'] = $property_id;
    $_SESSION['new_property_no'] = $ilan_no;
    $_SESSION['success'] = "İlan başarıyla eklendi! İlan No: " . $ilan_no;
    
    // Session'daki property_data'yı da temizle
    unset($_SESSION['property_data']);
    unset($_SESSION['emlak_tipi']);
    unset($_SESSION['kategori']);
    unset($_SESSION['alt_kategori']);
    
    header("Location: ../add-step4.php");
    exit();
    
} catch(Exception $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    error_log("Property save error: " . $e->getMessage());
    header("Location: ../add-step3.php");
    exit();
}
?>