<?php


require_once __DIR__ . 'config.php';
require_once __DIR__ . 'db.php';
require_once __DIR__ . 'mqtt/mqtt.php';

if (php_sapi_name() !== 'cli') {
    die("This script should be run from command line\n");
}

echo "--- Starting MQTT Subscription Service ---\n";

$mqtt = getMQTTClient();

if (!$mqtt->connect()) {
    die("❌ Failed to connect to MQTT broker. Check config or broker status.\n");
}

echo "✅ Connected to MQTT broker\n";

// 1. Đăng ký Topic điều khiển
$mqtt->subscribe('iot/color/device/command', function($topic, $message) {
    echo "\n[COMMAND] Received: $message\n";
    $command = json_decode($message, true);
    
    switch ($command['action'] ?? '') {
        case 'get_status':
            $status = [
                'status' => 'online',
                'timestamp' => time(),
                // 'products_count' => countProducts(), // Đảm bảo hàm này có trong db.php
            ];
            getMQTTClient()->publish('iot/color/system/status', $status);
            echo "-> Sent status update\n";
            break;
    }
});

// 2. Đăng ký Topic dữ liệu cảm biến
$mqtt->subscribe('iot/color/products/raw', function($topic, $message) {
    echo "\n[DATA] Raw data received: $message\n";
    
    $data = json_decode($message, true);
    
    if (isset($data['rgb']) && is_array($data['rgb'])) {
        try {
            $rgb_r = (int)$data['rgb'][0];
            $rgb_g = (int)$data['rgb'][1];
            $rgb_b = (int)$data['rgb'][2];
            
            // Tự động nhận diện màu (Logic lấy từ add_product.php)
            // Đảm bảo hàm detectColorFromRGB và addProduct có trong db.php
            $detected_color = detectColorFromRGB($rgb_r, $rgb_g, $rgb_b);
            $color_id = $detected_color ? (string)$detected_color['_id'] : null;
            
            $product_data = [
                'color_id' => $color_id,
                'rgb_r' => $rgb_r,
                'rgb_g' => $rgb_g,
                'rgb_b' => $rgb_b,
                'confidence' => 95.0,
                'batch_code' => isset($data['batch_code']) ? $data['batch_code'] : date('YmdHis'),
                'line_id' => 1,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            // Gọi trực tiếp hàm thêm vào DB thay vì gọi API file
            $product_id = addProduct($product_data);
            
            if ($product_id) {
                echo "-> ✅ Product added: ID $product_id (Color: " . ($detected_color['name'] ?? 'Unknown') . ")\n";
                
                // Gửi thông báo lại lên MQTT cho Web Dashboard
                $mqtt_data = array_merge($product_data, [
                    'product_id' => $product_id,
                    'color_name' => $detected_color['name'] ?? 'Unknown'
                ]);
                getMQTTClient()->notifyProductDetection($mqtt_data);
            }
            
        } catch (Exception $e) {
            echo "-> ❌ Error processing data: " . $e->getMessage() . "\n";
        }
    }
});

echo "🎧 Listening for messages...\n";

// Bắt đầu vòng lặp lắng nghe (Blocking loop)
$mqtt->loop();
?>