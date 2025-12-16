<?php
// create_admin.php - File chạy 1 lần để tạo tài khoản admin
require_once 'db.php';

echo "<h2>🛠️ Đang tạo tài khoản Admin...</h2>";

try {
    $users = Database::getCollection('users');
    
    // Kiểm tra xem admin đã tồn tại chưa
    $existing = $users->findOne(['username' => 'admin']);
    
    if ($existing) {
        echo "<p style='color:orange'>⚠️ Tài khoản 'admin' đã tồn tại. Không cần tạo lại.</p>";
    } else {
        // Tạo tài khoản mới
        $newUser = [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT), // Mật khẩu là admin123
            'fullname' => 'Quản Trị Viên',
            'email'    => 'admin@iot.com',
            'role'     => 'admin',
            'status'   => true,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $users->insertOne($newUser);
        echo "<p style='color:green'>✅ <strong>Thành công!</strong> Đã tạo tài khoản.</p>";
        echo "<ul>";
        echo "<li>Username: <strong>admin</strong></li>";
        echo "<li>Password: <strong>admin123</strong></li>";
        echo "</ul>";
    }
    
    echo "<p><a href='login.php'>👉 Bấm vào đây để Đăng nhập ngay</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>