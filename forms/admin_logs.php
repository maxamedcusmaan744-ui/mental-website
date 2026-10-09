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

    // Read Admin Logs
    if ($route == 'display') {
        $sql = "SELECT l.*, u.full_name AS admin_name 
                FROM admin_logs l
                JOIN users u ON l.admin_id = u.user_id
                ORDER BY l.log_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Admins only for Dropdown (Assuming Role or Type in Users table)
    if ($route == 'get_admins') {
        $stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Log
    if ($route == 'create') {
        $sql = "INSERT INTO admin_logs (admin_id, action) VALUES (:a_id, :action)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':a_id'   => $_POST['admin_id'],
            ':action' => $_POST['action']
        ]);
        echo json_encode(["status" => $success, "msg" => "Log recorded!"]);
        exit;
    }

    // Delete Log
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM admin_logs WHERE log_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Log entry deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs | Cyan System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }

        body { background-color: #f4fbfc; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.3);
        }

        .main-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            background: white;
        }

        .btn-cyan {
            background: var(--gradient-cyan);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-cyan:hover {
            color: white;
            box-shadow: 0 5px 15px rgba(0,188,212,0.3);
        }

        .action-text {
            color: #444;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
        }

        .time-badge {
            background: #e0f2f1;
            color: #00796b;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-list-check me-2"></i> System Audit Logs</h3>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4 class="text-secondary"><i class="fa-solid fa-shield-halved me-2"></i>Admin Activities</h4>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan" id="addBtn">
                <i class="fa-solid fa-plus me-1"></i> Add Manual Log
            </button>
        </div>
    </div>

    <div class="card main-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Log ID</th>
                        <th>Admin Name</th>
                        <th>Action Performed</th>
                        <th>Timestamp</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="logForm">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Record New Activity</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Admin User</label>
                        <select name="admin_id" id="admin_id" class="form-select" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Action Description</label>
                        <input type="text" name="action" class="form-control" placeholder="e.g. Updated User Status" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-4">Save Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadAdmins() {
    $.get('?url=get_admins', function(res) {
        let html = '<option value="">-- Choose Admin --</option>';
        res.forEach(a => html += `<option value="${a.user_id}">${a.full_name}</option>`);
        $('#admin_id').html(html);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(l => {
            html += `
            <tr>
                <td>#${l.log_id}</td>
                <td><i class="fa-solid fa-user-tie me-2 text-info"></i><strong>${l.admin_name}</strong></td>
                <td class="action-text text-start">${l.action}</td>
                <td><span class="time-badge">${l.log_time}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-danger del" data-id="${l.log_id}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="5">No logs recorded yet.</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#logForm')[0].reset();
    $('#modal').modal('show');
});

$('#logForm').submit(function(e) {
    e.preventDefault();
    $.post('?url=create', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        }
    });
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete Log Entry?',
        text: "Audit logs are usually kept for security reasons!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Deleted!', 'Log has been removed.', 'success');
            });
        }
    });
});

$(document).ready(() => {
    loadAdmins();
    loadData();
});
</script>
</body>
</html>