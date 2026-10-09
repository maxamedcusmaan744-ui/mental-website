<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* NIDAAMKA CADDAANKA (LIGHT MODE) */
    :root {
        /* Midabada rasmiga ah ee Habeeb Hospital */
        --navy-gradient: linear-gradient(135deg, #1b2a4a 0%, #0d1527 100%);
        --orange-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        --profile-size: 48px;
        --hdr-text-main: #1e293b; /* Qoraalka weyn */
        --hdr-text-sub: #64748b;  /* Qoraalka yar */
        --avatar-border: #ffffff; /* Xadka sawirka */
        --modal-bg: #ffffff;      /* Gadaasha modalka */
    }

    /* NIDAAMKA MADOWGA (DARK MODE) */
    [data-bs-theme="dark"] {
        --hdr-text-main: #f9fafb; /* Qoraal caddaan ah */
        --hdr-text-sub: #9ca3af;  /* Qoraal cirro khafiif ah */
        --avatar-border: #1f2937; /* Xad la dhex-gala madowga */
        --modal-bg: #111827;      /* Gadaasha modalka madow */
    }

    /* 1. The Gradient Ring Container (Orange Accent) */
    .avatar-container {
        position: relative;
        width: var(--profile-size);
        height: var(--profile-size);
        padding: 3px;
        background: var(--orange-gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .avatar-container:hover {
        transform: scale(1.08) rotate(5deg);
        box-shadow: 0 6px 16px rgba(249, 115, 22, 0.5);
    }

    /* 2. The Actual Image */
    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid var(--avatar-border);
        background-color: var(--avatar-border);
        transition: border-color 0.3s ease, background-color 0.3s ease;
    }

    /* 3. The Live Status Dot */
    .status-dot {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background-color: #22c55e;
        border: 2px solid var(--avatar-border);
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: border-color 0.3s ease;
    }

    /* Hospital Branding Style Changes */
    .hdr-title { color: var(--hdr-text-main) !important; transition: color 0.3s ease; }
    .hdr-desc { color: var(--hdr-text-sub) !important; transition: color 0.3s ease; }
    .modal-content { background-color: var(--modal-bg) !important; transition: background-color 0.3s ease; }
    
    /* Custom Badge styled with Hospital Colors */
    .hospital-badge {
        background: rgba(249, 115, 22, 0.1);
        color: #ea580c !important;
        font-size: 0.65rem;
    }
    [data-bs-theme="dark"] .hospital-badge {
        background: rgba(249, 115, 22, 0.2);
        color: #ff985e !important;
    }
</style>

<!-- SCRIPT-KAN WUXUU KA HORTAGAYAA IN BOGGU CADDAAN AHAN U DHALANDHABO (ANTI-FLICKER) -->
<script>
    (function () {
        const savedTheme = localStorage.getItem('app-theme') || 
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
</script>

<header class="d-flex flex-wrap justify-content-between align-items-center mb-5">
    <!-- LEFT -->
    <div class="d-flex align-items-center gap-3">
        <!-- Hospital Logo Branding -->
        <img src="./forms/uploads/images.jpg" alt="Habeeb Hospital Logo" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px;">
        <div>
            <h4 class="fw-bold mb-0 hdr-title">Good morning, Eng. <?php echo $_SESSION['full_name']; ?> 👋</h4>
            <p class="hdr-desc small mb-0">Habeeb Psychiatric Hospital Management System</p>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
        <!-- Notifications -->
        <button class="btn btn-light rounded-4 p-2 px-3 border shadow-sm position-relative">
            <i class="bi bi-bell-fill text-muted"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        </button>

        <!-- Profile Dropdown -->
        <div class="dropdown d-flex align-items-center gap-2 border-start ps-3" style="border-color: var(--bs-border-color) !important;">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <!-- THE BEAUTIFUL PROFILE DESIGN WITH ORANGE GRADIENT -->
                <div class="avatar-container">
                    <img src="./forms/uploads/<?php echo !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.png'; ?>" 
                         class="avatar-img"
                         alt="User Profile"
                         id="header-user-avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=1b2a4a&color=fff&bold=true';">
                    <span class="status-dot"></span>
                </div>

                <div class="d-none d-sm-block ms-2 text-start">
                    <p class="mb-0 fw-bold small hdr-title" id="header-user-name">Eng. <?php echo $_SESSION['full_name']; ?></p>
                    <span class="badge hospital-badge">
                        <?php echo $_SESSION['user_type']; ?>
                    </span>
                </div>
            </a>

            <!-- Dropdown Menu -->
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2">
                <li><h6 class="dropdown-header">Account Settings</h6></li>
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="bi bi-person me-2"></i> My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- LOGOUT MODAL -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold hdr-title">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-exclamation-circle text-warning mb-3" style="font-size: 3rem;"></i>
                <p class="hdr-desc">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <a href="sign_in.php" class="btn btn-danger px-4">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- PROFILE IFRAME MODAL (Header background changed to Navy Gradient) -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
            <div class="modal-header border-0 text-white" style="background: var(--navy-gradient);">
                <h5 class="modal-title fw-bold" id="profileModalLabel">
                    <i class="bi bi-person-circle me-2"></i> My Profile Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe src="./profile.php" width="100%" height="680" style="border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- SETTINGS IFRAME MODAL (Header background changed to Navy Gradient) -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden;">
            <div class="modal-header border-0 text-white" style="background: var(--navy-gradient);">
                <h5 class="modal-title fw-bold" id="settingsModalLabel">
                    <i class="bi bi-gear me-2"></i> My Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe src="./settings.php" width="100%" height="680" style="border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT-KA DAREEMAYA ISBEDDELKA DARK MODE-KA EE KAGA IMAANAYA SETTINGS.PHP -->
<script>
    window.addEventListener('storage', function (event) {
        if (event.key === 'app-theme') {
            const newTheme = event.newValue || 'light';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
        }
    });

    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'THEME_CHANGED') {
            document.documentElement.setAttribute('data-bs-theme', event.data.theme);
        }
    });
</script>