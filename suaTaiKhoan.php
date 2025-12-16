<?php
require_once 'config.php';
require_once 'db.php';

requireLogin();
if (getCurrentUser()['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$page_title = 'Sửa Tài khoản';
$user_id = $_GET['id'] ?? null;
$user_data = null;
$error_loading = false;

// 1. LẤY DỮ LIỆU NGƯỜI DÙNG CŨ
if ($user_id) {
    try {
        $users_col = Database::getCollection('users');
        $objectId = new MongoDB\BSON\ObjectId($user_id);
        $user_data = $users_col->findOne(['_id' => $objectId]);
        
        if (!$user_data) {
            $error_loading = true;
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Không tìm thấy tài khoản để sửa.'];
            header('Location: ' . SITE_URL . 'quanLyTaiKhoan.php');
            exit();
        }
    } catch (Exception $e) {
        $error_loading = true;
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Lỗi CSDL: ' . $e->getMessage()];
        header('Location: ' . SITE_URL . 'quanLyTaiKhoan.php');
        exit();
    }
} else {
    $error_loading = true;
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Thiếu ID tài khoản để sửa.'];
    header('Location: ' . SITE_URL . 'quanLyTaiKhoan.php');
    exit();
}

// 2. LẤY DỮ LIỆU VÀ LỖI CŨ (Nếu có lỗi redirect từ xuLyTaiKhoan.php)
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

// Nếu có lỗi, ưu tiên dùng dữ liệu từ form_data để điền lại
$display_data = empty($form_data) ? $user_data : array_merge((array)$user_data, $form_data);

// Xóa session sau khi lấy
unset($_SESSION['errors'], $_SESSION['form_data']);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i> Sửa Tài khoản: <?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></h1>
    <a href="<?php echo SITE_URL; ?>quanLyTaiKhoan.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Quay lại
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Lỗi:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="xuLyTaiKhoan.php?action=update" method="POST">
            
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập (Không thể sửa)</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($display_data['username'] ?? ''); ?>" disabled readonly>
            </div>

            <div class="mb-3">
                <label for="fullname" class="form-label">Tên đầy đủ</label>
                <input type="text" class="form-control" id="fullname" name="fullname" 
                       value="<?php echo htmlspecialchars($display_data['fullname'] ?? ''); ?>" required>
            </div>
            
            <hr>
            <p class="text-muted">Để trống các trường dưới đây nếu không muốn thay đổi mật khẩu.</p>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu mới</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận Mật khẩu mới</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Quyền</label>
                <select class="form-select" id="role" name="role" required>
                    <?php $selected_role = $display_data['role'] ?? 'user'; ?>
                    <option value="user" <?php echo ($selected_role === 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="manager" <?php echo ($selected_role === 'manager') ? 'selected' : ''; ?>>Quản lý</option>
                    <option value="admin" <?php echo ($selected_role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-arrow-repeat me-1"></i> Cập nhật Tài khoản
            </button>
        </form>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>