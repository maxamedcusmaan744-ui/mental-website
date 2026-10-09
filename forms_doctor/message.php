<?php
session_start();

// MAA_OBO: Ka saar comment-ka hoose si aad u tijaabiso session-ka adigoon login samayn
// $_SESSION['user_id'] = 2; 
// $_SESSION['user_type'] = 'Patient'; 

// =========================================================
// DATABASE CONNECTION
// =========================================================
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
            header('Content-Type: application/json');
            die(json_encode(["status" => false, "msg" => "DB connection failed: " . $e->getMessage()]));
        }
    }
}

$db = new Connection();
$conn = $db->db;

$current_user_id = $_SESSION['user_id'] ?? 0;
$session_type = $_SESSION['user_type'] ?? 'Patient';
$current_user_type = ($session_type == 'Counsellor') ? 'Doctor' : $session_type;

// 1. SOO SAAR MAGACA RASMIGA AH EE USER-KA (Full Name)
$current_user_name = "Guest User";
if ($current_user_id > 0) {
    $u_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = :uid");
    $u_stmt->execute([':uid' => $current_user_id]);
    $u_row = $u_stmt->fetch(PDO::FETCH_ASSOC);
    if ($u_row) {
        $current_user_name = $u_row['full_name'];
    }
}

// Raadi doctor_id-giisa rasmiga ah haddii uu yahay Doctor
$real_doctor_id = 0;
if ($current_user_id > 0 && $current_user_type == 'Doctor') {
    $doc_stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = :uid");
    $doc_stmt->execute([':uid' => $current_user_id]);
    $doc_row = $doc_stmt->fetch(PDO::FETCH_ASSOC);
    if ($doc_row) {
        $real_doctor_id = $doc_row['doctor_id'];
    }
}

