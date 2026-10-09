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

    // 1. Hel dhakhtarada jira si loogu dhex doorto Dropdown-ka
    if ($route == 'get_doctors') {
        $sql = "SELECT d.doctor_id, u.full_name 
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                ORDER BY u.full_name ASC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Hel xogta hal dhakhtar oo kaliya (Profile Image & Magac)
    if ($route == 'get_single_doctor') {
        $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : 0;
        $sql = "SELECT d.doctor_id, u.full_name, u.profile_pic 
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                WHERE d.doctor_id = :doctor_id LIMIT 1";
        $stm = $conn->prepare($sql);
        $stm->execute([':doctor_id' => $doctor_id]);
        echo json_encode($stm->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Soo saar waqtiyada (Slots) uu leeyahay dhakhtarka la doortay
    if ($route == 'get_doctor_slots') {
        $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : 0;
        
        $sql = "SELECT ss.slot_id, ss.slot_time, ss.is_booked, s.available_date, s.session
                FROM schedule_slots ss
                JOIN schedules s ON ss.schedule_id = s.schedule_id
                WHERE s.doctor_id = :doctor_id
                ORDER BY s.available_date ASC, ss.slot_time ASC";
                
        $stm = $conn->prepare($sql);
        $stm->execute([':doctor_id' => $doctor_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 3. Boos qabsashada Ballanka (Booking Action)
    if ($route == 'book_slot') {
        $patient_id = 1; 
        $slot_id = $_POST['slot_id'];

        $check = $conn->prepare("SELECT is_booked FROM schedule_slots WHERE slot_id = :slot_id");
        $check->execute([':slot_id' => $slot_id]);
        $slot = $check->fetch(PDO::FETCH_ASSOC);

        if ($slot && $slot['is_booked'] == 1) {
            echo json_encode(["status" => false, "msg" => "Waan ka xunnahay, booskan hadda waa la qabsaday!"]);
            exit;
        }

        $sql = "UPDATE schedule_slots SET is_booked = 1, booked_by = :patient_id WHERE slot_id = :slot_id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':patient_id' => $patient_id,
            ':slot_id'    => $slot_id
        ]);

        echo json_encode(["status" => $success, "msg" => "Ballantaada si guul leh ayaa loo qabsaday!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal | Ballan Qabsashada Website</title>
    
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

        .custom-select-card {
            background: #fff;
            border: 1px solid rgba(0, 188, 212, 0.15);
            border-radius: 16px;
            transition: all 0.3s ease;
        }
        .custom-select-card:focus-within {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.1);
        }

        /* Doctor Info Table Styling - Expanded for Desktop */
        .doctor-table-container {
            background: white;
            border-radius: 18px;
            padding: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.01);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .table-doctor {
            margin-bottom: 0;
            vertical-align: middle;
        }
        .table-doctor th {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding: 15px;
        }
        .table-doctor td {
            padding: 15px;
            border-bottom: none;
        }
        .table-avatar {
            width: 65px; height: 65px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid #eef9fa;
        }

        /* Days Horizontal selector - Desktop Optimization */
        .days-scroll-container {
            background-color: var(--main-dark);
            border-radius: 20px;
            padding: 15px;
            display: flex;
            overflow-x: auto;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(18, 11, 36, 0.15);
            margin-bottom: 25px;
        }
        .days-scroll-container::-webkit-scrollbar { height: 6px; }
        .days-scroll-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

        .day-tab {
            color: rgba(255, 255, 255, 0.6); text-align: center; padding: 12px 20px;
            border-radius: 14px; cursor: pointer; transition: all 0.3s ease;
            flex: 1 0 auto; min-width: 105px;
        }
        .day-tab .day-name { font-size: 0.9rem; font-weight: 600; display: block; }
        .day-tab .day-date { font-size: 0.8rem; font-weight: 500; display: block; margin-top: 4px; opacity: 0.8; }
        
        .day-tab:hover { color: #fff; background: rgba(255,255,255,0.08); }
        .day-tab.active {
            background-color: var(--accent-cyan); color: white;
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.3);
            transform: translateY(-2px);
        }

        /* Legend Style */
        .status-legend {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            padding: 12px;
            font-size: 0.9rem;
            display: flex;
            justify-content: flex-start;
            gap: 25px;
            margin-bottom: 25px;
            border: 1px dashed rgba(0,0,0,0.05);
        }
        .legend-item { display: flex; align-items: center; gap: 8px; font-weight: 500; }
        .legend-dot { width: 14px; height: 14px; border-radius: 4px; }
        .legend-available { background: white; border: 1px solid #ddd; }
        .legend-booked { background: var(--primary-pink); }

        /* Booking Slots Grid Style - Responsive Row Grid */
        .slots-display-area {
            background: transparent;
        }

        .session-section { 
            margin-bottom: 25px; 
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.01);
            border: 1px solid rgba(0,0,0,0.03);
        }
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-cyan);
            padding-left: 12px;
        }
        .session-title { font-weight: 700; color: var(--main-dark); font-size: 1.1rem; }
        .session-count { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; background: #eef9fa; padding: 5px 12px; border-radius: 20px; }

        /* More grids for desktop monitor screens */
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 12px;
        }

        .time-slot-btn {
            background-color: white; 
            color: var(--main-dark);
            border: 1px solid #e2e8f0; 
            padding: 14px 10px; 
            font-weight: 600;
            border-radius: 12px; 
            font-size: 0.95rem;
            transition: all 0.25s ease; 
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.01);
        }
        .time-slot-btn:hover {
            border-color: var(--accent-cyan);
            background: #e6f7f8;
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0,188,212,0.15);
        }
        .time-slot-btn.booked {
            background-color: var(--primary-pink) !important; 
            color: rgba(255,255,255,0.9) !important; 
            border-color: var(--primary-pink) !important;
            cursor: not-allowed; 
            pointer-events: none;
            position: relative;
        }
        .time-slot-btn.booked::after {
            content: 'Xafid';
            position: absolute;
            font-size: 0.65rem;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0.8;
        }

        .no-slots { 
            color: var(--text-muted); 
            font-size: 1rem;
            text-align: center; 
            padding: 20px 0; 
            font-weight: 500;
        }
    </style>
</head>
<body>

<!-- Main Website Container (Wide Layout) -->
<div class="container-fluid px-5 my-4">
    
    <!-- Top Website Bar -->
    <div class="app-header">
        <button class="back-btn" onclick="window.history.back();" title="Gaqso Dib"><i class="fa-solid fa-arrow-left"></i></button>
        <div>
            <h3 class="mb-1 fw-bold text-dark">Albaabka Ballamaha (Patient Booking Portal)</h3>
            <p class="text-muted mb-0 small">Dooro dhakhtar iyo saacad furan si aad u qabsato ballan degdeg ah.</p>
        </div>
    </div>

    <!-- Main Glassmorphic Panel -->
    <div class="main-glass-card">
        <div class="row g-4">
            
            <!-- LEFT COLUMN: Doctor Selection & Dynamic Profile Table -->
            <div class="col-lg-5 col-md-12">
                <div class="pe-lg-3">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clipboard-list text-info me-2"></i>1. Xogta Dhakhtarka</h5>
                    
                    <!-- Select Doctor Dropdown Input -->
                    <div class="card custom-select-card border-0 p-3 mb-4 shadow-sm">
                        <label class="small fw-bold text-muted mb-2"><i class="fa-solid fa-user-md me-1 text-info"></i> Dooro Dhakhtarka Aad Rabto:</label>
                        <select id="doctor_select" class="form-select border-0 p-1 fw-bold text-dark shadow-none">
                            <option value="">-- Dooro Dhakhtar --</option>
                        </select>
                    </div>

                    <!-- Doctor Information Table Display -->
                    <div id="doctor_profile_area" class="doctor-table-container d-none animate__animated animate__fadeIn">
                        <table class="table table-doctor">
                            <thead>
                                <tr>
                                    <th>Sawirka</th>
                                    <th>Magaca Dhakhtarka</th>
                                    <th>Cisbitaalka / Goobta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <img src="" id="selected_doctor_img" class="table-avatar" alt="Doc" onerror="this.src='https://ui-avatars.com/api/?background=00bcd4&color=fff&bold=true&name=Doctor'">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark fs-5" id="selected_doctor_name"></div>
                                        <span class="text-muted small"><i class="fa-solid fa-stethoscope text-info me-1"></i> Mental Health Expert</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-hospital text-info me-1"></i> Habeeb Hospital</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Calendar Days Wheel & Time Slots Grid -->
            <div class="col-lg-7 col-md-12">
                <div id="booking_area" class="d-none animate__animated animate__fadeIn">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clock text-info me-2"></i>2. Dooro Maalinta & Saacada</h5>
                    
                    <!-- Full Week Horizontal Selector Container -->
                    <div class="days-scroll-container" id="days_container"></div>

                    <!-- Status Visual Legend -->
                    <div class="status-legend">
                        <div class="legend-item">
                            <span class="legend-dot legend-available"></span>
                            <span class="fw-semibold">Waa Bannaan yahay (Guji si aad u qabsato)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot legend-booked"></span>
                            <span class="fw-semibold">Waa La Qabsaday (Xafid)</span>
                        </div>
                    </div>

                    <!-- Slots Grid Area organized by Day Sessions -->
                    <div class="slots-display-area">
                        
                        <!-- Subax (Morning Session) -->
                        <div class="session-section" id="section_Morning">
                            <div class="session-header">
                                <span class="session-title"><i class="fa-regular fa-sun text-warning me-1"></i> Qaybta Subax (Morning Session)</span>
                                <span class="session-count" id="count_Morning">0 Bannaan</span>
                            </div>
                            <div class="slots-grid" id="slots_Morning"></div>
                        </div>

                        <!-- Galab (Afternoon Session) -->
                        <div class="session-section" id="section_Afternoon">
                            <div class="session-header">
                                <span class="session-title"><i class="fa-solid fa-cloud-sun text-info me-1"></i> Qaybta Galab (Afternoon Session)</span>
                                <span class="session-count" id="count_Afternoon">0 Bannaan</span>
                            </div>
                            <div class="slots-grid" id="slots_Afternoon"></div>
                        </div>

                        <!-- Fiid (Evening Session) -->
                        <div class="session-section" id="section_Evening">
                            <div class="session-header">
                                <span class="session-title"><i class="fa-solid fa-moon text-primary me-1"></i> Qaybta Fiid (Evening Session)</span>
                                <span class="session-count" id="count_Evening">0 Bannaan</span>
                            </div>
                            <div class="slots-grid" id="slots_Evening"></div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let allSlots = []; 
let selectedDateStr = ''; 

function getUrlParameter(name) {
    let results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    return results ? results[1] : null;
}

function formatTo12Hour(timeString) {
    let [hours, minutes] = timeString.split(':');
    hours = parseInt(hours);
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
}

// 1. Dhalinta Maalmaha Isbuuca ee dhakhtarku xogta ku leeyahay
function generateWeekTabs(slotsData) {
    let uniqueDates = [...new Set(slotsData.map(item => item.available_date))].sort();
    
    if(uniqueDates.length === 0) {
        $('#days_container').html('<div class="text-white small p-2 text-center w-100"><i class="fa-solid fa-triangle-exclamation me-1"></i> Dhakhtarku nidaam ma laha weli shaashada</div>');
        return;
    }

    let html = '';
    uniqueDates.forEach((dateStr, index) => {
        let dateObj = new Date(dateStr);
        let dayIndex = dateObj.getDay(); 
        
        let jsDaysMap = ["Axad", "Isniin", "Talaado", "Arbaco", "Khamiis", "Jimce", "Sabti"];
        let daySomaliName = jsDaysMap[dayIndex];

        let activeClass = (index === 0) ? 'active' : '';
        if (index === 0) selectedDateStr = dateStr;

        let formattedShortDate = dateStr.substring(5); 

        html += `
            <div class="day-tab ${activeClass}" data-date="${dateStr}">
                <span class="day-name">${daySomaliName}</span>
                <span class="day-date">${formattedShortDate}</span>
            </div>`;
    });
    
    $('#days_container').html(html);
}

function loadDoctors() {
    $.get('?url=get_doctors', function(res) {
        let html = '<option value="">-- Dooro Dhakhtar --</option>';
        res.forEach(d => {
            html += `<option value="${d.doctor_id}">${d.full_name}</option>`;
        });
        $('#doctor_select').html(html);

        let urlDocId = getUrlParameter('doctor_id');
        if (urlDocId) {
            $('#doctor_select').val(urlDocId).trigger('change');
        }
    });
}

$('#doctor_select').change(function() {
    let doctorId = $(this).val();
    
    if(!doctorId) {
        $('#booking_area').addClass('d-none');
        $('#doctor_profile_area').addClass('d-none');
        return;
    }

    $.get('?url=get_single_doctor&doctor_id=' + doctorId, function(doc) {
        if(doc) {
            $('#selected_doctor_name').text("Dr. " + doc.full_name);
            let photo = doc.profile_pic ? doc.profile_pic : 'default.jpg';
            
            $('#selected_doctor_img').attr('src', 'uploads/' + photo);
            $('#selected_doctor_img').attr('onerror', `this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(doc.full_name)}&background=00bcd4&color=fff&bold=true'`);
            
            $('#doctor_profile_area').removeClass('d-none');
        }
    });

    $.get('?url=get_doctor_slots&doctor_id=' + doctorId, function(res) {
        allSlots = res;
        generateWeekTabs(res); 
        $('#booking_area').removeClass('d-none');
        renderSlots();
    });
});

$(document).on('click', '.day-tab', function() {
    $('.day-tab').removeClass('active');
    $(this).addClass('active');
    selectedDateStr = $(this).data('date'); 
    renderSlots();
});

function renderSlots() {
    $('#slots_Morning, #slots_Afternoon, #slots_Evening').html('');
    let counts = { Morning: 0, Afternoon: 0, Evening: 0 };

    let filtered = allSlots.filter(s => s.available_date === selectedDateStr);

    filtered.forEach(s => {
        let timeFormatted = formatTo12Hour(s.slot_time.substring(0, 5)); 
        let isBookedClass = parseInt(s.is_booked) ? 'booked' : ''; 
        
        let btnHtml = `<button class="time-slot-btn ${isBookedClass}" data-id="${s.slot_id}">
                        ${timeFormatted}
                       </button>`;
        
        $(`#slots_${s.session}`).append(btnHtml);
        counts[s.session]++;
    });

    $('#count_Morning').text(`${counts.Morning} Bannaan`);
    $('#count_Afternoon').text(`${counts.Afternoon} Bannaan`);
    $('#count_Evening').text(`${counts.Evening} Bannaan`);

    ['Morning', 'Afternoon', 'Evening'].forEach(session => {
        if (counts[session] === 0) {
            $(`#slots_${session}`).html('<div class="no-slots"><i class="fa-regular fa-face-frown me-1"></i> Ma jiro boos xilligan ah maalintan.</div>');
        }
    });
}

$(document).on('click', '.time-slot-btn:not(.booked)', function() {
    let slotId = $(this).data('id');
    let timeText = $(this).text().trim();

    Swal.fire({
        title: 'Ma hubtaa Ballantan?',
        text: `Waxaad qabsanaysaa ballan saacaddu tahay: ${timeText}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0097a7',
        cancelButtonColor: '#ff527b',
        confirmButtonText: 'Haa, Codso',
        cancelButtonText: 'Iska daa'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=book_slot', { slot_id: slotId }, function(res) {
                if(res.status) {
                    Swal.fire({
                        title: 'Guul!',
                        text: res.msg,
                        icon: 'success',
                        confirmButtonColor: '#0097a7'
                    });
                    
                    let docId = $('#doctor_select').val();
                    $.get('?url=get_doctor_slots&doctor_id=' + docId, function(refreshRes) {
                        allSlots = refreshRes;
                        renderSlots();
                    });
                } else {
                    Swal.fire('Waa cilad!', res.msg, 'error');
                }
            });
        }
    });
});

$(document).ready(() => {
    loadDoctors();
});
</script>
</body>
</html>