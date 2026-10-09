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

    // Read Schedule Slots (Flattened view linking Slots -> Schedules -> Doctors -> Users)
    if ($route == 'display') {
        $sql = "SELECT ss.slot_id, ss.schedule_id, ss.slot_time, ss.is_booked, ss.booked_by,
                       s.available_date, s.session, u_doc.full_name AS doctor_name, u_patient.full_name AS patient_name
                FROM schedule_slots ss
                JOIN schedules s ON ss.schedule_id = s.schedule_id
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u_doc ON d.user_id = u_doc.user_id
                LEFT JOIN users u_patient ON ss.booked_by = u_patient.user_id
                ORDER BY s.available_date DESC, ss.slot_time ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Active Shifts/Schedules for Dropdown mapping (Derived from schedules table layout)
    if ($route == 'get_schedules') {
        $sql = "SELECT s.schedule_id, s.available_date, s.session, u.full_name AS doctor_name 
                FROM schedules s
                JOIN doctors d ON s.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.user_id
                ORDER BY s.available_date DESC, u.full_name ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Schedule Slot
    if ($route == 'create') {
        $sql = "INSERT INTO schedule_slots (schedule_id, slot_time, is_booked)
                VALUES (:schedule_id, :slot_time, :is_booked)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':schedule_id' => $_POST['schedule_id'],
            ':slot_time'   => $_POST['slot_time'],
            ':is_booked'   => isset($_POST['is_booked']) ? (int)$_POST['is_booked'] : 0
        ]);
        echo json_encode(["status" => $success, "msg" => "Time slot added successfully!"]);
        exit;
    }

    // Update Schedule Slot
    if ($route == 'update') {
        $sql = "UPDATE schedule_slots 
                SET schedule_id = :schedule_id, slot_time = :slot_time, is_booked = :is_booked 
                WHERE slot_id = :id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'          => $_POST['id'],
            ':schedule_id' => $_POST['schedule_id'],
            ':slot_time'   => $_POST['slot_time'],
            ':is_booked'   => (int)$_POST['is_booked']
        ]);
        echo json_encode(["status" => $success, "msg" => "Time slot updated successfully!"]);
        exit;
    }

    // Delete Schedule Slot
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM schedule_slots WHERE slot_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Time slot deleted successfully!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Availability Slots | Cyan System</title>
    
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

        .badge-available { background-color: #2ec4b6; color: #fff; }
        .badge-booked { background-color: #e63946; color: #fff; }
        
        /* Session Shift Badges */
        .badge-morning { background-color: #ffb300; color: #fff; }
        .badge-afternoon { background-color: #29b6f6; color: #fff; }
        .badge-evening { background-color: #5c6bc0; color: #fff; }

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
    <h3><i class="fa-solid fa-clock me-2"></i> Doctor Hourly Availability Slots</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Manage Booking Slots</h4>
            <p class="text-muted">Configure fine-grained appointment times for scheduled shifts.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-calendar-plus me-2"></i> Add Appointment Slot
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Slot #</th>
                            <th>Doctor</th>
                            <th>Available Date</th>
                            <th>Shift Session</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th>Reserved By</th>
                            <th>Actions</th>
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
            <form id="slotForm">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Slot Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Select Active Doctor Shift</label>
                        <select name="schedule_id" id="schedule_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Slot Time</label>
                        <input type="time" name="slot_time" id="slot_time" class="form-control shadow-sm" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Booking Status</label>
                        <select name="is_booked" id="is_booked" class="form-select shadow-sm" required>
                            <option value="0">Available</option>
                            <option value="1">Booked</option>
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
function loadSchedulesDropdown() {
    $.get('?url=get_schedules', function(res) {
        let html = '<option value="">Select an Active Shift</option>';
        res.forEach(s => {
            html += `<option value="${s.schedule_id}">${s.doctor_name} - ${s.available_date} (${s.session})</option>`;
        });
        $('#schedule_id').html(html);
    });
}

function formatTime(timeString) {
    return timeString.substring(0, 5);
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(s => {
            // 1. Render Status Badge
            let statusBadge = parseInt(s.is_booked) 
                ? '<span class="badge badge-booked p-2"><i class="fa-solid fa-lock me-1"></i> Booked</span>' 
                : '<span class="badge badge-available p-2"><i class="fa-solid fa-lock-open me-1"></i> Available</span>';
            
            // 2. Render Session Badge based on the ENUM data
            let sessionBadge = '';
            if (s.session === 'Morning') {
                sessionBadge = '<span class="badge badge-morning p-2">Morning</span>';
            } else if (s.session === 'Afternoon') {
                sessionBadge = '<span class="badge badge-afternoon p-2">Afternoon</span>';
            } else if (s.session === 'Evening') {
                sessionBadge = '<span class="badge badge-evening p-2">Evening</span>';
            }

            let patientName = s.patient_name ? s.patient_name : '<span class="text-muted italic">-</span>';
            
            html += `
            <tr>
                <td>${s.slot_id}</td>
                <td class="fw-bold text-dark">${s.doctor_name}</td>
                <td>${s.available_date}</td>
                <td>${sessionBadge}</td>
                <td class="text-primary fw-bold">${formatTime(s.slot_time)}</td>
                <td>${statusBadge}</td>
                <td>${patientName}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(s)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${s.slot_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="8">No appointment time slots found</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#slotForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

$('#slotForm').submit(function(e) {
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
    $('#id').val(s.slot_id);
    $('#schedule_id').val(s.schedule_id);
    $('#slot_time').val(formatTime(s.slot_time));
    $('#is_booked').val(s.is_booked);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this time slot?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, (res) => {
                loadData();
                Swal.fire('Deleted!', res.msg, 'success');
            });
        }
    });
});

$(document).ready(() => {
    loadSchedulesDropdown();
    loadData();
});
</script>
</body>
</html>