<?php
session_start();
echo "PHP Limitleri:<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "<br>Session Temp Photos: ";
echo "<pre>";
print_r($_SESSION['temp_photos'] ?? 'BOŞ');
echo "</pre>";

// Klasör kontrolü
$tempDir = 'assets/uploads/temp/';
echo "<br>Temp klasör var mı: " . (is_dir($tempDir) ? 'EVET' : 'HAYIR');
echo "<br>Yazılabilir mi: " . (is_writable($tempDir) ? 'EVET' : 'HAYIR');
?>