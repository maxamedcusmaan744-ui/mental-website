<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC (PHP)
// =========================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Jileyn: Waxaan halkaan ku qeexeynaa in dhakhtarku soo galay (Kaga bedelo login-kaaga rasmiga ah)
$_SESSION['user_type'] = 'Doctor';
$_SESSION['doctor_id'] = 1; // ID-ga dhakhtarka hadda login-ka ah

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

// Xaqiiji in isticmaalahu yahay Dhakhtar
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Doctor') {
    die("Error: Ma haysatid ogolaansho aad ku aragto shaashaddan.");
}

// ROUTER LOGIC
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    $current_doctor_id = $_SESSION['doctor_id'];
    header('Content-Type: application/json');

    // 1. Hel dhammaan ballamaha u xafidan dhakhtarka (Booked Appointments)
    if ($route == 'get_doctor_appointments') {
        $sql = "SELECT 
                    ss.slot_id, 
                    ss.slot_time, 
                    s.available_date, 
                    s.session,
                    u.full_name AS patient_name,
                    u.profile_pic AS patient_pic
                FROM schedule_slots ss
                JOIN schedules s ON ss.schedule_id = s.schedule_id
                JOIN users u ON ss.booked_by = u.user_id
                WHERE s.doctor_id = :doctor_id AND ss.is_booked = 1
                ORDER BY s.available_date ASC, ss.slot_time ASC";
                
        $stm = $conn->prepare($sql);
        $stm->execute([':doctor_id' => $current_doctor_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Hel tirada guud ee ballamaha maanta ama kuwa u xafidan (Statistics)
    if ($route == 'get_doctor_stats') {
        $sql = "SELECT 
                    COUNT(CASE WHEN ss.is_booked = 1 THEN 1 END) as total_booked,
                    COUNT(CASE WHEN s.available_date = CURDATE() AND ss.is_booked = 1 THEN 1 END) as today_booked
                FROM schedule_slots ss
                JOIN schedules s ON ss.schedule_id = s.schedule_id
                WHERE s.doctor_id = :doctor_id";
        $stm = $conn->prepare($sql);
        $stm->execute([':doctor_id' => $current_doctor_id]);
        echo json_encode($stm->fetch(PDO::FETCH_ASSOC));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Portal | Ballamaha Bukaanada</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --bg-cyan: #f4fbfb;
            --main-dark: #121124;
            --main-card-bg: rgba(255, 255, 255, 0.9);
            --primary-pink: #ff527b;
            --accent-cyan: #00bcd4;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
            --text-muted: #758a99;
        }

        body { 
            background: linear-gradient(135deg, #f4fbfb 0%, #e6f7f8 100%); 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            color: #2c3e50;
            min-height: 100vh;
        }

        .app-header {
            display: flex;
            align-items: center;
            padding: 25px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0, 188, 212, 0.1);
        }

        .back-btn {
            background: white; border: none; width: 45px; height: 45px;
            border-radius: 14px; display: flex; align-items: center;
            justify-content: center; box-shadow: 0 8px 20px rgba(0,151,167,0.08);
            cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--main-dark);
            margin-right: 20px;
        }
        .back-btn:hover { 
            transform: translateX(-3px);
            background: var(--gradient-cyan); 
            color: white; 
            box-shadow: 0 8px 20px rgba(0,151,167,0.25);
        }

        /* Full Width Glassmorphic Container */
        .main-glass-card {
            background: var(--main-card-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(18, 17, 36, 0.05);
            padding: 35px;
            margin-bottom: 50px;
        }

        /* Mini Info Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            border: 1px solid rgba(0, 188, 212, 0.1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        /* Appointments Table Styling */
        .appointments-table-container {
            background: white;
            border-radius: 18px;
            padding: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.01);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .table-appointments {
            margin-bottom: 0;
            vertical-align: middle;
        }
        .table-appointments th {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding: 15px;
        }
        .table-appointments td {
            padding: 15px;
            border-bottom: 1px solid #f8fafc;
        }
        .patient-avatar {
            width: 45px; height: 45px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #eef9fa;
        }

        .badge-session {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .session-Morning { background-color: #fff9db; color: #f59f00; }
        .session-Afternoon { background-color: #e3fafc; color: #0c8599; }
        .session-Evening { background-color: #edf2ff; color: #4c6ef5; }

        .no-data {
            text-align: center;
            padding: 40px 0;
            color: var(--text-muted);
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container-fluid px-5 my-4">
    
    <div class="app-header">
        <button class="back-btn" onclick="window.history.back();" title="Gaqso Dib"><i class="fa-solid fa-arrow-left"></i></button>
        <div>
            <h3 class="mb-1 fw-bold text-dark">Shaashadda Dhakhtarka (Doctor Appointment Dashboard)</h3>
            <p class="text-muted mb-0 small">Halkaan ka eeg dhamaan ballamaha ay bukaanadu kuu qabsadeen.</p>
        </div>
    </div>

    <div class="main-glass-card">
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--gradient-cyan);">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block fw-semibold">Guud ahaan Ballamaha</span>
                        <h4 class="fw-bold mb-0 text-dark" id="stat_total">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff8e53, #ff527b);">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block fw-semibold">Ballamaha Maanta</span>
                        <h4 class="fw-bold mb-0 text-dark" id="stat_today">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-user-injured text-info me-2"></i> Liiska Ballamaha Bukaanada</h5>
                
                <div class="appointments-table-container">
                    <table class="table table-appointments d-none" id="appointments_table">
                        <thead>
                            <tr>
                                <th>Bukaanka (Patient)</th>
                                <th>Maalinta (Date)</th>
                                <th>Saacadda (Time)</th>
                                <th>Qaybta (Session)</th>
                                <th>Xaaladda</th>
                            </tr>
                        </thead>
                        <tbody id="appointments_tbody">
                            </tbody>
                    </table>
                    
                    <div id="no_appointments_msg" class="no-data">
                        <i class="fa-regular fa-calendar-times fa-2x mb-2 d-block text-muted"></i>
                        Weli ma jiraan wax ballan ah oo laguu qabsaday.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function formatTo12Hour(timeString) {
    let [hours, minutes] = timeString.split(':');
    hours = parseInt(hours);
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
}

function getSomaliDayName(dateStr) {
    let dateObj = new Date(dateStr);
    let jsDaysMap = ["Axad", "Isniin", "Talaado", "Arbaco", "Khamiis", "Jimce", "Sabti"];
    return jsDaysMap[dateObj.getDay()];
}

function loadDashboardData() {
    // 1. Load Stats
    $.get('?url=get_doctor_stats', function(stats) {
        if(stats) {
            $('#stat_total').text(stats.total_booked);
            $('#stat_today').text(stats.today_booked);
        }
    });

    // 2. Load Appointments
    $.get('?url=get_doctor_appointments', function(res) {
        if (res && res.length > 0) {
            $('#no_appointments_msg').addClass('d-none');
            $('#appointments_table').removeClass('d-none');
            
            let html = '';
            res.forEach(app => {
                let timeFormatted = formatTo12Hour(app.slot_time.substring(0, 5));
                let dayName = getSomaliDayName(app.available_date);
                let photo = app.patient_pic ? app.patient_pic : 'default.jpg';
                
                // Turjumaad qaybaha maalinta
                let sessionLabels = { 'Morning': 'Subax', 'Afternoon': 'Galab', 'Evening': 'Fiid' };
                let sessionSomali = sessionLabels[app.session] || app.session;

                html += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="uploads/${photo}" class="patient-avatar" alt="Patient" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(app.patient_name)}&background=00bcd4&color=fff&bold=true'">
                                <div>
                                    <div class="fw-bold text-dark">${app.patient_name}</div>
                                    <span class="text-muted small">ID: #${app.slot_id}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">${app.available_date}</div>
                            <span class="text-muted small">${dayName}</span>
                        </td>
                        <td>
                            <div class="badge bg-light text-dark border p-2 fw-bold"><i class="fa-regular fa-clock text-info me-1"></i> ${timeFormatted}</div>
                        </td>
                        <td>
                            <span class="badge-session session-${app.session}">${sessionSomali}</span>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-3"><i class="fa-solid fa-circle-check me-1"></i> Waa Hubaa</span>
                        </td>
                    </tr>
                `;
            });
            $('#appointments_tbody').html(html);
        } else {
            $('#no_appointments_msg').removeClass('d-none');
            $('#appointments_table').addClass('d-none');
        }
    });
}

$(document).ready(() => {
    loadDashboardData();
});
</script>
</body>
</html>