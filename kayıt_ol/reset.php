<?php
session_start();
require_once 'db.php';
$mesaj = "";
$hata = "";
$token_gecerli = false;
$user_id = null;
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $query = $db->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $query->execute([$token]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        if (strtotime($user['reset_expires']) > time()) {
            $token_gecerli = true;
            $user_id = $user['id'];
        } else {
            $hata = "Bu sıfırlama bağlantısının süresi dolmuş! Lütfen yeni bir talep oluşturun.";
        }
    } else {
        $hata = "Geçersiz veya daha önce kullanılmış şifre sıfırlama bağlantısı!";
    }
} 

if (isset($_POST['reset_pass'])) {
    $new_pass = $_POST['password'];
    $new_pass_retry = $_POST['password_retry'];
    $user_id_post = $_POST['user_id'];
    if (!empty($new_pass) && !empty($new_pass_retry)) {
        if ($new_pass === $new_pass_retry) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            if ($update->execute([$hashed_pass, $user_id_post])) {
                $mesaj = "Şifreniz başarıyla yenilendi! Şimdi giriş yapabilirsiniz.";
                $token_gecerli = false; 
            } else {
                $hata = "Şifre güncellenirken veritabanı kaynaklı bir hata oluştu.";
            }
        } else {
            $hata = "Girdiğiniz şifreler birbirisiyle uyuşmuyor!";
            $token_gecerli = true; 
            $user_id = $user_id_post;
        }
    } else {
        $hata = "Lütfen tüm alanları doldurun.";
        $token_gecerli = true;
        $user_id = $user_id_post;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkHK - Yeni Şifre Belirle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0a0c;
            --card-bg: rgba(18, 18, 22, 0.65);
            --primary-red: #e61e2a;
            --hover-red: #b8141d;
            --text-light: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(230, 30, 42, 0.25);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(230, 30, 42, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(230, 30, 42, 0.05) 0%, transparent 45%);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container { width: 100%; max-width: 450px; padding: 20px; }
        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7);
        }
        .form-label { font-size: 13px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
        .input-group-custom { position: relative; margin-bottom: 22px; }
        .input-group-custom i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .form-control-custom {
            width: 100%;
            background-color: rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px;
            padding: 14px 16px 14px 45px;
            color: var(--text-light) !important;
            font-size: 15px; transition: all 0.3s ease;
        }
        .form-control-custom:focus { outline: none; border-color: var(--primary-red); box-shadow: 0 0 12px rgba(230, 30, 42, 0.3); }
        .btn-red { background-color: var(--primary-red); color: #fff; font-weight: 600; padding: 14px; border-radius: 12px; border: none; width: 100%; transition: all 0.3s ease; }
        .btn-red:hover { background-color: var(--hover-red); }
        .auth-link { color: var(--primary-red); text-decoration: none; font-weight: 600; }
        .auth-link:hover { color: #ff4d5a; text-decoration: underline; }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <?php if (!empty($hata)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 rounded-3 fs-6 mb-4"><?= $hata ?></div>
        <?php endif; ?>
        <?php if (!empty($mesaj)): ?>
            <div class="alert alert-success bg-success text-white border-0 py-2 px-3 rounded-3 fs-6 mb-4"><?= $mesaj ?></div>
        <?php endif; ?>

        <?php if ($token_gecerli): ?>
            <h3 class="text-center mb-4 fw-bold"><i class="fa-solid fa-key me-2 text-danger"></i>Yeni Şifre Belirle</h3>
            <form action="reset.php?token=<?= htmlspecialchars($_GET['token']) ?>" method="POST">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                <div class="mb-1">
                    <label class="form-label">Yeni Şifreniz</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">Yeni Şifre (Tekrar)</label>
                    <div class="input-group-custom">
                        <input type="password" name="password_retry" class="form-control-custom" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock-open"></i>
                    </div>
                </div>
                <button type="submit" name="reset_pass" class="btn-red">Şifreyi Güncelle</button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="auth-link">
                <i class="fa-solid fa-arrow-left me-1"></i>Giriş Ekranına Dön
            </a>
        </div>
    </div>
</div>
</body>
</html>