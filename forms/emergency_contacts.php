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

    // Read Contacts
    if ($route == 'display') {
        $stm = $conn->prepare("SELECT * FROM emergency_contacts ORDER BY contact_id DESC");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Contact
    if ($route == 'create') {
        $sql = "INSERT INTO emergency_contacts (name, phone, email, organization, availability)
                VALUES (:name, :phone, :email, :org, :avail)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':name'  => $_POST['name'],
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':org'   => $_POST['organization'],
            ':avail' => $_POST['availability']
        ]);
        echo json_encode(["status" => $success, "msg" => "Contact saved!"]);
        exit;
    }

    // Update Contact
    if ($route == 'update') {
        $sql = "UPDATE emergency_contacts SET name=:name, phone=:phone, email=:email, 
                organization=:org, availability=:avail WHERE contact_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'    => $_POST['id'],
            ':name'  => $_POST['name'],
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':org'   => $_POST['organization'],
            ':avail' => $_POST['availability']
        ]);
        echo json_encode(["status" => $success, "msg" => "Contact updated!"]);
        exit;
    }

    // Delete Contact
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM emergency_contacts WHERE contact_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Contact deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contacts | Cyan Care</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
            --emergency-red: #ff5252;
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
        }

        .phone-link:hover { background: var(--primary-cyan); color: white; }

        .avail-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-truck-medical me-2"></i> Crisis & Emergency Resources</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-6">
            <h4 class="text-dark m-0">Helpline Directory</h4>
        </div>
        <div class="col-6 text-end">
            <button class="btn btn-cyan px-4" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Add Contact
            </button>
        </div>
    </div>

    <div class="card main-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name / Org</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="contactForm">
                <div class="modal-header bg-cyan text-white" style="background: var(--gradient-cyan)">
                    <h5 class="modal-title">Emergency Contact Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="small fw-bold">Full Name / Service Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Suicide Prevention Line" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="+252..." required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Email (Optional)</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="help@org.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Organization</label>
                            <input type="text" name="organization" id="organization" class="form-control" placeholder="e.g. Red Cross">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold">Availability</label>
                            <select name="availability" id="availability" class="form-select">
                                <option value="24/7">24/7 Support</option>
                                <option value="Mon-Fri (8am-5pm)">Mon-Fri (8am-5pm)</option>
                                <option value="Weekends Only">Weekends Only</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cyan px-4">Save Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(c => {
            html += `
            <tr>
                <td>${c.contact_id}</td>
                <td>
                    <div class="fw-bold">${c.name}</div>
                    <div class="text-muted small">${c.organization || '---'}</div>
                </td>
                <td>
                    <a href="tel:${c.phone}" class="phone-link">
                        <i class="fa-solid fa-phone-flip me-1"></i> ${c.phone}
                    </a>
                </td>
                <td class="small">${c.email || 'N/A'}</td>
                <td><span class="avail-badge">${c.availability}</span></td>
                <td>
                    <button class="btn btn-sm text-info edit" data='${JSON.stringify(c)}'><i class="fa-solid fa-pen-to-square"></i></button>
                    <button class="btn btn-sm text-danger del" data-id="${c.contact_id}"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="6">No emergency contacts listed.</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#contactForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

$('#contactForm').submit(function(e) {
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
    let c = JSON.parse($(this).attr('data'));
    $('#id').val(c.contact_id);
    $('#name').val(c.name);
    $('#phone').val(c.phone);
    $('#email').val(c.email);
    $('#organization').val(c.organization);
    $('#availability').val(c.availability);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Are you sure?',
        text: "Important contacts should only be deleted if outdated.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=deleteOperation', {id: id}, () => {
                loadData();
                Swal.fire('Deleted!', 'Contact removed.', 'success');
            });
        }
    });
});

$(document).ready(loadData);
</script>
</body>
</html>