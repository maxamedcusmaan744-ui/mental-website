<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC (PHP)
// =========================================================
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

// ROUTER LOGIC
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // Read Doctors
    if ($route == 'display') {
        $sql = "SELECT d.*, u.full_name, u.email, u.phone 
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                ORDER BY d.doctor_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Active Users with type 'Doctor' who aren't assigned yet
    if ($route == 'get_dropdown_data') {
        $p_stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='doctor' AND status='Active'");
        $p_stm->execute();
        $users = $p_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["users" => $users]);
        exit;
    }

    // Create
    // Qaybta CREATE
if ($route == 'create') {
    try {
        $sql = "INSERT INTO doctors (user_id, specialization, qualification, university, graduation_year, experience_years, license_number, license_expiry_date, consultation_fee, bio)
                VALUES (:user_id, :specialization, :qualification, :university, :graduation_year, :experience_years, :license_number, :license_expiry_date, :consultation_fee, :bio)";
        $stm = $conn->prepare($sql);
        
        $stm->execute([
            ':user_id'            => $_POST['user_id'],
            ':specialization'     => $_POST['specialization'],
            ':qualification'      => $_POST['qualification'],
            ':university'         => $_POST['university'],
            ':graduation_year'    => $_POST['graduation_year'],
            ':experience_years'   => $_POST['experience_years'],
            ':license_number'     => $_POST['license_number'],
            ':license_expiry_date'=> $_POST['license_expiry_date'],
            ':consultation_fee'   => $_POST['consultation_fee'],
            ':bio'                => $_POST['bio']
        ]);
        echo json_encode(["status" => true, "msg" => "Doctor profile created successfully!"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
    }
    exit;
}

    // Update
    if ($route == 'update') {
        $sql = "UPDATE doctors SET user_id=:user_id, specialization=:specialization, 
                experience_years=:experience_years, license_number=:license_number, 
                consultation_fee=:consultation_fee, bio=:bio, qualification=:qualification, 
                university=:university, graduation_year=:graduation_year, 
                license_expiry_date=:license_expiry_date
                WHERE doctor_id=:id";
        $stm = $conn->prepare($sql);

        $bio = !empty($_POST['bio']) ? $_POST['bio'] : null;
        $experience = !empty($_POST['experience_years']) ? $_POST['experience_years'] : 0;
        $fee = !empty($_POST['consultation_fee']) ? $_POST['consultation_fee'] : 0.00;
        $qualification = !empty($_POST['qualification']) ? $_POST['qualification'] : null;
        $university = !empty($_POST['university']) ? $_POST['university'] : null;
        $graduation_year = !empty($_POST['graduation_year']) ? $_POST['graduation_year'] : null;
        $license_expiry_date = !empty($_POST['license_expiry_date']) ? $_POST['license_expiry_date'] : null;

        $success = $stm->execute([
            ':id'               => $_POST['id'],
            ':user_id'          => $_POST['user_id'],
            ':specialization'   => $_POST['specialization'],
            ':experience_years' => $experience,
            ':license_number'   => $_POST['license_number'],
            ':consultation_fee' => $fee,
            ':bio'              => $bio,
            ':qualification'    => $qualification,
            ':university'       => $university,
            ':graduation_year'  => $graduation_year,
            ':license_expiry_date' => $license_expiry_date
        ]);
        echo json_encode(["status" => $success, "msg" => "Doctor profile updated successfully!"]);
        exit;
    }

    // Delete
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM doctors WHERE doctor_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Doctor record deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management | Glass Orange System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-orange: #ff7a18;
            --dark-orange: #c85a00;
            --gradient-orange: linear-gradient(135deg, #ffb347, #ff7a18, #c85a00);
            --glass-bg: rgba(255, 255, 255, 0.55);
            --glass-border: rgba(255, 255, 255, 0.35);
        }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(160deg, #fff3e6 0%, #ffe0c2 45%, #ffcf9e 100%);
            background-attachment: fixed;
        }

        .navbar-custom {
            background: var(--gradient-orange);
            padding: 20px;
            color: white;
            box-shadow: 0 8px 25px rgba(255, 122, 24, 0.35);
            border-bottom: 4px solid rgba(255,255,255,0.15);
        }

        .glass-card {
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(200, 90, 0, 0.12);
            overflow: hidden;
        }

        .btn-orange {
            background: var(--gradient-orange);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-orange:hover {
            color: white;
            box-shadow: 0 8px 20px rgba(255, 122, 24, 0.45);
            transform: translateY(-2px);
        }

        .table thead {
            background: rgba(255, 179, 71, 0.25);
            color: var(--dark-orange);
        }

        .table tbody tr:hover {
            background: rgba(255, 179, 71, 0.12);
        }

        .badge-orange {
            background: rgba(255, 122, 24, 0.12);
            color: var(--dark-orange);
            border: 1px solid rgba(255, 122, 24, 0.35);
        }

        .modal-content {
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .modal-header {
            background: var(--gradient-orange);
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 24, 0.2);
        }

        .text-orange { color: var(--dark-orange); }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="bi bi-heart-pulse me-2"></i> Mental Health Doctors Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-orange">Doctor Profiles & Qualifications</h4>
            <p class="text-muted">Manage specialization, licenses, experience metrics, and session rates.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-orange px-4 py-2 shadow-sm" id="addBtn">
                <i class="bi bi-plus-circle me-2"></i> Register New Doctor
            </button>
        </div>
    </div>

    <div class="card glass-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Doctor Name</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>License No.</th>
                            <th>Qualification</th>
                            <th>University</th>
                            <th>Graduation Year</th>
                            <th>License Expiry Date</th>
                            <th>Fee ($)</th>
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
            <form id="doctorForm">
                <div class="modal-header">
                    <h5 class="modal-title">Doctor Profile File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Select User Account (Doctor)</label>
                            <select name="user_id" id="user_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Specialization Area</label>
                            <input type="text" name="specialization" id="specialization" class="form-control shadow-sm" placeholder="e.g. Anxiety / CBT Specialist" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Experience (Years)</label>
                            <input type="number" name="experience_years" id="experience_years" min="0" class="form-control shadow-sm" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">License Number</label>
                            <input type="text" name="license_number" id="license_number" class="form-control shadow-sm" placeholder="e.g. LIC-99821" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Consultation Fee ($)</label>
                            <input type="number" step="0.01" name="consultation_fee" id="consultation_fee" min="0" class="form-control shadow-sm" value="0.00">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Qualification</label>
                            <input type="text" name="qualification" id="qualification" class="form-control shadow-sm" placeholder="e.g. MD, PhD" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">University</label>
                            <input type="text" name="university" id="university" class="form-control shadow-sm" placeholder="e.g. Harvard Medical School" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Graduation Year</label>
                            <input type="number" name="graduation_year" id="graduation_year" class="form-control shadow-sm" placeholder="e.g. 2010" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">License Expiry Date</label>
                            <input type="date" name="license_expiry_date" id="license_expiry_date" class="form-control shadow-sm" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold">Professional Biography (Bio)</label>
                        <textarea name="bio" id="bio" class="form-control shadow-sm" rows="4" placeholder="Brief statement regarding clinical background, focus areas, and medical philosophy..."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-orange px-5">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Available User Accounts
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let u = '<option value="">-- Choose Linked User Account --</option>';
        res.users.forEach(user => {
            u += `<option value="${user.user_id}">${user.full_name}</option>`;
        });
        $('#user_id').html(u);
    });
}

