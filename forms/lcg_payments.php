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

    // Read Payments
    if ($route == 'display') {
        $sql = "SELECT p.*, b.billing_month, b.billing_year, u.full_name, u.email 
                FROM lcg_payments p
                JOIN bills b ON p.bill_id = b.bill_id
                JOIN patient_admissions pa ON b.admission_id = pa.admission_id
                JOIN users u ON pa.patient_id = u.user_id
                ORDER BY p.payment_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Invoices/Bills for Dropdown
    if ($route == 'get_dropdown_data') {
        $b_stm = $conn->prepare("SELECT b.bill_id, b.billing_month, b.billing_year, b.total_amount, u.full_name, u.email
                                 FROM bills b
                                 JOIN patient_admissions pa ON b.admission_id = pa.admission_id
                                 JOIN users u ON pa.patient_id = u.user_id 
                                 WHERE b.status != 'Paid' AND b.total_amount > 0");
        $b_stm->execute();
        $bills = $b_stm->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["bills" => $bills]);
        exit;
    }

    // Create Payment Record & Deduct from Bill
    if ($route == 'create') {
        try {
            $conn->beginTransaction();

            $bill_id = $_POST['bill_id'];
            $amount_paid = !empty($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0.00;
            $payment_month = $_POST['payment_month'];
            $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

            $chk = $conn->prepare("SELECT b.total_amount, u.full_name, u.email 
                                   FROM bills b
                                   JOIN patient_admissions pa ON b.admission_id = pa.admission_id
                                   JOIN users u ON pa.patient_id = u.user_id
                                   WHERE b.bill_id = :bill_id");
            $chk->execute([':bill_id' => $bill_id]);
            $bill = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$bill) {
                echo json_encode(["status" => false, "msg" => "Target bill not found!"]);
                exit;
            }

            $current_total = floatval($bill['total_amount']);
            
            if ($amount_paid > $current_total) {
                echo json_encode(["status" => false, "msg" => "Error: Amount paid ($$amount_paid) is greater than the remaining bill balance ($$current_total)"]);
                exit;
            }

            $sql = "INSERT INTO lcg_payments (bill_id, amount_paid, payment_month, payment_date)
                    VALUES (:bill_id, :amount_paid, :payment_month, :payment_date)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':bill_id'       => $bill_id,
                ':amount_paid'   => $amount_paid,
                ':payment_month' => $payment_month,
                ':payment_date'  => $payment_date
            ]);
            $last_id = $conn->lastInsertId();

            $new_total = $current_total - $amount_paid;
            $new_status = ($new_total <= 0) ? 'Paid' : 'Partially Paid';

            $up_bill = $conn->prepare("UPDATE bills SET total_amount = :new_total, status = :new_status WHERE bill_id = :bill_id");
            $up_bill->execute([
                ':new_total'  => $new_total,
                ':new_status' => $new_status,
                ':bill_id'    => $bill_id
            ]);

            $conn->commit();
            echo json_encode([
                "status" => true, 
                "msg" => "Payment processed successfully!",
                "pdf_data" => [
                    "payment_id" => $last_id,
                    "bill_id" => $bill_id,
                    "full_name" => $bill['full_name'],
                    "email" => $bill['email'],
                    "payment_month" => $payment_month,
                    "amount_paid" => $amount_paid,
                    "remaining_balance" => $new_total,
                    "payment_date" => $payment_date
                ]
            ]);
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Payment
    if ($route == 'update') {
        try {
            $conn->beginTransaction();

            $payment_id = $_POST['id'];
            $bill_id = $_POST['bill_id'];
            $new_amount_paid = !empty($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0.00;
            $payment_month = $_POST['payment_month'];
            $payment_date = $_POST['payment_date'];

            $old_p_stm = $conn->prepare("SELECT amount_paid FROM lcg_payments WHERE payment_id = :id");
            $old_p_stm->execute([':id' => $payment_id]);
            $old_payment = $old_p_stm->fetch(PDO::FETCH_ASSOC);
            $old_amount_paid = floatval($old_payment['amount_paid']);

            $b_stm = $conn->prepare("SELECT b.total_amount, u.full_name, u.email FROM bills b 
                                     JOIN patient_admissions pa ON b.admission_id = pa.admission_id
                                     JOIN users u ON pa.patient_id = u.user_id WHERE b.bill_id = :bill_id");
            $b_stm->execute([':bill_id' => $bill_id]);
            $bill = $b_stm->fetch(PDO::FETCH_ASSOC);
            $current_total = floatval($bill['total_amount']);

            $original_bill_amount = $current_total + $old_amount_paid;

            if ($new_amount_paid > $original_bill_amount) {
                echo json_encode(["status" => false, "msg" => "Error: New amount exceeds original bill balance."]);
                exit;
            }

            $sql = "UPDATE lcg_payments SET bill_id=:bill_id, amount_paid=:amount_paid, 
                    payment_month=:payment_month, payment_date=:payment_date 
                    WHERE payment_id=:id";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':id'            => $payment_id,
                ':bill_id'       => $bill_id,
                ':amount_paid'   => $new_amount_paid,
                ':payment_month' => $payment_month,
                ':payment_date'  => $payment_date
            ]);

            $final_bill_amount = $original_bill_amount - $new_amount_paid;
            $new_status = ($final_bill_amount <= 0) ? 'Paid' : 'Partially Paid';

            $up_bill = $conn->prepare("UPDATE bills SET total_amount = :final_amount, status = :new_status WHERE bill_id = :bill_id");
            $up_bill->execute([
                ':final_amount' => $final_bill_amount,
                ':new_status'   => $new_status,
                ':bill_id'      => $bill_id
            ]);

            $conn->commit();
            echo json_encode([
                "status" => true, 
                "msg" => "Payment transaction updated!",
                "pdf_data" => [
                    "payment_id" => $payment_id,
                    "bill_id" => $bill_id,
                    "full_name" => $bill['full_name'],
                    "email" => $bill['email'],
                    "payment_month" => $payment_month,
                    "amount_paid" => $new_amount_paid,
                    "remaining_balance" => $final_bill_amount,
                    "payment_date" => $payment_date
                ]
            ]);
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Payment Record
    if ($route == 'deleteOperation') {
        try {
            $conn->beginTransaction();
            $payment_id = $_POST['id'];

            $p_stm = $conn->prepare("SELECT bill_id, amount_paid FROM lcg_payments WHERE payment_id = :id");
            $p_stm->execute([':id' => $payment_id]);
            $payment = $p_stm->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                $bill_id = $payment['bill_id'];
                $amount_to_refund = floatval($payment['amount_paid']);

                $up_bill = $conn->prepare("UPDATE bills SET total_amount = total_amount + :refund, status = 'Partially Paid' WHERE bill_id = :bill_id");
                $up_bill->execute([
                    ':refund'  => $amount_to_refund,
                    ':bill_id' => $bill_id
                ]);
            }

            $stm = $conn->prepare("DELETE FROM lcg_payments WHERE payment_id=:id");
            $success = $stm->execute([':id' => $payment_id]);

            $conn->commit();
            echo json_encode(["status" => $success, "msg" => "Payment entry purged and balance refunded."]);
        } catch (PDOException $e) {
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
    <title>Patient Payment & Ledger Management</title>
    
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
        .table thead { background: #e0f7f9; color: var(--dark-cyan); }
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { background: var(--gradient-cyan); color: white; border-radius: 20px 20px 0 0; }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-credit-card me-2"></i> Patient Payment & Ledger Management</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Patient Collection Records</h4>
            <p class="text-muted">Process invoices, manage remittance allocations, and audit real-time payment entries.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Process New Payment
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Target Invoice</th>
                            <th>Patient Name</th>
                            <th>Remittance Month</th>
                            <th>Amount Paid ($)</th>
                            <th>Transaction Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="paymentForm">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Remittance Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold">Select Active Invoice / Bill Target</label>
                            <select name="bill_id" id="bill_id" class="form-select shadow-sm" required></select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="small fw-bold">Payment Allocation Month</label>
                            <select name="payment_month" id="payment_month" class="form-select shadow-sm" required>
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
                            <label class="small fw-bold">Amount Paid ($)</label>
                            <input type="number" step="0.01" min="0.01" name="amount_paid" id="amount_paid" class="form-control shadow-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold">Transaction / Processing Date</label>
                            <input type="date" name="payment_date" id="payment_date" class="form-control shadow-sm" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Apply & Generate PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- LIBRARIES -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- PDF LIBRARIES -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>

<script>
let currentBillsList = [];

// Load Dropdown
function loadDropdownData() {
    $.get('?url=get_dropdown_data', function(res) {
        currentBillsList = res.bills;
        let u = '<option value="">-- Choose Target Billing Statement --</option>';
        res.bills.forEach(bill => {
            u += `<option value="${bill.bill_id}">Inv #${bill.bill_id} - ${bill.full_name} ($${parseFloat(bill.total_amount).toFixed(2)})</option>`;
        });
        $('#bill_id').html(u);
    });
}

// Auto-fill amount field
$('#bill_id').change(function() {
    let selectedBillId = $(this).val();
    let matchedBill = currentBillsList.find(bill => bill.bill_id == selectedBillId);
    if (matchedBill) {
        $('#amount_paid').val(parseFloat(matchedBill.total_amount).toFixed(2));
    } else {
        $('#amount_paid').val('');
    }
});

// Load Payments Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(p => {
            html += `
            <tr>
                <td><strong>#TRX-${p.payment_id}</strong></td>
                <td><span class="badge bg-dark text-white">Invoice #${p.bill_id}</span></td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${p.full_name}</div>
                    <div class="text-muted small fw-normal" style="font-size:0.75rem">${p.email}</div>
                </td>
                <td><span class="badge bg-light text-cyan border border-info px-2 py-1">${p.payment_month}</span></td>
                <td><span class="fw-bold text-success">+$${parseFloat(p.amount_paid).toFixed(2)}</span></td>
                <td><code class="text-muted small">${p.payment_date}</code></td>
                <td>
                    <i class="fa-solid fa-file-pdf text-info me-3 generate-old-pdf" style="cursor:pointer" data='${JSON.stringify(p)}' title="View Invoice"></i>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(p)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${p.payment_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// FUNCTION: Si toos ah u dhalisa Invoice PDF ah
function generateInvoicePDF(data) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Naqshadda iyo Midabka (Cyan Theme)
    doc.setFillColor(0, 131, 143); // Dark Cyan
    doc.rect(0, 0, 210, 40, 'F');

    // Cinwaanka Shirkadda / Cisbitaalka
    doc.setFont("helvetica", "bold");
    doc.setFontSize(22);
    doc.setTextColor(255, 255, 255);
    doc.text("MENTAL HEALTH SUPPORT CENTER", 14, 25);

    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    doc.text("Official Payment Receipt / Invoice", 150, 25);

    // Macluumaadka Bukaanka iyo Invoice-ka
    doc.setTextColor(40, 40, 40);
    doc.setFontSize(12);
    doc.setFont("helvetica", "bold");
    doc.text("Billed To:", 14, 55);
    
    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);
    doc.text(`Patient Name: ${data.full_name}`, 14, 63);
    doc.text(`Email Address: ${data.email}`, 14, 70);

    // Dhanka Midig ee Invoice-ka
    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text(`Receipt No: #TRX-${data.payment_id}`, 140, 55);
    doc.setFont("helvetica", "normal");
    doc.text(`Invoice Target: #INV-${data.bill_id}`, 140, 62);
    doc.text(`Date Processed: ${data.payment_date}`, 140, 69);
    doc.text(`Billing Month: ${data.payment_month}`, 140, 76);

    // Shaxda Alaabta / Adeegga (AutoTable)
    doc.autoTable({
        startY: 85,
        head: [['Description', 'Allocation Month', 'Amount Paid ($)']],
        body: [
            ['Medical Care & Mental Health Consultation Support', data.payment_month, `$${parseFloat(data.amount_paid).toFixed(2)}`]
        ],
        headStyles: { fillColor: [0, 188, 212], textColor: [255, 255, 255], fontStyle: 'bold' },
        styles: { halign: 'center', fontSize: 10 },
        columnStyles: { 0: { halign: 'left' } }
    });

    // Xisaabta Guud (Totals)
    let finalY = doc.lastAutoTable.finalY + 15;
    doc.setFont("helvetica", "bold");
    doc.text(`Total Amount Remitted: $${parseFloat(data.amount_paid).toFixed(2)}`, 130, finalY);
    
    doc.setFontSize(11);
    if(parseFloat(data.remaining_balance) <= 0) {
        doc.setTextColor(40, 167, 69); // Green for Paid
        doc.text("Account Status: FULLY PAID", 130, finalY + 8);
    } else {
        doc.setTextColor(220, 53, 69); // Red for Balance
        doc.text(`Remaining Balance: $${parseFloat(data.remaining_balance).toFixed(2)}`, 130, finalY + 8);
    }

    // Gunta Hoose (Footer)
    doc.setFontSize(9);
    doc.setFont("helvetica", "italic");
    doc.setTextColor(120, 120, 120);
    doc.text("Thank you for your payment. If you have any questions, please contact billing support.", 14, 280);

    // Daawasho toos ah (Wuxuu ku furayaa Tab cusub isagoo PDF ah)
    window.open(doc.output('bloburl'), '_blank');
}

// Rixidda badhanka daawashada PDF-yadii hore
$(document).on('click', '.generate-old-pdf', function() {
    let p = JSON.parse($(this).attr('data'));
    // Maadaama display-gu uusan wadin remaining balance si toos ah, waxaan u xisaabinaynaa si maqaakiil ah ama eber
    let dataForPdf = {
        payment_id: p.payment_id,
        bill_id: p.bill_id,
        full_name: p.full_name,
        email: p.email,
        payment_month: p.payment_month,
        amount_paid: p.amount_paid,
        remaining_balance: 0, // Waxaa laga akhrisan karaa dabeelka bills hadday muhiim tahay
        payment_date: p.payment_date
    };
    generateInvoicePDF(dataForPdf);
});

// Open Form
$('#addBtn').click(() => {
    $('#paymentForm')[0].reset();
    $('#id').val('');
    $('#payment_date').val(new Date().toISOString().split('T')[0]);
    $('#bill_id').prop('disabled', false); 
    $('#modal').modal('show');
});

// Form Submission (Create / Update)
$('#paymentForm').submit(function(e) {
    e.preventDefault();
    
    $('#bill_id').prop('disabled', false);
    let formData = $(this).serialize();
    if($('#id').val()) {
        $('#bill_id').prop('disabled', true);
    }

    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire({
                title: 'Success!',
                text: res.msg,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            loadData();
            loadDropdownData();
            $('#modal').modal('hide');

            // Halkan wuxuu toos ugu furayaa Invoice-ka PDF-ta ah marka la bixiyo lacagta!
            if(res.pdf_data) {
                generateInvoicePDF(res.pdf_data);
            }
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Mode
$(document).on('click', '.edit', function() {
    let p = JSON.parse($(this).attr('data'));
    $('#id').val(p.payment_id);
    $('#bill_id').val(p.bill_id).prop('disabled', true);
    $('#payment_month').val(p.payment_month);
    $('#amount_paid').val(p.amount_paid);
    $('#payment_date').val(p.payment_date);
    $('#modal').modal('show');
});

// Delete Mode
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Purge this payment transaction?',
        text: "This action removes the payment slice balance from the ledger.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, remove payment entry!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                loadDropdownData();
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