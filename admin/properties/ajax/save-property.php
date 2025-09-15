<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';
require_once '../../includes/upload.php'; // Upload fonksiyonları

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
        ekleyen_admin_id
    ) VALUES (
        :ilan_no, CURDATE(), :baslik, :aciklama, :fiyat,
        :emlak_tipi, :kategori, :oda_sayisi, :brut_metrekare, :net_metrekare,
        :bina_yasi, :bulundugu_kat, :kat_sayisi, :isitma, :banyo_sayisi, :balkon,
        :esyali, :kullanim_durumu, :site_icerisinde, :aidat,
        :il, :ilce, :mahalle, :adres, 'aktif', 'Ofisten',
        :anahtar_no, :mulk_sahibi_tel, :danisman_notu,
        :admin_id
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
    
    // Fotoğrafları yükle (eğer varsa)
    if(isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $uploadResult = uploadPropertyImages($_FILES['photos'], $property_id, $db);
        
        if(!$uploadResult['success'] && count($uploadResult['errors']) > 0) {
            $_SESSION['photo_errors'] = $uploadResult['errors'];
        }
    }
    
    // Başarı mesajı
    $_SESSION['new_property_id'] = $property_id;
    $_SESSION['new_property_no'] = $ilan_no;
    $_SESSION['success'] = "İlan başarıyla eklendi! İlan No: " . $ilan_no;
    
    // Step 4'e yönlendir
    header("Location: ../add-step4.php");
    exit();
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    header("Location: ../add-step3.php");
    exit();
}
?>