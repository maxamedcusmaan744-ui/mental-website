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
        } catch (PDOException $e) { die("DB Connection failed: " . $e->getMessage()); }
    }
}
$conn = (new Connection())->db;
$stmt = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --navy: #0f172a; 
            --orange: #f97316; 
            --gradient: linear-gradient(135deg, #0f172a 0%, #334155 100%);
        }
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; }
        
        .report-card { background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 40px; margin: 40px auto; max-width: 900px; }
        
        /* Header with Gradient */
        .header-section { background: var(--gradient); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        
        /* Table Styling */
        .table thead { background: var(--navy); color: white; }
        .dept-name { color: var(--navy); font-weight: 700; font-size: 1.1rem; }
        .highlight-border { border-left: 5px solid var(--orange); }
        
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="container no-print text-center mt-4">
    <button class="btn btn-lg text-white px-5" style="background: var(--orange)" onclick="window.print()">
        Print Department Report
    </button>
</div>

<div class="report-card">
    <div class="header-section text-center">
        <h2 class="fw-bold">Active Departments</h2>
        <p class="text-white-50 mb-0">System Directory - <?=date('F j, Y')?></p>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Department Name</th>
                <th>Description</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($departments as $dept): ?>
            <tr class="highlight-border">
                <td>
                    <div class="dept-name"><?= htmlspecialchars($dept['department_name']) ?></div>
                </td>
                <td class="text-muted"><?= htmlspecialchars($dept['description']) ?></td>
                <td><?= date('M d, Y', strtotime($dept['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>