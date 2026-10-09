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

// Fetch Staff Data
// Waxaan ku xirnay users iyo departments si aan u helno magaca iyo magaca qaybta
$stmt = $conn->prepare("
    SELECT s.*, u.full_name, u.email, d.department_name
    FROM staff s
    INNER JOIN users u ON s.user_id = u.user_id
    LEFT JOIN departments d ON s.department_id = d.department_id
    ORDER BY s.staff_id DESC
");
$stmt->execute();
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Personnel Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        
        @media print {
            .no-print { display: none !important; }
            .report-container { box-shadow: none !important; border: none !important; }
        }

        .report-container { background: white; max-width: 900px; margin: 40px auto; padding: 50px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .report-header { background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        
        .table thead { background: var(--navy) !important; color: white; }
        .btn-custom { background: var(--orange); color: white; border: none; }
        .btn-custom:hover { background: #ea580c; color: white; }
        .salary-text { font-weight: 700; color: var(--navy); }
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
        <h2 class="fw-bold">Staff Personnel Report</h2>
        <p class="mb-0 text-white-50">Generated on: <?=date('d M Y, h:i A')?></p>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Staff Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Hire Date</th>
                <th>Salary ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staff_list as $index => $staff): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <div class="fw-bold"><?=htmlspecialchars($staff['full_name'])?></div>
                    <small class="text-muted"><?=htmlspecialchars($staff['email'])?></small>
                </td>
                <td><?=htmlspecialchars($staff['department_name'] ?? 'N/A')?></td>
                <td><?=htmlspecialchars($staff['position'])?></td>
                <td><?= $staff['hire_date'] ? date('M d, Y', strtotime($staff['hire_date'])) : 'N/A' ?></td>
                <td class="salary-text"><?=number_format($staff['salary'], 2)?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-5 pt-4 border-top text-center text-muted">
        <small>&copy; <?=date('Y')?> Mental Health Support System. Personnel Records.</small>
    </div>
</div>

</body>
</html>