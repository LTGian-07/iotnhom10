<?php
// api/control_system.php
// File này xử lý các lệnh điều khiển hệ thống (Start, Stop, Manual, Sleep)

require_once 'config.php';
require_once 'db.php';

// Cần phải là người dùng đã đăng nhập
requireLogin();

// Chỉ cho phép Admin/Manager điều khiển
$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin' && $current_user['role'] !== 'manager') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
    exit();
}

$command = $_GET['command'] ?? null;
$mqtt_message = null;
$log_message = null;
$success = false;
$response_message = 'Lệnh điều khiển không hợp lệ.';
$log_type = 'warning';

switch ($command) {
    case 'start':
        $mqtt_message = '{"cmd":"start"}';
        $log_message = 'Lệnh BẮT ĐẦU vận hành hệ thống đã được gửi.';
        $response_message = 'Hệ thống đang được khởi động...';
        $log_type = 'info';
        $success = true;
        break;

    case 'stop':
        // --- ĐÃ THÊM: XỬ LÝ LỆNH STOP ---
        $mqtt_message = '{"cmd":"stop"}';
        $log_message = 'Lệnh DỪNG vận hành hệ thống đã được gửi.';
        $response_message = 'Hệ thống đang được dừng khẩn cấp.';
        $log_type = 'info';
        $success = true;
        break;

    case 'manual':
        // Lệnh này có thể yêu cầu thêm tham số, giữ nguyên ví dụ đơn giản
        $mqtt_message = '{"cmd":"manual"}';
        $log_message = 'Lệnh CHUYỂN sang chế độ điều khiển thủ công đã được gửi.';
        $response_message = 'Chuyển sang chế độ Thủ công.';
        $log_type = 'info';
        $success = true;
        break;

    case 'sleep':
        $mqtt_message = '{"cmd":"sleep"}';
        $log_message = 'Lệnh CHUYỂN sang chế độ ngủ (Sleep) đã được gửi.';
        $response_message = 'Chuyển sang chế độ Ngủ.';
        $log_type = 'info';
        $success = true;
        break;
}

if ($success && $mqtt_message !== null) {
    try {
        // Gửi lệnh qua MQTT (Giả lập vì PHP không có thư viện MQTT client)
        // Trong môi trường thực tế, bạn sẽ dùng thư viện như php-mqtt/client
        
        // *******************************************************************
        // * Lưu ý: Vì đây là môi trường PHP chạy Web, ta chỉ ghi log.        *
        // * Để gửi MQTT thật, cần dùng một Worker (daemon) hoặc thư viện    *
        // * Async PHP để kết nối với broker.                                *
        // *******************************************************************
        
        // Giả lập việc gửi thành công và ghi vào log
        logSystem('mqtt', 'control', $log_message . ' Payload: ' . $mqtt_message);
        
        // Thêm thông báo thành công vào session
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => $response_message
        ];

    } catch (Exception $e) {
        $success = false;
        $response_message = 'Lỗi trong quá trình gửi lệnh: ' . $e->getMessage();
        logSystem('error', 'control', $response_message);
        
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => $response_message
        ];
    }
} else if (!$success) {
    // Nếu command không hợp lệ
    logSystem('warning', 'control', 'Người dùng ' . $current_user['username'] . ' đã gửi lệnh điều khiển không hợp lệ: ' . $command);
    $_SESSION['message'] = [
        'type' => 'warning',
        'text' => $response_message
    ];
}


// Chuyển hướng người dùng về trang chủ hoặc trang dashboard
header('Location: index.php'); 
exit();

?>