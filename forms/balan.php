<?php
// 1. Bilow session-ka dusha sare si aan u helno xogta qofka soo galay
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================
// DATABASE CONNECTION & LOGIC (PHP)
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
            die(json_encode(["status" => false, "msg" => "Cillad ayaa ku dhacday khadka database-ka."]));
        }
    }
}

// ROUTER LOGIC
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

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

    if ($route == 'get_single_doctor') {
        $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : 0;
        $sql = "SELECT d.doctor_id, d.specialization, u.full_name, u.profile_pic 
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                WHERE d.doctor_id = :doctor_id LIMIT 1";
        $stm = $conn->prepare($sql);
        $stm->execute([':doctor_id' => $doctor_id]);
        echo json_encode($stm->fetch(PDO::FETCH_ASSOC));
        exit;
    }

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

    if ($route == 'book_slot') {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            echo json_encode(["status" => false, "msg" => "Fadlan marka hore bartaada soo gal (Login) si aad ballan u qabsato."]);
            exit;
        }

        if (strtolower($_SESSION['user_type']) !== 'patient') {
            echo json_encode([
                "status" => false, 
                "msg" => "Cudurdaar! Kaliya dadka ku qoran 'Patient' (Bukaan) ayaa ballan qabsan kara. Noocaaga hadda waa: (" . $_SESSION['user_type'] . ")"
            ]);
            exit;
        }

        $patient_id = $_SESSION['user_id'];
        $slot_id = $_POST['slot_id'];

        $check = $conn->prepare("SELECT is_booked FROM schedule_slots WHERE slot_id = :slot_id");
        $check->execute([':slot_id' => $slot_id]);
        $slot = $check->fetch(PDO::FETCH_ASSOC);

        if ($slot && $slot['is_booked'] == 1) {
            echo json_encode(["status" => false, "msg" => "Waan ka xunnahay, booskan hadda la qabsaday! Fadlan dooro saacad kale."]);
            exit;
        }

        $sql = "UPDATE schedule_slots SET is_booked = 1, booked_by = :patient_id WHERE slot_id = :slot_id";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([':patient_id' => $patient_id, ':slot_id' => $slot_id]);

        echo json_encode(["status" => $success, "msg" => "Guul! Ballantaada si ammaan ah ayaa loo qoray."]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habeeb Hospital | Ballan Qabsashada</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-teal: #0097a7;
            --light-teal: #eef9fa;
            --dark-blue: #1a2530;
            --soft-white: #ffffff;
            --booked-gray: #e2e8f0;
            --booked-text: #94a3b8;
        }

        body { 
            background-color: #f4fbfb;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif; 
            color: var(--dark-blue);
        }

        .main-header {
            background: linear-gradient(135deg, #00e5ff, #00838f);
            color: white;
            padding: 30px 20px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 25px rgba(0, 151, 167, 0.15);
        }

        .doc-profile-card {
            background: var(--soft-white);
            border: 2px solid transparent;
            border-radius: 24px;
            box-shadow: 0 12px 24px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            text-align: center;
            padding: 30px 20px;
        }
        .doc-profile-card:hover {
            border-color: var(--primary-teal);
            transform: translateY(-5px);
            box-shadow: 0 20px 35px rgba(0,151,167,0.1);
        }
        .doc-avatar-big {
            width: 120px; height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--light-teal);
            margin-bottom: 15px;
        }
        .badge-experience {
            background-color: var(--light-teal);
            color: var(--primary-teal);
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            display: inline-block;
        }
        .btn-choose-doc {
            background-color: var(--primary-teal);
            color: white;
            font-weight: 700;
            border: none;
            border-radius: 16px;
            padding: 12px 25px;
            width: 100%;
            margin-top: 15px;
            transition: background 0.2s;
        }
        .btn-choose-doc:hover { background-color: #007c8a; color: white; }

        .step-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark-blue);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step-number {
            background-color: var(--primary-teal);
            color: white;
            width: 28px; height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .days-container-simple {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 10px 0;
            margin-bottom: 25px;
        }
        .day-box {
            background-color: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 20px;
            min-width: 100px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .day-box:hover { border-color: var(--primary-teal); }
        .day-box.selected {
            background-color: var(--primary-teal);
            border-color: var(--primary-teal);
            color: white;
            box-shadow: 0 8px 20px rgba(0, 151, 167, 0.25);
        }
        .day-box .d-name { font-weight: 700; font-size: 1rem; display: block; }
        .day-box .d-date { font-size: 0.85rem; opacity: 0.8; }

        .time-section-box {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.01);
        }
        .time-section-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 15px;
            color: #4a5568;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .time-grid-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
        }
        .patient-time-btn {
            background-color: #fafafa;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 10px;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--dark-blue);
            transition: all 0.2s;
            text-align: center;
        }
        .patient-time-btn:hover:not(.booked) {
            border-color: var(--primary-teal);
            background-color: var(--light-teal);
            transform: scale(1.03);
        }
        .patient-time-btn.booked {
            background-color: var(--booked-gray);
            border-color: var(--booked-gray);
            color: var(--booked-text);
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .btn-back-home {
            background: white;
            border: 2px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 14px;
            font-weight: 600;
            color: var(--dark-blue);
            transition: all 0.2s;
        }
        .btn-back-home:hover { background-color: #f7fafc; border-color: #cbd5e1; }

        /* PRINT STYLES FOR THE TICKET (PDF EFFECT) */
        @media print {
            body * { visibility: hidden; }
            #print_ticket_area, #print_ticket_area * { visibility: visible; }
            #print_ticket_area { position: absolute; left: 0; top: 0; width: 100%; padding: 40px; }
            .no-print { display: none !important; }
        }

        .ticket-box {
            border: 3px dashed var(--primary-teal);
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            max-width: 550px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<!-- APP INTERFACE -->
<div class="no-print">
    <header class="main-header text-center mb-5">
        <h2 class="fw-bold mb-1"><i class="fa-solid fa-heart-pulse me-2"></i> Habeeb Hospital</h2>
        <p class="mb-0 opacity-90 fs-6">Si fudud u qabso ballantaada caafimaad adigoo gurigaaga jooga</p>
    </header>

    <div class="container mb-5">
        <div id="directory_section" class="animate__animated animate__fadeIn">
            <div class="text-center mb-4">
                <h4 class="fw-bold">Dooro Dhakhtarka Aad Rabto</h4>
                <p class="text-muted small">Guji badhanka buluugga ah ee dhakhtarka aad u baahan tahay</p>
            </div>
            <div class="row g-4 justify-content-center" id="doctors_cards_area"></div>
        </div>

        <div id="booking_section" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button class="btn btn-back-home" id="back_to_list_btn">
                    <i class="fa-solid fa-arrow-left me-2"></i>Ku noqo liiska dhakhaatiirta
                </button>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-title"><span class="step-number">1</span> Dhakhtarka la doortay</div>
                    <div class="card border-0 shadow-sm p-4 text-center rounded-4 bg-white">
                        <img src="" id="current_doc_img" class="doc-avatar-big" alt="Doctor">
                        <h4 class="fw-bold text-dark mb-1" id="current_doc_name"></h4>
                        <p class="text-muted mb-3" id="current_doc_specialty"></p>
                        <span class="badge bg-light text-dark border p-2 w-100 rounded-3">
                            <i class="fa-solid fa-hospital text-info me-1"></i> Habeeb Psychiatric Hospital
                        </span>
                        <div class="mt-4 text-start d-none">
                            <select id="doctor_select" class="form-select"></select>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div id="booking_flow_area" class="d-none">
                        <div class="step-title"><span class="step-number">2</span> Dooro Maalinta</div>
                        <div class="days-container-simple" id="days_navigation"></div>

                        <div class="step-title"><span class="step-number">3</span> Dooro Saacada kuu furan</div>
                        
                        <div id="hours_display_wrapper">
                            <div class="time-section-box" id="panel_Morning">
                                <div class="time-section-title d-flex justify-content-between">
                                    <span><i class="fa-regular fa-sun text-warning me-2"></i> Subaxnimadii</span>
                                    <span class="badge bg-light text-muted" id="lbl_Morning">0 boos</span>
                                </div>
                                <div class="time-grid-buttons" id="grid_Morning"></div>
                            </div>

                            <div class="time-section-box" id="panel_Afternoon">
                                <div class="time-section-title d-flex justify-content-between">
                                    <span><i class="fa-solid fa-cloud-sun text-info me-2"></i> Galabnimadii</span>
                                    <span class="badge bg-light text-muted" id="lbl_Afternoon">0 boos</span>
                                </div>
                                <div class="time-grid-buttons" id="grid_Afternoon"></div>
                            </div>

                            <div class="time-section-box" id="panel_Evening">
                                <div class="time-section-title d-flex justify-content-between">
                                    <span><i class="fa-solid fa-moon text-primary me-2"></i> Fiidnimadii</span>
                                    <span class="badge bg-light text-muted" id="lbl_Evening">0 boos</span>
                                </div>
                                <div class="time-grid-buttons" id="grid_Evening"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HIDDEN PRINT AREA FOR TICKET GENERATION -->
<div id="print_ticket_area" class="d-none d-print-block container mt-5">
    <div class="ticket-box shadow-lg text-center">
        <div class="py-3 mb-4" style="background-color: var(--primary-teal); color: white; border-radius: 12px;">
            <h3 class="fw-bold mb-0">HABEEB HOSPITAL</h3>
            <small>Official Appointment Ticket</small>
        </div>
        <h4 class="text-success fw-bold mb-4"><i class="fa-solid fa-circle-check"></i> BALLANTA WAA LA XAJISAY</h4>
        
        <div class="text-start border-bottom pb-3 mb-3">
            <p class="mb-2"><strong>Magaca Bukaanka:</strong> <span id="t_patient"><?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Bukaan'; ?></span></p>
            <p class="mb-2"><strong>Dhakhtarka:</strong> <span id="t_doctor"></span></p>
            <p class="mb-2"><strong>Maalinta:</strong> <span id="t_date"></span></p>
            <p class="mb-0"><strong>Saacadda Kulanka:</strong> <span id="t_time" class="text-info fw-bold fs-5"></span></p>
        </div>
        
        <p class="text-muted small italic">"Waxaad goosatay ticket oo ah saacadaas kor ku xusan in aad la kulanto dhaqtarka. Fadlan la kaalay tigidhkan marka aad imaanayso cosbitaalka."</p>
        <hr>
        <button class="btn btn-sm btn-secondary no-print mt-2" onclick="window.print()"><i class="fa-solid fa-print"></i> Daabaco Ticket-ka</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let globalSlots = [];
let currentSelectedDate = '';

function cleanTimeFormat(timeStr) {
    let [h, m] = timeStr.split(':');
    h = parseInt(h);
    let ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h}:${m} ${ap}`;
}

function fetchHospitalDirectory() {
    $.get('?url=display', function(data) {
        let cardsHtml = '';
        if(data.length === 0) {
            cardsHtml = '<div class="col-12 text-center text-muted py-5"><h5>Hadda ma jiraan dhakhaatiir diyaar ah.</h5></div>';
        }
        
        data.forEach(doc => {
            let imgFile = doc.profile_pic ? doc.profile_pic : 'default.jpg';
            cardsHtml += `
            <div class="col-md-6 col-lg-4">
                <div class="doc-profile-card">
                    <img src="uploads/${imgFile}" class="doc-avatar-big" alt="Doctor" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(doc.full_name)}&background=00bcd4&color=fff&bold=true'">
                    <h5 class="fw-bold mb-1">Dr. ${doc.full_name}</h5>
                    <p class="text-muted small mb-2">${doc.specialization}</p>
                    <div class="mb-3"><span class="badge-experience">Habeeb Hospital</span></div>
                    <button class="btn btn-choose-doc action-start-booking" data-id="${doc.doctor_id}">
                        <i class="fa-solid fa-calendar-days me-2"></i>Qabso Saacad
                    </button>
                </div>
            </div>`;
        });
        $('#doctors_cards_area').html(cardsHtml);
    });
}

function populateDropdown(selectedId = null) {
    $.get('?url=get_doctors', function(res) {
        let options = '<option value="">-- Dooro --</option>';
        res.forEach(d => { options += `<option value="${d.doctor_id}">${d.full_name}</option>`; });
        $('#doctor_select').html(options);
        if(selectedId) $('#doctor_select').val(selectedId).trigger('change');
    });
}

function buildCalendarTabs(slots) {
    let dates = [...new Set(slots.map(s => s.available_date))].sort();
    if(dates.length === 0) {
        $('#days_navigation').html('<div class="text-danger p-2 fw-semibold"><i class="fa-solid fa-circle-exclamation me-1"></i> Dhakhtarkaan weli ma hayo saacado furan.</div>');
        return;
    }

    let html = '';
    dates.forEach((dStr, idx) => {
        let dObj = new Date(dStr);
        let somaliDays = ["Axad", "Isniin", "Talaado", "Arbaco", "Khamiis", "Jimce", "Sabti"];
        let dayName = somaliDays[dObj.getDay()];
        let isSelected = (idx === 0) ? 'selected' : '';
        if(idx === 0) currentSelectedDate = dStr;

        let formattedDate = dStr.split('-').reverse().slice(0,2).join('/');

        html += `
            <div class="day-box ${isSelected}" data-date="${dStr}">
                <span class="d-name">${dayName}</span>
                <span class="d-date">${formattedDate}</span>
            </div>`;
    });
    $('#days_navigation').html(html);
}

$('#doctor_select').change(function() {
    let docId = $(this).val();
    if(!docId) return;

    $.get('?url=get_single_doctor&doctor_id=' + docId, function(doc) {
        if(doc) {
            $('#current_doc_name').text("Dr. " + doc.full_name);
            $('#current_doc_specialty').text(doc.specialization);
            let photo = doc.profile_pic ? doc.profile_pic : 'default.jpg';
            $('#current_doc_img').attr('src', 'uploads/' + photo).attr('onerror', `this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(doc.full_name)}&background=00bcd4&color=fff&bold=true'`);
        }
    });

    $.get('?url=get_doctor_slots&doctor_id=' + docId, function(res) {
        globalSlots = res;
        buildCalendarTabs(res);
        $('#booking_flow_area').removeClass('d-none');
        drawAvailableHours();
    });
});

