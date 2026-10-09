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

    // Read Paymentss
    if ($route == 'display') {
        $sql = "SELECT p.*, u.full_name AS patient_name, a.appointment_id 
                FROM payments p
                JOIN users u ON p.user_id = u.user_id
                JOIN appointments a ON p.appointment_id = a.appointment_id
                ORDER BY p.payment_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Data for Dropdowns (Patients & Appointments)
    if ($route == 'get_initial_data') {
        $data = [];
        // Get Patients
        $stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Patient' AND status='Active'");
        $stm->execute();
        $data['patients'] = $stm->fetchAll(PDO::FETCH_ASSOC);

        // Get Appointments
        $stm = $conn->prepare("SELECT appointment_id FROM appointments");
        $stm->execute();
        $data['appointments'] = $stm->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($data);
        exit;
    }

    // Create Payment
    if ($route == 'create') {
        $sql = "INSERT INTO payments (appointment_id, user_id, amount, method, transaction_id, status)
                VALUES (:a_id, :u_id, :amount, :method, :t_id, :status)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':a_id'    => $_POST['appointment_id'],
            ':u_id'    => $_POST['user_id'],
            ':amount'  => $_POST['amount'],
            ':method'  => $_POST['method'],
            ':t_id'    => $_POST['transaction_id'],
            ':status'  => $_POST['status']
        ]);
        echo json_encode(["status" => $success, "msg" => "Payment recorded!"]);
        exit;
    }

    // Update Payment
    if ($route == 'update') {
        $sql = "UPDATE payments SET appointment_id=:a_id, user_id=:u_id, amount=:amount, 
                method=:method, transaction_id=:t_id, status=:status 
                WHERE payment_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'      => $_POST['id'],
            ':a_id'    => $_POST['appointment_id'],
            ':u_id'    => $_POST['user_id'],
            ':amount'  => $_POST['amount'],
            ':method'  => $_POST['method'],
            ':t_id'    => $_POST['transaction_id'],
            ':status'  => $_POST['status']
        ]);
        echo json_encode(["status" => $success, "msg" => "Payment updated!"]);
        exit;
    }

    // Delete Payment
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM payments WHERE payment_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Payment deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Cyan System</title>
    
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

        .status-completed { color: #2e7d32; font-weight: bold; }
        .status-pending { color: #f9a825; font-weight: bold; }
        .status-failed { color: #c62828; font-weight: bold; }

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
    <h3><i class="fa-solid fa-file-invoice-dollar me-2"></i> Financial Transactions</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Billing & Payments</h4>
            <p class="text-muted">Track patient fees and transaction statuses.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Record Payment
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Appt ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="paymentForm">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Select Patient</label>
                            <select name="user_id" id="user_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Appointment ID</label>
                            <select name="appointment_id" id="appointment_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Amount ($)</label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control shadow-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Payment Method</label>
                            <select name="method" id="method" class="form-select shadow-sm">
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="PayPal">PayPal</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Transaction ID</label>
                            <input type="text" name="transaction_id" id="transaction_id" class="form-control shadow-sm" placeholder="e.g. TXN-12345">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Status</label>
                            <select name="status" id="status" class="form-select shadow-sm">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadInitialData() {
    $.get('?url=get_initial_data', function(res) {
        let p = '<option value="">Select Patient</option>';
        res.patients.forEach(u => p += `<option value="${u.user_id}">${u.full_name}</option>`);
        $('#user_id').html(p);

        let a = '<option value="">Select Appointment</option>';
        res.appointments.forEach(appt => a += `<option value="${appt.appointment_id}">ID: ${appt.appointment_id}</option>`);
        $('#appointment_id').html(a);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(p => {
            let statusClass = 'status-' + p.status.toLowerCase();
            html += `
            <tr>
                <td>${p.payment_id}</td>
                <td class="fw-bold">${p.patient_name}</td>
                <td><span class="badge bg-light text-dark">#${p.appointment_id}</span></td>
                <td class="text-primary fw-bold">$${p.amount}</td>
                <td>${p.method}</td>
                <td><small class="text-muted">${p.transaction_id || 'N/A'}</small></td>
                <td><span class="${statusClass}">${p.status}</span></td>
                <td><small>${p.payment_date}</small></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(p)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${p.payment_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="9">No payments found</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#paymentForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

$('#paymentForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        }
    });
});

$(document).on('click', '.edit', function() {
    let p = JSON.parse($(this).attr('data'));
    $('#id').val(p.payment_id);
    $('#appointment_id').val(p.appointment_id);
    $('#user_id').val(p.user_id);
    $('#amount').val(p.amount);
    $('#method').val(p.method);
    $('#transaction_id').val(p.transaction_id);
    $('#status').val(p.status);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete transaction?',
        text: "This action cannot be undone!",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete'
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
    loadInitialData();
    loadData();
});
</script>
</body>
</html>