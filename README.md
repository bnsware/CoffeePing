[🇹🇷 Türkçe dökümantasyon için tıklayın](README.tr.md)

# ☕ Buy Me a Coffee - Webhook Management System

A modern, secure, and mobile-responsive webhook management system for Buy Me a Coffee, built with pure PHP and JSON database. Monitor all your donations, memberships, and sales in real-time without touching Buy Me a Coffee dashboard.

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Database](https://img.shields.io/badge/database-JSON-yellow)

## ✨ Features

### 🎯 Core Functionality
- **Real-time Webhook Handling** - Instant notification processing
- **HMAC-SHA256 Signature Verification** - Secure webhook authentication
- **JSON Database** - No MySQL/PostgreSQL required, easy backup
- **Admin Dashboard** - Beautiful and intuitive management panel
- **Mobile Responsive** - Tailwind CSS powered, works on all devices

### 🎨 User Interface
- **Modern Card Layout** - Clean grid-based design instead of tables
- **Collapsible Details** - Expandable sections for endpoint URL and raw payloads
- **Turkish Event Labels** - Human-readable event types with emojis
- **Color-coded Status** - Visual indicators for verified/failed webhooks
- **One-Click Actions** - Copy endpoint URL, view details, delete entries

### 📊 Event Support
Supports all Buy Me a Coffee webhook events:
- ☕ Donations (created, refunded)
- ⭐ Memberships (started, updated, cancelled)
- 🛒 Extra purchases (created, updated, refunded)
- 💼 Commission orders (created, refunded)
- 🌟 Recurring donations (started, updated, cancelled)
- 🎁 Wishlist payments (created, refunded)

### 🔒 Security Features
- Session-based authentication
- Password-protected admin panel
- HMAC-SHA256 webhook signature verification
- Automatic invalid request rejection
- Rate limiting ready

## 📋 Requirements

- PHP 7.4 or higher
- Write permissions for JSON database file
- HTTPS recommended (for production)

## 🚀 Installation

### 1. Download

```bash
git clone https://github.com/yourusername/bmc-webhook-system.git
cd bmc-webhook-system
```

### 2. Upload to Server

Upload `webhook.php` to your web server.

### 3. Configure

Edit the configuration at the top of `webhook.php`:

```php
// REQUIRED: Your webhook secret from Buy Me a Coffee
define('WEBHOOK_SECRET', 'your_webhook_secret_key_here');

// REQUIRED: Change the default admin password
define('ADMIN_PASSWORD', 'your_secure_password_here');

// Optional: Customize database filename
define('DB_FILE', 'webhooks.json');
```

### 4. Set Permissions

Ensure the script can create and write to the JSON database:

```bash
chmod 755 /path/to/webhook.php
chmod 755 /path/to/directory
```

### 5. Configure Buy Me a Coffee

1. Go to https://www.buymeacoffee.com/webhooks
2. Click "Create New Webhook"
3. Enter your webhook URL: `https://yourdomain.com/webhook.php?endpoint`
4. Select the events you want to receive
5. Copy your Secret Key and update `WEBHOOK_SECRET` in the code
6. Click "Send Test" to verify

## 🎮 Usage

### Accessing Admin Panel

Visit your webhook URL without parameters:
```
https://yourdomain.com/webhook.php
```

Login with your configured password.

### Dashboard Features

**Statistics Cards**
- Total webhooks received
- Verified webhooks count
- Today's webhooks

**Webhook Cards**
- View supporter information
- See donation amounts
- Read messages
- Check verification status
- View detailed payload
- Delete individual webhooks

**Actions**
- Click "Detay" (Detail) to view full webhook information
- Click "Sil" (Delete) to remove a webhook entry
- Use "Tümünü Temizle" (Clear All) to reset database
- Expand "Webhook Endpoint URL" to copy your endpoint

### Webhook Endpoint

The system automatically receives webhooks at:
```
https://yourdomain.com/webhook.php?endpoint
```

## 📡 Supported Events

| Event Type | Display Name | Description |
|------------|--------------|-------------|
| `donation.created` | ☕ Bağış Alındı | New donation received |
| `donation.refunded` | ↩️ Bağış İade Edildi | Donation refunded |
| `support.created` | ☕ Destek Alındı | New support received |
| `support.refunded` | ↩️ Destek İade Edildi | Support refunded |
| `extra_purchase.created` | 🛒 Ekstra Ürün Satışı | Extra item purchased |
| `extra_purchase.updated` | 🔄 Ürün Güncellendi | Purchase updated |
| `extra_purchase.refunded` | ↩️ Ürün İadesi | Purchase refunded |
| `commission_order.created` | 💼 Komisyon Siparişi | Commission ordered |
| `commission_order.refunded` | ↩️ Komisyon İadesi | Commission refunded |
| `recurring_donation.started` | 🌟 Aylık Destek Başladı | Monthly support started |
| `recurring_donation.updated` | 🔄 Aylık Destek Güncellendi | Monthly support updated |
| `recurring_donation.cancelled` | ❌ Aylık Destek İptal Edildi | Monthly support cancelled |
| `membership.started` | ⭐ Üyelik Başladı | Membership started |
| `membership.updated` | 🔄 Üyelik Güncellendi | Membership updated |
| `membership.cancelled` | ❌ Üyelik İptal Edildi | Membership cancelled |
| `wishlist_payment.created` | 🎁 İstek Listesi Ödemesi | Wishlist payment received |
| `wishlist_payment.refunded` | ↩️ İstek Listesi İadesi | Wishlist payment refunded |

## 🔧 Customization

### Adding Custom Actions

You can add custom logic when webhooks are received. Edit the `handleWebhook()` function:

```php
// Example: Send email on new membership
if ($webhookLog['event_type'] === 'membership.started') {
    mail('admin@yoursite.com', 'New Member!', 'Someone subscribed!');
}

// Example: Discord notification
if ($webhookLog['event_type'] === 'donation.created') {
    // Send to Discord webhook
    $discord_webhook = 'YOUR_DISCORD_WEBHOOK_URL';
    // ... your Discord notification code
}

// Example: Add to database
// ... your database insertion code
```

### Changing Maximum Stored Webhooks

By default, the system stores up to 1000 webhooks. To change this:

```php
// In handleWebhook() function
if (count($db['webhooks']) > 1000) { // Change this number
    $db['webhooks'] = array_slice($db['webhooks'], 0, 1000);
}
```

## 🔒 Security Best Practices

1. **Change Default Password** - Never use the default `admin123`
2. **Use Strong Secret** - Copy the actual secret from Buy Me a Coffee
3. **Enable HTTPS** - Always use SSL certificate in production
4. **Restrict File Access** - Set proper file permissions (644 for files, 755 for directories)
5. **Regular Backups** - Backup your `webhooks.json` regularly
6. **Monitor Logs** - Check for suspicious webhook activities

## 📁 File Structure

```
your-project/
├── webhook.php          # Main system file
├── webhooks.json        # Auto-generated database (do not edit manually)
└── README.md           # This file
```

## 🐛 Troubleshooting

### Webhooks Not Arriving

1. Check your endpoint URL is correct
2. Verify webhook is enabled in Buy Me a Coffee
3. Check server error logs
4. Ensure file permissions are correct
5. Test with "Send Test" button first

### Signature Verification Failed

1. Verify `WEBHOOK_SECRET` matches Buy Me a Coffee
2. Test webhooks may not have valid signatures (this is normal)
3. Try a real donation for testing
4. Check for whitespace in secret key

### Admin Login Issues

1. Verify password is correct in code
2. Clear browser cookies
3. Check session support is enabled in PHP
4. Try incognito/private browsing mode

### JSON Database Errors

1. Check write permissions on directory
2. Ensure disk space is available
3. Verify JSON is not corrupted (validate syntax)
4. Delete `webhooks.json` to reset (backup first!)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 💬 Support

- 🐛 Report bugs via [GitHub Issues](https://github.com/yourusername/bmc-webhook-system/issues)
- 💡 Request features via [GitHub Discussions](https://github.com/yourusername/bmc-webhook-system/discussions)
- ⭐ Star this repo if you find it helpful!

## 🙏 Acknowledgments

- Built for [Buy Me a Coffee](https://www.buymeacoffee.com) platform
- Styled with [Tailwind CSS](https://tailwindcss.com)
- Inspired by the need for better webhook management

## 📊 Changelog

### v1.0.0 (Initial Release)
- ✅ JSON database support
- ✅ Modern card-based UI
- ✅ All event types supported
- ✅ HMAC-SHA256 verification
- ✅ Mobile responsive design
- ✅ Collapsible sections
- ✅ Turkish event labels
- ✅ One-click URL copy

---

Made with ☕ and ❤️ for the Buy Me a Coffee community