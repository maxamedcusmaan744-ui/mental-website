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

    // Read Staff Ledger
    if ($route == 'display') {
        $sql = "SELECT s.*, 
                       u.full_name AS staff_name, 
                       d.department_name 
                FROM staff s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN departments d ON s.department_id = d.department_id
                ORDER BY s.staff_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Fetch Relational Information For Choice Selectors
    if ($route == 'get_dropdown_data') {
        $data = [];
        
        // Users (Eligible for assignment - sorted alphabetically)
        $sqlUsers = "SELECT user_id, full_name FROM users WHERE user_type='Staff' AND status='Active'";
        $stm = $conn->prepare($sqlUsers);
        $stm->execute();
        $data['users'] = $stm->fetchAll(PDO::FETCH_ASSOC);
        
        // Departments
        $sqlDeps = "SELECT department_id, department_name FROM departments ORDER BY department_name ASC";
        $stm = $conn->prepare($sqlDeps);
        $stm->execute();
        $data['departments'] = $stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($data);
        exit;
    }

    // Create Staff Entry
    if ($route == 'create') {
        try {
            // Check if user_id is already assigned to a staff profile
            $checkSql = "SELECT COUNT(*) FROM staff WHERE user_id = :user_id";
            $checkStm = $conn->prepare($checkSql);
            $checkStm->execute([':user_id' => $_POST['user_id']]);
            
            if ($checkStm->fetchColumn() > 0) {
                echo json_encode(["status" => false, "msg" => "Error: This user is already assigned to an existing staff profile entry."]);
                exit;
            }

            // Inbilaaw Transaction si hadii mid qaldamo kan kalena u tirtirmo
            $conn->beginTransaction();

            $sql = "INSERT INTO staff (user_id, department_id, position, hire_date, salary, shift, start_time, end_time)
                    VALUES (:user_id, :department_id, :position, :hire_date, :salary, :shift, :start_time, :end_time)";
            $stm = $conn->prepare($sql);
            
            $depId = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
            $hireDate = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            $salary = !empty($_POST['salary']) ? $_POST['salary'] : null;
            $shift = !empty($_POST['shift']) ? $_POST['shift'] : 'Morning';
            $startTime = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
            $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;

            $stm->execute([
                ':user_id'       => $_POST['user_id'],
                ':department_id' => $depId,
                ':position'      => $_POST['position'],
                ':hire_date'     => $hireDate,
                ':salary'        => $salary,
                ':shift'         => $shift,
                ':start_time'    => $startTime,
                ':end_time'      => $endTime
            ]);

            // CUSBOONAYSIINTA USER TYPE: Waxaa user_type-ka qofka laga dhigayaa Staff
            $updateUserSql = "UPDATE users SET user_type = 'Staff' WHERE user_id = :user_id";
            $updateUserStm = $conn->prepare($updateUserSql);
            $updateUserStm->execute([':user_id' => $_POST['user_id']]);

            $conn->commit();
            echo json_encode(["status" => true, "msg" => "New staff profile registered and user type updated to Staff!"]);
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Staff Entry
    if ($route == 'update') {
        try {
            $sql = "UPDATE staff SET 
                        department_id = :department_id, 
                        position = :position, 
                        hire_date = :hire_date, 
                        salary = :salary,
                        shift = :shift,
                        start_time = :start_time,
                        end_time = :end_time
                    WHERE staff_id = :id";
            $stm = $conn->prepare($sql);

            $depId = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
            $hireDate = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            $salary = !empty($_POST['salary']) ? $_POST['salary'] : null;
            $shift = !empty($_POST['shift']) ? $_POST['shift'] : 'Morning';
            $startTime = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
            $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;

            $success = $stm->execute([
                ':id'            => $_POST['id'],
                ':department_id' => $depId,
                ':position'      => $_POST['position'],
                ':hire_date'     => $hireDate,
                ':salary'        => $salary,
                ':shift'         => $shift,
                ':start_time'    => $startTime,
                ':end_time'      => $endTime
            ]);
            echo json_encode(["status" => $success, "msg" => "Staff organizational profiling modified successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Staff Entry
    if ($route == 'deleteOperation') {
        try {
            $stm = $conn->prepare("DELETE FROM staff WHERE staff_id = :id");
            $success = $stm->execute([':id' => $_POST['id']]);
            echo json_encode(["status" => $success, "msg" => "Staff organizational placement cleared out of corporate directory records."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: Cannot delete profile due to system dependencies."]);
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
    <title>Internal Staff Profiling Directory | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-id-card me-2"></i> Clinical Administration & Staff Resource Directory</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Human Resources Matrix</h4>
            <p class="text-muted">Maintain departmental assignments, manage position scaling indexes, and monitor onboarding timelines.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Register New Staff Placement
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Identity Profile</th>
                            <th>Assigned Department</th>
                            <th>Corporate Position</th>
                            <th>Shift / Hours</th>
                            <th>Onboarding Date</th>
                            <th>Salary Metric</th>
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
            <form id="staffForm">
                <div class="modal-header">
                    <h5 class="modal-title">Staff Placement Charter</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Select Target System User Profile</label>
                            <select name="user_id" id="user_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Allocated Department Division</label>
                            <select name="department_id" id="department_id" class="form-select shadow-sm"></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold">Designated Position Title</label>
                            <input type="text" name="position" id="position" class="form-control shadow-sm" placeholder="e.g. Chief Psychiatric Specialist, Clinical Consultant" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Assigned Shift</label>
                            <select name="shift" id="shift" class="form-select shadow-sm">
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Night">Night</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Duty Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Duty End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control shadow-sm">
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="small fw-bold">Contractual Effective Onboarding Date</label>
                            <input type="date" name="hire_date" id="hire_date" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Annual/Monthly Fixed Base Salary ($)</label>
                            <input type="number" step="0.01" name="salary" id="salary" class="form-control shadow-sm" placeholder="e.g. 75000.00">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Placement Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        let userOpts = '<option value="">-- Bind User Profile Entity --</option>';
        res.users.forEach(u => {
            userOpts += `<option value="${u.user_id}">${u.full_name} (#USR-${u.user_id})</option>`;
        });
        $('#user_id').html(userOpts);

        let depOpts = '<option value="">-- Unassigned / Float Status --</option>';
        res.departments.forEach(d => {
            depOpts += `<option value="${d.department_id}">${d.department_name}</option>`;
        });
        $('#department_id').html(depOpts);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(s => {
            let depLabel = s.department_name ? `<span class="fw-semibold text-dark">${s.department_name}</span>` : '<span class="text-danger small italic">No Department Linked</span>';
            let formattedSalary = s.salary ? `$${parseFloat(s.salary).toLocaleString(undefined, {minimumFractionDigits: 2})}` : '<span class="text-muted small">Not Disclosed</span>';
            let formattedDate = s.hire_date ? `<code>${s.hire_date}</code>` : '<span class="text-muted small">N/A</span>';
            
            // Shift and Time formatting
            let shiftBadgeColor = s.shift === 'Morning' ? 'bg-info' : (s.shift === 'Evening' ? 'bg-warning' : 'bg-dark');
            let dutyHours = (s.start_time && s.end_time) ? `<div class="small text-muted">${s.start_time.substring(0,5)} - ${s.end_time.substring(0,5)}</div>` : '';
            let shiftLabel = `<span class="badge ${shiftBadgeColor} text-white">${s.shift}</span>${dutyHours}`;

            html += `
            <tr>
                <td><strong>#STF-${s.staff_id}</strong></td>
                <td class="fw-bold text-start text-dark"><i class="fa-solid fa-user-tie me-2 text-cyan"></i>${s.staff_name}</td>
                <td class="text-start">${depLabel}</td>
                <td class="small fw-semibold text-secondary text-start"><i class="fa-solid fa-briefcase me-1"></i> ${s.position}</td>
                <td>${shiftLabel}</td>
                <td>${formattedDate}</td>
                <td><span class="badge bg-light text-success border border-success-subtle px-2 py-1 fw-bold">${formattedSalary}</span></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(s)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${s.staff_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

$('#addBtn').click(() => {
    $('#staffForm')[0].reset();
    $('#id').val('');
    
    let today = new Date().toISOString().split('T')[0];
    $('#hire_date').val(today);
    $('#shift').val('Morning');
    
    $('#user_id').prop('disabled', false);
    $('#modal').modal('show');
});

$('#staffForm').submit(function(e) {
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
            $('#modal').modal('hide');
        } else {
            Swal.fire('Action Revoked', res.msg, 'error');
        }
    });
});

$(document).on('click', '.edit', function() {
    let s = JSON.parse($(this).attr('data'));
    $('#id').val(s.staff_id);
    $('#user_id').val(s.user_id).prop('disabled', true);
    $('#department_id').val(s.department_id ? s.department_id : '');
    $('#position').val(s.position);
    $('#shift').val(s.shift ? s.shift : 'Morning');
    $('#start_time').val(s.start_time ? s.start_time : '');
    $('#end_time').val(s.end_time ? s.end_time : '');
    $('#hire_date').val(s.hire_date ? s.hire_date : '');
    $('#salary').val(s.salary ? s.salary : '');
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Revoke staff profiling assignment?',
        text: "This removes the user's staffing matrix attributes safely, respecting structural system constraints.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, terminate assignment!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, function(res) {
                if(res.status) {
                    loadData();
                    Swal.fire('Terminated!', res.msg, 'success');
                } else {
                    Swal.fire('Failed', res.msg, 'error');
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