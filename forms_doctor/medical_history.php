<?php
// 1. XIRIIRKA DATABASE-KA & WADDOOYINKA API
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
            die(json_encode(["status" => false, "msg" => "Xiriirka database-ka waa guuldarraystay"]));
        }
    }
}

// Maareynta Codsiyada AJAX
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // Waddada: Soo qaadashada Bukaanada (Kuwa firfircoon oo kaliya)
    if ($route == 'get_patients') {
        $stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Patient' AND status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Waddada: Keydinta Taariikhda Caafimaadka
    if ($route == 'create_history') {
        try {
            $sql = "INSERT INTO medical_history (patient_id, condition_name, description, treatment_history)
                    VALUES (:p_id, :cond, :desc, :treat)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':p_id'  => $_POST['patient_id'],
                ':cond'  => $_POST['condition_name'],
                ':desc'  => $_POST['description'],
                ':treat' => $_POST['treatment_history']
            ]);
            echo json_encode(["status" => true, "msg" => "Taariikhda caafimaadka si guul leh ayaa loo keydiyay!"]);
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
    <title>Diiwaanka Caafimaadka | MindCare Pro</title>
    
    <!-- Agabka Dibadda -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-cyan: #0891b2;
            --cyan-light: #22d3ee;
            --cyan-grad: linear-gradient(135deg, #22d3ee 0%, #0891b2 100%);
            --soft-bg: #f8fafc;
        }

        body { 
            background: url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center; 
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(8, 145, 178, 0.35); 
            backdrop-filter: blur(10px);
            z-index: 0;
        }

        .main-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1050px; 
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.3);
            display: flex;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: slideIn 0.7s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .visual-side {
            width: 42%;
            background: linear-gradient(rgba(8, 145, 178, 0.3), rgba(8, 145, 178, 0.5)),
                        url('https://images.unsplash.com/photo-1505751172107-57322a3ad745?auto=format&fit=crop&w=800&q=80');
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

        .visual-content { position: relative; z-index: 2; color: white; }
        .visual-content h2 { font-weight: 800; font-size: 34px; letter-spacing: -1px; margin-bottom: 10px; }

        .form-side { width: 58%; padding: 50px; display: flex; flex-direction: column; justify-content: center; background: white; }
        .form-header h4 { color: var(--primary-cyan); font-weight: 800; font-size: 28px; }

        .input-group-text { background: #f1f5f9; border: 1px solid #e2e8f0; color: var(--primary-cyan); border-radius: 12px 0 0 12px; }
        .form-control, .form-select { 
            background: #f1f5f9; 
            border: 1px solid #e2e8f0; 
            padding: 14px; 
            border-radius: 0 12px 12px 0; 
        }
        
        .btn-cyan {
            background: var(--cyan-grad); 
            border: none; 
            color: white; 
            padding: 16px;
            border-radius: 15px; 
            font-weight: 700; 
            box-shadow: 0 10px 20px -5px rgba(8, 145, 178, 0.4);
        }

        .label-style { font-weight: 700; font-size: 11px; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.8px; }

        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; }
            .visual-side { width: 100%; height: 180px; }
            .form-side { width: 100%; padding: 35px 20px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Qaybta Bidix -->
    <div class="visual-side">
        <div class="visual-content">
            <i class="fa-solid fa-notes-medical mb-3" style="font-size: 45px;"></i>
            <h2>Diiwaanka<br>Caafimaadka</h2>
            <p class="opacity-75">Keydinta xogta waxay suurtagalisaa in bukaanku helo daryeel hufan oo dhameystiran.</p>
        </div>
    </div>

    <!-- Qaybta Midig -->
    <div class="form-side">
        <div class="form-header mb-4">
            <h4>Diiwaangeli Taariikhda</h4>
            <p class="text-muted small">Fadlan buuxi faahfaahinta caafimaad ee bukaanka la doortay.</p>
        </div>
        
        <form id="historyForm">
            <div class="row">
                <!-- Doorashada Bukaanka -->
                <div class="col-md-12 mb-3">
                    <label class="label-style">Magaca Bukaanka</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-hospital-user"></i></span>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">Soo raryaa bukaannada...</option>
                        </select>
                    </div>
                </div>

                <!-- Xanuunka -->
                <div class="col-md-12 mb-3">
                    <label class="label-style">Xanuunka (Condition)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-disease"></i></span>
                        <input type="text" name="condition_name" class="form-control" placeholder="Tusaale: Walwal daran ama Niyadjab" required>
                    </div>
                </div>

                <!-- Faahfaahin -->
                <div class="col-md-12 mb-3">
                    <label class="label-style">Faahfaahin Kooban (Description)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-align-left"></i></span>
                        <input type="text" name="description" class="form-control" maxlength="50" placeholder="Sharaxaad kooban (ilaa 50 xaraf)" required>
                    </div>
                </div>

                <!-- Daaweyntii hore -->
                <div class="col-md-12 mb-4">
                    <label class="label-style">Daaweyntii Hore (Treatment History)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-pills"></i></span>
                        <textarea name="treatment_history" class="form-control" rows="2" maxlength="70" placeholder="Daawooyinkii ama la-talintii hore ee uu qaatay..." required></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-cyan w-100">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i> Keydi Xogta Caafimaadka
            </button>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Soo qaadashada bukaannada
function loadPatients() {
    $.get('?url=get_patients', function(res) {
        let options = '<option value="">Dooro Bukaanka</option>';
        if(res && res.length > 0) {
            res.forEach(u => {
                options += `<option value="${u.user_id}">${u.full_name}</option>`;
            });
        } else {
            options = '<option value="">Lama helin bukaan firfircoon</option>';
        }
        $('#patient_id').html(options);
    });
}

// Dirista Foomka
$('#historyForm').submit(function(e) {
    e.preventDefault();
    const btn = $(this).find('button');
    const originalText = btn.html();
    
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Waa lagu guda jiraa...');

    $.post('?url=create_history', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({
                title: 'Guul!',
                text: res.msg,
                icon: 'success',
                confirmButtonText: 'Haye',
                confirmButtonColor: '#0891b2'
            });
            $('#historyForm')[0].reset();
        } else {
            Swal.fire({
                title: 'Khalad!',
                text: res.msg,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#0891b2'
            });
        }
        btn.prop('disabled', false).html(originalText);
    });
});

$(document).ready(() => {
    loadPatients();
});
</script>

</body>
</html>