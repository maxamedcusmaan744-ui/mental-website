<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_name = 'mental_health_support';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
        exit();
    } else {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
}

// Session security check
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current values
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    die('Error fetching data: ' . htmlspecialchars($e->getMessage()));
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    $full_name    = trim(htmlspecialchars($_POST['full_name'] ?? ''));
    $email        = trim(filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL));
    $phone        = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $gender       = trim(htmlspecialchars($_POST['gender'] ?? ''));
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    $current_pic  = $user['profile_pic'] ?? 'default.png';
    $new_pic      = $current_pic;

    // Server-side validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($gender)) {
        echo json_encode(['status' => 'error', 'message' => 'All basic fields are required.']);
        exit();
    }

    // Check if email is already taken by another user
    $email_check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $email_check->execute([$email, $user_id]);
    if ($email_check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'This email address is already in use.']);
        exit();
    }

    // Password Update Logic
    $updating_password = false;
    if (!empty($current_pass) || !empty($new_pass) || !empty($confirm_pass)) {
        
        if ($current_pass !== $user['password']) {
            echo json_encode(['status' => 'error', 'message' => 'Old password-ka aad gelisay waa khalad.']);
            exit();
        }
        
        if (empty($new_pass)) {
            echo json_encode(['status' => 'error', 'message' => 'Fadlan qor password-ka cusub.']);
            exit();
        }
        
        if ($new_pass !== $confirm_pass) {
            echo json_encode(['status' => 'error', 'message' => 'Password-ka cusub iyo confirm-kiisa isma laha.']);
            exit();
        }
        
        if (strlen($new_pass) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password-ka cusub waa inuu ka koobnaadaa ugu yaraan 6 xaraf.']);
            exit();
        }
        
        $updating_password = true;
    }

    // Image Upload Logic
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "forms/uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (in_array($file_extension, $allowed_types)) {
            $file_name = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                if (!empty($current_pic) && $current_pic != 'default.png' && $current_pic != 'default.jpg') {
                    $old_file = $target_dir . $current_pic;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $new_pic = $file_name;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded picture.']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG, and GIF images are allowed.']);
            exit();
        }
    }

    try {
        if ($updating_password) {
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, gender = ?, password = ?, profile_pic = ? WHERE user_id = ?");
            $update_stmt->execute([$full_name, $email, $phone, $gender, $new_pass, $new_pic, $user_id]);
        } else {
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, gender = ?, profile_pic = ? WHERE user_id = ?");
            $update_stmt->execute([$full_name, $email, $phone, $gender, $new_pic, $user_id]);
        }

        // Key for old name comparison before session rewrite
        $old_name_backup = $_SESSION['full_name'] ?? $user['full_name'];

        $_SESSION['full_name'] = $full_name;
        $_SESSION['profile_pic'] = $new_pic;

        echo json_encode([
            'status' => 'success',
            'message' => 'Account settings updated successfully!',
            'full_name' => $full_name,
            'old_name' => $old_name_backup,
            'profile_pic' => $new_pic
        ]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database update error: ' . $e->getMessage()]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --primary-cyan: #00bcd4;
        --dark-cyan: #00838f;
        --gradient-cyan: linear-gradient(135deg, #00e5ff, #0097a7);
        
        /* LIGHT THEME VARIABLES */
        --body-bg: #f0fafa;
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-sub: #64748b;
        --input-bg: #ffffff;
        --border-color: #e0f7f9;
    }

    [data-bs-theme="dark"] {
        /* DARK THEME VARIABLES */
        --body-bg: #0f172a;
        --card-bg: #1e293b;
        --text-main: #f8fafc;
        --text-sub: #94a3b8;
        --input-bg: #334155;
        --border-color: #334155;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: transparent; 
        color: var(--text-main);
        overflow-x: hidden;
        padding: 12px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .settings-container {
        background: var(--card-bg);
        border-radius: 24px;
        padding: 32px 24px;
        box-shadow: 0 10px 30px -5px rgba(0, 188, 212, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        border: 1px solid var(--border-color);
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    .form-control-custom, .form-select-custom {
        background-color: var(--input-bg) !important;
        border: 1.5px solid var(--border-color);
        border-radius: 14px;
        color: var(--text-main) !important;
        padding: 12px 16px;
        font-weight: 500;
        transition: all 0.25s ease-in-out;
    }

    .form-control-custom:focus, .form-select-custom:focus {
        background-color: var(--card-bg) !important;
        border-color: var(--primary-cyan);
        box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.15);
        color: var(--text-main) !important;
        outline: none;
    }

    .form-control-custom::placeholder {
        color: var(--text-sub);
        opacity: 0.7;
    }

    .custom-label {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: var(--text-sub);
        margin-bottom: 8px;
        display: block;
        text-transform: uppercase;
    }

    .btn-cyan-gradient {
        background: var(--gradient-cyan);
        border: none;
        color: white;
        font-weight: 700;
        letter-spacing: 0.3px;
        border-radius: 14px;
        padding: 14px 24px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 6px 20px rgba(0, 188, 212, 0.25);
    }

    .btn-cyan-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(0, 188, 212, 0.4);
        color: #ffffff;
        opacity: 0.95;
    }

    .avatar-wrapper {
        position: relative;
        display: inline-block;
        padding: 4px;
        margin-bottom: 16px;
    }
    
    .avatar-ring {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        border-radius: 50%;
        background: var(--gradient-cyan);
        z-index: 1;
    }
    
    .profile-preview {
        object-fit: cover;
        z-index: 2;
        position: relative;
        border: 4px solid var(--card-bg) !important;
        transition: border-color 0.3s ease;
    }
    
    .theme-toggle-btn {
        cursor: pointer;
        font-size: 1.4rem;
        color: var(--primary-cyan);
        border: none;
        background: transparent;
    }
    </style>
    <script>
    (function () {
        const savedTheme = localStorage.getItem('app-theme') || 
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
    </script>
</head>
<body>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            
            <div class="settings-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0"><i class="bi bi-gear-fill me-2" style="color: var(--primary-cyan);"></i>Account Settings</h3>
                    <button type="button" class="theme-toggle-btn" id="themeToggler" title="Toggle Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                </div>
                <hr class="mb-4" style="opacity: 0.1;">

                <form id="settingsForm" enctype="multipart/form-data">
                    
                    <div class="text-center mb-4">
                        <div class="avatar-wrapper">
                            <div class="avatar-ring"></div>
                            <img src="forms/uploads/<?php echo !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.png'; ?>"
                                 class="rounded-circle profile-preview"
                                 id="profilePreview"
                                 width="110" height="110"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=00bcd4&color=fff&bold=true';">
                        </div>
                        <div class="mx-auto" style="max-width: 300px;">
                            <label for="profile_pic" class="custom-label">Change Profile Picture</label>
                            <input type="file" class="form-control form-control-custom" id="profile_pic" name="profile_pic" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3" style="color: var(--primary-cyan);"><i class="bi bi-person-badge me-1"></i> Personal Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="custom-label">Full Name</label>
                            <input type="text" class="form-control form-control-custom" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="custom-label">Email Address</label>
                            <input type="email" class="form-control form-control-custom" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="custom-label">Phone Number</label>
                            <input type="tel" class="form-control form-control-custom" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="gender" class="custom-label">Gender</label>
                            <select class="form-select form-select-custom" id="gender" name="gender" required>
                                <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                <option value="Prefer not to say" <?php echo ($user['gender'] === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3" style="color: var(--primary-cyan);"><i class="bi bi-shield-lock me-1"></i> Security Update <small class="text-muted fs-6 fw-normal">(Leave blank to keep current)</small></h5>
                    <div class="row mb-2">
                        <div class="col-md-12 mb-3">
                            <label for="current_password" class="custom-label">Current (Old) Password</label>
                            <input type="password" class="form-control form-control-custom" id="current_password" name="current_password" placeholder="Enter Old Password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="custom-label">New Password</label>
                            <input type="password" class="form-control form-control-custom" id="new_password" name="new_password" placeholder="Enter New Password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="custom-label">Confirm New Password</label>
                            <input type="password" class="form-control form-control-custom" id="confirm_password" name="confirm_password" placeholder="Repeat New Password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-cyan-gradient px-5" id="btnSave">
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            <span id="btnText"><i class="bi bi-save me-1"></i> Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const themeToggler = document.getElementById('themeToggler');
const themeIcon = document.getElementById('themeIcon');
const htmlTag = document.documentElement;

const storedTheme = localStorage.getItem('app-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
setTheme(storedTheme);

themeToggler.addEventListener('click', () => {
    const currentTheme = htmlTag.getAttribute('data-bs-theme');
    const targetTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(targetTheme);
    if (window.parent) {
        window.parent.postMessage({ type: 'THEME_CHANGED', theme: targetTheme }, '*');
    }
});

function setTheme(theme) {
    htmlTag.setAttribute('data-bs-theme', theme);
    localStorage.setItem('app-theme', theme);
    if (theme === 'dark') {
        themeIcon.className = "bi bi-sun-fill text-warning";
    } else {
        themeIcon.className = "bi bi-moon-stars-fill text-info";
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const btnSave = document.getElementById('btnSave');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    
    btnSave.disabled = true;
    btnSpinner.classList.remove('d-none');
    btnText.textContent = "Saving changes...";

    const formData = new FormData(form);

    fetch('./settings.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        btnSave.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = '<i class="bi bi-save me-1"></i> Save Changes';
        
        const isDark = htmlTag.getAttribute('data-bs-theme') === 'dark';
        const modalBg = isDark ? '#1e293b' : '#ffffff';
        const modalColor = isDark ? '#f8fafc' : '#0f172a';
        
        if (data.status === 'success') {
            Swal.fire({
                title: 'Changes Saved!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#00bcd4',
                background: modalBg,
                color: modalColor,
                timer: 2500,
                timerProgressBar: true,
                customClass: { popup: 'rounded-4 border-0 shadow-lg' }
            });

            document.getElementById('current_password').value = "";
            document.getElementById('new_password').value = "";
            document.getElementById('confirm_password').value = "";

            const timestamp = new Date().getTime();
            document.getElementById('profilePreview').src = `forms/uploads/${data.profile_pic}?t=${timestamp}`;

            // === HELAANKA BOGGA SARE (PARENT WINDOW/HEADER) ===
            if (window.parent) {
                const parentDoc = window.parent.document;
                
                // 1. BEDDELISTA MAGACA (Habka koowaad: Raadin ID gaar ah)
                // TALO: Waxaad tagtaa Header-kaaga weyn, meesha magaca uu ku qoranyahay u sii ID-gan: id="header-user-name"
                let targetNameNode = parentDoc.getElementById('header-user-name');
                
                if (targetNameNode) {
                    targetNameNode.textContent = data.full_name;
                } else {
                    // Habka labaad: Raadin Class-yada caanka ah ee Bootstrap ama Custom Admin Panels
                    const commonClasses = [
                        '.user-name', '.profile-name', '.nav-user-name', 
                        '.fw-bold.small.text-dark', '.hdr-title', '.dropdown-toggle'
                    ];
                    
                    for (let selector of commonClasses) {
                        let elements = parentDoc.querySelectorAll(selector);
                        elements.forEach(el => {
                            // Haddii qoraalka dhexda ku jira uu la mid yahay magacaagii hore, durbaba ku beddel kan cusub
                            if (el.textContent.trim() === data.old_name.trim() || el.textContent.includes(data.old_name.trim())) {
                                el.textContent = data.full_name;
                            }
                        });
                    }
                }
                
                // 2. BEDDELISTA SAWIRKA PROFILE-KA EE HEADER-KA
                const avatarSelectors = [
                    '#header-user-avatar', '.avatar-img', '.user-img', 
                    '.profile-nav-img', '.rounded-circle.me-2'
                ];
                
                for (let selector of avatarSelectors) {
                    let avatars = parentDoc.querySelectorAll(selector);
                    avatars.forEach(img => {
                        if (img.tagName === 'IMG') {
                            img.src = `./forms/uploads/${data.profile_pic}?t=${timestamp}`;
                        }
                    });
                }
                
                // 3. Broadcast update haddii Header-ku leeyahay Event Listener firfircoon
                window.parent.postMessage({
                    type: 'USER_PROFILE_UPDATED',
                    full_name: data.full_name,
                    profile_pic: data.profile_pic
                }, '*');
            }
        } else {
            Swal.fire({
                title: 'Error Encountered',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#00bcd4',
                background: modalBg,
                color: modalColor,
                customClass: { popup: 'rounded-4 border-0 shadow-lg' }
            });
        }
    })
    .catch(error => {
        btnSave.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = '<i class="bi bi-save me-1"></i> Save Changes';
        
        const isDark = htmlTag.getAttribute('data-bs-theme') === 'dark';
        Swal.fire({
            title: 'Network Issue',
            text: 'Could not communicate safely with your server script.',
            icon: 'warning',
            confirmButtonColor: '#00bcd4',
            background: isDark ? '#1e293b' : '#ffffff',
            color: isDark ? '#f8fafc' : '#0f172a',
            customClass: { popup: 'rounded-4 border-0 shadow-lg' }
        });
        console.error(error);
    });
});

window.addEventListener('storage', function (event) {
    if (event.key === 'app-theme') {
        setTheme(event.newValue || 'light');
    }
});

window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'THEME_CHANGED') {
        setTheme(event.data.theme);
    }
});
</script>
</body>
</html>