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

    // Read Doctors with User details (Halkan waxaa lagu soo daray u.profile_pic)
    if ($route == 'display') {
        $sql = "SELECT d.*, u.full_name, u.email, u.phone, u.profile_pic 
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                ORDER BY d.doctor_id DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
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
    <title>Dhakhaatiirta | Habeeb Psychiatric Hospital</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
            --card-border-radius: 20px;
        }

        body { background-color: #f0fafa; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.3);
        }

        /* Naqshadda Card-ka Cusub ee interface-ka */
        .doctor-card {
            background: white;
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .doctor-thumb {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0fafa;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .star-rating {
            color: #ffc107;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .info-label {
            color: #8e8da2;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .info-value {
            color: #1a192e;
            font-weight: 600;
            font-size: 1rem;
        }

        .divider-dashed {
            border-top: 1px dashed #e0e0e0;
            margin: 15px 0;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-5 text-center">
    <h3><i class="fa-solid fa-user-doctor me-2"></i> Habeeb Psychiatric Hospital</h3>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h4 class="text-dark fw-bold">Dhakhaatiirta Isbitaalka</h4>
            <p class="text-muted">Halkan ka bogo dhammaan dhakhaatiirta iyo khuburada diyaarka u ah inay ku caawiyaan.</p>
        </div>
    </div>

    <div class="row justify-content-center" id="doctors_grid_container"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Soo saarista Card-yada Dhakhaatiirta
function loadData() {
    $.get('?url=display', function(res) {
        let html = '';
        if(res.length === 0) {
            html = '<div class="col-12 text-center text-muted py-5"><h5>Ma jiro wax xog dhakhaatiir ah oo hadda la heli karo!</h5></div>';
        }
        
        res.forEach(d => {
            let photo = d.profile_pic ? d.profile_pic : 'default.jpg';
            let avatarUrl = `uploads/${photo}`;
            
            html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card doctor-card p-4">
                    
                    <div class="text-center mb-3">
                        <img src="${avatarUrl}" class="doctor-thumb mb-2" alt="Doctor Face" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(d.full_name)}&background=00bcd4&color=fff&bold=true'">
                        <h5 class="fw-bold text-dark mb-1">Dr. ${d.full_name}</h5>
                        <p class="text-muted small mb-2">${d.experience_years} Sano Oo Khibrad Ah</p>
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            <span class="star-rating"><i class="fa-solid fa-star me-1"></i>5.0</span>
                        </div>
                    </div>

                    <div class="divider-dashed"></div>

                    <div class="px-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label">Isbitaal</span>
                            <span class="info-value">• Habeeb Hospital</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label">Takhasus</span>
                            <span class="info-value">• ${d.specialization}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="info-label">E-mail</span>
                            <span class="info-value text-muted small">${d.email}</span>
                        </div>
                    </div>

                </div>
            </div>`;
        });
        $('#doctors_grid_container').html(html);
    });
}

$(document).ready(() => {
    loadData();
});
</script>

</body>
</html>