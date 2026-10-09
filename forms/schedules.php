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

    // Read Schedules
    if ($route == 'display') {
        $sql = "SELECT s.*, u.full_name AS doctor_name 
                FROM schedules s
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.user_id
                ORDER BY s.available_date DESC, s.session ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Doctors for Dropdown
    if ($route == 'get_doctors') {
        $sql = "SELECT d.doctor_id, u.full_name 
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                ORDER BY u.full_name ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Schedule
    if ($route == 'create') {
        $sql = "INSERT INTO schedules (doctor_id, available_date, session, schedule_type)
                VALUES (:d_id, :date, :session, :type)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':d_id'     => $_POST['doctor_id'],
            ':date'     => $_POST['available_date'],
            ':session'  => $_POST['session'],
            ':type'     => $_POST['schedule_type'] // TIIRKA CUSUB
        ]);
        echo json_encode(["status" => $success, "msg" => "Schedule slot added successfully!"]);
        exit;
    }

    // Update Schedule
    if ($route == 'update') {
        $sql = "UPDATE schedules 
                SET doctor_id = :d_id, available_date = :date, session = :session, schedule_type = :type 
                WHERE schedule_id = :id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'       => $_POST['id'],
            ':d_id'     => $_POST['doctor_id'],
            ':date'     => $_POST['available_date'],
            ':session'  => $_POST['session'],
            ':type'     => $_POST['schedule_type'] // TIIRKA CUSUB
        ]);
        echo json_encode(["status" => $success, "msg" => "Schedule slot updated!"]);
        exit;
    }

    // Delete Schedule
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM schedules WHERE schedule_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Schedule slot deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedules | Cyan System</title>
    
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

        /* Badges */
        .badge-morning { background-color: #ffb300; color: #fff; }
        .badge-afternoon { background-color: #29b6f6; color: #fff; }
        .badge-evening { background-color: #5c6bc0; color: #fff; }
        .badge-hospital { background-color: #009688; color: #fff; }
        .badge-video { background-color: #e91e63; color: #fff; }

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
    <h3><i class="fa-solid fa-clock me-2"></i> Doctor Availability Schedules</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Manage Schedules</h4>
            <p class="text-muted">Set and monitor doctor session shifts.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-calendar-plus me-2"></i> Add New Slot
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
                            <th>Doctor</th>
                            <th>Available Date</th>
                            <th>Session Shift</th>
                            <th>Schedule Type</th> <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="scheduleForm">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Slot Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Select Doctor</label>
                        <select name="doctor_id" id="doctor_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Available Date</label>
                        <input type="date" name="available_date" id="available_date" class="form-control shadow-sm" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Session Shift</label>
                        <select name="session" id="session" class="form-select shadow-sm" required>
                            <option value="">Select Shift</option>
                            <option value="Morning">Morning</option>
                            <option value="Afternoon">Afternoon</option>
                            <option value="Evening">Evening</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Schedule Type</label>
                        <select name="schedule_type" id="schedule_type" class="form-select shadow-sm" required>
                            <option value="Hospital">Hospital</option>
                            <option value="VideoCall">VideoCall</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadDoctors() {
    $.get('?url=get_doctors', function(res) {
        let html = '<option value="">Select Doctor</option>';
        res.forEach(d => {
            html += `<option value="${d.doctor_id}">${d.full_name}</option>`;
        });
        $('#doctor_id').html(html);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(s => {
            let sessionBadge = '';
            if (s.session === 'Morning') {
                sessionBadge = '<span class="badge badge-morning p-2">Morning</span>';
            } else if (s.session === 'Afternoon') {
                sessionBadge = '<span class="badge badge-afternoon p-2">Afternoon</span>';
            } else if (s.session === 'Evening') {
                sessionBadge = '<span class="badge badge-evening p-2">Evening</span>';
            }
            
            // BADGE-KA CUSUB EE SCHEDULE TYPE
            let typeBadge = s.schedule_type === 'VideoCall' 
                ? '<span class="badge badge-video p-2"><i class="fa-solid fa-video me-1"></i> VideoCall</span>' 
                : '<span class="badge badge-hospital p-2"><i class="fa-solid fa-hospital me-1"></i> Hospital</span>';
            
            html += `
            <tr>
                <td>${s.schedule_id}</td>
                <td class="fw-bold text-dark">${s.doctor_name}</td>
                <td>${s.available_date}</td>
                <td>${sessionBadge}</td>
                <td>${typeBadge}</td> <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(s)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${s.schedule_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="6">No schedules found</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#scheduleForm')[0].reset();
    $('#id').val('');
    $('#schedule_type').val('Hospital'); // Default value
    $('#modal').modal('show');
});

$('#scheduleForm').submit(function(e) {
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
    $('#id').val(s.schedule_id);
    $('#doctor_id').val(s.doctor_id);
    $('#available_date').val(s.available_date);
    $('#session').val(s.session);
    $('#schedule_type').val(s.schedule_type); // SOO QABASHADA XOGTA CUSUB MARKA LA EDIT GARAYNAYO
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this schedule slot?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete!'
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
    loadDoctors();
    loadData();
});
</script>
</body>
</html>