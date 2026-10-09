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

    // Read Bills (Traversing through patient_admissions to get user details)
    if ($route == 'display') {
        $sql = "SELECT b.*, u.full_name, u.email 
                FROM bills b
                JOIN patient_admissions pa ON b.admission_id = pa.admission_id
                JOIN users u ON pa.patient_id = u.user_id
                ORDER BY b.bill_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Active Admitted Patients for Dropdown
    if ($route == 'get_dropdown_data') {
        $p_stm = $conn->prepare("SELECT pa.admission_id, u.full_name, u.email
                                 FROM patient_admissions pa
                                 JOIN users u ON pa.patient_id = u.user_id 
                                 WHERE pa.status='Admitted'");
        $p_stm->execute();
        $users = $p_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["users" => $users]);
        exit;
    }

    // Create Bill Record
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO bills (admission_id, total_amount, billing_month, billing_year, bill_date, status)
                    VALUES (:admission_id, :total_amount, :billing_month, :billing_year, :bill_date, :status)";
            $stm = $conn->prepare($sql);
            
            $status = !empty($_POST['status']) ? $_POST['status'] : 'Pending';
            $total_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : 0.00;
            $bill_date = date('Y-m-d');

            $success = $stm->execute([
                ':admission_id'   => $_POST['admission_id'],
                ':total_amount'   => $total_amount,
                ':billing_month'  => $_POST['billing_month'],
                ':billing_year'   => $_POST['billing_year'],
                ':bill_date'      => $bill_date,
                ':status'         => $status
            ]);
            
            // Soo celi ID-gii u dambeeyey ee la geliyey si loogu daabaco Invoice-ka
            $last_id = $conn->lastInsertId();
            echo json_encode(["status" => true, "msg" => "Billing record created successfully!", "bill_id" => $last_id, "bill_date" => $bill_date]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Bill Record
    if ($route == 'update') {
        try {
            $sql = "UPDATE bills SET admission_id=:admission_id, total_amount=:total_amount, 
                    billing_month=:billing_month, billing_year=:billing_year, status=:status 
                    WHERE bill_id=:id";
            $stm = $conn->prepare($sql);

            $status = !empty($_POST['status']) ? $_POST['status'] : 'Pending';
            $total_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : 0.00;

            $success = $stm->execute([
                ':id'             => $_POST['id'],
                ':admission_id'   => $_POST['admission_id'],
                ':total_amount'   => $total_amount,
                ':billing_month'  => $_POST['billing_month'],
                ':billing_year'   => $_POST['billing_year'],
                ':status'         => $status
            ]);
            echo json_encode(["status" => $success, "msg" => "Billing record updated successfully!", "bill_id" => $_POST['id'], "bill_date" => date('Y-m-d')]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Bill Record
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM bills WHERE bill_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Billing record deleted safely"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Billing & Ledger Management</title>
    
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
    <h3><i class="fa-solid fa-credit-card me-2"></i> Patient Billing & Ledger Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Patient Financial Records</h4>
            <p class="text-muted">Track account balance thresholds, processing periods, and dynamic statements.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Record New Invoice / Bill
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Patient Name</th>
                            <th>Billing Period</th>
                            <th>Total Amount ($)</th>
                            <th>Invoice Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="paymentForm">
                <div class="modal-header">
                    <h5 class="modal-title">Billing Statement Transaction Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Active Patient Admission File</label>
                            <select name="admission_id" id="admission_id" class="form-select shadow-sm" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Statement Amount ($)</label>
                            <input type="number" step="0.01" min="0.01" name="total_amount" id="total_amount" class="form-control shadow-sm" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Billing Statement Month</label>
                            <select name="billing_month" id="billing_month" class="form-select shadow-sm" required>
                                <option value="">-- Select Month --</option>
                                <option value="January">January</option>
                                <option value="February">February</option>
                                <option value="March">March</option>
                                <option value="April">April</option>
                                <option value="May">May</option>
                                <option value="June">June</option>
                                <option value="July">July</option>
                                <option value="August">August</option>
                                <option value="September">September</option>
                                <option value="October">October</option>
                                <option value="November">November</option>
                                <option value="December">December</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Billing Fiscal Year</label>
                            <input type="number" name="billing_year" id="billing_year" class="form-control shadow-sm" min="2020" max="2100" value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Settlement Status</label>
                            <select name="status" id="status" class="form-select shadow-sm">
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                                <option value="Partially Paid">Partially Paid</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>

<script>
let dropdownUsersData = []; // Waxay kaydinaysaa xogta dropdown-ka si loogu isticmaalo Invoice-ka

// Load Patient Dropdown Options From Admissions
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        dropdownUsersData = res.users; // Kaydi xogta dadka
        let u = '<option value="">-- Choose Account Profile --</option>';
        res.users.forEach(user => {
            u += `<option value="${user.admission_id}">${user.full_name}</option>`;
        });
        $('#admission_id').html(u);
    });
}

// Load Payments Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(p => {
            let badgeColor = 'bg-warning text-dark';
            if(p.status === 'Paid') badgeColor = 'bg-success text-white';
            if(p.status === 'Partially Paid') badgeColor = 'bg-info text-dark';

            html += `
            <tr>
                <td><strong>#BILL-${p.bill_id}</strong></td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${p.full_name}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${p.email}</div>
                </td>
                <td><span class="badge bg-light text-cyan border border-info px-2 py-1">${p.billing_month}, ${p.billing_year}</span></td>
                <td><span class="fw-bold text-dark">$${parseFloat(p.total_amount).toFixed(2)}</span></td>
                <td><code class="text-muted small">${p.bill_date}</code></td>
                <td><span class="badge ${badgeColor} px-3 py-1 rounded-pill">${p.status}</span></td>
                <td>
                    <i class="fa-solid fa-print text-primary me-3 print-bill" style="cursor:pointer" data='${JSON.stringify(p)}' title="Print Invoice"></i>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(p)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${p.bill_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// FUNCTION-KA GENERATE GARAYNAYA PDF INVOICE-KA
function generateInvoicePDF(billId, patientName, patientEmail, billingPeriod, totalAmount, billDate, billStatus) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // 1. Qurxinta Header-ka dhakhtarka / Rugta caafimaadka
    doc.setFillColor(0, 131, 143); // midabka Dark Cyan
    doc.rect(0, 0, 210, 40, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFont("Helvetica", "bold");
    doc.setFontSize(22);
    doc.text("MENTAL HEALTH SUPPORT CENTER", 14, 25);
    
    // 2. Cinwaanka Invoice-ka iyo Faahfaahinta Biilka
    doc.setTextColor(50, 50, 50);
    doc.setFontSize(18);
    doc.text("INVOICE / STATEMENT", 14, 55);
    
    doc.setFont("Helvetica", "normal");
    doc.setFontSize(10);
    doc.text(`Invoice ID: #BILL-${billId}`, 14, 65);
    doc.text(`Date Issued: ${billDate}`, 14, 71);
    doc.text(`Billing Status: ${billStatus}`, 14, 77);

    // 3. Macluumaadka Bukaanka (Patient Info)
    doc.setFont("Helvetica", "bold");
    doc.text("BILLED TO:", 140, 65);
    doc.setFont("Helvetica", "normal");
    doc.text(patientName, 140, 71);
    doc.text(patientEmail ? patientEmail : 'N/A', 140, 77);

    // 4. Shaxda Alaabta ama Adeegga (Table)
    const tableBody = [
        [
            `Mental Health Medical Services & Treatment`,
            billingPeriod,
            `$${parseFloat(totalAmount).toFixed(2)}`
        ]
    ];

    doc.autoTable({
        startY: 90,
        head: [['Service Description', 'Billing Period', 'Subtotal']],
        body: tableBody,
        headStyles: { fillColor: [0, 131, 143], textColor: [255, 255, 255], fontStyle: 'bold' },
        styles: { fontSize: 11, cellPadding: 5 },
        columnStyles: {
            0: { cellWidth: 100 },
            1: { cellWidth: 50, halign: 'center' },
            2: { cellWidth: 40, halign: 'right' }
        }
    });

    // 5. Total-ka Guud (Summary Section)
    const finalY = doc.lastAutoTable.finalY + 10;
    doc.setFont("Helvetica", "bold");
    doc.setFontSize(12);
    doc.text(`Grand Total:`, 130, finalY);
    doc.text(`$${parseFloat(totalAmount).toFixed(2)}`, 180, finalY, { align: 'right' });

    // 6. Qoraal hoose (Footer Note)
    doc.setFont("Helvetica", "italic");
    doc.setFontSize(10);
    doc.setTextColor(120, 120, 120);
    doc.text("Thank you for choosing our center. For any financial inquiries, please contact management.", 14, finalY + 30);

    // 7. Preview ama Daabacaad toos ah oo browser-ka ku dhex furmaysa
    window.open(doc.output('bloburl'), '_blank');
}

// Marka la rixo astaanta Print-ka ee shaxda ku dhex jirta
$(document).on('click', '.print-bill', function() {
    let p = JSON.parse($(this).attr('data'));
    generateInvoicePDF(p.bill_id, p.full_name, p.email, `${p.billing_month}, ${p.billing_year}`, p.total_amount, p.bill_date, p.status);
});

// Open Form Window (Create Mode)
$('#addBtn').click(() => {
    $('#paymentForm')[0].reset();
    $('#id').val('');
    $('#billing_year').val(new Date().getFullYear());
    $('#admission_id').prop('disabled', false); 
    $('#modal').modal('show');
});

// Post Request Submission (Marka la Save gareeyo)
$('#paymentForm').submit(function(e) {
    e.preventDefault();
    
    let admissionId = $('#admission_id').val();
    let totalAmount = $('#total_amount').val();
    let billingMonth = $('#billing_month').val();
    let billingYear = $('#billing_year').val();
    let billStatus = $('#status').val();

    // Raadi magaca iyo email-ka bukaanka la doortay ee ku jira dropdown-ka
    let selectedUser = dropdownUsersData.find(u => u.admission_id == admissionId);
    let patientName = selectedUser ? selectedUser.full_name : 'Patient';
    let patientEmail = selectedUser ? selectedUser.email : '';

    $('#admission_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#admission_id').prop('disabled', true);
    }

    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            $('#modal').modal('hide');
            loadData();
            
            // Marka uu guuleysto keydinta, tus fariinta guusha kadibna soo saar PDF Report
            Swal.fire({
                title: 'Success',
                text: res.msg + " Generating PDF Invoice...",
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Halkan waxaa toos loogu wacayaa nidaamka PDF preview-ga
                generateInvoicePDF(
                    res.bill_id, 
                    patientName, 
                    patientEmail, 
                    `${billingMonth}, ${billingYear}`, 
                    totalAmount, 
                    res.bill_date, 
                    billStatus
                );
            });

        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Mode Capture
$(document).on('click', '.edit', function() {
    let p = JSON.parse($(this).attr('data'));
    $('#id').val(p.bill_id);
    $('#admission_id').val(p.admission_id).prop('disabled', true);
    $('#total_amount').val(p.total_amount);
    $('#billing_month').val(p.billing_month);
    $('#billing_year').val(p.billing_year);
    $('#status').val(p.status);
    $('#modal').modal('show');
});

// Delete Transaction 
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Purge this billing record?',
        text: "This removes the physical ledger file. The parent user directory is secure.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, clear ledger statement!'
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