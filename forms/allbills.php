<?php
// ================= DATABASE CONNECTION =================
if (!class_exists('Connection')) {
    class Connection {
        private $host = 'localhost'; private $db_name = 'mental_health_support';
        private $user_name = 'root'; private $password = '';
        public $db;
        public function __construct() {
            try {
                $this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4", $this->user_name, $this->password);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) { die("Connection failed: " . $e->getMessage()); }
        }
    }
}
$conn = (new Connection())->db;

// Get patients list for dropdown
$patients_list = $conn->query("SELECT DISTINCT u.user_id, u.full_name FROM users u JOIN patient_admissions pa ON u.user_id = pa.patient_id JOIN bills b ON pa.admission_id = b.admission_id")->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = $_GET['user_id'] ?? '';
$bills = [];

// Base Query
$query = "SELECT b.*, u.full_name AS patient_name, pa.admission_reason 
          FROM bills b
          JOIN patient_admissions pa ON b.admission_id = pa.admission_id
          JOIN users u ON pa.patient_id = u.user_id";

if ($selected_user_id === 'all') {
    $stmt = $conn->prepare($query . " ORDER BY b.bill_date DESC");
    $stmt->execute();
} elseif (!empty($selected_user_id)) {
    $stmt = $conn->prepare($query . " WHERE u.user_id = :user_id ORDER BY b.bill_date DESC");
    $stmt->execute(['user_id' => $selected_user_id]);
}

if (isset($stmt)) $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Records Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; }
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, var(--navy), #1e293b); color: white; padding: 50px; border-radius: 25px; }
        .main-card { background: white; border-radius: 25px; padding: 40px; margin-top: -40px; box-shadow: 0 20px 25px rgba(0,0,0,0.1); }
        .info-pill { background: #fff7ed; color: var(--orange); font-weight: 800; border: 2px solid var(--orange); padding: 5px 15px; border-radius: 50px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="gradient-bg text-center mb-5">
        <h1 class="fw-bold">Billing Records Dashboard</h1>
        <form method="GET" id="reportForm" class="mt-4">
            <select name="user_id" class="form-select w-75 mx-auto" onchange="this.form.submit()">
                <option value="">-- Select Patient to View Bills --</option>
                <option value="all" <?= $selected_user_id === 'all' ? 'selected' : '' ?>>View All Bills</option>
                <?php foreach ($patients_list as $p): ?>
                    <option value="<?= $p['user_id'] ?>" <?= $selected_user_id == $p['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!empty($bills)): ?>
    <div class="main-card">
        <h2 class="fw-bold mb-4">Financial Overview</h2>
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Month/Year</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $row): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                    <td><?= $row['billing_month'] ?> <?= $row['billing_year'] ?></td>
                    <td class="text-success fw-bold">$<?= number_format($row['total_amount'], 2) ?></td>
                    <td>
                        <span class="status-badge <?= $row['status'] == 'Paid' ? 'bg-success text-white' : 'bg-warning' ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td><?= $row['bill_date'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-dark btn-lg mt-3" onclick="window.print()">Print Report</button>
    </div>
    <?php endif; ?>
</div>

</body>
</html>