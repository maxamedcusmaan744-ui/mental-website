<?php
// =========================================================
// 1. SESSION START & PROTECTION
// =========================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hubi in isticmaalahu uu soo galay (Logged In) iyo inuu yahay Doctor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: sign_in.php");
    exit();
}

// Ka soo qaad ID-ga dhakhtarka hadda session-ka ku jira
$current_doctor_id = $_SESSION['user_id'];

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

// ROUTER LOGIC
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // Akhrinta Kulamada (Video Meetings) ee u gaarka ah Dhakhtarka soo galay oo kaliya
    if ($route == 'display') {
        $sql = "SELECT 
                    vm.meeting_link, 
                    vm.started_at, 
                    vm.ended_at,
                    u_doc.full_name AS doctor_name, 
                    u_doc.profile_pic AS doctor_pic,
                    u_pat.full_name AS patient_name, 
                    u_pat.profile_pic AS patient_pic
                FROM video_meetings vm
                JOIN appointment a ON vm.appointment_id = a.appointment_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u_doc ON d.user_id = u_doc.user_id
                JOIN users u_pat ON a.patient_id = u_pat.user_id
                WHERE d.user_id = :doctor_id AND u_doc.user_type = 'Doctor'
                ORDER BY vm.started_at DESC";
                
        $stm = $conn->prepare($sql);
        // Waxaan ku xiraynaa ID-gii laga soo qabtay Session-ka kor ku xusan
        $stm->execute([':doctor_id' => $current_doctor_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kulamadayda & Ogeysiisyada | Habeeb Psychiatric Hospital</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #00bcd4, #00838f);
            --card-border-radius: 24px;
        }

        body { 
            background-color: #f4fbfb; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            font-size: 1.1rem;
        }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 30px 20px;
            color: white;
            box-shadow: 0 6px 20px rgba(0,188,212,0.35);
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .navbar-custom h2 {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .welcome-section h3 {
            font-size: 2rem;
        }
        
        .welcome-section p {
            font-size: 1.2rem;
        }

        .session-card {
            background: white;
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 188, 212, 0.1);
        }

        .session-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 188, 212, 0.15);
        }

        .user-thumb {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00bcd4;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .name-text {
            font-size: 1.15rem;
            font-weight: 700;
            margin-top: 8px;
        }

        .role-badge {
            font-size: 0.85rem !important;
            padding: 6px 12px !important;
            border-radius: 8px;
        }

        .info-label {
            color: #6c757d;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .info-value {
            color: #1a192e;
            font-weight: 700;
            font-size: 1.15rem;
        }

        .divider-dashed {
            border-top: 2px dashed #e3f2fd;
            margin: 20px 0;
        }

        .btn-join {
            background: var(--gradient-cyan);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.25rem;
            padding: 14px 20px;
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
            transition: all 0.2s ease;
        }
        
        .btn-join:hover {
            color: white;
            opacity: 0.95;
            box-shadow: 0 7px 20px rgba(0, 188, 212, 0.45);
            transform: scale(1.02);
        }

        .empty-state {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            font-size: 1.3rem;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h2><i class="fa-solid fa-video me-3"></i> Habeeb Psychiatric Hospital</h2>
</nav>

<div class="container mb-5">
    <div class="row mb-5 welcome-section">
        <div class="col-12 text-center">
            <h3 class="text-dark fw-bold mb-2">Ku soo dhawaada, Dr. <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Dhakhtar'); ?></h3>
            <p class="text-muted">Halkan ka bogo kulamadaada tooska ah ee muuqaalka (Video Meetings) ee aad la leedahay bukaannada aad kula socoto.</p>
        </div>
    </div>

    <!-- Halkan weydiinta grid-ka waxaa laga dhigay col-lg-6 si labo-labo waaweyn ugu soo baxaan screens-ka waaweyn -->
    <div class="row justify-content-center g-4" id="sessions_grid_container"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        if(res.length === 0) {
            html = '<div class="col-md-8 text-center text-muted empty-state my-5"><h5><i class="fa-regular fa-calendar-times d-block mb-3 fa-2x text-secondary"></i> Ma jiro wax kulamo ah oo kuu qorshaysan hadda!</h5></div>';
        }
        
        res.forEach(s => {
            let docPhoto = s.doctor_pic ? s.doctor_pic : 'default.jpg';
            let patPhoto = s.patient_pic ? s.patient_pic : 'default.jpg';
            
            let docAvatar = `uploads/${docPhoto}`;
            let patAvatar = `uploads/${patPhoto}`;
            
            html += `
            <div class="col-12 col-md-6 mb-2">
                <div class="card session-card p-4 p-lg-5">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="text-center" style="width: 44%;">
                            <img src="${docAvatar}" class="user-thumb mb-2" alt="Doctor" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(s.doctor_name)}&background=00bcd4&color=fff&bold=true&size=128'">
                            <p class="text-dark name-text mb-1">Dr. ${s.doctor_name}</p>
                            <span class="badge bg-info text-dark role-badge">Adiga</span>
                        </div>
                        
                        <div class="text-muted"><i class="fa-solid fa-right-left fa-xl text-info opacity-50"></i></div>
                        
                        <div class="text-center" style="width: 44%;">
                            <img src="${patAvatar}" class="user-thumb mb-2" alt="Patient" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(s.patient_name)}&background=ff9800&color=fff&bold=true&size=128'">
                            <p class="text-dark name-text mb-1">${s.patient_name}</p>
                            <span class="badge bg-warning text-dark role-badge">Bukaanka</span>
                        </div>
                    </div>

                    <div class="divider-dashed"></div>

                    <div class="px-2 mb-4 bg-light p-3 rounded-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="info-label"><i class="fa-regular fa-clock me-2 text-success fa-lg"></i> Bilaabashada</span>
                            <span class="info-value text-success">${s.started_at ? s.started_at : '---'}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="info-label"><i class="fa-solid fa-hourglass-end me-2 text-danger fa-lg"></i> Dhammaadka</span>
                            <span class="info-value text-danger">${s.ended_at ? s.ended_at : '---'}</span>
                        </div>
                    </div>

                    <div class="text-center mt-2">
                        <a href="${s.meeting_link}" target="_blank" class="btn btn-join w-100 py-3">
                            <i class="fa-solid fa-video me-2"></i> Ku Biir Kulanka Tooska Ah (Join Meeting)
                        </a>
                    </div>

                </div>
            </div>`;
        });
        $('#sessions_grid_container').html(html);
    });
}

$(document).ready(() => {
    loadData();
});
</script>

</body>
</html>
