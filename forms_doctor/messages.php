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

    if ($route == 'get_users') {
        try {
            $users = $conn->query("SELECT user_id, full_name, user_type FROM users")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    if ($route == 'send_message') {
        try {
            $sql = "INSERT INTO messages (sender_id, receiver_id, message_text) 
                    VALUES (:s_id, :r_id, :msg)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':s_id' => $_POST['sender_id'],
                ':r_id' => $_POST['receiver_id'],
                ':msg'  => $_POST['message_text']
            ]);
            echo json_encode(["status" => true, "msg" => "Farriinta si guul leh ayaa loo diray!"]);
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
    <title>Farriimaha | MindCare Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #06b6d4; /* Cyan */
            --primary-grad: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        body { 
            /* Background image leh overlay khafiif ah */
            background: linear-gradient(rgba(15, 23, 42, 0.6), rgba(15, 23, 42, 0.6)), 
                        url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center; 
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 20px;
        }

        .chat-container {
            width: 100%;
            max-width: 900px; 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(6, 182, 212, 0.2);
            display: flex;
            overflow: hidden;
            min-height: 550px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .info-panel {
            width: 35%;
            background: var(--primary-grad);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel { width: 65%; padding: 40px; background: white; }

        .label-style { font-weight: 700; font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block; }
        
        .form-select, .form-control {
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1);
        }

        .btn-send {
            background: var(--primary-grad);
            border: none; 
            color: white; 
            padding: 15px;
            border-radius: 12px; 
            font-weight: 700; 
            width: 100%;
            margin-top: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(6, 182, 212, 0.3);
            color: white;
        }

        .icon-box {
            width: 60px; height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="chat-container">
    <div class="info-panel">
        <div class="icon-box"><i class="fa-solid fa-comments"></i></div>
        <h2 class="fw-800">Isgaarsiinta</h2>
        <p class="opacity-75">Ku xiriir bulshada MindCare adigoo u diraya farriimo ammaan ah oo qarsoodi ah.</p>
    </div>

    <div class="form-panel">
        <h4 class="fw-bold mb-4" style="color: #0f172a;">Qor Farriin Cusub</h4>
        
        <form id="messageForm">
            <div class="row">
                <!-- Sender -->
                <div class="col-md-6 mb-3">
                    <label class="label-style">Adiga (Sender)</label>
                    <select name="sender_id" id="sender_id" class="form-select" required>
                        <option value="">Soo raryaa...</option>
                    </select>
                </div>

                <!-- Receiver -->
                <div class="col-md-6 mb-3">
                    <label class="label-style">U dir (Receiver)</label>
                    <select name="receiver_id" id="receiver_id" class="form-select" required>
                        <option value="">Soo raryaa...</option>
                    </select>
                </div>

                <!-- Message Text -->
                <div class="col-md-12 mb-3">
                    <label class="label-style">Farriintaada</label>
                    <textarea name="message_text" class="form-control" rows="5" placeholder="Halkan ku qor farriintaada..." maxlength="400" required></textarea>
                    <div class="text-end mt-1">
                        <small class="text-muted"><span id="charCount">0</span>/400</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-send">
                <i class="fa-solid fa-paper-plane me-2"></i> Dir Farriinta
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function fetchUsers() {
    $.get('?url=get_users', function(data) {
        let options = '<option value="">Dooro qofka...</option>';
        data.forEach(user => {
            options += `<option value="${user.user_id}">${user.full_name} (${user.user_type})</option>`;
        });
        $('#sender_id, #receiver_id').html(options);
    });
}

$('textarea').on('input', function() {
    $('#charCount').text($(this).val().length);
});

$('#messageForm').submit(function(e) {
    e.preventDefault();
    
    if($('#sender_id').val() == $('#receiver_id').val()) {
        Swal.fire({
            title: 'Iska jir',
            text: 'Lama oggola inaad naftaada farriin u dirto!',
            icon: 'warning',
            confirmButtonColor: '#06b6d4'
        });
        return;
    }

    const btn = $(this).find('button');
    btn.prop('disabled', true).text('Dirista waa socotaa...');

    $.post('?url=send_message', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({
                title: 'La diray!',
                text: res.msg,
                icon: 'success',
                confirmButtonColor: '#06b6d4'
            });
            $('#messageForm')[0].reset();
            $('#charCount').text('0');
        } else {
            Swal.fire('Khalad', res.msg, 'error');
        }
        btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i> Dir Farriinta');
    });
});

$(document).ready(fetchUsers);
</script>

</body>
</html>