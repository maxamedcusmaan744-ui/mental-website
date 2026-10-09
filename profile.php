<?php
session_start();

// Database configuration - Update these details to match your database server setup
$db_host = 'localhost';
$db_name = 'mental_health_support';
$db_user = 'root';
$db_pass = '';

try {
    // Establish secure PDO connection with security attributes configured
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Return connection error cleanly during AJAX submit, or stop script gracefully on page load
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit();
    } else {
        die('Database connection failed. Please verify configurations: ' . htmlspecialchars($e->getMessage()));
    }
}

// We assume standard session stores user_id upon login. 
// If session is empty or missing user_id, redirect to index/login.
if (!isset($_SESSION['user_id'])) {
    // Fallback: If your authentication logic currently sets only 'full_name' instead of 'user_id',
    // we query using full_name to fetch the user_id dynamically as a fallback logic.
    if (isset($_SESSION['full_name'])) {
        $init_stmt = $pdo->prepare("SELECT user_id, user_type, profile_pic FROM users WHERE full_name = ? LIMIT 1");
        $init_stmt->execute([$_SESSION['full_name']]);
        $fallback_user = $init_stmt->fetch();
        if ($fallback_user) {
            $_SESSION['user_id'] = $fallback_user['user_id'];
        } else {
            header('Location: index.php');
            exit();
        }
    } else {
        header('Location: index.php');
        exit();
    }
}

$user_id = $_SESSION['user_id'];

