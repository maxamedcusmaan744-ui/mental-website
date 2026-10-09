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

// Fetch Rooms Data
$stmt = $conn->prepare("SELECT * FROM rooms ORDER BY room_id DESC");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Inventory Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        
        @media print {
            .no-print { display: none !important; }
            .report-container { box-shadow: none !important; border: none !important; margin: 0; }
        }

        .report-container { background: white; max-width: 900px; margin: 40px auto; padding: 50px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .report-header { background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        
        .table thead { background: var(--navy) !important; color: white; }
        .btn-custom { background: var(--orange); color: white; border: none; }
        .btn-custom:hover { background: #ea580c; color: white; }
        
        .status-badge { font-weight: 600; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; }
        .bg-available { background: #dcfce7; color: #166534; }
        .bg-occupied { background: #fee2e2; color: #991b1b; }
        .bg-maintenance { background: #fef3c7; color: #92400e; }
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
        <h2 class="fw-bold">Room Inventory Report</h2>
        <p class="mb-0 text-white-50">Generated on: <?=date('d M Y, h:i A')?></p>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Room #</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
            <tr>
                <td class="fw-bold"><?=htmlspecialchars($room['room_number'])?></td>
                <td><?=htmlspecialchars($room['room_type'])?></td>
                <td><?=htmlspecialchars($room['capacity'])?> Beds</td>
                <td>
                    <span class="status-badge bg-<?=strtolower($room['status'])?>">
                        <?=htmlspecialchars($room['status'])?>
                    </span>
                </td>
                <td class="text-muted"><?=date('M d, Y', strtotime($room['created_at']))?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5 pt-4 border-top text-center text-muted">
        <small>&copy; <?=date('Y')?> Mental Health Support System. Inventory Department.</small>
    </div>
</div>

</body>
</html>