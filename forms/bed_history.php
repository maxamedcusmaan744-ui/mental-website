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

    // Read Bed History
    if ($route == 'display') {
        $sql = "SELECT bh.*, p.full_name AS patient_name, b.bed_number, r.room_number 
                FROM bed_history bh
                JOIN users p ON bh.patient_id = p.user_id
                JOIN beds b ON bh.bed_id = b.bed_id
                JOIN rooms r ON b.room_id = r.room_id
                ORDER BY bh.history_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Active Patients and Available Beds for Dropdowns
    if ($route == 'get_dropdown_data') {
        // Patients
        $p_stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Patient' AND status='Active'");
        $p_stm->execute();
        $patients = $p_stm->fetchAll(PDO::FETCH_ASSOC);

        // Beds along with their Room info
        $b_stm = $conn->prepare("SELECT b.bed_id, b.bed_number, r.room_number FROM beds b JOIN rooms r ON b.room_id = r.room_id");
        $b_stm->execute();
        $beds = $b_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["patients" => $patients, "beds" => $beds]);
        exit;
    }

    // Create
    if ($route == 'create') {
        $sql = "INSERT INTO bed_history (patient_id, bed_id, assigned_date, released_date)
                VALUES (:p_id, :b_id, :assigned, :released)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':p_id'     => $_POST['patient_id'],
            ':b_id'     => $_POST['bed_id'],
            ':assigned' => $_POST['assigned_date'],
            ':released' => !empty($_POST['released_date']) ? $_POST['released_date'] : null
        ]);
        echo json_encode(["status" => $success, "msg" => "Bed assigned successfully!"]);
        exit;
    }

    // Update
    if ($route == 'update') {
        $sql = "UPDATE bed_history SET patient_id=:p_id, bed_id=:b_id, 
                assigned_date=:assigned, released_date=:released 
                WHERE history_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'       => $_POST['id'],
            ':p_id'     => $_POST['patient_id'],
            ':b_id'     => $_POST['bed_id'],
            ':assigned' => $_POST['assigned_date'],
            ':released' => !empty($_POST['released_date']) ? $_POST['released_date'] : null
        ]);
        echo json_encode(["status" => $success, "msg" => "Record updated!"]);
        exit;
    }

    // Delete
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM bed_history WHERE history_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Record deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed History | Cyan Gradient System</title>
    
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

        .status-badge {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: bold;
        }

        .status-assigned { background: #e8f5e9; color: #4caf50; }
        .status-released { background: #eceff1; color: #607d8b; }

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
    <h3><i class="fa-solid fa-bed me-2"></i> Bed Allocation History</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Patient Bed History</h4>
            <p class="text-muted">Track and manage room and bed assignments for patients.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Assign New Bed
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>History ID</th>
                            <th>Patient Name</th>
                            <th>Room & Bed Number</th>
                            <th>Assigned Date</th>
                            <th>Released Date</th>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bedHistoryForm">
                <div class="modal-header">
                    <h5 class="modal-title">Bed Allocation Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Patient Name</label>
                        <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Select Room & Bed</label>
                        <select name="bed_id" id="bed_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Assigned Date & Time</label>
                        <input type="datetime-local" name="assigned_date" id="assigned_date" class="form-control shadow-sm" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Released Date & Time <span class="text-muted">(Optional)</span></label>
                        <input type="datetime-local" name="released_date" id="released_date" class="form-control shadow-sm">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Dropdown Options for Patients and Beds
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let p = '<option value="">Select Patient</option>';
        let b = '<option value="">Select Room & Bed</option>';
        
        res.patients.forEach(patient => {
            p += `<option value="${patient.user_id}">${patient.full_name}</option>`;
        });
        
        res.beds.forEach(bed => {
            b += `<option value="${bed.bed_id}">Room: ${bed.room_number} - Bed: ${bed.bed_number}</option>`;
        });
        
        $('#patient_id').html(p);
        $('#bed_id').html(b);
    });
}

// Load Bed History Table
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(bh => {
            let statusBadge = bh.released_date 
                ? `<span class="status-badge status-released">Released</span>` 
                : `<span class="status-badge status-assigned">Still Admitted</span>`;
                
            let releasedValue = bh.released_date ? bh.released_date : '<span class="text-muted">Not Released Yet</span>';

            html += `
            <tr>
                <td>${bh.history_id}</td>
                <td class="fw-bold text-dark">${bh.patient_name}</td>
                <td><span class="badge bg-info text-dark">Room ${bh.room_number} / Bed ${bh.bed_number}</span></td>
                <td><small>${bh.assigned_date}</small></td>
                <td><small>${releasedValue}</small></td>
                <td>${statusBadge}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(bh)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${bh.history_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Add Button Click
$('#addBtn').click(() => {
    $('#bedHistoryForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

// Form Submit (Create/Update)
$('#bedHistoryForm').submit(function(e) {
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

// Edit Button Click
$(document).on('click', '.edit', function() {
    let bh = JSON.parse($(this).attr('data'));
    $('#id').val(bh.history_id);
    $('#patient_id').val(bh.patient_id);
    $('#bed_id').val(bh.bed_id);
    $('#assigned_date').val(bh.assigned_date.replace(" ", "T"));
    $('#released_date').val(bh.released_date ? bh.released_date.replace(" ", "T") : '');
    $('#modal').modal('show');
});

// Delete Button Click
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this bed history record?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete it!'
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
    loadDropdownData();
    loadData();
});
</script>

</body>
</html>