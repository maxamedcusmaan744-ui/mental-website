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

    // Read Therapy Sessions Ledger
    if ($route == 'display') {
        $sql = "SELECT t.*, 
                       u1.full_name AS patient_name, 
                       u2.full_name AS counsellor_name 
                FROM therapy_sessions t
                JOIN users u1 ON t.patient_id = u1.user_id
                JOIN users u2 ON t.counsellor_id = u2.user_id
                ORDER BY t.therapy_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Fetch System Users for Options Selectors
    if ($route == 'get_dropdown_data') {
        $sqlUsers = "SELECT user_id, full_name FROM users ORDER BY full_name ASC";
        $stm = $conn->prepare($sqlUsers);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Therapy Session Entry
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO therapy_sessions (patient_id, counsellor_id, session_date, notes, status)
                    VALUES (:patient_id, :counsellor_id, :session_date, :notes, :status)";
            $stm = $conn->prepare($sql);
            
            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;

            $success = $stm->execute([
                ':patient_id'     => $_POST['patient_id'],
                ':counsellor_id'  => $_POST['counsellor_id'],
                ':session_date'   => $_POST['session_date'],
                ':notes'          => $notes,
                ':status'         => $_POST['status']
            ]);
            echo json_encode(["status" => true, "msg" => "New therapy session slot booked and initialized!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Therapy Session Entry
    if ($route == 'update') {
        try {
            $sql = "UPDATE therapy_sessions SET 
                        patient_id = :patient_id, 
                        counsellor_id = :counsellor_id, 
                        session_date = :session_date, 
                        notes = :notes, 
                        status = :status 
                    WHERE therapy_id = :id";
            $stm = $conn->prepare($sql);

            $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;

            $success = $stm->execute([
                ':id'             => $_POST['id'],
                ':patient_id'     => $_POST['patient_id'],
                ':counsellor_id'  => $_POST['counsellor_id'],
                ':session_date'   => $_POST['session_date'],
                ':notes'          => $notes,
                ':status'         => $_POST['status']
            ]);
            echo json_encode(["status" => $success, "msg" => "Therapeutic timeline properties updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Therapy Session Entry
    if ($route == 'deleteOperation') {
        try {
            $stm = $conn->prepare("DELETE FROM therapy_sessions WHERE therapy_id = :id");
            $success = $stm->execute([':id' => $_POST['id']]);
            echo json_encode(["status" => $success, "msg" => "Therapy profile logs cleared cleanly out of directory indexes."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: Structural constraints prevented system deletion."]);
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
    <title>Therapy Sessions Management Matrix | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-comments me-2"></i> Clinical Administration & Therapy Session Registry</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Therapeutic Session Matrix</h4>
            <p class="text-muted">Maintain counsellor allocations, track client onboarding progressions, and update clinical session documentation timelines.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Schedule New Consultation Slot
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Identity Patient</th>
                            <th>Assigned Counsellor</th>
                            <th>Target Appointment Time</th>
                            <th>Case Overview Notes</th>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="sessionForm">
                <div class="modal-header">
                    <h5 class="modal-title">Clinical Placement Configuration Charter</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Select Target Client Profile</label>
                            <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Allocated Care Consultant</label>
                            <select name="counsellor_id" id="counsellor_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Contractual Appointment Datetime Window</label>
                            <input type="datetime-local" name="session_date" id="session_date" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Clinical Progress Status Indicator</label>
                            <select name="status" id="status" class="form-select shadow-sm" required>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label class="small fw-bold">Private Case Progress & Observational Diagnostic Notes</label>
                            <textarea name="notes" id="notes" class="form-control shadow-sm" rows="4" placeholder="Enter session summaries, clinical updates, or therapeutic milestones discovered..."></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Consultation Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Populates Relational User Select boxes dynamically 
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let patientOpts = '<option value="">-- Bind Patient Entity profile --</option>';
        let counsellorOpts = '<option value="">-- Bind Counsellor Entity profile --</option>';
        
        res.forEach(u => {
            let optionStr = `<option value="${u.user_id}">${u.full_name} (#USR-${u.user_id})</option>`;
            patientOpts += optionStr;
            counsellorOpts += optionStr;
        });
        
        $('#patient_id').html(patientOpts);
        $('#counsellor_id').html(counsellorOpts);
    });
}

// Load Core Therapy Ledger Layout
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(t => {
            let statusBadge = '';
            if(t.status === 'Scheduled') {
                statusBadge = '<span class="badge bg-light text-info border border-info-subtle px-3 py-1 fw-bold">Scheduled</span>';
            } else if(t.status === 'Completed') {
                statusBadge = '<span class="badge bg-light text-success border border-success-subtle px-3 py-1 fw-bold">Completed</span>';
            } else {
                statusBadge = '<span class="badge bg-light text-danger border border-danger-subtle px-3 py-1 fw-bold">Cancelled</span>';
            }

            let notesSnippet = t.notes ? `<div class="text-truncate small text-secondary text-start mx-auto" style="max-width: 200px;" title="${t.notes}">${t.notes}</div>` : '<span class="text-muted small italic">Empty Notes</span>';
            
            // Format datetime safely for front-end presentation 
            let dt = new Date(t.session_date);
            let formattedDate = dt.toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: 'numeric'}) + ' ' + dt.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

            html += `
            <tr>
                <td><strong>#THP-${t.therapy_id}</strong></td>
                <td class="fw-bold text-start text-dark"><i class="fa-solid fa-user me-2 text-secondary"></i>${t.patient_name}</td>
                <td class="fw-semibold text-start text-cyan"><i class="fa-solid fa-user-doctor me-2"></i>${t.counsellor_name}</td>
                <td><code>${formattedDate}</code></td>
                <td>${notesSnippet}</td>
                <td>${statusBadge}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(t)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${t.therapy_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Modal (Create Mode Variant)
$('#addBtn').click(() => {
    $('#sessionForm')[0].reset();
    $('#id').val('');
    
    // Auto populate local datetime input to match current structural runtime hours
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#session_date').val(now.toISOString().slice(0,16));
    
    $('#modal').modal('show');
});

// AJAX Dynamic Form Submission Engine
$('#sessionForm').submit(function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    let url = $('#id').val() ? 'update' : 'create';

    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Action Revoked', res.msg, 'error');
        }
    });
});

// Structural Injection to Edit Existing Profiles
$(document).on('click', '.edit', function() {
    let t = JSON.parse($(this).attr('data'));
    $('#id').val(t.therapy_id);
    $('#patient_id').val(t.patient_id);
    $('#counsellor_id').val(t.counsellor_id);
    
    // Replace standard format variants to properly populate datetime-local attributes
    if (t.session_date) {
        let formattedDT = t.session_date.replace(" ", "T").substring(0, 16);
        $('#session_date').val(formattedDT);
    }
    
    $('#notes').val(t.notes ? t.notes : '');
    $('#status').val(t.status);
    $('#modal').modal('show');
});

// Purge Placement Allocation Routing 
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Revoke therapy session entry?',
        text: "This removes the session's diagnostic matrix records safely from tracking directories.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, terminate assignment!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, function(res) {
                if(res.status) {
                    loadData();
                    Swal.fire('Terminated!', res.msg, 'success');
                } else {
                    Swal.fire('Failed', res.msg, 'error');
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