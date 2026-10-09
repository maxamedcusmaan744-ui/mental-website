<?php
// 1. START SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Jileyn: Dhakhtarka soo galay nidaamka
$_SESSION['user_type'] = 'doctor';
$_SESSION['doctor_user_id'] = 2; 

// =========================================================
// 2. DATABASE CONNECTION & LOGIC (PHP)
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

$db = new Connection();
$conn = $db->db;

$current_user_name = "Dhakhtar aan la aqoonsan";
$current_user_id = 0;

if (isset($_SESSION['doctor_user_id'])) {
    $current_user_id = $_SESSION['doctor_user_id'];
    // Waxaan hubineynaa in user-ku yahay 'doctor' marka loo eego shaxda users iyo doctors
    $user_stmt = $conn->prepare("
        SELECT u.full_name FROM users u 
        JOIN doctors d ON u.user_id = d.user_id 
        WHERE u.user_id = :id AND u.user_type = 'doctor'
    ");
    $user_stmt->execute([':id' => $current_user_id]);
    $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_row) {
        $current_user_name = "Dr. " . $user_row['full_name'];
    }
}

// Xaqiiji ogolaanshaha Dhakhtarka
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
    die("Error: Ma haysatid ogolaansho aad ku aragto shaashaddan degdegga ah.");
}

