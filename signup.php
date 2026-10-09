<?php
// ================= DATABASE CONNECTION =================
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
            if(isset($_GET['url']) && $_GET['url'] == 'create') {
                header('Content-Type: application/json');
                die(json_encode(["status"=>false,"msg"=>"DB connection failed"]));
            }
        }
    }
}

// ================= ACTION LOGIC =================
if(isset($_GET['url']) && $_GET['url'] == 'create') {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    function uploadImage($file) {
        $target_dir = "forms/uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        $filename = time() . "_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($file["tmp_name"], $target_file)) return $filename;
        return "default.jpg";
    }

    try {
        $pic = "default.jpg";
        if(!empty($_FILES['profile_pic']['name'])) $pic = uploadImage($_FILES['profile_pic']);
        
        $sql = "INSERT INTO users (full_name, email, password, phone, gender, date_of_birth, user_type, profile_pic, status)
                VALUES (:name, :email, :password, :phone, :gender, :dob, :type, :pic, :status)";
        
        $stm = $conn->prepare($sql);
        $res = $stm->execute([
            ':name'     => $_POST['name'],
            ':email'    => $_POST['email'],
            ':password' => $_POST['password'],
            ':phone'    => $_POST['phone'],
            ':gender'   => $_POST['gender'],
            ':dob'      => $_POST['date_of_birth'],
            ':type'     => $_POST['user_type'],
            ':pic'      => $pic,
            ':status'   => $_POST['status']
        ]);

        echo json_encode(["status"=>true,"msg"=>"User registered successfully!"]);
    } catch (Exception $e) {
        echo json_encode(["status"=>false,"msg"=>"Error: " . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration | MindCare Cyan</title>
    
    <!-- Fonts & Frameworks -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-cyan: #00e5ff;
            --secondary-cyan: #00b8d4;
            --dark-cyan: #00838f;
            --text-dark: #0f172a;
            --glass: rgba(255, 255, 255, 0.92);
        }

        body { 
            /* Background Image with Cyan Overlay */
            background: linear-gradient(rgba(0, 131, 143, 0.45), rgba(15, 23, 42, 0.75)), 
                        url('https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=2029&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            display: flex;
            align-items: center; 
            justify-content: center;
            padding: 40px 0;
            margin: 0;
        }

        .registration-card {
            border: none;
            border-radius: 35px;
            background: var(--glass);
            backdrop-filter: blur(15px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255,255,255,0.3);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-label { font-weight: 700; color: #475569; font-size: 0.75rem; letter-spacing: 0.5px; text-transform: uppercase; }
        .icon-box { color: var(--secondary-cyan); width: 25px; }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 12px 15px;
            background-color: #f8fafc;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-cyan);
            box-shadow: 0 0 0 4px rgba(0, 229, 255, 0.1);
            background-color: #fff;
        }

        .preview-img {
            width: 130px; height: 130px;
            object-fit: cover; border-radius: 40px;
            border: 5px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .btn-premium {
            background: linear-gradient(135deg, var(--secondary-cyan) 0%, var(--primary-cyan) 100%);
            color: white; border: none;
            border-radius: 15px; padding: 16px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(0, 184, 212, 0.3);
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(0, 184, 212, 0.4);
            filter: brightness(1.1);
            color: white;
        }

        .signin-badge {
    background: rgba(0, 229, 255, 0.1);
    color: var(--secondary-cyan);
    padding: 8px 20px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 229, 255, 0.2);
    display: inline-flex;
    align-items: center;
}

.signin-badge:hover {
    background: var(--secondary-cyan);
    color: white;
    transform: translateX(-5px); /* Waxay u dhaqaaqaysaa dhanka bidix xoogaa */
    box-shadow: 0 5px 15px rgba(0, 184, 212, 0.3);
}

.signin-badge i {
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.signin-badge:hover i {
    transform: scale(1.2);
}
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-7">
            <div class="card registration-card">
                <div class="card-body p-4 p-md-5">
                    <form id="userForm">
                        
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="uploads/default.jpg" id="preview" class="preview-img" onerror="this.src='https://ui-avatars.com/api/?name=User&background=00e5ff&color=fff'">
                                <button type="button" class="btn btn-sm btn-info position-absolute bottom-0 end-0 rounded-circle text-white shadow" onclick="$('#profile_pic').click()">
                                    <i class="fa-solid fa-camera"></i>
                                </button>
                                <input type="file" name="profile_pic" id="profile_pic" hidden accept="image/*">
                            </div>
                            <h4 class="mt-3 fw-bold">MindCare Registration</h4>
                            <p class="text-muted small">Ku soo dhawaaw madasha caafimaadka maskaxda</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label"><i class="fa-solid fa-user icon-box"></i> Magaca oo Dhammaystiran</label>
                                <input class="form-control" name="name" placeholder="Ahmed Mohamed Ali" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><i class="fa-solid fa-envelope icon-box"></i> Email-ka</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><i class="fa-solid fa-lock icon-box"></i> Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><i class="fa-solid fa-phone icon-box"></i> Taleefanka</label>
                                <input class="form-control" name="phone">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><i class="fa-solid fa-venus-mars icon-box"></i> Jinsiga</label>
                                <select name="gender" class="form-select">
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="fa-solid fa-calendar icon-box"></i> Taariikhda Dhalashada</label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="fa-solid fa-user-tag icon-box"></i> Nooca User-ka</label>
                                <select name="user_type" class="form-select">
                                    <option>Patient</option>
                                    <!-- <option>Staff</option>
                                    <option>Counsellor</option> -->
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><i class="fa-solid fa-circle-check icon-box"></i> Status</label>
                                <select name="status" class="form-select">
                                    <option>Active</option>
                                    <!-- <option>Inactive</option> -->
                                </select>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-premium w-100">
                                    Samey Koonto Cusub <i class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="d-flex justify-content-end align-items-center mb-4">
    <span class="text-muted small me-3 fw-semibold">Already have an account?</span>
    <a href="sign_in.php" class="signin-badge">
        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Sign In
    </a>
</div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#userForm').submit(function(e){
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Loading...');

        $.ajax({
            url: window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'url=create',
            type: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(res){
                btn.prop('disabled', false).html('Samey Koonto Cusub <i class="fa-solid fa-arrow-right ms-2"></i>');
                if(res.status){
                    Swal.fire({ icon: 'success', title: 'Guul!', text: res.msg, confirmButtonColor: '#00b8d4' });
                    $('#userForm')[0].reset();
                    $('#preview').attr('src', 'uploads/default.jpg');
                } else {
                    Swal.fire({ icon: 'error', title: 'Khalad!', text: res.msg });
                }
            },
            error: function() {
                btn.prop('disabled', false).html('Samey Koonto Cusub');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Server connection failed!' });
            }
        });
    });

    $("#profile_pic").change(function(){
        const file = this.files[0];
        if (file){
            let reader = new FileReader();
            reader.onload = function(e){ $('#preview').attr('src', e.target.result); }
            reader.readAsDataURL(file);
        }
    });
});
</script>

</body>
</html>