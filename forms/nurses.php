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

    // Read Nurses
    if ($route == 'display') {
        $sql = "SELECT n.*, u.full_name, u.email, u.phone, d.department_name
                FROM nurses n
                JOIN users u ON n.user_id = u.user_id
                LEFT JOIN departments d ON n.department_id = d.department_id
                ORDER BY n.nurse_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Active Users with type 'Nurse' + Departments list for dropdowns
    if ($route == 'get_dropdown_data') {
        $p_stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Nurse' AND status='Active'");
        $p_stm->execute();
        $users = $p_stm->fetchAll(PDO::FETCH_ASSOC);

        $d_stm = $conn->prepare("SELECT department_id, department_name FROM departments ORDER BY department_name ASC");
        $d_stm->execute();
        $departments = $d_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["users" => $users, "departments" => $departments]);
        exit;
    }

    // Create
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO nurses (user_id, department_id, qualification, experience_years, shift, start_time, end_time, hire_date, salary)
                    VALUES (:user_id, :department_id, :qualification, :experience_years, :shift, :start_time, :end_time, :hire_date, :salary)";
            $stm = $conn->prepare($sql);

            $stm->execute([
                ':user_id'          => $_POST['user_id'],
                ':department_id'    => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                ':qualification'    => $_POST['qualification'],
                ':experience_years' => !empty($_POST['experience_years']) ? $_POST['experience_years'] : 0,
                ':shift'            => $_POST['shift'],
                ':start_time'       => $_POST['start_time'],
                ':end_time'         => $_POST['end_time'],
                ':hire_date'        => !empty($_POST['hire_date']) ? $_POST['hire_date'] : null,
                ':salary'           => !empty($_POST['salary']) ? $_POST['salary'] : 0.00
            ]);
            echo json_encode(["status" => true, "msg" => "Nurse profile created successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update
    if ($route == 'update') {
        try {
            $sql = "UPDATE nurses SET user_id=:user_id, department_id=:department_id,
                    qualification=:qualification, experience_years=:experience_years,
                    shift=:shift, start_time=:start_time, end_time=:end_time,
                    hire_date=:hire_date, salary=:salary
                    WHERE nurse_id=:id";
            $stm = $conn->prepare($sql);

            $success = $stm->execute([
                ':id'               => $_POST['id'],
                ':user_id'          => $_POST['user_id'],
                ':department_id'    => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                ':qualification'    => $_POST['qualification'],
                ':experience_years' => !empty($_POST['experience_years']) ? $_POST['experience_years'] : 0,
                ':shift'            => $_POST['shift'],
                ':start_time'       => $_POST['start_time'],
                ':end_time'         => $_POST['end_time'],
                ':hire_date'        => !empty($_POST['hire_date']) ? $_POST['hire_date'] : null,
                ':salary'           => !empty($_POST['salary']) ? $_POST['salary'] : 0.00
            ]);
            echo json_encode(["status" => $success, "msg" => "Nurse profile updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM nurses WHERE nurse_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Nurse record deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Management | Glass Orange System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-orange: #ff7a18;
            --dark-orange: #c85a00;
            --gradient-orange: linear-gradient(135deg, #ffb347, #ff7a18, #c85a00);
            --glass-bg: rgba(255, 255, 255, 0.55);
            --glass-border: rgba(255, 255, 255, 0.35);
        }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(160deg, #fff3e6 0%, #ffe0c2 45%, #ffcf9e 100%);
            background-attachment: fixed;
        }

        .navbar-custom {
            background: var(--gradient-orange);
            padding: 20px;
            color: white;
            box-shadow: 0 8px 25px rgba(255, 122, 24, 0.35);
            border-bottom: 4px solid rgba(255,255,255,0.15);
        }

        .glass-card {
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(200, 90, 0, 0.12);
            overflow: hidden;
        }

        .btn-orange {
            background: var(--gradient-orange);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-orange:hover {
            color: white;
            box-shadow: 0 8px 20px rgba(255, 122, 24, 0.45);
            transform: translateY(-2px);
        }

        .table thead {
            background: rgba(255, 179, 71, 0.25);
            color: var(--dark-orange);
        }

        .table tbody tr:hover {
            background: rgba(255, 179, 71, 0.12);
        }

        .badge-orange {
            background: rgba(255, 122, 24, 0.12);
            color: var(--dark-orange);
            border: 1px solid rgba(255, 122, 24, 0.35);
        }

        .badge-shift-morning { background: rgba(255, 193, 7, 0.15); color: #b8860b; border: 1px solid rgba(255,193,7,0.4); }
        .badge-shift-afternoon { background: rgba(255, 122, 24, 0.15); color: var(--dark-orange); border: 1px solid rgba(255,122,24,0.4); }
        .badge-shift-night { background: rgba(90, 90, 150, 0.15); color: #4b4b8f; border: 1px solid rgba(90,90,150,0.4); }

        .modal-content {
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .modal-header {
            background: var(--gradient-orange);
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 0.2rem rgba(255, 122, 24, 0.2);
        }

        .text-orange { color: var(--dark-orange); }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="bi bi-clipboard2-pulse me-2"></i> Mental Health Nurses Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-orange">Nurse Profiles & Shift Assignments</h4>
            <p class="text-muted">Manage department assignment, shifts, qualifications, and salary.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-orange px-4 py-2 shadow-sm" id="addBtn">
                <i class="bi bi-plus-circle me-2"></i> Register New Nurse
            </button>
        </div>
    </div>

    <div class="card glass-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nurse Name</th>
                            <th>Department</th>
                            <th>Qualification</th>
                            <th>Experience</th>
                            <th>Shift</th>
                            <th>Working Hours</th>
                            <th>Hire Date</th>
                            <th>Salary ($)</th>
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
            <form id="nurseForm">
                <div class="modal-header">
                    <h5 class="modal-title">Nurse Profile File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Select User Account (Nurse)</label>
                            <select name="user_id" id="user_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Department</label>
                            <select name="department_id" id="department_id" class="form-select shadow-sm"></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Qualification</label>
                            <input type="text" name="qualification" id="qualification" class="form-control shadow-sm" placeholder="e.g. BSc Nursing" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Experience (Years)</label>
                            <input type="number" name="experience_years" id="experience_years" min="0" class="form-control shadow-sm" value="0">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Shift</label>
                            <select name="shift" id="shift" class="form-select shadow-sm">
                                <option value="Morning">Morning</option>
                                <option value="Afternoon">Afternoon</option>
                                <option value="Night">Night</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control shadow-sm" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small fw-bold">Hire Date</label>
                            <input type="date" name="hire_date" id="hire_date" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Salary ($)</label>
                            <input type="number" step="0.01" name="salary" id="salary" min="0" class="form-control shadow-sm" value="0.00">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-orange px-5">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Available User Accounts + Departments
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let u = '<option value="">-- Choose Linked User Account --</option>';
        res.users.forEach(user => {
            u += `<option value="${user.user_id}">${user.full_name}</option>`;
        });
        $('#user_id').html(u);

        let d = '<option value="">-- No Department --</option>';
        res.departments.forEach(dep => {
            d += `<option value="${dep.department_id}">${dep.department_name}</option>`;
        });
        $('#department_id').html(d);
    });
}

function shiftBadgeClass(shift) {
    if (shift === 'Morning') return 'badge-shift-morning';
    if (shift === 'Afternoon') return 'badge-shift-afternoon';
    return 'badge-shift-night';
}

// Load Main Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(n => {
            html += `
            <tr>
                <td>${n.nurse_id}</td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${n.full_name}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${n.email}</div>
                </td>
                <td>${n.department_name ?? '<span class="text-muted">Unassigned</span>'}</td>
                <td>${n.qualification}</td>
                <td><span class="fw-semibold">${n.experience_years} Years</span></td>
                <td><span class="badge ${shiftBadgeClass(n.shift)} px-2 py-1">${n.shift}</span></td>
                <td><code class="text-dark">${n.start_time} - ${n.end_time}</code></td>
                <td>${n.hire_date ?? ''}</td>
                <td class="text-success fw-bold">$${parseFloat(n.salary).toFixed(2)}</td>
                <td>
                    <i class="bi bi-pencil-square text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(n)}'></i>
                    <i class="bi bi-trash text-danger del" style="cursor:pointer" data-id="${n.nurse_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Window
$('#addBtn').click(() => {
    $('#nurseForm')[0].reset();
    $('#id').val('');
    $('#user_id').prop('disabled', false);
    $('#modal').modal('show');
});

// Post Request
$('#nurseForm').submit(function(e) {
    e.preventDefault();

    $('#user_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#user_id').prop('disabled', true);
    }

    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('show');
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Mode Capture
$(document).on('click', '.edit', function() {
    let n = JSON.parse($(this).attr('data'));
    $('#id').val(n.nurse_id);
    $('#user_id').val(n.user_id).prop('disabled', true);
    $('#department_id').val(n.department_id ?? '');
    $('#qualification').val(n.qualification);
    $('#experience_years').val(n.experience_years);
    $('#shift').val(n.shift);
    $('#start_time').val(n.start_time);
    $('#end_time').val(n.end_time);
    $('#hire_date').val(n.hire_date);
    $('#salary').val(n.salary);
    $('#modal').modal('show');
});

// Delete Row
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this nurse profile?',
        text: "This safely removes nursing system metrics. The primary user account remains safe.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c85a00',
        confirmButtonText: 'Yes, purge profile!'
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
