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

// Get patients list
$patients_list = $conn->query("SELECT DISTINCT u.user_id, u.full_name FROM users u JOIN patient_admissions pa ON u.user_id = pa.patient_id JOIN diagnoses d ON pa.admission_id = d.patient_id")->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$diagnoses = [];

// Base Query
$query = "SELECT d.*, u_patient.full_name AS patient_name, u_doctor.full_name AS doctor_name, doc.specialization, r.room_number, b.bed_number
          FROM diagnoses d
          JOIN patient_admissions pa ON d.patient_id = pa.admission_id
          JOIN users u_patient ON pa.patient_id = u_patient.user_id 
          JOIN doctors doc ON d.doctor_id = doc.doctor_id
          JOIN users u_doctor ON doc.user_id = u_doctor.user_id
          JOIN rooms r ON pa.room_id = r.room_id
          JOIN beds b ON pa.bed_id = b.bed_id";

// Handle "All" vs "Individual"
if ($selected_user_id === 'all') {
    $query .= " ORDER BY d.diagnosis_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
} elseif ((int)$selected_user_id > 0) {
    $query .= " WHERE u_patient.user_id = :user_id ORDER BY d.diagnosis_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute(['user_id' => (int)$selected_user_id]);
} else {
    $stmt = null;
}

if ($stmt) $diagnoses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Records Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #0f172a; --orange: #f97316; }
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, var(--navy), #1e293b); color: white; padding: 50px; border-radius: 25px; }
        .main-card { background: white; border-radius: 25px; padding: 40px; margin-top: -40px; box-shadow: 0 20px 25px rgba(0,0,0,0.1); }
        .info-pill { background: #fff7ed; color: var(--orange); font-weight: 800; border: 2px solid var(--orange); padding: 5px 15px; border-radius: 50px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="gradient-bg text-center mb-5">
        <h1 class="fw-bold">Clinical Records Dashboard</h1>
        <form method="GET" id="reportForm" class="mt-4">
            <select name="user_id" class="form-select w-75 mx-auto" onchange="document.getElementById('reportForm').submit()">
                <option value="">-- Select View --</option>
                <option value="all" <?= $selected_user_id === 'all' ? 'selected' : '' ?>>View All Patients</option>
                <optgroup label="Individual Patients">
                    <?php foreach ($patients_list as $p): ?>
                        <option value="<?= $p['user_id'] ?>" <?= $selected_user_id == $p['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['full_name']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </form>
    </div>

    <?php if (!empty($diagnoses)): ?>
    <div class="main-card">
        <h2 class="fw-bold mb-4"><?= $selected_user_id === 'all' ? 'All Patient History' : 'History for: ' . htmlspecialchars($diagnoses[0]['patient_name']) ?></h2>
        
        <?php foreach ($diagnoses as $row): ?>
            <div class="card mb-3 border-0 shadow-sm bg-light p-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="fw-bold text-navy"><?= htmlspecialchars($row['diagnosis_name']) ?></h4>
                        <span class="info-pill"><?= $row['diagnosis_date'] ?></span>
                    </div>
                    <?php if($selected_user_id === 'all'): ?><p class="text-primary fw-bold">Patient: <?= htmlspecialchars($row['patient_name']) ?></p><?php endif; ?>
                    <p><?= htmlspecialchars($row['diagnosis_details']) ?></p>
                    <div class="row border-top pt-2 mt-2">
                        <div class="col-4">Doctor: <strong><?= htmlspecialchars($row['doctor_name']) ?></strong></div>
                        <div class="col-4">Room: <strong>#<?= htmlspecialchars($row['room_number']) ?></strong></div>
                        <div class="col-4">Bed: <strong>#<?= htmlspecialchars($row['bed_number']) ?></strong></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-dark btn-lg mt-3 no-print" onclick="window.print()">Print Report</button>
    </div>
    <?php endif; ?>
</div>

</body>
</html>