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

    // Read Departments
    if ($route == 'display') {
        $sql = "SELECT * FROM departments ORDER BY department_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create
    if ($route == 'create') {
        $sql = "INSERT INTO departments (department_name, description) VALUES (:name, :description)";
        $stm = $conn->prepare($sql);
        
        $description = !empty($_POST['description']) ? $_POST['description'] : null;

        $success = $stm->execute([
            ':name'        => $_POST['department_name'],
            ':description' => $description
        ]);
        echo json_encode(["status" => $success, "msg" => "Department added successfully!"]);
        exit;
    }

    // Update
    if ($route == 'update') {
        $sql = "UPDATE departments SET department_name=:name, description=:description WHERE department_id=:id";
        $stm = $conn->prepare($sql);

        $description = !empty($_POST['description']) ? $_POST['description'] : null;

        $success = $stm->execute([
            ':id'          => $_POST['id'],
            ':name'        => $_POST['department_name'],
            ':description' => $description
        ]);
        echo json_encode(["status" => $success, "msg" => "Department updated successfully!"]);
        exit;
    }

    // Delete
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM departments WHERE department_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Department record deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-building-user me-2"></i> Hospital Department Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Hospital Departments</h4>
            <p class="text-muted">Configure and manage clinical and administrative departments.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Add New Department
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Dept ID</th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Created At</th>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="departmentForm">
                <div class="modal-header">
                    <h5 class="modal-title">Department Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Department Name</label>
                        <input type="text" name="department_name" id="department_name" class="form-control shadow-sm" placeholder="e.g. Outpatient (OPD)" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Description</label>
                        <textarea name="description" id="description" class="form-control shadow-sm" rows="4" placeholder="Briefly describe the department functions..."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Main Table
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(d => {
            let desc = d.description ? d.description : '<span class="text-muted italic">No description provided</span>';
            
            html += `
            <tr>
                <td>${d.department_id}</td>
                <td class="fw-bold text-dark">${d.department_name}</td>
                <td class="text-start text-muted style="max-width: 300px;"><small>${desc}</small></td>
                <td><small>${d.created_at}</small></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(d)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${d.department_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Trigger Create Window
$('#addBtn').click(() => {
    $('#departmentForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

// Submit Form (Insert/Update)
$('#departmentForm').submit(function(e) {
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
    $('#id').val(d.department_id);
    $('#department_name').val(d.department_name);
    $('#description').val(d.description ? d.description : '');
    $('#modal').modal('show');
});

// Delete Execution
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Remove this department?',
        text: "This action cannot be undone. Make sure no staff or doctors are linked to this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete department!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Deleted!', '', 'success');
            });
        }
    });s
});

$(document).ready(() => {
    loadData();
});
</script>

</body>
</html>