// =========================================================
// ROUTER LOGIC FOR AJAX
// =========================================================
if (isset($_GET['url'])) {
    $route = $_GET['url'];
    header('Content-Type: application/json');

    // Fetch Chat History
    if ($route == 'fetch_messages') {
        $target_id = $_GET['target_id'] ?? 0;
        
        if ($current_user_type == 'Patient') {
            $patient_id = $current_user_id;
            $doctor_id = $target_id;
        } else {
            $patient_id = $target_id;
            $doctor_id = $real_doctor_id;
        }

        $sql = "SELECT * FROM messages 
                WHERE patient_id = :p_id AND doctor_id = :d_id 
                ORDER BY sent_at ASC";
        $stm = $conn->prepare($sql);
        $stm->execute([':p_id' => $patient_id, ':d_id' => $doctor_id]);
        echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Send Message
    if ($route == 'send_message') {
        if ($current_user_id == 0) {
            echo json_encode(["status" => false, "msg" => "Fadlan nidaamka soo gal marka hore!"]);
            exit;
        }

        $target_id = $_POST['target_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');

        if (empty($message) || $target_id == 0) {
            echo json_encode(["status" => false, "msg" => "Fariinta ama qofka la rabo midna ma dhowrna!"]);
            exit;
        }

        if ($current_user_type == 'Patient') {
            $patient_id = $current_user_id;
            $doctor_id = $target_id;
        } else {
            $patient_id = $target_id;
            $doctor_id = $real_doctor_id;
        }

        if ($current_user_type == 'Doctor' && $doctor_id == 0) {
            echo json_encode(["status" => false, "msg" => "Cilad: doctor_id missing."]);
            exit;
        }

        try {
            $sql = "INSERT INTO messages (patient_id, doctor_id, message, sender_type) 
                    VALUES (:p_id, :d_id, :msg, :sender)";
            $stm = $conn->prepare($sql);
            $success = $stm->execute([
                ':p_id'   => $patient_id,
                ':d_id'   => $doctor_id,
                ':msg'    => $message,
                ':sender' => $current_user_type
            ]);
            echo json_encode(["status" => $success, "msg" => "Fariinta waa la diray!"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => false, "msg" => "Database Error: " . $e->getMessage()]);
        }
        exit;
    }
}

// Soo saar liiska dadka la sheekaysan karo (Contacts)
$contacts = [];
if ($current_user_id > 0) {
    if ($current_user_type == 'Patient') {
        $sql = "SELECT d.doctor_id as id, u.full_name, d.specialization as subtext 
                FROM doctors d JOIN users u ON d.user_id = u.user_id";
        $contacts = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT user_id as id, full_name, email as subtext FROM users WHERE user_type = 'Patient'";
        $contacts = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyan Care | Session Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-cyan: #00bcd4;
            --dark-cyan: #00838f;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }
        body { background-color: #f4f9fa; font-family: 'Segoe UI', sans-serif; }
        
        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 15px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,188,212,0.25);
        }

        .chat-container {
            height: calc(100vh - 140px);
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .sidebar-contacts {
            border-right: 1px solid #edf2f7;
            height: 100%;
            overflow-y: auto;
        }

        .contact-box {
            padding: 15px;
            border-bottom: 1px solid #f7fafc;
            cursor: pointer;
            transition: 0.2s;
        }
        .contact-box:hover, .contact-box.active {
            background: rgba(0, 188, 212, 0.08);
        }

        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: #fafdfd;
        }

        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .msg-bubble {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 16px;
            font-size: 0.95rem;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .msg-sent {
            background: var(--gradient-cyan);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }

        .msg-received {
            background: #ffffff;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
            border: 1px solid #e2e8f0;
        }

        .chat-footer {
            padding: 15px;
            background: white;
            border-top: 1px solid #edf2f7;
        }

        .btn-cyan {
            background: var(--gradient-cyan);
            color: white;
            border: none;
            transition: 0.3s;
        }
        .btn-cyan:hover { color: white; box-shadow: 0 4px 12px rgba(0,188,212,0.3); }
    </style>
</head>
<body>

<nav class="navbar-custom mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-solid fa-user-md me-2"></i> Cyan Care Portal Chat</h5>
        <span class="badge bg-light text-dark">
            <i class="fa-solid fa-user me-1"></i> Logged as: <?php echo htmlspecialchars($current_user_name); ?> (<?php echo $current_user_type; ?>)
        </span>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row chat-container">
        <div class="col-md-4 col-lg-3 sidebar-contacts p-0 bg-white">
            <div class="p-3 bg-light fw-bold text-secondary border-bottom">
                <i class="fa-solid fa-hospital-user me-1"></i> Conversations List
            </div>
            <?php if(empty($contacts)): ?>
                <div class="text-center py-4 text-muted small">No active contacts found or session empty.</div>
            <?php else: ?>
                <?php foreach($contacts as $index => $c): ?>
                    <div class="contact-box <?php echo $index === 0 ? 'active' : ''; ?>" data-id="<?php echo $c['id']; ?>" data-name="<?php echo htmlspecialchars($c['full_name']); ?>">
                        <div class="fw-bold text-dark"><i class="fa-solid fa-circle-user text-info me-2"></i><?php echo htmlspecialchars($c['full_name']); ?></div>
                        <small class="text-muted d-block text-truncate"><?php echo htmlspecialchars($c['subtext']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-md-8 col-lg-9 p-0">
            <div class="chat-area">
                <div class="p-3 bg-white border-bottom fw-bold text-dark d-flex align-items-center">
                    <i class="fa-solid fa-user-md text-cyan me-2 fs-5"></i>
                    <span id="activeChatName">Loading session...</span>
                </div>

                <div class="chat-messages" id="chatBox"></div>

                <div class="chat-footer">
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="hidden" id="targetId" name="target_id">
                        <input type="text" id="messageInput" name="message" class="form-control rounded-pill px-3" placeholder="Type a secured message..." required autocomplete="off">
                        <button type="submit" class="btn btn-cyan rounded-circle"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentRole = "<?php echo $current_user_type; ?>";

function loadMessages() {
    let targetId = $('.contact-box.active').data('id');
    if(!targetId) {
        $('#activeChatName').text("No active target selected");
        return;
    }

    $('#targetId').val(targetId);
    $('#activeChatName').text($('.contact-box.active').data('name'));

    $.get('?url=fetch_messages&target_id=' + targetId, function(res) {
        let html = '';
        if(res.length === 0) {
            html = `<div class="text-center text-muted my-auto small py-5">
                        <i class="fa-regular fa-envelope fs-2 mb-2"></i><br>No messages yet. Send a secure message.
                    </div>`;
        } else {
            res.forEach(m => {
                let isMe = (m.sender_type === currentRole);
                let bubbleClass = isMe ? 'msg-sent' : 'msg-received';
                
                html += `<div class="msg-bubble ${bubbleClass}">
                            ${m.message}
                            <div class="text-end opacity-50" style="font-size:0.65rem; margin-top:4px;">
                                ${m.sent_at.substring(11, 16)}
                            </div>
                         </div>`;
            });
        }
        $('#chatBox').html(html);
        $('#chatBox').scrollTop($('#chatBox')[0].scrollHeight);
    });
}

$(document).on('click', '.contact-box', function() {
    $('.contact-box').removeClass('active');
    $(this).addClass('active');
    loadMessages();
});

$('#chatForm').submit(function(e) {
    e.preventDefault();
    let msg = $('#messageInput').val().trim();
    if(!msg) return;

    $.post('?url=send_message', $(this).serialize(), function(res) {
        if(res.status) {
            $('#messageInput').val('');
            loadMessages();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error Sending',
                text: res.msg
            });
        }
    }).fail(function(xhr, status, error) {
        console.error(xhr.responseText);
    });
});

$(document).ready(function() {
    loadMessages();
    setInterval(loadMessages, 3000); 
});
</script>
</body>
</html>