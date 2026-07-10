<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../kayıt_ol/index.php");
    exit;
}

include 'db.php';
$current_user_id = $_SESSION['user_id'];

$update_success = false;
$update_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $avatar = trim($_POST['avatar']); // Örn: fa-dragon, fa-user-ninja
    $new_password = $_POST['new_password'];

    if (!empty($username) && !empty($email)) {
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $db->prepare("UPDATE users SET username = :username, email = :email, avatar = :avatar, password = :password WHERE id = :id");
                $update_stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'avatar' => $avatar,
                    'password' => $hashed_password,
                    'id' => $current_user_id
                ]);
            } else {
                $update_stmt = $db->prepare("UPDATE users SET username = :username, email = :email, avatar = :avatar WHERE id = :id");
                $update_stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'avatar' => $avatar,
                    'id' => $current_user_id
                ]);
            }
            
            // 🌟 KRİTİK ADIM: Diğer sayfalarda (Ana sayfa, header vb.) anında değişmesi için Session'ı güncelliyoruz!
            $_SESSION['user_avatar'] = $avatar;
            $_SESSION['username'] = $username; // İsim değiştiyse o da yansısın
            
            $update_success = true;
        } catch (PDOException $e) {
            $update_error = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}

$user_stmt = $db->prepare("SELECT username, email, role, avatar FROM users WHERE id = :id");
$user_stmt->execute(['id' => $current_user_id]);
$user_info = $user_stmt->fetch();

if (!$user_info) {
    header("Location: cikis.php");
    exit;
}

// Ejderha ve efsanevi kadro geri geldiği için varsayılanı 'fa-dragon' yapıyoruz
$current_avatar = (!empty($user_info['avatar'])) ? $user_info['avatar'] : 'fa-dragon';
$_SESSION['user_avatar'] = $current_avatar;
// Session senkronizasyonu garantiye alınıyor

$sql = "SELECT p.*, s.end_date AS sub_end_date FROM parking_records p 
        LEFT JOIN subscriptions s ON p.plate_number = s.plate_number AND s.end_date >= CURDATE()
        WHERE p.user_id = :user_id 
        ORDER BY p.appointment_date DESC, p.appointment_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['user_id' => $current_user_id]);