// ROUTER LOGIC FOR AJAX CALLS
if (isset($_GET['url'])) {
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // 1. Read Emergency Requests (Bukaanada u baahan caawinaad leh user_type = 'Patient')
    if ($route == 'display') {
        // SQL kan wuxuu soo akhrinayaa full_name iyo profile_pic isagoo hubinaya inuu yahay Patient
        $sql = "SELECT er.*, u.full_name, u.profile_pic, u.gender, TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS age
                FROM emergency_requests er 
                JOIN users u ON er.user_id = u.user_id 
                WHERE u.user_type = 'Patient'
                ORDER BY er.request_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Update Request Status
    if ($route == 'update_status') {
        $request_id = $_POST['request_id'];
        $new_status = $_POST['request_status'];

        $sql = "UPDATE emergency_requests SET request_status = :status WHERE request_id = :id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':status' => $new_status,
            ':id'     => $request_id
        ]);

        echo json_encode(["status" => $success, "msg" => "Xaaladda bukaanka waa la cusboonaysiiyay!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Dashboard | Doctor View</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
            --main-card-bg: rgba(255, 255, 255, 0.95);
        }

        body { 
            background: linear-gradient(135deg, #f4fbfb 0%, #e6f7f8 100%); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
        }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.25);
        }

        .emergency-card {
            border: none;
            border-radius: 20px;
            background: var(--main-card-bg);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .emergency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 188, 212, 0.15);
        }

        .border-Pending { border-left: 6px solid #ffc107; }
        .border-Assigned { border-left: 6px solid #0d6efd; }
        .border-On-The-Way { border-left: 6px solid #6c757d; }
        .border-Completed { border-left: 6px solid #198754; }
        .border-Cancelled { border-left: 6px solid #dc3545; }

        .status-badge {
            font-size: 0.75rem;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Assigned { background-color: #cce5ff; color: #004085; }
        .status-On-The-Way { background-color: #e2e3e5; color: #383d41; }
        .status-Completed { background-color: #d4edda; color: #155724; }
        .status-Cancelled { background-color: #f8d7da; color: #721c24; }

        .patient-avatar {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary-cyan);
            box-shadow: 0 4px 8px rgba(0, 188, 212, 0.2);
        }

        .phone-action-btn {
            color: var(--dark-cyan);
            font-weight: 600;
            text-decoration: none;
            padding: 8px 14px;
            background: #e0f7fa;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .phone-action-btn:hover { background: var(--primary-cyan); color: white; }

        .card-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a0aec0;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .update-select {
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid rgba(0, 188, 212, 0.2);
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-4 text-center">
    <h3><i class="fa-solid fa-star-of-life me-2"></i> Qaybta Xaaladaha Degdegga ah (Emergency Patients)</h3>
    <small class="text-white-50">Shaashadda Dhakhtarka - Live Patient Logs</small>
</nav>

<div class="container px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
        <div>
            <span class="text-muted small">Dhakhtarka Soo Galay:</span> 
            <strong class="text-dark ms-1"><i class="fa-solid fa-user-md text-info me-1"></i> <?php echo htmlspecialchars($current_user_name); ?></strong>
        </div>
        <div>
            <span class="badge bg-danger p-2 rounded-3 text-uppercase"><i class="fa-solid fa-heart-pulse fa-beat me-1"></i> Emergency Active</span>
        </div>
    </div>

    <div class="row g-4" id="requestsGrid"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        if(res.length === 0) {
            html = `<div class="col-12 text-center py-5">
                        <div class="text-muted bg-white p-5 rounded-4 shadow-sm">
                            <i class="fa-solid fa-folder-open text-secondary fs-1 mb-2"></i>
                            <br>Ma jiraan bukaan u baahan xaalad Emergency ah hadda.
                        </div>
                    </div>`;
            $('#requestsGrid').html(html);
            return;
        }

        res.forEach(r => {
            let serviceIcon = r.service_type === 'Ambulance' ? '🚑' : '🩺';
            let genderIcon = r.gender.toLowerCase() === 'male' ? '<i class="fa-solid fa-mars text-primary ms-1"></i>' : '<i class="fa-solid fa-venus text-danger ms-1"></i>';
            let statusNormalized = r.request_status.replace(/\s+/g, '-');
            
            // Haddi sawirka uu madhanyahay ama la waayo, kii default ahaa ayaa la siinayaa
            let profilePicPath = r.profile_pic ? 'uploads/' + r.profile_pic : 'uploads/default.jpg';

            html += `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 emergency-card border-${statusNormalized}">
                    <div class="card-body d-flex flex-column p-4">
                        
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="card-label">REQ ID</span>
                                <div class="fw-bold text-dark">#REQ-${r.request_id}</div>
                            </div>
                            <span class="status-badge status-${statusNormalized}">${r.request_status}</span>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3 bg-light p-2 rounded-3">
                            <img src="${profilePicPath}" class="patient-avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';" alt="Patient">
                            <div>
                                <span class="card-label d-block">Bukaanka (Patient)</span>
                                <strong class="text-dark fs-6">${r.full_name} ${genderIcon}</strong>
                                <div class="text-muted small">${r.age} Sano | ID: #${r.user_id}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="card-label">Adeegga loo baahanyahay</span>
                            <div class="p-2 bg-light rounded-3 fw-bold text-secondary small">
                                ${serviceIcon} ${r.service_type} Service
                            </div>
                        </div>

                        <div class="mb-3 flex-grow-1">
                            <span class="card-label">Xaaladda Bukaanka</span>
                            <p class="text-secondary small m-0 text-wrap bg-light p-2 rounded" style="border-left: 3px solid #00bcd4; font-style: italic;">
                                "${r.emergency_description}"
                            </p>
                        </div>

                        <hr class="text-muted my-3 opacity-25">

                        <div class="mb-3">
                            <div class="small text-dark mb-1">
                                <i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong>Goobta:</strong> ${r.location}
                            </div>
                            <div class="small text-muted">
                                <i class="fa-regular fa-clock me-1 text-info"></i> <strong>Waqtiga:</strong> ${r.requested_at}
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 gap-2">
                            <a href="tel:${r.phone}" class="phone-action-btn small">
                                <i class="fa-solid fa-phone"></i> ${r.phone}
                            </a>
                            
                            <div class="flex-grow-1">
                                <select class="form-select update-select status-changer" data-id="${r.request_id}">
                                    <option value="Pending" ${r.request_status == 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Assigned" ${r.request_status == 'Assigned' ? 'selected' : ''}>Assigned</option>
                                    <option value="On The Way" ${r.request_status == 'On The Way' ? 'selected' : ''}>On The Way</option>
                                    <option value="Completed" ${r.request_status == 'Completed' ? 'selected' : ''}>Completed</option>
                                    <option value="Cancelled" ${r.request_status == 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>`;
        });
        $('#requestsGrid').html(html);
    });
}

// Update Status AJAX
$(document).on('change', '.status-changer', function() {
    let requestId = $(this).data('id');
    let newStatus = $(this).val();

    $.post('?url=update_status', { request_id: requestId, request_status: newStatus }, function(res) {
        if(res.status) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: res.msg
            });
            loadData();
        } else {
            Swal.fire('Cilad', 'Ma suurtagelin in xaaladda la beddelo', 'error');
        }
    });
});

$(document).ready(loadData);
</script>
</body>
</html>