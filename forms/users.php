<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC (PHP)
// =========================================================
if (!class_exists('Connection')) {
    class Connection {
        private $host = 'localhost';
        private $db_name = 'mental_health_support';
        private $user_name = 'root';
        private $password = '';
        public $db;

        public function __construct() {
            try {
                $this->db = new PDO(
                    "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4",
                    $this->user_name,
                    $this->password
                );
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                header('Content-Type: application/json');
                die(json_encode(["status" => false, "msg" => "DB connection failed"]));
            }
        }
    }
}

// ROUTER LOGIC
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    function uploadImage($file) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            return "default.jpg";
        }

        $filename = time() . "_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $filename;
        }

        return "default.jpg";
    }

    // Read Users
    if ($route == 'display') {
        $sql = "SELECT *
                FROM users
                ORDER BY user_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create User
    if ($route == 'create') {
        try {
            $profile_pic = "default.jpg";

            if (!empty($_FILES['profile_pic']['name'])) {
                $profile_pic = uploadImage($_FILES['profile_pic']);
            }

            $sql = "INSERT INTO users
                    (full_name, email, password, phone, gender, date_of_birth, user_type, profile_pic, status)
                    VALUES
                    (:full_name, :email, :password, :phone, :gender, :date_of_birth, :user_type, :profile_pic, :status)";

            $stm = $conn->prepare($sql);
            $success = $stm->execute([
                ':full_name'     => $_POST['full_name'],
                ':email'         => $_POST['email'],
                ':password'      => $_POST['password'],
                ':phone'         => $_POST['phone'],
                ':gender'        => $_POST['gender'],
                ':date_of_birth' => $_POST['date_of_birth'],
                ':user_type'     => $_POST['user_type'],
                ':profile_pic'   => $profile_pic,
                ':status'        => $_POST['status']
            ]);

            echo json_encode(["status" => $success, "msg" => "User created successfully!"]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(["status" => false, "msg" => "Email or password already exists!"]);
            } else {
                echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
            }
        }
        exit;
    }

    // Update User
    if ($route == 'update') {
        try {
            $params = [
                ':id'            => $_POST['id'],
                ':full_name'     => $_POST['full_name'],
                ':email'         => $_POST['email'],
                ':phone'         => $_POST['phone'],
                ':gender'        => $_POST['gender'],
                ':date_of_birth' => $_POST['date_of_birth'],
                ':user_type'     => $_POST['user_type'],
                ':status'        => $_POST['status']
            ];

            $passwordPart = "";
            if (!empty($_POST['password'])) {
                $passwordPart = ", password=:password";
                $params[':password'] = $_POST['password'];
            }

            $picPart = "";
            if (!empty($_FILES['profile_pic']['name'])) {
                $profile_pic = uploadImage($_FILES['profile_pic']);
                $picPart = ", profile_pic=:profile_pic";
                $params[':profile_pic'] = $profile_pic;
            }

            $sql = "UPDATE users
                    SET full_name=:full_name,
                        email=:email
                        $passwordPart,
                        phone=:phone,
                        gender=:gender,
                        date_of_birth=:date_of_birth,
                        user_type=:user_type,
                        status=:status
                        $picPart
                    WHERE user_id=:id";

            $stm = $conn->prepare($sql);
            $success = $stm->execute($params);

            echo json_encode(["status" => $success, "msg" => "User updated successfully!"]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(["status" => false, "msg" => "Email or password already exists!"]);
            } else {
                echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
            }
        }
        exit;
    }

    // Delete User
    if ($route == 'deleteOperation') {
        try {
            $stm = $conn->prepare("DELETE FROM users WHERE user_id=:id");
            $success = $stm->execute([':id' => $_POST['id']]);
            echo json_encode(["status" => $success, "msg" => "User record deleted"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Cyan Gradient System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }

        body { background-color: #f0fafa; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.3);
            border-bottom: 4px solid rgba(255,255,255,0.1);
        }

        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background: white;
            overflow: hidden;
        }

        .btn-cyan {
            background: var(--gradient-cyan);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-cyan:hover {
            color: white;
            box-shadow: 0 8px 20px rgba(0,188,212,0.4);
            transform: translateY(-2px);
        }

        .table thead {
            background: #e0f7f9;
            color: var(--dark-cyan);
        }

        .modal-content { border-radius: 20px; border: none; }
        .modal-header {
            background: var(--gradient-cyan);
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .profile-img {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #b2ebf2;
        }

        .preview-img {
            width: 118px;
            height: 118px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #b2ebf2;
            padding: 3px;
            background: white;
        }

        .status-pill {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: .8rem;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-users-gear me-2"></i> Mental Health Users Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">System Users & Accounts</h4>
            <p class="text-muted">Manage patient, staff, nurse, doctor, and admin user accounts.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Register New User
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile</th>
                            <th>Full Name</th>
                            <th>Email / Phone</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="userForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">User Account File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">

                    <div class="row mb-3">
                        <div class="col-md-4 text-center">
                            <label class="small fw-bold d-block mb-2">Profile Picture</label>
                            <img src="uploads/default.jpg" id="preview" class="preview-img mb-3">
                            <input type="file" name="profile_pic" id="profile_pic" class="form-control shadow-sm" accept="image/*">
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="small fw-bold">Full Name</label>
                                    <input type="text" name="full_name" id="full_name" class="form-control shadow-sm" placeholder="Full name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control shadow-sm" placeholder="example@mail.com" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Password</label>
                                    <input type="password" name="password" id="password" class="form-control shadow-sm" placeholder="Password">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control shadow-sm" placeholder="+252..." required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="small fw-bold">Gender</label>
                                    <select name="gender" id="gender" class="form-select shadow-sm" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">User Type</label>
                            <select name="user_type" id="user_type" class="form-select shadow-sm" required>
                                <option value="Patient">Patient</option>
                                <option value="Staff">Staff</option>
                                <option value="Nurse">Nurse</option>
                                <option value="doctor">doctor</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Status</label>
                            <select name="status" id="status" class="form-select shadow-sm" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

// Load Main Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(u => {
            let photo = u.profile_pic ? u.profile_pic : 'default.jpg';
            let statusClass = u.status === 'Active' ? 'status-active' : 'status-inactive';
            let safeUserJson = btoa(unescape(encodeURIComponent(JSON.stringify(u))));
            let fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(u.full_name)}&background=00838f&color=fff&bold=true`;

            html += `
            <tr>
                <td>${escapeHtml(u.user_id)}</td>
                <td>
                    <img src="uploads/${escapeHtml(photo)}" class="profile-img" onerror="this.src='${fallback}'">
                </td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${escapeHtml(u.full_name)}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${escapeHtml(u.created_at)}</div>
                </td>
                <td class="text-start">
                    <div class="fw-semibold">${escapeHtml(u.email)}</div>
                    <div class="text-muted small">${escapeHtml(u.phone)}</div>
                </td>
                <td>${escapeHtml(u.gender)}</td>
                <td>${escapeHtml(u.date_of_birth)}</td>
                <td><span class="badge bg-light text-cyan border border-info px-2 py-1">${escapeHtml(u.user_type)}</span></td>
                <td><span class="status-pill ${statusClass}">${escapeHtml(u.status)}</span></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data-user="${safeUserJson}"></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${escapeHtml(u.user_id)}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Window
$('#addBtn').click(() => {
    $('#userForm')[0].reset();
    $('#id').val('');
    $('#password').prop('required', true);
    $('#preview').attr('src', 'uploads/default.jpg');
    $('#modal').modal('show');
});

// Post Request
$('#userForm').submit(function(e) {
    e.preventDefault();

    let formData = new FormData(this);
    let url = $('#id').val() ? 'update' : 'create';

    $.ajax({
        url: '?url=' + url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if(res.status) {
                Swal.fire('Success', res.msg, 'success');
                loadData();
                $('#modal').modal('hide');
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        }
    });
});

// Edit Mode Capture
$(document).on('click', '.edit', function() {
    let u = JSON.parse(decodeURIComponent(escape(atob($(this).attr('data-user')))));

    $('#id').val(u.user_id);
    $('#full_name').val(u.full_name);
    $('#email').val(u.email);
    $('#password').val('').prop('required', false);
    $('#phone').val(u.phone);
    $('#gender').val(u.gender);
    $('#date_of_birth').val(u.date_of_birth);
    $('#user_type').val(u.user_type);
    $('#status').val(u.status);
    $('#preview').attr('src', 'uploads/' + (u.profile_pic ? u.profile_pic : 'default.jpg'));
    $('#modal').modal('show');
});

// Delete Row
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this user?',
        text: "This removes the user account from the system.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete user!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, (res) => {
                if (res.status) {
                    loadData();
                    Swal.fire('Deleted!', res.msg, 'success');
                } else {
                    Swal.fire('Error', res.msg, 'error');
                }
            });
        }
    });
});

$("#profile_pic").change(function(){
    const file = this.files[0];
    if (file){
        let reader = new FileReader();
        reader.onload = function(event){
            $('#preview').attr('src', event.target.result);
        };
        reader.readAsDataURL(file);
    }
});

$(document).ready(() => {
    loadData();
});
</script>

</body>
</html>
