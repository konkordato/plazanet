<?php
// Veritabanı bağlantı ayarları
class Database {
    private $host = "localhost";
    private $db_name = "plazanet";
    private $username = "root";
    private $password = "";
    public $conn;

    // Veritabanına bağlan
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Türkçe karakterler için UTF-8 ayarı
            $this->conn->exec("set names utf8mb4");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
            $this->conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
            // Hata ayıklama modu
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Bağlantı hatası: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// Veritabanı bağlantısını başlat
$database = new Database();
$db = $database->getConnection();

// Oturum başlat (tüm sayfalarda kullanılacak)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>