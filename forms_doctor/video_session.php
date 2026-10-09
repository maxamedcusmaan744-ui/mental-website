<?php
// 1. XIRIIRKA DATABASE-KA
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
            die(json_encode(["status" => false, "msg" => "Xiriirka database-ku waa guuldarraystay"]));
        }
    }
}

if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // 1. Soo qaadashada Macluumaadka (Appointments & Users)
    if ($route == 'get_data') {
        $data = [];
        $data['appointments'] = $conn->query("SELECT appointment_id FROM appointments")->fetchAll(PDO::FETCH_ASSOC);
        $data['counsellors'] = $conn->query("SELECT user_id, full_name FROM users WHERE user_type='Counsellor'")->fetchAll(PDO::FETCH_ASSOC);
        $data['patients'] = $conn->query("SELECT user_id, full_name FROM users WHERE user_type='Patient'")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
        exit;
    }

    // 2. Samaynta Fadhiga Fiidiyowga
    if ($route == 'create_session') {
        try {
            $sql = "INSERT INTO video_sessions (appointment_id, counsellor_id, patient_id, session_link, session_time, duration_minutes)
                    VALUES (:a_id, :c_id, :p_id, :link, :time, :duration)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':a_id'     => $_POST['appointment_id'],
                ':c_id'     => $_POST['counsellor_id'],
                ':p_id'     => $_POST['patient_id'],
                ':link'     => $_POST['session_link'],
                ':time'     => $_POST['session_time'],
                ':duration' => $_POST['duration_minutes']
            ]);
            echo json_encode(["status" => true, "msg" => "Fadhiga fiidiyowga si guul leh ayaa loo qorsheeyay!"]);
        } catch (Exception $e) {
            echo json_encode(["status" => false, "msg" => "Khalad: " . $e->getMessage()]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Session | MindCare Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --cyan-main: #0891b2; /* Cyan Darker */
            --cyan-grad: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        body { 
            background: #f0fdfa;
            background-image: linear-gradient(rgba(6, 182, 212, 0.08), rgba(6, 182, 212, 0.08)), 
                              url('https://images.unsplash.com/photo-1588196749597-9ff075ee6b5b?q=80&w=1974&auto=format&fit=crop');
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center; 
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 20px;
        }

        .main-wrapper {
            width: 100%;
            max-width: 1000px; 
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            box-shadow: 0 40px 100px -20px rgba(8, 145, 178, 0.2);
            display: flex;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .visual-side {
            width: 40%;
            background: var(--cyan-grad);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .visual-side::after {
            content: '';
            position: absolute;
            bottom: 0; right: 0; width: 100px; height: 100px;
            background: rgba(255,255,255,0.15);
            border-radius: 100% 0 0 0;
        }

        .form-side { width: 60%; padding: 50px; background: white; }

        .input-group-text { background: #ecfeff; color: var(--cyan-main); border: none; border-radius: 12px 0 0 12px; }
        .form-control, .form-select { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0 12px 12px 0; padding: 12px; font-size: 14px; }
        .form-control:focus, .form-select:focus { border-color: #06b6d4; background: #fff; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1); }
        
        .btn-cyan {
            background: var(--cyan-grad); 
            border: none; color: white; padding: 15px;
            border-radius: 12px; font-weight: 700;
            transition: all 0.3s;
        }

        .btn-cyan:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6, 182, 212, 0.3); color: white; }

        .label-style { font-weight: 700; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: block; }
        
        .link-gen { cursor: pointer; color: var(--cyan-main); font-size: 12px; font-weight: 700; text-decoration: none; }
        .link-gen:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="visual-side">
        <i class="fa-solid fa-video mb-4" style="font-size: 50px;"></i>
        <h2 class="fw-800">Kulamada Online-ka</h2>
        <p class="opacity-75">Maaree oo qorshee fadhiyada fiidiyowga ee u dhexeeya la-taliyaha iyo bukaanka si fudud.</p>
    </div>

    <div class="form-side">
        <div class="mb-4">
            <h4 class="fw-bold text-dark">Qorshee Fadhi Cusub</h4>
            <p class="text-muted small">Fadlan buuxi faahfaahinta kulanka hoose si loo keydiyo.</p>
        </div>
        
        <form id="sessionForm">
            <div class="row">
                <!-- Appointment Selection -->
                <div class="col-md-12 mb-3">
                    <label class="label-style">Xulo Aqoonsiga Ballanta (ID)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                        <select name="appointment_id" id="appointment_id" class="form-select" required>
                            <option value="">Soo raryaa...</option>
                        </select>
                    </div>
                </div>

                <!-- Counsellor -->
                <div class="col-md-6 mb-3">
                    <label class="label-style">La-taliyaha</label>
                    <select name="counsellor_id" id="counsellor_id" class="form-select" style="border-radius:12px" required>
                        <option value="">Dooro...</option>
                    </select>
                </div>

                <!-- Patient -->
                <div class="col-md-6 mb-3">
                    <label class="label-style">Bukaanka</label>
                    <select name="patient_id" id="patient_id" class="form-select" style="border-radius:12px" required>
                        <option value="">Dooro...</option>
                    </select>
                </div>

                <!-- Session Link -->
                <div class="col-md-12 mb-3">
                    <div class="d-flex justify-content-between">
                        <label class="label-style">Link-ga Kulanka (URL)</label>
                        <span class="link-gen" onclick="generateLink()">Guji si aad u dhaliso link</span>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                        <input type="url" name="session_link" id="session_link" class="form-control" placeholder="https://meet.google.com/xxx-xxxx" required>
                    </div>
                </div>

                <!-- Time -->
                <div class="col-md-7 mb-3">
                    <label class="label-style">Waqtiga Kulanka</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-day"></i></span>
                        <input type="datetime-local" name="session_time" class="form-control" required>
                    </div>
                </div>

                <!-- Duration -->
                <div class="col-md-5 mb-4">
                    <label class="label-style">Muddada (Daqiiqo)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-hourglass-half"></i></span>
                        <input type="number" name="duration_minutes" class="form-control" placeholder="60" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-cyan w-100">
                <i class="fa-solid fa-plus-circle me-2"></i> Diwaangeli Fadhiga Fiidiyowga
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Soo rari xogta markii bogg furmo
function loadData() {
    $.get('?url=get_data', function(res) {
        let appts = '<option value="">Dooro Ballanta</option>';
        let couns = '<option value="">Dooro...</option>';
        let pats = '<option value="">Dooro...</option>';

        res.appointments.forEach(a => appts += `<option value="${a.appointment_id}">Ballanta #${a.appointment_id}</option>`);
        res.counsellors.forEach(c => couns += `<option value="${c.user_id}">${c.full_name}</option>`);
        res.patients.forEach(p => pats += `<option value="${p.user_id}">${p.full_name}</option>`);

        $('#appointment_id').html(appts);
        $('#counsellor_id').html(couns);
        $('#patient_id').html(pats);
    });
}

function generateLink() {
    const random = Math.random().toString(36).substring(7);
    $('#session_link').val(`https://meet.jit.si/MindCare-${random}`);
}

$('#sessionForm').submit(function(e) {
    e.preventDefault();
    const btn = $(this).find('button');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Waa la kaydinayaa...');

    $.post('?url=create_session', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({ title: 'Guul!', text: res.msg, icon: 'success', confirmButtonColor: '#0891b2' });
            $('#sessionForm')[0].reset();
        } else {
            Swal.fire('Khalad', res.msg, 'error');
        }
        btn.prop('disabled', false).html('<i class="fa-solid fa-plus-circle me-2"></i> Diwaangeli Fadhiga Fiidiyowga');
    });
});

$(document).ready(() => { loadData(); });
</script>

</body>
</html>