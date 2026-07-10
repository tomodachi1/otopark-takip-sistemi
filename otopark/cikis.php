<?php
session_start();

// Tüm session verilerini temizle
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Relative path ile yönlendir (diğer dosyalarla tutarlı)
header("Location: ../kayıt_ol/index.php");
exit;