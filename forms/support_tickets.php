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

$current_user_id = 1; // Tusaale: Isticmaalaha hadda jira

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // 1. Fetch Tickets
    if ($_GET['action'] == 'fetch') {
        $sql = "SELECT st.*, u.full_name 
                FROM support_tickets st 
                JOIN users u ON st.user_id = u.user_id 
                ORDER BY st.created_at DESC";
        $stm = $conn->prepare($sql);
        $stm->execute();
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Create Ticket
    if ($_GET['action'] == 'save') {
        $sql = "INSERT INTO support_tickets (user_id, subject, message) VALUES (:uid, :sub, :msg)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':uid' => $current_user_id,
            ':sub' => $_POST['subject'],
            ':msg' => $_POST['message']
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
    <title>Support Tickets | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --cyan-gradient: linear-gradient(135deg, #00fbff 0%, #00838f 100%);
        }
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        
        .top-header {
            background: var(--cyan-gradient);
            color: white;
            padding: 40px 0;
            border-radius: 0 0 30px 30px;
        }

        .ticket-card {
            border: none;
            border-radius: 15px;
            transition: 0.3s;
            background: white;
        }

        .ticket-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

        .status-open { background: #fff3e0; color: #ef6c00; }
        .status-progress { background: #e3f2fd; color: #1565c0; }
        .status-closed { background: #e8f5e9; color: #2e7d32; }

        .btn-cyan {
            background: var(--cyan-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-cyan:hover { color: white; opacity: 0.9; }
    </style>
</head>
<body>

<div class="top-header text-center mb-5">
    <div class="container">
        <h2 class="fw-bold"><i class="fa-solid fa-headset me-2"></i> Help & Support</h2>
        <p>How can we help you today? Submit a ticket and we'll get back to you.</p>
        <button class="btn btn-light fw-bold px-4 rounded-pill mt-2" data-bs-toggle="modal" data-bs-target="#ticketModal">
            <i class="fa fa-plus-circle me-1"></i> Open New Ticket
        </button>
    </div>
</div>

<div class="container">
    <div class="row" id="ticketContainer">
        <!-- Tickets will be injected here -->
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <form id="ticketForm">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Create Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject / Issue</label>
                        <input type="text" name="subject" class="form-control" placeholder="Briefly describe the issue" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Message (Max 70 chars)</label>
                        <textarea name="message" class="form-control" rows="3" maxlength="70" placeholder="Details of your request..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-cyan w-100 py-2">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function fetchTickets() {
    $.get('?action=fetch', function(data) {
        let html = '';
        data.forEach(t => {
            let statusClass = t.status == 'Open' ? 'status-open' : (t.status == 'In Progress' ? 'status-progress' : 'status-closed');
            let date = new Date(t.created_at).toLocaleDateString();
            
            html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card ticket-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge ${statusClass} px-3 py-2 rounded-pill">${t.status}</span>
                            <small class="text-muted"><i class="fa fa-clock me-1"></i> ${date}</small>
                        </div>
                        <h6 class="fw-bold text-dark mb-2">${t.subject}</h6>
                        <p class="text-secondary small mb-3">${t.message}</p>
                        <div class="pt-3 border-top d-flex align-items-center">
                            <div class="bg-light rounded-circle p-2 me-2"><i class="fa fa-user small text-cyan"></i></div>
                            <small class="fw-bold text-muted">${t.full_name}</small>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        $('#ticketContainer').html(html || '<div class="text-center p-5 w-100">No support tickets found.</div>');
    });
}

$('#ticketForm').submit(function(e) {
    e.preventDefault();
    $.post('?action=save', $(this).serialize(), function(res) {
        if(res.status) {
            Swal.fire({ icon: 'success', title: 'Ticket Created!', timer: 1500, showConfirmButton: false });
            $('#ticketModal').modal('hide');
            $('#ticketForm')[0].reset();
            fetchTickets();
        }
    });
});

$(document).ready(fetchTickets);
</script>
</body>
</html>