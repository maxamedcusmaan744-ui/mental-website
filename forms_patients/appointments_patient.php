<?php
// PHP LOGIC (Sidiisii ayuu u qoran yahay)
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

if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    if ($route == 'get_users') {
        $stm = $conn->prepare("SELECT user_id, full_name, user_type FROM users WHERE status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($route == 'create') {
        $sql = "INSERT INTO appointments (patient_id, counsellor_id, appointment_date, status, notes)
                VALUES (:p_id, :c_id, :date, :status, :notes)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':p_id'   => $_POST['patient_id'],
            ':c_id'   => $_POST['counsellor_id'],
            ':date'   => $_POST['appointment_date'],
            ':status' => $_POST['status'],
            ':notes'  => $_POST['notes']
        ]);
        echo json_encode(["status" => $success, "msg" => "Ballanta si guul leh ayaa loo qabsaday!"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ballan Qabsasho Professional ah</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #0891b2;
            --soft-bg: #f8fafc;
            --accent: #0284c7;
        }

        body { 
            /* BACKGROUND IMAGE KA HALKAN AYUU KU JIRAA */
            background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.7)), 
                        url('https://images.unsplash.com/photo-1516549655169-df83a0774514?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .main-wrapper {
            width: 100%;
            max-width: 1050px; 
            min-height: 600px;
            background: rgba(255, 255, 255, 0.95); /* In yar oo hufnaan ah si uu background-ka u dareemo */
            backdrop-filter: blur(10px); /* Glassmorphism effect yar */
            border-radius: 30px;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
            display: flex;
            overflow: hidden;
            animation: slideIn 0.8s ease-out;
        }

        /* Bidix: Sawirka Dhakhtarka ku dhex jira container-ka */
        .visual-side {
            width: 45%;
            background: linear-gradient(rgba(8, 145, 178, 0.3), rgba(8, 145, 178, 0.5)),
                        url('https://images.unsplash.com/photo-1559839734-2b71f1536783?auto=format&fit=crop&w=800&q=80');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 40px;
        }

        .visual-side::after {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(transparent, rgba(8, 145, 178, 0.9));
        }

        .visual-content {
            position: relative;
            z-index: 2;
            color: white;
        }

        .visual-content h2 { font-weight: 800; font-size: 35px; margin-bottom: 10px; }
        .visual-content p { font-size: 16px; opacity: 0.95; line-height: 1.5; }

        /* Midig: Form-ka */
        .form-side {
            width: 55%;
            padding: 50px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header { margin-bottom: 30px; }
        .form-header h4 {
            color: var(--primary-cyan);
            font-weight: 800;
            font-size: 28px;
            margin: 0;
        }
        .form-header span { color: #64748b; font-size: 15px; }

        .input-group-text {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: var(--primary-cyan);
            border-radius: 12px 0 0 12px;
        }

        .form-control, .form-select {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 0 12px 12px 0;
            font-size: 15px;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: var(--primary-cyan);
            background: #fff;
        }

        .btn-cyan {
            background: var(--primary-cyan);
            border: none;
            color: white;
            padding: 16px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(8, 145, 178, 0.3);
        }

        .btn-cyan:hover {
            background: var(--accent);
            transform: translateY(-2px);
        }

        .label-style {
            font-weight: 700;
            font-size: 12px;
            color: #475569;
            margin-bottom: 5px;
            display: block;
            text-transform: uppercase;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; width: 100%; }
            .visual-side { width: 100%; height: 200px; }
            .form-side { width: 100%; padding: 30px; }
            body { height: auto; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Bidix Visual Section -->
    <div class="visual-side">
        <div class="visual-content">
            <i class="fa-solid fa-heart-pulse mb-3" style="font-size: 40px;"></i>
            <h2>Is daryeelku waa muhiim.</h2>
            <p>Waxaan kuu diyaarinay khubaro u tababaran dhageysigaaga iyo caawintaada. Ballan qabso hadda si aad u hesho daryeel tayo leh.</p>
        </div>
    </div>

    <!-- Midig Form Section -->
    <div class="form-side">
        <div class="form-header">
            <h4>Qabso Ballan Cusub</h4>
            <span>Fadlan buuxi foomka hoose si aad u ballansato dhakhtarkaaga.</span>
        </div>
        
        <form id="appointmentForm">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="label-style">Magaca Bukaanka</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">Soo raryaa...</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="label-style">Dooro Dhakhtarka / La-taliyaha</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user-doctor"></i></span>
                        <select name="counsellor_id" id="counsellor_id" class="form-select" required>
                            <option value="">Soo raryaa...</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="label-style">Taariikhda iyo Saacadda</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-alt"></i></span>
                        <input type="datetime-local" name="appointment_date" class="form-control" required>
                    </div>
                </div>

                <input type="hidden" name="status" value="Pending">

                <div class="col-md-12 mb-4">
                    <label class="label-style">Sababta Ballanta (Notes)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-comment-medical"></i></span>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Maxaan kaa caawinaa?"></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-cyan w-100">
                <i class="fa-solid fa-check-circle me-2"></i> Xaqiiji Ballanta
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function loadUsers() {
    $.get('?url=get_users', function(res) {
        let p = '<option value="">Dooro Bukaanka</option>';
        let c = '<option value="">Dooro La-taliyaha</option>';
        res.forEach(u => {
            if(u.user_type === 'Patient') p += `<option value="${u.user_id}">${u.full_name}</option>`;
            if(u.user_type === 'Counsellor') c += `<option value="${u.user_id}">${u.full_name}</option>`;
        });
        $('#patient_id').html(p);
        $('#counsellor_id').html(c);
    });
}

$('#appointmentForm').submit(function(e) {
    e.preventDefault();
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Waa la hubinayaa...');

    $.post('?url=create', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({
                title: 'Guul!',
                text: res.msg,
                icon: 'success',
                confirmButtonColor: '#0891b2'
            });
            $('#appointmentForm')[0].reset();
        } else {
            Swal.fire('Khalad', 'Waxbaa khaldamay, fadlan isku day markale.', 'error');
        }
        submitBtn.prop('disabled', false).html(originalText);
    });
});

$(document).ready(() => {
    loadUsers();
});
</script>

</body>
</html>