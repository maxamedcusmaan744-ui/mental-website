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

    // Read Feedback
    if ($route == 'display') {
        $sql = "SELECT f.*, u.full_name AS user_name 
                FROM feedback f
                JOIN users u ON f.user_id = u.user_id
                ORDER BY f.feedback_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get Users for Dropdown
    if ($route == 'get_users') {
        $stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create Feedback
    if ($route == 'create') {
        $sql = "INSERT INTO feedback (user_id, message, rating)
                VALUES (:u_id, :msg, :rating)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':u_id'   => $_POST['user_id'],
            ':msg'    => $_POST['message'],
            ':rating' => $_POST['rating']
        ]);
        echo json_encode(["status" => $success, "msg" => "Feedback submitted!"]);
        exit;
    }

    // Update Feedback
    if ($route == 'update') {
        $sql = "UPDATE feedback SET user_id=:u_id, message=:msg, rating=:rating 
                WHERE feedback_id=:id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':id'     => $_POST['id'],
            ':u_id'   => $_POST['user_id'],
            ':msg'    => $_POST['message'],
            ':rating' => $_POST['rating']
        ]);
        echo json_encode(["status" => $success, "msg" => "Feedback updated!"]);
        exit;
    }

    // Delete Feedback
    if ($route == 'deleteOperation') {
        $stm = $conn->prepare("DELETE FROM feedback WHERE feedback_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Feedback deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Cyan System</title>
    
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

        .star-active { color: #ffc107; }
        .star-inactive { color: #e4e5e9; }
        
        .rating-badge {
            background: #e0f7fa;
            color: #00838f;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .modal-header {
            background: var(--gradient-cyan);
            color: white;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-comments me-2"></i> User Feedback & Ratings</h3>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="text-dark">Feedback Management</h4>
            <p class="text-muted">Review and manage user experiences.</p>
        </div>
        <div class="col-md text-md-end">
            <button class="btn btn-cyan px-4 shadow-sm" id="addBtn">
                <i class="fa-solid fa-plus-circle me-2"></i> New Feedback
            </button>
        </div>
    </div>

    <div class="card main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Message</th>
                        <th>Rating</th>
                        <th>Date</th>
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
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="feedbackForm">
                <div class="modal-header">
                    <h5 class="modal-title">Feedback Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Select User</label>
                        <select name="user_id" id="user_id" class="form-select shadow-sm" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Experience Message</label>
                        <textarea name="message" id="message" class="form-control shadow-sm" rows="4" required placeholder="What did the user say?"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Rating (1 - 5 Stars)</label>
                        <select name="rating" id="rating" class="form-select shadow-sm">
                            <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (Good)</option>
                            <option value="3">⭐⭐⭐ (Average)</option>
                            <option value="2">⭐⭐ (Poor)</option>
                            <option value="1">⭐ (Very Poor)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function getStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<i class="fa-solid fa-star ${i <= rating ? 'star-active' : 'star-inactive'}"></i>`;
    }
    return stars;
}

function loadUsers() {
    $.get('?url=get_users', function(res) {
        let html = '<option value="">Select User</option>';
        res.forEach(u => html += `<option value="${u.user_id}">${u.full_name}</option>`);
        $('#user_id').html(html);
    });
}

function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(f => {
            html += `
            <tr>
                <td>${f.feedback_id}</td>
                <td class="fw-bold">${f.user_name}</td>
                <td class="text-muted small" style="max-width:250px">${f.message}</td>
                <td>
                    <div class="mb-1">${getStars(f.rating)}</div>
                    <span class="rating-badge">${f.rating}/5</span>
                </td>
                <td class="small">${new Date(f.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-sm text-warning edit" data='${JSON.stringify(f)}'><i class="fa-solid fa-edit"></i></button>
                    <button class="btn btn-sm text-danger del" data-id="${f.feedback_id}"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>`;
        });
        $('#tbody').html(html || '<tr><td colspan="6">No feedback records yet.</td></tr>');
    });
}

$('#addBtn').click(() => {
    $('#feedbackForm')[0].reset();
    $('#id').val('');
    $('#modal').modal('show');
});

$('#feedbackForm').submit(function(e) {
    e.preventDefault();
    let url = $('#id').val() ? 'update' : 'create';
    $.post('?url=' + url, $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Saved!', res.msg, 'success');
            loadData();
            $('#modal').modal('hide');
        }
    });
});

$(document).on('click', '.edit', function() {
    let f = JSON.parse($(this).attr('data'));
    $('#id').val(f.feedback_id);
    $('#user_id').val(f.user_id);
    $('#message').val(f.message);
    $('#rating').val(f.rating);
    $('#modal').modal('show');
});

$(document).on('click', '.del', function() {
    let id = $(this).attr('data-id');
    Swal.fire({
        title: 'Delete feedback?',
        icon: 'warning',
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
    loadUsers();
    loadData();
});
</script>
</body>
</html>