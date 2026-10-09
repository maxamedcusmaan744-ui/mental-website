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
if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    $action = $_GET['action'];
    header('Content-Type: application/json');

    // 1. Read Treatment Plans
    if ($action == 'display') {
        $sql = "SELECT tp.*, 
                       u1.full_name AS patient_name, 
                       u2.full_name AS counsellor_name 
                FROM treatment_plans tp
                JOIN users u1 ON tp.patient_id = u1.user_id
                JOIN users u2 ON tp.counsellor_id = u2.user_id
                ORDER BY tp.plan_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Fetch System Users for Choices Selectors
    if ($action == 'get_dropdown_data') {
        $sqlUsers = "SELECT user_id, full_name FROM users ORDER BY full_name ASC";
        $stm = $conn->prepare($sqlUsers);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 3. Create Treatment Plan Entry
    if ($action == 'create') {
        try {
            $sql = "INSERT INTO treatment_plans (patient_id, counsellor_id, treatment_goal, treatment_details, start_date, end_date, status)
                    VALUES (:patient_id, :counsellor_id, :treatment_goal, :treatment_details, :start_date, :end_date, :status)";
            $stm = $conn->prepare($sql);
            
            $details = !empty($_POST['treatment_details']) ? $_POST['treatment_details'] : null;
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            $success = $stm->execute([
                ':patient_id'        => $_POST['patient_id'],
                ':counsellor_id'     => $_POST['counsellor_id'],
                ':treatment_goal'    => $_POST['treatment_goal'],
                ':treatment_details' => $details,
                ':start_date'        => $_POST['start_date'],
                ':end_date'          => $endDate,
                ':status'            => $_POST['status']
            ]);
            echo json_encode(["status" => true, "msg" => "New therapeutic treatment plan structured successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // 4. Update Treatment Plan Entry
    if ($action == 'update') {
        try {
            $sql = "UPDATE treatment_plans SET 
                        patient_id = :patient_id, 
                        counsellor_id = :counsellor_id, 
                        treatment_goal = :treatment_goal, 
                        treatment_details = :treatment_details, 
                        start_date = :start_date, 
                        end_date = :end_date, 
                        status = :status 
                    WHERE plan_id = :id";
            $stm = $conn->prepare($sql);

            $details = !empty($_POST['treatment_details']) ? $_POST['treatment_details'] : null;
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            $success = $stm->execute([
                ':id'                => $_POST['id'],
                ':patient_id'        => $_POST['patient_id'],
                ':counsellor_id'     => $_POST['counsellor_id'],
                ':treatment_goal'    => $_POST['treatment_goal'],
                ':treatment_details' => $details,
                ':start_date'        => $_POST['start_date'],
                ':end_date'          => $endDate,
                ':status'            => $_POST['status']
            ]);
            echo json_encode(["status" => $success, "msg" => "Clinical treatment metrics updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // 5. Delete Treatment Plan Entry
    if ($action == 'deleteOperation') {
        try {
            $stm = $conn->prepare("DELETE FROM treatment_plans WHERE plan_id = :id");
            $success = $stm->execute([':id' => $_POST['id']]);
            echo json_encode(["status" => $success, "msg" => "Treatment architectural mapping cleared cleanly from storage records."]);
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
    <title>Treatment Plans Matrix | MindCare</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --cyan-gradient: linear-gradient(135deg, #00fbff 0%, #00838f 100%);
            --dark-cyan: #00838f;
        }

        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--cyan-gradient);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,131,143,0.3);
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
            background: var(--cyan-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-cyan:hover {
            color: white;
            box-shadow: 0 8px 20px rgba(0,131,143,0.4);
            transform: translateY(-2px);
        }

        .table thead {
            background: #e0f7f9;
            color: var(--dark-cyan);
        }

        .modal-content { border-radius: 20px; border: none; }
        .modal-header {
            background: var(--cyan-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-heart-pulse me-2"></i> Clinical Administration & Treatment Plan Matrix</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h4 class="text-dark">Patient Recovery Architectures</h4>
            <p class="text-muted mb-0">Track clinical treatment goals, manage medical intervention lengths, and supervise ongoing professional case notes.</p>
        </div>
        <div class="col-md-5 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Structure Treatment Plan
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Plan ID</th>
                            <th>Target Patient</th>
                            <th>Assigned Counsellor</th>
                            <th>Primary Goal Target</th>
                            <th>Timeline Window</th>
                            <th>Status Tag</th>
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
            <form id="planForm">
                <div class="modal-header">
                    <h5 class="modal-title">Clinical Recovery Setting Configuration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">Select Target Patient Entity</label>
                            <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">Allocated Care Consultant</label>
                            <select name="counsellor_id" id="counsellor_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold text-secondary">Primary Treatment Goal</label>
                            <input type="text" name="treatment_goal" id="treatment_goal" class="form-control shadow-sm" placeholder="e.g., Cognitive Behavioral Adjustment for Social Anxiety Recovery" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold text-secondary">Effective Commencement Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-secondary">Target Completion Window</label>
                            <input type="date" name="end_date" id="end_date" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-secondary">Current Pipeline Tracking State</label>
                            <select name="status" id="status" class="form-select shadow-sm" required>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label class="small fw-bold text-secondary">Comprehensive Progress Details & Therapeutic Methodologies</label>
                            <textarea name="treatment_details" id="treatment_details" class="form-control shadow-sm" rows="4" placeholder="Enter session structures, analytical data benchmarks, medical methodologies, etc..."></textarea>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Commit Architecture Updates</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Populates Relational Users dynamically inside choice selectors
function loadDropdownData() {
    $.get('?action=get_dropdown_data', function(res) {
        let patientOpts = '<option value="">-- Bind Target Patient --</option>';
        let counsellorOpts = '<option value="">-- Bind Professional Counsellor --</option>';
        
        res.forEach(u => {
            let optionStr = `<option value="${u.user_id}">${u.full_name} (#USR-${u.user_id})</option>`;
            patientOpts += optionStr;
            counsellorOpts += optionStr;
        });
        
        $('#patient_id').html(patientOpts);
        $('#counsellor_id').html(counsellorOpts);
    });
}

// Load Core Structural Treatment Ledger 
function loadData() {
    $.get('?action=display', function(res) {
        let html = '';
        res.forEach(tp => {
            let statusBadge = '';
            if(tp.status === 'Active') {
                statusBadge = '<span class="badge bg-light text-primary border border-primary-subtle px-3 py-1 fw-bold">Active</span>';
            } else if(tp.status === 'Completed') {
                statusBadge = '<span class="badge bg-light text-success border border-success-subtle px-3 py-1 fw-bold">Completed</span>';
            } else {
                statusBadge = '<span class="badge bg-light text-danger border border-danger-subtle px-3 py-1 fw-bold">Cancelled</span>';
            }

            let startFmt = tp.start_date ? `<code>${tp.start_date}</code>` : 'N/A';
            let endFmt = tp.end_date ? `<code>${tp.end_date}</code>` : '<span class="text-muted small">Open Ended</span>';

            html += `
            <tr>
                <td><strong>#PLN-${tp.plan_id}</strong></td>
                <td class="fw-bold text-start text-dark"><i class="fa-solid fa-user me-2 text-secondary"></i>${tp.patient_name}</td>
                <td class="fw-semibold text-start text-info"><i class="fa-solid fa-user-doctor me-2"></i>${tp.counsellor_name}</td>
                <td class="small fw-semibold text-secondary text-start" title="${tp.treatment_goal}">${tp.treatment_goal}</td>
                <td><small>${startFmt} → ${endFmt}</small></td>
                <td>${statusBadge}</td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(tp)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${tp.plan_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Modal (Create Mode)
$('#addBtn').click(() => {
    $('#planForm')[0].reset();
    $('#id').val('');
    
    let today = new Date().toISOString().split('T')[0];
    $('#start_date').val(today);
    
    $('#modal').modal('show');
});

// Dynamic Ajax Database Mutation Submissions
$('#planForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';

    $.post('?action=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Matrix Process Completed', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Action Revoked', res.msg, 'error');
        }
    });
});

// Structural Injection to Edit Existing treatment plan frameworks
$(document).on('click', '.edit', function() {
    let tp = JSON.parse($(this).attr('data'));
    $('#id').val(tp.plan_id);
    $('#patient_id').val(tp.patient_id);
    $('#counsellor_id').val(tp.counsellor_id);
    $('#treatment_goal').val(tp.treatment_goal);
    $('#treatment_details').val(tp.treatment_details ? tp.treatment_details : '');
    $('#start_date').val(tp.start_date);
    $('#end_date').val(tp.end_date ? tp.end_date : '');
    $('#status').val(tp.status);
    $('#modal').modal('show');
});

// Purge Placement Allocation Processes
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Revoke active treatment plan mapping?',
        text: "This safely removes the user's operational treatment tracking parameters from corporate storage systems.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, terminate framework!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?action=deleteOperation', {id: id}, function(res) {
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