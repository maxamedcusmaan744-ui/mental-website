<?php
// ================= DATABASE CONNECTION =================
if (!class_exists('Connection')) {
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
                die("DB connection failed: " . $e->getMessage());
            }
        }
    }
}

$database = new Connection();
$conn = $database->db;

// Query to fetch Appointments with details
$stmt = $conn->prepare("
    SELECT a.*, u.full_name AS patient_name, u.email, s.slot_time
    FROM appointment a
    INNER JOIN users u ON a.patient_id = u.user_id
    INNER JOIN schedule_slots s ON a.slot_id = s.slot_id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($appointments);
$confirmed = count(array_filter($appointments, fn($a) => $a['status'] === 'Confirmed'));
$pending = count(array_filter($appointments, fn($a) => $a['status'] === 'Pending'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; --bg: #f8fafc; }
        body { background: var(--bg); font-family: "Segoe UI", sans-serif; }
        
        .header-section {
            background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%);
            color: white; padding: 40px; border-bottom: 5px solid var(--orange);
            border-radius: 0 0 20px 20px; margin-bottom: 30px;
        }

        .stat-card { 
            background: #fff; padding: 20px; border-radius: 12px; 
            border-left: 4px solid var(--orange); box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .table thead { background: var(--navy); color: white; }
        .status-badge { 
            padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; 
            font-weight: bold; background: var(--orange); color: white; 
        }
        
        .btn-print { background: var(--orange); color: white; border: none; padding: 10px 20px; border-radius: 8px; }
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="header-section text-center shadow no-print">
    <h1 class="fw-bold">Clinic Appointments Report</h1>
    <p class="text-white-50">Comprehensive schedule overview</p>
</div>

<main class="container py-4">
    <div class="d-flex justify-content-end mb-4 no-print">
        <button class="btn btn-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Print / Save PDF
        </button>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4"><div class="stat-card"><small class="text-muted">Total</small><h4><?=$total?></h4></div></div>
        <div class="col-md-4"><div class="stat-card"><small class="text-muted">Confirmed</small><h4><?=$confirmed?></h4></div></div>
        <div class="col-md-4"><div class="stat-card"><small class="text-muted">Pending</small><h4><?=$pending?></h4></div></div>
    </div>

    <div class="card p-3 shadow-sm border-0">
        <table class="table table-hover align-middle">
            <thead class="text-uppercase small">
                <tr>
                    <th>Patient</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Gender</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?=htmlspecialchars($a['patient_name'])?></div>
                        <small class="text-muted"><?=htmlspecialchars($a['patient_phone'])?></small>
                    </td>
                    <td><?=date('H:i', strtotime($a['slot_time']))?></td>
                    <td><?=htmlspecialchars($a['reason_for_visit'])?></td>
                    <td><span class="status-badge"><?=$a['status']?></span></td>
                    <td><?=$a['patient_gender']?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>