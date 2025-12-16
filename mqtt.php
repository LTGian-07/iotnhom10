<?php
// mqtt.php - MQTT Client Integration (Đã sửa lỗi Class not found)
require_once __DIR__ . '/config.php';

class MQTTClient {
    private static $instance = null;
    public $client = null;
    private $connected = false;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initializeClient();
    }
    
    private function initializeClient() {
        try {
            if (!defined('MQTT_BROKER') || empty(MQTT_BROKER)) {
                throw new Exception("MQTT broker not configured");
            }
            
            // 1. Load file thư viện (Sử dụng __DIR__ để luôn tìm thấy file)
            $libPath = __DIR__ . '/phpMQTT/phpMQTT.php';
            if (file_exists($libPath)) {
                require_once $libPath;
            } else {
                throw new Exception("Library phpMQTT not found at $libPath");
            }
            
            $broker_url = parse_url(MQTT_BROKER);
            $host = $broker_url['host'] ?? 'localhost';
            $port = $broker_url['port'] ?? 1883;
            $clientId = MQTT_CLIENT_ID . '_' . uniqid();

            // 2. KHỞI TẠO CLIENT (QUAN TRỌNG: Kiểm tra cả 2 trường hợp tên lớp)
            if (class_exists('\\Bluerhinos\\phpMQTT')) {
                // Trường hợp bạn tải từ link Github mới (Có namespace)
                $this->client = new \Bluerhinos\phpMQTT($host, $port, $clientId);
            } elseif (class_exists('phpMQTT')) {
                // Trường hợp thư viện cũ
                $this->client = new phpMQTT($host, $port, $clientId);
            } else {
                throw new Exception("Class phpMQTT not found. Please check phpMQTT.php content.");
            }
            
        } catch (Exception $e) {
            error_log("MQTT Init Error: " . $e->getMessage());
            $this->client = null;
        }
    }
    
    public function connect() {
        if (!$this->client) return false;
        if ($this->connected) return true;
        
        try {
            if (!empty(MQTT_USERNAME) && !empty(MQTT_PASSWORD)) {
                $this->connected = $this->client->connect(true, null, MQTT_USERNAME, MQTT_PASSWORD);
            } else {
                $this->connected = $this->client->connect();
            }
            return $this->connected;
        } catch (Exception $e) {
            error_log("MQTT Connection Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function publish($topic, $message, $qos = 0, $retain = false) {
        if (!$this->connect()) return false;
        
        try {
            $payload = is_array($message) ? json_encode($message) : $message;
            $this->client->publish($topic, $payload, $qos, $retain);
            return true;
        } catch (Exception $e) {
            error_log("MQTT Publish Error: " . $e->getMessage());
            return false;
        }
    }
    
    // Sửa lại hàm subscribe để tương thích
    public function subscribe($topic, $callback, $qos = 0) {
        if (!$this->connect()) return false;
        
        try {
            $topics = [$topic => ['qos' => $qos, 'function' => $callback]];
            $this->client->subscribe($topics, 0); 
            return true;
        } catch (Exception $e) {
            error_log("MQTT Subscribe Error: " . $e->getMessage());
            return false;
        }
    }

    public function loop() {
        if ($this->connect()) {
            while ($this->client->proc()) {
                // Vòng lặp lắng nghe tin nhắn
            }
            $this->client->close();
        }
    }
    
    public function notifyProductDetection($productData) {
        $topic = defined('MQTT_TOPICS') ? MQTT_TOPICS['product_detected'] : 'iot/color/products/detected';
        return $this->publish($topic, [
            'type' => 'product_detected',
            'data' => $productData,
            'timestamp' => time()
        ], 1);
    }
}

function getMQTTClient() {
    return MQTTClient::getInstance();
}
?>
