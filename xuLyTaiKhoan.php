<?php
require_once 'config.php';
require_once 'db.php';

// Kiểm tra phiên đăng nhập và quyền Admin
requireLogin();
if (getCurrentUser()['role'] !== 'admin') {
    header('Location: ' . SITE_URL . 'index.php');
    exit();
}

// 💥 FIX 1: Lấy 'action' từ cả $_GET (dùng cho Xóa) và $_POST (dùng cho Thêm/Sửa)
$action = $_GET['action'] ?? $_POST['action'] ?? null; 

// Khai báo các biến mặc định
$users_col = Database::getCollection('users');
$redirect_to = 'quanLyTaiKhoan.php';
$errors = [];
$form_data = [];

// Xử lý các hành động
switch ($action) {
    case 'create':
    case 'update':
        
        // 1. CHUẨN BỊ DỮ LIỆU
        $form_data['username'] = trim($_POST['username'] ?? '');
        $form_data['fullname'] = trim($_POST['fullname'] ?? '');
        $form_data['role'] = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // 2. KIỂM TRA DỮ LIỆU
        if (empty($form_data['username'])) {
            $errors[] = 'Tên đăng nhập không được để trống.';
        }
        if (empty($form_data['fullname'])) {
            $errors[] = 'Tên đầy đủ không được để trống.';
        }
        if (!in_array($form_data['role'], ['admin', 'manager', 'user'])) {
            $errors[] = 'Quyền người dùng không hợp lệ.';
        }

        // Kiểm tra mật khẩu (bắt buộc khi tạo, tùy chọn khi sửa)
        if ($action === 'create' || !empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            }
            if ($password !== $confirm_password) {
                $errors[] = 'Xác nhận mật khẩu không khớp.';
            }
        }

        // 3. XỬ LÝ ACTION TẠO (CREATE)
        if ($action === 'create' && empty($errors)) {
            
            // Kiểm tra trùng tên đăng nhập
            if ($users_col->findOne(['username' => $form_data['username']])) {
                $errors[] = 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.';
            }

            if (empty($errors)) {
                try {
                    $insert_data = [
                        'username' => $form_data['username'],
                        'fullname' => $form_data['fullname'],
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $form_data['role'],
                        'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000)
                    ];
                    
                    $users_col->insertOne($insert_data);

                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Tạo tài khoản thành công.'];
                    header('Location: ' . SITE_URL . $redirect_to);
                    exit();
                } catch (Exception $e) {
                    $errors[] = 'Lỗi CSDL khi tạo: ' . $e->getMessage();
                }
            }
        }

        // 4. XỬ LÝ ACTION SỬA (UPDATE)
        if ($action === 'update') {
            $user_id = $_POST['user_id'] ?? null;
            $redirect_to = "suaTaiKhoan.php?id={$user_id}";

            if (!$user_id) {
                $errors[] = 'Thiếu ID người dùng để sửa.';
            }

            if (empty($errors)) {
                try {
                    $objectId = new MongoDB\BSON\ObjectId($user_id);
                    $update_fields = [
                        'fullname' => $form_data['fullname'],
                        'role' => $form_data['role']
                    ];

                    // Chỉ cập nhật mật khẩu nếu có nhập (và đã được kiểm tra ở bước 2)
                    if (!empty($password)) {
                        $update_fields['password'] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    $users_col->updateOne(
                        ['_id' => $objectId],
                        ['$set' => $update_fields]
                    );

                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Cập nhật tài khoản thành công.'];
                    $redirect_to = 'quanLyTaiKhoan.php'; // Chuyển về trang danh sách sau khi sửa thành công
                    header('Location: ' . SITE_URL . $redirect_to);
                    exit();

                } catch (Exception $e) {
                    $errors[] = 'Lỗi CSDL khi cập nhật: ' . $e->getMessage();
                }
            }
        }

        // 5. XỬ LÝ KHI CÓ LỖI (CREATE/UPDATE)
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $form_data;
            // Chuyển hướng về trang form ban đầu
            header('Location: ' . SITE_URL . $redirect_to);
            exit();
        }
        break;

    case 'delete':
        
        // 6. XỬ LÝ ACTION XÓA (DELETE)
        $user_id = $_GET['id'] ?? null;
        $current_user_id = (string)(getCurrentUser()['_id'] ?? '');

        if (!$user_id) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Thiếu ID tài khoản để xóa.'];
        } elseif ($user_id === $current_user_id) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Bạn không thể xóa tài khoản của chính mình.'];
        } else {
            try {
                $objectId = new MongoDB\BSON\ObjectId($user_id);
                $result = $users_col->deleteOne(['_id' => $objectId]);

                if ($result->getDeletedCount() === 1) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Xóa tài khoản thành công.'];
                } else {
                    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Không tìm thấy tài khoản để xóa.'];
                }
            } catch (Exception $e) {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Lỗi CSDL khi xóa: ' . $e->getMessage()];
            }
        }
        header('Location: ' . SITE_URL . $redirect_to);
        exit();

    default:
        // Hành động không hợp lệ
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Hành động không hợp lệ.'];
        header('Location: ' . SITE_URL . $redirect_to);
        exit();
}
?>