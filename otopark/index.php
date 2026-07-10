<?php
session_start();

// Tarayıcı önbelleğini temizleme ve engelleme kodları:
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxy sunucuları için.

// Güvenlik Kontrolü: Giriş yapmayan göremez
if (!isset($_SESSION['user_id'])) {
    header("Location: ../kayıt_ol/index.php");
    exit;
}

include 'db.php';
$current_user_id = $_SESSION['user_id'];

// 🌟 KRİTİK DÜZENLEME: Avatarın profil sayfasında değiştiği an ana sayfada da anında güncellenmesi için veritabanından çekiyoruz.
$user_stmt = $db->prepare("SELECT avatar, username FROM users WHERE id = :id");
$user_stmt->execute(['id' => $current_user_id]);
$user_info = $user_stmt->fetch();

// Eğer kullanıcı veritabanında bir avatar seçtiyse onu alıyoruz, yoksa varsayılan ejderhayı atıyoruz
$current_avatar = ($user_info && !empty($user_info['avatar'])) ? $user_info['avatar'] : 'fa-dragon';

$isAdmin = (
    isset($_SESSION['role']) &&
    $_SESSION['role'] == 'admin'
);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$secilen_tarih = isset($_GET['filtre_tarih']) ? $_GET['filtre_tarih'] : '';

