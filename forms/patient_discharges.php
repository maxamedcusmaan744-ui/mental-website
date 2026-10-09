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

    // Read Discharges Register
    if ($route == 'display') {
        $sql = "SELECT d.*, a.admission_date, a.admission_reason, u.full_name AS patient_name 
                FROM patient_discharges d
                JOIN patient_admissions a ON d.admission_id = a.admission_id
                JOIN users u ON a.patient_id = u.user_id
                ORDER BY d.discharge_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Fetch ONLY Active Admissions for Dropdown Identification (Status == 'Admitted')
    if ($route == 'get_dropdown_data') {
        $sql = "SELECT a.admission_id, u.full_name 
                FROM patient_admissions a 
                JOIN users u ON a.patient_id = u.user_id 
                WHERE a.status = 'Admitted'
                ORDER BY a.admission_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Discharge Entry & Auto Update Admission Status
    if ($route == 'create') {
        try {
            // Waxaan bilaabaynaa Transaction si labada isbedel ay mar qura u dhacaan
            $conn->beginTransaction();

            // 1. Geli xogta jadwalka patient_discharges
            $sql = "INSERT INTO patient_discharges (admission_id, discharge_date, discharge_summary)
                    VALUES (:admission_id, :discharge_date, :discharge_summary)";
            $stm = $conn->prepare($sql);
            
            $summary = !empty($_POST['discharge_summary']) ? $_POST['discharge_summary'] : null;
            $stm->execute([
                ':admission_id'     => $_POST['admission_id'],
                ':discharge_date'   => $_POST['discharge_date'],
                ':discharge_summary'=> $summary
            ]);

            // 2. Si automatic ah u update-garee status-ka admission-ka una dhig 'Discharged'
            $updateSql = "UPDATE patient_admissions SET status = 'Discharged' WHERE admission_id = :admission_id";
            $updateStm = $conn->prepare($updateSql);
            $updateStm->execute([':admission_id' => $_POST['admission_id']]);

            // Haddi ay labaduba guulaystaan, database-ka ku kaydi
            $conn->commit();
            echo json_encode(["status" => true, "msg" => "Patient discharge successfully processed and admission closed!"]);
        } catch (PDOException $e) {
            // Haddi uu cilad dhaco, dib u celi wixii isbedel ahaa (Rollback)
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Discharge Entry
    if ($route == 'update') {
        try {
            $sql = "UPDATE patient_discharges SET 
                        admission_id = :admission_id, 
                        discharge_date = :discharge_date, 
                        discharge_summary = :discharge_summary 
                    WHERE discharge_id = :id";
            $stm = $conn->prepare($sql);

            $summary = !empty($_POST['discharge_summary']) ? $_POST['discharge_summary'] : null;

            $success = $stm->execute([
                ':id'                => $_POST['id'],
                ':admission_id'      => $_POST['admission_id'],
                ':discharge_date'    => $_POST['discharge_date'],
                ':discharge_summary' => $summary
            ]);
            echo json_encode(["status" => $success, "msg" => "Discharge clearance updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Discharge Entry & Auto Reset Admission Status back to 'Admitted'
    if ($route == 'deleteOperation') {
        try {
            $conn->beginTransaction();

            // 1. Soo hel admission_id ka kahor intaanan tirtirin record-ka
            $getAdmissionSql = "SELECT admission_id FROM patient_discharges WHERE discharge_id = :id";
            $getAdmissionStm = $conn->prepare($getAdmissionSql);
            $getAdmissionStm->execute([':id' => $_POST['id']]);
            $discharge = $getAdmissionStm->fetch(PDO::FETCH_ASSOC);

            if ($discharge) {
                // 2. Tirtir record-ka discharge-ka
                $stm = $conn->prepare("DELETE FROM patient_discharges WHERE discharge_id = :id");
                $stm->execute([':id' => $_POST['id']]);

                // 3. Dib u fur admission-ka (U bedel 'Admitted')
                $resetSql = "UPDATE patient_admissions SET status = 'Admitted' WHERE admission_id = :admission_id";
                $resetStm = $conn->prepare($resetSql);
                $resetStm->execute([':admission_id' => $discharge['admission_id']]);
            }

            $conn->commit();
            echo json_encode(["status" => true, "msg" => "Discharge logging archived and admission status restored to Admitted."]);
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
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
    <title>Patient Discharge Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-door-open me-2"></i> Clinical Discharges & Departure Clearance</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Discharge Logs Registry</h4>
            <p class="text-muted">Review patient departures, formulate recovery summary charts, and authorize checkout metrics.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Log New Discharge
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Discharge ID</th>
                            <th>Admission Reference</th>
                            <th>Patient Identity</th>
                            <th>Discharge Date</th>
                            <th>Clinical Summary / Conditions</th>
                            <th>Logged On</th>
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
            <form id="dischargeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Clinical Exit Sheet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Active Admission Case File</label>
                            <select name="admission_id" id="admission_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Discharge Date & Time</label>
                            <input type="datetime-local" name="discharge_date" id="discharge_date" class="form-control shadow-sm" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold">Post-Treatment Summary & Discharge Directives</label>
                        <textarea name="discharge_summary" id="discharge_summary" class="form-control shadow-sm" rows="5" placeholder="Specify final diagnosis, treatment outcome, or prescribed home care limitations..."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Checkout Manifest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Populates Active Admission Folders into Form Selection Elements
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let options = '<option value="">-- Choose Admission File Reference --</option>';
        res.forEach(a => {
            options += `<option value="${a.admission_id}">Case #ADM-${a.admission_id} - [ ${a.full_name} ]</option>`;
        });
        $('#admission_id').html(options);
    });
}

// Converts standard MySQL DATETIME to fit HTML5 input values safely
function formatDateTimeLocal(dateTimeStr) {
    if(!dateTimeStr) return "";
    let d = new Date(dateTimeStr.replace(/-/g, "/"));
    let month = "" + (d.getMonth() + 1),
        day = "" + d.getDate(),
        year = d.getFullYear(),
        hours = "" + d.getHours(),
        minutes = "" + d.getMinutes();

    if (month.length < 2) month = "0" + month;
    if (day.length < 2) day = "0" + day;
    if (hours.length < 2) hours = "0" + hours;
    if (minutes.length < 2) minutes = "0" + minutes;

    return [year, month, day].join("-") + "T" + [hours, minutes].join(":");
}

// Load Core Discharges Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(d => {
            let descSnippet = d.discharge_summary ? d.discharge_summary : 'No recovery notes provided.';

            html += `
            <tr>
                <td><strong>#DIS-${d.discharge_id}</strong></td>
                <td><span class="badge bg-light text-cyan border border-info px-2 py-1">#ADM-${d.admission_id}</span></td>
                <td class="text-start fw-bold text-dark">${d.patient_name}</td>
                <td><code class="text-danger small fw-bold">${d.discharge_date}</code></td>
                <td class="text-start small text-truncate" style="max-width: 280px;" title="${descSnippet}">${descSnippet}</td>
                <td><code class="text-muted small">${d.created_at}</code></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(d)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${d.discharge_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Modal (Create Execution Variant)
$('#addBtn').click(() => {
    $('#dischargeForm')[0].reset();
    $('#id').val('');
    
    // Refresh dropdown to display only currently admitted patients
    loadDropdownData();
    
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#discharge_date').val(now.toISOString().slice(0,16));
    
    $('#admission_id').prop('disabled', false);
    $('#modal').modal('show');
});

// Ajax Submission Management Engine
$('#dischargeForm').submit(function(e) {
    e.preventDefault();
    
    $('#admission_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#admission_id').prop('disabled', true); 
    }

    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            loadDropdownData(); // reload dropdown lists
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Trigger Configurations
$(document).on('click', '.edit', function() {
    let d = JSON.parse($(this).attr('data'));
    
    // Si kumeel gaar ah loogu muujiyo dropdown-ka marka la joogo update mode
    let options = `<option value="${d.admission_id}">Case #ADM-${d.admission_id} - [ ${d.patient_name} ]</option>`;
    $('#admission_id').html(options);

    $('#id').val(d.discharge_id);
    $('#admission_id').val(d.admission_id).prop('disabled', true); 
    $('#discharge_date').val(formatDateTimeLocal(d.discharge_date));
    $('#discharge_summary').val(d.discharge_summary ? d.discharge_summary : '');
    $('#modal').modal('show');
});

// Delete Data Operations
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Purge this discharge record?',
        text: "This removes the physical release log and reverts admission status to Admitted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, clear history entry!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, function(res) {
                if(res.status){
                    loadData();
                    loadDropdownData();
                    Swal.fire('Purged!', res.msg, 'success');
                } else {
                    Swal.fire('Error', res.msg, 'error');
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