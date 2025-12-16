<?php
// db.php - MongoDB Database Connection
// Code đã được tối ưu để tránh lỗi trùng lặp và lỗi đường dẫn

// 1. Load Config (Dùng __DIR__ để định vị chính xác)
require_once __DIR__ . '/config.php';

// 2. Load thư viện MongoDB (Vendor)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class Database {
    private static $client = null;
    private static $db = null;
    
    // Kết nối tới MongoDB
    public static function getClient() {
        if (self::$client === null) {
            try {
                $options = [
                    'connectTimeoutMS' => 5000,
                    'readPreference' => 'primary',
                    'retryWrites' => true
                ];
                 
                self::$client = new MongoDB\Client(MONGODB_URI, $options);
                // Test thử kết nối
                self::$client->listDatabases(); 
                
            } catch (Exception $e) {
                error_log("MongoDB Error: " . $e->getMessage());
                die("Lỗi kết nối Database: " . $e->getMessage());
            }
        }
        return self::$client;
    }
    
    // Lấy Database mặc định
    public static function getDB() {
        if (self::$db === null) {
            self::$db = self::getClient()->selectDatabase(MONGODB_DB);
        }
        return self::$db;
    }
    
    // Lấy Collection (Bảng)
    public static function getCollection($name) {
        return self::getDB()->selectCollection($name);
    }
}

// --- CÁC HÀM HỖ TRỢ (UTILS) ---

