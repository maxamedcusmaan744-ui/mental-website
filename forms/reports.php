<?php
// =========================================================
// 1. DATABASE CONNECTION & LOGIC
// =========================================================
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
            die(json_encode(["status" => false, "msg" => "Connection failed"]));
        }
    }
}

$admin_id = 1; // Kan hadda login-ka ah (Admin)

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // 1. Fetch Reports
    if ($_GET['action'] == 'fetch') {
        $sql = "SELECT r.*, u.full_name as admin_name 
                FROM reports r 
                JOIN users u ON r.admin_id = u.user_id 
                ORDER BY r.generated_on DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Generate/Save Report
    if ($_GET['action'] == 'save') {
        $sql = "INSERT INTO reports (admin_id, report_type, description) VALUES (:aid, :type, :desc)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':aid'  => $admin_id,
            ':type' => $_POST['report_type'],
            ':desc' => $_POST['description']
        ]);
        echo json_encode(["status" => $success]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports | MindCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .report-card { border: none; border-radius: 12px; transition: 0.3s; }
        .report-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .sidebar { background: #212529; min-height: 100vh; color: white; }
        .btn-generate { background: #00bcd4; color: white; border: none; border-radius: 8px; }
        .btn-generate:hover { background: #0097a7; color: white; }
        .table thead { background: #f1f3f5; }
        .badge-report { background: rgba(0, 188, 212, 0.1); color: #0097a7; border: 1px solid #00bcd4; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-12 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark">System Analytics & Reports</h3>
                    <p class="text-muted">Manage and generate system performance reports.</p>
                </div>
                <button class="btn btn-generate px-4 py-2" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="fa fa-plus-circle me-2"></i> Generate New Report
                </button>
            </div>

            <div class="card report-card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="p-3">#ID</th>
                                <th class="p-3">Report Type</th>
                                <th class="p-3">Description</th>
                                <th class="p-3">Admin</th>
                                <th class="p-3">Date Generated</th>
                                <th class="p-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reportList">
                            <!-- Reports will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <form id="reportForm">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Generate Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Type of Report</label>
                        <select name="report_type" class="form-select" required>
                            <option value="User Activity">User Activity</option>
                            <option value="Appointment Summary">Appointment Summary</option>
                            <option value="Assessment Trends">Assessment Trends</option>
                            <option value="Counsellor Performance">Counsellor Performance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Brief Description</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="50" placeholder="Max 50 characters..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-generate px-4">Save Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function fetchReports() {
    $.get('?action=fetch', function(data) {
        let html = '';
        data.forEach(r => {
            let date = new Date(r.generated_on).toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            html += `
                <tr>
                    <td class="p-3 text-muted">#${r.report_id}</td>
                    <td class="p-3"><span class="badge badge-report">${r.report_type}</span></td>
                    <td class="p-3 text-dark">${r.description}</td>
                    <td class="p-3 small">${r.admin_name}</td>
                    <td class="p-3 text-muted small">${date}</td>
                    <td class="p-3 text-center">
                        <button class="btn btn-sm btn-outline-secondary border-0"><i class="fa fa-download"></i></button>
                    </td>
                </tr>`;
        });
        $('#reportList').html(html || '<tr><td colspan="6" class="text-center p-5">No reports found.</td></tr>');
    });
}

$('#reportForm').submit(function(e) {
    e.preventDefault();
    $.post('?action=save', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({ icon: 'success', title: 'Success', text: 'Report saved successfully!', timer: 1500, showConfirmButton: false });
            $('#reportModal').modal('hide');
            $('#reportForm')[0].reset();
            fetchReports();
        }
    });
});

$(document).ready(fetchReports);
</script>
</body>
</html>