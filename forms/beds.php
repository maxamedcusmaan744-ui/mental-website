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

    // Read Beds (Matches columns exactly)
    if ($route == 'display') {
        $sql = "SELECT b.*, r.room_number, r.room_type 
                FROM beds b
                JOIN rooms r ON b.room_id = r.room_id
                ORDER BY b.bed_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Rooms for Dropdown
    if ($route == 'get_dropdown_data') {
        $r_stm = $conn->prepare("SELECT room_id, room_number, room_type FROM rooms");
        $r_stm->execute();
        $rooms = $r_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["rooms" => $rooms]);
        exit;
    }

    // Create Bed Record
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO beds (room_id, bed_number, bed_type, status)
                    VALUES (:room_id, :bed_number, :bed_type, :status)";
            $stm = $conn->prepare($sql);
            
            $bed_type = !empty($_POST['bed_type']) ? $_POST['bed_type'] : null;
            $status = !empty($_POST['status']) ? $_POST['status'] : 'Available';

            $success = $stm->execute([
                ':room_id'    => $_POST['room_id'],
                ':bed_number' => $_POST['bed_number'],
                ':bed_type'   => $bed_type,
                ':status'     => $status
            ]);
            echo json_encode(["status" => $success, "msg" => "New bed structural unit registered successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Bed Record
    if ($route == 'update') {
        try {
            $sql = "UPDATE beds SET room_id=:room_id, bed_number=:bed_number, bed_type=:bed_type, status=:status 
                    WHERE bed_id=:id";
            $stm = $conn->prepare($sql);

            $bed_type = !empty($_POST['bed_type']) ? $_POST['bed_type'] : null;
            $status = !empty($_POST['status']) ? $_POST['status'] : 'Available';

            $success = $stm->execute([
                ':id'         => $_POST['id'],
                ':room_id'    => $_POST['room_id'],
                ':bed_number' => $_POST['bed_number'],
                ':bed_type'   => $bed_type,
                ':status'     => $status
            ]);
            echo json_encode(["status" => $success, "msg" => "Bed configuration matrix updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Bed Record
    if ($route == 'deleteOperation') {
        try {
            $stm = $conn->prepare("DELETE FROM beds WHERE bed_id=:id");
            $success = $stm->execute([':id' => $_POST['id']]);
            echo json_encode(["status" => $success, "msg" => "Bed asset successfully removed from database ledger."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: Integrity constraint violation or database failure."]);
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
    <title>Bed Asset Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-bed me-2"></i> Hospital Ward Bed Asset Directory</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Ward Bed Registry Matrix</h4>
            <p class="text-muted">Catalog physical beds, select electrical classifications, and maintain real-time availability states.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Provision New Bed Unit
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Bed ID</th>
                            <th>Associated Room Info</th>
                            <th>Bed Label / Number</th>
                            <th>Classification Type</th>
                            <th>Current Status</th>
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
            <form id="bedForm">
                <div class="modal-header">
                    <h5 class="modal-title">Bed Structural Configuration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Target Structural Room</label>
                        <select name="room_id" id="room_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Physical Bed Identifier / Label</label>
                        <input type="text" name="bed_number" id="bed_number" class="form-control shadow-sm" placeholder="e.g. Bed-A1, BD-104" maxlength="20" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Bed Structural Category</label>
                        <select name="bed_type" id="bed_type" class="form-select shadow-sm">
                            <option value="">-- Optional Type Specification --</option>
                            <option value="Manual Gatch Bed">Manual Gatch Bed</option>
                            <option value="Semi-Electric Ward Bed">Semi-Electric Ward Bed</option>
                            <option value="Full-Electric Care Bed">Full-Electric Care Bed</option>
                            <option value="ICU Heavy-Duty Bed">ICU Heavy-Duty Bed</option>
                            <option value="Pediatric Crib">Pediatric Crib</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Current Allocation Status</label>
                        <select name="status" id="status" class="form-select shadow-sm" required>
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Bed Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Relational Rooms Array Into Dropdown
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let r = '<option value="">-- Select Target Room Assignment --</option>';
        res.rooms.forEach(room => {
            r += `<option value="${room.room_id}">Room #${room.room_number} (${room.room_type})</option>`;
        });
        $('#room_id').html(r);
    });
}

// Load Core Data Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(b => {
            let typeDisplay = b.bed_type ? b.bed_type : '<em class="text-muted">Standard</em>';
            
            // Contextual color badges map for the ENUM status values
            let statusBadge = '';
            if(b.status === 'Available')   statusBadge = 'bg-success text-white';
            if(b.status === 'Occupied')    statusBadge = 'bg-danger text-white';
            if(b.status === 'Reserved')    statusBadge = 'bg-warning text-dark';
            if(b.status === 'Maintenance') statusBadge = 'bg-secondary text-white';

            html += `
            <tr>
                <td><strong>#BD-${b.bed_id}</strong></td>
                <td><span class="fw-bold text-dark">Room ${b.room_number}</span> <br><small class="text-muted" style="font-size:0.75rem">${b.room_type}</small></td>
                <td><mark class="px-3 py-1 border rounded fw-bold bg-light text-cyan border-info">${b.bed_number}</mark></td>
                <td class="fw-semibold text-dark">${typeDisplay}</td>
                <td><span class="badge ${statusBadge} px-3 py-2 rounded-pill shadow-sm" style="font-size:0.8rem">${b.status}</span></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(b)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${b.bed_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Dialog Container (Create Execution Mode)
$('#addBtn').click(() => {
    $('#bedForm')[0].reset();
    $('#id').val('');
    $('#status').val('Available'); // Default ENUM mapping initialization
    $('#modal').modal('show');
});

// Post Request Transaction Processor
$('#bedForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Open Dialog Container (Edit Extraction Mode)
$(document).on('click', '.edit', function() {
    let b = JSON.parse($(this).attr('data'));
    $('#id').val(b.bed_id);
    $('#room_id').val(b.room_id);
    $('#bed_number').val(b.bed_number);
    $('#bed_type').val(b.bed_type ? b.bed_type : '');
    $('#status').val(b.status);
    $('#modal').modal('show');
});

// Delete Execution Pipeline
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Remove this bed structure?',
        text: "Make sure no critical active clinical admission folders are referencing this bed index.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete structural unit!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, (res) => {
                if(res.status) {
                    loadData();
                    Swal.fire('Deleted!', res.msg, 'success');
                } else {
                    Swal.fire('Restricted', res.msg, 'error');
                }
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