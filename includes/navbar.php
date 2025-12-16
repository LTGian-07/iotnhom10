<?php
// includes/navbar.php - Thanh điều hướng chính
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_user = $_SESSION['user'] ?? null;
if (!$current_user) return;

$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = $current_user['role'] === 'admin';

// Lấy thống kê hệ thống
$system_stats = [
    'today_products' => 0,
    'reliability' => '0%',
    'unread_notifications' => 0,
    'last_update' => date('H:i:s'),
    'db_status' => 'danger',
    'db_message' => 'Mất kết nối',
    'system_status' => 'success',
    'system_text' => 'Hệ thống hoạt động bình thường'
];

// Kiểm tra kết nối database và lấy thống kê
try {
    require_once '../db.php';
    
    // Kiểm tra kết nối MongoDB
    $db_client = Database::getClient();
    $db_status = $db_client->listDatabases();
    $system_stats['db_status'] = 'success';
    $system_stats['db_message'] = 'MongoDB Connected';
    
    // Lấy thống kê sản phẩm hôm nay
    $today_start = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
    $products_collection = Database::getCollection('products');
    
    if ($products_collection) {
        $today_products = $products_collection->countDocuments([
            'created_at' => ['$gte' => $today_start]
        ]);
        $system_stats['today_products'] = $today_products;
        
        // Tính độ tin cậy trung bình
        $reliability_agg = $products_collection->aggregate([
            ['$match' => ['created_at' => ['$gte' => $today_start]]],
            ['$group' => [
                '_id' => null,
                'avg_confidence' => ['$avg' => '$confidence']
            ]]
        ])->toArray();
        
        if (!empty($reliability_agg) && isset($reliability_agg[0]['avg_confidence'])) {
            $system_stats['reliability'] = round($reliability_agg[0]['avg_confidence'], 1) . '%';
        }
    }
    
    // Đếm thông báo chưa đọc
    $notifications_collection = Database::getCollection('notifications');
    if ($notifications_collection) {
        $unread_count = $notifications_collection->countDocuments([
            'user_id' => new MongoDB\BSON\ObjectId($current_user['_id']),
            'read' => false,
            'created_at' => ['$gte' => new MongoDB\BSON\UTCDateTime(strtotime('-7 days') * 1000)]
        ]);
        $system_stats['unread_notifications'] = $unread_count;
    }
    
} catch (Exception $e) {
    error_log("Lỗi navbar: " . $e->getMessage());
}

// Xác định trang hiện tại
$is_active = function($page) use ($current_page) {
    return $current_page === $page ? 'active' : '';
};

$is_active_group = function($pages) use ($current_page) {
    return in_array($current_page, $pages) ? 'active' : '';
};

// Tải danh sách màu sắc cho quick add
$colors = [];
try {
    if (isset($db_client)) {
        $colors_collection = Database::getCollection('colors');
        if ($colors_collection) {
            $colors = $colors_collection->find(
                ['status' => true],
                ['sort' => ['name' => 1]]
            )->toArray();
        }
    }
} catch (Exception $e) {
    error_log("Lỗi tải màu sắc: " . $e->getMessage());
}
?>

