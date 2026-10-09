<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC
// =========================================================
class Connection {
    private $host = 'localhost';
    private $db_name = 'mental_health_support';
    private $user_name = 'root';
    private $password = '';
    public $db;

    public function __construct() {
        try {
            $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4", $this->user_name, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die(json_encode(["status" => false, "msg" => "Connection failed"]));
        }
    }
}

$current_admin = 1; // Session ID-ga Admin-ka hadda login-ka ah

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // 1. Fetch Announcements
    if ($_GET['action'] == 'fetch') {
        $sql = "SELECT a.*, u.full_name as author 
                FROM announcements a 
                JOIN users u ON a.posted_by = u.user_id 
                ORDER BY a.created_at DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Post New Announcement
    if ($_GET['action'] == 'save') {
        $sql = "INSERT INTO announcements (title, message, target_group, posted_by) VALUES (:title, :msg, :target, :pid)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':title'  => $_POST['title'],
            ':msg'    => $_POST['message'],
            ':target' => $_POST['target_group'],
            ':pid'    => $current_admin
        ]);
        echo json_encode(["status" => $success]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --cyan-gradient: linear-gradient(135deg, #00fbff 0%, #00838f 100%);
        }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        
        .header-section {
            background: var(--cyan-gradient);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
            border-radius: 0 0 50px 50px;
            box-shadow: 0 10px 30px rgba(0, 131, 143, 0.2);
        }

        .announcement-card {
            border: none;
            border-radius: 20px;
            background: white;
            transition: all 0.3s ease;
            border-left: 5px solid #00acc1;
        }

        .announcement-card:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .target-badge {
            font-size: 0.7rem;
            padding: 5px 12px;
            border-radius: 50px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .btn-cyan {
            background: var(--cyan-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-cyan:hover { color: white; opacity: 0.9; }
    </style>
</head>
<body>

<div class="header-section text-center">
    <div class="container">
        <h1 class="fw-bold"><i class="fa-solid fa-bullhorn me-2"></i> System Announcements</h1>
        <p class="lead">Communicate important updates to the MindCare community</p>
        <button class="btn btn-light mt-3 fw-bold text-dark px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#postModal">
            <i class="fa-solid fa-plus me-2"></i> Create Announcement
        </button>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4" id="announcementList">
        <!-- Announcements will load here -->
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="postModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <form id="announcementForm">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Short & clear title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Target Group</label>
                        <select name="target_group" class="form-select">
                            <option value="All">Everyone (Public)</option>
                            <option value="Patients">Patients Only</option>
                            <option value="Counsellors">Counsellors Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Message Content</label>
                        <textarea name="message" class="form-control" rows="4" maxlength="500" placeholder="Write your message here..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-cyan w-100">Publish Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadAnnouncements() {
    $.get('?action=fetch', function(data) {
        let html = '';
        data.forEach(a => {
            let date = new Date(a.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            let badgeClass = a.target_group == 'All' ? 'bg-info' : (a.target_group == 'Patients' ? 'bg-success' : 'bg-primary');
            
            html += `
            <div class="col-md-6 col-lg-4">
                <div class="card announcement-card h-100 p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge ${badgeClass} target-badge">${a.target_group}</span>
                        <small class="text-muted fw-bold"><i class="fa-regular fa-calendar me-1"></i> ${date}</small>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">${a.title}</h5>
                    <p class="text-secondary small mb-4">${a.message}</p>
                    <div class="mt-auto pt-3 border-top d-flex align-items-center">
                        <div class="bg-light rounded-circle p-2 me-2"><i class="fa fa-user-tie text-secondary"></i></div>
                        <small class="fw-bold text-muted">Admin: ${a.author}</small>
                    </div>
                </div>
            </div>`;
        });
        $('#announcementList').html(html || '<div class="text-center p-5 w-100">No announcements yet.</div>');
    });
}

$('#announcementForm').submit(function(e) {
    e.preventDefault();
    $.post('?action=save', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({ icon: 'success', title: 'Published!', timer: 1500, showConfirmButton: false });
            $('#postModal').modal('hide');
            $('#announcementForm')[0].reset();
            loadAnnouncements();
        }
    });
});

$(document).ready(loadAnnouncements);
</script>
</body>
</html>