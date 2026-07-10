<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $db->prepare("DELETE FROM parking_records WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

header("Location: index.php");
exit;
?>