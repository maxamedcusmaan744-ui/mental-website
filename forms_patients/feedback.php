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

    // 1. Soo qaadashada Dadka (Users)
    if ($route == 'get_users') {
        try {
            $users = $conn->query("SELECT user_id, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // 2. Kaydinta Feedback-ga
    if ($route == 'send_feedback') {
        try {
            $sql = "INSERT INTO feedback (user_id, message, rating) VALUES (:u_id, :msg, :rate)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':u_id' => $_POST['user_id'],
                ':msg'  => $_POST['message'],
                ':rate' => $_POST['rating']
            ]);
            echo json_encode(["status" => true, "msg" => "Mahadsanid! Ra'yigaaga si guul leh ayaa loo helay."]);
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
    <title>Feedback | MindCare Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --cyan-main: #0891b2;
            --cyan-grad: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        body { 
            background: #f0fdfa;
            background-image: linear-gradient(rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.1)), 
                              url('https://images.unsplash.com/photo-1551836022-d5d88e9218df?q=80&w=2070&auto=format&fit=crop');
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
            max-width: 900px; 
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            box-shadow: 0 40px 100px -20px rgba(8, 145, 178, 0.25);
            display: flex;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .visual-side {
            width: 35%;
            background: var(--cyan-grad);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-side { width: 65%; padding: 45px; background: white; }

        .form-control, .form-select { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 12px; 
            font-size: 14px; 
        }

        .form-control:focus, .form-select:focus { 
            border-color: #06b6d4; 
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1); 
        }
        
        .btn-cyan {
            background: var(--cyan-grad); 
            border: none; color: white; padding: 15px;
            border-radius: 12px; font-weight: 700;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-cyan:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(6, 182, 212, 0.3); color: white; }

        .label-style { font-weight: 700; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block; }
        
        .rating-stars {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .rating-stars input { display: none; }
        .rating-stars label {
            font-size: 25px;
            color: #e2e8f0;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label { color: #f59e0b; } /* Gold color for stars */
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="visual-side">
        <i class="fa-solid fa-heart-pulse mb-4" style="font-size: 50px;"></i>
        <h2 class="fw-800">Nala Wadaag Ra'yigaaga</h2>
        <p class="opacity-75">Caawintaadu waxay noo horseedaysaa inaan bixinno adeeg ka sii wanaagsan.</p>
    </div>

    <div class="form-side">
        <h4 class="fw-bold text-dark mb-4">Feedback Form</h4>
        
        <form id="feedbackForm">
            <div class="mb-3">
                <label class="label-style">Magacaaga</label>
                <select name="user_id" id="user_id" class="form-select" required>
                    <option value="">Soo raryaa...</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="label-style">Qiimaynta (Stars)</label>
                <div class="rating-stars flex-row-reverse justify-content-end">
                    <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" class="fas fa-star"></label>
                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4" class="fas fa-star"></label>
                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3" class="fas fa-star"></label>
                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2" class="fas fa-star"></label>
                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1" class="fas fa-star"></label>
                </div>
            </div>

            <div class="mb-4">
                <label class="label-style">Farriintaada / Message</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Maxaad naga dhihi lahayd?" required></textarea>
            </div>

            <button type="submit" class="btn btn-cyan">
                <i class="fa-solid fa-paper-plane me-2"></i> Dir Ra'yiga
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // 1. Soo rari Users
    $.get('?url=get_users', function(res) {
        let options = '<option value="">Dooro magacaaga</option>';
        res.forEach(u => options += `<option value="${u.user_id}">${u.full_name}</option>`);
        $('#user_id').html(options);
    });

    // 2. Dirista Feedback
    $('#feedbackForm').submit(function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Waa la dirayaa...');

        $.post('?url=send_feedback', $(this).serialize(), function(res) {
            if(res.status) {
                Swal.fire({ title: 'Mahadsanid!', text: res.msg, icon: 'success', confirmButtonColor: '#0891b2' });
                $('#feedbackForm')[0].reset();
            } else {
                Swal.fire('Khalad', res.msg, 'error');
            }
            btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i> Dir Ra\'yiga');
        });
    });
});
</script>

</body>
</html>