// Load Main Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(d => {
            html += `
            <tr>
                <td>${d.doctor_id}</td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${d.full_name}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${d.email}</div>
                </td>
                <td><span class="badge badge-orange px-2 py-1">${d.specialization}</span></td>
                <td><span class="fw-semibold">${d.experience_years} Years</span></td>
                <td><code class="text-dark fw-bold">${d.license_number}</code></td>
                <td>${d.qualification ?? ''}</td>
                <td>${d.university ?? ''}</td>
                <td>${d.graduation_year ?? ''}</td>
                <td>${d.license_expiry_date ?? ''}</td>
                <td class="text-success fw-bold">$${parseFloat(d.consultation_fee).toFixed(2)}</td>
                <td>
                    <i class="bi bi-pencil-square text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(d)}'></i>
                    <i class="bi bi-trash text-danger del" style="cursor:pointer" data-id="${d.doctor_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Window
$('#addBtn').click(() => {
    $('#doctorForm')[0].reset();
    $('#id').val('');
    $('#user_id').prop('disabled', false);
    $('#modal').modal('show');
});

// Post Request
$('#doctorForm').submit(function(e) {
    e.preventDefault();

    $('#user_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#user_id').prop('disabled', true);
    }

    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('show');
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Mode Capture
$(document).on('click', '.edit', function() {
    let d = JSON.parse($(this).attr('data'));
    $('#id').val(d.doctor_id);
    $('#user_id').val(d.user_id).prop('disabled', true);
    $('#specialization').val(d.specialization);
    $('#experience_years').val(d.experience_years);
    $('#license_number').val(d.license_number);
    $('#consultation_fee').val(d.consultation_fee);
    $('#qualification').val(d.qualification);
    $('#university').val(d.university);
    $('#graduation_year').val(d.graduation_year);
    $('#license_expiry_date').val(d.license_expiry_date);
    $('#bio').val(d.bio ? d.bio : '');
    $('#modal').modal('show');
});

// Delete Row
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this doctor profile?',
        text: "This safely removes medical system metrics. The primary user account remains safe.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c85a00',
        confirmButtonText: 'Yes, purge profile!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Purged!', '', 'success');
            });
        }
    });
});

$(document).ready(() => {
    loadDropdownData();
    loadData();
});
</script>

</body>
</html>
