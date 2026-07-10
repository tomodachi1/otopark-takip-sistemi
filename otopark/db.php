<?php
$host = 'localhost';
$dbname = 'otopark_db'; // Artık her şey bu veritabanında
$username = 'root';     // Kendi veritabanı kullanıcı adınız
$password = '';         // Kendi veritabanı şifreniz

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>