<?php
// Oturum başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantı sınıfı
class Database {
    // HOSTING İÇİN AYARLAR
    private $host = "localhost";
    private $db_name = "plazaeml_plazanet"; // Hosting'deki veritabanı adı
    private $username = "plazaeml_ahm3t03";  // Hosting'deki kullanıcı adı
    private $password = "hi-fi-ahm3t";       // Hosting'deki şifre
    public $conn;

    // Veritabanına bağlan
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            
            // Türkçe karakterler için UTF-8 ayarı
            $this->conn->exec("SET NAMES utf8mb4");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
            $this->conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
            
        } catch(PDOException $exception) {
            // Hata mesajını loglayalım ama kullanıcıya detay vermeyelim
            error_log("Database Connection Error: " . $exception->getMessage());
            
            // Kullanıcıya genel hata mesajı
            die("Veritabanı bağlantı hatası oluştu. Lütfen yönetici ile iletişime geçin.");
        }

        return $this->conn;
    }
}

// Veritabanı bağlantısını başlat
$database = new Database();
$db = $database->getConnection();

// Yardımcı fonksiyonları tanımla
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
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

// Admin kontrolü
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true && 
               ($_SESSION['user_role'] ?? 'admin') === 'admin';
    }
}

// Kullanıcı kontrolü
if (!function_exists('isUser')) {
    function isUser() {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    }
}

// Güvenli redirect fonksiyonu
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}
?>