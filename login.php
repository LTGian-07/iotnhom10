<?php
// login.php - MongoDB Authentication (Fixed)

// 1. Load Cấu hình & Database
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// 2. Định nghĩa các hàm hỗ trợ (nếu chưa có trong db.php)
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('generateToken')) {
    function generateToken() {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

// Hàm kiểm tra Brute Force (Đăng nhập sai nhiều lần)
if (!function_exists('checkBruteForce')) {
    function checkBruteForce($username, $ip) {
        try {
            $users = Database::getCollection('users');
            $user = $users->findOne(['username' => $username]);
            
            // Nếu sai quá 5 lần trong 15 phút
            if ($user && isset($user['failed_attempts']) && $user['failed_attempts'] >= 5) {
                if (isset($user['last_failed_attempt'])) {
                    // Chuyển BSON Date sang timestamp
                    $last_fail = $user['last_failed_attempt']->toDateTime()->getTimestamp();
                    // Nếu chưa qua 15 phút (900 giây)
                    if (time() - $last_fail < 900) {
                        return true; // Bị khóa
                    }
                }
            }
            return false;
        } catch (Exception $e) { return false; }
    }
}

// Hàm ghi lại lịch sử đăng nhập
if (!function_exists('recordLoginAttempt')) {
    function recordLoginAttempt($username, $success, $user_id = null) {
        // Ghi vào log hệ thống
        $msg = $success ? "Login success" : "Login failed";
        logSystem('login', 'auth', 'Người dùng đăng nhập thành công.');
    }
}

// 3. Xử lý logic đăng nhập


// Nếu đã đăng nhập, chuyển hướng về index.php
if(isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Check for messages from other pages
if (isset($_GET['registered'])) {
    $success_message = "Tài khoản đã được tạo thành công! Vui lòng đăng nhập.";
}
if (isset($_GET['timeout'])) {
    $error_message = "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.";
}
if (isset($_GET['logout'])) {
    $success_message = "Đã đăng xuất thành công.";
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Hàm sanitize có thể chưa có, dùng tạm htmlspecialchars
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng nhập tên đăng nhập và mật khẩu.";
    } else {
        // Check for brute force attacks
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        if (checkBruteForce($username, $ip_address)) {
            $error_message = "Tài khoản tạm thời bị khóa do quá nhiều lần đăng nhập sai. Vui lòng thử lại sau 15 phút.";
            recordLoginAttempt($username, false);
        } else {
            try {
                // Get user from database
                $users = Database::getCollection('users');
                $user = $users->findOne([
                    'username' => $username,
                    'status' => true
                ]);
                
                if ($user && verifyPassword($password, $user['password'])) {
                    // SỬA LỖI: $$users -> $users
                    // SỬA LỖI: UTCDateTime cần namespace MongoDB\BSON\
                    $users->updateOne(
                        ['_id' => $user['_id']],
                        [
                            '$set' => [
                                'last_login' => new MongoDB\BSON\UTCDateTime(time() * 1000),
                                'last_ip' => $ip_address,
                                'failed_attempts' => 0,
                                'last_failed_attempt' => null
                            ]
                        ]
                    );
                    
                    // Create session
                    $_SESSION['user'] = [
                        'id' => (string)$user['_id'],
                        'username' => $user['username'],
                        'fullname' => $user['fullname'] ?? $user['username'],
                        'email' => $user['email'] ?? '',
                        'role' => $user['role'] ?? 'user',
                        'avatar' => $user['avatar'] ?? null
                    ];
                    
                    $_SESSION['LAST_ACTIVITY'] = time();
                    $_SESSION['CREATED'] = time();
                    
                    // Set remember me cookie if requested
                    if ($remember) {
                        $token = generateToken();
                        $expiry = time() + (30 * 24 * 3600); // 30 days
                        
                        setcookie('remember_token', $token, $expiry, '/', '', true, true);
                        
                        // Store token in database
                        $users->updateOne(
                            ['_id' => $user['_id']],
                            [
                                '$set' => [
                                    'remember_token' => $token,
                                    'remember_expiry' => new MongoDB\BSON\UTCDateTime($expiry * 1000)
                                ]
                            ]
                        );
                    }
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Log successful login
                    recordLoginAttempt($username, true, (string)$user['_id']);
                    
                    // Redirect to intended page or dashboard
                    $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
                    unset($_SESSION['redirect_url']);
                    
                    header("Location: " . $redirect_url);
                    exit();
                    
                } else {
                    $error_message = "Tên đăng nhập hoặc mật khẩu không đúng.";
                    
                    // Increment failed attempts
                    if ($user) {
                        $users->updateOne(
                            ['_id' => $user['_id']],
                            [
                                '$inc' => ['failed_attempts' => 1],
                                '$set' => [
                                    'last_failed_attempt' => new MongoDB\BSON\UTCDateTime(time() * 1000),
                                    'last_failed_ip' => $ip_address
                                ]
                            ]
                        );
                    }
                    
                    recordLoginAttempt($username, false);
                }
                
            } catch (Exception $e) {
                $error_message = "Lỗi hệ thống: " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}

// Handle forgot password (Logic giữ nguyên, chỉ sửa namespace Date)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = htmlspecialchars(trim($_POST['email']));
    
    if (validateEmail($email)) {
        try {
            $users = Database::getCollection('users');
            $user = $users->findOne(['email' => $email, 'status' => true]);
            
            if ($user) {
                $reset_token = generateToken();
                $reset_expiry = time() + (3600 * 2); // 2 hours
                
                $users->updateOne(
                    ['_id' => $user['_id']],
                    [
                        '$set' => [
                            'reset_token' => $reset_token,
                            'reset_expiry' => new MongoDB\BSON\UTCDateTime($reset_expiry * 1000)
                        ]
                    ]
                );
                
                // Demo: In ra màn hình vì chưa cấu hình gửi mail
                $reset_link = SITE_URL . "reset_password.php?token=" . $reset_token;
                $success_message = "Đã tạo link reset (Demo): <a href='$reset_link'>Click vào đây</a>";
                
            } else {
                $error_message = "Không tìm thấy tài khoản với email này.";
            }
            
        } catch (Exception $e) {
            $error_message = "Lỗi hệ thống.";
        }
    } else {
        $error_message = "Email không hợp lệ.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?php echo defined('SITE_NAME') ? SITE_NAME : 'IoT System'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .video-background {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; object-fit: cover; opacity: 0.3;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%; max-width: 450px;
        }
        .login-logo-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; color: white; font-size: 2rem;
        }
        .divider {
            display: flex; align-items: center; text-align: center;
            margin: 20px 0; color: #6c757d;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; border-bottom: 1px solid #dee2e6;
        }
        .divider span { padding: 0 10px; }
    </style>
</head>
<body class="login-page">
    <video autoplay muted loop class="video-background">
        <source src="assets/video.mp4" type="video/mp4">
    </video>
    
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="login-logo-icon">
                <i class="bi bi-cpu"></i>
            </div>
            <h2 class="fw-bold text-primary"><?php echo defined('SITE_NAME') ? SITE_NAME : 'IoT System'; ?></h2>
            <p class="text-muted mb-0">Hệ thống phân loại màu sắc thông minh</p>
        </div>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Nhập tên đăng nhập" required autofocus
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Nhập mật khẩu" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>
            
            <button type="submit" name="login" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Đăng nhập
            </button>
            
            <div class="divider">
                <span>hoặc</span>
            </div>
            
            <div class="text-center">
                <p class="mb-2">Chưa có tài khoản?</p>
                <a href="register.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-person-plus me-2"></i> Đăng ký tài khoản mới
                </a>
            </div>
        </form>
        
        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'IoT System'; ?>
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
</body>
</html>