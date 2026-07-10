<?php
session_start();

// Tarayıcı önbelleğini temizleme ve engelleme kodları (Senin kodundaki gibi):
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Güvenlik Kontrolü: Giriş yapmayan göremez
if (!isset($_SESSION['user_id'])) {
    header("Location: ../kayıt_ol/index.php");
    exit;
}

include 'db.php';
$current_user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate_number = strtoupper(trim($_POST['plate_number']));
    $owner_name = trim($_POST['owner_name']);
    $phone = trim($_POST['phone']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $process_type = isset($_POST['process_type']) ? trim($_POST['process_type']) : 'Sadece Otopark';
    
    // 👑 ANA KODUNLA UYUMLU ALAN: slot_name doğrudan peron ismini alır (Örn: A-1, B-2)
    $slot_name = isset($_POST['slot_name']) ? trim($_POST['slot_name']) : ''; 
    
    $fee = ($process_type === 'Sadece Otopark') ? 100.00 : 250.00;
    
    if (!empty($plate_number) && !empty($owner_name) && !empty($appointment_date) && !empty($appointment_time) && !empty($slot_name)) {
        try {
            // Seçilen yerin o tarihte/saatte içeride olup olmadığını ana tablodaki slot_name ile sorgula
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM parking_records WHERE appointment_date = :adate AND slot_name = :slot_name AND status = 'içerde'");
            $check_stmt->execute(['adate' => $appointment_date, 'slot_name' => $slot_name]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $msg = '<div class="alert alert-danger custom-alert"><i class="fa-solid fa-triangle-exclamation me-2"></i>Seçtiğiniz tarihte bu park yeri zaten dolu!</div>';
            } else {
                // Ana kodunun veritabanı sütunlarına (slot_name ve user_id dahil) birebir kayıt
                $sql = "INSERT INTO parking_records (user_id, plate_number, owner_name, phone, appointment_date, appointment_time, process_type, fee, status, slot_name) 
                        VALUES (:user_id, :plate, :owner, :phone, :adate, :atime, :process, :fee, 'bekliyor', :slot_name)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'user_id' => $current_user_id,
                    'plate' => $plate_number,
                    'owner' => $owner_name,
                    'phone' => $phone,
                    'adate' => $appointment_date,
                    'atime' => $appointment_time,
                    'process' => $process_type,
                    'fee' => $fee,
                    'slot_name' => $slot_name
                ]);
                $msg = '<div class="alert alert-success custom-alert-success"><i class="fa-solid fa-circle-check me-2"></i>Randevu ve Yer Seçimi Başarıyla Kaydedildi!</div>';
            }
        } catch (PDOException $e) {
            $msg = '<div class="alert alert-danger custom-alert">Veritabanı Hatası: ' . $e->getMessage() . '</div>';
        }
    } else {
        $msg = '<div class="alert alert-danger custom-alert"><i class="fa-solid fa-circle-info me-2"></i>Lütfen bir park yeri seçin ve zorunlu alanları doldurun.</div>';
    }
}

