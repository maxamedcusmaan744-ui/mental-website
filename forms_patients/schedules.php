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
            die(json_encode(["status" => false, "msg" => "Xiriirka database-ku waa guuldarraystay"]));
        }
    }
}

// Maareynta Codsiyada AJAX
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // Waddada: Soo qaadashada La-taliyayaasha
    if ($route == 'get_counsellors') {
        $stm = $conn->prepare("SELECT user_id, full_name FROM users WHERE user_type='Counsellor' AND status='Active'");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Waddada: Samaynta Jadwalka
    if ($route == 'create_schedule') {
        try {
            $sql = "INSERT INTO schedules (counsellor_id, available_date, start_time, end_time)
                    VALUES (:c_id, :a_date, :s_time, :e_time)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':c_id'   => $_POST['counsellor_id'],
                ':a_date' => $_POST['available_date'],
                ':s_time' => $_POST['start_time'],
                ':e_time' => $_POST['end_time']
            ]);
            echo json_encode(["status" => true, "msg" => "Jadwalka si guul leh ayaa loo kaydiyay!"]);
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
    <title>Jadwalka La-taliyaha | MindCare Pro</title>
    
    <!-- Agabka Dibadda -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --cyan-light: #22d3ee;
            --cyan-main: #06b6d4;
            --cyan-dark: #0891b2;
            --cyan-grad: linear-gradient(135deg, var(--cyan-light) 0%, var(--cyan-main) 100%);
            --soft-bg: #f8fafc;
            --text-dark: #0f172a;
        }

        body { 
            background: #f1f5f9;
            background-image: linear-gradient(rgba(8, 145, 178, 0.1), rgba(8, 145, 178, 0.1)), 
                              url('https://images.unsplash.com/photo-1629909613654-28e377c37b09?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center; 
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .main-wrapper {
            width: 100%;
            max-width: 1050px; 
            min-height: 600px; 
            background: white;
            border-radius: 30px;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.2);
            display: flex;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .visual-side {
            width: 45%;
            background: linear-gradient(rgba(6, 182, 212, 0.8), rgba(8, 145, 178, 0.9)),
                        url('https://images.unsplash.com/photo-1506784919141-177b7ec16c6a?auto=format&fit=crop&w=800&q=80');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 40px;
        }

        .visual-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
        }

        .visual-content { position: relative; z-index: 2; color: white; }
        .visual-content h2 { font-weight: 800; font-size: 32px; letter-spacing: -1px; }

        .form-side { width: 55%; padding: 60px; display: flex; flex-direction: column; justify-content: center; }
        .form-header h4 { color: var(--cyan-dark); font-weight: 800; font-size: 28px; letter-spacing: -0.5px; }

        .input-group-text { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            color: var(--cyan-main); 
            border-radius: 12px 0 0 12px; 
        }
        .form-control, .form-select { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            padding: 14px; 
            font-size: 14px; 
            border-radius: 0 12px 12px 0; 
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--cyan-light);
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.15);
            background-color: #fff;
        }

        .btn-cyan {
            background: var(--cyan-grad); 
            border: none; 
            color: white; 
            padding: 16px;
            border-radius: 15px; 
            font-weight: 700; 
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(6, 182, 212, 0.3);
        }

        .btn-cyan:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 20px -5px rgba(6, 182, 212, 0.4);
            filter: brightness(1.1);
        }

        .label-style { 
            font-weight: 700; 
            font-size: 11px; 
            color: var(--cyan-dark); 
            margin-bottom: 6px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }

        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; width: 100%; height: auto; }
            .visual-side { width: 100%; height: 180px; padding: 20px; }
            .form-side { width: 100%; padding: 40px 20px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="visual-side">
        <div class="visual-content">
            <i class="fa-solid fa-calendar-check mb-3" style="font-size: 40px;"></i>
            <h2>Jadwalka La-taliyaha</h2>
            <p>Deji waqtiyada ay firaaqada u yihiin la-taliyayaasha si ay bukaanadu u qabsadaan ballan.</p>
        </div>
    </div>

    <div class="form-side">
        <div class="form-header mb-4">
            <h4>Maaree Waqtiyada</h4>
            <span class="text-muted">Samee waqti cusub oo uu la-taliyuhu heli karo.</span>
        </div>
        
        <form id="scheduleForm">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="label-style">Dooro La-taliyaha</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user-doctor"></i></span>
                        <select name="counsellor_id" id="counsellor_id" class="form-select" required>
                            <option value="">Soo raryaa la-taliyayaasha...</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="label-style">Taariikhda la heli karo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-day"></i></span>
                        <input type="date" name="available_date" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="label-style">Waqtiga Billowga</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-clock"></i></span>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="label-style">Waqtiga Dhamaadka</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-cyan w-100 mt-3">
                <i class="fa-solid fa-calendar-plus me-2"></i> Kaydi Waqtiga Jadwalka
            </button>
        </form>
    </div>
</div>

<!-- JS Dependencies -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Soo rari La-taliyayaasha markii bogg u furmo
function loadCounsellors() {
    $.get('?url=get_counsellors', function(res) {
        let options = '<option value="">Dooro La-taliyaha</option>';
        if(res && res.length > 0) {
            res.forEach(u => {
                options += `<option value="${u.user_id}">${u.full_name}</option>`;
            });
        } else {
            options = '<option value="">Lama helin la-taliyayaal firfircoon</option>';
        }
        $('#counsellor_id').html(options);
    });
}

// Maareynta dirista foomka
$('#scheduleForm').submit(function(e) {
    e.preventDefault();
    const btn = $(this).find('button');
    const originalText = btn.html();
    
    // Bilaabista xaaladda rarista (Loading)
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Waa la kaydinayaa...');

    $.post('?url=create_schedule', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({
                title: 'Waa la kaydiyay!',
                text: res.msg,
                icon: 'success',
                confirmButtonText: 'Haye',
                confirmButtonColor: '#06b6d4'
            });
            $('#scheduleForm')[0].reset();
        } else {
            Swal.fire({
                title: 'Khalad',
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
    loadCounsellors();
});
</script>

</body>
</html>