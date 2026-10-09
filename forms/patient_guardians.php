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

    // Read Guardians Ledger (Matches columns exactly)
    if ($route == 'display') {
        $sql = "SELECT g.guardian_id, g.admission_id, g.guardian_name, g.relationship, g.phone, g.address,
                       a.admission_reason, u.full_name AS patient_name, u.email AS patient_email 
                FROM patient_guardians g
                JOIN patient_admissions a ON g.admission_id = a.admission_id
                JOIN users u ON a.patient_id = u.user_id
                ORDER BY g.guardian_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Fetch Active Admissions for Dropdown Relationships
    if ($route == 'get_dropdown_data') {
        $sql = "SELECT a.admission_id, a.admission_reason, DATE(a.admission_date) as adm_date, u.full_name 
                FROM patient_admissions a
                JOIN users u ON a.patient_id = u.user_id 
                WHERE a.status='Admitted' AND u.status='Active' 
                ORDER BY u.full_name ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Guardian Entry
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO patient_guardians (admission_id, guardian_name, relationship, phone, address)
                    VALUES (:admission_id, :guardian_name, :relationship, :phone, :address)";
            $stm = $conn->prepare($sql);
            
            $address = !empty($_POST['address']) ? $_POST['address'] : null;

            $success = $stm->execute([
                ':admission_id'   => $_POST['admission_id'],
                ':guardian_name'  => $_POST['guardian_name'],
                ':relationship'   => $_POST['relationship'],
                ':phone'          => $_POST['phone'],
                ':address'        => $address
            ]);
            echo json_encode(["status" => true, "msg" => "Emergency guardian profile successfully registered!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Guardian Entry
    if ($route == 'update') {
        try {
            $sql = "UPDATE patient_guardians SET 
                        admission_id = :admission_id, 
                        guardian_name = :guardian_name, 
                        relationship = :relationship, 
                        phone = :phone, 
                        address = :address 
                    WHERE guardian_id = :id";
            $stm = $conn->prepare($sql);

            $address = !empty($_POST['address']) ? $_POST['address'] : null;

            $success = $stm->execute([
                ':id'             => $_POST['id'],
                ':admission_id'   => $_POST['admission_id'],
                ':guardian_name'  => $_POST['guardian_name'],
                ':relationship'   => $_POST['relationship'],
                ':phone'          => $_POST['phone'],
                ':address'        => $address
            ]);
            echo json_encode(["status" => $success, "msg" => "Guardian contact details updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Guardian Entry
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM patient_guardians WHERE guardian_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Guardian contact severed and removed from directory."]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Guardian Management | Cyan Gradient System</title>
    
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
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-users-viewfinder me-2"></i> Patient Guardians & Kin Registry</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Emergency Contact Directory</h4>
            <p class="text-muted">Document trusted guardians, catalog familial relationships, and configure critical communication channels based on active admissions.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Register New Guardian
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Guardian ID</th>
                            <th>Patient / Admission Record</th>
                            <th>Guardian Name</th>
                            <th>Relationship</th>
                            <th>Phone Contact</th>
                            <th>Residential Address</th>
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
            <form id="guardianForm">
                <div class="modal-header">
                    <h5 class="modal-title">Guardian Profile Intake</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold">Associated Admission Record</label>
                            <select name="admission_id" id="admission_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Full Name of Guardian</label>
                            <input type="text" name="guardian_name" id="guardian_name" class="form-control shadow-sm" placeholder="e.g. Eleanor Vance" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Relationship Status to Patient</label>
                            <input type="text" name="relationship" id="relationship" class="form-control shadow-sm" placeholder="e.g. Parent, Spouse, Legal Guardian" maxlength="100" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Primary Telephone / Mobile Line</label>
                            <input type="tel" name="phone" id="phone" class="form-control shadow-sm" placeholder="e.g. 555-019-2834" maxlength="20" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Residential Address</label>
                            <input type="text" name="address" id="address" class="form-control shadow-sm" placeholder="e.g. 742 Evergreen Terrace" maxlength="255">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Guardian Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Populates Active Admissions List inside the Profile Form
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let options = '<option value="">-- Choose Admission Chart Reference --</option>';
        res.forEach(a => {
            options += `<option value="${a.admission_id}">${a.full_name} (Reason: ${a.admission_reason} - ${a.adm_date})</option>`;
        });
        $('#admission_id').html(options);
    });
}

// Load Core Guardians Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(g => {
            let addressDisplay = g.address ? g.address : '<em class="text-muted">No address provided</em>';
            
            html += `
            <tr>
                <td><strong>#GRD-${g.guardian_id}</strong></td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${g.patient_name}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${g.patient_email} | <span class="badge bg-secondary text-white">Reason: ${g.admission_reason}</span></div>
                </td>
                <td class="fw-bold text-cyan">${g.guardian_name}</td>
                <td><span class="badge bg-light text-dark border border-secondary px-2 py-1">${g.relationship}</span></td>
                <td><code>${g.phone}</code></td>
                <td class="text-start small text-truncate" style="max-width: 220px;" title="${g.address || ''}">${addressDisplay}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(g)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${g.guardian_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Modal (Create Profile Configuration)
$('#addBtn').click(() => {
    $('#guardianForm')[0].reset();
    $('#id').val('');
    $('#admission_id').prop('disabled', false);
    $('#modal').modal('show');
});

// Ajax Submission Management Engine
$('#guardianForm').submit(function(e) {
    e.preventDefault();
    
    // Temporarily enable field so serialize() safely reads dropdown data
    $('#admission_id').prop('disabled', false);
    let formData = $(this).serialize();
    
    // Re-lock field if updating an existing record
    if($('#id').val()) {
        $('#admission_id').prop('disabled', true); 
    }

    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Mode Capture & Form Extraction
$(document).on('click', '.edit', function() {
    let g = JSON.parse($(this).attr('data'));
    $('#id').val(g.guardian_id);
    $('#admission_id').val(g.admission_id).prop('disabled', true); 
    $('#guardian_name').val(g.guardian_name);
    $('#relationship').val(g.relationship);
    $('#phone').val(g.phone);
    $('#address').val(g.address ? g.address : '');
    $('#modal').modal('show');
});

// Delete Guardian Actions Lifecycle
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Sever this guardian relationship?',
        text: "This removes the immediate emergency family contact folder from our tracking indexes.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, remove representative!'
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