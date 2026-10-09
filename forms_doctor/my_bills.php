<?php
session_start();

// Halkan waxaan u qaadanaynaa in qofka soo galay ID-giisa lagu kaydiyey Session-ka.
// $_SESSION['user_id'] = 1; // Furo line-kan haddii aad rabto inaad test-garayso

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

// API ROUTE LOGIC
if (isset($_GET['url']) && $_GET['url'] == 'get_balance') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => false, "msg" => "Fadlan soo gal nidaamka (Login)."]);
        exit;
    }

    $db = new Connection();
    $conn = $db->db;
    $patient_id = $_SESSION['user_id'];

    // Waxaan soo xulaynaa oo kaliya biilasha aan la bixin (Pending ama Partially Paid)
    $sql = "SELECT b.total_amount, b.billing_month, b.billing_year 
            FROM bills b
            JOIN patient_admissions pa ON b.admission_id = pa.admission_id
            JOIN users u ON pa.patient_id = u.user_id
            WHERE u.user_id = :patient_id AND b.status IN ('Pending', 'Partially Paid')";
    
    $stm = $conn->prepare($sql);
    $stm->execute([':patient_id' => $patient_id]);
    $bills = $stm->fetchAll(PDO::FETCH_ASSOC);

    $total_due = 0;
    $months_array = [];
    
    foreach ($bills as $b) {
        $total_due += (float)$b['total_amount'];
        $months_array[] = $b['billing_month'] . ' ' . $b['billing_year'];
    }

    // Isku darka bilaha haddii ay dhawr bilood yihiin (oo soo noqnoqoshada laga saaray)
    $months_str = implode(', ', array_unique($months_array));

    echo json_encode([
        "status" => true,
        "is_owing" => $total_due > 0,
        "total_amount" => $total_due,
        "months" => $months_str
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bixinta Biilasha (Patient Balance)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Modern Glassmorphism & Cyan/Blue Gradient */
        body {
            background: linear-gradient(135deg, #00bcd4 0%, #6366f1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 30px;
            padding: 60px 40px;
            text-align: center;
            color: white;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            transition: all 0.4s ease;
        }

        .title-text {
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .amount-display {
            font-size: 5.5rem;
            font-weight: 800;
            text-shadow: 3px 6px 15px rgba(0, 0, 0, 0.25);
            margin: 20px 0;
            line-height: 1;
        }

        .month-display {
            font-size: 1.8rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.25);
            padding: 12px 30px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .no-debt-icon {
            font-size: 6.5rem;
            color: #00ffaa;
            text-shadow: 0 0 30px rgba(0, 255, 170, 0.6);
            margin-bottom: 20px;
        }

        .no-debt-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 10px;
        }

        /* Loading Spinner */
        .spinner-border {
            width: 4rem;
            height: 4rem;
            color: white;
        }
    </style>
</head>
<body>

    <div class="glass-card" id="balanceCard">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="mt-4 text-white">Xogtaada ayaa la soo baarayaa...</h4>
    </div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    
    // API Call si loo soo ogaado lacagta lagu leeyahay bukaanka
    $.get('?url=get_balance', function(res) {
        
        let cardHTML = '';

        if (res.status === false) {
            // Haddii uusan soo galin nidaamka ama Error jiro
            cardHTML = `
                <i class="fa-solid fa-triangle-exclamation text-warning" style="font-size: 5rem;"></i>
                <h3 class="mt-4">${res.msg}</h3>
            `;
        } 
        else if (res.is_owing) {
            // Haddii Lacag lagu leeyahay (Waa la dalacay)
            cardHTML = `
                <div class="title-text">Lacagta Kugu Taagan</div>
                <div class="amount-display">$${parseFloat(res.total_amount).toFixed(2)}</div>
                
                <div class="mt-4">
                    <span class="d-block mb-2" style="font-size: 1.2rem; opacity: 0.8;">Bisha/Bilaha lagu leeyahay:</span>
                    <div class="month-display">
                        <i class="fa-regular fa-calendar-alt me-2"></i> ${res.months}
                    </div>
                </div>
            `;
        } 
        else {
            // Haddii uusan wax lacag ah lagu lahayn (Wax malagu lahan)
            cardHTML = `
                <i class="fa-solid fa-circle-check no-debt-icon"></i>
                <div class="no-debt-text">Wax Lacag Ah Laguma Laha!</div>
                <p class="mt-3" style="font-size: 1.2rem; opacity: 0.8;">Xisaabtaadu waa nadiif. Aad baad u mahadsan tahay.</p>
            `;
        }

        // Geli xogta Card-ka dhexdiisa
        $('#balanceCard').hide().html(cardHTML).fadeIn(600);
        
    }).fail(function() {
        $('#balanceCard').html('<h3 class="text-danger">Cilad ayaa dhacday, isku day mar kale.</h3>');
    });

});
</script>

</body>
</html>