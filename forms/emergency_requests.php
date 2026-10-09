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

    // Read Requests
    if ($route == 'display') {
        $stm = $conn->prepare("SELECT * FROM emergency_requests ORDER BY request_id DESC");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Request
    if ($route == 'create') {
        $sql = "INSERT INTO emergency_requests (user_id, service_type, emergency_description, location, phone, request_status)
                VALUES (:user_id, :service_type, :emergency_description, :location, :phone, :request_status)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':user_id'               => $_POST['user_id'],
            ':service_type'          => $_POST['service_type'],
            ':emergency_description' => $_POST['emergency_description'],
            ':location'              => $_POST['location'],
            ':phone'                 => $_POST['phone'],
            ':request_status'        => $_POST['request_status'] ?? 'Pending'
        ]);
        echo json_encode(["status" => $success, "msg" => "Emergency request submitted!"]);
        exit;
    }

    // Update Request
    if ($route == 'update') {
        $sql = "UPDATE emergency_requests SET user_id=:user_id, service_type=:service_type, 
                emergency_description=:emergency_description, location=:location, phone=:phone, 
                request_status=:request_status WHERE request_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'                    => $_POST['id'],
            ':user_id'               => $_POST['user_id'],
            ':service_type'          => $_POST['service_type'],
            ':emergency_description' => $_POST['emergency_description'],
            ':location'              => $_POST['location'],
            ':phone'                 => $_POST['phone'],
            ':request_status'        => $_POST['request_status']
        ]);
        echo json_encode(["status" => $success, "msg" => "Request updated successfully!"]);
        exit;
    }

    // Delete Request
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM emergency_requests WHERE request_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Request removed from logs"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Requests | Cyan Care</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }

        body { background-color: #f0f7f8; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 25px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.3);
            border-bottom: 4px solid rgba(0,0,0,0.1);
        }

        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            background: white;
        }

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
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,188,212,0.4);
        }

        .phone-link {
            color: var(--dark-cyan);
            font-weight: bold;
            text-decoration: none;
            padding: 5px 10px;
            background: #e0f7fa;
            border-radius: 5px;
            display: inline-block;
        }

        .phone-link:hover { background: var(--primary-cyan); color: white; }

        /* Dynamic Status Badges Styles */
        .status-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-assigned { background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .status-ontheway { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .status-completed { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .service-icon {
            font-size: 1.1rem;
            margin-right: 5px;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-star-of-life class-pulse me-2"></i> Real-time Emergency Dispatch Dashboard</h3>
</nav>

<div class="container-fluid px-5">
    <div class="row mb-4 align-items-center">
        <div class="col-6">
            <h4 class="text-dark m-0"><i class="fa-solid fa-list-check text-muted me-2"></i>Active Emergency Requests</h4>
        </div>
        <div class="col-6 text-end">
            <button class="btn btn-cyan px-4" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> New Emergency Call
            </button>
        </div>
    </div>

    <div class="card main-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Req ID</th>
                        <th>User ID</th>
                        <th>Service Type</th>
                        <th>Emergency Description</th>
                        <th>Location & Contact</th>
                        <th>Status</th>
                        <th>Requested At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="requestForm">
                <div class="modal-header text-white" style="background: var(--gradient-cyan)">
                    <h5 class="modal-title"><i class="fa-solid fa-kit-medical me-2"></i> Emergency Incident Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">User / Patient ID</label>
                            <input type="number" name="user_id" id="user_id" class="form-control" placeholder="e.g. 102" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Service Required</label>
                            <select name="service_type" id="service_type" class="form-select" required>
                                <option value="Ambulance">🚑 Ambulance Service</option>
                                <option value="Nursing">🩺 Home Nursing care</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Contact number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Request Status</label>
                            <select name="request_status" id="request_status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Assigned">Assigned</option>
                                <option value="On The Way">On The Way</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold">Location Address</label>
                            <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Hodan, near Digfeer Hospital" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold">Emergency Description & Symptoms</label>
                            <textarea name="emergency_description" id="emergency_description" class="form-control" rows="3" placeholder="Describe the crisis status..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
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

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(r => {
            let serviceIcon = r.service_type === 'Ambulance' ? '🚑' : '🩺';
            html += `
            <tr>
                <td><strong>#REQ-${r.request_id}</strong></td>
                <td><span class="badge bg-secondary p-2">UID: ${r.user_id}</span></td>
                <td><span class="fw-bold">${serviceIcon} ${r.service_type}</span></td>
                <td class="text-start text-wrap" style="max-width: 250px;">${r.emergency_description}</td>
                <td>
                    <div class="mb-1 text-muted small"><i class="fa-solid fa-location-dot me-1 text-danger"></i> ${r.location}</div>
                    <a href="tel:${r.phone}" class="phone-link"><i class="fa-solid fa-phone me-1"></i> ${r.phone}</a>
                </td>
                <td><span class="status-badge ${getStatusClass(r.request_status)}">${r.request_status}</span></td>
                <td class="small text-muted">${r.requested_at}</td>
                <td>
                    <button class="btn btn-sm text-info edit" data='${JSON.stringify(r)}'><i class="fa-solid fa-pen-to-square fs-5"></i></button>
                    <button class="btn btn-sm text-danger del" data-id="${r.request_id}"><i class="fa-solid fa-trash-can fs-5"></i></button>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="8" class="py-4 text-muted">No active critical requests found.</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#requestForm')[0].reset();
    $('#id').val('');
    $('#request_status').val('Pending').prop('disabled', true); // Defauting status for new records
    $('#modal').modal('show');
});

$('#requestForm').submit(function(e) {
    e.preventDefault();
    $('#request_status').prop('disabled', false); // re-enable before serialize if it was disabled
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Dispatched', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        }
    });
});

$(document).on('click', '.edit', function() {
    let r = JSON.parse($(this).attr('data'));
    $('#id').val(r.request_id);
    $('#user_id').val(r.user_id);
    $('#service_type').val(r.service_type);
    $('#phone').val(r.phone);
    $('#location').val(r.location);
    $('#emergency_description').val(r.emergency_description);
    $('#request_status').val(r.request_status).prop('disabled', false);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently remove the emergency log from the screen.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete log'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Removed!', 'Emergency request deleted.', 'success');
            });
        }
    });
});

$(document).ready(loadData);
</script>
</body>
</html>