// İSTATİSTİKLER (Sadece Admin İçin doğrulanıyor)
if ($isAdmin) {
    $total_records = $db->query("SELECT COUNT(*) FROM parking_records")->fetchColumn();
    $today_records = $db->query("SELECT COUNT(*) FROM parking_records WHERE appointment_date = CURDATE()")->fetchColumn();
    $inside_vehicles = $db->query("SELECT COUNT(*) FROM parking_records WHERE status = 'içerde'")->fetchColumn();
    
    $total_revenue = $db->query("SELECT SUM(p.fee) FROM parking_records p 
                                 LEFT JOIN subscriptions s ON p.plate_number = s.plate_number AND s.end_date >= CURDATE()
                                 WHERE s.id IS NULL")->fetchColumn() ?? 0;
                                 
    $today_revenue = $db->query("SELECT SUM(p.fee) FROM parking_records p 
                                 LEFT JOIN subscriptions s ON p.plate_number = s.plate_number AND s.end_date >= CURDATE()
                                 WHERE s.id IS NULL AND p.appointment_date = CURDATE()")->fetchColumn() ?? 0;
}

// LİSTELEME SORGUSU
if ($isAdmin) {
    $sql = "SELECT p.*, s.end_date AS sub_end_date FROM parking_records p 
            LEFT JOIN subscriptions s ON p.plate_number = s.plate_number AND s.end_date >= CURDATE()
            WHERE 1=1";
    $params = [];
} else {
    $sql = "SELECT p.*, s.end_date AS sub_end_date FROM parking_records p 
            LEFT JOIN subscriptions s ON p.plate_number = s.plate_number AND s.end_date >= CURDATE()
            WHERE p.user_id = :user_id";
    $params = ['user_id' => $current_user_id];
}

if ($search !== '') {
    $sql .= " AND p.plate_number LIKE :search";
    $params['search'] = "%$search%";
}
if ($status_filter !== '') {
    $sql .= " AND p.status = :status";
    $params['status'] = $status_filter;
}
if ($secilen_tarih !== '') {
    $sql .= " AND p.appointment_date = :filtre_tarih";
    $params['filtre_tarih'] = $secilen_tarih;
}

$sql .= " ORDER BY p.appointment_date DESC, p.appointment_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Otopark Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        /* Üst Menü - Premium Mat Siyah */
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
        
        /* İstatistik Kartları */
        .stat-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            background: #ffffff;
            border-top: 3px solid transparent;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-top-color: #b91c1c;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
            background: #fee2e2;
            color: #b91c1c;
        }
        .icon-dark-style {
            background: #f1f5f9;
            color: #1e2229;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e2229;
            margin-top: 2px;
        }
        
        /* Genel Kart Yapısı */
        .custom-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        /* Sorgulama Alanı */
        .search-area {
            background: #272c35;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 25px;
            border-left: 5px solid #b91c1c;
        }
        .form-control-modern {
            border-radius: 8px;
            border: 1px solid #3f4754;
            background-color: #333a45 !important;
            color: #ffffff !important;
            padding: 10px 14px;
        }
        .form-control-modern::placeholder {
            color: #94a3b8;
        }
        .form-control-modern:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.25);
        }
        .form-control-modern option {
            background: #272c35;
            color: #ffffff;
        }

        /* Tablo Tasarımı */
        .table-theme th {
            background-color: #1e2229 !important;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 14px;
            border-bottom: 2px solid #b91c1c !important;
        }
        .table-theme td {
            padding: 14px;
            vertical-align: middle;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        
        /* Butonlar */
        .btn-modern {
            border-radius: 8px;
            font-weight: 600;
            padding: 8px 16px;
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
            color: white;
        }
        
        /* Durum Belirteçleri */
        .badge-modern {
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11px;
            display: inline-block;
        }
        .bg-waiting { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .bg-inside { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .bg-success-modern { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        
        /* STANDART GENİŞLİKTE VE KESİNLİKLE TAŞMAYAN TR PLAKA TASARIMI */
        .tr-plate {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            border: 2px solid #1e2229;
            border-radius: 5px;
            overflow: hidden;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            white-space: nowrap;
            word-break: keep-all;
            width: 115px; /* Standart Sabit Genişlik */
            height: 28px; /* Standart Sabit Yükseklik */
        }
        .tr-plate .blue-strip {
            background: #0033aa;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 0 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: stretch;
            flex-shrink: 0;
        }
        .tr-plate .plate-number {
            color: #1e2229;
            flex-grow: 1;
            text-align: center; /* Plaka numarasını tam ortalar */
            letter-spacing: 0.3px;
            text-transform: uppercase;
            padding: 0 4px;
            overflow: hidden;
        }

        /* Liste Açılır Kapanır Ok Animasyonu */
        .card-header[aria-expanded="false"] .transition-icon {
            transform: rotate(-90deg);
        }
        .transition-icon {
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body>

<header class="main-header shadow-sm">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title mb-0 fw-bold"><i class="fa-solid fa-square-parking me-2 text-danger"></i>ParkMaster</h2>
                <span class="text-muted uppercase" style="font-size: 10px; letter-spacing: 1px; color: #94a3b8 !important;">OTOPARK KONTROL MERKEZİ</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($isAdmin): ?>
                    <a href="abone_ol.php" class="btn btn-premium-red fw-semibold shadow-sm px-3 py-2 btn-modern"><i class="fa-solid fa-crown me-1"></i> Abonelik Modülü</a>
                <?php else: ?>
                    <a href="abone_ol.php" class="btn btn-outline-danger fw-semibold shadow-sm px-3 py-2 btn-modern"><i class="fa-solid fa-star me-1"></i> Abone Ol</a>
                <?php endif; ?>
                
                <a href="ekle.php" class="btn btn-light fw-bold px-3 py-2 border btn-modern"><i class="fa-solid fa-plus me-1 text-danger"></i> Yeni Randevu</a>
                
                <!-- PROFİL BUTONU: Seçilen harika ikon anında burada değişiyor! -->
                <a href="profil.php" class="btn fw-semibold px-3 py-2 btn-modern text-white d-flex align-items-center gap-1" style="background: #272c35;">
                    <i class="fa-solid <?= htmlspecialchars($current_avatar) ?> me-1 text-danger"></i> <?= htmlspecialchars($user_info['username'] ?? $_SESSION['username'] ?? 'Kullanıcı') ?>
                </a>
                
                <!-- YENİ PREMIUM ÇIKIŞ YAP BUTONU: Koyu gri arka planın içinde parlayan kırmızı renkte ve çok belirgin -->
                <a href="cikis.php" class="btn d-flex align-items-center justify-content-center shadow-sm text-white" 
                   style="background: #b91c1c; border-radius: 8px; width: 40px; height: 38px; transition: all 0.2s;"
                   onmouseover="this.style.background='#991b1b'; this.style.transform='scale(1.05)';" 
                   onmouseout="this.style.background='#b91c1c'; this.style.transform='scale(1)';"
                   onclick="return confirm('Oturumu kapatmak istediğinize emin misiniz?')"
                   title="Çıkış Yap">
                    <i class="fa-solid fa-right-from-bracket" style="font-size: 14px;"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container">

    <?php if ($isAdmin): ?>
    <div class="row g-3 mb-4">
        <div class="col">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon"><i class="fa-solid fa-car-side"></i></div>
                    <div class="stat-label">Toplam Kayıt</div>
                    <div class="stat-value"><?= $total_records ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="stat-label">Bugünkü Kayıt</div>
                    <div class="stat-value"><?= $today_records ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-icon"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>
                    <div class="stat-label">İçerdeki Araçlar</div>
                    <div class="stat-value"><?= $inside_vehicles ?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-icon icon-dark-style"><i class="fa-solid fa-money-bill-wave text-danger"></i></div>
                            <div class="stat-label">Günlük Gelir</div>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-0 toggle-revenue-btn" onclick="toggleRevenueVisibility()"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    <div class="stat-value revenue-value" style="color: #b91c1c;" data-value="<?= number_format($today_revenue, 2) ?> TL">*** TL</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-icon icon-dark-style"><i class="fa-solid fa-coins text-danger"></i></div>
                            <div class="stat-label">Toplam Gelir</div>
                        </div>
                        <button type="button" class="btn btn-link text-muted p-0 toggle-revenue-btn" onclick="toggleRevenueVisibility()"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    <div class="stat-value revenue-value" style="color: #b91c1c;" data-value="<?= number_format($total_revenue, 2) ?> TL">*** TL</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="search-area shadow-sm">
        <form method="GET" class="row g-3 align-items-center">
            <?php if(!empty($secilen_tarih)): ?><input type="hidden" name="filtre_tarih" value="<?= htmlspecialchars($secilen_tarih) ?>"><?php endif; ?>

            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary text-muted" style="border-radius: 8px 0 0 8px;"><i class="fa-solid fa-magnifying-glass text-danger"></i></span>
                    <input type="text" name="search" class="form-control form-control-modern border-start-0" style="border-radius: 0 8px 8px 0;" placeholder="Plaka girip süzün..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status_filter" class="form-select form-control-modern">
                    <option value="">Tüm Durumlar</option>
                    <option value="bekliyor" <?= $status_filter == 'bekliyor' ? 'selected' : '' ?>>⏳ Bekliyor</option>
                    <option value="içerde" <?= $status_filter == 'içerde' ? 'selected' : '' ?>>🚗 İçerde</option>
                    <option value="çıkış yaptı" <?= $status_filter == 'çıkış yaptı' ? 'selected' : '' ?>>✅ Çıkış Yaptı</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-premium-red btn-modern w-100">Sorgula</button>
                <a href="index.php" class="btn btn-outline-light btn-modern text-nowrap" style="border-color: #3f4754;">Sıfırla</a>
            </div>
        </form>
    </div>

    <div class="card custom-card">
        
        <div class="card-header bg-dark p-4 d-flex justify-content-between align-items-center" style="cursor: pointer; background: #1e2229 !important;" data-bs-toggle="collapse" data-bs-target="#aracListeCollapse" aria-expanded="true" aria-controls="aracListeCollapse">
            <h5 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-list me-2 text-danger"></i><?= $isAdmin ? 'Tüm Araç Kayıt Listesi' : 'Benim Randevularım' ?>
                <?php if(!empty($secilen_tarih)): ?>
                    <span class="badge bg-danger ms-2" style="font-size: 11px; background-color: #b91c1c !important;"><?= date('d.m.Y', strtotime($secilen_tarih)) ?></span>
                <?php endif; ?>
            </h5>
            <span class="text-white-50"><i class="fa-solid fa-chevron-down transition-icon"></i></span>
        </div>

        <div id="aracListeCollapse" class="collapse show">
            <div class="card-body p-4 pt-3">
                
                <form method="GET" action="" class="row g-2 mb-4 align-items-end" style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <?php if(!empty($search)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                    <?php if(!empty($status_filter)): ?><input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>"><?php endif; ?>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Tarihe Göre Süz</label>
                        <input type="date" name="filtre_tarih" class="form-control form-control-sm" style="border-radius: 6px; border: 1px solid #cbd5e1;" value="<?= htmlspecialchars($secilen_tarih) ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-dark px-3 fw-semibold" style="border-radius: 6px; background: #1e2229;"><i class="fa-solid fa-filter me-1 text-danger"></i> Filtrele</button>
                        <?php if(!empty($secilen_tarih)): ?>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary px-2" style="border-radius: 6px;"><i class="fa-solid fa-rotate-left"></i> Kaldır</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-theme">
                        <thead>
                            <tr>
                                <th>Plaka</th>
                                <th>Araç Sahibi</th>
                                <th>Telefon</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>İşlem</th>
                                <th>Park Yeri</th>
                                <th>Ücret</th>
                                <th>Durum</th>
                                <?php if ($isAdmin): ?><th class="text-end">Aksiyonlar</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($records) > 0): ?>
                                <?php foreach ($records as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="tr-plate">
                                                <div class="blue-strip">TR</div>
                                                <div class="plate-number"><?= htmlspecialchars($row['plate_number']) ?></div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['owner_name']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><i class="fa-regular fa-calendar text-muted me-1"></i> <?= $row['appointment_date'] ?></td>
                                        <td><i class="fa-regular fa-clock text-muted me-1"></i> <?= substr($row['appointment_time'], 0, 5) ?></td>
                                        <td><span class="badge bg-light text-dark border fw-medium"><?= htmlspecialchars($row['process_type']) ?></span></td>
                                        
                                        <td><span class="badge bg-dark px-2 py-1 fw-bold" style="border-radius: 5px; background: #1e2229 !important;"><?= htmlspecialchars($row['slot_name'] ?? '-') ?></span></td>
                                        
                                        <td class="fw-bold text-dark">
                                            <?php if (!empty($row['sub_end_date'])): ?>
                                                <span class="text-danger fw-bold"><i class="fa-solid fa-crown me-1"></i>VIP</span>
                                            <?php else: ?>
                                                <span><?= number_format($row['fee'], 2) ?> TL</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <?php if($row['status'] == 'bekliyor'): ?>
                                                <span class="badge-modern bg-waiting">Bekliyor</span>
                                            <?php elseif($row['status'] == 'içerde'): ?>
                                                <span class="badge-modern bg-inside">İçerde</span>
                                            <?php else: ?>
                                                <span class="badge-modern bg-success-modern">Çıkış Yaptı</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <?php if ($isAdmin): ?>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($row['status'] == 'bekliyor'): ?>
                                                    <a href="durum_guncelle.php?action=checkin&id=<?= $row['id'] ?>" class="btn btn-sm btn-success py-1 px-2" style="border-radius: 6px;" title="Giriş Yap"><i class="fa-solid fa-right-to-bracket"></i></a>
                                                <?php elseif ($row['status'] == 'içerde'): ?>
                                                    <a href="duzenle.php?id=<?= $row['id'] ?>&trigger=checkout" class="btn btn-sm btn-danger py-1 px-2" style="border-radius: 6px;" title="Çıkış Yap"><i class="fa-solid fa-right-from-bracket"></i></a>
                                                <?php endif; ?>
                                                <a href="duzenle.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border py-1 px-2" style="border-radius: 6px;" title="Düzenle"><i class="fa-solid fa-pen-to-square text-dark"></i></a>
                                                <a href="sil.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border py-1 px-2" style="border-radius: 6px;" title="Sil" onclick="return confirm('Silmek istiyor musunuz?')"><i class="fa-solid fa-trash text-danger"></i></a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $isAdmin ? 10 : 9 ?>" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open d-block fs-3 mb-2 text-secondary"></i> Eşleşen kayıt bulunamadı.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let revenueVisible = false;
function toggleRevenueVisibility() {
    revenueVisible = !revenueVisible;
    const revenueElements = document.querySelectorAll('.revenue-value');
    const toggleButtons = document.querySelectorAll('.toggle-revenue-btn i');
    revenueElements.forEach(element => {
        if (revenueVisible) {
            element.innerText = element.getAttribute('data-value');
        } else {
            element.innerText = '*** TL';
        }
    });
    toggleButtons.forEach(icon => {
        if (revenueVisible) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
}
</script>
</body>
</html>