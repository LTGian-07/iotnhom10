<?php
// index.php - Dashboard chính
require_once 'config.php';
require_once 'db.php';

// Kiểm tra đăng nhập
requireLogin();

// Lấy thông tin người dùng
$current_user = getCurrentUser();
$page_title = 'Dashboard - ' . SITE_NAME;

// Lấy thống kê
try {
    // Tổng sản phẩm
    $total_products = countProducts();
    
    // Tổng màu sắc
    $colors = Database::getCollection('colors');
    $total_colors = $colors->countDocuments();
    
    // Sản phẩm hôm nay
    $today_start = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
    $today_end = new MongoDB\BSON\UTCDateTime(strtotime('tomorrow') * 1000);
    $today_products = countProducts(['created_at' => [
        '$gte' => $today_start,
        '$lt' => $today_end
    ]]);
    
    // Độ tin cậy trung bình
    $products_col = Database::getCollection('products');
    $avg_confidence = $products_col->aggregate([
        ['$group' => [
            '_id' => null,
            'avg_confidence' => ['$avg' => '$confidence']
        ]]
    ])->toArray();
    $avg_confidence = $avg_confidence[0]['avg_confidence'] ?? 0;
    
    // Thống kê theo màu (7 ngày)
    $week_ago = new MongoDB\BSON\UTCDateTime(strtotime('-7 days') * 1000);
    $color_stats = $products_col->aggregate([
        ['$match' => ['created_at' => ['$gte' => $week_ago]]],
        ['$lookup' => [
            'from' => 'colors',
            'localField' => 'color_id',
            'foreignField' => '_id',
            'as' => 'color_info'
        ]],
        ['$unwind' => '$color_info'],
        ['$group' => [
            '_id' => '$color_id',
            'name' => ['$first' => '$color_info.name'],
            'code' => ['$first' => '$color_info.code'],
            'count' => ['$sum' => 1]
        ]],
        ['$sort' => ['count' => -1]],
        ['$limit' => 5]
    ])->toArray();
    
    // Sản phẩm mới nhất
    $recent_products = getProducts([], 10, 0, ['created_at' => -1]);
    
    // Hoạt động hệ thống (logs)
    $recent_logs = getLogs([], 10);
    
} catch (Exception $e) {
    $error = "Lỗi khi lấy thống kê: " . $e->getMessage();
    logSystem('error', 'dashboard', $e->getMessage(), $current_user['id']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .recent-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .recent-item:hover {
            background: #f8f9fa;
        }
        
        .recent-item:last-child {
            border-bottom: none;
        }
        
        .color-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-action-btn {
            padding: 15px;
            border-radius: 10px;
            background: white;
            border: 1px solid #dee2e6;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #4361ee;
            color: #4361ee;
        }
        
        .quick-action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .stat-card .value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-3">Chào mừng, <?php echo htmlspecialchars($current_user['fullname']); ?>!</h1>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-clock-history me-2"></i>
                            <?php 
                                $hour = date('H');
                                if ($hour < 12) echo 'Buổi sáng tốt lành!';
                                elseif ($hour < 18) echo 'Buổi chiều vui vẻ!';
                                else echo 'Buổi tối an lành!';
                            ?>
                            Bạn có <?php echo $today_products; ?> sản phẩm mới hôm nay.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="avatar-circle bg-white text-primary d-inline-flex">
                            <i class="bi bi-person fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card bg-white">
                        <div class="icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="value text-primary"><?php echo number_format($total_products); ?></div>
                        <div class="label">Tổng sản phẩm</div>
                        <small class="text-muted">+<?php echo $today_products; ?> hôm nay</small>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card bg-white">
                        <div class="icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-palette"></i>
                        </div>
                        <div class="value text-success"><?php echo $total_colors; ?></div>
                        <div class="label">Màu sắc</div>
                        <small class="text-muted">Đang hoạt động</small>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card bg-white">
                        <div class="icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div class="value text-warning"><?php echo number_format($avg_confidence, 1); ?>%</div>
                        <div class="label">Độ tin cậy TB</div>
                        <small class="text-muted">Toàn hệ thống</small>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card bg-white">
                        <div class="icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="value text-info"><?php echo number_format($today_products); ?></div>
                        <div class="label">Sản xuất hôm nay</div>
                        <small class="text-muted">Cập nhật mới nhất</small>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Recent Activities -->
            <div class="row">
                <!-- Left Column: Charts -->
                <div class="col-lg-8">
                    <!-- Color Distribution Chart -->
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">
                                <i class="bi bi-pie-chart me-2"></i>
                                Phân bố màu sắc (7 ngày)
                            </h5>
                            <select class="form-select form-select-sm" style="width: auto;" id="chartPeriod">
                                <option value="7">7 ngày</option>
                                <option value="30">30 ngày</option>
                                <option value="90">90 ngày</option>
                            </select>
                        </div>
                        <canvas id="colorChart" height="250"></canvas>
                    </div>
                    
                    <!-- Production Trend -->
                    <div class="chart-container">
                        <h5 class="mb-4">
                            <i class="bi bi-bar-chart me-2"></i>
                            Xu hướng sản xuất
                        </h5>
                        <canvas id="productionChart" height="250"></canvas>
                    </div>
                </div>
                
                <!-- Right Column: Recent Activities -->
                <div class="col-lg-4">
                    <!-- Recent Products -->
                    <div class="card mb-4">
                        
                       
                        <div class="card-footer bg-white">
                            <a href="xemSanPham.php" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-arrow-right me-1"></i> Xem tất cả
                            </a>
                        </div>
                    </div>
                    
                    <!-- System Logs -->
                    <div class="card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">
                                <i class="bi bi-activity me-2"></i>
                                Hoạt động hệ thống
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_logs)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-journal fs-1 text-muted"></i>
                                    <p class="mt-2">Chưa có hoạt động</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_logs as $log): ?>
                                    <div class="list-group-item recent-item">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <?php 
                                                $icon_class = '';
                                                switch ($log['type']) {
                                                    case 'success': $icon_class = 'bi-check-circle text-success'; break;
                                                    case 'warning': $icon_class = 'bi-exclamation-triangle text-warning'; break;
                                                    case 'error': $icon_class = 'bi-x-circle text-danger'; break;
                                                    default: $icon_class = 'bi-info-circle text-info';
                                                }
                                                ?>
                                                <i class="bi <?php echo $icon_class; ?> fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium"><?php echo htmlspecialchars($log['module']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($log['message']); ?></div>
                                                <div class="small text-muted">
                                                    <?php echo formatDate($log['created_at'], 'd/m H:i'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">
                        <i class="bi bi-lightning me-2"></i>
                        Thao tác nhanh
                    </h5>
                    <div class="quick-actions">
                        <?php if ($current_user['role'] === 'admin'): ?>
                        
                        <a href="quanLyTaiKhoan.php" class="quick-action-btn">
                            <i class="bi bi-people"></i>
                            <span>Quản lý tài khoản</span>
                        </a>
                        <?php endif; ?>
                    
                        
                        <a href="thongKeSanPham.php" class="quick-action-btn">
                            <i class="bi bi-bar-chart"></i>
                            <span>Thống kê</span>
                        </a>
                        <a href="#" class="quick-action-btn action-btn-command" data-command="start">
                            <i class="bi bi-play-circle text-success"></i>
                            <span>Bắt đầu (Start)</span>
                        </a>
                        <a href="#" class="quick-action-btn action-btn-command" data-command="stop">
                            <i class="bi bi-play-circle text-success"></i>
                            <span>Dừng (Stop)</span>
                        </a>
                        <a href="#" class="quick-action-btn action-btn-command" data-command="manual">
                            <i class="bi bi-gear text-warning"></i>
                            <span>Chế độ Thủ công</span>
                        </a>
                        
                        <a href="#" class="quick-action-btn action-btn-command" data-command="sleep">
                            <i class="bi bi-moon-stars text-info"></i>
                            <span>Chế độ Ngủ (Sleep)</span>
                        </a>

                       
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm thủ công</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProductForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Màu sắc</label>
                            <select name="color_id" class="form-control" required>
                                <option value="">Chọn màu...</option>
                                <?php
                                $colors_list = getColors(['status' => true]);
                                foreach ($colors_list as $color):
                                ?>
                                <option value="<?php echo (string)$color['_id']; ?>">
                                    <?php echo htmlspecialchars($color['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-4">
                                <label class="form-label">R</label>
                                <input type="number" name="rgb_r" class="form-control" min="0" max="255" value="128" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">G</label>
                                <input type="number" name="rgb_g" class="form-control" min="0" max="255" value="128" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">B</label>
                                <input type="number" name="rgb_b" class="form-control" min="0" max="255" value="128" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Độ tin cậy (%)</label>
                            <input type="number" name="confidence" class="form-control" min="0" max="100" step="0.1" value="95.5" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mã lô</label>
                            <input type="text" name="batch_code" class="form-control" value="<?php echo generateBatchCode(); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Dây chuyền</label>
                            <select name="line_id" class="form-control">
                                <option value="1">Line 1</option>
                                <option value="2">Line 2</option>
                                <option value="3">Line 3</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Color Distribution Chart
        const colorCtx = document.getElementById('colorChart').getContext('2d');
        const colorChart = new Chart(colorCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($color_stats, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($color_stats, 'count')); ?>,
                    backgroundColor: <?php echo json_encode(array_column($color_stats, 'code')); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' sản phẩm';
                            }
                        }
                    }
                }
            }
        });
        
        // Production Trend Chart (sample data)
        const productionCtx = document.getElementById('productionChart').getContext('2d');
        const productionChart = new Chart(productionCtx, {
            type: 'line',
            data: {
                labels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
                datasets: [{
                    label: 'Sản phẩm',
                    data: [120, 150, 180, 130, 200, 170, 190],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Số lượng'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Ngày trong tuần'
                        }
                    }
                }
            }
        });
        
        // Add Product Form
        const addProductForm = document.getElementById('addProductForm');
        addProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang thêm...';
            submitBtn.disabled = true;
            
            fetch('api/add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thêm sản phẩm thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể thêm sản phẩm'));
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                alert('Lỗi kết nối: ' + error.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Update chart on period change
        document.getElementById('chartPeriod').addEventListener('change', function() {
            const period = this.value;
            
            // In a real app, fetch new data from API
            fetch(`api/get_stats.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    colorChart.data.labels = data.labels;
                    colorChart.data.datasets[0].data = data.data;
                    colorChart.data.datasets[0].backgroundColor = data.colors;
                    colorChart.update();
                });
        });
    });
    const controlButtons = document.querySelectorAll('.action-btn-command');
    controlButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const command = this.getAttribute('data-command');
            
            if (!confirm(`Bạn có chắc chắn muốn gửi lệnh "${command.toUpperCase()}" đến hệ thống không?`)) {
                return;
            }
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Gửi lệnh...';
            this.disabled = true;
            
            const formData = new FormData();
            formData.append('action', command);
            
            fetch('api/control_system.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lệnh "' + command.toUpperCase() + '" đã được gửi thành công!');
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể gửi lệnh.'));
                }
            })
            .catch(error => {
                alert('Lỗi kết nối: Không thể gửi lệnh điều khiển. ' + error.message);
            })
            .finally(() => {
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    });

    </script>
</body>
</html>