$(document).on('click', '.day-box', function() {
    $('.day-box').removeClass('selected');
    $(this).addClass('selected');
    currentSelectedDate = $(this).data('date');
    drawAvailableHours();
});

function drawAvailableHours() {
    $('#grid_Morning, #grid_Afternoon, #grid_Evening').html('');
    let counters = { Morning: 0, Afternoon: 0, Evening: 0 };
    let filtered = globalSlots.filter(s => s.available_date === currentSelectedDate);

    filtered.forEach(s => {
        let humanTime = cleanTimeFormat(s.slot_time.substring(0,5));
        let isBooked = parseInt(s.is_booked) ? 'booked' : '';
        let btnText = parseInt(s.is_booked) ? `${humanTime} (Xafidan)` : humanTime;
        
        let htmlBtn = `<button class="patient-time-btn ${isBooked}" data-id="${s.slot_id}">${btnText}</button>`;
        $(`#grid_${s.session}`).append(htmlBtn);
        if(!parseInt(s.is_booked)) counters[s.session]++;
    });

    $('#lbl_Morning').text(`${counters.Morning} boos furan`);
    $('#lbl_Afternoon').text(`${counters.Afternoon} boos furan`);
    $('#lbl_Evening').text(`${counters.Evening} boos furan`);

    ['Morning', 'Afternoon', 'Evening'].forEach(sess => {
        if ($(`#grid_${sess}`).children().length === 0) {
            $(`#grid_${sess}`).html('<div class="text-muted small py-2">Ma jiraan saacado la heli karo waqtigaan.</div>');
        }
    });
}