// Şu an içeride olan araçların yerlerini çekerek görsel şemada "DOLU" olarak işaretleyelim
try {
    $dolu_yerler_stmt = $db->query("SELECT slot_name FROM parking_records WHERE status = 'içerde'");
    $dolu_yerler = $dolu_yerler_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $dolu_yerler = [];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ParkMaster | Yeni Randevu Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        /* Üst Menü - Senin Kodundaki Premium Mat Siyah */
        .main-header {
            background: #1e2229;
            border-bottom: 4px solid #b91c1c; 
            padding: 16px 0;
            margin-bottom: 30px;
        }
        .page-title {
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        /* Form Kart Yapısı */
        .custom-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .form-label-custom {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        /* Giriş Elemanları */
        .form-control-modern {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #1e2229;
            padding: 11px 14px;
            font-weight: 500;
        }
        .form-control-modern:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.15);
        }

        /* 👑 GÖRSEL PARK YERLERİ - SENİN RENK PALETİNLE IŞILDAYAN TASARIM */
        .parking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(75px, 1fr));
            gap: 12px;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .slot-box {
            background: #ffffff;
            border: 2px solid #1e2229; /* Siyah şerit çizgiler */
            color: #1e2229;
            border-radius: 8px;
            padding: 12px 5px;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .slot-box:hover:not(.dolu) {
            border-color: #b91c1c;
            color: #b91c1c;
            background: rgba(185, 28, 28, 0.03);
        }
        /* Seçilen Kutu: Dinamik Odaklanan Premium Kırmızı */
        .slot-box.secili {
            background: #b91c1c !important;
            border-color: #b91c1c !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(185, 28, 28, 0.25);
            transform: scale(1.03);
        }
        /* Dolu Kutu: Mat Gri ve Pasif */
        .slot-box.dolu {
            background: #cbd5e1 !important;
            border-color: #94a3b8 !important;
            color: #64748b !important;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Butonlar */
        .btn-modern {
            border-radius: 8px;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.2s ease;
        }
        .btn-premium-red {
            background-color: #b91c1c;
            border-color: #b91c1c;
            color: white;
        }
        .btn-premium-red:hover {
            background-color: #991b1b;
            border-color: #991b1b;
        }

        .custom-alert {
            background-color: #fee2e2 !important;
            border: 1px solid #fca5a5 !important;
            color: #b91c1c !important;
            border-radius: 10px;
            font-weight: 600;
        }
        .custom-alert-success {
            background-color: #f0fdf4 !important;
            border: 1px solid #bbf7d0 !important;
            color: #16a34a !important;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<header class="main-header shadow-sm">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title mb-0 fw-bold"><i class="fa-solid fa-square-parking me-2 text-danger"></i>ParkMaster</h2>
                <span class="text-muted uppercase" style="font-size: 10px; letter-spacing: 1px; color: #94a3b8 !important;">YENİ RANDEVU KAYDI</span>
            </div>
            <div>
                <a href="index.php" class="btn btn-dark fw-semibold px-3 py-2 btn-modern" style="background: #272c35;">
                    <i class="fa-solid fa-arrow-left me-1 text-danger"></i> Yönetim Paneli
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container" style="max-width: 850px;">
    
    <?php if (!empty($msg)) echo $msg; ?>

    <div class="card custom-card">
        <div class="card-header bg-dark p-4" style="background: #1e2229 !important;">
            <h5 class="mb-0 fw-bold text-white"><i class="fa-solid fa-plus me-2 text-danger"></i>Araç Kayıt ve Yer Rezerve Formu</h5>
        </div>
        
        <div class="card-body p-4">
            <form method="POST" action="">
                <!-- 👑 GİZLİ INPUT: Seçilen kutunun ismini (slot_name) taşır -->
                <input type="hidden" name="slot_name" id="selected_slot_name" value="" required>

                <div class="row g-3">
                    <!-- Plaka -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Araç Plakası <span class="text-danger">*</span></label>
                        <input type="text" name="plate_number" class="form-control form-control-modern w-100" placeholder="Örn: 34ABC123" required autocomplete="off">
                    </div>

                    <!-- Müşteri Adı -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Müşteri Adı Soyadı <span class="text-danger">*</span></label>
                        <input type="text" name="owner_name" class="form-control form-control-modern w-100" placeholder="Ahmet Yılmaz" required autocomplete="off">
                    </div>

                    <!-- Telefon -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Telefon Numarası</label>
                        <input type="tel" name="phone" class="form-control form-control-modern w-100" placeholder="0555 555 5555" autocomplete="off">
                    </div>

                    <!-- Hizmet -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Talep Edilen Hizmet</label>
                        <select name="process_type" class="form-select form-control-modern">
                            <option value="Sadece Otopark">Sadece Otopark Hizmeti</option>
                            <option value="Otopark + İç Dış Yıkama">Otopark + İç Dış Yıkama</option>
                        </select>
                    </div>

                    <!-- Tarih -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Giriş Tarihi <span class="text-danger">*</span></label>
                        <input type="date" name="appointment_date" class="form-control form-control-modern w-100" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Saat -->
                    <div class="col-md-6">
                        <label class="form-label-custom">Giriş Saati <span class="text-danger">*</span></label>
                        <input type="time" name="appointment_time" class="form-control form-control-modern w-100" value="<?= date('H:i') ?>" required>
                    </div>

                    <!-- 👑 GÖRSEL PARK YERLERİ SEÇİMİ -->
                    <div class="col-12 mt-4">
                        <label class="form-label-custom d-block mb-2">Boş Park Yerini Tıklayarak Seçin <span class="text-danger">*</span></label>
                        <div class="parking-grid">
                            <?php
                            // Otopark peron listesi (A-1'den A-20'ye kadar)
                            for ($i = 1; $i <= 20; $i++) {
                                $current_slot = "A-" . $i;
                                $is_dolu = in_array($current_slot, $dolu_yerler) ? 'dolu' : '';
                                echo "<div class='slot-box {$is_dolu}' data-name='{$current_slot}'>{$current_slot}</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Gönderim Butonu -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-premium-red btn-modern w-100 fs-6">
                            <i class="fa-solid fa-square-plus me-2"></i>Randevuyu Kaydet ve Listeye Ekle
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Park yeri tıklama olayını yöneten JS dinamiği
    document.querySelectorAll('.slot-box').forEach(box => {
        box.addEventListener('click', function() {
            if (this.classList.contains('dolu')) return; // Doluysa tıklatmayız

            // Önceki tüm seçili durumları temizle
            document.querySelectorAll('.slot-box').forEach(b => b.classList.remove('secili'));

            // Tıklananı seçili yap ve gizli inputa ismi ata
            this.classList.add('secili');
            document.getElementById('selected_slot_name').value = this.getAttribute('data-name');
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>