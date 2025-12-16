<?php
require_once 'config.php';
require_once 'db.php';

requireLogin();
if (getCurrentUser()['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$page_title = 'Thêm Tài khoản Mới';

// 💥 FIX 3: Sửa tên biến session để đọc lỗi và dữ liệu cũ đúng
$old_input = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['errors']);

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-plus me-2"></i> Thêm Tài khoản Mới</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card">
            <div class="card-header">Thông tin Tài khoản</div>
            <div class="card-body">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Lỗi:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="xuLyTaiKhoan.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($old_input['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận Mật khẩu</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Tên đầy đủ</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" 
                               value="<?php echo htmlspecialchars($old_input['fullname'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Quyền hạn</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user" <?php echo ($old_input['role'] ?? '') == 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="manager" <?php echo ($old_input['role'] ?? '') == 'manager' ? 'selected' : ''; ?>>Quản lý</option>
                            <option value="admin" <?php echo ($old_input['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo SITE_URL; ?>quanLyTaiKhoan.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Thêm Tài khoản
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>