<?php
// Check if session is started before checking user authorization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Only allow users with user_type 'Doctor'
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Doctor') {
    header('Location: sign_in.php');
    exit();
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ===== DARK NAVY & ORANGE GRADIENT NAVBAR ===== */
.navbar{
    background: linear-gradient(135deg, #1e293b, #0f172a); /* Dark Navy / Slate gradient */
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

/* BRAND */
.navbar-brand{
    color:#fff !important;
    font-weight: 700;
    font-size: 1.35rem !important; /* Slightly smaller logo */
}

.navbar-brand span{
    color:#ff8e53 !important; /* Bright Orange Accent for dot */
}

/* NAV LINKS */
.navbar-nav{
    align-items: center;
    gap: 4px;
}

.nav-link{
    color:#e2e8f0 !important;
    padding: 0.45rem 0.85rem !important; /* Compact padding */
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s ease-in-out;
    font-weight: 400; /* Lighter font weight */
    font-size: 0.88rem; /* Smaller font size */
}

.nav-link i{
    color:#ff8e53; /* Warm Orange Icons */
    font-size: 0.95rem; /* Balanced icon size */
}

.nav-link:hover{
    background: rgba(255, 255, 255, 0.08);
    color: #fff !important;
    transform: translateY(-1px);
}

/* ACTIVE LINK */
.active-link{
    background: linear-gradient(135deg, #ff6b6b, #ff8e53); /* Vibrant Orange Gradient for Active state */
    color: #fff !important;
    font-weight: 600;
}

.active-link i {
    color: #fff !important; /* Force active icon white over orange background */
}

/* PROFILE */
.profile-wrapper{
    display: flex;
    align-items: center;
    margin-left: 15px;
    cursor: pointer;
}

.profile-img{
    width: 36px; /* Smaller avatar */
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ff8e53; /* Orange border matching theme */
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* DROPDOWN */
.dropdown-menu{
    border-radius: 14px;
    min-width: 200px;
    border: none;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    font-size: 0.85rem; /* Smaller dropdown text */
    background-color: #ffffff;
}

.dropdown-item{
    border-radius: 8px;
    padding: 0.5rem 0.8rem;
    transition: 0.2s;
    color: #334155;
}

.dropdown-item:hover{
    background: #fff3eb; /* Soft orange tint on hover */
    color: #e056fd !important; /* Fallback text adjustment */
    color: #ff6b6b !important; 
}

/* TEXT ADJUSTMENTS */
.text-orange-accent {
    color: #ff8e53 !important;
}

.text-danger{
    color: #d32f2f !important;
}

.bg-orange-badge {
    background-color: rgba(255, 142, 83, 0.15) !important;
    color: #ff6b6b !important;
}
</style>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">

         <a class="navbar-brand" href="#">
            <i class="fa-solid fa-house-medical me-2"></i>
            NameHabeeb Psychiatric Hospital<span>.</span>
        </a>

        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active-link" href="home_doctor.php" target="fr">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./forms/doctor_appoint.php" target="fr">
                        <i class="fa-solid fa-calendar-check"></i> Appointments
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/pateint_info.php" target="fr">
                        <i class="fa-solid fa-user-injured"></i> Patients
                    </a>
                </li>

                 <li class="nav-item">
                    <a class="nav-link" href="./forms_doctor/emergency_requests.php" target="fr">
                        <i class="fa-solid fa-kit-medical"></i> Emergency
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/doctor_message.php" target="fr">
                        <i class="fa-solid fa-envelope"></i> Messages
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="./forms/doctor_video.php" target="fr">
                        <i class="fa-solid fa-video"></i> Video Calls
                    </a>
                </li>
            </ul>

            <div class="dropdown profile-wrapper">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" data-bs-toggle="dropdown">
                    
                    <img src="./forms/uploads/<?php echo !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg'; ?>"
                         class="profile-img" id="navbar-profile-img">

                    <div class="ms-2 d-none d-sm-block text-start">
                        <div class="fw-semibold" style="font-size: 0.82rem; line-height: 1.2; color: #fff;" id="navbar-user-name">
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </div>
                        <span class="badge bg-orange-badge p-1 px-2 mt-1" style="font-size: 0.62rem; text-transform: capitalize; font-weight: 600;">
                            <?php echo htmlspecialchars($_SESSION['user_type']); ?>
                        </span>
                    </div>
                </a>

                <ul class="dropdown-menu dropdown-menu-end p-2 mt-2">
                    <li>
                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fa-solid fa-user me-2 text-orange-accent"></i> My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#settingsModal">
                            <i class="fa-solid fa-gear me-2 text-orange-accent"></i> Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
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

<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm"> 
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="fw-bold text-dark mb-0">Confirm Logout</h6>
                <button class="btn-close" data-bs-dismiss="modal" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body text-center py-3">
                <i class="fa-solid fa-circle-question text-orange-accent mb-2" style="font-size: 36px;"></i>
                <p class="text-secondary mb-0" style="font-size: 0.9rem;">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-3">
                <button class="btn btn-light btn-sm px-3 rounded-2" data-bs-dismiss="modal" style="font-size: 0.8rem;">Cancel</button>
                <a href="sign_in.php" class="btn btn-danger btn-sm px-3 rounded-2" style="font-size: 0.8rem;">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 text-white py-3" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 2px solid #ff8e53 !important;">
                <h6 class="modal-title fw-semibold" id="profileModalLabel" style="font-size: 0.95rem;">
                    <i class="fa-solid fa-user-gear me-2 text-orange-accent"></i> My Profile Settings
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe src="./profile.php" width="100%" height="650" style="border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 text-white py-3" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 2px solid #ff8e53 !important;">
                <h6 class="modal-title fw-semibold" id="settingsModalLabel" style="font-size: 0.95rem;">
                     <i class="fa-solid fa-gear me-2 text-orange-accent"></i> My Settings
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe src="./settings_doctor.php" width="100%" height="650" style="border: none; display: block;"></iframe>
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