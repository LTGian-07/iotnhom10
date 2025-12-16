<?php
// includes/header.php - Common header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css">
    
    <!-- Page-specific CSS -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/favicon.ico">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .nav-link.active {
            font-weight: 600;
            color: var(--primary-color) !important;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .page-header h1 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 15px;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 1px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .main-content {
                padding: 15px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</head>

<body>
    <?php if ($current_user): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">
                            <i class="bi bi-cpu"></i> IoT System
                        </h4>
                        <small class="text-white-50">Color Detection</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                               href="http://localhost/iot_system/index.php">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'xemSanPham.php' ? 'active' : ''; ?>" 
                               href="http://localhost/iot_system/xemSanPham.php">
                                <i class="bi bi-box-seam me-2"></i>
                                Sản phẩm
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'thongKeSanPham.php' ? 'active' : ''; ?>" 
                               href="http://localhost/iot_system/thongKeSanPham.php">
                                <i class="bi bi-bar-chart me-2"></i>
                                Thống kê
                            </a>
                        </li>
                         
                        <li class="nav-item mt-3">
                            <small class="text-white-50 ms-3">TÀI KHOẢN</small>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'quanLyTaiKhoan.php' ? 'active' : ''; ?>" 
                               href="http://localhost/iot_system/quanLyTaiKhoan.php">
                                <i class="bi bi-person me-2"></i>
                                Tài khoản
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link text-danger" 
                               href="http://localhost/iot_system/logout.php"
                               onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Đăng xuất
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-5 pt-3 border-top border-white-10">
                        <div class="text-center">
                            <small class="text-white-50">
                                <i class="bi bi-shield-check me-1"></i>
                               Nhóm 10
                            
                            </small>
                            <br>
                            <small class="text-white-50">
                                &copy; <?php echo date('Y'); ?> IoT System
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 ms-sm-auto px-0">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                    <div class="container-fluid">
                        <button class="btn btn-outline-primary me-2 d-md-none" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="bi bi-list"></i>
                        </button>
                        
                        <div class="navbar-brand">
                            <?php echo isset($page_title) ? $page_title : SITE_NAME; ?>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <!-- Notifications -->
                            <div class="dropdown me-3">
                                <button class="btn btn-outline-secondary position-relative" 
                                        type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        3
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><h6 class="dropdown-header">Thông báo</h6></li>
                                    <li><a class="dropdown-item" href="#">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Có 15 sản phẩm mới
                                    </a></li>
                                    <li><a class="dropdown-item" href="#">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        Line 2 cần bảo trì
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-center" href="#">
                                        Xem tất cả
                                    </a></li>
                                </ul>
                            </div>
                            
                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <?php echo htmlspecialchars($current_user['fullname']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <h6 class="dropdown-header">
                                            <small>Đăng nhập với quyền</small><br>
                                            <strong class="text-uppercase">
                                                <?php echo $current_user['role']; ?>
                                            </strong>
                                        </h6>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>thongTinTaiKhoan.php">
                                            <i class="bi bi-person me-2"></i>
                                            Tài khoản
                                        </a>
                                    </li>
                                    <?php if ($current_user['role'] === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>system_settings.php">
                                            <i class="bi bi-gear me-2"></i>
                                            Cài đặt hệ thống
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" 
                                           href="<?php echo SITE_URL; ?>logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>
                                            Đăng xuất
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <!-- Main Content Area -->
                <main class="main-content">
    <?php else: ?>
    <!-- No sidebar for non-authenticated pages -->
    <?php endif; ?>