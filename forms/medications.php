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

    // Read Medications
    if ($route == 'display') {
        $sql = "SELECT * FROM medications ORDER BY medication_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Medication Record
    if ($route == 'create') {
        try {
            $sql = "INSERT INTO medications (medication_name, description, dosage, stock_quantity)
                    VALUES (:medication_name, :description, :dosage, :stock_quantity)";
            $stm = $conn->prepare($sql);
            
            $desc = !empty($_POST['description']) ? $_POST['description'] : null;
            $dosage = !empty($_POST['dosage']) ? $_POST['dosage'] : null;
            $stock = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? intval($_POST['stock_quantity']) : 0;

            $success = $stm->execute([
                ':medication_name' => $_POST['medication_name'],
                ':description'     => $desc,
                ':dosage'          => $dosage,
                ':stock_quantity'  => $stock
            ]);
            echo json_encode(["status" => true, "msg" => "Medication registered successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Update Medication Record
    if ($route == 'update') {
        try {
            $sql = "UPDATE medications SET medication_name=:medication_name, description=:description, 
                    dosage=:dosage, stock_quantity=:stock_quantity 
                    WHERE medication_id=:id";
            $stm = $conn->prepare($sql);

            $desc = !empty($_POST['description']) ? $_POST['description'] : null;
            $dosage = !empty($_POST['dosage']) ? $_POST['dosage'] : null;
            $stock = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? intval($_POST['stock_quantity']) : 0;

            $success = $stm->execute([
                ':id'              => $_POST['id'],
                ':medication_name' => $_POST['medication_name'],
                ':description'     => $desc,
                ':dosage'          => $dosage,
                ':stock_quantity'  => $stock
            ]);
            echo json_encode(["status" => $success, "msg" => "Medication data updated successfully!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
        }
        exit;
    }

    // Delete Medication Record
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM medications WHERE medication_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Medication removed safely from registry"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Management | Cyan Gradient System</title>
    
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
    <h3><i class="fa-solid fa-pills me-2"></i> Clinical Medication & Stock Registry</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Pharmaceutical Inventory</h4>
            <p class="text-muted">Manage available medications, structural dosage profiles, and stock-room quantity metrics.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Add New Medication
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Medication Name</th>
                            <th>Dosage Specification</th>
                            <th>Stock Status</th>
                            <th>Registered On</th>
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
            <form id="medicationForm">
                <div class="modal-header">
                    <h5 class="modal-title">Medication Entry Sheet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Medication Name</label>
                            <input type="text" name="medication_name" id="medication_name" class="form-control shadow-sm" placeholder="e.g. Sertraline / Diazepam" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Standard Dosage</label>
                            <input type="text" name="dosage" id="dosage" class="form-control shadow-sm" placeholder="e.g. 50mg daily / 10mg as needed">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="small fw-bold">Inventory Quantity In-Stock</label>
                            <input type="number" min="0" name="stock_quantity" id="stock_quantity" class="form-control shadow-sm" value="0" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold">Description & Indications</label>
                        <textarea name="description" id="description" class="form-control shadow-sm" rows="4" placeholder="Describe medication type, class, common use cases, or storage warnings..."></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-5">Save Product Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Load Main Grid
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(m => {
            let stockBadge = 'bg-success text-white';
            if(parseInt(m.stock_quantity) === 0) {
                stockBadge = 'bg-danger text-white';
            } else if(parseInt(m.stock_quantity) < 10) {
                stockBadge = 'bg-warning text-dark';
            }

            let descSnippet = m.description ? m.description : 'No additional data listed.';

            html += `
            <tr>
                <td><strong>#MED-${m.medication_id}</strong></td>
                <td class="text-start fw-bold">
                    <div class="text-dark">${m.medication_name}</div>
                    <div class="text-muted small fw-normal text-truncate" style="max-width: 280px; font-size:0.75rem" title="${descSnippet}">${descSnippet}</div>
                </td>
                <td><span class="badge bg-light text-cyan border border-info px-2 py-1">${m.dosage ? m.dosage : 'N/A'}</span></td>
                <td><span class="badge ${stockBadge} px-3 py-1 rounded-pill fw-bold">${m.stock_quantity} Units</span></td>
                <td><code class="text-muted small">${m.created_at}</code></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(m)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${m.medication_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html);
    });
}

// Open Form Window (Create Mode)
$('#addBtn').click(() => {
    $('#medicationForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

// Post Request (Create / Update Submission)
$('#medicationForm').submit(function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    let url = $('#id').val() ? 'update' : 'create';

    $.post('?url=' + url, formData, function(res) {
        if(res.status) {
            Swal.fire('Success', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        } else {
            Swal.fire('Error', res.msg, 'error');
        }
    });
});

// Edit Mode Capture
$(document).on('click', '.edit', function() {
    let m = JSON.parse($(this).attr('data'));
    $('#id').val(m.medication_id);
    $('#medication_name').val(m.medication_name);
    $('#dosage').val(m.dosage ? m.dosage : '');
    $('#stock_quantity').val(m.stock_quantity);
    $('#description').val(m.description ? m.description : '');
    $('#modal').modal('show');
});

// Delete Transaction 
$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this medication record?',
        text: "This permanently deletes the operational stock listing from the catalog.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, remove item!'
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
    loadData();
});
</script>

</body>
</html>