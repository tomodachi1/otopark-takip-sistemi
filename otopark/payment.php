<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../kayıtol/index.php");
    exit;
}
require_once 'db.php';

if (!isset($_SESSION['selected_package'])) {
    header("Location: abone_ol.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$package = $_SESSION['selected_package'];

$price = ($package === 'yillik') ? '10.000 TL' : '1.500 TL';
$packageName = ($package === 'yillik') ? 'Yıllık VIP Paket' : 'Aylık VIP Paket';
$packageDuration = ($package === 'yillik') ? '365 Gün Kesintisiz' : '30 Gün Boyunca';

$hata = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $card_name = trim($_POST['card_name']);
    $card_number = trim($_POST['card_number']);
    $card_expiry = trim($_POST['card_expiry']);
    $card_cvv = trim($_POST['card_cvv']);
    $plate_number = strtoupper(trim($_POST['plate_number'])); // Yeni plaka alanı (büyük harfe çevirir)
    
    if (!empty($card_name) && !empty($card_number) && !empty($card_expiry) && !empty($card_cvv) && !empty($plate_number)) {
        $interval = ($package === 'yillik') ? '+1 year' : '+1 month';
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime($interval));
        
        try {
            // 1. Eski aktif abonelikleri pasife çek
            $db->prepare("UPDATE subscriptions SET status = 'pasif' WHERE user_id = ? AND status = 'aktif'")->execute([$user_id]);
            
            // 2. Yeni aboneliği kullanıcının girdiği plaka ile kaydet
            $stmt = $db->prepare("INSERT INTO subscriptions (user_id, subscription_type, start_date, end_date, status, plate_number) VALUES (?, ?, ?, ?, 'aktif', ?)");
            
            if ($stmt->execute([$user_id, $package, $start_date, $end_date, $plate_number])) {
                unset($_SESSION['selected_package']);
                $_SESSION['payment_success'] = "Ödemeniz başarıyla alındı! VIP üyeliğiniz anında aktifleştirildi.";
                header("Location: abone_ol.php");
                exit;
            } else {
                $hata = "Sistemsel bir hata nedeniyle aboneliğiniz başlatılamadı.";
            }
        } catch (PDOException $e) {
            $hata = "Veritabanı Hatası: " . $e->getMessage();
        }
    } else {
        $hata = "Lütfen plaka numarası dahil tüm alanları doldurun.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkMaster | Güvenli Ödeme Portalı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #ffffff;
            --bg-gradient-end: #111216;
            --card-bg: #22252f;
            --primary-red: #ff2a3b;
            --hover-red: #d61b2a;
            --text-light: #f3f4f6;
            --text-muted: #a0aec0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .payment-container {
            max-width: 900px;
            width: 100%;
            margin: auto;
            padding: 20px;
        }

        .main-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 18px;
        }

        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .form-control-custom {
            width: 100%;
            background-color: #16181f !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 13px 16px 13px 45px;
            color: var(--text-light) !important;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(255, 42, 59, 0.15);
            background-color: #111318 !important;
        }

        .form-control-custom:focus + i {
            color: var(--primary-red);
        }

        /* 👑 SAĞ PANEL GÜNCELLEMESİ: Yazıların okunabilirliği artırıldı */
        .summary-panel {
            background: #16181f;
            border-radius: 16px;
            border: 1px solid rgba(255, 42, 59, 0.15); /* Hafif kırmızımsı çerçeve */
        }
        
        .summary-panel p.text-muted {
            color: #cbd5e0 !important; /* Soluk gri yerine açık beyaz/gri yapıldı */
        }

        .summary-panel .bg-dark {
            background-color: #111318 !important;
            color: #e2e8f0 !important;
        }

        .badge-package {
            background: rgba(255, 42, 59, 0.15);
            color: #ff4d5a;
            font-weight: 700;
            font-size: 13px;
        }

        .btn-pay {
            background: linear-gradient(90deg, var(--primary-red), #e01223);
            color: #fff;
            font-weight: 700;
            padding: 15px;
            border-radius: 12px;
            border: none;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 42, 59, 0.25);
        }

        .btn-pay:hover {
            background: linear-gradient(90deg, #e01223, var(--hover-red));
            box-shadow: 0 8px 22px rgba(255, 42, 59, 0.4);
            transform: translateY(-1px);
        }

        .btn-cancel {
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #cbd5e0;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s;
            background: transparent;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
        }

        /* Sanal Kredi Kartı Görseli */
        .virtual-card {
            background: linear-gradient(135deg, #32191d 0%, #1a1c23 100%);
            border: 1px solid rgba(255, 42, 59, 0.25);
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        
        .virtual-card .card-label {
            color: rgba(255, 255, 255, 0.5) !important; /* Okunabilirlik artırıldı */
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .virtual-card .card-value {
            color: #ffffff !important;
            font-family: 'Courier New', Courier, monospace;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>

<div class="container payment-container">
    <div class="card main-card p-4 p-md-5">
        
        <div class="row mb-4 align-items-center">
            <div class="col-8">
                <h3 class="fw-bold m-0 text-white"><i class="fa-solid fa-shield-halved text-danger me-2"></i>Güvenli Ödeme</h3>
                <p class="text-muted small m-0">256-Bit SSL sertifikası ile işlemleriniz uçtan uca şifrelenir.</p>
            </div>
            <div class="col-4 text-end">
                <i class="fa-brands fa-cc-visa fs-2 text-muted me-2"></i>
                <i class="fa-brands fa-cc-mastercard fs-2 text-muted"></i>
            </div>
        </div>

        <?php if (!empty($hata)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 rounded-3 fs-6 mb-4">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $hata ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="payment.php">
            <div class="row g-4">
                
                <div class="col-lg-7">
                    
                    <div class="virtual-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <i class="fa-solid fa-microchip fs-3 text-white-50"></i>
                            <span class="small fw-bold tracking-widest text-white" style="font-size: 13px; letter-spacing: 0.5px;">ParkMaster Pass VIP</span>
                        </div>
                        
                        <div class="fs-4 fw-bold card-value mb-4" id="card_preview_number" style="letter-spacing: 2.5px;">•••• •••• •••• ••••</div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="card-label">KART SAHİBİ</div>
                                <div class="fw-bold card-value" id="card_preview_name" style="font-size: 14px; letter-spacing: 1px;">KART SAHİBİ</div>
                            </div>
                            <div class="text-end">
                                <div class="card-label">SON KUL.</div>
                                <div class="fw-bold card-value" id="card_preview_expiry" style="font-size: 14px; letter-spacing: 1px;">MM/YY</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #ff4d5a; font-weight: 700;">Aboneliğin Tanımlanacağı Araç Plakası</label>
                        <div class="input-group-custom">
                            <input type="text" name="plate_number" class="form-control-custom" placeholder="Örn: 34ABC123 veya 06VIP99" required autocomplete="off" style="border: 1px solid rgba(255, 42, 59, 0.4);">
                            <i class="fa-solid fa-car-side" style="color: var(--primary-red);"></i>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Kart Üzerindeki İsim</label>
                        <div class="input-group-custom">
                            <input type="text" name="card_name" id="card_name_input" class="form-control-custom" placeholder="John Doe" required autocomplete="off">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Kart Numarası</label>
                        <div class="input-group-custom">
                            <input type="text" name="card_number" id="card_number_input" class="form-control-custom" placeholder="0000 0000 0000 0000" maxlength="19" required autocomplete="off">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-1">
                            <label class="form-label">Son Kullanma</label>
                            <div class="input-group-custom">
                                <input type="text" name="card_expiry" id="card_expiry_input" class="form-control-custom" placeholder="AA/YY" maxlength="5" required autocomplete="off">
                                <i class="fa-solid fa-calendar"></i>
                            </div>
                        </div>
                        <div class="col-6 mb-1">
                            <label class="form-label">CVV / CVC2</label>
                            <div class="input-group-custom">
                                <input type="password" name="card_cvv" class="form-control-custom" placeholder="•••" maxlength="3" required autocomplete="off">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="summary-panel p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h6 class="fw-bold text-white mb-3 pb-2 border-bottom border-secondary border-opacity-20">Sipariş Özeti</h6>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="badge badge-package px-2 py-1 rounded mb-1"><?= $packageName ?></span>
                                    <p class="small m-0" style="color: #e2e8f0;"><?= $packageDuration ?> geçerli üyelik</p>
                                </div>
                                <span class="fw-bold text-white fs-5"><?= $price ?></span>
                            </div>

                            <div class="p-3 rounded-3 text-white small mb-3 bg-dark bg-opacity-60">
                                <i class="fa-solid fa-circle-check text-success me-2"></i>Komisyon veya ek ücret alınmaz.
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center fs-5 fw-bold text-white mb-4">
                                <span>Toplam Tutar</span>
                                <span style="color: var(--primary-red); font-size: 24px; text-shadow: 0 0 10px rgba(255,42,59,0.2);"><?= $price ?></span>
                            </div>

                            <button type="submit" name="process_payment" class="btn btn-pay py-3 mb-2">
                                <i class="fa-solid fa-lock me-2"></i>Ödemeyi Güvenli Yap
                            </button>
                            
                            <a href="abone_ol.php" class="btn btn-cancel w-100 py-2 small text-center d-block text-decoration-none">
                                İşlemi İptal Et
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>

<script>
    const nameInput = document.getElementById('card_name_input');
    const numberInput = document.getElementById('card_number_input');
    const expiryInput = document.getElementById('card_expiry_input');

    const previewName = document.getElementById('card_preview_name');
    const previewNumber = document.getElementById('card_preview_number');
    const previewExpiry = document.getElementById('card_preview_expiry');

    nameInput.addEventListener('input', (e) => {
        previewName.textContent = e.target.value.toUpperCase() || 'KART SAHİBİ';
    });

    numberInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let matches = value.match(/\d{4,16}/g);
        let match = matches && matches[0] || '';
        let parts = [];

        for (let i=0, len=match.length; i<len; i+=4) {
            parts.push(match.substring(i, i+4));
        }

        if (parts.length > 0) {
            e.target.value = parts.join(' ');
        } else {
            e.target.value = value;
        }
        previewNumber.textContent = e.target.value || '•••• •••• •••• ••••';
    });

    expiryInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (value.length >= 2) {
            e.target.value = value.substring(0, 2) + '/' + value.substring(2, 4);
        } else {
            e.target.value = value;
        }
        previewExpiry.textContent = e.target.value || 'MM/YY';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>