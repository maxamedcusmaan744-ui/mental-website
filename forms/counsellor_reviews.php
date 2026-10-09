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

    // Read Reviews
    if ($route == 'display') {
        $sql = "SELECT r.*, p.full_name AS patient_name, c.full_name AS counsellor_name 
                FROM counsellor_reviews r
                JOIN users p ON r.patient_id = p.user_id
                JOIN users c ON r.counsellor_id = c.user_id
                ORDER BY r.review_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Users for Dropdowns
    if ($route == 'get_users') {
        $stm = $conn->prepare("SELECT user_id, full_name, user_type FROM users WHERE status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Review
    if ($route == 'create') {
        $sql = "INSERT INTO counsellor_reviews (counsellor_id, patient_id, rating, review_text)
                VALUES (:c_id, :p_id, :rating, :text)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':c_id'   => $_POST['counsellor_id'],
            ':p_id'   => $_POST['patient_id'],
            ':rating' => $_POST['rating'],
            ':text'   => $_POST['review_text']
        ]);
        echo json_encode(["status" => $success, "msg" => "Review submitted!"]);
        exit;
    }

    // Update Review
    if ($route == 'update') {
        $sql = "UPDATE counsellor_reviews SET counsellor_id=:c_id, patient_id=:p_id, 
                rating=:rating, review_text=:text 
                WHERE review_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'     => $_POST['id'],
            ':c_id'   => $_POST['counsellor_id'],
            ':p_id'   => $_POST['patient_id'],
            ':rating' => $_POST['rating'],
            ':text'   => $_POST['review_text']
        ]);
        echo json_encode(["status" => $success, "msg" => "Review updated!"]);
        exit;
    }

    // Delete Review
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM counsellor_reviews WHERE review_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Review deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counsellor Reviews | Cyan System</title>
    
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

        .rating-star { color: #ffc107; }

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
    <h3><i class="fa-solid fa-star me-2"></i> Counsellor Reviews & Ratings</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Patient Feedback</h4>
            <p class="text-muted">Manage and view all counsellor performance reviews.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-cyan px-4 py-2 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> Write a Review
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
                            <th>Counsellor</th>
                            <th>Patient</th>
                            <th>Rating</th>
                            <th>Feedback</th>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reviewForm">
                <div class="modal-header">
                    <h5 class="modal-title">Review Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Select Counsellor</label>
                        <select name="counsellor_id" id="counsellor_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Select Patient</label>
                        <select name="patient_id" id="patient_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Rating (1-5)</label>
                        <select name="rating" id="rating" class="form-select shadow-sm" required>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Review Text</label>
                        <textarea name="review_text" id="review_text" class="form-control shadow-sm" maxlength="50" placeholder="Max 50 characters" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadUsers() {
    $.get('?url=get_users', function(res) {
        let p = '<option value="">Select Patient</option>';
        let c = '<option value="">Select Counsellor</option>';
        res.forEach(u => {
            if(u.user_type === 'Patient') p += `<option value="${u.user_id}">${u.full_name}</option>`;
            if(u.user_type === 'Counsellor') c += `<option value="${u.user_id}">${u.full_name}</option>`;
        });
        $('#patient_id').html(p);
        $('#counsellor_id').html(c);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(r => {
            let stars = '';
            for(let i=0; i<r.rating; i++) stars += '<i class="fa-solid fa-star rating-star"></i>';
            
            html += `
            <tr>
                <td>${r.review_id}</td>
                <td class="fw-bold text-dark">${r.counsellor_name}</td>
                <td>${r.patient_name}</td>
                <td>${stars}</td>
                <td><small>${r.review_text}</small></td>
                <td><small>${r.created_at}</small></td>
                <td>
                    <i class="fa-solid fa-edit text-warning me-3 edit" style="cursor:pointer" data='${JSON.stringify(r)}'></i>
                    <i class="fa-solid fa-trash text-danger del" style="cursor:pointer" data-id="${r.review_id}"></i>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="7">No reviews found</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#reviewForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

$('#reviewForm').submit(function(e) {
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
    let r = JSON.parse($(this).attr('data'));
    $('#id').val(r.review_id);
    $('#counsellor_id').val(r.counsellor_id);
    $('#patient_id').val(r.patient_id);
    $('#rating').val(r.rating);
    $('#review_text').val(r.review_text);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete this review?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete!'
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
    loadUsers();
    loadData();
});
</script>
</body>
</html>