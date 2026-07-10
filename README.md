# 🚗 ParkHK - Otopark Takip Sistemi

ParkHK, kullanıcıların otopark randevularını yönetebileceği, araç giriş-çıkış durumlarını (bekliyor, içerde, tamamlandı) takip edebileceği ve abonelik süreçlerini yönetebileceği modern bir web tabanlı otopark yönetim sistemidir.

---

## 🚀 Özellikler

- **Gelişmiş Profil Paneli:** Kullanıcıların e-posta, şifre ve kullanıcı adı bilgilerini güncelleyebileceği alan.
- **Efsanevi Avatar Sistemi:** Kullanıcıların profillerine özel karakter ikonları (Ejderha, Ninja, Astronot vb.) seçebilmesi.
- **Araç ve Randevu Geçmişi:** Kullanıcının geçmişe dönük tüm plaka, tarih, saat, işlem türü ve güncel durum (Bekliyor / İçerde / Tamamlandı) kayıtlarının listelenmesi.
- **TR Plaka Tasarımı:** Araç plakalarının gerçekçi Türkiye plakası formatında (mavi şeritli) gösterilmesi.
- **Abonelik Entegrasyonu:** Plaka üzerinden aktif aboneliklerin ve bitiş tarihlerinin kontrolü.

---

## 🛠️ Kullanılan Teknolojiler

- **Backend:** PHP (PDO ve Session yönetimi ile güvenli mimari)
- **Database:** MySQL
- **Frontend:** Bootstrap 5, Font Awesome 6 (Efsanevi İkonlar için), Google Fonts (Inter)
- **Tasarım Teması:** Premium Mat Siyah & Canlı Kırmızı Çizgiler

---

## ⚙️ Kurulum Gereksinimleri

Projeyi yerel bilgisayarınızda çalıştırmak için aşağıdaki araçların kurulu olması gerekir:
- **Laragon**, **XAMPP** veya **WampServer** (PHP 7.4 veya üzeri & MySQL)
- Tarayıcı (Chrome, Edge vb.)

### 💻 Yerel Kurulum Adımları

1. **Projeyi İndirin:**
   Proje dosyalarını yerel sunucunuzun kök dizinine (`C:/laragon/www/` veya `C:/xampp/htdocs/`) kopyalayın.

2. **Veritabanını Hazırlayın:**
   - `localhost/phpmyadmin` paneline gidin.
   - `parkmaster` (veya dilediğiniz isimde) bir veritabanı oluşturun.
   - Proje içindeki veritabanı yedeğini (`.sql` dosyasını) içeri aktarın (import edin).

3. **Veritabanı Bağlantısını Yapın:**
   - `db.php` dosyasını açarak veritabanı adı, kullanıcı adı ve şifre bilgilerinizi kendi yerel ayarlarınıza göre güncelleyin.

4. **Çalıştırın:**
   Tarayıcınızı açın ve `http://localhost/otopark-takip-sistemi` (klasör adınız neyse) yazarak sisteme giriş yapın.

---

## 🔒 Güvenlik Notları

- Sayfa başlarında tarayıcı önbelleklemesini önleyen `Cache-Control` başlıkları kullanılarak oturum güvenliği artırılmıştır.
- Kullanıcı şifreleri veritabanına doğrudan kaydedilmez; `password_hash()` fonksiyonu ile kriptolanarak (BCRYPT) saklanır.
- Veritabanı sorgularında SQL Injection açıklarını engellemek adına tamamen **PDO Prepared Statements (Hazırlıklı Sorgular)** tercih edilmiştir.

---

## 📄 Lisans

Bu proje eğitim ve kişisel gelişim amacıyla geliştirilmiştir. Ticari amaçla doğrudan kullanılamaz.
