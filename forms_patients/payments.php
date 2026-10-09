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

// Maareynta Codsiyada AJAX
if (isset($_GET['url'])) {
    $db = new Connection();
    $conn = $db->db;
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // 1. Soo qaadashada Ballamaha aan lacagta laga bixin
    if ($route == 'get_appointments') {
        $stm = $conn->prepare("SELECT a.appointment_id, u.full_name FROM appointments a 
                               JOIN users u ON a.patient_id = u.user_id");
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Samaynta Lacag-bixinta
    if ($route == 'process_payment') {
        try {
            // Soo qaado user_id-ka ballanta iska leh (Simplified)
            $stmt_user = $conn->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
            $stmt_user->execute([$_POST['appointment_id']]);
            $user_id = $stmt_user->fetchColumn();

            $sql = "INSERT INTO payments (appointment_id, user_id, amount, method, transaction_id, status)
                    VALUES (:a_id, :u_id, :amount, :method, :t_id, :status)";
            $stm = $conn->prepare($sql);
            $stm->execute([
                ':a_id'    => $_POST['appointment_id'],
                ':u_id'    => $user_id,
                ':amount'  => $_POST['amount'],
                ':method'  => $_POST['method'],
                ':t_id'    => $_POST['transaction_id'] ?? null,
                ':status'  => 'Completed' // Waxaad ka dhigi kartaa Pending haddii loo baahdo
            ]);
            echo json_encode(["status" => true, "msg" => "Lacag-bixinta si guul leh ayaa loo diwaangeliyay!"]);
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
    <title>Bixinta Lacagta | MindCare Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --cyan-main: #0891b2;
            --cyan-grad: linear-gradient(135deg, #22d3ee 0%, #0891b2 100%);
        }

        body { 
            background: linear-gradient(rgba(8, 145, 178, 0.1), rgba(8, 145, 178, 0.1)), 
                        url('https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=2022&auto=format&fit=crop');
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
            max-width: 950px; 
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            display: flex;
            overflow: hidden;
        }

        .visual-side {
            width: 40%;
            background: var(--cyan-grad);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-side { width: 60%; padding: 50px; }

        .input-group-text { background: #f8fafc; color: var(--cyan-main); border-radius: 12px 0 0 12px; }
        .form-control, .form-select { background: #f8fafc; border-radius: 0 12px 12px 0; padding: 12px; }
        
        .btn-cyan {
            background: var(--cyan-grad); 
            border: none; color: white; padding: 14px;
            border-radius: 12px; font-weight: 700;
            box-shadow: 0 10px 15px rgba(8, 145, 178, 0.2);
        }

        .label-style { font-weight: 700; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="visual-side text-center">
        <i class="fa-solid fa-file-invoice-dollar mb-4" style="font-size: 60px;"></i>
        <h3>Nidaamka Lacag-bixinta</h3>
        <p>Fadlan hubi macluumaadka lacag-bixinta ka hor inta aadan gujin badanka xaqiijinta.</p>
    </div>

    <div class="form-side">
        <div class="mb-4">
            <h4 class="fw-bold">Bixi Lacagta Ballanta</h4>
            <span class="text-muted">Dooro ballanta iyo habka lacag-bixinta.</span>
        </div>
        
        <form id="paymentForm">
            <div class="row">
                <!-- Ballanta -->
                <div class="col-12 mb-3">
                    <label class="label-style">Xulo Ballanta (Appointment)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-check"></i></span>
                        <select name="appointment_id" id="appointment_id" class="form-select" required>
                            <option value="">Soo raryaa ballamaha...</option>
                        </select>
                    </div>
                </div>

                <!-- Qadarka Lacagta -->
                <div class="col-md-6 mb-3">
                    <label class="label-style">Qadarka Lacagta ($)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-dollar-sign"></i></span>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <!-- Habka Lacagta -->
                <div class="col-md-6 mb-3">
                    <label class="label-style">Habka Lacag-bixinta</label>
                    <select name="method" class="form-select" style="border-radius: 12px;">
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Cash">Cash</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Credit Card">Credit Card</option>
                    </select>
                </div>

                <!-- Transaction ID -->
                <div class="col-12 mb-4">
                    <label class="label-style">Tixraaca (Transaction ID)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Tusaale: TXN12345678">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-cyan w-100">
                <i class="fa-solid fa-paper-plane me-2"></i> Xaqiiji Lacag-bixinta
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Soo rari Ballamaha
function loadAppointments() {
    $.get('?url=get_appointments', function(res) {
        let options = '<option value="">Dooro Ballanta</option>';
        res.forEach(a => {
            options += `<option value="${a.appointment_id}">Ballanta #${a.appointment_id} - ${a.full_name}</option>`;
        });
        $('#appointment_id').html(options);
    });
}

// Dirista Lacagta
$('#paymentForm').submit(function(e) {
    e.preventDefault();
    const btn = $(this).find('button');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Waa la xaqiijinayaa...');

    $.post('?url=process_payment', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire('Guul!', res.msg, 'success');
            $('#paymentForm')[0].reset();
        } else {
            Swal.fire('Khalad', res.msg, 'error');
        }
        btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i> Xaqiiji Lacag-bixinta');
    });
});

$(document).ready(() => { loadAppointments(); });
</script>

</body>
</html>