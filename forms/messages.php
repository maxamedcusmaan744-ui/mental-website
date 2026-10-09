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

$current_user = 1; // Tusaale: Kan login-ka ah (Session-ka ka soo qaado)

if (isset($_GET['action'])) {
    $db = new Connection();
    $conn = $db->db;
    header('Content-Type: application/json');

    // 1. Fetch Messages
    if ($_GET['action'] == 'fetch') {
        $receiver_id = $_GET['receiver_id'];
        $sql = "SELECT * FROM messages 
                WHERE (sender_id = :curr AND receiver_id = :rec) 
                OR (sender_id = :rec AND receiver_id = :curr) 
                ORDER BY sent_at ASC";
        $stm = $conn->prepare($sql);
        $stm->execute([':curr' => $current_user, ':rec' => $receiver_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // 2. Send Message
    if ($_GET['action'] == 'send') {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (:sid, :rid, :msg)";
        $stm = $conn->prepare($sql);
        $success = $stm->execute([
            ':sid' => $current_user,
            ':rid' => $_POST['receiver_id'],
            ':msg' => $_POST['message_text']
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
    <title>Support Chat | MindCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #e5ddd5; font-family: 'Inter', sans-serif; }
        .chat-container { height: 70vh; overflow-y: auto; background: #f0f2f5; padding: 20px; border-radius: 15px; }
        
        /* Message Bubbles */
        .msg { max-width: 75%; margin-bottom: 15px; padding: 10px 15px; border-radius: 15px; position: relative; clear: both; }
        .msg-sent { background: #dcf8c6; float: right; border-bottom-right-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .msg-received { background: white; float: left; border-bottom-left-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .msg-time { font-size: 0.7rem; color: #888; display: block; margin-top: 5px; text-align: right; }
        
        .chat-footer { background: white; padding: 15px; border-radius: 0 0 15px 15px; border-top: 1px solid #ddd; }
        .input-group input { border-radius: 30px; padding-left: 20px; border: 1px solid #ddd; }
        .btn-send { background: #00838f; color: white; border-radius: 50%; width: 45px; height: 45px; margin-left: 10px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-dark text-white p-3" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex align-items-center">
                        <div class="bg-info rounded-circle p-2 me-3"><i class="fa fa-user-md text-white"></i></div>
                        <div>
                            <h6 class="mb-0">Counsellor Support</h6>
                            <small class="text-success"><i class="fa fa-circle small"></i> Online</small>
                        </div>
                    </div>
                </div>

                <div class="chat-container" id="chatBox">
                    <!-- Messages will load here via AJAX -->
                </div>

                <div class="chat-footer">
                    <form id="chatForm">
                        <input type="hidden" name="receiver_id" value="2"> <!-- ID-ga qofka aad la hadlayso -->
                        <div class="input-group">
                            <input type="text" name="message_text" id="msgInput" class="form-control" placeholder="Type a message..." required autocomplete="off">
                            <button type="submit" class="btn btn-send"><i class="fa fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const receiver_id = 2; // Tusaale: Counsellor ID
const current_user = 1; // User-ka hadda jooga

function loadMessages() {
    $.get(`?action=fetch&receiver_id=${receiver_id}`, function(data) {
        let html = '';
        data.forEach(m => {
            let isSent = (m.sender_id == current_user);
            let time = new Date(m.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            html += `
                <div class="msg ${isSent ? 'msg-sent' : 'msg-received'}">
                    ${m.message_text}
                    <span class="msg-time">${time}</span>
                </div>`;
        });
        $('#chatBox').html(html);
        $("#chatBox").scrollTop($("#chatBox")[0].scrollHeight); // Auto-scroll to bottom
    });
}

$('#chatForm').submit(function(e) {
    e.preventDefault();
    let msg = $('#msgInput').val();
    if(msg.trim() == '') return;

    $.post('?action=send', $(this).serialize(), function(res) {
        if(res.status) {
            $('#msgInput').val('');
            loadMessages();
        }
    });
});

// Refresh chat every 3 seconds for real-time feel
setInterval(loadMessages, 3000);
$(document).ready(loadMessages);
</script>

</body>
</html>