// Fetch current user details from DB to ensure most recent records are rendered
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: index.php');
        exit();
    }
    // Sync current database values into session to keep navigation templates up-to-date
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['profile_pic'] = $user['profile_pic'];
    $_SESSION['user_type'] = $user['user_type'];
} catch (PDOException $e) {
    die('Error fetching user profile data: ' . htmlspecialchars($e->getMessage()));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    $new_name = trim(htmlspecialchars($_POST['full_name'] ?? ''));
    $current_pic = $user['profile_pic'] ?? 'default.jpg';
    $new_pic = $current_pic; // Keep existing profile picture by default

    // Validate if the input name is blank
    if (empty($new_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Name field cannot be left blank.']);
        exit();
    }

    // Process new picture upload if provided
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "forms/uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
        // Use time combined with unique ID to avoid potential collisions
        $file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        $allowed_types = array("jpg", "jpeg", "png", "gif");
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                // Delete existing custom profile picture if it's not the default placeholder
                if (!empty($current_pic) && $current_pic != 'default.png' && $current_pic != 'default.jpg') {
                    $old_file = $target_dir . $current_pic;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $new_pic = $file_name;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'An error occurred while uploading your profile picture file.']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Only JPG, JPEG, PNG & GIF formats are permitted.']);
            exit();
        }
    }

    try {
        // Execute dynamic secure parameters query inside database table
        $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, profile_pic = ? WHERE user_id = ?");
        $update_stmt->execute([$new_name, $new_pic, $user_id]);

        // Keep local Session updated dynamically for instant application changes
        $_SESSION['full_name'] = $new_name;
        $_SESSION['profile_pic'] = $new_pic;

        echo json_encode([
            'status' => 'success',
            'message' => 'Your profile details have been successfully updated!',
            'full_name' => $new_name,
            'profile_pic' => $new_pic
        ]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save updates to the database: ' . $e->getMessage()]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management</title>
    
    <!-- Bootstrap 5 & Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* NIDAAMKA midabyada (LIGHT & DARK VARIABLE CORES) */
        :root {
            --bg-body: transparent;
            --bg-container: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --avatar-border: #ffffff;
            --border-container: rgba(0, 180, 216, 0.1);
            --shadow-container: 0 10px 30px -5px rgba(0, 180, 216, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
        }

        [data-bs-theme="dark"] {
            --bg-body: transparent;
            --bg-container: #111827; /* Gadaal madow jilicsan (Gray 900) */
            --text-main: #f9fafb;     /* Qoraal caddaan ah */
            --text-muted: #9ca3af;    /* Qoraal cirro ah */
            --input-bg: #1f2937;      /* Sanduuq madow ah (Gray 800) */
            --input-border: #374151;  /* Xad madow ah */
            --avatar-border: #111827; 
            --border-container: rgba(0, 180, 216, 0.2);
            --shadow-container: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body); 
            color: var(--text-main);
            overflow-x: hidden;
            padding: 12px;
            transition: color 0.3s ease;
        }

        /* Seamless premium container framing */
        .profile-container {
            background: var(--bg-container);
            border-radius: 24px;
            padding: 32px 24px;
            box-shadow: var(--shadow-container);
            border: 1px solid var(--border-container);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* Avatar styling with breathing gradient ring */
        .avatar-container {
            position: relative;
            display: inline-block;
            padding: 6px;
        }

        .avatar-gloring {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, #00f5ff, #0077b6, #00b4d8);
            background-size: 200% 200%;
            z-index: 1;
            animation: gradientShift 4s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; transform: rotate(0deg); }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; transform: rotate(360deg); }
        }

        .profile-img-glow {
            object-fit: cover; 
            z-index: 2; 
            position: relative;
            box-shadow: 0 8px 20px rgba(0, 119, 182, 0.2);
            border: 4px solid var(--avatar-border) !important;
            transition: border-color 0.3s ease;
        }

        /* Modern text input fields */
        .form-control-custom {
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 14px;
            color: var(--text-main);
            padding: 14px 18px;
            font-weight: 500;
            transition: all 0.25s ease-in-out;
        }

        .form-control-custom:focus {
            background: var(--bg-container);
            border-color: #00b4d8;
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.12);
            color: var(--text-main);
            outline: none;
        }

        /* Styled custom file uploader container */
        .file-upload-wrapper {
            position: relative;
            width: 100%;
        }

        .file-upload-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #00b4d8;
            font-size: 1.2rem;
            pointer-events: none;
            z-index: 5;
        }

        .form-control-custom[type="file"] {
            padding-left: 48px;
            cursor: pointer;
        }

        .form-control-custom[type="file"]::file-selector-button {
            background: linear-gradient(135deg, rgba(0, 180, 216, 0.1), rgba(0, 119, 182, 0.1));
            color: #0077b6;
            border: none;
            border-radius: 8px;
            padding: 4px 12px;
            font-weight: 600;
            margin-right: 12px;
            transition: all 0.2s ease;
        }

        [data-bs-theme="dark"] .form-control-custom[type="file"]::file-selector-button {
            background: rgba(0, 180, 216, 0.2);
            color: #00cbd6;
        }

        .form-control-custom[type="file"]:hover::file-selector-button {
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: #ffffff;
        }

        .custom-label {
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            transition: color 0.3s ease;
        }

        /* High contrast deep Cyan CTA action button */
        .btn-cyan-gradient {
            background: linear-gradient(135deg, #00cbd6 0%, #0077b6 100%);
            border: none;
            color: white;
            font-weight: 700;
            letter-spacing: 0.3px;
            border-radius: 14px;
            padding: 14px 24px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 6px 20px rgba(0, 180, 216, 0.25);
        }

        .btn-cyan-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0, 180, 216, 0.4);
            color: #ffffff;
            opacity: 0.95;
        }

        .btn-cyan-gradient:active {
            transform: translateY(1px);
        }

        /* Clean customized User Type Badge */
        .user-badge {
            background: linear-gradient(135deg, rgba(0, 180, 216, 0.08), rgba(0, 119, 182, 0.08));
            color: #0077b6;
            font-weight: 700;
            border: 1px solid rgba(0, 180, 216, 0.15);
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        [data-bs-theme="dark"] .user-badge {
            background: rgba(0, 180, 216, 0.15);
            color: #00cbd6;
            border-color: rgba(0, 180, 216, 0.3);
        }
    </style>
    
    <!-- ANTI-FLICKER SCRIPT: Wuxuu maqaarka saxda ah saarayaa bogga ka hor intaan koodhku dhalan -->
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
        <div class="col-100 col-md-8 col-lg-6">
            
            <div class="profile-container">
                <form id="profileForm" enctype="multipart/form-data">
                    
                    <!-- AVATAR RENDER & PREVIEW -->
                    <div class="text-center mb-4">
                        <div class="avatar-container">
                            <div class="avatar-gloring"></div>
                            <img src="forms/uploads/<?php echo !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.png'; ?>"
                                 class="rounded-circle profile-img-glow"
                                 id="profilePreview"
                                 width="115"
                                 height="115"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name'] ?? 'User'); ?>&background=00b4d8&color=fff&bold=true';">
                        </div>
                        
                        <div class="mt-3">
                            <span class="badge rounded-pill user-badge px-3 py-2">
                                <i class="bi bi-shield-check me-1"></i> <?php echo htmlspecialchars($user['user_type'] ?? 'User'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- INPUT: PROFILE IMAGE -->
                    <div class="mb-3">
                        <label for="profile_pic" class="custom-label">Profile Image</label>
                        <div class="file-upload-wrapper">
                            <i class="bi bi-image file-upload-icon"></i>
                            <input type="file" class="form-control form-control-custom" id="profile_pic" name="profile_pic" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </div>

                    <!-- INPUT: FULL NAME -->
                    <div class="mb-4">
                        <label for="full_name" class="custom-label">Full Name</label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-custom" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required placeholder="Enter your full name">
                        </div>
                    </div>

                    <!-- ACTION BUTTON -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-cyan-gradient" id="btnSave">
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            <span id="btnText"><i class="bi bi-check2-circle me-1"></i> Save Updates</span>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<!-- SweetAlert2 library and Bootstrap JS dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Instantly update layout preview with selected file
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Bind custom dynamic listener for form submission
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const btnSave = document.getElementById('btnSave');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    
    // Configure loading state for form submit
    btnSave.disabled = true;
    btnSpinner.classList.remove('d-none');
    btnText.textContent = "Updating Profile...";

    const formData = new FormData(form);

    // Communicate asynchronously to backend database via fetch
    fetch('./profile.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Reset button state
        btnSave.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Updates';
        
        // Helitaanka maqaarka hadda jira si loogu gudbiyo SweetAlert
        const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        
        if (data.status === 'success') {
            // Success alert
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#0077b6',
                background: isDarkMode ? '#1f2937' : '#ffffff',
                color: isDarkMode ? '#f9fafb' : '#0f172a',
                timer: 3500,
                timerProgressBar: true,
                customClass: {
                    popup: 'rounded-4 border-0 shadow-lg'
                }
            });
            
            // Instantly cache bust image dynamically
            const newSrc = `./forms/uploads/${data.profile_pic}?t=${new Date().getTime()}`;
            document.getElementById('profilePreview').src = newSrc;
            
            // Update parent window element (The parent dashboard holding the modal frame)
            if (window.parent) {
                // Modified selectors to match flexible text class architectures
                const navName = window.parent.document.querySelector('.hdr-title.small') || window.parent.document.querySelector('.fw-bold.small');
                if (navName) {
                    navName.textContent = data.full_name;
                }
                const navAvatar = window.parent.document.querySelector('.avatar-img');
                if (navAvatar) {
                    navAvatar.src = newSrc;
                }
            }
        } else {
            // Show failure modal
            Swal.fire({
                title: 'Operation Failed',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#00b4d8',
                background: isDarkMode ? '#1f2937' : '#ffffff',
                color: isDarkMode ? '#f9fafb' : '#0f172a',
                customClass: {
                    popup: 'rounded-4 border-0 shadow-lg'
                }
            });
        }
    })
    .catch(error => {
        // Reset loading states on catch error event
        btnSave.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Save Updates';
        
        const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        Swal.fire({
            title: 'Connection error',
            text: 'Could not connect to the profile update script. Check server status.',
            icon: 'warning',
            confirmButtonColor: '#00b4d8',
            background: isDarkMode ? '#1f2937' : '#ffffff',
            color: isDarkMode ? '#f9fafb' : '#0f172a',
            customClass: {
                popup: 'rounded-4 border-0 shadow-lg'
            }
        });
        console.error('Error details:', error);
    });
});

// DHAGAYSTAYAASHA (LISTENERS) CODKA DARK MODE-KA EE KA IMAANAYA BOGGA WEYN (PARENT)
window.addEventListener('storage', function (event) {
    if (event.key === 'app-theme') {
        document.documentElement.setAttribute('data-bs-theme', event.newValue || 'light');
    }
});

window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'THEME_CHANGED') {
        document.documentElement.setAttribute('data-bs-theme', event.data.theme);
    }
});
</script>
</body>
</html>