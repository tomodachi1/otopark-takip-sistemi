<?php require_once 'islem.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkHK - Giriş Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #1a1c23;
            --bg-gradient-end: #111216;
            --card-bg: #22252f;
            --primary-red: #ff2a3b; 
            --hover-red: #d61b2a;
            --text-light: #f3f4f6;
            --text-muted: #a0aec0;
            --border-color: rgba(255, 42, 59, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% -20%, rgba(255, 42, 59, 0.18) 0%, transparent 50%),
                        linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .auth-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-top: 3px solid var(--primary-red); 
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 30px rgba(255, 42, 59, 0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .auth-card:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 40px rgba(255, 42, 59, 0.06);
        }

        .brand-logo {
            font-size: 30px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 35px;
            letter-spacing: -0.5px;
            color: #fff;
        }

        .brand-logo span {
            color: var(--primary-red);
            text-shadow: 0 0 20px rgba(255, 42, 59, 0.4);
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 22px;
        }

        .input-group-custom > i:not(.password-toggle) {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .input-group-custom .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            z-index: 10;
            transition: color 0.2s ease;
        }

        .input-group-custom .password-toggle:hover {
            color: var(--primary-red);
        }

        .form-control-custom {
            width: 100%;
            background-color: #16181f !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px 45px 14px 45px;
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

        .btn-red {
            background: linear-gradient(90deg, var(--primary-red), #e01223);
            color: #fff;
            font-weight: 600;
            padding: 14px;
            border-radius: 12px;
            border: none;
            width: 100%;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(255, 42, 59, 0.25);
        }

        .btn-red:hover {
            background: linear-gradient(90deg, #e01223, var(--hover-red));
            box-shadow: 0 6px 20px rgba(255, 42, 59, 0.4);
            transform: translateY(-1px);
        }

        .auth-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .auth-link {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .auth-link:hover {
            color: #ff5c6a;
            text-decoration: underline;
        }

        .form-section {
            display: none;
            animation: fadeIn 0.4s ease forwards;
        }

        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        
        <!-- Logo -->
        <div class="brand-logo">
            <i class="fa-solid fa-square-p text-danger me-2"></i>Park<span>HK</span>
        </div>

        <!-- PHP Bildirimleri -->
        <?php if (!empty($hata)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 py-2 px-3 rounded-3 fs-6 mb-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $hata ?></div>
        <?php endif; ?>
        <?php if (!empty($mesaj)): ?>
            <div class="alert alert-success bg-success text-white border-0 py-2 px-3 rounded-3 fs-6 mb-3"><i class="fa-solid fa-circle-check me-2"></i><?= $mesaj ?></div>
        <?php endif; ?>

        <!-- ================= GİRİŞ YAP FORMU ================= -->
        <div id="login-form" class="form-section active">
            <form action="index.php" method="POST">
                <div class="mb-1">
                    <label class="form-label">E-posta veya Kullanıcı Adı</label>
                    <div class="input-group-custom">
                        <input type="text" name="email" class="form-control-custom" placeholder="kullanici@mail.com" required>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="mb-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label">Şifre</label>
                        <a href="javascript:void(0)" onclick="switchForm('forgot-form')" class="auth-link small mb-2" style="font-size: 12px;">Şifremi Unuttum?</a>
                    </div>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="login-pass" class="form-control-custom" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock"></i>
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePasswordVisibility('login-pass', this)"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-red">Giriş Yap</button>
            </form>
            
            <div class="auth-footer">
                Hesabın yok mu? <a href="javascript:void(0)" onclick="switchForm('register-form')" class="auth-link">Hemen Kayıt Ol</a>
            </div>
        </div>

        <!-- ================= KAYIT OL FORMU ================= -->
        <div id="register-form" class="form-section">
            <form action="index.php" method="POST">
                <div class="mb-1">
                    <label class="form-label">Kullanıcı Adı</label>
                    <div class="input-group-custom">
                        <input type="text" name="username" class="form-control-custom" placeholder="Kullanıcı adı belirleyin" required>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="mb-1">
                    <label class="form-label">E-posta Adresi</label>
                    <div class="input-group-custom">
                        <input type="email" name="email" class="form-control-custom" placeholder="ornek@mail.com" required>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>

                <div class="mb-1">
                    <label class="form-label">Şifre</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="register-pass" class="form-control-custom" placeholder="Güçlü bir şifre seç" required>
                        <i class="fa-solid fa-lock"></i>
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePasswordVisibility('register-pass', this)"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-red">Kayıt Ol</button>
            </form>
            
            <div class="auth-footer">
                Zaten bir hesabın var mı? <a href="javascript:void(0)" onclick="switchForm('login-form')" class="auth-link">Giriş Yap</a>
            </div>
        </div>

        <!-- ================= ŞİFREMİ UNUTTUM FORMU ================= -->
        <div id="forgot-form" class="form-section">
            <div class="text-muted small text-center mb-4">
                E-posta adresini yaz, sana sıfırlama bağlantısı gönderelim.
            </div>
            <form action="index.php" method="POST">
                <div class="mb-1">
                    <label class="form-label">Kayıtlı E-posta Adresiniz</label>
                    <div class="input-group-custom">
                        <input type="email" name="email" class="form-control-custom" placeholder="ornek@mail.com" required>
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>

                <button type="submit" name="forgot" class="btn-red">SIFIRLAMA LİNKİ GÖNDER</button>
            </form>
            
            <div class="auth-footer">
                <a href="javascript:void(0)" onclick="switchForm('login-form')" class="auth-link"><i class="fa-solid fa-arrow-left me-2"></i>Giriş Ekranına Dön</a>
            </div>
        </div>

    </div>
</div>

<script>
    function switchForm(targetId) {
        document.querySelectorAll('.form-section').forEach(form => {
            form.classList.remove('active');
        });
        document.getElementById(targetId).classList.add('active');
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => alert.style.display = 'none');
    }

    function togglePasswordVisibility(inputId, iconElement) {
        const passwordInput = document.getElementById(inputId);
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            iconElement.classList.remove('fa-eye');
            iconElement.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            iconElement.classList.remove('fa-eye-slash');
            iconElement.classList.add('fa-eye');
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>