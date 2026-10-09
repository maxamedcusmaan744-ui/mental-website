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

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // Fetch Logs with User Details
    if ($_GET['action'] == 'fetch') {
        $sql = "SELECT al.*, u.full_name, u.role 
                FROM activity_logs al 
                JOIN users u ON al.user_id = u.user_id 
                ORDER BY al.created_at DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
}

// Function to log activity (Tusaale ahaan sida loo isticmaalo koodka kale dhexdiisa)
/*
function saveLog($conn, $user_id, $activity) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $device = $_SERVER['HTTP_USER_AGENT'];
    $sql = "INSERT INTO activity_logs (user_id, activity, ip_address, device_info) VALUES (?, ?, ?, ?)";
    $conn->prepare($sql)->execute([$user_id, $activity, $ip, $device]);
}
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Logs | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --cyan-gradient: linear-gradient(135deg, #00e5ff, #00838f);
        }
        body { background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .top-banner {
            background: var(--cyan-gradient);
            color: white;
            padding: 40px 0;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .log-card {
            border: none;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: -30px;
        }

        .table thead {
            background: #f8f9fa;
            color: #555;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .badge-user { background: #e0f7fa; color: #00838f; border-radius: 5px; }
        .ip-text { font-family: 'Courier New', Courier, monospace; color: #d81b60; font-weight: bold; }
        .device-info { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; }
    </style>
</head>
<body>

<div class="top-banner text-center">
    <div class="container">
        <h2 class="fw-bold"><i class="fa-solid fa-shield-halved me-2"></i> System Activity Logs</h2>
        <p class="opacity-75">Monitoring security and user interactions in real-time</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card log-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-bold text-secondary">Recent Activities</h5>
                    <button onclick="fetchLogs()" class="btn btn-sm btn-light border">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Activity</th>
                                <th>IP Address</th>
                                <th>Device</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody id="logTable">
                            <!-- Data loads here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function fetchLogs() {
    $.get('?action=fetch', function(data) {
        let html = '';
        data.forEach(log => {
            let date = new Date(log.created_at).toLocaleString();
            html += `
            <tr>
                <td>
                    <div class="fw-bold text-dark">${log.full_name}</div>
                    <small class="badge badge-user">${log.role}</small>
                </td>
                <td>
                    <i class="fa-solid fa-circle-dot text-info me-2 small"></i>
                    ${log.activity}
                </td>
                <td><span class="ip-text">${log.ip_address}</span></td>
                <td>
                    <span class="device-info text-muted small" title="${log.device_info}">
                        <i class="fa-solid fa-laptop me-1"></i> ${log.device_info}
                    </span>
                </td>
                <td class="small text-secondary">${date}</td>
            </tr>`;
        });
        $('#logTable').html(html || '<tr><td colspan="5" class="text-center">No logs recorded yet.</td></tr>');
    });
}

$(document).ready(fetchLogs);
</script>
</body>
</html>