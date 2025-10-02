<?php
session_start();
session_destroy();

// Tüm output buffer'ları temizle
while (ob_get_level()) {
    ob_end_clean();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo "Cache temizlendi. <a href='index.php'>Ana sayfaya git</a>";
?>