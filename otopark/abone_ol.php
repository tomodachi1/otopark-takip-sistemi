<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'db.php';
$user_id = $_SESSION['user_id'];
$mesaj = "";
$hata = "";

$check = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND end_date >= CURDATE() AND status = 'aktif'");
$check->execute([$user_id]);
$current_sub = $check->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_subscription'])) {
    $_SESSION['selected_package'] = $_POST['subscription_type'];
    header("Location: payment.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ParkMaster Premium | Abonelik Merkezi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #ffffff;
            --bg-gradient-end: #000000;
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
        }
        .main-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .pricing-card {
            background: #16181f;
            border: 2px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .pricing-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 42, 59, 0.4);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .pricing-card.selected-active {
            border-color: var(--primary-red);
            background: rgba(255, 42, 59, 0.03);
            box-shadow: 0 10px 25px rgba(255, 42, 59, 0.1);
        }
        .card-badge {
            position: absolute; top: 15px; right: -35px;
            background: var(--primary-red); color: #fff;
            padding: 4px 40px; font-size: 11px; font-weight: 700;
            transform: rotate(45deg); text-transform: uppercase;
        }
        .btn-premium-action {
            background: linear-gradient(90deg, var(--primary-red), #e01223);
            border: none; color: white;
            padding: 16px; font-weight: 700; border-radius: 12px;
            transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(255, 42, 59, 0.2);
        }
        .btn-premium-action:hover {
            background: linear-gradient(90deg, #e01223, var(--hover-red));
            box-shadow: 0 8px 20px rgba(255, 42, 59, 0.35);
        }
        .btn-outline-custom {
            border: 1px solid rgba(255, 255, 255, 0.1); color: var(--text-light);
            border-radius: 10px; font-weight: 600; transition: all 0.2s; background: rgba(255,255,255,0.03);
        }
        .btn-outline-custom:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .feature-list-item { font-size: 14px; color: #cbd5e0; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .feature-list-item i { color: var(--primary-red); }
        .custom-radio-input { position: absolute; opacity: 0; }
        .bg-status-panel { background: #16181f; border-radius: 12px; border: 1px solid rgba(255,255,255,0.03); }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 950px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge px-3 py-2 rounded-pill mb-2" style="background: rgba(255, 42, 59, 0.1); color: var(--primary-red) !important; font-weight: 600;">👑 PREMIUM MEMBERSHIP</span>
            <h2 class="fw-bold m-0 text-white">ParkMaster <span style="color: var(--primary-red);">Pass</span></h2>
        </div>
        <a href="../otopark/index.php" class="btn btn-outline-custom btn-sm px-3 py-2">
            <i class="fa-solid fa-arrow-left me-2"></i>Panale Dön
        </a>
    </div>

    <?php if(!empty($mesaj)): ?>
        <div class="alert alert-success border-0 bg-success bg-opacity-20 text-white p-3 rounded-3 mb-4"><?= $mesaj ?></div>
    <?php endif; ?>
    <?php if(!empty($hata)): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-20 text-white p-3 rounded-3 mb-4"><?= $hata ?></div>
    <?php endif; ?>

    <div class="card main-card p-4">
        
        <div class="p-3 bg-status-panel mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-dark rounded-3 text-danger">
                        <i class="fa-solid fa-crown fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-white m-0">Mevcut Abonelik Durumunuz</h6>
                    </div>
                </div>
                
                <div>
                    <?php if ($current_sub): ?>
                        <div class="bg-dark px-3 py-2 rounded-3 border border-secondary border-opacity-20">
                            <span class="fw-bold text-danger text-uppercase" style="font-size: 13px;"><?= htmlspecialchars($current_sub['subscription_type']) ?> VIP</span>
                            <span class="text-muted mx-2">|</span>
                            <span class="small text-white">Son: <?= date('d.m.Y', strtotime($current_sub['end_date'])) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="badge px-3 py-2 rounded-3 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10fw-semibold">
                            Aktif Abonelik Bulunmuyor
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$current_sub): ?>
            <div class="text-center mb-4">
                <h4 class="fw-bold text-white">Size En Uygun Abonelik Planı</h4>
            </div>

            <form method="POST">
                <div class="row g-4 justify-content-center">
                    
                    <!-- Aylık Paket -->
                    <div class="col-md-6">
                        <div class="card pricing-card p-4 h-100 selected-active" id="card_aylik" onclick="selectPremiumPlan('aylik')">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0 text-white">Aylık VIP</h5>
                                <i class="fa-solid fa-calendar-days text-danger fs-4"></i>
                            </div>
                            <h3 class="fw-bold text-white mb-3">1.500 TL <span class="fs-6 text-muted fw-normal">/ ay</span></h3>
                            <div class="mb-2">
                                <div class="feature-list-item"><i class="fa-solid fa-circle-check"></i> 7/24 Rezervasyon Önceliği</div>
                                <div class="feature-list-item"><i class="fa-solid fa-circle-check"></i> Giriş / Çıkış Ücreti Yok</div>
                            </div>
                            <input type="radio" class="custom-radio-input" name="subscription_type" id="sub_aylik" value="aylik" checked>
                        </div>
                    </div>

                    <!-- Yıllık Paket -->
                    <div class="col-md-6">
                        <div class="card pricing-card p-4 h-100" id="card_yillik" onclick="selectPremiumPlan('yillik')">
                            <div class="card-badge">Avantajlı</div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0 text-white">Yıllık VIP</h5>
                                <i class="fa-solid fa-bolt text-warning fs-4"></i>
                            </div>
                            <h3 class="fw-bold text-white mb-3">10.000 TL <span class="fs-6 text-muted fw-normal">/ yıl</span></h3>
                            <div class="mb-2">
                                <div class="feature-list-item"><i class="fa-solid fa-circle-check"></i> Tüm VIP Özellikleri Dahil</div>
                                <div class="feature-list-item"><i class="fa-solid fa-circle-check"></i> %45 Daha Ekonomik</div>
                            </div>
                            <input type="radio" class="custom-radio-input" name="subscription_type" id="sub_yillik" value="yillik">
                        </div>
                    </div>

                    <div class="col-md-12 mt-4">
                        <button type="submit" name="buy_subscription" class="btn btn-premium-action w-100 py-3">
                            <i class="fa-solid fa-credit-card me-2"></i>Ödeme Bilgilerine İlerle
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    function selectPremiumPlan(planType) {
        document.getElementById('sub_' + planType).checked = true;
        document.getElementById('card_aylik').classList.remove('selected-active');
        document.getElementById('card_yillik').classList.remove('selected-active');
        document.getElementById('card_' + planType).classList.add('selected-active');
    }
</script>
</body>
</html>