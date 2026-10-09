<?php
// 1. START SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. CHECK IF USER IS LOGGED IN AND IS A PATIENT
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Patient') {
    // Haddii uusan ahayn Patient, u dhiib bogga login-ka
    header("Location: sign_in.php");
    exit();
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ===== NAVY & ORANGE GRADIENT NAVBAR ===== */
.navbar {
    /* Background-ka weyn oo ah Dark Navy Blue Gradient */
    background: linear-gradient(135deg, #0a192f 0%, #172a45 100%);
    box-shadow: 0 6px 18px rgba(0,0,0,0.3);
    border-bottom: 1px solid rgba(255, 138, 0, 0.2);
}

/* BRAND */
.navbar-brand {
    color: #fff !important;
    font-weight: 800;
}

/* Habeeb text gradient iyo barta orange-ka ah */
.navbar-brand span.brand-habeeb {
    background: linear-gradient(45deg, #ff6b00, #ff9e00);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.navbar-brand span.brand-dot {
    color: #ff8a00 !important;
    -webkit-text-fill-color: initial;
}

/* NAV LINKS */
.navbar-nav {
    align-items: center;
    gap: 6px;
}                             

.nav-link {
    color: rgba(255, 255, 255, 0.8) !important;
    padding: 0.6rem 1rem !important;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: 0.3s;
    font-weight: 500;
}

.nav-link i {
    color: #ff9e00; /* Astaamaha oo laga dhigay Orange */
}

.nav-link:hover {
    background: rgba(255, 138, 0, 0.15);
    color: #fff !important;
    transform: translateY(-2px);
}

/* ACTIVE LINK */
.active-link {
    background: linear-gradient(45deg, #ff6b00, #ff9e00);
    color: #fff !important;
    font-weight: 700;
}

.active-link i {
    color: #fff;
}

/* PROFILE */
.profile-wrapper {
    display: flex;
    align-items: center;
    margin-left: 20px;
    cursor: pointer;
}

.profile-img {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ff9e00; /* Profile border loo sameeyay orange */
    box-shadow: 0 3px 10px rgba(255, 138, 0, 0.2);
}

/* DROPDOWN */
.dropdown-menu {
    background: #172a45;
    border-radius: 18px;
    min-width: 220px;
    border: 1px solid rgba(255, 138, 0, 0.2);
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
}

.dropdown-item {
    color: rgba(255, 255, 255, 0.8) !important;
    border-radius: 10px;
    padding: 0.6rem 1rem;
    transition: 0.2s;
}

.dropdown-item:hover {
    background: rgba(255, 138, 0, 0.15);
    color: #ff9e00 !important;
}

/* LOGOUT TEXT */
.text-danger {
    color: #ff4d4d !important;
}
.dropdown-item.text-danger:hover {
    background: rgba(255, 77, 77, 0.1) !important;
    color: #ff4d4d !important;
}

.text-orange-custom {
    color: #ff9e00 !important;
}
</style>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">

        <!-- Magaca Cusub: Habeeb Psychiatric Hospital -->
        <a class="navbar-brand fs-4" href="#">
            <i class="fa-solid fa-house-medical text-orange-custom me-2"></i>
            <span class="brand-habeeb">Habeeb</span> Hospital<span class="brand-dot">.</span>
        </a>

        <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto fw-medium">
                <li class="nav-item">
                    <a class="nav-link active-link" href="home_patient.php" target="fr">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./forms/appoint.php" target="fr">
                        <i class="fa-solid fa-user-doctor"></i> Doctors Info
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/balan.php" target="fr">
                        <i class="fa-solid fa-calendar-check"></i> Booking at hospital
                    </a>
                </li>

                 <li class="nav-item">
                    <a class="nav-link" href="./forms_patients/my_bills.php" target="fr">
                        <i class="fa-solid fa-credit-card"></i> Finance
                    </a>
                </li>

                 <li class="nav-item">
                    <a class="nav-link" href="./forms_patients/emergency_requests.php" target="fr">
                        <i class="fa-solid fa-kit-medical"></i> Emergency
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/doctor_message.php" target="fr">
                        <i class="fa-solid fa-envelope"></i> Message
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/video_sessions.php" target="fr">
                        <i class="fa-solid fa-video"></i> Booking at Video Call
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/notifications.php" target="fr">
                        <i class="fa-solid fa-bell"></i> Video call 
                    </a>
                </li>
            </ul>

            <div class="dropdown profile-wrapper">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" data-bs-toggle="dropdown">
                    
                    <img src="./forms/uploads/<?php echo !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.png'; ?>"
                         class="profile-img" id="navbar-profile-img">

                    <div class="ms-2 d-none d-sm-block text-start">
                        <div class="fw-bold small" id="navbar-user-name">
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </div>
                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem; background-color: #ff9e00 !important;">
                            <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Patient'); ?>
                        </span>
                    </div>
                </a>

                <ul class="dropdown-menu dropdown-menu-end p-2 mt-2">
                    <li>
                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fa-solid fa-user me-2 text-orange-custom"></i> My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#settingsModal">
                            <i class="fa-solid fa-gear me-2 text-orange-custom"></i> Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider" style="background-color: rgba(255,138,0,0.2);"></li>
                    <li>
                        <a class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- LOGOUT MODAL -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px; background: #172a45; color: #fff;">
            <div class="modal-header border-0">
                <h5 class="fw-bold text-white">Confirm Logout</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fa-solid fa-circle-question text-orange-custom mb-3" style="font-size:50px;"></i>
                <p class="fs-5 text-white-50">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">No, Stay</button>
                <a href="sign_in.php" class="btn btn-danger px-4 rounded-3">Yes, Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- PROFILE MODAL -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #0a192f;">
            <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #ff6b00 0%, #ff9e00 100%);">
                <h5 class="modal-title fw-bold" id="profileModalLabel">
                    <i class="fa-solid fa-user-gear me-2"></i> My Profile Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe src="./profile.php" width="100%" height="680" style="border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- SETTINGS MODAL -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #0a192f;">
            <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #ff6b00 0%, #ff9e00 100%);">
                <h5 class="modal-title fw-bold" id="settingsModalLabel">
                     <i class="fa-solid fa-gear me-2"></i> My Settings
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe src="./settings_patients.php" width="100%" height="680" style="border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'USER_PROFILE_UPDATED') {
        const timestamp = new Date().getTime();
        
        const nameEl = document.getElementById('navbar-user-name');
        if (nameEl) nameEl.textContent = event.data.full_name;
        
        const imgEl = document.getElementById('navbar-profile-img');
        if (imgEl) imgEl.src = `./forms/uploads/${event.data.profile_pic}?t=${timestamp}`;
    }
});
</script>