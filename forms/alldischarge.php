<?php
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
$conn = (new Connection())->db;

// Fetch list of patients who have diagnoses
$patients_list = $conn->query("SELECT DISTINCT u.user_id, u.full_name FROM users u JOIN patient_admissions pa ON u.user_id = pa.patient_id JOIN diagnoses d ON pa.admission_id = d.patient_id")->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$diagnoses = [];

if (!empty($selected_user_id)) {
    // Query joins diagnoses with the new patient_discharges table
    $sql = "SELECT d.*, u_p.full_name AS patient_name, u_d.full_name AS doctor_name, 
                   doc.specialization, r.room_number, b.bed_number,
                   dis.discharge_date, dis.discharge_summary
            FROM diagnoses d
            JOIN patient_admissions pa ON d.patient_id = pa.admission_id
            JOIN users u_p ON pa.patient_id = u_p.user_id 
            JOIN doctors doc ON d.doctor_id = doc.doctor_id
            JOIN users u_d ON doc.user_id = u_d.user_id
            JOIN rooms r ON pa.room_id = r.room_id
            JOIN beds b ON pa.bed_id = b.bed_id
            LEFT JOIN patient_discharges dis ON pa.admission_id = dis.admission_id";
    
    if ($selected_user_id !== 'all') {
        $sql .= " WHERE u_p.user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['user_id' => (int)$selected_user_id]);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    $diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical & Discharge Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; }
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, var(--navy), #1e293b); color: white; padding: 40px; border-radius: 20px; }
        .main-card { background: white; border-radius: 20px; padding: 40px; margin-top: -30px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .info-pill { background: #fff7ed; color: var(--orange); font-weight: 800; border: 1px solid var(--orange); padding: 5px 12px; border-radius: 20px; }
        .discharge-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="gradient-bg text-center">
        <h1 class="fw-bold">Clinical & Discharge Records</h1>
        <form method="GET" id="reportForm" class="mt-4">
            <select name="user_id" class="form-select w-50 mx-auto" onchange="this.form.submit()">
                <option value="">-- Select Patient/View All --</option>
                <option value="all" <?= $selected_user_id === 'all' ? 'selected' : '' ?>>View All Patients</option>
                <?php foreach ($patients_list as $p): ?>
                    <option value="<?= $p['user_id'] ?>" <?= $selected_user_id == $p['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!empty($diagnoses)): ?>
    <div class="main-card mt-4">
        <?php foreach ($diagnoses as $row): ?>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h4 class="fw-bold text-navy"><?= htmlspecialchars($row['diagnosis_name']) ?></h4>
                        <span class="info-pill"><?= $row['diagnosis_date'] ?></span>
                    </div>
                    <p class="mt-2"><?= htmlspecialchars($row['diagnosis_details']) ?></p>
                    
                    <?php if (!empty($row['discharge_date'])): ?>
                        <div class="discharge-box">
                            <strong>Discharge Status:</strong> Discharged on <?= date('d M, Y', strtotime($row['discharge_date'])) ?>
                            <br><small><?= htmlspecialchars($row['discharge_summary']) ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mt-3 text-muted small">
                        <div class="col-4">Doctor: <?= htmlspecialchars($row['doctor_name']) ?></div>
                        <div class="col-4">Room/Bed: #<?= $row['room_number'] ?> / #<?= $row['bed_number'] ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-dark no-print" onclick="window.print()">Print Report</button>
    </div>
    <?php endif; ?>
</div>
</body>
</html>