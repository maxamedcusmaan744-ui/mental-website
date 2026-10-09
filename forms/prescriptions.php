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

    // Read Prescriptions Ledger
    if ($route == 'display') {
        $sql = "SELECT p.*, 
                       u1.full_name AS patient_name, 
                       u2.full_name AS counsellor_name, 
                       m.medication_name 
                FROM prescriptions p
                JOIN users u1 ON p.patient_id = u1.user_id
                JOIN users u2 ON p.counsellor_id = u2.user_id
                JOIN medications m ON p.medication_id = m.medication_id
                ORDER BY p.prescription_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Fetch Relational Information For Choice Selectors
    if ($route == 'get_dropdown_data') {
        $data = [];
        
        // Patients
        $sqlPatients = "SELECT user_id, full_name FROM users WHERE user_type='Patient' AND status='Active' ORDER BY full_name ASC";
        $stm = $conn->prepare($sqlPatients);
        $stm->execute();
        $data['patients'] = $stm->fetchAll(PDO::FETCH_ASSOC);
        
        // Counsellors / Doctors
        $sqlCounsellors = "SELECT user_id, full_name FROM users WHERE user_type='Counsellor' AND status='Active' ORDER BY full_name ASC";
        $stm = $conn->prepare($sqlCounsellors);
        $stm->execute();
        $data['counsellors'] = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Medications
        $sqlMeds = "SELECT medication_id, medication_name FROM medications ORDER BY medication_name ASC";
        $stm = $conn->prepare($sqlMeds);
        $stm->execute();
        $data['medications'] = $stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
        exit;
    }

    // Create Prescription Entry
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO prescriptions (patient_id, counsellor_id, medication_id, dosage, frequency, start_date, end_date)
                    VALUES (:patient_id, :counsellor_id, :medication_id, :dosage, :frequency, :start_date, :end_date)";
            $stm = $conn->prepare($sql);
            
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            $success = $stm->execute([
                ':patient_id'     => $_POST['patient_id'],
                ':counsellor_id'  => $_POST['counsellor_id'],
                ':medication_id'  => $_POST['medication_id'],
                ':dosage'         => $_POST['dosage'],
                ':frequency'      => $_POST['frequency'],
                ':start_date'     => $_POST['start_date'],
                ':end_date'       => $endDate
            ]);
            echo json_encode(["status" => true, "msg" => "Clinical prescription charting authorized successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Prescription Entry
    if ($route == 'update') {
        try {
            $sql = "UPDATE prescriptions SET 
                        patient_id = :patient_id, 
                        counsellor_id = :counsellor_id, 
                        medication_id = :medication_id, 
                        dosage = :dosage, 
                        frequency = :frequency, 
                        start_date = :start_date, 
                        end_date = :end_date 
                    WHERE prescription_id = :id";
            $stm = $conn->prepare($sql);

            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

            $success = $stm->execute([
                ':id'             => $_POST['id'],
                ':patient_id'     => $_POST['patient_id'],
                ':counsellor_id'  => $_POST['counsellor_id'],
                ':medication_id'  => $_POST['medication_id'],
                ':dosage'         => $_POST['dosage'],
                ':frequency'      => $_POST['frequency'],
                ':start_date'     => $_POST['start_date'],
                ':end_date'       => $endDate
            ]);
            echo json_encode(["status" => $success, "msg" => "Prescription regimen updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Prescription Entry
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = :id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Prescription record archived safely out of therapeutic scheduling logs."]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Registry Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-pills me-2"></i> Clinical Prescriptions & Medication Charting</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Psychopharmacology Registry</h4>
            <p class="text-muted">Track treatment regimens, manage authorized dosing metrics, and monitor clinical implementation schedules.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Chart New Prescription
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Rx ID</th>
                            <th>Patient Identity</th>
                            <th>Prescribing Professional</th>
                            <th>Medication / Substance</th>
                            <th>Dosage Metric</th>
                            <th>Frequency Interval</th>
                            <th>Active Regimen Dates</th>
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
            <form id="prescriptionForm">
                <div class="modal-header">
                    <h5 class="modal-title">Clinical Prescription Slip</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Target Patient File</label>
                            <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Authorizing Clinical Counsellor</label>
                            <select name="counsellor_id" id="counsellor_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold">Formulary Medication Designation</label>
                            <select name="medication_id" id="medication_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Dosage Measurements</label>
                            <input type="text" name="dosage" id="dosage" class="form-control shadow-sm" placeholder="e.g. 20mg, 5ml, 1 Capsule" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Intake Frequency Interval</label>
                            <input type="text" name="frequency" id="frequency" class="form-control shadow-sm" placeholder="e.g. Once daily at bedtime, Every 8 hours" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small fw-bold">Regimen Initiation Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Regimen Conclusion Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control shadow-sm">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Regimen Protocol</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Populates Patients, Doctors, and Pharmacy Items into Form Select Inputs
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let patOpts = '<option value="">-- Select Patient Chart --</option>';
        res.patients.forEach(p => {
            patOpts += `<option value="${p.user_id}">${p.full_name} (#PT-${p.user_id})</option>`;
        });
        $('#patient_id').html(patOpts);

        let counsOpts = '<option value="">-- Select Prescribing Practitioner --</option>';
        res.counsellors.forEach(c => {
            counsOpts += `<option value="${c.user_id}">${c.full_name} (#CNS-${c.user_id})</option>`;
        });
        $('#counsellor_id').html(counsOpts);

        let medOpts = '<option value="">-- Select Medication Item --</option>';
        res.medications.forEach(m => {
            medOpts += `<option value="${m.medication_id}">${m.medication_name}</option>`;
        });
        $('#medication_id').html(medOpts);
    });
}

// Load Core Prescriptions Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(p => {
            let endDisp = p.end_date ? p.end_date : '<span class="text-info fw-semibold">Continuous Regimen</span>';

            html += `
            <tr>
                <td><strong>#RX-${p.prescription_id}</strong></td>
                <td class="fw-bold text-dark text-start">${p.patient_name}</td>
                <td class="text-muted small text-start"><i class="fa-solid fa-user-doctor me-1"></i> ${p.counsellor_name}</td>
                <td class="fw-bold text-cyan">${p.medication_name}</td>
                <td><span class="badge bg-light text-dark border border-cyan px-2 py-1">${p.dosage}</span></td>
                <td class="small text-secondary">${p.frequency}</td>
                <td class="small text-start">
                    <div><span class="text-success small fw-bold">From:</span> <code>${p.start_date}</code></div>
                    <div><span class="text-danger small fw-bold">Till:</span> <code>${endDisp}</code></div>
                </td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(p)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${p.prescription_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Modal (Create Variant Setup)
$('#addBtn').click(() => {
    $('#prescriptionForm')[0].reset();
    $('#id').val('');
    
    // Default system initialization timeline
    let today = new Date().toISOString().split('T')[0];
    $('#start_date').val(today);
    
    $('#patient_id, #counsellor_id, #medication_id').prop('disabled', false);
    $('#modal').modal('show');
});

// Ajax Submission Pipeline Engine
$('#prescriptionForm').submit(function(e) {
    e.preventDefault();
    
    $('#patient_id, #counsellor_id, #medication_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#patient_id, #counsellor_id, #medication_id').prop('disabled', true); // Safe lock inside history update routines
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

// Edit Regimen Activation Interface
$(document).on('click', '.edit', function() {
    let p = JSON.parse($(this).attr('data'));
    $('#id').val(p.prescription_id);
    $('#patient_id').val(p.patient_id).prop('disabled', true); 
    $('#counsellor_id').val(p.counsellor_id).prop('disabled', true); 
    $('#medication_id').val(p.medication_id).prop('disabled', true); 
    $('#dosage').val(p.dosage);
    $('#frequency').val(p.frequency);
    $('#start_date').val(p.start_date);
    $('#end_date').val(p.end_date ? p.end_date : '');
    $('#modal').modal('show');
});

// Purge Treatment Index Record
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Revoke therapeutic script?',
        text: "This safely purges the clinical medication tracking assignment from current pharmacological processing queues.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, clear prescription!'
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