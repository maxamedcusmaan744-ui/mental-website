<?php
// ================= DATABASE CONNECTION =================
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

// Fetch medications
$meds = $conn->query("SELECT * FROM medications ORDER BY medication_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medication Inventory Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --navy: #0f172a; 
            --orange: #f97316; 
            --gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        .report-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 40px; margin: 40px auto; max-width: 950px; }
        .header-section { background: var(--gradient); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .table thead { background: var(--navy); color: white; }
        .low-stock { color: #dc2626; font-weight: bold; background: #fee2e2; padding: 2px 8px; border-radius: 4px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="container no-print text-center mt-4">
    <button class="btn btn-lg text-white shadow" style="background: var(--orange)" onclick="window.print()">
        Print Inventory Report
    </button>
</div>

<div class="report-card">
    <div class="header-section text-center">
        <h2 class="fw-bold">Medication Inventory</h2>
        <p class="text-white-50 mb-0">Stock Overview - <?=date('d M Y, h:i A')?></p>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Medication Name</th>
                <th>Dosage</th>
                <th>Stock Level</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($meds as $m): ?>
            <tr>
                <td class="fw-bold text-dark"><?= htmlspecialchars($m['medication_name']) ?></td>
                <td><?= htmlspecialchars($m['dosage']) ?></td>
                <td>
                    <?php if ($m['stock_quantity'] <= 5): ?>
                        <span class="low-stock">Low: <?= $m['stock_quantity'] ?></span>
                    <?php else: ?>
                        <span class="text-success fw-bold"><?= $m['stock_quantity'] ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($m['description']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>