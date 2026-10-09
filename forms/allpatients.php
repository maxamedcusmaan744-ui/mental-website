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
                $this->db = new PDO(
                    "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4",
                    $this->user_name,
                    $this->password
                );
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
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($status === 'Admitted' || $status === 'Discharged') {
    $where[] = "pa.status = :status";
    $params[':status'] = $status;
}

if (!empty($dateFrom)) {
    $where[] = "DATE(pa.admission_date) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "DATE(pa.admission_date) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// patient_admissions.patient_id waxaa loo isticmaalay users.user_id
$stmt = $conn->prepare("
    SELECT
        pa.admission_id,
        pa.patient_id,
        pa.room_id,
        pa.bed_id,
        pa.admission_date,
        pa.admission_reason,
        pa.status,
        pa.created_at,
        u.full_name AS patient_name,
        u.phone AS patient_phone,
        u.email AS patient_email,
        u.profile_pic,
        r.room_number,
        b.bed_number
    FROM patient_admissions pa
    INNER JOIN users u ON pa.patient_id = u.user_id
    INNER JOIN rooms r ON pa.room_id = r.room_id
    INNER JOIN beds b ON pa.bed_id = b.bed_id
    $whereSql
    ORDER BY pa.admission_date DESC, pa.admission_id DESC
");
$stmt->execute($params);
$admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAdmissions = count($admissions);
$admittedCount = count(array_filter($admissions, fn($row) => $row['status'] === 'Admitted'));
$dischargedCount = count(array_filter($admissions, fn($row) => $row['status'] === 'Discharged'));
$todayCount = count(array_filter($admissions, fn($row) => date('Y-m-d', strtotime($row['admission_date'])) === date('Y-m-d')));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Admissions Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --orange: #f97316;
            --orange-dark: #ea580c;
            --navy: #0f172a;
            --muted: #64748b;
            --border: #e5e7eb;
            --bg: #f8fafc;
        }

        body {
            background: var(--bg);
            color: var(--navy);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .report-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 22px 0;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(249, 115, 22, .12);
            color: var(--orange-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-card,
        .filter-card,
        .table-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .stat-card {
            padding: 18px;
        }

        .stat-label {
            color: var(--muted);
            font-size: .78rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 4px 0 0;
        }

        .btn-orange {
            background: var(--orange);
            color: #fff;
            border: none;
            font-weight: 700;
        }

        .btn-orange:hover {
            background: var(--orange-dark);
            color: #fff;
        }

        .table thead th {
            background: #fff7ed;
            color: #9a3412;
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid #fed7aa;
        }

        .patient-img {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #fed7aa;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
        }

        .status-pill::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-admitted {
            background: rgba(16, 185, 129, .13);
            color: #047857;
        }

        .status-admitted::before {
            background: #10b981;
        }

        .status-discharged {
            background: rgba(100, 116, 139, .13);
            color: #475569;
        }

        .status-discharged::before {
            background: #64748b;
        }

        .reason-cell {
            max-width: 280px;
            white-space: normal;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff;
            }
        }
    </style>
</head>
<body>
    <header class="report-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="brand-icon">
                        <i class="bi bi-clipboard2-pulse-fill"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1">Patient Admissions Report</h3>
                        <div class="text-muted small">Generated on <?=date('M d, Y h:i A')?></div>
                    </div>
                </div>

                <button class="btn btn-orange px-4 no-print" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> Print Report
                </button>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Total Admissions</div>
                    <div class="stat-value"><?=number_format($totalAdmissions)?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Currently Admitted</div>
                    <div class="stat-value"><?=number_format($admittedCount)?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Discharged</div>
                    <div class="stat-value"><?=number_format($dischargedCount)?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Today</div>
                    <div class="stat-value"><?=number_format($todayCount)?></div>
                </div>
            </div>
        </div>

        <form class="filter-card p-3 mb-4 no-print" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Admitted" <?=$status === 'Admitted' ? 'selected' : ''?>>Admitted</option>
                        <option value="Discharged" <?=$status === 'Discharged' ? 'selected' : ''?>>Discharged</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?=htmlspecialchars($dateFrom)?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?=htmlspecialchars($dateTo)?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-orange flex-fill" type="submit">
                        <i class="bi bi-funnel me-2"></i> Filter
                    </button>
                    <a class="btn btn-outline-secondary" href="<?=htmlspecialchars(basename($_SERVER['PHP_SELF']))?>">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Profile</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Room</th>
                            <th>Bed</th>
                            <th>Admission Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admissions)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No admission records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admissions as $row): ?>
                                <?php
                                    $statusClass = $row['status'] === 'Admitted' ? 'status-admitted' : 'status-discharged';
                                    $profilePic = !empty($row['profile_pic']) ? $row['profile_pic'] : 'default.jpg';
                                    $avatar = "https://ui-avatars.com/api/?background=1b2a4a&color=fff&bold=true&name=" . urlencode($row['patient_name']);
                                ?>
                                <tr>
                                    <td class="fw-bold text-muted">#<?=htmlspecialchars($row['admission_id'])?></td>
                                    <td>
                                        <img class="patient-img" src="uploads/<?=htmlspecialchars($profilePic)?>" onerror="this.src='<?=htmlspecialchars($avatar)?>'">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?=htmlspecialchars($row['patient_name'])?></div>
                                        <div class="text-muted small">Patient/User ID: <?=htmlspecialchars($row['patient_id'])?></div>
                                    </td>
                                    <td>
                                        <div><?=htmlspecialchars($row['patient_phone'])?></div>
                                        <div class="text-muted small"><?=htmlspecialchars($row['patient_email'])?></div>
                                    </td>
                                    <td><?=htmlspecialchars($row['room_number'])?></td>
                                    <td><?=htmlspecialchars($row['bed_number'])?></td>
                                    <td><?=date('M d, Y h:i A', strtotime($row['admission_date']))?></td>
                                    <td class="reason-cell"><?=htmlspecialchars($row['admission_reason'])?></td>
                                    <td>
                                        <span class="status-pill <?=$statusClass?>">
                                            <?=htmlspecialchars($row['status'])?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
