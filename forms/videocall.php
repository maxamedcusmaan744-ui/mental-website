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

    // Display / Read Video Meetings - Miiska 'appointment' oo aan s lahayn
    if ($route == 'display') {
        $sql = "SELECT vm.*, 
                       u_p.full_name AS patient_name, 
                       u_d.full_name AS doctor_name
                FROM video_meetings vm
                JOIN appointment a ON vm.appointment_id = a.appointment_id
                JOIN users u_p ON a.patient_id = u_p.user_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u_d ON d.user_id = u_d.user_id
                ORDER BY vm.created_at DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Appointments for Video Calls - Lagu xiriiriyey schedule_slots (slot_id)
    if ($route == 'get_appointments') {
        // dynamic query-gan wuxuu soo jiidayaa inta ballan oo ka samaysan miiska appointment
        $sql = "SELECT a.appointment_id, 
                       u_p.full_name AS patient_name, 
                       u_d.full_name AS doctor_name, 
                       '2026-07-03' AS available_date 
                FROM appointment a
                JOIN users u_p ON a.patient_id = u_p.user_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u_d ON d.user_id = u_d.user_id
                JOIN schedule_slots ss ON a.slot_id = ss.slot_id
                ORDER BY a.appointment_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Video Meeting & Auto Notification
    if ($route == 'create') {
        try {
            $conn->beginTransaction();

            // 1. Geli Kulanka Video-ga miiskiisa
            $sqlMeeting = "INSERT INTO video_meetings (appointment_id, meeting_link, meeting_status, started_at, ended_at)
                           VALUES (:app_id, :link, :status, :start, :end)";
            $stmMeeting = $conn->prepare($sqlMeeting);
            $stmMeeting->execute([
                ':app_id' => $_POST['appointment_id'],
                ':link'   => $_POST['meeting_link'],
                ':status' => $_POST['meeting_status'],
                ':start'  => !empty($_POST['started_at']) ? $_POST['started_at'] : null,
                ':end'    => !empty($_POST['ended_at']) ? $_POST['ended_at'] : null
            ]);

            // 2. Soo qaad Magacyada Bukaanka iyo Dhakhtarka
            $sqlDetails = "SELECT u_p.full_name AS patient_name, u_d.full_name AS doctor_name
                           FROM appointment a
                           JOIN users u_p ON a.patient_id = u_p.user_id
                           JOIN doctors d ON a.doctor_id = d.doctor_id
                           JOIN users u_d ON d.user_id = u_d.user_id
                           WHERE a.appointment_id = :app_id";
            $stmDetails = $conn->prepare($sqlDetails);
            $stmDetails->execute([':app_id' => $_POST['appointment_id']]);
            $details = $stmDetails->fetch(PDO::FETCH_ASSOC);

            $patient = $details['patient_name'] ?? 'Patient';
            $doctor = $details['doctor_name'] ?? 'Doctor';
            $meetingLink = $_POST['meeting_link'];

            $notificationMessage = "New Video Consultation Link! Hello {$patient} and Dr. {$doctor}, your online session meeting link is ready. Link: {$meetingLink}";

            // 3. Ogeysiis otomaatig ah
            $sqlNotify = "INSERT INTO notifications (appointment_id, message, status) VALUES (:app_id, :msg, 'Sent')";
            $stmNotify = $conn->prepare($sqlNotify);
            $stmNotify->execute([
                ':app_id' => $_POST['appointment_id'],
                ':msg'    => $notificationMessage
            ]);

            $conn->commit();
            echo json_encode(["status" => true, "msg" => "Meeting created and Notification sent automatically!"]);

        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Database Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Video Meeting
    if ($route == 'update') {
        $sql = "UPDATE video_meetings 
                SET appointment_id = :app_id, meeting_link = :link, meeting_status = :status, 
                    started_at = :start, ended_at = :end 
                WHERE meeting_id = :id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'     => $_POST['id'],
            ':app_id' => $_POST['appointment_id'],
            ':link'   => $_POST['meeting_link'],
            ':status' => $_POST['meeting_status'],
            ':start'  => !empty($_POST['started_at']) ? $_POST['started_at'] : null,
            ':end'    => !empty($_POST['ended_at']) ? $_POST['ended_at'] : null
        ]);
        echo json_encode(["status" => $success, "msg" => "Meeting details updated!"]);
        exit;
    }

    // Delete Video Meeting
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM video_meetings WHERE meeting_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Meeting link deleted successfully"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Meetings | Cyan System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        :root {
            --cyan-start: #00e5ff;
            --cyan-mid: #0097a7;
            --cyan-dark: #006064;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.4);
        }
        body { background: linear-gradient(135deg, #f0fafd 0%, #e0f7fa 100%); min-height: 100vh; color: #263238; padding-bottom: 50px; }
        .navbar-custom { background: linear-gradient(135deg, rgba(0, 229, 255, 0.9), rgba(0, 151, 167, 0.9)); padding: 24px; color: white; border-bottom: 1px solid var(--glass-border); backdrop-filter: blur(10px); box-shadow: 0 8px 32px 0 rgba(0, 151, 167, 0.2); border-radius: 0 0 24px 24px; margin-bottom: 40px; }
        .navbar-custom h3 { font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        .main-card { border: 1px solid var(--glass-border); border-radius: 20px; box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.04); background: var(--glass-bg); backdrop-filter: blur(12px); overflow: hidden; padding: 10px; }
        .btn-cyan { background: linear-gradient(135deg, var(--cyan-start), var(--cyan-mid)); color: white; border: none; border-radius: 12px; font-weight: 600; padding: 12px 24px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 151, 167, 0.3); }
        .btn-cyan:hover { color: white; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 151, 167, 0.4); }
        .table thead { background: rgba(0, 188, 212, 0.1); }
        .table th { color: var(--cyan-dark); font-weight: 600; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.5px; padding: 16px; border-bottom: 2px solid rgba(0, 151, 167, 0.1); }
        .table td { padding: 16px; font-size: 0.9rem; color: #455a64; border-bottom: 1px solid rgba(0, 0, 0, 0.03); }
        .status-badge { padding: 6px 14px; border-radius: 30px; font-size: 0.78rem; font-weight: 600; display: inline-block; }
        .badge-scheduled { background: rgba(92, 107, 192, 0.15); color: #3f51b5; }
        .badge-live { background: rgba(233, 30, 99, 0.15); color: #c2185b; animation: pulse 1.8s infinite; }
        .badge-finished { background: rgba(76, 175, 80, 0.15); color: #2e7d32; }
        .badge-missed { background: rgba(244, 67, 54, 0.15); color: #c62828; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
        .modal-content { border-radius: 24px; border: 1px solid var(--glass-border); background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
        .modal-header { background: linear-gradient(135deg, var(--cyan-start), var(--cyan-mid)); color: white; border-radius: 24px 24px 0 0; padding: 20px 28px; border-bottom: none; }
        .modal-header .modal-title { font-weight: 700; letter-spacing: -0.5px; }
        .modal-body { padding: 28px; }
        .form-select, .form-control { border: 1px solid rgba(0, 151, 167, 0.25); border-radius: 12px; padding: 11px 16px; font-size: 0.92rem; background-color: #ffffff; transition: all 0.2s ease; }
        .form-select:focus, .form-control:focus { border-color: var(--cyan-mid); box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.15); }
        .input-group-text { border: 1px solid rgba(0, 151, 167, 0.25); border-radius: 12px 0 0 12px; background-color: #f8fdfd; color: var(--cyan-mid); }
        .input-group .form-control { border-radius: 0 12px 12px 0; }
        label { color: #37474f; margin-bottom: 8px; font-size: 0.85rem; }
        .action-icon { font-size: 1.1rem; cursor: pointer; transition: transform 0.2s; }
        .action-icon:hover { transform: scale(1.15); }
    </style>
</head>
<body>

<nav class="navbar-custom text-center">
    <h3><i class="fa-solid fa-circle-nodes me-2"></i> Telehealth Meeting Workspace</h3>
</nav>

<div class="container-fluid px-md-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="fw-bold m-0 text-dark" style="letter-spacing: -0.5px;">Video Consultations</h4>
            <p class="text-muted small m-0 mt-1">Manage and deploy synchronized meeting spaces for remote therapeutic sessions.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-cyan" id="addBtn">
                <i class="fa-solid fa-plus me-2"></i> Create Meeting Link
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Meeting ID</th>
                            <th>Appt ID</th>
                            <th>Patient Name</th>
                            <th>Assigned Doctor</th>
                            <th>Access Point</th>
                            <th>Session Status</th>
                            <th>Started At</th>
                            <th>Ended At</th>
                            <th>Management</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="meetingForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-video me-2"></i>Configure Consultation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="fw-semibold">Target Appointment</label>
                            <select name="appointment_id" id="appointment_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Live Operational Status</label>
                            <select name="meeting_status" id="meeting_status" class="form-select" required>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Live">Live</option>
                                <option value="Finished">Finished</option>
                                <option value="Missed">Missed</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="fw-semibold">Secure Communication Endpoint (URL)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="url" name="meeting_link" id="meeting_link" class="form-control" required placeholder="https://meet.google.com/abc-xyz-123">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="fw-semibold text-secondary">Started At (Synchronized)</label>
                            <input type="datetime-local" name="started_at" id="started_at" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold text-secondary">Ended At (Manual Override)</label>
                            <input type="datetime-local" name="ended_at" id="ended_at" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-3" style="border-radius: 0 0 24px 24px;">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="border-radius: 12px;">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let appointmentsData = [];

function loadAppointments() {
    $.get('?url=get_appointments', function(res) {
        appointmentsData = res;
        let html = '<option value="">Select Video Appointment</option>';
        res.forEach(a => { 
            html += `<option value="${a.appointment_id}" data-date="${a.available_date}">ID: ${a.appointment_id} — ${a.patient_name} vs ${a.doctor_name}</option>`; 
        });
        $('#appointment_id').html(html);
    });
}

$('#appointment_id').change(function() {
    let selectedOption = $(this).find('option:selected');
    let appointmentDate = selectedOption.data('date'); 
    if (appointmentDate) {
        $('#started_at').val(appointmentDate + 'T00:00');
    } else {
        $('#started_at').val('');
    }
});

function loadMeetings() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(m => {
            let statusBadge = `<span class="status-badge badge-${m.meeting_status.toLowerCase()}">${m.meeting_status}</span>`;
            let start = m.started_at ? m.started_at : '<span class="text-muted fw-light">—</span>';
            let end = m.ended_at ? m.ended_at : '<span class="text-muted fw-light">—</span>';
            
            html += `
            <tr>
                <td class="text-secondary small">#${m.meeting_id}</td>
                <td class="fw-bold text-dark">${m.appointment_id}</td>
                <td class="fw-medium">${m.patient_name}</td>
                <td class="text-dark fw-semibold">${m.doctor_name}</td>
                <td>
                    <a href="${m.meeting_link}" target="_blank" class="btn btn-sm btn-outline-info text-dark rounded-pill px-3" style="font-size:0.82rem; font-weight:500;">
                        <i class="fa-solid fa-video me-1 text-danger"></i> Launch Meeting
                    </a>
                </td>
                <td>${statusBadge}</td>
                <td><small class="text-secondary">${start}</small></td>
                <td><small class="text-secondary">${end}</small></td>
                <td>
                    <i class="fa-solid fa-pen-to-square text-warning me-3 action-icon edit" data='${JSON.stringify(m)}'></i>
                    <i class="fa-solid fa-trash-can text-danger action-icon del" data-id="${m.meeting_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="9" class="text-muted py-4">No remote video consultations registered.</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#meetingForm')[0].reset();
    $('#id').val('');
    $('#meeting_status').val('Scheduled');
    $('#modal').modal('show');
});

$('#meetingForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({ title: 'Success', text: res.msg, icon: 'success', confirmButtonColor: '#0097a7' });
            loadMeetings();
            $('#modal').modal('hide');
        } else {
            Swal.fire({ title: 'Operation Failed', text: res.msg, icon: 'error', confirmButtonColor: '#0097a7' });
        }
    });
});

$(document).on('click', '.edit', function() {
    let m = JSON.parse($(this).attr('data'));
    $('#id').val(m.meeting_id);
    $('#appointment_id').val(m.appointment_id);
    $('#meeting_status').val(m.meeting_status);
    $('#meeting_link').val(m.meeting_link);
    
    if(m.started_at) $('#started_at').val(m.started_at.replace(' ', 'T'));
    if(m.ended_at) $('#ended_at').val(m.ended_at.replace(' ', 'T'));
    
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Discard session endpoint?',
        text: 'This action will revoke the generated conference setup.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#cfd8dc',
        confirmButtonText: 'Yes, discard!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, (res) => {
                loadMeetings();
                Swal.fire({ title: 'Deleted!', text: res.msg, icon: 'success', confirmButtonColor: '#0097a7' });
            });
        }
    });
});

$(document).ready(() => {
    loadAppointments();
    loadMeetings();
});
</script>
</body>
</html>