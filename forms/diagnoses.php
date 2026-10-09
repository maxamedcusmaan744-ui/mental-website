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

    // Read Diagnoses
    if ($route == 'display') {
        $sql = "SELECT d.*, 
                       p_user.full_name AS patient_name, 
                       doc_user.full_name AS doctor_name 
                FROM diagnoses d
                JOIN patient_admissions pa ON d.patient_id = pa.admission_id
                JOIN users p_user ON pa.patient_id = p_user.user_id
                JOIN doctors doc ON d.doctor_id = doc.doctor_id
                JOIN users doc_user ON doc.user_id = doc_user.user_id
                ORDER BY d.diagnosis_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Patients and Doctors for Dropdowns
    if ($route == 'get_dropdown_data') {
        // Patients via active admissions
        $p_stm = $conn->prepare("SELECT pa.admission_id AS user_id, u.full_name 
                                 FROM patient_admissions pa
                                 JOIN users u ON pa.patient_id = u.user_id
                                 WHERE pa.status = 'Admitted'");
        $p_stm->execute();
        $patients = $p_stm->fetchAll(PDO::FETCH_ASSOC);

        // Doctors 
        $c_stm = $conn->prepare("SELECT doc.doctor_id AS user_id, u.full_name 
                                 FROM doctors doc
                                 JOIN users u ON doc.user_id = u.user_id");
        $c_stm->execute();
        $doctors = $c_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["patients" => $patients, "doctors" => $doctors]);
        exit;
    }

    // Create
    if ($route == 'create') {
        $sql = "INSERT INTO diagnoses (patient_id, doctor_id, diagnosis_name, diagnosis_details, diagnosis_date) 
                VALUES (:patient_id, :doctor_id, :name, :details, :diagnosis_date)";
        $stm = $conn->prepare($sql);
        
        $details = !empty($_POST['diagnosis_details']) ? $_POST['diagnosis_details'] : null;

        $success = $stm->execute([
            ':patient_id'     => $_POST['patient_id'],
            ':doctor_id'      => $_POST['doctor_id'],
            ':name'           => $_POST['diagnosis_name'],
            ':details'        => $details,
            ':diagnosis_date' => $_POST['diagnosis_date']
        ]);
        echo json_encode(["status" => $success, "msg" => "Diagnosis recorded successfully!"]);
        exit;
    }

    // Update
    if ($route == 'update') {
        $sql = "UPDATE diagnoses SET patient_id=:patient_id, doctor_id=:doctor_id, 
                diagnosis_name=:name, diagnosis_details=:details, diagnosis_date=:diagnosis_date 
                WHERE diagnosis_id=:id";
        $stm = $conn->prepare($sql);

        $details = !empty($_POST['diagnosis_details']) ? $_POST['diagnosis_details'] : null;

        $success = $stm->execute([
            ':id'             => $_POST['id'],
            ':patient_id'     => $_POST['patient_id'],
            ':doctor_id'      => $_POST['doctor_id'],
            ':name'           => $_POST['diagnosis_name'],
            ':details'        => $details,
            ':diagnosis_date' => $_POST['diagnosis_date']
        ]);
        echo json_encode(["status" => $success, "msg" => "Diagnosis updated successfully!"]);
        exit;
    }

    // Delete
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM diagnoses WHERE diagnosis_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Diagnosis record deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosis Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-file-medical me-2"></i> Patient Diagnosis Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Diagnosis & Case Records</h4>
            <p class="text-muted">Document and review medical diagnoses assigned to patients by attending doctors.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-notes-medical me-2"></i> Record New Diagnosis
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Diagnosis ID</th>
                            <th>Patient Name</th>
                            <th>Diagnosis / Illness</th>
                            <th>Assigned Doctor</th>
                            <th>Date</th>
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
            <form id="diagnosisForm">
                <div class="modal-header">
                    <h5 class="modal-title">Diagnosis Sheet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Patient Name</label>
                            <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Attending Doctor</label>
                            <select name="doctor_id" id="doctor_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="small fw-bold">Diagnosis Name / Condition</label>
                            <input type="text" name="diagnosis_name" id="diagnosis_name" class="form-control shadow-sm" placeholder="e.g. Clinical Depression / PTSD" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="small fw-bold">Diagnosis Date</label>
                            <input type="date" name="diagnosis_date" id="diagnosis_date" class="form-control shadow-sm" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Detailed Observations & Case Notes</label>
                        <textarea name="diagnosis_details" id="diagnosis_details" class="form-control shadow-sm" rows="5" placeholder="Enter clinical assessment notes, symptoms, or recommendations..."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Dropdown Selections
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let p = '<option value="">-- Choose Patient --</option>';
        let d = '<option value="">-- Choose Doctor --</option>';
        
        res.patients.forEach(patient => {
            p += `<option value="${patient.user_id}">${patient.full_name}</option>`;
        });
        
        res.doctors.forEach(doctor => {
            d += `<option value="${doctor.user_id}">${doctor.full_name}</option>`;
        });
        
        $('#patient_id').html(p);
        $('#doctor_id').html(d);
    });
}

// Load Main Table
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(d => {
            let details = d.diagnosis_details ? d.diagnosis_details : '<span class="text-muted italic">No extra details</span>';
            
            html += `
            <tr>
                <td>${d.diagnosis_id}</td>
                <td class="fw-bold text-dark">${d.patient_name}</td>
                <td>
                    <span class="badge bg-light text-cyan border border-info px-2 py-1 fw-bold">${d.diagnosis_name}</span>
                    <br><small class="text-muted d-block text-start mt-1 px-2" style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${details}">${details}</small>
                </td>
                <td class="text-secondary"><i class="fa-solid fa-user-md small me-1"></i>${d.doctor_name}</td>
                <td><small class="fw-semibold">${d.diagnosis_date}</small></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(d)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${d.diagnosis_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Trigger New Diagnosis Window
$('#addBtn').click(() => {
    $('#diagnosisForm')[0].reset();
    $('#id').val('');
    document.getElementById('diagnosis_date').valueAsDate = new Date();
    $('#modal').modal('show');
});

// Submit Form (Insert/Update)
$('#diagnosisForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Saved', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        }
    });
});

// Trigger Edit Window
$(document).on('click', '.edit', function() {
    let d = JSON.parse($(this).attr('data'));
    $('#id').val(d.diagnosis_id);
    $('#patient_id').val(d.patient_id);
    $('#doctor_id').val(d.doctor_id);
    $('#diagnosis_name').val(d.diagnosis_name);
    $('#diagnosis_date').val(d.diagnosis_date);
    $('#diagnosis_details').val(d.diagnosis_details ? d.diagnosis_details : '');
    $('#modal').modal('show');
});

// Delete Execution
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this medical diagnosis?',
        text: "This record will be permanently erased from the patient clinical file.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete file!'
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