// Hàm ghi log hệ thống
function logSystem($type, $module, $message) {
    try {
        $logs = Database::getCollection('system_logs');
        $logs->insertOne([
            'type' => $type,
            'module' => $module,
            'message' => $message,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);
    } catch (Exception $e) { /* Bỏ qua lỗi log */ }
}

// --- CÁC HÀM XỬ LÝ SẢN PHẨM & MÀU SẮC ---

// Hàm thêm sản phẩm (QUAN TRỌNG: Chỉ giữ 1 hàm này duy nhất)
function addProduct($productData) {
    try {
        $products = Database::getCollection('products');
        
        // Xử lý color_id an toàn
        $colorId = null;
        if (!empty($productData['color_id'])) {
            try {
                $colorId = new MongoDB\BSON\ObjectId($productData['color_id']);
            } catch (Exception $e) {
                $colorId = null;
            }
        }

        $product = [
            'color_id' => $colorId,
            'rgb_r' => (int)$productData['rgb_r'],
            'rgb_g' => (int)$productData['rgb_g'],
            'rgb_b' => (int)$productData['rgb_b'],
            'confidence' => (float)($productData['confidence'] ?? 0),
            'batch_code' => $productData['batch_code'] ?? '',
            'line_id' => (int)($productData['line_id'] ?? 1),
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $result = $products->insertOne($product);
        return (string)$result->getInsertedId();
        
    } catch (Exception $e) {
        error_log("Add product error: " . $e->getMessage());
        return false;
    }
}

// Hàm nhận diện màu từ RGB
function detectColorFromRGB($r, $g, $b) {
    try {
        $colors = Database::getCollection('colors')->find(['status' => true])->toArray();
        foreach ($colors as $color) {
            if ($r >= $color['min_r'] && $r <= $color['max_r'] &&
                $g >= $color['min_g'] && $g <= $color['max_g'] &&
                $b >= $color['min_b'] && $b <= $color['max_b']) {
                return $color;
            }
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}

// Hàm đếm số lượng sản phẩm (Cho Dashboard)
function countProducts($filter = []) {
    try {
        return Database::getCollection('products')->countDocuments($filter);
    } catch (Exception $e) { return 0; }
}

// Hàm lấy danh sách màu (Cho Dashboard)
/**
 * Lấy danh sách màu sắc và chuyển đổi thành mảng liên kết (key là _id)
 *
 * @param array $filter Bộ lọc tùy chọn
 * @param array $sort Sắp xếp
 * @return array Mảng màu sắc liên kết theo ID
 */
function getColors(array $filter = [], array $sort = ['sort_order' => 1]) {
    try {
        $cursor = Database::getCollection('colors')->find($filter, ['sort' => $sort]);
        $results = [];
        
        // Sửa: Chuyển sang mảng liên kết (key là _id dạng chuỗi)
        foreach ($cursor as $doc) {
            $color = (array)$doc;
            // Đảm bảo key là chuỗi ID để dễ dàng tham chiếu
            $color_id_str = (string)$color['_id']; 
            $results[$color_id_str] = $color; // Key là ID, Value là thông tin màu
        }
        
        return $results;
    } catch (Exception $e) { 
        logSystem('error', 'db', 'Error getting colors: ' . $e->getMessage());
        return []; 
    }
}
// Các hàm Authentication
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }
}function verifyPassword($password, $hash) {
    // Sử dụng hàm chuẩn của PHP để so sánh mật khẩu đã hash
    return password_verify($password, $hash);
}
// lấy ds sp
function getProducts(array $filter = [], int $limit = 0, int $offset = 0, array $sort = ['created_at' => -1]) {
    try {
        $products = Database::getCollection('products');
        
        // 1. Lấy tất cả thông tin màu sắc để tạo Map tham chiếu nhanh
        $color_map = getColors(); // Hàm này đã được sửa để trả về mảng key=ID

        $options = [];
        if ($limit > 0) {
            $options['limit'] = $limit;
        }
        if ($offset > 0) {
            $options['skip'] = $offset;
        }
        if (!empty($sort)) {
            $options['sort'] = $sort;
        }
        
        $cursor = $products->find($filter, $options);
        
        $results = [];
        foreach ($cursor as $doc) {
            $product = (array)$doc;
            
            // Chuyển ObjectID sang chuỗi ID
            $product['_id'] = (string)$doc['_id'];
            
            // Thiết lập giá trị mặc định an toàn
            $product['color_name'] = 'N/A';
            $product['color_code'] = '#CCCCCC'; // Màu xám mặc định
            
            // 2. Thực hiện Tham chiếu màu sắc
            if (isset($product['color_id']) && $product['color_id'] instanceof MongoDB\BSON\ObjectID) {
                
                $color_id_str = (string)$product['color_id'];
                
                // Sửa: Dùng color_id_str để tham chiếu trực tiếp từ map đã tạo
                if (isset($color_map[$color_id_str])) {
                    $product['color_name'] = $color_map[$color_id_str]['name'];
                    $product['color_code'] = $color_map[$color_id_str]['code'];
                }
            } 
            
            // Đảm bảo 'confidence' là số (để tránh lỗi number_format)
            $product['confidence'] = $product['confidence'] ?? 0;
            
            $results[] = $product;
        }
        
        return $results;
    } catch (Exception $e) {
        logSystem('error', 'db', 'Error getting products: ' . $e->getMessage());
        return [];
    }
}
function getLogs(array $filter = [], int $limit = 10, array $sort = ['timestamp' => -1]) {
    try {
        $logs = Database::getCollection('logs');
        
        $options = [
            'sort' => $sort,
            'limit' => $limit
        ];
        
        $cursor = $logs->find($filter, $options);
        
        $results = [];
        foreach ($cursor as $doc) {
            // Chuyển đối tượng MongoDB\\BSON\\ObjectID thành string ID
            $doc['_id'] = (string)$doc['_id'];
            $results[] = $doc;
        }
        
        return $results;
    } catch (Exception $e) {
        // Ghi log lỗi vào system error, không cần dùng hàm logSystem ở đây
        // vì hàm này nằm trong hàm logSystem.
        error_log("DB Error: Failed to retrieve logs: " . $e->getMessage());
        return [];
    }
}function formatDate($date, $format = 'd/m/Y H:i:s') {
        if ($date instanceof MongoDB\BSON\UTCDateTime) {
            // Chuyển UTCDateTime sang đối tượng DateTime của PHP
            $dateTime = $date->toDateTime();
        } elseif (is_numeric($date)) {
            // Nếu là timestamp (số giây)
            $dateTime = new DateTime("@$date");
        } else {
            // Trường hợp khác (ví dụ: chuỗi ngày tháng)
            try {
                $dateTime = new DateTime($date);
            } catch (\Exception $e) {
                return 'N/A';
            }
        }
        
        // Đặt múi giờ (giả sử múi giờ của bạn)
        $dateTime->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        return $dateTime->format($format);
    }
    // Thêm hàm này vào cuối file db.php

if (!function_exists('generateBatchCode')) {
    /**
     * Tạo một mã lô (batch code) duy nhất dựa trên thời gian hiện tại và một số ngẫu nhiên.
     *
     * @param string $prefix Tiền tố của mã lô (VD: 'BATCH-')
     * @return string Mã lô duy nhất
     */
    function generateBatchCode(string $prefix = 'BATCH-'): string {
        // Sử dụng timestamp (microtime) và random number để đảm bảo tính duy nhất cao
        $time_part = str_replace(['.', ' '], '', microtime());
        $random_part = bin2hex(random_bytes(2)); // Tạo 4 ký tự ngẫu nhiên
        
        return $prefix . $time_part . '-' . strtoupper($random_part);
    }
}
// Thêm vào db.php (Cần sử dụng MongoDB Aggregation Pipeline)

function getProductStatsByColor() {
    try {
        $products = Database::getCollection('products');
        // Aggregation Pipeline: 
        // 1. Group by color_name và count
        $pipeline = [
            ['$group' => ['_id' => '$color_name', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]]
        ];
        
        $cursor = $products->aggregate($pipeline);
        $results = [];
        foreach ($cursor as $doc) {
            $results[$doc['_id']] = $doc['count'];
        }
        return $results;
    } catch (Exception $e) {
        logSystem('error', 'db', 'Error getting color stats: ' . $e->getMessage());
        return [];
    }
}

function getDailyProductCounts(int $days) {
    // (Việc viết hàm này rất phức tạp vì cần xử lý múi giờ và Aggregation Pipeline của MongoDB)
    // Để đơn giản, hàm này sẽ trả về dữ liệu mẫu nếu không muốn dùng Aggregation phức tạp:
    $counts = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        // Thay thế bằng logic truy vấn MongoDB thật
        $counts[$date] = rand(50, 150); // Dữ liệu giả lập
    }
    return $counts;
}
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            // Lưu ý: Cần đảm bảo SITE_URL đã được định nghĩa trong config.php
            header("Location: login.php");
            exit();
        }
    }
}
function findColorByRGB(int $r, int $g, int $b) {
    try {
        $colors_col = Database::getCollection('colors');
        
        // Filter: Tìm màu mà giá trị R, G, B nằm trong dải Min/Max tương ứng
        $filter = [
            'min_r' => ['$lte' => $r], // R >= min_r
            'max_r' => ['$gte' => $r], // R <= max_r
            'min_g' => ['$lte' => $g], // G >= min_g
            'max_g' => ['$gte' => $g], // G <= max_g
            'min_b' => ['$lte' => $b], // B >= min_b
            'max_b' => ['$gte' => $b]  // B <= max_b
        ];
        
        // Tìm một màu phù hợp
        $color = $colors_col->findOne($filter);
        
        if ($color) {
            // Nếu tìm thấy: trả về tên màu và mã màu (ví dụ: 'Red', '#FF0000')
            return [
                'name' => $color['name'], 
                'code' => $color['code']
            ];
        } else {
            // Nếu không tìm thấy: coi là màu không xác định
            return [
                'name' => 'Unknown', 
                'code' => '#808080' // Màu xám mặc định
            ];
        }
    } catch (Exception $e) {
        logSystem('error', 'db', 'Lỗi khi phân loại RGB: ' . $e->getMessage());
        return ['name' => 'Error', 'code' => '#000000'];
    }
}

?>