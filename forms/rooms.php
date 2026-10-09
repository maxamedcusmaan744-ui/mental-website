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

    // Read Rooms Ledger
    if ($route == 'display') {
        $sql = "SELECT * FROM rooms ORDER BY room_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Room Entry
    if ($route == 'create') {
        try {
            // Unique check for room_number
            $checkSql = "SELECT COUNT(*) FROM rooms WHERE room_number = :room_number";
            $checkStm = $conn->prepare($checkSql);
            $checkStm->execute([':room_number' => $_POST['room_number']]);
            
            if ($checkStm->fetchColumn() > 0) {
                echo json_encode(["status" => false, "msg" => "Error: Room number already exists inside system indices."]);
                exit;
            }

            $sql = "INSERT INTO rooms (room_number, room_type, capacity, status)
                    VALUES (:room_number, :room_type, :capacity, :status)";
            $stm = $conn->prepare($sql);
            
            $success = $stm->execute([
                ':room_number' => $_POST['room_number'],
                ':room_type'   => $_POST['room_type'],
                ':capacity'    => $_POST['capacity'],
                ':status'      => $_POST['status']
            ]);
            echo json_encode(["status" => true, "msg" => "Clinical room asset successfully registered and added to the grid!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Room Entry
    if ($route == 'update') {
        try {
            // Unique check excluding current id
            $checkSql = "SELECT COUNT(*) FROM rooms WHERE room_number = :room_number AND room_id != :id";
            $checkStm = $conn->prepare($checkSql);
            $checkStm->execute([
                ':room_number' => $_POST['room_number'],
                ':id'          => $_POST['id']
            ]);
            
            if ($checkStm->fetchColumn() > 0) {
                echo json_encode(["status" => false, "msg" => "Error: Room number conflict detected with another entry."]);
                exit;
            }

            $sql = "UPDATE rooms SET 
                        room_number = :room_number, 
                        room_type = :room_type, 
                        capacity = :capacity, 
                        status = :status 
                    WHERE room_id = :id";
            $stm = $conn->prepare($sql);

            $success = $stm->execute([
                ':id'          => $_POST['id'],
                ':room_number' => $_POST['room_number'],
                ':room_type'   => $_POST['room_type'],
                ':capacity'    => $_POST['capacity'],
                ':status'      => $_POST['status']
            ]);
            echo json_encode(["status" => $success, "msg" => "Room configurations updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Room Entry
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM rooms WHERE room_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Room profile safely deleted from the resource directories."]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Room Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-door-open me-2"></i> Clinical Facilities & Room Resource Registry</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Ward & Room Asset Directory</h4>
            <p class="text-muted">Track facility layout accommodations, update structural capacities, and control real-time occupancy status indicators.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Initialize New Room
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Internal ID</th>
                            <th>Room Number Reference</th>
                            <th>Classification Type</th>
                            <th>Bed Capacity</th>
                            <th>Operational Status</th>
                            <th>System Logging Date</th>
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
            <form id="roomForm">
                <div class="modal-header">
                    <h5 class="modal-title">Clinical Asset Setup Form</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Room Identifier / Number String</label>
                            <input type="text" name="room_number" id="room_number" class="form-control shadow-sm" placeholder="e.g. WARD-302, RM-10A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Room Classification Model</label>
                            <select name="room_type" id="room_type" class="form-select shadow-sm" required>
                                <option value="General">General Ward Setup</option>
                                <option value="Private">Private Quarters Suite</option>
                                <option value="VIP">VIP Premium Lounge Suite</option>
                                <option value="Emergency">Emergency / Intensive Unit</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small fw-bold">Maximum Client / Bed Capacity</label>
                            <input type="number" name="capacity" id="capacity" min="1" class="form-control shadow-sm" placeholder="e.g. 4" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Immediate Availability Status</label>
                            <select name="status" id="status" class="form-select shadow-sm" required>
                                <option value="Available">Available for Placement</option>
                                <option value="Occupied">Occupied / Full Capacity</option>
                                <option value="Maintenance">Maintenance & Sanitation Mode</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Commit Room Configurations</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Core Rooms Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(r => {
            // Status Badges Engine
            let statusBadge = '';
            if (r.status === 'Available') {
                statusBadge = '<span class="badge bg-success-subtle text-success border border-success px-3 py-1">Available</span>';
            } else if (r.status === 'Occupied') {
                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1">Occupied</span>';
            } else {
                statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning px-3 py-1">Maintenance</span>';
            }

            // Room Type Color Mappings
            let typeIcon = 'fa-bed';
            if (r.room_type === 'VIP') typeIcon = 'fa-crown';
            if (r.room_type === 'Emergency') typeIcon = 'fa-circle-h';

            html += `
            <tr>
                <td><strong>#RM-${r.room_id}</strong></td>
                <td class="fw-bold text-dark"><code>${r.room_number}</code></td>
                <td class="text-cyan fw-bold text-start"><i class="fa-solid ${typeIcon} me-2"></i> ${r.room_type}</td>
                <td><span class="badge bg-light text-dark border px-2 py-1">${r.capacity} Occupants Max</span></td>
                <td>${statusBadge}</td>
                <td class="small text-muted">${r.created_at}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(r)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${r.room_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Modal (Create Variant Setup)
$('#addBtn').click(() => {
    $('#roomForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

// Ajax Submission Pipeline Engine
$('#roomForm').submit(function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    let url = $('#id').val() ? 'update' : 'create';
    
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Validation Error', res.msg, 'error');
        }
    });
});

// Edit Mode Active Trigger Injection
$(document).on('click', '.edit', function() {
    let r = JSON.parse($(this).attr('data'));
    $('#id').val(r.room_id);
    $('#room_number').val(r.room_number);
    $('#room_type').val(r.room_type);
    $('#capacity').val(r.capacity);
    $('#status').val(r.status);
    $('#modal').modal('show');
});

// Delete Room Asset Process Routine
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Purge this facility asset row?',
        text: "This deletes the selected room record entirely from active physical management indexes.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, clear room record!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Purged!', '', 'success');
            });
        }
    });s
});

$(document).ready(() => {
    loadData();
});
</script>

</body>
</html>