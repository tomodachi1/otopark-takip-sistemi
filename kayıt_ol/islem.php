<?php

session_start();
require_once 'db.php';

$mesaj = "";
$hata = "";

// KAYIT OLMA
if (isset($_POST['register'])) {

    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if (!empty($user) && !empty($email) && !empty($pass)) {

        $check = $db->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $check->execute([$email,$user]);

        if($check->rowCount()>0){

            $hata="Bu kullanıcı adı veya e-posta zaten kullanılıyor!";

        }else{

            $hashed_pass=password_hash($pass,PASSWORD_DEFAULT);

            $query=$db->prepare("INSERT INTO users(username,email,password) VALUES(?,?,?)");

            if($query->execute([$user,$email,$hashed_pass])){

                $mesaj="Kayıt başarılı! Şimdi giriş yapabilirsiniz.";

            }else{

                $hata="Kayıt olurken hata oluştu.";

            }

        }

    }else{

        $hata="Lütfen tüm alanları doldurun.";

    }

}



// GİRİŞ
if(isset($_POST['login'])){

    $email=trim($_POST['email']);
    $pass=$_POST['password'];

    if(!empty($email) && !empty($pass)){

        $query=$db->prepare("SELECT * FROM users WHERE email=? OR username=?");
        $query->execute([$email,$email]);

        $user=$query->fetch(PDO::FETCH_ASSOC);

        if($user){

            if(password_verify($pass,$user['password'])){

                $_SESSION['user_id']=$user['id'];
                $_SESSION['username']=$user['username'];
                $_SESSION['role']=$user['role'];   // <-- EKLENDİ

                header("Location: ../otopark/index.php");
                exit;

            }else{

                $hata="Şifre yanlış!";

            }

        }else{

            $hata="Kullanıcı bulunamadı!";

        }

    }else{

        $hata="Lütfen tüm alanları doldurun.";

    }

}



// ŞİFREMİ UNUTTUM
if(isset($_POST['forgot'])){

    $email=trim($_POST['email']);

    $query=$db->prepare("SELECT * FROM users WHERE email=?");
    $query->execute([$email]);

    if($user=$query->fetch(PDO::FETCH_ASSOC)){

        $token=bin2hex(random_bytes(32));

        $expire=date("Y-m-d H:i:s",strtotime("+1 hour"));

        $update=$db->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");

        if($update->execute([$token,$expire,$user['id']])){

            $mesaj="
            Şifre sıfırlama bağlantınız oluşturuldu.<br><br>

            <a href='reset.php?token=$token' class='btn btn-warning'>
            Şifremi Sıfırla
            </a>";

        }else{

            $hata="Token oluşturulamadı.";

        }

    }else{

        $hata="Bu e-posta adresi bulunamadı.";

    }

}
?>