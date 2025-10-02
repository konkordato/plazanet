<?php
// HATA KONTROL SCRİPTİ
// Bu dosyayı test.php olarak kaydedin ve çalıştırın

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Plaza Emlak - Sistem Kontrolü</h1>";
echo "<hr>";

// 1. PHP Versiyonu
echo "<h2>1. PHP Versiyonu:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "<span style='color:green'>✓ PHP çalışıyor</span><br>";

// 2. Veritabanı Bağlantısı
echo "<h2>2. Veritabanı Bağlantısı:</h2>";
require_once 'config/database.php';

try {
    if($db) {
        echo "<span style='color:green'>✓ Veritabanı bağlantısı başarılı</span><br>";
        
        // Tablo kontrolü
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tablolar: " . implode(", ", $tables) . "<br>";
        
        // Properties tablosu kontrolü
        if(in_array('properties', $tables)) {
            echo "<span style='color:green'>✓ Properties tablosu mevcut</span><br>";
            
            // İlan sayısı
            $count = $db->query("SELECT COUNT(*) FROM properties")->fetchColumn();
            echo "Toplam ilan sayısı: " . $count . "<br>";
            
            // Son eklenen ilan
            $lastProperty = $db->query("SELECT id, baslik, created_at FROM properties ORDER BY id DESC LIMIT 1")->fetch();
            if($lastProperty) {
                echo "Son ilan: " . $lastProperty['baslik'] . " (ID: " . $lastProperty['id'] . ")<br>";
            }
        } else {
            echo "<span style='color:red'>✗ Properties tablosu bulunamadı!</span><br>";
        }
        
        // Property_images tablosu
        if(in_array('property_images', $tables)) {
            echo "<span style='color:green'>✓ Property_images tablosu mevcut</span><br>";
        }
        
        // Users tablosu
        if(in_array('users', $tables)) {
            echo "<span style='color:green'>✓ Users tablosu mevcut</span><br>";
            $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Kullanıcı sayısı: " . $userCount . "<br>";
        }
        
    } else {
        echo "<span style='color:red'>✗ Veritabanı bağlantısı başarısız!</span><br>";
    }
} catch(Exception $e) {
    echo "<span style='color:red'>✗ Veritabanı hatası: " . $e->getMessage() . "</span><br>";
}

// 3. Dosya İzinleri
echo "<h2>3. Dosya Yükleme Dizini:</h2>";
$uploadDir = 'uploads/properties/';
if(is_dir($uploadDir)) {
    echo "<span style='color:green'>✓ Upload dizini mevcut</span><br>";
    if(is_writable($uploadDir)) {
        echo "<span style='color:green'>✓ Upload dizini yazılabilir</span><br>";
    } else {
        echo "<span style='color:red'>✗ Upload dizinine yazılamıyor! (chmod 777 yapın)</span><br>";
    }
} else {
    echo "<span style='color:red'>✗ Upload dizini bulunamadı!</span><br>";
    echo "Oluşturulması gereken dizin: " . $uploadDir . "<br>";
}

// 4. Session Kontrolü
echo "<h2>4. Session Kontrolü:</h2>";
session_start();
$_SESSION['test'] = 'test_value';
if($_SESSION['test'] == 'test_value') {
    echo "<span style='color:green'>✓ Session çalışıyor</span><br>";
} else {
    echo "<span style='color:red'>✗ Session çalışmıyor!</span><br>";
}

// 5. Önemli Dosya Kontrolü
echo "<h2>5. Önemli Dosyalar:</h2>";
$files = [
    'config/database.php',
    'admin/index.php',
    'admin/properties/add-step1.php',
    'index.php',
    '.htaccess'
];

foreach($files as $file) {
    if(file_exists($file)) {
        echo "<span style='color:green'>✓</span> " . $file . " mevcut<br>";
    } else {
        echo "<span style='color:red'>✗</span> " . $file . " BULUNAMADI!<br>";
    }
}

// 6. Karakter Seti
echo "<h2>6. Karakter Seti:</h2>";
echo "Default charset: " . ini_get('default_charset') . "<br>";
echo "MB String: " . (extension_loaded('mbstring') ? '<span style="color:green">✓ Yüklü</span>' : '<span style="color:red">✗ Yüklü değil</span>') . "<br>";

// 7. Hata Logları
echo "<h2>7. Son Hatalar:</h2>";
$errorLog = ini_get('error_log');
if($errorLog && file_exists($errorLog)) {
    $errors = file_get_contents($errorLog);
    $lines = explode("\n", $errors);
    $lastErrors = array_slice($lines, -5);
    foreach($lastErrors as $error) {
        if(!empty($error)) {
            echo "<small>" . htmlspecialchars($error) . "</small><br>";
        }
    }
} else {
    echo "Hata log dosyası bulunamadı veya erişilemiyor<br>";
}

echo "<hr>";
echo "<h2>✅ TEST TAMAMLANDI</h2>";
echo "Bu sonuçları kopyalayıp gönderin!";
?>