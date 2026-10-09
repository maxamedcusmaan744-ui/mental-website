<?php
// ================= DATABASE CONNECTION =================
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
            die("DB Connection failed: " . $e->getMessage());
        }
    }
}

$conn = (new Connection())->db;

// Fetch doctors
$stmt = $conn->prepare("
    SELECT d.*, u.full_name, u.email 
    FROM doctors d 
    INNER JOIN users u ON d.user_id = u.user_id 
    ORDER BY d.doctor_id DESC
");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Report - <?=date('Y-m-d')?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --navy: #0f172a;
            --orange: #f97316;
            --gradient: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%);
        }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .report-container { box-shadow: none !important; border: none !important; }
        }

        .report-container { 
            background: white; max-width: 850px; margin: 40px auto; 
            padding: 50px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        }
        
        .report-header { 
            background: var(--gradient); 
            color: white; 
            padding: 30px; 
            border-radius: 12px; 
            margin-bottom: 30px;
        }

        .table thead { background: var(--navy) !important; color: white; }
        
        .btn-custom { 
            background: var(--orange); 
            color: white; 
            border: none; 
            transition: 0.3s;
        }
        .btn-custom:hover { background: #ea580c; color: white; }
        
        .badge-orange { background: var(--orange); color: white; }
    </style>
</head>
<body>

<div class="container mt-4 no-print text-center">
    <button class="btn btn-custom btn-lg px-5 shadow" onclick="window.print()">
        <i class="bi bi-printer"></i> Print / Download PDF
    </button>
</div>

<div class="report-container">
    <div class="report-header">
        <h2 class="fw-bold">Registered Doctors Report</h2>
        <p class="mb-0 text-white-50">System Export Date: <?=date('d M Y, h:i A')?></p>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Specialization</th>
                <th>Experience</th>
                <th>Fee ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($doctors as $index => $doc): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <div class="fw-bold text-dark"><?=htmlspecialchars($doc['full_name'])?></div>
                    <small class="text-muted"><?=htmlspecialchars($doc['email'])?></small>
                </td>
                <td><span class="badge badge-orange"><?=htmlspecialchars($doc['specialization'])?></span></td>
                <td><?=htmlspecialchars($doc['experience_years'])?> Years</td>
                <td class="fw-bold text-dark"><?=number_format($doc['consultation_fee'], 2)?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5 pt-4 border-top text-center text-muted">
        <small>&copy; <?=date('Y')?> Mental Health Support System. Confidential Report.</small>
    </div>
</div>

</body>
</html>