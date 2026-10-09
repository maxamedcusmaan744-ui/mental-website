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

// Check if connection already exists, if not, create it
if (!isset($conn)) {
    $database = new Connection();
    $conn = $database->db;
}

// ================= GET PAYMENTS LINKED THROUGH ADMISSIONS =================
// Multi-table JOIN resolved: lcg_payments -> bills -> patient_admissions -> users
$query = "
    SELECT 
        p.payment_id,
        p.amount_paid AS amount,
        p.payment_date,
        u.full_name AS payer_name,
        u.profile_pic AS payer_pic
    FROM lcg_payments p
    INNER JOIN bills b ON p.bill_id = b.bill_id
    INNER JOIN patient_admissions pa ON b.admission_id = pa.admission_id
    INNER JOIN users u ON pa.patient_id = u.user_id 
    ORDER BY p.payment_id DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPayments = count($payments);
?>

<style>
    /* Habeeb Hospital Stylesheet & Variables */
    :root {
        --navy-main: #1b2a4a;
        --navy-gradient: linear-gradient(135deg, #1b2a4a 0%, #0d1527 100%);
        --orange-main: #ea580c;
        --orange-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        
        --card-bg: rgba(255, 255, 255, 0.45);
        --card-border: rgba(255, 255, 255, 0.6);
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --tr-bg: rgba(255, 255, 255, 0.6);
        --tr-hover: rgba(255, 255, 255, 0.9);
    }

    [data-bs-theme="dark"] {
        --card-bg: rgba(15, 23, 42, 0.45);
        --card-border: rgba(255, 255, 255, 0.06);
        --text-dark: #f8fafc;
        --text-muted: #94a3b8;
        --tr-bg: rgba(30, 41, 59, 0.5);
        --tr-hover: rgba(30, 41, 59, 0.8);
    }

    /* Glassmorphism Card Element */
    .glass-card {
        background: var(--card-bg) !important;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid var(--card-border) !important;
        border-radius: 24px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.01);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(234, 88, 12, 0.08);
        border: 1px solid rgba(249, 115, 22, 0.25) !important;
    }

    /* Modal Glassmorphism */
    .modal-content-glass {
        background: var(--card-bg) !important;
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid var(--card-border) !important;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    /* Stats Icon Styling */
    .stats-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bg-hospital-orange { 
        background-color: rgba(249, 115, 22, 0.1) !important; 
        color: #ea580c !important; 
        border: 1px solid rgba(249, 115, 22, 0.15); 
    }
    [data-bs-theme="dark"] .bg-hospital-orange { 
        background-color: rgba(249, 115, 22, 0.2) !important; 
        color: #ff985e !important; 
    }

    /* Table Custom Design */
    .custom-table {
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .custom-table thead th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        color: var(--text-muted);
        border: none;
        padding: 1rem;
    }
    .custom-table tbody tr {
        background-color: var(--tr-bg) !important;
        transition: all 0.2s ease;
    }
    .custom-table tbody tr:hover {
        background-color: var(--tr-hover) !important;
        transform: scale(1.005);
    }
    .custom-table tbody td {
        padding: 1rem;
        border: none;
        vertical-align: middle;
        color: var(--text-dark);
    }
    .custom-table tbody tr td:first-child { border-top-left-radius: 14px; border-bottom-left-radius: 14px; }
    .custom-table tbody tr td:last-child { border-top-right-radius: 14px; border-bottom-right-radius: 14px; }

    /* Profile Images */
    .user-img {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        object-fit: cover;
        box-shadow: 0 4px 10px rgba(27, 42, 74, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.4);
    }

    /* Custom Badges for Payment Methods */
    .method-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
    }
    .method-badge.bg-default { background-color: rgba(100, 116, 139, 0.15) !important; color: #94a3b8 !important; }

    /* Dynamic Payment Status Pills */
    .status-pill {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .status-pill.badge-pay-completed { background-color: rgba(16, 185, 129, 0.15) !important; color: #10b981 !important; }

    @keyframes pulse {
        0% { opacity: 0.4; }
        50% { opacity: 1; }
        100% { opacity: 0.4; }
    }
    .live-pulse {
        animation: pulse 2s infinite;
        color: #ea580c;
    }
</style>

<div class="col-xl-3 col-md-6">
    <div class="card glass-card p-4 border-0 h-100" 
         data-bs-toggle="modal" 
         data-bs-target="#paymentsModal"
         style="cursor: pointer;">
        
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <p class="text-muted small fw-bold mb-1 text-uppercase" style="letter-spacing: 0.5px;">Total Transactions</p>
                <h2 class="fw-bold mb-2" style="font-size: 2rem; color: var(--text-dark);">
                    <?= number_format($totalPayments) ?>
                </h2>
                <span class="small fw-bold d-inline-flex align-items-center" style="color: var(--orange-main);">
                    <i class="bi bi-circle-fill me-1 live-pulse" style="font-size: 8px;"></i> 
                    Live Database
                </span>
            </div>
            
            <div class="stats-icon bg-hospital-orange">
                <i class="bi bi-cash-stack fs-4"></i>
            </div>
        </div>
        
    </div>
</div>

<div class="modal fade" id="paymentsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content modal-content-glass">
      
      <div class="modal-header px-4 py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--card-border) !important;">
        <div class="d-flex align-items-center gap-2">
            <div class="stats-icon bg-hospital-orange" style="width: 40px; height: 40px; border-radius: 10px;">
                <i class="bi bi-credit-card-2-front fs-5"></i>
            </div>
            <h5 class="modal-title fw-bold" style="color: var(--text-dark);">Payment History</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center custom-table mb-0">
            <thead>
              <tr>
                <th>Profile</th>
                <th>Payer Name</th>
                <th>Amount Paid</th>
                <th>Method</th>
                <th>Payment Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($payments)): ?>
                <tr>
                    <td colspan="6" class="text-muted py-4">No payments recorded in database.</td>
                </tr>
            <?php else: ?>
                <?php foreach($payments as $pay): ?>
                    <?php
                        $payerName = htmlspecialchars($pay['payer_name']);
                        $amount = number_format($pay['amount'], 2);
                        $paymentDate = date('M d, Y', strtotime($pay['payment_date']));
                        $profilePic = $pay['payer_pic'] ?? '';

                        // Fallback labels due to architectural separation
                        $method = 'Bill Payment';
                        $status = 'Completed';

                        // Avatar Image Handler
                        if (!empty($profilePic)) {
                            $pathFromHome = "forms/uploads/" . $profilePic;
                            $pathFromPat  = "../forms/uploads/" . $profilePic;

                            if (file_exists(__DIR__ . "/../forms/uploads/" . $profilePic)) {
                                if (basename($_SERVER['PHP_SELF']) == 'home.php') {
                                    $imgFinal = $pathFromHome;
                                } else {
                                    $imgFinal = $pathFromPat;
                                }
                            } else {
                                $imgFinal = "https://ui-avatars.com/api/?background=1b2a4a&color=ffffff&bold=true&name=" . urlencode($pay['payer_name']);
                            }
                        } else {
                            $imgFinal = "https://ui-avatars.com/api/?background=1b2a4a&color=ffffff&bold=true&name=" . urlencode($pay['payer_name']);
                        }

                        $statusClass = 'badge-pay-completed';
                        $methodClass = 'bg-default';
                    ?>
                    <tr>
                        <td>
                            <img class="user-img" src="<?= htmlspecialchars($imgFinal) ?>" alt="Payer Profile">
                        </td>
                        <td class="fw-semibold" style="color: var(--text-dark);"><?= $payerName ?></td>
                        <td class="fw-bold" style="color: var(--text-dark);">$<?= $amount ?></td>
                        <td>
                            <span class="method-badge <?= $methodClass ?>">
                                <?= $method ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= $paymentDate ?></td>
                        <td>
                            <span class="status-pill <?= $statusClass ?>"><?= $status ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
    </div>
  </div>
</div>