<?php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// 0. Include composer autoload cho MongoDB
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
define('MQTT_TOPIC_COMMAND', 'iot/color_sorter/command'); 
define('MQTT_TOPIC_PRODUCT', 'iot/color_sorter/product');
// 1. Cấu hình MongoDB Atlas (Giữ nguyên của bạn)
define('MONGODB_URI', 'mongodb+srv://legiangquangtan_db_user:admin123@cluster0.x2aiwzz.mongodb.net/iot_system?retryWrites=true&w=majority&appName=Cluster0');
define('MONGODB_DB', 'iot_system');

// 2. Cấu hình hệ thống
define('SITE_NAME', 'Hệ Thống IoT Phân Loại Màu Sắc');
define('SITE_TIMEZONE', 'Asia/Ho_Chi_Minh');
define('ENCRYPTION_KEY', 'iot2024@color@detection@system');
define('SESSION_TIMEOUT', 3600);
define('DEBUG', true);

// 3. Xử lý SITE_URL an toàn
$site_url = getenv('RENDER_EXTERNAL_URL');
if (!$site_url) {
    // Fallback cho môi trường cục bộ (localhost)
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $server_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $site_url = $protocol . '://' . $server_host;
}
// Đảm bảo có dấu '/' ở cuối
define('SITE_URL', rtrim($site_url, '/') . '/');
// 4. Cấu hình thời gian
date_default_timezone_set(SITE_TIMEZONE);

// 5. Cài đặt Error Reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 6. Khởi tạo Session an toàn
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

// --- 7. CẤU HÌNH MQTT (ĐÃ SỬA: Dùng Server Online) ---
// Thay localhost bằng server công cộng miễn phí của EMQX
define('MQTT_BROKER', 'tcp://broker.emqx.io:1883');
define('MQTT_USERNAME', ''); // Server công cộng không cần mật khẩu
define('MQTT_PASSWORD', ''); // Để trống
define('MQTT_CLIENT_ID', 'iot_color_sys_' . uniqid()); // Tạo ID ngẫu nhiên để không bị đá văng

// MQTT Topics
// Lưu ý: Vì là server công cộng, mình thêm tiền tố 'tan_iot_' để không bị trùng với người khác
$mqtt_topics = [
    'product_detected' => 'tan_iot/color/products/detected',
    'alerts'           => 'tan_iot/color/system/alerts',
    'stats_update'     => 'tan_iot/color/stats/update',
    'color_update'     => 'tan_iot/color/colors/update',
    'device_command'   => 'iot/color/device/command' // Topic này giữ nguyên để trùng với code ESP32 (nếu có)
];
?>