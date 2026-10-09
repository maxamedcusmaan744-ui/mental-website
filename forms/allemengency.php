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

$status = $_GET['status'] ?? '';
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "er.request_status = :status";
    $params[':status'] = $status;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $conn->prepare("
    SELECT er.*, u.full_name, u.email 
    FROM emergency_requests er
    INNER JOIN users u ON er.user_id = u.user_id
    $whereSql
    ORDER BY er.requested_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRequests = count($requests);
$pendingCount = count(array_filter($requests, fn($r) => $r['request_status'] === 'Pending'));
$onTheWayCount = count(array_filter($requests, fn($r) => $r['request_status'] === 'On The Way'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Emergency Requests Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; 
            font-weight: bold; background: var(--orange); color: white; 
        }
        
        .btn-print { background: var(--orange); color: white; border: none; padding: 10px 20px; border-radius: 8px; }
        .btn-print:hover { background: #ea580c; color: white; }

        /* Media Print Settings */
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none !important; border: 1px solid #ccc !important; }
            .header-section { border-bottom: 2px solid #000; background: #000 !important; }
        }
    </style>
</head>
<body>

<div class="header-section text-center shadow no-print">
    <h1 class="fw-bold">Emergency Operations Report</h1>
    <p class="text-white-50">Real-time status tracking</p>
</div>

<main class="container py-4">
    <div class="d-flex justify-content-end mb-4 no-print">
        <button class="btn btn-print" onclick="window.print()">
            <i class="bi bi-file-earmark-pdf"></i> Download/Print PDF
        </button>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4"><div class="stat-card"><small class="text-muted">Total</small><h4><?=$totalRequests?></h4></div></div>
        <div class="col-md-4"><div class="stat-card"><small class="text-muted">Pending</small><h4><?=$pendingCount?></h4></div></div>
        <div class="col-md-4"><div class="stat-card"><small class="text-muted">On The Way</small><h4><?=$onTheWayCount?></h4></div></div>
    </div>

    <div class="card p-3 shadow-sm border-0">
        <table class="table table-hover align-middle">
            <thead class="text-uppercase small">
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $row): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?=htmlspecialchars($row['full_name'])?></div>
                        <small class="text-muted"><?=htmlspecialchars($row['email'])?></small>
                    </td>
                    <td><span class="badge bg-secondary"><?=$row['service_type']?></span></td>
                    <td><?=htmlspecialchars($row['location'])?></td>
                    <td><span class="status-badge"><?=$row['request_status']?></span></td>
                    <td><?=date('M d, H:i', strtotime($row['requested_at']))?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>