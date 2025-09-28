<?php
// Oturum başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantı sınıfı
class Database {
    private $host = "localhost";
    private $db_name = "plazanet";
    private $username = "root";
    private $password = ""; // MEVCUT ŞİFRENİZ
    public $conn;

    // Veritabanına bağlan
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            
            // Türkçe karakterler için UTF-8 ayarı
            $this->conn->exec("set names utf8mb4");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
            $this->conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
            
        } catch(PDOException $exception) {
            echo "Bağlantı hatası: " . $exception->getMessage();
            echo "<br><br>Lütfen MySQL root şifrenizin 'hi-fi-ahm3t' olduğundan emin olun.";
            echo "<br>XAMPP Shell'de şu komutu deneyin: mysql -u root -p";
            echo "<br>Şifre sorduğunda: hi-fi-ahm3t";
        }

        return $this->conn;
    }
}

// Veritabanı bağlantısını başlat
$database = new Database();
$db = $database->getConnection();

// Yardımcı fonksiyonları SADECE tanımlı değilse ekle
if (!function_exists('cleanInput')) {
    function cleanInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

if (!function_exists('safeOutput')) {
    function safeOutput($data) {
        return htmlspecialchars($data);
    }
}

// Admin kontrolü - fonksiyon yoksa tanımla
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true && 
               ($_SESSION['user_role'] ?? 'admin') === 'admin';
    }
}

// Kullanıcı kontrolü - fonksiyon yoksa tanımla
if (!function_exists('isUser')) {
    function isUser() {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    }
}
?>