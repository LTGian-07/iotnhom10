<?php
require_once 'config.php';
require_once 'db.php';

requireLogin();

$page_title = 'Thống kê & Biểu đồ';

// --- XỬ LÝ THỐNG KÊ ---

// 1. Thống kê theo Màu sắc (Tổng số lượng từng loại màu)
$color_counts = getProductStatsByColor(); 
// Giả định hàm getProductStatsByColor() trả về mảng ['Red' => 120, 'Green' => 80, ...]

// 2. Thống kê theo Thời gian (Ví dụ: Sản phẩm mỗi ngày trong 7 ngày gần nhất)
$date_range = 7;
$daily_counts = getDailyProductCounts($date_range);
// Giả định hàm getDailyProductCounts() trả về mảng ['2025-12-10' => 50, ...]

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <h2><i class="bi bi-graph-up-arrow me-2"></i> <?php echo $page_title; ?></h2>
    <hr>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Phân bố Sản phẩm theo Màu</h5></div>
                <div class="card-body">
                    <canvas id="colorChart" width="400" height="400"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Số lượng Sản phẩm trong <?php echo $date_range; ?> ngày qua</h5></div>
                <div class="card-body">
                    <canvas id="dailyChart" width="400" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Số liệu Chi tiết</h5></div>
                <div class="card-body">
                    <p>Cần triển khai bảng số liệu cụ thể tại đây...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dữ liệu cho biểu đồ Màu sắc
    const colorLabels = <?php echo json_encode(array_keys($color_counts)); ?>;
    const colorData = <?php echo json_encode(array_values($color_counts)); ?>;

    new Chart(document.getElementById('colorChart'), {
        type: 'doughnut',
        data: {
            labels: colorLabels,
            datasets: [{
                data: colorData,
                backgroundColor: ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF7F00'], // Cần mapping màu thực tế
            }]
        }
    });

    // Dữ liệu cho biểu đồ Hàng ngày
    const dailyLabels = <?php echo json_encode(array_keys($daily_counts)); ?>;
    const dailyData = <?php echo json_encode(array_values($daily_counts)); ?>;

    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Sản phẩm',
                data: dailyData,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>