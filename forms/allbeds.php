<?php
// Database Logic
class Connection {
    private $host = 'localhost'; private $db_name = 'mental_health_support';
    private $user_name = 'root'; private $password = '';
    public $db;
    public function __construct() {
        try {
            $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4", $this->user_name, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) { die("DB Connection failed: " . $e->getMessage()); }
    }
}
$conn = (new Connection())->db;
$beds = $conn->query("SELECT * FROM beds ORDER BY status ASC, bed_number ASC")->fetchAll(PDO::FETCH_ASSOC);

// Xisaabinta tirada (Summary logic)
$stats = array_count_values(array_column($beds, 'status'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bed Management Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --navy: #0f172a; 
            --orange: #f97316; 
            --gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        
        .report-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 40px; margin: 40px auto; max-width: 900px; }
        
        /* Header Gradient */
        .header-section { background: var(--gradient); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        
        /* Stats Cards */
        .stat-card { background: #f8fafc; border-left: 4px solid var(--orange); border-radius: 8px; padding: 15px; }
        
        /* Table Styles */
        .table thead { background: var(--navy); color: white; }
        .status-pill { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="container no-print text-center mt-4">
    <button class="btn btn-lg text-white" style="background: var(--orange)" onclick="window.print()">Print / Export PDF</button>
</div>

<div class="report-card">
    <div class="header-section text-center">
        <h2 class="fw-bold">Facility Bed Inventory</h2>
        <p class="text-white-50">Warbixinta Maareynta Sariiraha - <?=date('d M Y')?></p>
    </div>

    <div class="row mb-4">
        <?php foreach(['Available', 'Occupied', 'Reserved'] as $s): ?>
        <div class="col-4">
            <div class="stat-card">
                <small class="text-muted"><?= $s ?></small>
                <h4 class="fw-bold"><?= $stats[$s] ?? 0 ?></h4>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Bed #</th>
                <th>Category</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($beds as $bed): ?>
            <tr>
                <td class="fw-bold">Bed <?= htmlspecialchars($bed['bed_number']) ?></td>
                <td><?= htmlspecialchars($bed['bed_type']) ?></td>
                <td>
                    <span class="badge" style="background: var(--orange)">
                        <?= htmlspecialchars($bed['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>