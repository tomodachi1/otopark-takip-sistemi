<?php
session_start();

// Tarayıcı önbelleğini temizleme ve engelleme kodları:
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Güvenlik Kontrolü: Giriş yapmayan bu sayfayı göremez
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "../kayıt_ol/index.php";</script>';
    exit;
}

include 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $db->prepare("SELECT * FROM parking_records WHERE id = :id");
$stmt->execute(['id' => $id]);
$record = $stmt->fetch();

if (!$record) {
    die("Kayıt bulunamadı!");
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate_number = strtoupper(trim($_POST['plate_number']));
    $owner_name = trim($_POST['owner_name']);
    $phone = trim($_POST['phone']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $process_type = isset($_POST['process_type']) ? trim($_POST['process_type']) : 'Sadece Otopark';
    $fee = $_POST['fee'];
    $status = $_POST['status'];

    $check_stmt = $db->prepare("SELECT COUNT(*) FROM parking_records WHERE appointment_date = :adate AND appointment_time = :atime AND id != :id");
    $check_stmt->execute(['adate' => $appointment_date, 'atime' => $appointment_time, 'id' => $id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        $msg = '<div class="alert alert-danger custom-alert"><i class="fa-solid fa-triangle-exclamation me-2"></i>Uyarı: Güncellemek istediğiniz tarih ve saatte başka bir randevu mevcut!</div>';
    } else {
        $sql = "UPDATE parking_records SET 
                plate_number = :plate, owner_name = :owner, phone = :phone, 
                appointment_date = :adate, appointment_time = :atime, 
                process_type = :process, fee = :fee, status = :status 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'plate' => $plate_number,
            'owner' => $owner_name,
            'phone' => $phone,
            'adate' => $appointment_date,
            'atime' => $appointment_time,
            'process' => $process_type,
            'fee' => $fee,
            'status' => $status,
            'id' => $id
        ]);

        if ($status === 'çıkış yaptı' && !empty($record['slot_name'])) {
            $updateSlot = $db->prepare("UPDATE parking_slots SET status = 'bos' WHERE slot_name = ?");
            $updateSlot->execute([$record['slot_name']]);
        }

        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ParkHK | Kaydı Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        /* Üst Menü - Ana Sayfanın Birebir Aynısı */
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

        /* Düzenleme Kart Yapısı */
        .custom-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .form-label-custom {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        /* Girdi Alanları */
        .form-control-modern {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #1e2229 !important;
            padding: 11px 14px;
            font-weight: 500;
        }
        .form-control-modern:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.15);
        }
        
        .input-group-text-modern {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #64748b;
            border-radius: 8px 0 0 8px;
        }

        /* Butonlar */
        .btn-modern {
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 20px;
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
        
        /* Salt Okunur Alanlar İçin Soft Stil */
        .form-control-modern[readonly] {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }
    </style>
</head>
<body>

<header class="main-header shadow-sm">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title mb-0 fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-danger"></i>Park<span style="color: #b91c1c;">HK</span></h2>
                <span class="text-muted uppercase" style="font-size: 10px; letter-spacing: 1px; color: #94a3b8 !important;">KAYIT GÜNCELLEME PANELİ</span>
            </div>
  
            <div>
                <a href="index.php" class="btn btn-dark fw-semibold px-3 py-2 btn-modern" style="background: #272c35;">
                    <i class="fa-solid fa-arrow-left me-1 text-danger"></i> İptal / Geri Dön
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container" style="max-width: 750px;">
    
    <?php if (!empty($msg)) echo $msg; ?>

    <div class="card custom-card">
        <div class="card-header bg-dark p-4 d-flex justify-content-between align-items-center" style="background: #1e2229 !important;">
            <h5 class="mb-0 fw-bold text-white"><i class="fa-solid fa-circle-info me-2 text-danger"></i>Kayıt Detayları</h5>
            <span class="badge bg-danger p-2" style="background-color: #b91c1c !important; font-size: 12px; font-weight: 700;">ID: #<?= $record['id'] ?></span>
        </div>
        
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    
                    <div class="col-md-6">
                        <label class="form-label-custom">Araç Plakası</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-modern"><i class="fa-solid fa-id-card text-danger"></i></span>
                            <input type="text" name="plate_number" class="form-control form-control-modern text-uppercase font-monospace fw-bold" value="<?= htmlspecialchars($record['plate_number']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Araç Sahibi</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-modern"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="owner_name" class="form-control form-control-modern" value="<?= htmlspecialchars($record['owner_name']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Telefon</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-modern"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" name="phone" class="form-control form-control-modern" value="<?= htmlspecialchars($record['phone']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Yapılan İşlem / Ek Hizmet</label>
                        <select id="process_type" name="process_type" class="form-select form-control-modern fw-semibold">
                            <option value="Sadece Otopark" <?= $record['process_type'] == 'Sadece Otopark' ? 'selected' : '' ?>>🚗 Sadece Otopark (Ek Ücret Yok)</option>
                            <option value="Otopark + Yıkama" <?= $record['process_type'] == 'Otopark + Yıkama' ? 'selected' : '' ?>>🧽 Otopark + Yıkama (+300 TL)</option>
                            <option value="Detaylı Temizlik" <?= $record['process_type'] == 'Detaylı Temizlik' ? 'selected' : '' ?>>✨ Detaylı Temizlik (+500 TL)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Randevu Tarihi</label>
                        <input type="date" name="appointment_date" class="form-control form-control-modern" value="<?= $record['appointment_date'] ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Randevu Saati</label>
                        <input type="time" name="appointment_time" class="form-control form-control-modern" value="<?= substr($record['appointment_time'],0,5) ?>" required>
                    </div>

                    <div class="col-12"><hr class="my-2" style="border-color: #e2e8f0;"></div>

                    <div class="col-md-6">
                        <label class="form-label-custom text-muted">Giriş Tarihi (Sistem)</label>
                        <input type="date" id="entry_date" name="appointment_date" class="form-control form-control-modern" value="<?= $record['appointment_date'] ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom text-muted">Giriş Saati (Sistem)</label>
                        <input type="time" id="entry_time" name="appointment_time" class="form-control form-control-modern" value="<?= substr($record['appointment_time'],0,5) ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Çıkış Tarihi</label>
                        <input type="date" id="exit_date" name="exit_date" class="form-control form-control-modern" value="<?= $record['exit_date'] ?? '' ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Çıkış Saati</label>
                        <input type="time" id="exit_time" name="exit_time" class="form-control form-control-modern" value="<?= isset($record['exit_time']) ? substr($record['exit_time'],0,5) : '' ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Toplam Süre</label>
                        <input type="text" id="stayed_duration" class="form-control form-control-modern fw-semibold" style="color: #475569 !important;" readonly placeholder="Araç henüz çıkış yapmadı">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom">Hesaplanan Toplam Ücret (TL)</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-modern fw-bold text-success" style="background: #f0fdf4;"><i class="fa-solid fa-money-bill-wave text-success"></i></span>
                            <input type="number" step="0.01" id="fee" name="fee" class="form-control form-control-modern fw-bold text-success" style="background: #f0fdf4;" value="<?= $record['fee'] ?>" readonly>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label-custom">Araç Park Durumu</label>
                        <select name="status" class="form-select form-control-modern fw-semibold">
                            <option value="bekliyor" <?= $record['status'] == 'bekliyor' ? 'selected' : '' ?>>⏳ Bekliyor</option>
                            <option value="içerde" <?= $record['status'] == 'içerde' ? 'selected' : '' ?>>🚗 İçerde</option>
                            <option value="çıkış yaptı" <?= $record['status'] == 'çıkış yaptı' ? 'selected' : '' ?>>✅ Çıkış Yaptı</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-premium-red btn-modern w-100 fs-6 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Değişiklikleri Güncelle ve Kaydet
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
const HOURLY_RATE = 50.00;
function checkURLTrigger() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('trigger') === 'checkout') {
        const now = new Date();
        const localDate = now.toISOString().split('T')[0];
        const localTime = now.toTimeString().split(' ')[0].substring(0,5);
        
        document.getElementById('exit_date').value = localDate;
        document.getElementById('exit_time').value = localTime;
        
        const statusSelect = document.querySelector('select[name="status"]');
        if(statusSelect) statusSelect.value = "çıkış yaptı";
    }
}

function autoCalculateParking() {
    const entryDateVal = document.getElementById('entry_date').value;
    const entryTimeVal = document.getElementById('entry_time').value;
    const exitDateVal = document.getElementById('exit_date').value;
    const exitTimeVal = document.getElementById('exit_time').value;
    const processTypeVal = document.getElementById('process_type').value;
    if (!entryDateVal || !entryTimeVal || !exitDateVal || !exitTimeVal) return;

    const entryDateTime = new Date(`${entryDateVal}T${entryTimeVal}`);
    const exitDateTime = new Date(`${exitDateVal}T${exitTimeVal}`);
    let timeDiff = exitDateTime - entryDateTime;

    if (timeDiff < 0) {
        document.getElementById('stayed_duration').value = "Hatalı Zaman Seçimi!";
        return;
    }

    let totalMinutes = Math.floor(timeDiff / (1000 * 60));
    let hours = Math.floor(totalMinutes / 60);
    let mins = totalMinutes % 60;
    
    document.getElementById('stayed_duration').value = `${hours} Saat ${mins} Dakika (${totalMinutes} Dk)`;
    let baseParkingFee = totalMinutes * (HOURLY_RATE / 60);
    
    let extraFee = 0.00;
    if (processTypeVal === "Otopark + Yıkama") {
        extraFee = 300.00;
    } else if (processTypeVal === "Detaylı Temizlik") {
        extraFee = 500.00;
    }

    let totalFee = baseParkingFee + extraFee;
    document.getElementById('fee').value = totalFee.toFixed(2);
}

document.getElementById('exit_date').addEventListener('change', autoCalculateParking);
document.getElementById('exit_time').addEventListener('change', autoCalculateParking);
document.getElementById('process_type').addEventListener('change', autoCalculateParking);
window.addEventListener('DOMContentLoaded', () => {
    checkURLTrigger(); 
    autoCalculateParking(); 
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>