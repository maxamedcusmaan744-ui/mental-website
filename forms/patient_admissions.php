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

    // Read Admissions Ledger
    if ($route == 'display') {
        $sql = "SELECT a.*, 
                       u.full_name AS patient_name, u.email AS patient_email,
                       r.room_id AS room_number,
                       b.bed_number AS bed_name
                FROM patient_admissions a
                JOIN users u ON a.patient_id = u.user_id
                JOIN rooms r ON a.room_id = r.room_id
                JOIN beds b ON a.bed_id = b.bed_id
                ORDER BY a.admission_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Fetch Patients and Rooms for initial Dropdowns
    if ($route == 'get_dropdown_data') {
        $p_stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Patient' AND status='Active'");
        $p_stm->execute();
        $patients = $p_stm->fetchAll(PDO::FETCH_ASSOC);

        $r_stm = $conn->prepare("SELECT room_id FROM rooms ORDER BY room_id ASC");
        $r_stm->execute();
        $rooms = $r_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "patients" => $patients,
            "rooms"    => $rooms
        ]);
        exit;
    }

    // Fetch specific Available Beds for a selected Room
    if ($route == 'get_beds_by_room') {
        $room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
        $current_bed_id = isset($_GET['current_bed_id']) ? intval($_GET['current_bed_id']) : 0;
        
        $sql = "SELECT bed_id, bed_number FROM beds 
                WHERE room_id = :room_id AND (status = 'Available' OR bed_id = :current_bed_id)
                ORDER BY bed_number ASC";
                
        $b_stm = $conn->prepare($sql);
        $b_stm->execute([
            ':room_id' => $room_id,
            ':current_bed_id' => $current_bed_id
        ]);
        echo json_encode($b_stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Admission Entry
    if ($route == 'create') {
        try {
            $conn->beginTransaction();

            $sql = "INSERT INTO patient_admissions (patient_id, room_id, bed_id, admission_date, admission_reason, status)
                    VALUES (:patient_id, :room_id, :bed_id, :admission_date, :admission_reason, :status)";
            $stm = $conn->prepare($sql);
            
            $status = !empty($_POST['status']) ? $_POST['status'] : 'Admitted';

            $stm->execute([
                ':patient_id'       => $_POST['patient_id'],
                ':room_id'          => $_POST['room_id'],
                ':bed_id'           => $_POST['bed_id'],
                ':admission_date'   => $_POST['admission_date'],
                ':admission_reason' => $_POST['admission_reason'],
                ':status'           => $status
            ]);

            // AUTOMATIC: Haddi qofka la seexiyay, sariirta ka dhig Occupied
            if ($status == 'Admitted') {
                $bed_sql = "UPDATE beds SET status = 'Occupied' WHERE bed_id = :bed_id";
                $bed_stm = $conn->prepare($bed_sql);
                $bed_stm->execute([':bed_id' => $_POST['bed_id']]);
            }

            $conn->commit();
            echo json_encode(["status" => true, "msg" => "Patient admission successfully registered!"]);
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Admission Entry
    if ($route == 'update') {
        try {
            $conn->beginTransaction();

            // 1. Soo hel xogta sariirtii hore intaan la beddelin
            $old_stm = $conn->prepare("SELECT bed_id, status FROM patient_admissions WHERE admission_id = :id");
            $old_stm->execute([':id' => $_POST['id']]);
            $old_admission = $old_stm->fetch(PDO::FETCH_ASSOC);

            // 2. Cusboonaysii xogta foomka
            $sql = "UPDATE patient_admissions SET 
                        patient_id = :patient_id, 
                        room_id = :room_id, 
                        bed_id = :bed_id, 
                        admission_date = :admission_date, 
                        admission_reason = :admission_reason, 
                        status = :status 
                    WHERE admission_id = :id";
            $stm = $conn->prepare($sql);

            $status = !empty($_POST['status']) ? $_POST['status'] : 'Admitted';

            $stm->execute([
                ':id'               => $_POST['id'],
                ':patient_id'       => $_POST['patient_id'],
                ':room_id'          => $_POST['room_id'],
                ':bed_id'           => $_POST['bed_id'],
                ':admission_date'   => $_POST['admission_date'],
                ':admission_reason' => $_POST['admission_reason'],
                ':status'           => $status
            ]);

            // 3. AUTOMATIC BED UPDATES
            if ($old_admission) {
                // Haddi sariirta la beddelay, tii hore xorree (Available)
                if ($old_admission['bed_id'] != $_POST['bed_id']) {
                    $free_old = $conn->prepare("UPDATE beds SET status = 'Available' WHERE bed_id = :old_bed");
                    $free_old->execute([':old_bed' => $old_admission['bed_id']]);
                }
            }

            // Haddi status-ku yahay Admitted, sariirta cusub ka dhig Occupied, haddi kale fur (Available)
            if ($status == 'Admitted') {
                $update_new = $conn->prepare("UPDATE beds SET status = 'Occupied' WHERE bed_id = :bed_id");
                $update_new->execute([':bed_id' => $_POST['bed_id']]);
            } else {
                $release_current = $conn->prepare("UPDATE beds SET status = 'Available' WHERE bed_id = :bed_id");
                $release_current->execute([':bed_id' => $_POST['bed_id']]);
            }

            $conn->commit();
            echo json_encode(["status" => true, "msg" => "Admission details updated successfully!"]);
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Admission Entry
    if ($route == 'deleteOperation') {
        try {
            $conn->beginTransaction();
            
            $get_bed = $conn->prepare("SELECT bed_id FROM patient_admissions WHERE admission_id = :id");
            $get_bed->execute([':id' => $_POST['id']]);
            $bed = $get_bed->fetch(PDO::FETCH_ASSOC);
            
            if($bed) {
                $free_bed = $conn->prepare("UPDATE beds SET status = 'Available' WHERE bed_id = :bed_id");
                $free_bed->execute([':bed_id' => $bed['bed_id']]);
            }

            $stm = $conn->prepare("DELETE FROM patient_admissions WHERE admission_id = :id");
            $success = $stm->execute([':id' => $_POST['id']]);
            
            $conn->commit();
            echo json_encode(["status" => $success, "msg" => "Admission folder successfully cleared from registry"]);
        } catch(PDOException $e) {
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
    <title>Inpatient Admissions Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-hospital-user me-2"></i> Inpatient Admissions & Ward Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Ward Allocation Directory</h4>
            <p class="text-muted">Process institutional admissions, verify bed placement codes, and modify case histories.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Admit New Patient
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Admission ID</th>
                            <th>Patient Identity</th>
                            <th>Room / Bed</th>
                            <th>Admission Date</th>
                            <th>Reason / Clinical Notes</th>
                            <th>Status</th>
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
            <form id="admissionForm">
                <div class="modal-header">
                    <h5 class="modal-title">Clinical Intake Manifest</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold">Target Patient File</label>
                            <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Assigned Room Number</label>
                            <select name="room_id" id="room_id" class="form-select shadow-sm" required>
                                <option value="">-- Choose Room First --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Assigned Bed Position</label>
                            <select name="bed_id" id="bed_id" class="form-select shadow-sm" required>
                                <option value="">-- Select Room First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Admission Date & Time</label>
                            <input type="datetime-local" name="admission_date" id="admission_date" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Discharge Status</label>
                            <select name="status" id="status" class="form-select shadow-sm">
                                <option value="Admitted">Admitted</option>
                                <option value="Discharged">Discharged</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold">Primary Admission Reason & Diagnosis Summary</label>
                        <input type="text" name="admission_reason" id="admission_reason" class="form-control shadow-sm" placeholder="e.g. Acute clinical evaluation" required>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Intake Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var activeBedId = 0;

function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let pOptions = '<option value="">-- Choose Patient Chart --</option>';
        res.patients.forEach(p => {
            pOptions += `<option value="${p.user_id}">${p.full_name}</option>`;
        });
        $('#patient_id').html(pOptions);

        let rOptions = '<option value="">-- Choose Room --</option>';
        res.rooms.forEach(r => {
            rOptions += `<option value="${r.room_id}">Room #${r.room_id}</option>`;
        });
        $('#room_id').html(rOptions);
    });
}

function loadBedsByRoom(roomId, currentBedId = 0) {
    if(!roomId) {
        $('#bed_id').html('<option value="">-- Select Room First --</option>');
        return;
    }
    
    $('#bed_id').html('<option value="">Loading available beds...</option>');
    
    $.get(`?url=get_beds_by_room&room_id=${roomId}&current_bed_id=${currentBedId}`, function(beds) {
        let bOptions = '<option value="">-- Choose Bed Assignment --</option>';
        if(beds.length === 0) {
            bOptions = '<option value="">❌ No Available Beds in this Room</option>';
        } else {
            beds.forEach(b => {
                let selected = (b.bed_id == currentBedId) ? 'selected' : '';
                bOptions += `<option value="${b.bed_id}" ${selected}>${b.bed_number}</option>`;
            });
        }
        $('#bed_id').html(bOptions);
    });
}

$('#room_id').change(function() {
    let roomId = $(this).val();
    loadBedsByRoom(roomId, activeBedId);
});

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

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(a => {
            let statusBadge = a.status === 'Admitted' ? 'bg-success text-white' : 'bg-secondary text-white';

            html += `
            <tr>
                <td><strong>#ADM-${a.admission_id}</strong></td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${a.patient_name}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${a.patient_email}</div>
                </td>
                <td>
                    <span class="badge bg-light text-cyan border border-info px-2 py-1">Room ${a.room_number}</span>
                    <span class="badge bg-light text-dark border border-secondary px-2 py-1">Bed ${a.bed_name}</span>
                </td>
                <td><code class="text-dark small fw-bold">${a.admission_date}</code></td>
                <td class="text-start small text-truncate" style="max-width: 250px;" title="${a.admission_reason}">${a.admission_reason}</td>
                <td><span class="badge ${statusBadge} px-3 py-1 rounded-pill">${a.status}</span></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(a)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${a.admission_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

$('#addBtn').click(() => {
    $('#admissionForm')[0].reset();
    $('#id').val('');
    activeBedId = 0;
    $('#bed_id').html('<option value="">-- Select Room First --</option>');
    
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#admission_date').val(now.toISOString().slice(0,16));
    
    $('#patient_id').prop('disabled', false); 
    $('#modal').modal('show');
});

$('#admissionForm').submit(function(e) {
    e.preventDefault();
    
    $('#patient_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#patient_id').prop('disabled', true);
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

$(document).on('click', '.edit', function() {
    let a = JSON.parse($(this).attr('data'));
    $('#id').val(a.admission_id);
    $('#patient_id').val(a.patient_id).prop('disabled', true);
    $('#room_id').val(a.room_id);
    
    activeBedId = a.bed_id;
    loadBedsByRoom(a.room_id, a.bed_id);
    
    $('#admission_date').val(formatDateTimeLocal(a.admission_date));
    $('#admission_reason').val(a.admission_reason);
    $('#status').val(a.status);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Purge entry from admissions files?',
        text: "This removes the physical room/bed reservation record from system logs.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, drop entry files!'
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