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

// In loo maleeyo inaan halkan ku helay Ticket ID
$ticket_id = $_GET['id'] ?? 1; 
$current_user_id = 1; 

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // 1. Fetch Responses
    if ($_GET['action'] == 'fetch_responses') {
        $sql = "SELECT tr.*, u.full_name, u.role 
                FROM ticket_responses tr 
                JOIN users u ON tr.responder_id = u.user_id 
                WHERE tr.ticket_id = :tid 
                ORDER BY tr.responded_at ASC";
        $stm = $conn->prepare($sql);
        $stm->execute([':tid' => $ticket_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Save Response
    if ($_GET['action'] == 'save_response') {
        $sql = "INSERT INTO ticket_responses (ticket_id, responder_id, response_message) 
                VALUES (:tid, :rid, :msg)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':tid' => $ticket_id,
            ':rid' => $current_user_id,
            ':msg' => $_POST['response_message']
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
    <title>Ticket Discussion | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --cyan-gradient: linear-gradient(135deg, #00fbff 0%, #00838f 100%); }
        body { background-color: #f4f7f6; }
        
        .chat-container {
            max-width: 700px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .chat-header {
            background: var(--cyan-gradient);
            color: white;
            padding: 20px;
        }

        .chat-box {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f9f9f9;
        }

        .message {
            margin-bottom: 20px;
            max-width: 80%;
        }

        .message.received { margin-right: auto; }
        .message.sent { margin-left: auto; text-align: right; }

        .message-content {
            padding: 10px 15px;
            border-radius: 15px;
            display: inline-block;
            font-size: 0.9rem;
        }

        .received .message-content { background: #e9ecef; color: #333; border-bottom-left-radius: 2px; }
        .sent .message-content { background: #00acc1; color: white; border-bottom-right-radius: 2px; }

        .user-name { font-size: 0.7rem; font-weight: bold; color: #666; margin-bottom: 4px; display: block; }
        
        .chat-footer { padding: 20px; background: white; border-top: 1px solid #eee; }
        
        .input-group {
            background: #f1f3f4;
            border-radius: 30px;
            padding: 5px 15px;
        }

        .input-group input {
            border: none;
            background: transparent;
            box-shadow: none !important;
        }

        .btn-send {
            background: var(--cyan-gradient);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
    </style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold">Ticket Discussion</h6>
            <small class="opacity-75">Case ID: #<?php echo $ticket_id; ?></small>
        </div>
        <a href="support_tickets.php" class="text-white"><i class="fa fa-times"></i></a>
    </div>

    <div class="chat-box" id="chatBox">
        <!-- Responses load here -->
    </div>

    <div class="chat-footer">
        <form id="responseForm">
            <div class="input-group">
                <input type="text" name="response_message" class="form-control" maxlength="60" placeholder="Type your response (Max 60)..." required>
                <button type="submit" class="btn-send"><i class="fa fa-paper-plane"></i></button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function fetchResponses() {
    $.get('?id=<?php echo $ticket_id; ?>&action=fetch_responses', function(data) {
        let html = '';
        data.forEach(r => {
            let isMe = r.responder_id == <?php echo $current_user_id; ?>;
            let typeClass = isMe ? 'sent' : 'received';
            
            html += `
            <div class="message ${typeClass}">
                <span class="user-name">${r.full_name} (${r.role})</span>
                <div class="message-content shadow-sm">
                    ${r.response_message}
                </div>
            </div>`;
        });
        $('#chatBox').html(html);
        $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
    });
}

$('#responseForm').submit(function(e) {
    e.preventDefault();
    $.post('?id=<?php echo $ticket_id; ?>&action=save_response', $(this).serialize(), function(res) {
        if(res.status) {
            $('#responseForm')[0].reset();
            fetchResponses();
        }
    });
});

$(document).ready(function() {
    fetchResponses();
    setInterval(fetchResponses, 5000); // Auto-refresh every 5 seconds
});
</script>

</body>
</html>