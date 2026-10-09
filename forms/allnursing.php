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

// Fetch Nurses Data
$stmt = $conn->prepare("
    SELECT n.*, u.full_name, u.email, d.department_name
    FROM nurses n
    INNER JOIN users u ON n.user_id = u.user_id
    LEFT JOIN departments d ON n.department_id = d.department_id
    ORDER BY n.nurse_id DESC
");
$stmt->execute();
$nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nurses Personnel Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        
        @media print {
            .no-print { display: none !important; }
            .report-container { box-shadow: none !important; border: none !important; margin: 0; }
        }

        .report-container { background: white; max-width: 950px; margin: 40px auto; padding: 50px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .report-header { background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        
        .table thead { background: var(--navy) !important; color: white; }
        .btn-custom { background: var(--orange); color: white; border: none; }
        .btn-custom:hover { background: #ea580c; color: white; }
        .shift-badge { background: #e2e8f0; color: var(--navy); font-weight: 600; padding: 5px 10px; border-radius: 6px; }
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
        <h2 class="fw-bold">Nurses Personnel Report</h2>
        <p class="mb-0 text-white-50">Generated on: <?=date('d M Y, h:i A')?></p>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Nurse Name</th>
                <th>Department</th>
                <th>Qualification</th>
                <th>Shift</th>
                <th>Salary ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($nurses as $index => $nurse): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <div class="fw-bold"><?=htmlspecialchars($nurse['full_name'])?></div>
                    <small class="text-muted"><?=htmlspecialchars($nurse['email'])?></small>
                </td>
                <td><?=htmlspecialchars($nurse['department_name'] ?? 'N/A')?></td>
                <td><?=htmlspecialchars($nurse['qualification'])?></td>
                <td><span class="shift-badge"><?=htmlspecialchars($nurse['shift'])?></span></td>
                <td class="fw-bold"><?=number_format($nurse['salary'], 2)?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5 pt-4 border-top text-center text-muted">
        <small>&copy; <?=date('Y')?> Mental Health Support System. Nursing Department Records.</small>
    </div>
</div>

</body>
</html>