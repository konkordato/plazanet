<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
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
    
    // FOTOĞRAF YÜKLEME - DÜZELTİLMİŞ VERSİYON
    $uploadPath = dirname(__FILE__) . '/../../../assets/uploads/properties/';
    $uploadPath = realpath($uploadPath) . '/';
    
    // Klasör kontrolü
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }
    
    // Session'dan gelen dosyaları işle
    if(isset($_SESSION['property_files']['photos'])) {
        $files = $_SESSION['property_files']['photos'];
        
        for($i = 0; $i < count($files['name']); $i++) {
            if($files['error'][$i] == 0) {
                
                $temp = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                // Basit dosya adı
                $newName = 'img_' . $property_id . '_' . ($i+1) . '.' . $ext;
                $target = $uploadPath . $newName;
                
                // Geçici dosya hala var mı kontrol et
                if(file_exists($temp)) {
                    // copy kullanarak dosyayı kopyala
                    if(copy($temp, $target)) {
                        $dbPath = 'assets/uploads/properties/' . $newName;
                        $isMain = ($i == 0) ? 1 : 0;
                        
                        $stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, image_name, is_main) 
                                             VALUES (?, ?, ?, ?)");
                        $stmt->execute([$property_id, $dbPath, $name, $isMain]);
                    }
                } else if(isset($_FILES['photos']) && $_FILES['photos']['error'][$i] == 0) {
                    // Eğer session'da yoksa doğrudan $_FILES'dan dene
                    $temp = $_FILES['photos']['tmp_name'][$i];
                    if(move_uploaded_file($temp, $target)) {
                        $dbPath = 'assets/uploads/properties/' . $newName;
                        $isMain = ($i == 0) ? 1 : 0;
                        
                        $stmt = $db->prepare("INSERT INTO property_images (property_id, image_path, image_name, is_main) 
                                             VALUES (?, ?, ?, ?)");
                        $stmt->execute([$property_id, $dbPath, $name, $isMain]);
                    }
                }
            }
        }
        
        // Session'ı temizle
        unset($_SESSION['property_files']);
    }
    
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