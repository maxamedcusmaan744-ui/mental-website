<?php
session_start();

// MAA_OBO: Ka saar comment-ka hoose si aad u tijaabiso session-ka adigoon login samayn
// $_SESSION['user_id'] = 2; 
// $_SESSION['user_type'] = 'Doctor'; 

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
$current_user_type = ($session_type == 'Counsellor' || $session_type == 'doctor' || $session_type == 'Doctor') ? 'Doctor' : $session_type;

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

// Soo saar liiska dadka la sheekaysan karo (Contacts) + Profile Pictures
$contacts = [];
if ($current_user_id > 0) {
    if ($current_user_type == 'Patient') {
        $sql = "SELECT d.doctor_id as id, u.full_name, d.specialization as subtext, u.profile_pic 
                FROM doctors d JOIN users u ON d.user_id = u.user_id";
        $contacts = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT user_id as id, full_name, email as subtext, profile_pic 
                FROM users 
                WHERE user_type = 'Patient' AND status = 'Active'";
        $contacts = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyan Care | WhatsApp Style Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --whatsapp-bg: #efeae2;
            --whatsapp-out: #e1ffc7;
            --whatsapp-in: #ffffff;
            --primary-cyan: #00bcd4;
            --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7, #00838f);
        }
        body { background-color: #dadbd4; font-family: 'Segoe UI', -apple-system, sans-serif; }
        
        .navbar-custom {
            background: var(--gradient-cyan);
            padding: 12px;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .chat-container {
            height: calc(100vh - 120px);
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .sidebar-contacts {
            border-right: 1px solid #e9edef;
            height: 100%;
            overflow-y: auto;
            background: #ffffff;
        }

        .contact-box {
            padding: 10px 14px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
            transition: 0.1s ease-in-out;
        }
        .contact-box:hover {
            background: #f0f2f5;
        }
        .contact-box.active {
            background: #eaeaea;
        }

        .contact-avatar {
            width: 49px;
            height: 49px;
            object-fit: cover;
            border-radius: 50%;
        }

        /* WHATSAPP CORE CHAT BACKGROUND & LAYOUT */
        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-messages {
            flex-grow: 1;
            padding: 20px 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background-color: var(--whatsapp-bg);
            /* WhatsApp iconic tile wallpaper background pattern */
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            background-repeat: repeat;
        }

        /* WHATSAPP CHAT BUBBLES */
        .msg-row {
            display: flex;
            width: 100%;
            margin-bottom: 2px;
        }
        .msg-row.row-sent { justify-content: flex-end; }
        .msg-row.row-received { justify-content: flex-start; }

        .msg-bubble {
            max-width: 65%;
            padding: 6px 10px 4px 12px;
            font-size: 0.92rem;
            position: relative;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
            display: flex;
            flex-direction: column;
            word-break: break-word;
        }

        .msg-sent {
            background: var(--whatsapp-out);
            color: #111b21;
            border-radius: 8px 0px 8px 8px;
        }

        .msg-received {
            background: var(--whatsapp-in);
            color: #111b21;
            border-radius: 0px 8px 8px 8px;
        }

        /* Meta block for Time & Ticks right inside bubble */
        .msg-meta {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            font-size: 0.68rem;
            color: #667781;
            margin-top: 2px;
            user-select: none;
            align-self: flex-end;
        }
        
        .msg-ticks {
            color: #53bdeb; /* WhatsApp Blue Double Ticks */
            font-size: 0.75rem;
        }

        /* WHATSAPP FOOTER INPUT BAR */
        .chat-footer {
            padding: 10px 15px;
            background: #f0f2f5;
            border-top: 1px solid #e9edef;
        }

        .input-bar-custom {
            background: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 9px 15px;
            font-size: 0.95rem;
        }
        .input-bar-custom:focus {
            box-shadow: none;
            border: none;
        }

        .btn-send-wa {
            background: transparent;
            color: #54656f;
            border: none;
            font-size: 1.3rem;
            transition: 0.2s;
        }
        .btn-send-wa:hover {
            color: #00a884;
        }
    </style>
</head>
<body>

<nav class="navbar-custom mb-2">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h5 class="m-0"><i class="fa-brands fa-whatsapp me-2"></i> Cyan Care Portal Chat</h5>
        <span class="badge bg-light text-dark shadow-sm">
            <i class="fa-solid fa-user me-1 text-info"></i> Active User: <?php echo htmlspecialchars($current_user_name); ?> (<?php echo $current_user_type; ?>)
        </span>
    </div>
</nav>

<div class="container-fluid px-4 py-1">
    <div class="row chat-container">
        <!-- SIDEBAR: CONTACT LIST -->
        <div class="col-md-4 col-lg-3 sidebar-contacts p-0">
            <div class="p-3 fw-bold text-dark border-bottom d-flex align-items-center justify-content-between" style="background:#f0f2f5;">
                <span>Chats</span>
                <i class="fa-solid fa-message text-secondary"></i>
            </div>
            <?php if(empty($contacts)): ?>
                <div class="text-center py-4 text-muted small">No active chats found.</div>
            <?php else: ?>
                <?php foreach($contacts as $index => $c): ?>
                    <?php $user_pic = !empty($c['profile_pic']) ? $c['profile_pic'] : 'default.jpg'; ?>
                    <div class="contact-box d-flex align-items-center gap-3 <?php echo $index === 0 ? 'active' : ''; ?>" 
                         data-id="<?php echo $c['id']; ?>" 
                         data-name="<?php echo htmlspecialchars($c['full_name']); ?>"
                         data-pic="<?php echo htmlspecialchars($user_pic); ?>">
                        
                        <img src="uploads/<?php echo htmlspecialchars($user_pic); ?>" 
                             onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';" 
                             class="contact-avatar shadow-sm" 
                             alt="User">
                        
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-dark text-truncate" style="font-size:0.95rem;"><?php echo htmlspecialchars($c['full_name']); ?></span>
                            </div>
                            <small class="text-muted d-block text-truncate" style="font-size:0.82rem;"><?php echo htmlspecialchars($c['subtext']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- MAIN CHAT PANEL -->
        <div class="col-md-8 col-lg-9 p-0">
            <div class="chat-area">
                <!-- Chat Window Header -->
                <div class="p-2 border-bottom d-flex align-items-center gap-3" style="background:#f0f2f5; min-height:59px;">
                    <img id="activeChatPic" src="" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" alt="">
                    <div>
                        <span id="activeChatName" class="d-block fw-semibold text-dark" style="font-size:0.95rem;">Loading chat...</span>
                        <small class="text-muted small" style="font-size: 0.72rem;">online</small>
                    </div>
                </div>

                <!-- Chat Box Messages (WhatsApp Styled) -->
                <div class="chat-messages" id="chatBox"></div>

                <!-- Chat Window Footer Input -->
                <div class="chat-footer">
                    <form id="chatForm" class="d-flex align-items-center gap-2">
                        <input type="hidden" id="targetId" name="target_id">
                        
                        <!-- Extra icons for accurate WA Web look -->
                        <button type="button" class="btn text-secondary p-1"><i class="fa-regular fa-face-smile fs-4"></i></button>
                        <button type="button" class="btn text-secondary p-1 me-1"><i class="fa-solid fa-plus fs-4"></i></button>
                        
                        <input type="text" id="messageInput" name="message" class="form-control input-bar-custom flex-grow-1" placeholder="Type a message" required autocomplete="off">
                        
                        <button type="submit" class="btn btn-send-wa px-2"><i class="fa-solid fa-paper-plane"></i></button>
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
    let activeContact = $('.contact-box.active');
    let targetId = activeContact.data('id');
    if(!targetId) {
        $('#activeChatName').text("No chat selected");
        $('#activeChatPic').hide();
        return;
    }

    $('#targetId').val(targetId);
    $('#activeChatName').text(activeContact.data('name'));
    
    let picUrl = activeContact.data('pic');
    $('#activeChatPic').attr('src', 'uploads/' + picUrl).show();

    $.get('?url=fetch_messages&target_id=' + targetId, function(res) {
        let html = '';
        if(res.length === 0) {
            html = `<div class="text-center text-muted my-auto mx-auto px-3 py-2 rounded-3 small shadow-sm" style="background:#fffbdf; max-width:300px; font-size:0.82rem;">
                        <i class="fa-solid fa-lock me-1" style="font-size:0.75rem;"></i> Messages are end-to-end secured. No one outside of this chat can read them.
                    </div>`;
        } else {
            res.forEach(m => {
                let isMe = (m.sender_type === currentRole);
                let rowClass = isMe ? 'row-sent' : 'row-received';
                let bubbleClass = isMe ? 'msg-sent' : 'msg-received';
                
                // Haddii fariinta adiga lagaa soo diray, tusi Double Blue Ticks (✓✓) sida WhatsAppka
                let ticks = isMe ? '<span class="msg-ticks"><i class="fa-solid fa-check-double"></i></span>' : '';
                let timeFormatted = m.sent_at.substring(11, 16); // Soo reeb saacada iyo daqiiqada kaliya

                html += `<div class="msg-row ${rowClass}">
                            <div class="msg-bubble ${bubbleClass}">
                                <span>${m.message}</span>
                                <div class="msg-meta">
                                    ${timeFormatted} ${ticks}
                                </div>
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
            Swal.fire({ icon: 'error', title: 'Cilad', text: res.msg });
        }s
    });
});

$(document).ready(function() {
    loadMessages();
    setInterval(loadMessages, 3000); // 3-dii ilbiriqsiba mar soo cusboonaysii fariimaha
});
</script>
</body>
</html>