$(document).on('click', '.action-start-booking', function() {
    let id = $(this).data('id');
    $('#directory_section').addClass('d-none');
    $('#booking_section').removeClass('d-none').addClass('animate__animated animate__fadeIn');
    populateDropdown(id);
});

$('#back_to_list_btn').click(function() {
    $('#booking_section').addClass('d-none');
    $('#directory_section').removeClass('d-none').addClass('animate__animated animate__fadeIn');
    fetchHospitalDirectory();
});

// QABSASHADA BALLANTA IYU DAABACAADA TICKET-KA
$(document).on('click', '.patient-time-btn:not(.booked)', function() {
    let slotId = $(this).data('id');
    let timeLabel = $(this).text().trim();
    let docName = $('#current_doc_name').text();
    let appointmentDate = currentSelectedDate;

    Swal.fire({
        title: 'Ma hubtaa saacaddaan?',
        text: `Waxaad codsanaysaa saacadda: ${timeLabel}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0097a7',
        cancelButtonColor: '#ff527b',
        confirmButtonText: 'Haa, waan rabaa',
        cancelButtonText: 'Iska daa'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('?url=book_slot', { slot_id: slotId }, function(res) {
                if(res.status) {
                    
                    // 1. Ku qor xogta Tigidhka qarsoon ee diyaarka u ah Print-ga
                    $('#t_doctor').text(docName);
                    $('#t_date').text(appointmentDate.split('-').reverse().join('/'));
                    $('#t_time').text(timeLabel);
                    
                    // 2. Soo saar Alert-ka Guusha leh fariintii aad rabtay
                    Swal.fire({
                        title: 'Guul weyn!',
                        text: `Waxaad goosatay ticket oo ah saacadaas (${timeLabel}) in aad la kulanto dhaqtarka (${docName}).`,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#0097a7',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fa-solid fa-print"></i> Daabaco / Save as PDF',
                        cancelButtonText: 'Xir'
                    }).then((printResult) => {
                        if (printResult.isConfirmed) {
                            // Si toos ah u fur interface-ka daabacaadda browser-ka (Save as PDF)
                            window.print();
                        }
                    });

                    // Dib u cusbooneysii saacadaha
                    let activeDoc = $('#doctor_select').val();
                    $.get('?url=get_doctor_slots&doctor_id=' + activeDoc, function(refresh) {
                        globalSlots = refresh;
                        drawAvailableHours();
                    });
                } else {
                    Swal.fire({ title: 'Waa la diiday!', text: res.msg, icon: 'error', confirmButtonColor: '#ff527b' });
                }
            }, 'json');
        }
    });
});

$(document).ready(() => {
    fetchHospitalDirectory();
});
</script>
</body>
</html>