$my_records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim - ParkMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
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
        .custom-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        /* EFSANEVİ RADİANT İKON AVATAR ALANI */
        .profile-avatar-wrapper {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1e2229 0%, #b91c1c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 42px;
            margin: 0 auto 15px auto;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 20px rgba(185, 28, 28, 0.15);
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .profile-avatar-wrapper:hover {
            transform: scale(1.06) rotate(-5deg);
            background: linear-gradient(135deg, #b91c1c 0%, #1e2229 100%);
        }
        .profile-avatar-wrapper::after {
            content: "\f304";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            bottom: -2px;
            right: -2px;
            font-size: 11px;
            background: #b91c1c;
            color: white;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        /* YATAY EFSANEVİ LİSTE */
        .avatar-scroll-wrapper {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 15px 5px;
            scroll-behavior: smooth;
        }
        .avatar-scroll-wrapper::-webkit-scrollbar {
            height: 6px;
        }
        .avatar-scroll-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .avatar-scroll-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .avatar-option {
            flex: 0 0 65px;
            height: 65px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #475569;
        }
        .avatar-option:hover {
            border-color: #b91c1c;
            color: #b91c1c;
            transform: translateY(-3px);
            background: #fff5f5;
        }
        .avatar-option.selected {
            border-color: #b91c1c;
            background: #1e2229;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2);
        }

        .profile-info-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .profile-info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e2229;
            word-break: break-all;
        }
        .form-control-profile {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 14px;
        }
        .form-control-profile:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.15);
        }
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
        
        /* 👑 YENİ EKLEDİĞİM PREMIUM ÇIKISH TUŞU CSS TANIMI */
        .btn-logout-modern {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #64748b;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-logout-modern:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }
        
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
        
        .tr-plate {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            border: 2px solid #1e2229;
            border-radius: 5px;
            overflow: hidden;
            font-weight: 700;
            font-size: 13px;
            width: 115px; 
            height: 28px;
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
        }
        .tr-plate .plate-number {
            color: #1e2229;
            flex-grow: 1;
            text-align: center; 
        }
    </style>
</head>
<body>

<header class="main-header shadow-sm">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title mb-0 fw-bold"><i class="fa-solid fa-user-gear me-2 text-danger"></i>Profil Paneli</h2>
                <span class="text-muted uppercase" style="font-size: 10px; letter-spacing: 1px; color: #94a3b8 !important;">HESAP VE HAREKET ÖZETİ</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <a href="index.php" class="btn btn-dark fw-semibold px-3 py-2 btn-modern text-light border">
                    <i class="fa-solid fa-arrow-left me-2 text-danger"></i>Kontrol Paneline Dön
                </a>
                
                <a href="cikis.php" class="btn btn-logout-modern" onclick="return confirm('Oturumu kapatmak istediğinize emin misiniz?')">
                    <i class="fa-solid fa-right-from-bracket me-1 text-danger"></i> Çıkış
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container">
    
    <?php if ($update_success): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 10px; border-left: 4px solid #16a34a !important;">
            <i class="fa-solid fa-circle-check me-2"></i> Profil ve efsanevi yeni avatarınız başarıyla kaydedildi! Tüm sitede güncellendi. 
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card custom-card p-4 text-center">
                <div class="profile-avatar-wrapper" data-bs-toggle="modal" data-bs-target="#profileEditModal" title="Avatarı Değiştir">
                    <i class="fa-solid <?= htmlspecialchars($current_avatar) ?>" id="mainProfileIcon"></i>
                </div>
                <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user_info['username']) ?></h4>
                <span class="badge bg-dark mb-4 px-3 py-1 text-uppercase small" style="background: #1e2229 !important; letter-spacing: 0.5px;">
                    <?= htmlspecialchars($user_info['role'] == 'admin' ? 'Yönetici / Admin' : 'Standart Üye') ?>
                </span>
                
                <hr class="text-muted opacity-25 my-3">
         
                <div class="text-start px-2 mb-4">
                    <div class="mb-3">
                        <div class="profile-info-label"><i class="fa-regular fa-envelope me-1"></i> E-Posta Adresi</div>
                        <div class="profile-info-value"><?= htmlspecialchars($user_info['email']) ?></div>
                    </div>
                </div>

                <button type="button" class="btn btn-premium-red btn-modern w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#profileEditModal">
                    <i class="fa-solid fa-user-pen me-2"></i>Avatarı ve Bilgileri Düzenle
                </button>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card custom-card">
                <div class="card-header bg-dark p-4" style="background: #1e2229 !important;">
                    <h5 class="mb-0 fw-bold text-white"><i class="fa-solid fa-clock-history me-2 text-danger"></i>Araç ve Randevu Geçmişim</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-theme">
                            <thead>
                                <tr><th>Plaka</th><th>Tarih</th><th>Saat</th><th>İşlem</th><th>Durum</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_records as $row): ?>
                                <tr>
                                    <td><div class="tr-plate"><div class="blue-strip">TR</div><div class="plate-number"><?= htmlspecialchars($row['plate_number']) ?></div></div></td>
                                    <td><?= $row['appointment_date'] ?></td>
                                    <td><?= substr($row['appointment_time'], 0, 5) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['process_type']) ?></span></td>
                                    <td>
                                        <span class="badge-modern <?= $row['status']=='bekliyor'?'bg-waiting':($row['status']=='içerde'?'bg-inside':'bg-success-modern') ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<div class="modal fade" id="profileEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none;">
            <div class="modal-header text-white p-4" style="background: #1e2229; border-bottom: 3px solid #b91c1c;">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-gear me-2 text-danger"></i>Karakter Setini Seç</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="avatar" id="selectedAvatarInput" value="<?= htmlspecialchars($current_avatar) ?>">
                
                <div class="modal-body p-4 bg-light">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary mb-2">Efsanevi İkonunu Belirle 🔥</label>
                        <div class="avatar-scroll-wrapper">
                            <div class="avatar-option" data-icon="fa-dragon"><i class="fa-solid fa-dragon"></i></div>
                            <div class="avatar-option" data-icon="fa-user-ninja"><i class="fa-solid fa-user-ninja"></i></div>
                            <div class="avatar-option" data-icon="fa-user-astronaut"><i class="fa-solid fa-user-astronaut"></i></div>
                            <div class="avatar-option" data-icon="fa-ghost"><i class="fa-solid fa-ghost"></i></div>
                            <div class="avatar-option" data-icon="fa-mask"><i class="fa-solid fa-mask"></i></div>
                            <div class="avatar-option" data-icon="fa-crown"><i class="fa-solid fa-crown"></i></div>
                            <div class="avatar-option" data-icon="fa-bolt"><i class="fa-solid fa-bolt"></i></div>
                            <div class="avatar-option" data-icon="fa-car"><i class="fa-solid fa-car"></i></div>
                            <div class="avatar-option" data-icon="fa-motorcycle"><i class="fa-solid fa-motorcycle"></i></div>
                            <div class="avatar-option" data-icon="fa-fire"><i class="fa-solid fa-fire"></i></div>
                            <div class="avatar-option" data-icon="fa-shield-halved"><i class="fa-solid fa-shield-halved"></i></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control form-control-profile" value="<?= htmlspecialchars($user_info['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">E-Posta Adresi</label>
                        <input type="email" name="email" class="form-control form-control-profile" value="<?= htmlspecialchars($user_info['email']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Yeni Şifre (Değişmeyecekse Boş Bırakın)</label>
                        <input type="password" name="new_password" class="form-control form-control-profile" placeholder="••••••••">
                    </div>
                </div>
                <div class="modal-footer p-3 bg-white">
                    <button type="button" class="btn btn-light border btn-modern" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-premium-red btn-modern">Değişiklikleri Kaydet ✨</button>
                </div>
          </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const avatarInput = document.getElementById("selectedAvatarInput");
    const avatarOptions = document.querySelectorAll(".avatar-option");
    const mainProfileIcon = document.getElementById("mainProfileIcon");
    
    avatarOptions.forEach(opt => {
        if(opt.getAttribute("data-icon") === avatarInput.value) {
            opt.classList.add("selected");
            setTimeout(() => { opt.scrollIntoView({ block: 'nearest', inline: 'center' }); }, 200);
        }
        
        opt.addEventListener("click", function() {
            document.querySelector(".avatar-option.selected")?.classList.remove("selected");
            this.classList.add("selected");
            const iconClass = this.getAttribute("data-icon");
            avatarInput.value = iconClass;
            if(mainProfileIcon) { mainProfileIcon.className = "fa-solid " + iconClass; }
        });
    });
});
</script>
</body>
</html>