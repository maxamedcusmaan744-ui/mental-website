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

    // 1. Helitaanka bukaanada hadda jiifa (Currently Admitted)
    if ($route == 'display_admissions') {
        $sql = "SELECT 
                    pa.admission_id,
                    pa.admission_date,
                    pa.admission_reason,
                    pa.status AS admission_status,
                    pa.room_id,
                    pa.bed_id,
                    u.user_id,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.gender,
                    u.profile_pic,
                    TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS age
                FROM patient_admissions pa
                JOIN users u ON pa.patient_id = u.user_id
                WHERE pa.status = 'Admitted' AND u.user_type = 'Patient'
                ORDER BY pa.admission_id DESC";
                
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Helitaanka bukaanada kale ee diwaangashan laakiin aan hadda sariir jiifin
    if ($route == 'display_non_admitted') {
        $sql = "SELECT 
                    u.user_id,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.gender,
                    u.profile_pic,
                    u.status AS user_status,
                    TIMESTAMPDIFF(YEAR, u.date_of_birth, CURDATE()) AS age
                FROM users u
                WHERE u.user_type = 'Patient' 
                AND u.user_id NOT IN (
                    SELECT patient_id FROM patient_admissions WHERE status = 'Admitted'
                )
                ORDER BY u.user_id DESC";
                
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
    <title>Maareynta Bukaanada | Habeeb Psychiatric Hospital</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
            --card-border-radius: 20px;
            --accent-cyan: #00bcd4;
        }

        body { background-color: #f0fafa; font-family: 'Segoe UI', sans-serif; }

        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.3);
        }

        /* Tabs styling modern */
        .nav-pills .nav-link {
            color: #00838f;
            font-weight: 600;
            border-radius: 12px;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .nav-pills .nav-link.active {
            background: var(--gradient-cyan);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 188, 212, 0.2);
        }

        /* Patient Card Styling */
        .patient-card {
            background: white;
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .patient-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .patient-thumb {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0fafa;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .info-label {
            color: #8e8da2;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .info-value {
            color: #1a192e;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .divider-dashed {
            border-top: 1px dashed #e0e0e0;
            margin: 12px 0;
        }

        .admission-reason-box {
            background-color: #f8fbfb;
            border-left: 3px solid var(--accent-cyan);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.88rem;
            color: #495057;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-4 text-center">
    <h3><i class="fa-solid fa-hospital-user me-2"></i> Habeeb Psychiatric Hospital</h3>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h4 class="text-dark fw-bold">Dashboard-ka Maareynta Bukaanada</h4>
            <p class="text-muted">Kala soco bukaanada jiifa isbitaalka iyo kuwa caadiga ah ee nidaamka ka diwaangashan.</p>
        </div>
    </div>

    <div class="d-flex justify-content-center mb-4">
        <ul class="nav nav-pills bg-white p-2 rounded-3 shadow-sm" id="patientTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="admitted-tab" data-bs-toggle="pill" data-bs-target="#admitted_section" type="button">
                    <i class="fa-solid fa-bed me-2"></i> Bukaanada Jiifa (Inpatients)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="non-admitted-tab" data-bs-toggle="pill" data-bs-target="#non_admitted_section" type="button">
                    <i class="fa-solid fa-user-injured me-2"></i> Kuwa Aan Jiifin (Outpatients)
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="patientTabsContent">
        
        <div class="tab-pane fade show active" id="admitted_section" role="tabpanel">
            <div class="row justify-content-start" id="admitted_grid"></div>
        </div>
        
        <div class="tab-pane fade" id="non_admitted_section" role="tabpanel">
            <div class="row justify-content-start" id="non_admitted_grid"></div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function formatDate(dateString) {
    let date = new Date(dateString);
    return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// 1. Load Bukaanada Jiifa
function loadAdmittedPatients() {
    $.get('?url=display_admissions', function(res) {
        let html = '';
        if(res.length === 0) {
            html = '<div class="col-12 text-center text-muted py-5"><h5>Ma jiro wax bukaan ah oo hadda jiifa isbitaalka!</h5></div>';
            $('#admitted_grid').html(html);
            return;
        }
        
        res.forEach(p => {
            let photo = p.profile_pic ? p.profile_pic : 'default.jpg';
            let avatarUrl = `uploads/${photo}`;
            let genderIcon = p.gender.toLowerCase() === 'male' ? '<i class="fa-solid fa-mars text-primary ms-1"></i>' : '<i class="fa-solid fa-venus text-danger ms-1"></i>';

            html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card patient-card p-4" style="border-top: 4px solid #198754;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="${avatarUrl}" class="patient-thumb" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.full_name)}&background=00bcd4&color=fff&bold=true'">
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1 fs-6">${p.full_name} ${genderIcon}</h5>
                            <p class="text-muted small mb-1">${p.age} Sano | ${p.phone}</p>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small"><i class="fa-solid fa-circle text-success me-1 small"></i> Hadda Jiifa</span>
                        </div>
                    </div>
                    <div class="divider-dashed"></div>
                    <div class="px-1">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label"><i class="fa-solid fa-door-closed me-1"></i> Lambarka Qolka</span>
                            <span class="info-value">Qolka ${p.room_id}aad</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label"><i class="fa-solid fa-bed me-1"></i> Lambarka Sariirta</span>
                            <span class="info-value">Sariirta ${p.bed_id}aad</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label"><i class="fa-solid fa-calendar-day me-1"></i> Taariikhda la seexiyay</span>
                            <span class="info-value small text-secondary">${formatDate(p.admission_date)}</span>
                        </div>
                    </div>
                    <div class="divider-dashed"></div>
                    <div class="mt-2">
                        <span class="info-label d-block mb-1"><i class="fa-solid fa-notes-medical me-1"></i> Sababta Seexinta:</span>
                        <div class="admission-reason-box fw-semibold">
                            ${p.admission_reason}
                        </div>
                    </div>
                </div>
            </div>`;
        });
        $('#admitted_grid').html(html);
    });
}

// 2. Load Bukaanada Aan Jiifin
function loadNonAdmittedPatients() {
    $.get('?url=display_non_admitted', function(res) {
        let html = '';
        if(res.length === 0) {
            html = '<div class="col-12 text-center text-muted py-5"><h5>Ma jiraan bukaanade kale oo diwaangashan.</h5></div>';
            $('#non_admitted_grid').html(html);
            return;
        }
        
        res.forEach(p => {
            let photo = p.profile_pic ? p.profile_pic : 'default.jpg';
            let avatarUrl = `uploads/${photo}`;
            let genderIcon = p.gender.toLowerCase() === 'male' ? '<i class="fa-solid fa-mars text-primary ms-1"></i>' : '<i class="fa-solid fa-venus text-danger ms-1"></i>';
            let statusBadge = p.user_status === 'Active' 
                ? '<span class="badge bg-info-subtle text-info rounded-pill px-2 py-1 small">Active Outpatient</span>'
                : '<span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1 small">Inactive</span>';

            html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card patient-card p-4" style="border-top: 4px solid #00bcd4;">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="${avatarUrl}" class="patient-thumb" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.full_name)}&background=6c757d&color=fff&bold=true'">
                        <div class="flex-grow-1">
                            <h5 class="fw-bold text-dark mb-1 fs-6">${p.full_name} ${genderIcon}</h5>
                            <p class="text-muted small mb-1">${p.age} Sano | ID: #${p.user_id}</p>
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="divider-dashed"></div>
                    <div class="px-1">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label"><i class="fa-solid fa-phone me-1"></i> Tel-ka Bukaanka</span>
                            <span class="info-value">${p.phone}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label"><i class="fa-solid fa-envelope me-1"></i> Email-ka</span>
                            <span class="info-value small text-truncate" style="max-width: 180px;">${p.email}</span>
                        </div>
                    </div>
                    <div class="divider-dashed"></div>
                    <div class="text-center mt-2">
                        <button class="btn btn-sm btn-outline-info w-100 rounded-3 structure-admission" data-id="${p.user_id}">
                            <i class="fa-solid fa-bed-pulse me-1"></i> Seexi Bukaanka (Admit Patient)
                        </button>
                    </div>
                </div>
            </div>`;
        });
        $('#non_admitted_grid').html(html);
    });
}

$(document).ready(() => {
    loadAdmittedPatients();
    loadNonAdmittedPatients();
});
</script>
</body>
</html>