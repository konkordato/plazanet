<<<<<<< HEAD
<?php
// Oturumu başlat
session_start();

// Tüm oturum değişkenlerini temizle
$_SESSION = array();

// Oturum çerezini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu tamamen yok et
session_destroy();

// Giriş sayfasına yönlendir
header("Location: index.php");
exit();
=======
<?php
// Oturumu başlat
session_start();

// Tüm oturum değişkenlerini temizle
$_SESSION = array();

// Oturum çerezini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu tamamen yok et
session_destroy();

// Giriş sayfasına yönlendir
header("Location: index.php");
exit();
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
?> 