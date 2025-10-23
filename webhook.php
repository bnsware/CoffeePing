<?php
/**
 * Buy Me a Coffee Webhook System
 * Saf PHP ile hazırlanmış, JSON veritabanı kullanan, mobil uyumlu webhook yönetim sistemi
 */

// Konfigürasyon
define('WEBHOOK_SECRET', 'your_webhook_secret_key_here'); // Buy Me a Coffee'den alacağınız secret key
define('DB_FILE', 'webhooks.json');
define('ADMIN_PASSWORD', 'admin123'); // Değiştirin!

// Event tiplerini Türkçeleştir
function getEventLabel($eventType) {
    $labels = [
        // Bağış/Destek
        'donation.created' => '☕ Bağış Alındı',
        'donation.refunded' => '↩️ Bağış İade Edildi',
        'support.created' => '☕ Destek Alındı',
        'support.refunded' => '↩️ Destek İade Edildi',
        
        // Ekstra Ürünler
        'extra_purchase.created' => '🛒 Ekstra Ürün Satışı',
        'extra_purchase.updated' => '🔄 Ürün Güncellendi',
        'extra_purchase.refunded' => '↩️ Ürün İadesi',
        
        // Komisyonlar
        'commission_order.created' => '💼 Komisyon Siparişi',
        'commission_order.refunded' => '↩️ Komisyon İadesi',
        
        // Aylık Destek
        'recurring_donation.started' => '🌟 Aylık Destek Başladı',
        'recurring_donation.updated' => '🔄 Aylık Destek Güncellendi',
        'recurring_donation.cancelled' => '❌ Aylık Destek İptal Edildi',
        
        // Üyelik
        'membership.started' => '⭐ Üyelik Başladı',
        'membership.updated' => '🔄 Üyelik Güncellendi',
        'membership.cancelled' => '❌ Üyelik İptal Edildi',
        
        // Wishlist (İstek Listesi)
        'wishlist_payment.created' => '🎁 İstek Listesi Ödemesi',
        'wishlist_payment.refunded' => '↩️ İstek Listesi İadesi'
    ];
    
    return $labels[$eventType] ?? '📌 ' . ucfirst(str_replace(['.', '_'], ' ', $eventType));
}

// JSON veritabanı okuma
function readDatabase() {
    if (!file_exists(DB_FILE)) {
        return ['webhooks' => []];
    }
    $content = file_get_contents(DB_FILE);
    return json_decode($content, true) ?: ['webhooks' => []];
}

