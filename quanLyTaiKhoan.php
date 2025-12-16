<?php
require_once 'config.php';
require_once 'db.php';

// Kiểm tra phiên đăng nhập
requireLogin();

// Chỉ Admin mới được quản lý tài khoản
$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$page_title = 'Quản lý Tài khoản';

// --- LOGIC TRUY VẤN DANH SÁCH NGƯỜI DÙNG ---
try {
    $users_col = Database::getCollection('users');
    // Lấy tất cả người dùng, sắp xếp theo ngày tạo mới nhất
    $users = $users_col->find([], ['sort' => ['created_at' => -1]])->toArray();
    $db_error = false;
} catch (Exception $e) {
    // Xử lý lỗi kết nối DB
    $users = [];
    $db_error = true;
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Lỗi kết nối hoặc truy vấn cơ sở dữ liệu: ' . $e->getMessage()
    ];
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i> Quản lý Tài khoản</h1>
    <a href="http://localhost/iot_system/themTaiKhoan.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Thêm Tài khoản mới
    </a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['message']['text']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">Danh sách Người dùng (<?php echo count($users); ?>)</div>
    <div class="card-body p-0">
        <?php if ($db_error): ?>
             <div class="alert alert-danger m-3" role="alert">
                Không thể tải danh sách người dùng do lỗi hệ thống. Vui lòng kiểm tra kết nối database.
            </div>
        <?php elseif (empty($users)): ?>
            <div class="alert alert-info m-3" role="alert">
                Chưa có tài khoản nào được tạo.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Tên đầy đủ</th>
                            <th scope="col">Tên đăng nhập</th>
                            <th scope="col">Quyền</th>
                            <th scope="col">Ngày tạo</th>
                            <th scope="col">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                        <?php foreach ($users as $user): 
                            // Định nghĩa màu badge cho Role
                            $role_badge = match ($user['role']) {
                                'admin' => '<span class="badge bg-danger">Admin</span>',
                                'manager' => '<span class="badge bg-warning text-dark">Quản lý</span>',
                                default => '<span class="badge bg-secondary">User</span>',
                            };
                        ?>
                            <tr>
                                <th scope="row"><?php echo $i++; ?></th>
                                <td><?php echo htmlspecialchars($user['fullname'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                                <td><?php echo $role_badge; ?></td>
                                <td>
                                    <?php echo isset($user['created_at']) ? date('d/m/Y H:i', $user['created_at']->toDateTime()->getTimestamp()) : 'N/A'; ?>
                                </td>
                                <td>
                                    <a href="http://localhost/iot_system/suaTaiKhoan>" 
                                       class="btn btn-sm btn-info text-white me-2" title="Sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <?php if ((string)($user['_id'] ?? '') !== (string)($current_user['_id'] ?? '')): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                title="Xóa"
                                                onclick="confirmDelete('<?php echo $user['_id']; ?>', '<?php echo htmlspecialchars($user['fullname'] ?? 'Tài khoản này'); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-light" disabled title="Không thể xóa tài khoản đang dùng">
                                            <i class="bi bi-trash-fill text-secondary"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Xác nhận Xóa Tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa tài khoản <strong id="userNameToDelete"></strong> không? Hành động này không thể hoàn tác.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <a id="confirmDeleteButton" href="#" class="btn btn-danger">Xóa</a>
            </div>
        </div>
    </div>
</div>

<script>
   
    
    function confirmDelete(userId, userName) {
        document.getElementById('userNameToDelete').textContent = userName;
        // Đường dẫn đến file xử lý xóa (bạn cần tạo file này)
        const deleteUrl = SITE_URL + 'xuLyTaiKhoan.php?action=delete&id=' + userId; 
        document.getElementById('confirmDeleteButton').href = deleteUrl;
        
        // Hiển thị modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        deleteModal.show();
    }
</script>

<?php 
include 'includes/footer.php'; 
?>