<!-- Thanh điều hướng chính -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <!-- Logo và tên hệ thống -->
        <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>index.php">
            <i class="bi bi-cpu me-2"></i>
            <?= htmlspecialchars(SITE_NAME) ?>
        </a>
        
        <!-- Nút toggle mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#mainNavbar" aria-controls="mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Nội dung navbar -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <!-- Menu chính -->
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= $is_active('index.php') ?>" 
                       href="<?= SITE_URL ?>index.php">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Dashboard
                    </a>
                </li>
                
                <!-- Sản phẩm -->
                <li class="nav-item">
                    <a class="nav-link <?= $is_active('xemSanPham.php') ?>" 
                       href="<?= SITE_URL ?>xemSanPham.php">
                        <i class="bi bi-box-seam me-1"></i>
                        Sản phẩm
                        <?php if ($system_stats['today_products'] > 0): ?>
                        <span class="badge bg-success ms-1">
                            <?= number_format($system_stats['today_products']) ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <!-- Thống kê -->
                <li class="nav-item">
                    <a class="nav-link <?= $is_active('thongKeSanPham.php') ?>" 
                       href="<?= SITE_URL ?>thongKeSanPham.php">
                        <i class="bi bi-bar-chart me-1"></i>
                        Thống kê
                    </a>
                </li>
                
                <?php if ($is_admin): ?>
                <!-- Quản trị hệ thống -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $is_active_group(['quanlyMau.php', 'system_settings.php']) ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear me-1"></i>
                        Quản trị
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item <?= $is_active('quanlyMau.php') ?>" 
                               href="<?= SITE_URL ?>quanlyMau.php">
                                <i class="bi bi-palette me-2"></i>
                                Quản lý màu sắc
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>install.php">
                                <i class="bi bi-hdd me-2"></i>
                                Cài đặt hệ thống
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>api/stats.php" target="_blank">
                                <i class="bi bi-graph-up me-2"></i>
                                Xem API Stats
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- Bên phải navbar -->
            <ul class="navbar-nav align-items-center">
                <!-- Tìm kiếm -->
                <li class="nav-item d-none d-lg-block me-3">
                    <form action="<?= SITE_URL ?>xemSanPham.php" method="get" class="d-flex">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" class="form-control" placeholder="Tìm sản phẩm..." 
                                   name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button class="btn btn-outline-light" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </li>
                
                <!-- Thêm sản phẩm nhanh -->
                <li class="nav-item me-2">
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" 
                            data-bs-target="#quickAddModal" title="Thêm sản phẩm nhanh">
                        <i class="bi bi-plus-circle me-1"></i>
                        Thêm nhanh
                    </button>
                </li>
                
                <!-- Thông báo -->
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative" href="#" role="button" 
                       data-bs-toggle="dropdown" title="Thông báo">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if ($system_stats['unread_notifications'] > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $system_stats['unread_notifications'] < 10 ? $system_stats['unread_notifications'] : '9+' ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px; max-width: 400px;">
                        <li>
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="bi bi-bell me-2"></i>
                                    Thông báo
                                </span>
                                <?php if ($system_stats['unread_notifications'] > 0): ?>
                                <span class="badge bg-danger">
                                    <?= $system_stats['unread_notifications'] ?> mới
                                </span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider m-0"></li>
                        
                        <div style="max-height: 400px; overflow-y: auto;">
                        <?php
                        try {
                            // Lấy thông báo gần đây
                            $notifications_collection = Database::getCollection('notifications');
                            $notifications = $notifications_collection->find([
                                'user_id' => new MongoDB\BSON\ObjectId($current_user['_id'])
                            ], [
                                'sort' => ['created_at' => -1],
                                'limit' => 5
                            ])->toArray();
                            
                            if (empty($notifications)): ?>
                            <li>
                                <div class="dropdown-item text-muted text-center py-3">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Không có thông báo mới
                                </div>
                            </li>
                            <?php else: 
                            foreach ($notifications as $notif):
                                $time_ago = time_ago($notif['created_at']->toDateTime());
                                $icon_class = match($notif['type'] ?? 'info') {
                                    'success' => 'check-circle text-success',
                                    'warning' => 'exclamation-triangle text-warning',
                                    'error' => 'x-circle text-danger',
                                    default => 'info-circle text-info'
                                };
                        ?>
                        <li>
                            <a class="dropdown-item py-2 <?= !($notif['read'] ?? false) ? 'bg-light' : '' ?>" 
                               href="javascript:void(0)" onclick="markNotificationRead('<?= (string)$notif['_id'] ?>')">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="bi bi-<?= $icon_class ?> fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <strong class="<?= !($notif['read'] ?? false) ? 'text-dark' : 'text-muted' ?>">
                                                <?= htmlspecialchars($notif['title'] ?? 'Không có tiêu đề') ?>
                                            </strong>
                                            <small class="text-muted ms-2"><?= $time_ago ?></small>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?= htmlspecialchars(substr($notif['message'] ?? '', 0, 100)) ?>
                                            <?= strlen($notif['message'] ?? '') > 100 ? '...' : '' ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider m-0"></li>
                        <?php endforeach; 
                            endif;
                            
                        } catch (Exception $e) { ?>
                        <li>
                            <div class="dropdown-item text-danger text-center py-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Lỗi tải thông báo
                            </div>
                        </li>
                        <?php } ?>
                        </div>
                        
                        <li>
                            <div class="dropdown-footer text-center py-2">
                                <a class="btn btn-sm btn-outline-primary w-100" 
                                   href="javascript:void(0)" onclick="markAllNotificationsRead()">
                                    <i class="bi bi-check-all me-2"></i>
                                    Đánh dấu đã đọc tất cả
                                </a>
                            </div>
                        </li>
                    </ul>
                </li>
                
                <!-- Người dùng -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <!-- Avatar -->
                        <div class="position-relative">
                            <?php if (!empty($current_user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($current_user['avatar']) ?>" 
                                 alt="Avatar" class="rounded-circle border border-2 border-white" 
                                 width="36" height="36" style="object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center border border-2 border-white" 
                                 style="width: 36px; height: 36px;">
                                <i class="bi bi-person fs-6"></i>
                            </div>
                            <?php endif; ?>
                            <span class="position-absolute bottom-0 end-0 translate-middle badge rounded-pill bg-<?= $is_admin ? 'danger' : 'success' ?> border border-2 border-white p-1"></span>
                        </div>
                        
                        <!-- Thông tin user -->
                        <div class="d-none d-lg-block ms-2">
                            <div class="fw-medium text-truncate" style="max-width: 150px;">
                                <?= htmlspecialchars($current_user['fullname']) ?>
                            </div>
                            <small class="text-white-50">
                                <?= ucfirst($current_user['role']) ?>
                            </small>
                        </div>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                        <!-- Header user -->
                        <li>
                            <div class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($current_user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($current_user['avatar']) ?>" 
                                         alt="Avatar" class="rounded-circle me-3 border border-2" 
                                         width="48" height="48" style="object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 border border-2" 
                                         style="width: 48px; height: 48px;">
                                        <i class="bi bi-person fs-4"></i>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-truncate"><?= htmlspecialchars($current_user['fullname']) ?></div>
                                        <small class="text-muted d-block">@<?= htmlspecialchars($current_user['username']) ?></small>
                                        <span class="badge bg-<?= $is_admin ? 'danger' : 'primary' ?> mt-1">
                                            <?= ucfirst($current_user['role']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <li><hr class="dropdown-divider m-0"></li>
                        
                        <!-- Menu user -->
                        <li>
                            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="showUserProfile()">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person fs-5 me-3 text-primary"></i>
                                    <div>
                                        <div class="fw-medium">Tài khoản của tôi</div>
                                        <small class="text-muted">Xem và chỉnh sửa thông tin</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="showChangePassword()">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-key fs-5 me-3 text-warning"></i>
                                    <div>
                                        <div class="fw-medium">Đổi mật khẩu</div>
                                        <small class="text-muted">Cập nhật mật khẩu mới</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        
                        <?php if ($is_admin): ?>
                        <li>
                            <a class="dropdown-item py-2" href="<?= SITE_URL ?>quanlyMau.php">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-palette fs-5 me-3 text-success"></i>
                                    <div>
                                        <div class="fw-medium">Quản lý màu sắc</div>
                                        <small class="text-muted">Thêm/sửa/xóa màu</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li>
                            <a class="dropdown-item py-2" href="<?= SITE_URL ?>api/stats.php" target="_blank">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-graph-up fs-5 me-3 text-info"></i>
                                    <div>
                                        <div class="fw-medium">Thống kê hệ thống</div>
                                        <small class="text-muted">Xem báo cáo chi tiết</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider m-0"></li>
                        
                        <!-- Thống kê nhanh -->
                        <li>
                            <div class="dropdown-header py-2">
                                <small class="text-uppercase fw-bold">Thống kê hôm nay</small>
                            </div>
                        </li>
                        <li>
                            <div class="px-3 py-2">
                                <div class="row g-2 text-center">
                                    <div class="col-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-2">
                                                <div class="small text-muted">Sản phẩm</div>
                                                <div class="fw-bold fs-5 text-primary"><?= number_format($system_stats['today_products']) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body py-2">
                                                <div class="small text-muted">Độ tin cậy</div>
                                                <div class="fw-bold fs-5 text-success"><?= $system_stats['reliability'] ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <li><hr class="dropdown-divider m-0"></li>
                        
                        <!-- Đăng xuất -->
                        <li>
                            <a class="dropdown-item py-2 text-danger" 
                               href="<?= SITE_URL ?>logout.php"
                               onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-box-arrow-right fs-5 me-3"></i>
                                    <div>
                                        <div class="fw-medium">Đăng xuất</div>
                                        <small class="text-muted">Thoát khỏi hệ thống</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Thanh trạng thái hệ thống -->
<div class="container-fluid bg-light border-bottom py-1">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center flex-wrap">
                <!-- Trạng thái hệ thống -->
                <div class="me-3 d-flex align-items-center">
                    <span class="badge bg-<?= $system_stats['system_status'] ?> me-2">
                        <i class="bi bi-circle-fill"></i>
                    </span>
                    <small class="text-muted"><?= $system_stats['system_text'] ?></small>
                </div>
                
                <!-- Thời gian cập nhật -->
                <div class="me-3 d-flex align-items-center">
                    <i class="bi bi-clock text-muted me-1"></i>
                    <small class="text-muted">Cập nhật: <span data-time-update><?= $system_stats['last_update'] ?></span></small>
                </div>
                
                <!-- Trạng thái database -->
                <div class="me-3 d-flex align-items-center">
                    <span class="badge bg-<?= $system_stats['db_status'] ?> me-2">
                        <i class="bi bi-database"></i>
                    </span>
                    <small class="text-muted"><?= $system_stats['db_message'] ?></small>
                </div>
                
                <!-- Số lượng sản phẩm -->
                <div class="me-3 d-flex align-items-center">
                    <i class="bi bi-box text-muted me-1"></i>
                    <small class="text-muted">Hôm nay: <strong><?= number_format($system_stats['today_products']) ?></strong> sản phẩm</small>
                </div>
            </div>
        </div>
        
        <div class="col-auto">
            <!-- Hành động nhanh -->
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" onclick="window.location.reload()" 
                        title="Làm mới trang (F5)">
                    <i class="bi bi-arrow-clockwise"></i> Làm mới
                </button>
                
                <a href="<?= SITE_URL ?>api/stats.php" class="btn btn-outline-secondary" 
                   target="_blank" title="Thống kê API">
                    <i class="bi bi-graph-up"></i> Stats
                </a>
                
                <?php if ($is_admin): ?>
                <a href="<?= SITE_URL ?>install.php" class="btn btn-outline-secondary" 
                   title="Cài đặt hệ thống">
                    <i class="bi bi-gear"></i> Cài đặt
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm sản phẩm nhanh -->
<div class="modal fade" id="quickAddModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Thêm sản phẩm nhanh
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickAddForm" onsubmit="return quickAddProduct(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Màu sắc <span class="text-danger">*</span></label>
                        <select name="color_id" class="form-control form-select-sm" required>
                            <option value="">Chọn màu...</option>
                            <?php if (!empty($colors)): ?>
                                <?php foreach ($colors as $color): ?>
                                <option value="<?= (string)$color['_id'] ?>">
                                    <?= htmlspecialchars($color['name']) ?>
                                    <?php if (isset($color['hex_code'])): ?>
                                    <span style="color: <?= htmlspecialchars($color['hex_code']) ?>">●</span>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Không có màu nào</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Giá trị RGB <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="number" name="rgb_r" class="form-control form-control-sm" 
                                       placeholder="R" min="0" max="255" value="128" required>
                                <small class="text-muted">Red</small>
                            </div>
                            <div class="col-4">
                                <input type="number" name="rgb_g" class="form-control form-control-sm" 
                                       placeholder="G" min="0" max="255" value="128" required>
                                <small class="text-muted">Green</small>
                            </div>
                            <div class="col-4">
                                <input type="number" name="rgb_b" class="form-control form-control-sm" 
                                       placeholder="B" min="0" max="255" value="128" required>
                                <small class="text-muted">Blue</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Độ tin cậy: <span id="confidenceValue" class="fw-bold">95%</span></label>
                        <input type="range" name="confidence" class="form-range" 
                               min="0" max="100" value="95" oninput="document.getElementById('confidenceValue').textContent = this.value + '%'">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Thấp (0%)</small>
                            <small class="text-muted">Cao (100%)</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dây chuyền</label>
                        <select name="line_id" class="form-control form-select-sm">
                            <option value="1">Line 1</option>
                            <option value="2">Line 2</option>
                            <option value="3">Line 3</option>
                        </select>
                    </div>
                    
                    <div id="quickAddError" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="quickAddSubmit">
                        <span id="addButtonText">Thêm sản phẩm</span>
                        <span id="addButtonSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1056;"></div>

<script>
// Hàm định dạng thời gian
function time_ago(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " năm trước";
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " tháng trước";
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " ngày trước";
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " giờ trước";
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " phút trước";
    
    return "Vừa xong";
}

// Thêm sản phẩm nhanh
async function quickAddProduct(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('quickAddSubmit');
    const buttonText = document.getElementById('addButtonText');
    const buttonSpinner = document.getElementById('addButtonSpinner');
    const errorDiv = document.getElementById('quickAddError');
    
    // Ẩn thông báo lỗi cũ
    errorDiv.classList.add('d-none');
    
    // Hiển thị loading
    buttonText.textContent = 'Đang thêm...';
    buttonSpinner.classList.remove('d-none');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('<?= SITE_URL ?>api/add_product.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Thành công', 'Đã thêm sản phẩm thành công!');
            
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddModal'));
            modal.hide();
            
            // Reset form
            form.reset();
            document.getElementById('confidenceValue').textContent = '95%';
            
            // Làm mới trang sau 1 giây
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            errorDiv.textContent = data.message || 'Không thể thêm sản phẩm';
            errorDiv.classList.remove('d-none');
            resetAddButton();
        }
    } catch (error) {
        errorDiv.textContent = 'Lỗi kết nối: ' + error.message;
        errorDiv.classList.remove('d-none');
        resetAddButton();
    }
    
    function resetAddButton() {
        buttonText.textContent = 'Thêm sản phẩm';
        buttonSpinner.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

// Đánh dấu thông báo đã đọc
async function markNotificationRead(notificationId) {
    try {
        const response = await fetch('<?= SITE_URL ?>api/mark_notification_read.php?id=' + notificationId);
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            showToast('error', 'Lỗi', data.message || 'Không thể đánh dấu đã đọc');
        }
    } catch (error) {
        showToast('error', 'Lỗi', 'Không thể kết nối đến server');
    }
}

// Đánh dấu tất cả thông báo đã đọc
async function markAllNotificationsRead() {
    try {
        const response = await fetch('<?= SITE_URL ?>api/mark_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Thành công', 'Đã đánh dấu tất cả thông báo là đã đọc');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('error', 'Lỗi', data.message || 'Không thể đánh dấu đã đọc');
        }
    } catch (error) {
        showToast('error', 'Lỗi', 'Không thể kết nối đến server');
    }
}

// Hiển thị toast message
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer');
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-${type} text-white">
                <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-x-circle' : type === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle'} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Hiển thị hồ sơ người dùng
function showUserProfile() {
    // Tạo modal động
    const modalHtml = `
        <div class="modal fade" id="userProfileModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person me-2"></i>
                            Thông tin tài khoản
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <?php if (!empty($current_user['avatar'])): ?>
                                <img src="<?= htmlspecialchars($current_user['avatar']) ?>" 
                                     alt="Avatar" class="rounded-circle mb-3 border border-3 border-primary" 
                                     width="150" height="150" style="object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 border border-3 border-primary" 
                                     style="width: 150px; height: 150px;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                                <?php endif; ?>
                                
                                <h5 class="mb-1"><?= htmlspecialchars($current_user['fullname']) ?></h5>
                                <p class="text-muted mb-2">@<?= htmlspecialchars($current_user['username']) ?></p>
                                <span class="badge bg-<?= $is_admin ? 'danger' : 'primary' ?> fs-6">
                                    <?= ucfirst($current_user['role']) ?>
                                </span>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Chi tiết tài khoản</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Họ và tên</label>
                                                <div class="form-control bg-light"><?= htmlspecialchars($current_user['fullname']) ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Tên đăng nhập</label>
                                                <div class="form-control bg-light"><?= htmlspecialchars($current_user['username']) ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Email</label>
                                                <div class="form-control bg-light"><?= htmlspecialchars($current_user['email'] ?? 'Chưa cập nhật') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Số điện thoại</label>
                                                <div class="form-control bg-light"><?= htmlspecialchars($current_user['phone'] ?? 'Chưa cập nhật') ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Ngày tạo</label>
                                                <div class="form-control bg-light"><?= 
                                                    isset($current_user['created_at']) ? 
                                                    $current_user['created_at']->toDateTime()->format('d/m/Y H:i') : 
                                                    'Không xác định' 
                                                ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">Lần đăng nhập cuối</label>
                                                <div class="form-control bg-light"><?= 
                                                    isset($current_user['last_login']) ? 
                                                    $current_user['last_login']->toDateTime()->format('d/m/Y H:i') : 
                                                    'Không xác định' 
                                                ?></div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($current_user['department'])): ?>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label class="form-label text-muted">Phòng ban</label>
                                                <div class="form-control bg-light"><?= htmlspecialchars($current_user['department']) ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($current_user['notes'])): ?>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <label class="form-label text-muted">Ghi chú</label>
                                                <textarea class="form-control bg-light" rows="3" readonly><?= htmlspecialchars($current_user['notes']) ?></textarea>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-warning" onclick="showChangePassword()">
                            <i class="bi bi-key me-1"></i> Đổi mật khẩu
                        </button>
                        <button type="button" class="btn btn-primary" onclick="editUserProfile()">
                            <i class="bi bi-pencil me-1"></i> Chỉnh sửa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Thêm modal vào body nếu chưa có
    if (!document.getElementById('userProfileModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // Hiển thị modal
    const modal = new bootstrap.Modal(document.getElementById('userProfileModal'));
    modal.show();
}

// Hiển thị form đổi mật khẩu
function showChangePassword() {
    // Tạo modal động
    const modalHtml = `
        <div class="modal fade" id="changePasswordModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-key me-2"></i>
                            Đổi mật khẩu
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="changePasswordForm" onsubmit="return changePassword(event)">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ và số
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="current_password" class="form-control" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div id="passwordStrength" class="mb-3">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="strengthBar" style="width: 0%"></div>
                                </div>
                                <small id="strengthText" class="text-muted"></small>
                            </div>
                            
                            <div id="passwordError" class="alert alert-danger d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary" id="changePassSubmit">
                                <span id="changePassText">Đổi mật khẩu</span>
                                <span id="changePassSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Thêm modal vào body nếu chưa có
    if (!document.getElementById('changePasswordModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Thêm sự kiện kiểm tra mật khẩu mới
        setTimeout(() => {
            const newPasswordInput = document.querySelector('input[name="new_password"]');
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', checkPasswordStrength);
            }
        }, 100);
    }
    
    // Hiển thị modal
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
    
    // Đóng modal hồ sơ nếu đang mở
    const profileModal = document.getElementById('userProfileModal');
    if (profileModal) {
        const bsModal = bootstrap.Modal.getInstance(profileModal);
        if (bsModal) bsModal.hide();
    }
}

// Hiển thị/ẩn mật khẩu
function togglePassword(button) {
    const input = button.closest('.input-group').querySelector('input');
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Kiểm tra độ mạnh mật khẩu
function checkPasswordStrength() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    let text = '';
    let color = '';
    
    if (password.length >= 6) strength += 25;
    if (password.match(/[a-z]+/)) strength += 25;
    if (password.match(/[A-Z]+/)) strength += 25;
    if (password.match(/[0-9]+/)) strength += 25;
    
    if (strength < 50) {
        text = 'Yếu';
        color = 'danger';
    } else if (strength < 75) {
        text = 'Trung bình';
        color = 'warning';
    } else {
        text = 'Mạnh';
        color = 'success';
    }
    
    strengthBar.style.width = strength + '%';
    strengthBar.className = `progress-bar bg-${color}`;
    strengthText.textContent = `Độ mạnh: ${text}`;
    strengthText.className = `text-${color}`;
}

// Xử lý đổi mật khẩu
async function changePassword(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('passwordError');
    const submitBtn = document.getElementById('changePassSubmit');
    const buttonText = document.getElementById('changePassText');
    const buttonSpinner = document.getElementById('changePassSpinner');
    
    // Kiểm tra mật khẩu mới khớp
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
        errorDiv.textContent = 'Mật khẩu mới không khớp!';
        errorDiv.classList.remove('d-none');
        return false;
    }
    
    if (newPassword.length < 6) {
        errorDiv.textContent = 'Mật khẩu phải có ít nhất 6 ký tự!';
        errorDiv.classList.remove('d-none');
        return false;
    }
    
    // Hiển thị loading
    buttonText.textContent = 'Đang xử lý...';
    buttonSpinner.classList.remove('d-none');
    submitBtn.disabled = true;
    errorDiv.classList.add('d-none');
    
    try {
        const response = await fetch('<?= SITE_URL ?>api/change_password.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Thành công', 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.');
            
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
            
            // Đăng xuất sau 2 giây
            setTimeout(() => {
                window.location.href = '<?= SITE_URL ?>logout.php';
            }, 2000);
        } else {
            errorDiv.textContent = data.message || 'Đổi mật khẩu thất bại';
            errorDiv.classList.remove('d-none');
            resetPasswordButton();
        }
    } catch (error) {
        errorDiv.textContent = 'Không thể kết nối đến server: ' + error.message;
        errorDiv.classList.remove('d-none');
        resetPasswordButton();
    }
    
    function resetPasswordButton() {
        buttonText.textContent = 'Đổi mật khẩu';
        buttonSpinner.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

// Chỉnh sửa hồ sơ người dùng
function editUserProfile() {
    // Tạo modal chỉnh sửa
    const modalHtml = `
        <div class="modal fade" id="editProfileModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil me-2"></i>
                            Chỉnh sửa thông tin
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editProfileForm" onsubmit="return updateProfile(event)">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control" 
                                       value="<?= htmlspecialchars($current_user['fullname']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($current_user['email'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phòng ban</label>
                                <input type="text" name="department" class="form-control" 
                                       value="<?= htmlspecialchars($current_user['department'] ?? '') ?>">
                            </div>
                            
                            <div id="editProfileError" class="alert alert-danger d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary" id="editProfileSubmit">
                                <span id="editProfileText">Cập nhật</span>
                                <span id="editProfileSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Thêm modal vào body nếu chưa có
    if (!document.getElementById('editProfileModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // Đóng modal hồ sơ
    const profileModal = document.getElementById('userProfileModal');
    if (profileModal) {
        const bsModal = bootstrap.Modal.getInstance(profileModal);
        if (bsModal) bsModal.hide();
    }
    
    // Hiển thị modal chỉnh sửa
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
        modal.show();
    }, 300);
}

// Cập nhật hồ sơ
async function updateProfile(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('editProfileError');
    const submitBtn = document.getElementById('editProfileSubmit');
    const buttonText = document.getElementById('editProfileText');
    const buttonSpinner = document.getElementById('editProfileSpinner');
    
    // Hiển thị loading
    buttonText.textContent = 'Đang cập nhật...';
    buttonSpinner.classList.remove('d-none');
    submitBtn.disabled = true;
    errorDiv.classList.add('d-none');
    
    try {
        const response = await fetch('<?= SITE_URL ?>api/update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Thành công', 'Cập nhật thông tin thành công!');
            
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            modal.hide();
            
            // Làm mới trang
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            errorDiv.textContent = data.message || 'Cập nhật thất bại';
            errorDiv.classList.remove('d-none');
            resetEditButton();
        }
    } catch (error) {
        errorDiv.textContent = 'Không thể kết nối đến server';
        errorDiv.classList.remove('d-none');
        resetEditButton();
    }
    
    function resetEditButton() {
        buttonText.textContent = 'Cập nhật';
        buttonSpinner.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

// Khởi tạo khi trang tải xong
document.addEventListener('DOMContentLoaded', function() {
    // Phím tắt F5 để làm mới
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F5') {
            e.preventDefault();
            window.location.reload();
        }
    });
    
    // Cập nhật thời gian mỗi phút
    setInterval(() => {
        const timeElement = document.querySelector('[data-time-update]');
        if (timeElement) {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('vi-VN');
        }
    }, 60000);
    
    // Tự động đóng dropdown khi click bên ngoài
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown') && !e.target.closest('.modal')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
});
</script>