[![badge](https://rozet.vixware.net/icon/coffee/badge/Buy%20Me%20a%20Coffee/yellow?style=single)](https://www.buymeacoffee.com/bnsware)

# ☕ Buy Me a Coffee - Webhook Yönetim Sistemi

Buy Me a Coffee için modern, güvenli ve mobil uyumlu webhook yönetim sistemi. Saf PHP ve JSON veritabanı ile geliştirilmiştir. Tüm bağışlarınızı, üyeliklerinizi ve satışlarınızı Buy Me a Coffee paneline girmeden gerçek zamanlı olarak izleyin.

![PHP Versiyonu](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![Lisans](https://img.shields.io/badge/lisans-MIT-green)
![Veritabanı](https://img.shields.io/badge/veritaban%C4%B1-JSON-yellow)

## ✨ Özellikler

### 🎯 Temel İşlevsellik
- **Gerçek Zamanlı Webhook İşleme** - Anlık bildirim işleme
- **HMAC-SHA256 İmza Doğrulama** - Güvenli webhook kimlik doğrulama
- **JSON Veritabanı** - MySQL/PostgreSQL gereksiz, kolay yedekleme
- **Admin Paneli** - Güzel ve sezgisel yönetim paneli
- **Mobil Uyumlu** - Tailwind CSS destekli, tüm cihazlarda çalışır

### 🎨 Kullanıcı Arayüzü
- **Modern Kart Düzeni** - Tablo yerine temiz grid tabanlı tasarım
- **Açılır-Kapanır Detaylar** - Endpoint URL ve raw payload için genişletilebilir bölümler
- **Türkçe Event İsimleri** - Emoji ile insan tarafından okunabilir event tipleri
- **Renkli Durum Göstergeleri** - Doğrulanmış/başarısız webhook'lar için görsel göstergeler
- **Tek Tıkla İşlemler** - Endpoint URL kopyalama, detay görüntüleme, kayıt silme

### 📊 Event Desteği
Tüm Buy Me a Coffee webhook event'lerini destekler:
- ☕ Bağışlar (alındı, iade edildi)
- ⭐ Üyelikler (başladı, güncellendi, iptal edildi)
- 🛒 Ekstra satın alımlar (oluşturuldu, güncellendi, iade edildi)
- 💼 Komisyon siparişleri (oluşturuldu, iade edildi)
- 🌟 Yinelenen bağışlar (başladı, güncellendi, iptal edildi)
- 🎁 İstek listesi ödemeleri (oluşturuldu, iade edildi)

### 🔒 Güvenlik Özellikleri
- Oturum tabanlı kimlik doğrulama
- Şifre korumalı admin paneli
- HMAC-SHA256 webhook imza doğrulama
- Otomatik geçersiz istek reddi
- Rate limiting hazır

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- JSON veritabanı dosyası için yazma izinleri
- HTTPS önerilir (üretim için)

## 🚀 Kurulum

### 1. İndirme

```bash
git clone https://github.com/bnsware/coffeeping.git
cd coffeeping
```

### 2. Sunucuya Yükleme

`webhook.php` dosyasını web sunucunuza yükleyin.

### 3. Yapılandırma

`webhook.php` dosyasının başındaki yapılandırmayı düzenleyin:

```php
// ZORUNLU: Buy Me a Coffee'den webhook secret'ınız
define('WEBHOOK_SECRET', 'buraya_webhook_secret_keyinizi_yazin');

// ZORUNLU: Varsayılan admin şifresini değiştirin
define('ADMIN_PASSWORD', 'güvenli_şifrenizi_buraya_yazin');

// Opsiyonel: Veritabanı dosya adını özelleştirin
define('DB_FILE', 'webhooks.json');
```

### 4. İzinleri Ayarlama

Script'in JSON veritabanını oluşturup yazabilmesini sağlayın:

```bash
chmod 755 /webhook/dizin/yolu
chmod 755 /webhook.php/dosya/yolu
```

### 5. Buy Me a Coffee'yi Yapılandırma

1. https://www.buymeacoffee.com/webhooks adresine gidin
2. "Create New Webhook" butonuna tıklayın
3. Webhook URL'nizi girin: `https://siteniz.com/webhook.php?endpoint`
4. Almak istediğiniz event'leri seçin
5. Secret Key'inizi kopyalayın ve koddaki `WEBHOOK_SECRET`'ı güncelleyin
6. Doğrulamak için "Send Test" butonuna tıklayın

## 🎮 Kullanım

### Admin Paneline Erişim

Webhook URL'nizi parametre olmadan ziyaret edin:
```
https://siteniz.com/webhook.php
```

Yapılandırılmış şifrenizle giriş yapın.

### Panel Özellikleri

**İstatistik Kartları**
- Alınan toplam webhook sayısı
- Doğrulanmış webhook sayısı
- Bugünkü webhook sayısı

**Webhook Kartları**
- Destekçi bilgilerini görüntüleme
- Bağış miktarlarını görme
- Mesajları okuma
- Doğrulama durumunu kontrol etme
- Detaylı payload görüntüleme
- Tekil webhook'ları silme

**İşlemler**
- Tam webhook bilgilerini görmek için "Detay"a tıklayın
- Webhook kaydını kaldırmak için "Sil"e tıklayın
- Veritabanını sıfırlamak için "Tümünü Temizle"yi kullanın
- Endpoint'inizi kopyalamak için "Webhook Endpoint URL"yi genişletin

### Webhook Endpoint'i

Sistem otomatik olarak webhook'ları şu adreste alır:
```
https://siteniz.com/webhook.php?endpoint
```

## 📡 Desteklenen Event'ler

| Event Tipi | Görünen İsim | Açıklama |
|------------|--------------|-----------|
| `donation.created` | ☕ Bağış Alındı | Yeni bağış alındı |
| `donation.refunded` | ↩️ Bağış İade Edildi | Bağış iade edildi |
| `support.created` | ☕ Destek Alındı | Yeni destek alındı |
| `support.refunded` | ↩️ Destek İade Edildi | Destek iade edildi |
| `extra_purchase.created` | 🛒 Ekstra Ürün Satışı | Ekstra ürün satın alındı |
| `extra_purchase.updated` | 🔄 Ürün Güncellendi | Satın alma güncellendi |
| `extra_purchase.refunded` | ↩️ Ürün İadesi | Satın alma iade edildi |
| `commission_order.created` | 💼 Komisyon Siparişi | Komisyon sipariş edildi |
| `commission_order.refunded` | ↩️ Komisyon İadesi | Komisyon iade edildi |
| `recurring_donation.started` | 🌟 Aylık Destek Başladı | Aylık destek başladı |
| `recurring_donation.updated` | 🔄 Aylık Destek Güncellendi | Aylık destek güncellendi |
| `recurring_donation.cancelled` | ❌ Aylık Destek İptal Edildi | Aylık destek iptal edildi |
| `membership.started` | ⭐ Üyelik Başladı | Üyelik başladı |
| `membership.updated` | 🔄 Üyelik Güncellendi | Üyelik güncellendi |
| `membership.cancelled` | ❌ Üyelik İptal Edildi | Üyelik iptal edildi |
| `wishlist_payment.created` | 🎁 İstek Listesi Ödemesi | İstek listesi ödemesi alındı |
| `wishlist_payment.refunded` | ↩️ İstek Listesi İadesi | İstek listesi ödemesi iade edildi |

## 🔧 Özelleştirme

### Özel İşlemler Ekleme

Webhook'lar alındığında özel mantık ekleyebilirsiniz. `handleWebhook()` fonksiyonunu düzenleyin:

```php
// Örnek: Yeni üyelikte email gönder
if ($webhookLog['event_type'] === 'membership.started') {
    mail('admin@siteniz.com', 'Yeni Üye!', 'Biri üye oldu!');
}

// Örnek: Discord bildirimi
if ($webhookLog['event_type'] === 'donation.created') {
    // Discord webhook'una gönder
    $discord_webhook = 'DISCORD_WEBHOOK_URL';
    // ... Discord bildirim kodunuz
}

// Örnek: Veritabanına ekle
// ... veritabanı ekleme kodunuz
```

### Maksimum Depolanan Webhook Sayısını Değiştirme

Varsayılan olarak sistem 1000 webhook'a kadar saklar. Bunu değiştirmek için:

```php
// handleWebhook() fonksiyonunda
if (count($db['webhooks']) > 1000) { // Bu sayıyı değiştirin
    $db['webhooks'] = array_slice($db['webhooks'], 0, 1000);
}
```

## 🔒 Güvenlik En İyi Uygulamaları

1. **Varsayılan Şifreyi Değiştirin** - Asla `admin123` kullanmayın
2. **Güçlü Secret Kullanın** - Buy Me a Coffee'den gerçek secret'ı kopyalayın
3. **HTTPS'yi Etkinleştirin** - Üretimde her zaman SSL sertifikası kullanın
4. **Dosya Erişimini Kısıtlayın** - Uygun dosya izinlerini ayarlayın (dosyalar için 644, dizinler için 755)
5. **Düzenli Yedekleme** - `webhooks.json` dosyanızı düzenli olarak yedekleyin
6. **Logları İzleyin** - Şüpheli webhook aktivitelerini kontrol edin

## 📁 Dosya Yapısı

```
proje-dizininiz/
├── webhook.php          # Ana sistem dosyası
├── webhooks.json        # Otomatik oluşturulan veritabanı (manuel düzenlemeyın)
└── README.md           # Bu dosya
```

## 🐛 Sorun Giderme

### Webhook'lar Gelmiyor

1. Endpoint URL'nizin doğru olduğunu kontrol edin
2. Buy Me a Coffee'de webhook'un etkin olduğunu doğrulayın
3. Sunucu hata loglarını kontrol edin
4. Dosya izinlerinin doğru olduğundan emin olun
5. Önce "Send Test" butonu ile test edin

### İmza Doğrulama Başarısız

1. `WEBHOOK_SECRET`'in Buy Me a Coffee ile eşleştiğini doğrulayın
2. Test webhook'larında geçerli imza olmayabilir (bu normaldir)
3. Test için gerçek bir bağış deneyin
4. Secret key'de boşluk olmadığını kontrol edin

### Admin Giriş Sorunları

1. Koddaki şifrenin doğru olduğunu doğrulayın
2. Tarayıcı çerezlerini temizleyin
3. PHP'de oturum desteğinin etkin olduğunu kontrol edin
4. Gizli/özel tarama modunu deneyin

### JSON Veritabanı Hataları

1. Dizin için yazma izinlerini kontrol edin
2. Disk alanının mevcut olduğundan emin olun
3. JSON'un bozuk olmadığını doğrulayın (sözdizimini doğrulayın)
4. Sıfırlamak için `webhooks.json`'u silin (önce yedekleyin!)

## 🤝 Katkıda Bulunma

Katkılar memnuniyetle karşılanır! Lütfen Pull Request göndermekten çekinmeyin.

1. Repository'yi fork'layın
2. Feature branch'inizi oluşturun (`git checkout -b feature/HarikaOzellik`)
3. Değişikliklerinizi commit edin (`git commit -m 'Harika özellik eklendi'`)
4. Branch'e push yapın (`git push origin feature/HarikaOzellik`)
5. Pull Request açın

## 📝 Lisans

Bu proje MIT Lisansı altında lisanslanmıştır - detaylar için LICENSE dosyasına bakın.

## 💬 Destek

- 🐛 Hataları [GitHub Issues](https://github.com/bnsware/coffeeping/issues) üzerinden bildirin
- 💡 Özellikleri [GitHub Discussions](https://github.com/bnsware/coffeeping/discussions) üzerinden isteyin
- ⭐ Faydalı bulduysanız bu repo'yu yıldızlayın!

## 🙏 Teşekkürler

- [Buy Me a Coffee](https://www.buymeacoffee.com) platformu için geliştirilmiştir
- [Tailwind CSS](https://tailwindcss.com) ile stillendirilmiştir
- Daha iyi webhook yönetimi ihtiyacından ilham alınmıştır

## 📊 Değişiklik Günlüğü

### v1.0.0 (İlk Sürüm)
- ✅ JSON veritabanı desteği
- ✅ Modern kart tabanlı arayüz
- ✅ Tüm event tipleri desteklenir
- ✅ HMAC-SHA256 doğrulama
- ✅ Mobil uyumlu tasarım
- ✅ Açılır-kapanır bölümler
- ✅ Türkçe event etiketleri
- ✅ Tek tıkla URL kopyalama

---

Buy Me a Coffee topluluğu için ☕ ve ❤️ ile yapıldı