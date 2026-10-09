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

    // Read Articles
    if ($route == 'display') {
        $sql = "SELECT a.*, u.full_name as author_name 
                FROM awareness_library a 
                LEFT JOIN users u ON a.author_id = u.user_id 
                ORDER BY a.article_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Create/Update Logic
    if ($route == 'save') {
        $id = $_POST['id'];
        if (empty($id)) {
            $sql = "INSERT INTO awareness_library (title, content, category, author_id) 
                    VALUES (:title, :content, :cat, :auth)";
        } else {
            $sql = "UPDATE awareness_library SET title=:title, content=:content, 
                    category=:cat, author_id=:auth WHERE article_id=:id";
        }
        
        $stm = $conn->prepare($sql);
        $params = [
            ':title'   => $_POST['title'],
            ':content' => $_POST['content'],
            ':cat'     => $_POST['category'],
            ':auth'    => $_POST['author_id']
        ];
        if (!empty($id)) $params[':id'] = $id;
        
        $success = $stm->execute($params);
        echo json_encode(["status" => $success, "msg" => "Article saved successfully!"]);
        exit;
    }

    // Delete Article
    if ($route == 'delete') {
        $stm = $conn->prepare("DELETE FROM awareness_library WHERE article_id=:id");
        $success = $stm->execute([':id' => $_POST['id']]);
        echo json_encode(["status" => $success, "msg" => "Article deleted"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awareness Library | Mental Health</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }

        body { background-color: #f8fdfe; font-family: 'Segoe UI', sans-serif; }

        .header-box {
            background: var(--gradient-cyan);
            padding: 40px 20px;
            color: white;
            text-align: center;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 20px rgba(0,151,167,0.2);
        }

        .article-card {
            border: none;
            border-radius: 15px;
            transition: 0.3s;
            height: 100%;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,188,212,0.2);
        }

        .category-badge {
            background: #e0f7fa;
            color: #00838f;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 50px;
        }

        .btn-cyan {
            background: var(--gradient-cyan);
            color: white; border: none;
        }

        .truncate {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #666;
        }
    </style>
</head>
<body>

<div class="header-box mb-5">
    <h1><i class="fa-solid fa-book-open-reader me-2"></i> Awareness Library</h1>
    <p>Educational Resources & Mental Health Articles</p>
    <button class="btn btn-light rounded-pill px-4 mt-2 fw-bold" id="addBtn">
        <i class="fa-solid fa-pen-nib me-2"></i> Write New Article
    </button>
</div>

<div class="container">
    <div class="row g-4" id="libraryContainer">
        <!-- Articles will load here -->
    </div>
</div>

<!-- MODAL FOR ADD/EDIT -->
<div class="modal fade" id="articleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 rounded-4">
            <form id="articleForm">
                <div class="modal-header bg-cyan text-white" style="background: var(--gradient-cyan)">
                    <h5 class="modal-title">Article Editor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="article_id">
                    <input type="hidden" name="author_id" value="1"> <!-- Simulated Logged-in Admin -->
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="fw-bold small">Article Title</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="Enter a catchy title..." required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold small">Category</label>
                            <select name="category" id="category" class="form-select">
                                <option>Anxiety</option>
                                <option>Depression</option>
                                <option>Self-Care</option>
                                <option>Work-Life Balance</option>
                                <option>Therapy Tips</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="fw-bold small">Content</label>
                            <textarea name="content" id="content" class="form-control" rows="10" placeholder="Write the full article here..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-cyan px-5 rounded-pill">Publish Article</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadLibrary() {
    $.get('?url=display', function(res) {
        let html = '';
        res.forEach(a => {
            html += `
            <div class="col-md-4">
                <div class="card article-card p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="category-badge">${a.category}</span>
                        <div class="dropdown">
                            <i class="fa-solid fa-ellipsis-vertical text-muted cursor-pointer" data-bs-toggle="dropdown"></i>
                            <ul class="dropdown-menu shadow border-0">
                                <li><a class="dropdown-item editBtn" href="#" data='${JSON.stringify(a)}'><i class="fa-solid fa-edit me-2 text-info"></i> Edit</a></li>
                                <li><a class="dropdown-item delBtn" href="#" data-id="${a.article_id}"><i class="fa-solid fa-trash me-2 text-danger"></i> Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark">${a.title}</h5>
                    <p class="truncate small">${a.content}</p>
                    <hr class="opacity-5">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted"><i class="fa-solid fa-user-pen me-1"></i> ${a.author_name || 'Admin'}</small>
                        <small class="text-muted"><i class="fa-solid fa-calendar-day me-1"></i> ${new Date(a.created_at).toLocaleDateString()}</small>
                    </div>
                </div>
            </div>`;
        });
        $('#libraryContainer').html(html || '<div class="text-center p-5"><h4>Maktabaddu waa madhan tahay.</h4></div>');
    });
}

$('#addBtn').click(() => {
    $('#articleForm')[0].reset();
    $('#article_id').val('');
    $('#articleModal').modal('show');
});

$('#articleForm').submit(function(e) {
    e.preventDefault();
    $.post('?url=save', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Published!', res.msg, 'success');
            loadLibrary();
            $('#articleModal').modal('hide');
        }
    });
});

$(document).on('click', '.editBtn', function() {
    let a = JSON.parse($(this).attr('data'));
    $('#article_id').val(a.article_id);
    $('#title').val(a.title);
    $('#category').val(a.category);
    $('#content').val(a.content);
    $('#articleModal').modal('show');
});

$(document).on('click', '.delBtn', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Are you sure?',
        text: "This article will be permanently removed!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00838f',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=delete', {id: id}, () => {
                loadLibrary();
                Swal.fire('Deleted!', 'Article has been removed.', 'success');
            });
        }
    });
});

$(document).ready(loadLibrary);
</script>
</body>
</html>