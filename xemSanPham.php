<?php
require_once 'config.php';
require_once 'db.php';

// Yêu cầu đăng nhập trước khi truy cập
requireLogin();

$page_title = 'Danh sách Sản phẩm';

// --- XỬ LÝ PHÂN TRANG & LỌC ---
$page = $_GET['page'] ?? 1;
$limit = 15; // Số sản phẩm trên mỗi trang
$skip = ($page - 1) * $limit;
$filter = [];
$search = '';

// Lọc theo màu sắc
if (isset($_GET['color']) && $_GET['color'] !== '') {
    // Nếu tham số 'color' tồn tại trên URL, thêm vào bộ lọc
    $filter['color_name'] = $_GET['color'];
}

// Lọc theo khoảng tin cậy
if (isset($_GET['min_conf']) && is_numeric($_GET['min_conf'])) {
    // Chuyển đổi giá trị % (ví dụ: 90) thành float (0.9) và áp dụng
    $filter['confidence'] = ['$gte' => (float)$_GET['min_conf'] / 100];
}

// Sau đó, dữ liệu được truy vấn:
$products = getProducts($filter, $limit, $skip, ['created_at' => -1]);


$total_items = countProducts($filter);
$total_pages = ceil($total_items / $limit);

$products = getProducts($filter, $limit, $skip, ['created_at' => -1]);


$colors = getColors(); 

include 'includes/header.php'; 
?>

<div class="container-fluid py-4">
    <h2><i class="bi bi-box me-2"></i> <?php echo $page_title; ?></h2>
    <hr>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Lọc theo Màu sắc</label>
                    <select name="color" class="form-select">
                        <option value="">-- Tất cả Màu --</option>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?php echo $color['name']; ?>" 
                                <?php echo (isset($_GET['color']) && $_GET['color'] == $color['name']) ? 'selected' : ''; ?>>
                                <?php echo $color['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Độ tin cậy Tối thiểu (%)</label>
                    <input type="number" name="min_conf" class="form-control" min="0" max="100" 
                           value="<?php echo $_GET['min_conf'] ?? 80; ?>">
                </div>
                <div class="col-md-4">
                    <form method="GET" class="row g-3 align-items-end">
                       <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Lọc</button>
                    </form>
                    <a href="xemSanPham.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <p class="text-muted">Tổng cộng: <strong><?php echo number_format($total_items); ?></strong> sản phẩm</p>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Mã Batch</th>
                            <th>Màu Sắc</th>
                            <th>Giá trị RGB</th>
                            <th>Độ Tin Cậy</th>
                            <th>Thời Gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="text-center">Không tìm thấy sản phẩm nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['batch_code'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="color-dot me-2" style="background-color: <?php echo $product['color_code']; ?>"></div>
                                    <?php echo htmlspecialchars($product['color_name']); ?>
                                </td>
                                <td>R:<?php echo $product['rgb_r']; ?> G:<?php echo $product['rgb_g']; ?> B:<?php echo $product['rgb_b']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($product['confidence'] >= 0.9) ? 'success' : 'warning'; ?>">
                                        <?php echo number_format($product['confidence'] * 100, 1); ?>%
                                    </span>
                                </td>
                                <td><?php echo formatDate($product['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>