<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC
// =========================================================
class Connection {
    private $host = 'localhost';
    private $db_name = 'mental_health_support';
    private $user_name = 'root';
    private $password = '';
    public $db;

    public function __construct() {
        try {
            $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4", $this->user_name, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die(json_encode(["status" => false, "msg" => "Connection failed"]));
        }
    }
}

// In loo maleeyo in bukaanka hadda jira uu yahay ID: 2
$current_patient_id = 2; 

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // 1. Fetch History
    if ($_GET['action'] == 'fetch') {
        $sql = "SELECT mh.*, u.full_name as patient_name 
                FROM medical_history mh 
                JOIN users u ON mh.patient_id = u.user_id 
                WHERE mh.patient_id = :pid
                ORDER BY mh.recorded_at DESC";
        $stm = $conn->prepare($sql);
        $stm->execute([':pid' => $current_patient_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Save Medical Record
    if ($_GET['action'] == 'save') {
        $sql = "INSERT INTO medical_history (patient_id, condition_name, description, treatment_history) 
                VALUES (:pid, :cond, :desc, :treat)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':pid'   => $current_patient_id,
            ':cond'  => $_POST['condition_name'],
            ':desc'  => $_POST['description'],
            ':treat' => $_POST['treatment_history']
        ]);
        echo json_encode(["status" => $success]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --cyan-gradient: linear-gradient(135deg, #00fbff 0%, #00838f 100%);
        }
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .hero-header {
            background: var(--cyan-gradient);
            color: white;
            padding: 60px 0;
            border-radius: 0 0 40px 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .history-card {
            border: none;
            border-radius: 15px;
            background: white;
            position: relative;
            padding-left: 20px;
            border-left: 4px solid #00acc1;
            transition: 0.3s;
        }

        .history-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .date-badge {
            background: #e0f7fa;
            color: #00838f;
            font-weight: bold;
            font-size: 0.75rem;
            padding: 5px 15px;
            border-radius: 50px;
        }

        .condition-title { color: #00838f; font-weight: 700; }
        
        .btn-add {
            background: var(--cyan-gradient);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
        }
        .btn-add:hover { color: white; opacity: 0.9; }
    </style>
</head>
<body>

<div class="hero-header text-center">
    <div class="container">
        <h2 class="fw-bold"><i class="fa-solid fa-notes-medical me-2"></i> Medical History</h2>
        <p class="opacity-75">Keep track of your health conditions and past treatments</p>
    </div>
</div>

<div class="container mt-n4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-secondary">Timeline of Records</h5>
                <button class="btn btn-add shadow-sm" data-bs-toggle="modal" data-bs-target="#historyModal">
                    <i class="fa fa-plus me-1"></i> Add Record
                </button>
            </div>

            <div id="historyList">
                <!-- Records will load here -->
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <form id="historyForm">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold">Add Medical Condition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Condition Name</label>
                        <input type="text" name="condition_name" class="form-control" placeholder="E.g. Chronic Anxiety" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Brief Description (Max 50)</label>
                        <input type="text" name="description" class="form-control" maxlength="50" placeholder="Short description of symptoms" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Treatment History (Max 70)</label>
                        <textarea name="treatment_history" class="form-control" rows="2" maxlength="70" placeholder="Past medications or therapies..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-add w-100">Save History Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function fetchHistory() {
    $.get('?action=fetch', function(data) {
        let html = '';
        data.forEach(h => {
            let date = new Date(h.recorded_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
            html += `
            <div class="card history-card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="date-badge">${date}</span>
                        <i class="fa-solid fa-stethoscope text-info"></i>
                    </div>
                    <h5 class="condition-title">${h.condition_name}</h5>
                    <p class="text-dark small mb-2"><strong>Symptoms:</strong> ${h.description}</p>
                    <div class="bg-light p-2 rounded small">
                        <i class="fa-solid fa-pills me-1 text-secondary"></i> 
                        <strong>Treatment:</strong> ${h.treatment_history}
                    </div>
                </div>
            </div>`;
        });
        $('#historyList').html(html || '<div class="text-center p-5">No history records found.</div>');
    });
}

$('#historyForm').submit(function(e) {
    e.preventDefault();
    $.post('?action=save', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({ icon: 'success', title: 'Record Saved', timer: 1500, showConfirmButton: false });
            $('#historyModal').modal('hide');
            $('#historyForm')[0].reset();
            fetchHistory();
        }
    });
});

$(document).ready(fetchHistory);
</script>
</body>
</html>