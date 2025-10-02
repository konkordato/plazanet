<?php
session_start();
echo "<h2>Session Verileri:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>POST Verileri:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

if(isset($_SESSION['property_data'])) {
    echo "<h2>Property Data Var!</h2>";
    echo "Başlık: " . $_SESSION['property_data']['baslik'];
}

if(isset($_SESSION['temp_photos'])) {
    echo "<h2>Fotoğraflar:</h2>";
    echo "Sayı: " . count($_SESSION['temp_photos']);
}
?>