<?php
// 1. START SESSION
session_start();

// MAA_OBO: Line-kan hoose ka saar comment-ka si aad u tijaabiso haddii aysan jirin Login Page wali.
// $_SESSION['user_id'] = 1; 

// =========================================================
// 2. DATABASE CONNECTION & LOGIC (PHP)
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

$db = new Connection();
$conn = $db->db;

$current_user_name = "Gidbiye aan la aqoonsan";
$current_user_id = 0;

if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $user_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = :id");
    $user_stmt->execute([':id' => $current_user_id]);
    $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_row) {
        $current_user_name = $user_row['full_name'];
    }
}

// ROUTER LOGIC FOR AJAX CALLS
if (isset($_GET['url'])) {
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // Read Requests
    if ($route == 'display') {
        $sql = "SELECT er.*, u.full_name 
                FROM emergency_requests er 
                JOIN users u ON er.user_id = u.user_id 
                ORDER BY er.request_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Request
    if ($route == 'create') {
        if ($current_user_id == 0) {
            echo json_encode(["status" => false, "msg" => "Fadlan marka hore nidaamka soo gal (Login)!"]);
            exit;
        }

        $sql = "INSERT INTO emergency_requests (user_id, service_type, emergency_description, location, phone, request_status)
                VALUES (:user_id, :service_type, :emergency_description, :location, :phone, :request_status)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':user_id'               => $current_user_id,
            ':service_type'          => $_POST['service_type'],
            ':emergency_description' => $_POST['emergency_description'],
            ':location'              => $_POST['location'],
            ':phone'                 => $_POST['phone'],
            ':request_status'        => 'Pending' // Si toos ah ayuu Pending u noqonayaa marka la abuurayo
        ]);
        echo json_encode(["status" => $success, "msg" => "Emergency request submitted!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Care Dashboard | Cyan Care</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }

        body { background-color: #f4f9fa; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.25);
        }

        .emergency-card {
            border: none;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .emergency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 188, 212, 0.15);
        }

        .border-pending { border-left: 6px solid #ffc107; }
        .border-assigned { border-left: 6px solid #0d6efd; }
        .border-ontheway { border-left: 6px solid #6c757d; }
        .border-completed { border-left: 6px solid #198754; }
        .border-cancelled { border-left: 6px solid #dc3545; }

        .btn-cyan {
            background: var(--gradient-cyan);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-cyan:hover {
            color: white;
            box-shadow: 0 5px 15px rgba(0,188,212,0.4);
        }

        .phone-action-btn {
            color: var(--dark-cyan);
            font-weight: 600;
            text-decoration: none;
            padding: 8px 14px;
            background: #e0f7fa;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .phone-action-btn:hover { background: var(--primary-cyan); color: white; }

        .status-badge {
            font-size: 0.75rem;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-assigned { background-color: #cce5ff; color: #004085; }
        .status-ontheway { background-color: #e2e3e5; color: #383d41; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .user-session-card {
            background: rgba(0, 188, 212, 0.08);
            border: 1px dashed var(--primary-cyan);
            border-radius: 12px;
            padding: 12px 20px;
        }

        .card-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a0aec0;
            font-weight: 700;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-4 text-center">
    <h3><i class="fa-solid fa-star-of-life class-pulse me-2"></i> Real-time Emergency Dispatch Center</h3>
    <small class="text-white-50">Modernized User Interface Logs</small>
</nav>

<div class="container px-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 bg-white p-3 rounded-4 shadow-sm">
        <div>
            <span class="text-muted small">Active Session:</span> 
            <strong class="text-dark ms-1"><i class="fa-solid fa-circle-user text-info me-1"></i> <?php echo htmlspecialchars($current_user_name); ?></strong>
            <span class="badge bg-secondary ms-2 small">ID: <?php echo $current_user_id; ?></span>
        </div>
        <div>
            <button class="btn btn-cyan px-4 py-2" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Report Emergency Incident
            </button>
        </div>
    </div>

    <div class="row g-4" id="requestsGrid"></div>
</div>

<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form id="requestForm">
                <div class="modal-header text-white" style="background: var(--gradient-cyan); border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title"><i class="fa-solid fa-kit-medical me-2"></i> Emergency Incident Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <div class="user-session-card">
                            <i class="fa-solid fa-id-card text-cyan me-1"></i> 
                            Codsiga waxaa loo xaraynayaa: <strong class="text-dark"><?php echo htmlspecialchars($current_user_name); ?></strong>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold mb-1">Service Required</label>
                            <select name="service_type" id="service_type" class="form-select" required>
                                <option value="Ambulance"> Amos Ambulance Service</option>
                                <option value="Nursing">🩺 Home Nursing care</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold mb-1">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Contact number" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold mb-1">Location Address</label>
                            <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Hodan, near Digfeer Hospital" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold mb-1">Emergency Description & Symptoms</label>
                            <textarea name="emergency_description" id="emergency_description" class="form-control" rows="3" placeholder="Describe the crisis status..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-4">Dispatch Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function getStatusClass(status) {
    switch(status) {
        case 'Pending': return 'status-pending';
        case 'Assigned': return 'status-assigned';
        case 'On The Way': return 'status-ontheway';
        case 'Completed': return 'status-completed';
        case 'Cancelled': return 'status-cancelled';
        default: return 'status-pending';
    }
}

function getBorderClass(status) {
    switch(status) {
        case 'Pending': return 'border-pending';
        case 'Assigned': return 'border-assigned';
        case 'On The Way': return 'border-ontheway';
        case 'Completed': return 'border-completed';
        case 'Cancelled': return 'border-cancelled';
        default: return 'border-pending';
    }
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        if(res.length === 0) {
            html = `<div class="col-12 text-center py-5">
                        <div class="text-muted"><i class="fa-solid fa-folder-open fs-1 mb-2"></i><br>No active critical requests found.</div>
                    </div>`;
            $('#requestsGrid').html(html);
            return;
        }

        res.forEach(r => {
            let serviceIcon = r.service_type === 'Ambulance' ? '🚑' : '🩺';
            html += `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 emergency-card ${getBorderClass(r.request_status)}">
                    <div class="card-body d-flex flex-column p-4">
                        
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="card-label">Incident ID</span>
                                <div class="fw-bold text-dark">#REQ-${r.request_id}</div>
                            </div>
                            <span class="status-badge ${getStatusClass(r.request_status)}">${r.request_status}</span>
                        </div>

                        <div class="mb-3">
                            <span class="card-label">Patient Name</span>
                            <div class="fw-bold text-dark"><i class="fa-regular fa-user text-muted me-1"></i> ${r.full_name}</div>
                            <small class="text-muted">User ID: ${r.user_id}</small>
                        </div>

                        <div class="mb-3">
                            <span class="card-label">Service Required</span>
                            <div class="p-2 bg-light rounded-3 fw-bold text-secondary" style="font-size:0.9rem;">
                                ${serviceIcon} ${r.service_type} Service
                            </div>
                        </div>

                        <div class="mb-3 flex-grow-1">
                            <span class="card-label">Medical Complaint</span>
                            <p class="text-secondary small m-0 text-wrap">${r.emergency_description}</p>
                        </div>

                        <hr class="text-muted my-3 opacity-25">

                        <div class="mb-3">
                            <div class="small text-dark mb-2">
                                <i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong>Location:</strong> ${r.location}
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="fa-regular fa-clock me-1"></i> ${r.requested_at}
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                            <a href="tel:${r.phone}" class="phone-link phone-action-btn small">
                                <i class="fa-solid fa-phone"></i> ${r.phone}
                            </a>
                        </div>

                    </div>
                </div>
            </div>`;
        });
        $('#requestsGrid').html(html);
    });
}

$('#addBtn').click(() => {
    $('#requestForm')[0].reset();
    $('#modal').modal('show');
});

$('#requestForm').submit(function(e) {
    e.preventDefault();
    $.post('?url=create', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Dispatched', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

$(document).ready(loadData);
</script>
</body>
</html>