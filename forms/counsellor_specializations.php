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

    // Read Specializations
    if ($route == 'display') {
        $sql = "SELECT s.*, u.full_name AS counsellor_name 
                FROM counsellor_specializations s
                JOIN users u ON s.counsellor_id = u.user_id
                ORDER BY s.specialization_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Counsellors for Dropdown
    if ($route == 'get_counsellors') {
        $stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Counsellor' AND status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Specialization
    if ($route == 'create') {
        $sql = "INSERT INTO counsellor_specializations (counsellor_id, specialization_area, experience_years, description)
                VALUES (:c_id, :area, :exp, :desc)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':c_id' => $_POST['counsellor_id'],
            ':area' => $_POST['specialization_area'],
            ':exp'  => $_POST['experience_years'],
            ':desc' => $_POST['description']
        ]);
        echo json_encode(["status" => $success, "msg" => "Specialization added successfully!"]);
        exit;
    }

    // Update Specialization
    if ($route == 'update') {
        $sql = "UPDATE counsellor_specializations SET counsellor_id=:c_id, specialization_area=:area, 
                experience_years=:exp, description=:desc 
                WHERE specialization_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'   => $_POST['id'],
            ':c_id' => $_POST['counsellor_id'],
            ':area' => $_POST['specialization_area'],
            ':exp'  => $_POST['experience_years'],
            ':desc' => $_POST['description']
        ]);
        echo json_encode(["status" => $success, "msg" => "Specialization updated!"]);
        exit;
    }

    // Delete Specialization
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM counsellor_specializations WHERE specialization_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Specialization deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specializations | Cyan System</title>
    
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

        .exp-badge {
            background: #e0f2f1;
            color: #00796b;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .modal-content { border-radius: 20px; border: none; }
        .modal-header {
            background: var(--gradient-cyan);
            color: white;
            border-radius: 20px 20px 0 0;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-award me-2"></i> Counsellor Specialization Areas</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Expertise Management</h4>
            <p class="text-muted">Define areas of expertise and experience for each counsellor.</p>
        </div>
        <div class="col-md text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Add Expertise
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Counsellor Name</th>
                            <th>Specialization Area</th>
                            <th>Experience</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="specForm">
                <div class="modal-header">
                    <h5 class="modal-title">Expertise Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Assign to Counsellor</label>
                        <select name="counsellor_id" id="counsellor_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Area of Specialization</label>
                        <input type="text" name="specialization_area" id="specialization_area" class="form-control shadow-sm" placeholder="e.g. Depression, Career, Marriage" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Years of Experience</label>
                        <input type="number" name="experience_years" id="experience_years" class="form-control shadow-sm" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Full Description</label>
                        <textarea name="description" id="description" class="form-control shadow-sm" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Expertise</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadCounsellors() {
    $.get('?url=get_counsellors', function(res) {
        let html = '<option value="">Select Counsellor</option>';
        res.forEach(u => {
            html += `<option value="${u.user_id}">${u.full_name}</option>`;
        });
        $('#counsellor_id').html(html);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(s => {
            html += `
            <tr>
                <td>${s.specialization_id}</td>
                <td class="fw-bold text-dark">${s.counsellor_name}</td>
                <td><span class="text-primary fw-bold">${s.specialization_area}</span></td>
                <td><span class="exp-badge">${s.experience_years} Years</span></td>
                <td class="small text-muted text-truncate" style="max-width: 200px;">${s.description || 'No description'}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(s)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${s.specialization_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="6">No expertise records found</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#specForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

$('#specForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        }
    });
});

$(document).on('click', '.edit', function() {
    let s = JSON.parse($(this).attr('data'));
    $('#id').val(s.specialization_id);
    $('#counsellor_id').val(s.counsellor_id);
    $('#specialization_area').val(s.specialization_area);
    $('#experience_years').val(s.experience_years);
    $('#description').val(s.description);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this record?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Deleted!', '', 'success');
            });
        }
    });
});

$(document).ready(() => {
    loadCounsellors();
    loadData();
});
</script>
</body>
</html>