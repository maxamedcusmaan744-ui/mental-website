<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC
// =========================================================
class Connection {
    private $host = 'localhost';
    private $db_name = 'mental_health_support';
    private $user_name = 'root';
    private $password = '';
    public $db;

    public function __construct() {
        try {
            $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4", $this->user_name, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die(json_encode(["status" => false, "msg" => "Connection failed"]));
        }
    }
}

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    if ($_GET['action'] == 'submit_test') {
        $user_id = 1; // Tusaale: Waxaad ka heli kartaa $_SESSION['user_id']
        $questions = $_POST['q']; // Waa Array ka imaanaya Form-ka

        try {
            $sql = "INSERT INTO self_assessments (user_id, question, answer_value) VALUES (:uid, :q, :a)";
            $stm = $conn->prepare($sql);

            foreach ($questions as $q_text => $val) {
                $stm->execute([
                    ':uid' => $user_id,
                    ':q'   => $q_text,
                    ':a'   => $val
                ]);
            }
            echo json_encode(["status" => true, "msg" => "Assessment submitted successfully!"]);
        } catch (Exception $e) {
            echo json_encode(["status" => false, "msg" => $e->getMessage()]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Assessment | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root { --primary-cyan: #00bcd4; --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7); }
        body { background-color: #f0fbfc; font-family: 'Segoe UI', sans-serif; }
        
        .test-header {
            background: var(--gradient-cyan);
            color: white; padding: 60px 20px;
            text-align: center; border-radius: 0 0 50px 50px;
        }

        .question-card {
            background: white; border-radius: 20px;
            padding: 25px; margin-bottom: 20px;
            border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
        }

        .question-card:hover { border-left: 5px solid var(--primary-cyan); }

        .option-btn {
            border: 2px solid #e0e0e0; border-radius: 10px;
            padding: 10px; cursor: pointer; transition: 0.2s;
            text-align: center; font-weight: 500;
        }

        /* Mark selected radio */
        input[type="radio"]:checked + label {
            background-color: #00bcd4; color: white; border-color: #00bcd4;
        }

        input[type="radio"] { display: none; }

        .btn-submit {
            background: var(--gradient-cyan); color: white;
            padding: 15px 50px; border-radius: 50px;
            border: none; font-size: 1.1rem; font-weight: bold;
            box-shadow: 0 10px 20px rgba(0,188,212,0.3);
        }
    </style>
</head>
<body>

<div class="test-header mb-5">
    <h1><i class="fa-solid fa-clipboard-check me-2"></i> Self-Assessment Test</h1>
    <p>Take a moment to check in with yourself. Your responses are private.</p>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form id="testForm">
                <?php
                $test_questions = [
                    "In the last 2 weeks, how often have you felt nervous or anxious?",
                    "How often do you have trouble relaxing?",
                    "How often do you feel down, depressed, or hopeless?",
                    "How much interest or pleasure do you have in doing things lately?",
                    "How often do you feel overwhelmed by your daily tasks?"
                ];

                foreach ($test_questions as $index => $q) {
                ?>
                <div class="question-card">
                    <h5 class="mb-4 text-dark"><?php echo ($index + 1) . ". " . $q; ?></h5>
                    <div class="row g-2">
                        <?php 
                        $options = ["Never" => 1, "Rarely" => 2, "Sometimes" => 3, "Often" => 4, "Always" => 5];
                        foreach ($options as $label => $val) {
                            $id = "q{$index}_{$val}";
                        ?>
                        <div class="col">
                            <input type="radio" name="q[<?php echo $q; ?>]" id="<?php echo $id; ?>" value="<?php echo $val; ?>" required>
                            <label class="option-btn d-block" for="<?php echo $id; ?>"><?php echo $label; ?></label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>

                <div class="text-center mt-5">
                    <button type="submit" class="btn btn-submit">
                        <i class="fa-solid fa-paper-plane me-2"></i> Submit My Results
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$('#testForm').submit(function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Submitting...',
        text: 'Please wait while we record your responses.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.post('?action=submit_test', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({
                icon: 'success',
                title: 'Assessment Complete',
                text: 'Your responses have been saved. Remember, seeking help is a sign of strength.',
                confirmButtonColor: '#00838f'
            }).then(() => {
                window.location.reload(); // Reset the test
            });
        } else {
            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
        }
    });
});
</script>

</body>
</html>