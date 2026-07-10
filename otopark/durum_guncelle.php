<?php
include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';
const HOURLY_RATE = 50.00; // Saatlik ücret tarifemiz

if ($id > 0) {
    if ($action === 'checkin') {
        // --- GİRİŞ YAPMA MEKANİZMASI ---
        $stmt = $db->prepare("UPDATE parking_records SET status = 'içerde', appointment_date = :cdate, appointment_time = :ctime WHERE id = :id");
        $stmt->execute([
            'cdate' => date('Y-m-d'),
            'ctime' => date('H:i:s'),
            'id' => $id
        ]);
    } 
    elseif ($action === 'checkout') {
        // --- TAM OTOMATİK ÇIKIŞ YAPMA MEKANİZMASI ---
        
        // 1. Aracın giriş bilgilerini ve durduğu park yerini (slot_name) veritabanından çekelim
        $stmt = $db->prepare("SELECT appointment_date, appointment_time, slot_name FROM parking_records WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();
        
        if ($record) {
            // Giriş zamanını birleştirelim
            $entry_datetime = new DateTime($record['appointment_date'] . ' ' . $record['appointment_time']);
            // Şu anki (çıkış) zamanını alalım
            $exit_datetime = new DateTime();
            // 2. Aradaki zaman farkını dakika cinsinden hesaplayalım
            $interval = $entry_datetime->diff($exit_datetime);
            // Toplam geçen dakikayı bulma (Gün ve saatleri de dakikaya çevirir)
            $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            if ($total_minutes < 1) $total_minutes = 1; // En az 1 dakika sayılsın
            
            // 3. Ücreti kuruşu kuruşuna hesaplayalım: Dakika * (50 / 60)
            $total_fee = $total_minutes * (HOURLY_RATE / 60);
            
            // 4. Veritabanını güncelleyelim (Durumu değiştir, ücreti yaz ve çıkış zamanını kaydet)
            $update_stmt = $db->prepare("UPDATE parking_records SET 
                status = 'çıkış yaptı', 
                fee = :fee,
                exit_date = :edate,
                exit_time = :etime
                WHERE id = :id");
            $update_stmt->execute([
                'fee' => round($total_fee, 2), // Virgülden sonra 2 basamağa yuvarlar
                'edate' => $exit_datetime->format('Y-m-d'),
                'etime' => $exit_datetime->format('H:i:s'),
                'id' => $id
            ]);

            // 🌟 YENİ EKLENEN KISIM: Eğer araca ait bir park yeri (slot) varsa o yeri otopark planında boşa çıkarıyoruz
            if (!empty($record['slot_name'])) {
                $updateSlot = $db->prepare("UPDATE parking_slots SET status = 'bos' WHERE slot_name = ?");
                $updateSlot->execute([$record['slot_name']]);
            }
        }
    }
}

// İşlem bitince kullanıcının ruhu bile duymadan ana sayfaya geri fırlatır
header("Location: index.php");
exit;