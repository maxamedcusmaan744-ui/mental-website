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

    // Read Appointments
    if ($route == 'display') {
        $sql = "SELECT a.*, 
                       u_p.full_name AS patient_name, 
                       u_d.full_name AS doctor_name,
                       s.available_date, s.session
                FROM appointment a
                JOIN users u_p ON a.patient_id = u_p.user_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u_d ON d.user_id = u_d.user_id
                JOIN schedules s ON a.slot_id = s.schedule_id
                ORDER BY a.created_at DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Patients Dropdown
    if ($route == 'get_patients') {
        $sql = "SELECT user_id, full_name FROM users ORDER BY full_name ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Doctors Dropdown
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

    // Get Slots Available for a Doctor
    if ($route == 'get_slots') {
        $sql = "SELECT schedule_id, available_date, session, schedule_type FROM schedules ORDER BY available_date DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Book/Create Appointment
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO appointment (patient_id, doctor_id, slot_id, patient_phone, patient_gender, reason_for_visit, status)
                    VALUES (:p_id, :d_id, :slot_id, :phone, :gender, :reason, :status)";
            $stm = $conn->prepare($sql);
            $success = $stm->execute([
                ':p_id'    => $_POST['patient_id'],
                ':d_id'    => $_POST['doctor_id'],
                ':slot_id' => $_POST['slot_id'],
                ':phone'   => $_POST['patient_phone'],
                ':gender'  => $_POST['patient_gender'],
                ':reason'  => $_POST['reason_for_visit'],
                ':status'  => $_POST['status']
            ]);
            echo json_encode(["status" => $success, "msg" => "Appointment booked successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Slot already booked or error occurred!"]);
        }
        exit;
    }

    // Update Appointment
    if ($route == 'update') {
        $sql = "UPDATE appointment 
                SET patient_id = :p_id, doctor_id = :d_id, slot_id = :slot_id, 
                    patient_phone = :phone, patient_gender = :gender, reason_for_visit = :reason, status = :status
                WHERE appointment_id = :id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'      => $_POST['id'],
            ':p_id'    => $_POST['patient_id'],
            ':d_id'    => $_POST['doctor_id'],
            ':slot_id' => $_POST['slot_id'],
            ':phone'   => $_POST['patient_phone'],
            ':gender'  => $_POST['patient_gender'],
            ':reason'  => $_POST['reason_for_visit'],
            ':status'  => $_POST['status']
        ]);
        echo json_encode(["status" => $success, "msg" => "Appointment details updated!"]);
        exit;
    }

    // Delete/Cancel Appointment
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM appointment WHERE appointment_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Appointment deleted successfully"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Appointments | Cyan System</title>
    
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

        /* Status Badges */
        .badge-pending { background-color: #ffb300; color: #fff; }
        .badge-confirmed { background-color: #4caf50; color: #fff; }
        .badge-cancelled { background-color: #f44336; color: #fff; }
        .badge-completed { background-color: #00bcd4; color: #fff; }

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
    <h3><i class="fa-solid fa-calendar-check me-2"></i> Patient Appointment Management</h3>
</nav>

<div class="container-fluid px-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Manage Appointments</h4>
            <p class="text-muted">Book, track, and manage doctor-patient consultations.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus me-2"></i> Book New Appointment
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
                            <th>Patient</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Doctor</th>
                            <th>Session / Date</th>
                            <th>Reason</th>
                            <th>Status</th>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="appointmentForm">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Select Patient</label>
                            <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Select Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Patient Phone</label>
                            <input type="text" name="patient_phone" id="patient_phone" class="form-control shadow-sm" required placeholder="e.g. +25261xxxxxx">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Patient Gender</label>
                            <select name="patient_gender" id="patient_gender" class="form-select shadow-sm" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Available Session Slot</label>
                            <select name="slot_id" id="slot_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Status</label>
                            <select name="status" id="status" class="form-select shadow-sm" required>
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Reason for Visit</label>
                        <textarea name="reason_for_visit" id="reason_for_visit" rows="3" class="form-control shadow-sm" required placeholder="Describe symptoms or reasons..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function populateDropdowns() {
    $.get('?url=get_patients', function(res) {
        let html = '<option value="">Select Patient</option>';
        res.forEach(p => { html += `<option value="${p.user_id}">${p.full_name}</option>`; });
        $('#patient_id').html(html);
    });

    $.get('?url=get_doctors', function(res) {
        let html = '<option value="">Select Doctor</option>';
        res.forEach(d => { html += `<option value="${d.doctor_id}">${d.full_name}</option>`; });
        $('#doctor_id').html(html);
    });

    $.get('?url=get_slots', function(res) {
        let html = '<option value="">Select Shift Slot</option>';
        res.forEach(s => { html += `<option value="${s.schedule_id}">${s.available_date} (${s.session} - ${s.schedule_type})</option>`; });
        $('#slot_id').html(html);
    });
}

function loadAppointments() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(a => {
            let statusBadge = `<span class="badge badge-${a.status.toLowerCase()} p-2">${a.status}</span>`;
            
            html += `
            <tr>
                <td>${a.appointment_id}</td>
                <td class="fw-bold text-dark">${a.patient_name}</td>
                <td>${a.patient_phone}</td>
                <td>${a.patient_gender}</td>
                <td class="text-primary fw-semibold">${a.doctor_name}</td>
                <td><small>${a.available_date}<br><strong>${a.session}</strong></small></td>
                <td class="text-muted text-start"><small>${a.reason_for_visit}</small></td>
                <td>${statusBadge}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(a)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${a.appointment_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="9">No appointments found</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#appointmentForm')[0].reset();
    $('#id').val('');
    $('#status').val('Pending');
    $('#modal').modal('show');
});

$('#appointmentForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadAppointments();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

$(document).on('click', '.edit', function() {
    let a = JSON.parse($(this).attr('data'));
    $('#id').val(a.appointment_id);
    $('#patient_id').val(a.patient_id);
    $('#doctor_id').val(a.doctor_id);
    $('#slot_id').val(a.slot_id);
    $('#patient_phone').val(a.patient_phone);
    $('#patient_gender').val(a.patient_gender);
    $('#status').val(a.status);
    $('#reason_for_visit').val(a.reason_for_visit);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Cancel/Delete this appointment?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, (res) => {
                loadAppointments();
                Swal.fire('Removed!', res.msg, 'success');
            });
        }
    });
});

$(document).ready(() => {
    populateDropdowns();
    loadAppointments();
});
</script>
</body>
</html>