// JSON veritabanı yazma
function writeDatabase($data) {
    file_put_contents(DB_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Webhook signature doğrulama
function verifyWebhookSignature($payload, $signature) {
    $calculatedSignature = hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($calculatedSignature, $signature);
}

// Webhook işleme
function handleWebhook() {
    // Sadece POST isteklerini kabul et
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    // Raw payload al
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_BMC_SIGNATURE'] ?? '';

    // Signature doğrula
    $verified = verifyWebhookSignature($payload, $signature);

    // Payload'u parse et
    $data = json_decode($payload, true);

    // Veritabanından oku
    $db = readDatabase();

    // Event tipine göre veri çıkarma
    $eventType = $data['type'] ?? 'unknown';
    $eventData = $data['data'] ?? [];
    
    // Destekçi bilgilerini al (farklı event tiplerinde farklı yerlerde olabilir)
    $supporterName = $eventData['supporter_name'] 
                  ?? $data['supporter']['supporter_name'] 
                  ?? 'N/A';
    
    $supporterEmail = $eventData['supporter_email'] 
                   ?? $data['supporter']['supporter_email'] 
                   ?? 'N/A';
    
    // Miktar bilgisi (farklı formatlarda olabilir)
    $amount = 'N/A';
    if (isset($eventData['coffee_count'])) {
        $amount = $eventData['coffee_count'] . ' coffee';
    } elseif (isset($eventData['amount'])) {
        $amount = '$' . $eventData['amount'];
    } elseif (isset($data['support_coffees'])) {
        $amount = $data['support_coffees'] . ' coffee';
    }
    
    // Mesaj
    $message = $eventData['support_note'] 
            ?? $eventData['message'] 
            ?? $data['support_note'] 
            ?? '';
    
    // Yeni webhook logu
    $webhookLog = [
        'id' => uniqid('wh_', true),
        'event_type' => $eventType,
        'supporter_name' => $supporterName,
        'supporter_email' => $supporterEmail,
        'amount' => $amount,
        'message' => $message,
        'payload' => $data,
        'signature' => $signature,
        'verified' => $verified,
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Webhook logunu ekle (başa ekle - en yeniler önce)
    array_unshift($db['webhooks'], $webhookLog);

    // Maksimum 1000 kayıt tut (performans için)
    if (count($db['webhooks']) > 1000) {
        $db['webhooks'] = array_slice($db['webhooks'], 0, 1000);
    }

    // Veritabanına kaydet
    writeDatabase($db);

    // Hızlı 200 OK yanıtı dön
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'verified' => $verified,
        'event' => $webhookLog['event_type']
    ]);

    // Buraya özel işlemlerinizi ekleyebilirsiniz:
    // - Email gönderme
    // - Discord/Slack bildirimi
    // - Üye ekleme vb.
    
    // Örnek: Üyelik başladığında email gönder
    // if ($webhookLog['event_type'] === 'membership.started') {
    //     mail('admin@site.com', 'Yeni Üye!', 'Yeni bir üyelik başladı!');
    // }
}

// İstatistik hesaplama
function getStats() {
    $db = readDatabase();
    $webhooks = $db['webhooks'];
    
    $total = count($webhooks);
    $verified = count(array_filter($webhooks, fn($w) => $w['verified']));
    $today = count(array_filter($webhooks, function($w) {
        return date('Y-m-d', strtotime($w['created_at'])) === date('Y-m-d');
    }));
    
    return ['total' => $total, 'verified' => $verified, 'today' => $today];
}

// Admin paneli - basit kimlik doğrulama
session_start();
function checkAuth() {
    if (!isset($_SESSION['authenticated'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            if ($_POST['password'] === ADMIN_PASSWORD) {
                $_SESSION['authenticated'] = true;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                return 'Hatalı şifre!';
            }
        }
        return false;
    }
    return true;
}

// Çıkış yap
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// AJAX detay getirme
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_detail' && isset($_GET['id'])) {
    $db = readDatabase();
    $webhook = null;
    foreach ($db['webhooks'] as $w) {
        if ($w['id'] === $_GET['id']) {
            $webhook = $w;
            break;
        }
    }
    
    header('Content-Type: application/json');
    if ($webhook) {
        echo json_encode([
            'event_type' => $webhook['event_type'],
            'supporter_name' => $webhook['supporter_name'],
            'supporter_email' => $webhook['supporter_email'],
            'message' => $webhook['message'],
            'payload' => json_encode($webhook['payload']),
            'signature' => $webhook['signature'],
            'verified' => $webhook['verified']
        ]);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// AJAX webhook silme
if (isset($_GET['ajax']) && $_GET['ajax'] === 'delete' && isset($_GET['id'])) {
    session_start();
    if (!isset($_SESSION['authenticated'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $db = readDatabase();
    $db['webhooks'] = array_filter($db['webhooks'], fn($w) => $w['id'] !== $_GET['id']);
    $db['webhooks'] = array_values($db['webhooks']); // Reindex
    writeDatabase($db);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// AJAX tüm webhook'ları temizle
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clear_all') {
    session_start();
    if (!isset($_SESSION['authenticated'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    writeDatabase(['webhooks' => []]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Webhook endpoint mı yoksa admin paneli mi?
$isWebhook = isset($_GET['endpoint']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['password']));

if ($isWebhook) {
    handleWebhook();
    exit;
}

// Admin paneli
$authStatus = checkAuth();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Me a Coffee - Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .badge-verified { @apply bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded; }
        .badge-failed { @apply bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        details summary::-webkit-details-marker {
            display: none;
        }
        details[open] summary svg:last-child {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-gray-50">
    
<?php if ($authStatus !== true): ?>
    <!-- Login Ekranı -->
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">Kontrol Paneli</h2>
                    <p class="text-gray-600 mt-2">Güvenli giriş yapın</p>
                </div>

                <?php if ($authStatus === 'Hatalı şifre!'): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                        ⚠️ Hatalı şifre!
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Şifre</label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    </div>
                    <button type="submit" 
                        class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-3 rounded-lg transition duration-200">
                        Giriş Yap
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Admin Paneli -->
    <div class="min-h-screen p-4 md:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <span class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center">
                                ☕
                            </span>
                            Yönetim Paneli
                        </h1>
                        <p class="text-gray-600 mt-1">Buy Me a Coffee bildirimleri (JSON Database)</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="clearAllWebhooks()" 
                            class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Tümünü Temizle
                        </button>
                        <a href="?logout" class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Çıkış
                        </a>
                    </div>
                </div>
            </div>

            <!-- Webhook URL Bilgisi - Collapsible -->
            <details class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-lg overflow-hidden mb-6 text-white">
                <summary class="cursor-pointer p-6 hover:bg-black/10 transition flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <h3 class="text-lg font-bold">Webhook Endpoint URL</h3>
                    </div>
                    <svg class="w-5 h-5 transform transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 border-t border-white/20">
                    <div class="mt-4 bg-white/10 backdrop-blur rounded-lg p-4">
                        <div class="font-mono text-sm break-all bg-black/20 rounded p-3">
                            <?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?endpoint'; ?>
                        </div>
                        <button onclick="copyEndpoint()" class="mt-3 bg-white/20 hover:bg-white/30 backdrop-blur text-white text-sm font-medium py-2 px-4 rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Kopyala
                        </button>
                    </div>
                    <p class="text-sm mt-3 opacity-90">
                        Bu URL'yi Buy Me a Coffee webhook ayarlarınıza ekleyin.
                    </p>
                </div>
            </details>

            <!-- İstatistikler -->
            <?php $stats = getStats(); ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Toplam Webhook</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Doğrulanmış</p>
                            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $stats['verified']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Bugünkü</p>
                            <p class="text-3xl font-bold text-purple-600 mt-1"><?php echo $stats['today']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhook Logları -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">Son Webhook Bildirimleri</h2>
                </div>
                
                <?php
                $db = readDatabase();
                $webhooks = array_slice($db['webhooks'], 0, 50); // İlk 50 kayıt
                
                if (empty($webhooks)): ?>
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Henüz webhook kaydı yok</h3>
                        <p class="text-gray-600">Webhook bildirimleri buraya görünecek</p>
                    </div>
                <?php else: ?>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($webhooks as $webhook): ?>
                        <div class="bg-gradient-to-br from-white to-gray-50 border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-all duration-200">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="text-lg font-bold text-gray-900 mb-1">
                                        <?php echo getEventLabel($webhook['event_type']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('d.m.Y H:i', strtotime($webhook['created_at'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($webhook['verified']): ?>
                                        <span class="badge-verified">✓</span>
                                    <?php else: ?>
                                        <span class="badge-failed">✗</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Destekçi Bilgileri -->
                            <div class="mb-4 pb-4 border-b border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($webhook['supporter_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 truncate">
                                            <?php echo htmlspecialchars($webhook['supporter_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">
                                            <?php echo htmlspecialchars($webhook['supporter_email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Miktar ve Mesaj -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Miktar:</span>
                                    <span class="text-sm font-bold text-yellow-600"><?php echo htmlspecialchars($webhook['amount']); ?></span>
                                </div>
                                <?php if (!empty($webhook['message'])): ?>
                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-2 mt-2">
                                    <div class="text-xs text-gray-600 mb-1">💬 Mesaj:</div>
                                    <div class="text-sm text-gray-900 line-clamp-2">
                                        <?php echo htmlspecialchars($webhook['message']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Aksiyon Butonları -->
                            <div class="flex gap-2">
                                <button onclick="showDetails('<?php echo $webhook['id']; ?>')" 
                                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-3 rounded-lg transition">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Detay
                                </button>
                                <button onclick="deleteWebhook('<?php echo $webhook['id']; ?>')" 
                                    class="bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2 px-3 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detay Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white">
                <h3 class="text-xl font-bold text-gray-900">Webhook Detayları</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="detailContent" class="p-6">
                <!-- İçerik buraya gelecek -->
            </div>
        </div>
    </div>

    <script>
        function copyEndpoint() {
            const endpoint = '<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?endpoint'; ?>';
            navigator.clipboard.writeText(endpoint).then(() => {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Kopyalandı!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }

        function showDetails(id) {
            fetch(`?ajax=get_detail&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    const content = `
                        <div class="space-y-3">
                            <!-- Event Tipi -->
                            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">📌</span>
                                    <div>
                                        <div class="text-xs text-gray-600 font-medium">Event Tipi</div>
                                        <div class="text-lg font-bold text-gray-900">${getEventLabelJS(data.event_type)}</div>
                                        <div class="text-xs text-gray-500 mt-1">${data.event_type}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Destekçi Bilgileri -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                        ${data.supporter_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">${data.supporter_name}</div>
                                        <div class="text-sm text-gray-600">${data.supporter_email}</div>
                                    </div>
                                </div>
                            </div>

                            ${data.message ? `
                            <!-- Mesaj -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start gap-2">
                                    <span class="text-xl">💬</span>
                                    <div class="flex-1">
                                        <div class="text-xs text-gray-600 font-medium mb-1">Mesaj</div>
                                        <div class="text-gray-900">${data.message}</div>
                                    </div>
                                </div>
                            </div>
                            ` : ''}

                            <!-- İmza Durumu -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">İmza Durumu</span>
                                    <span class="${data.verified ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'}">
                                        ${data.verified ? '✓ Doğrulandı' : '✗ Doğrulanamadı'}
                                    </span>
                                </div>
                            </div>

                            <!-- Collapsible: Raw Payload -->
                            <details class="bg-gray-900 rounded-lg overflow-hidden">
                                <summary class="cursor-pointer p-4 text-green-400 font-semibold hover:bg-gray-800 transition flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                        </svg>
                                        Raw Payload
                                    </span>
                                    <svg class="w-5 h-5 transform transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </summary>
                                <div class="p-4 border-t border-gray-700">
                                    <pre class="text-xs text-green-400 overflow-x-auto">${JSON.stringify(JSON.parse(data.payload), null, 2)}</pre>
                                </div>
                            </details>

                            <!-- Collapsible: İmza -->
                            <details class="bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                                <summary class="cursor-pointer p-4 text-gray-700 font-semibold hover:bg-gray-100 transition flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        HMAC-SHA256 İmza
                                    </span>
                                    <svg class="w-5 h-5 transform transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </summary>
                                <div class="p-4 border-t border-gray-200">
                                    ${data.signature && data.signature.trim() !== '' ? `
                                        <code class="text-xs font-mono text-gray-600 break-all block">${data.signature}</code>
                                    ` : `
                                        <div class="text-sm text-gray-500 italic">
                                            ⚠️ İmza bilgisi bulunamadı. Bu genellikle test webhook'larında normal bir durumdur.
                                        </div>
                                    `}
                                </div>
                            </details>
                        </div>
                    `;
                    document.getElementById('detailContent').innerHTML = content;
                    document.getElementById('detailModal').classList.remove('hidden');
                });
        }

        function getEventLabelJS(eventType) {
            const labels = {
                // Bağış/Destek
                'donation.created': '☕ Bağış Alındı',
                'donation.refunded': '↩️ Bağış İade Edildi',
                'support.created': '☕ Destek Alındı',
                'support.refunded': '↩️ Destek İade Edildi',
                
                // Ekstra Ürünler
                'extra_purchase.created': '🛒 Ekstra Ürün Satışı',
                'extra_purchase.updated': '🔄 Ürün Güncellendi',
                'extra_purchase.refunded': '↩️ Ürün İadesi',
                
                // Komisyonlar
                'commission_order.created': '💼 Komisyon Siparişi',
                'commission_order.refunded': '↩️ Komisyon İadesi',
                
                // Aylık Destek
                'recurring_donation.started': '🌟 Aylık Destek Başladı',
                'recurring_donation.updated': '🔄 Aylık Destek Güncellendi',
                'recurring_donation.cancelled': '❌ Aylık Destek İptal Edildi',
                
                // Üyelik
                'membership.started': '⭐ Üyelik Başladı',
                'membership.updated': '🔄 Üyelik Güncellendi',
                'membership.cancelled': '❌ Üyelik İptal Edildi',
                
                // Wishlist (İstek Listesi)
                'wishlist_payment.created': '🎁 İstek Listesi Ödemesi',
                'wishlist_payment.refunded': '↩️ İstek Listesi İadesi'
            };
            return labels[eventType] || '📌 ' + eventType.replace(/[._]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function deleteWebhook(id) {
            if (confirm('Bu webhook kaydını silmek istediğinizden emin misiniz?')) {
                fetch(`?ajax=delete&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        }

        function clearAllWebhooks() {
            if (confirm('TÜM webhook kayıtlarını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
                fetch('?ajax=clear_all')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        }

        // ESC tuşu ile kapat
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // Details arrow animation
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('details').forEach(details => {
                details.addEventListener('toggle', function() {
                    const arrow = this.querySelector('summary svg:last-child');
                    if (arrow) {
                        arrow.style.transform = this.open ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });
            });
        });
    </script>

<?php endif; ?>

</body>
</html>
