<!-- Load Modern Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* NIDAAMKA MIDABYADA (LIGHT MODE - DEFAULT) */
    :root {
        --sidebar-bg: #ffffff;
        --brand-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        --active-bg: rgba(249, 115, 22, 0.08);
        --text-primary: #1e3a8a;
        --text-muted: #64748b;
        --text-dark: #0f172a;
        --border-color: rgba(226, 232, 240, 0.8);
        --body-bg: #f8fafc;
        --inner-border: #f1f5f9;
        --storage-card-bg: rgba(254, 243, 199, 0.4);
        --storage-card-border: rgba(253, 186, 116, 0.4);
        --storage-icon-bg: #ffffff;
        --icon-color: #f97316;
    }

    /* NIDAAMKA MIDABYADA (DARK MODE) */
    [data-bs-theme="dark"] {
        --sidebar-bg: #0f172a;
        --active-bg: rgba(249, 115, 22, 0.15);
        --text-primary: #3b82f6;
        --text-muted: #64748b;
        --text-dark: #f8fafc;
        --border-color: rgba(30, 41, 59, 0.8);
        --body-bg: #020617;
        --inner-border: #1e293b;
        --storage-card-bg: rgba(249, 115, 22, 0.05);
        --storage-card-border: rgba(249, 115, 22, 0.2);
        --storage-icon-bg: #1e293b;
        --icon-color: #fb923c;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--body-bg);
    }

    .sidebar-wrapper {
        width: 280px;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--border-color);
        box-shadow: 10px 0 30px rgba(0, 0, 0, 0.01);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--border-color) transparent;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    .brand-logo-img {
        width: 42px;
        height: 42px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .brand-title {
        color: var(--text-dark); 
        letter-spacing: -0.5px;
        font-size: 1.15rem;
        line-height: 1.3;
    }

    .nav-link {
        color: var(--text-muted);
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 12px;
        margin: 4px 16px;
        padding: 10px 16px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .nav-link i {
        font-size: 1.1rem;
    }

    .nav-link:hover {
        color: #f97316;
        background: var(--active-bg);
        transform: translateX(4px);
    }

    .nav-link.active {
        background: var(--brand-gradient) !important;
        color: #ffffff !important;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
    }

    .section-label {
        color: var(--text-primary);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        padding: 24px 24px 8px;
        opacity: 0.8;
    }

    .collapse-inner {
        margin-left: 34px;
        border-left: 1.5px solid var(--inner-border);
        padding-left: 6px;
    }

    .sub-link {
        font-size: 0.825rem;
        padding: 8px 14px;
        margin: 2px 16px 2px 8px;
    }

    .sub-link:hover {
        color: #f97316;
        background: var(--active-bg);
    }

    .storage-card {
        background: var(--storage-card-bg);
        backdrop-filter: blur(8px);
        border: 1px solid var(--storage-card-border);
        transition: all 0.3s ease;
    }

    .arrow-icon {
        transition: transform 0.2s ease;
    }
    
    [aria-expanded="true"] .arrow-icon {
        transform: rotate(180deg);
        color: #f97316;
    }
    
    [aria-expanded="true"] {
        color: #f97316 !important;
    }
</style>

<aside class="sidebar-wrapper">
    <!-- Brand Info -->
    <div class="brand-logo p-4 pb-2">
        <div class="d-flex align-items-center">
            <img src="forms/uploads/images.jpg" alt="Habeeb Logo" class="brand-logo-img me-3">
            <h4 class="fw-bold brand-title mb-0">
                Habeeb<br><span style="color: #f97316;">Psychiatric Hospital</span>
            </h4>
        </div>
    </div>

    <!-- Navigation -->
    <div class="nav flex-column mt-3">
        
        <!-- SECTION: DASHBOARD -->
        <small class="section-label text-uppercase">Main</small>
        <a href="./index.php" class="nav-link active d-flex align-items-center">
            <i class="bi bi-grid-1x2-fill me-3"></i> Dashboard
        </a>

        <!-- SECTION: CORE SYSTEM -->
        <small class="section-label text-uppercase">Core System</small>
        <a href="./forms/users.php" target="fr" class="nav-link d-flex align-items-center">
            <i class="bi bi-people-fill me-3"></i> Users & Accounts
        </a>

        <!-- Admission Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#admissionMenu" role="button" aria-expanded="false" aria-controls="admissionMenu">
                <span><i class="bi bi-hospital-fill me-3"></i> Admission</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="admissionMenu">
                <div class="collapse-inner">
                    <a href="./forms/patient_admissions.php" target="fr" class="nav-link sub-link"><i class="bi bi-person-badge me-2"></i> Patients</a>
                    <a href="./forms/patient_guardians.php" target="fr" class="nav-link sub-link"><i class="bi bi-shield-check me-2"></i> Guardians</a>
                    <a href="./forms/doctors.php" target="fr" class="nav-link sub-link"><i class="bi bi-person-heart me-2"></i> Doctors</a>
                    <a href="./forms/staff.php" target="fr" class="nav-link sub-link"><i class="bi bi-person-workspace me-2"></i> Staff</a>
                       <a href="./forms/nurses.php" target="fr" class="nav-link sub-link"><i class="bi bi-person-workspace me-2"></i> Nurse</a>
                </div>
            </div>
        </div>

        <!-- Registration Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#registrationMenu" role="button" aria-expanded="false" aria-controls="registrationMenu">
                <span><i class="bi bi-building-fill me-3"></i> Registration</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="registrationMenu">
                <div class="collapse-inner">
                    <a href="./forms/departments.php" target="fr" class="nav-link sub-link"><i class="bi bi-door-open me-2"></i> Departments</a>
                   <a href="./forms/rooms.php" target="fr" class="nav-link sub-link"><i class="bi bi-door-closed me-2"></i> Rooms</a>
                    <a href="./forms/beds.php" target="fr" class="nav-link sub-link"><i class="bi bi-layers me-2"></i> Beds</a>
                </div>
            </div>
        </div>

        <!-- Clinical Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#clinicalMenu" role="button" aria-expanded="false" aria-controls="clinicalMenu">
                <span><i class="bi bi-clipboard2-pulse-fill me-3"></i> Clinical</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="clinicalMenu">
                <div class="collapse-inner">
                    <a href="./forms/diagnoses.php" target="fr" class="nav-link sub-link"><i class="bi bi-activity me-2"></i> Diagnoses</a>
                    <a href="./forms/patient_discharges.php" target="fr" class="nav-link sub-link"><i class="bi bi-box-arrow-left me-2"></i> Patient Discharges</a>
                    <a href="./forms/medications.php" target="fr" class="nav-link sub-link"><i class="bi bi-capsule me-2"></i> Medications</a>

                </div>
            </div>
        </div>

        <!-- Schedule Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#scheduleMenu" role="button" aria-expanded="false" aria-controls="scheduleMenu">
                <span><i class="bi bi-calendar3 me-3"></i> Schedule</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="scheduleMenu">
                <div class="collapse-inner">
                    <a href="./forms/schedules.php" target="fr" class="nav-link sub-link"><i class="bi bi-file-earmark-ruled me-2"></i> schedules</a>
                        <a href="./forms/schedule_slots.php" target="fr" class="nav-link sub-link"><i class="bi bi-cash-stack me-2"></i> Booking</a>
                        <!-- <a href="./forms/emergency_requests.php" target="fr" class="nav-link sub-link"><i class="bi bi-exclamation-triangle me-2"></i> emergency_requests</a> -->
                </div>
            </div>
        </div>

         <!-- Meeting Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#meetingMenu" role="button" aria-expanded="false" aria-controls="meetingMenu">
                <span><i class="bi bi-calendar-event me-3"></i> Meeting</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="meetingMenu">
                <div class="collapse-inner">
                    <a href="./forms/appointments.php" target="fr" class="nav-link sub-link"><i class="bi bi-file-earmark-ruled me-2"></i> Appointments</a>
                        <a href="./forms/videocall.php" target="fr" class="nav-link sub-link"><i class="bi bi-cash-stack me-2"></i> Video Calls</a>
                   </div>
            </div>
        </div>

        <!-- Schedule Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#emergency" role="button" aria-expanded="false" aria-controls="emergency">
                <span><i class="bi bi-exclamation-triangle me-3"></i> Emergency</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="emergency">
                <div class="collapse-inner">
                    <a href="./forms/emergency_requests.php" target="fr" class="nav-link sub-link"><i class="bi bi-exclamation-triangle me-2"></i> Emergency</a>
                </div>
            </div>
        </div>

        
        <!-- Finance Dropdown -->
        <div class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-between" 
               data-bs-toggle="collapse" href="#financeMenu" role="button" aria-expanded="false" aria-controls="financeMenu">
                <span><i class="bi bi-wallet2 me-3"></i> Finance</span>
                <i class="bi bi-chevron-down arrow-icon small"></i>
            </a>
            <div class="collapse" id="financeMenu">
                <div class="collapse-inner">
                    <a href="./forms/bills.php" target="fr" class="nav-link sub-link"><i class="bi bi-file-earmark-ruled me-2"></i> Bills</a>
                    <a href="./forms/lcg_payments.php" target="fr" class="nav-link sub-link"><i class="bi bi-cash-stack me-2"></i> Payments</a>
                </div>
            </div>
        </div>


         <!-- Report Dropdown -->
       <div class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-between" 
       data-bs-toggle="collapse" href="#reportMenu" role="button" aria-expanded="false" aria-controls="reportMenu">
        <span><i class="bi bi-bar-chart-line me-3"></i> Report</span>
        <i class="bi bi-chevron-down arrow-icon small"></i>
    </a>
    <div class="collapse" id="reportMenu">
        <div class="collapse-inner">
            <a href="./forms/allpatients.php" target="fr" class="nav-link sub-link"><i class="bi bi-people me-2"></i> All Patients</a>
            <a href="./forms/allstaff.php" target="fr" class="nav-link sub-link"><i class="bi bi-person-badge me-2"></i> All Staff</a>  
            <a href="./forms/alldoctors.php" target="fr" class="nav-link sub-link"><i class="bi bi-person-heart me-2"></i> All Doctors</a>
            <a href="./forms/allnursing.php" target="fr" class="nav-link sub-link"><i class="bi bi-capsule-pill me-2"></i> All Nursing</a> 
            <a href="./forms/allrooms.php" target="fr" class="nav-link sub-link"><i class="bi bi-house-door me-2"></i> All Rooms</a>    
            <a href="./forms/allbeds.php" target="fr" class="nav-link sub-link"><i class="bi bi-hospital me-2"></i> All Beds</a> 
            <a href="./forms/alldepartments.php" target="fr" class="nav-link sub-link"><i class="bi bi-building me-2"></i> All Departments</a> 
            <a href="./forms/allmedications.php" target="fr" class="nav-link sub-link"><i class="bi bi-prescription2 me-2"></i> All Medications</a> 
            <a href="./forms/alldiagnoses.php" target="fr" class="nav-link sub-link"><i class="bi bi-clipboard2-pulse me-2"></i> All Diagnoses</a>
            <a href="./forms/alldischarge.php" target="fr" class="nav-link sub-link"><i class="bi bi-box-arrow-right me-2"></i> All Discharges</a>  
            <a href="./forms/allbooking.php" target="fr" class="nav-link sub-link"><i class="bi bi-calendar-check me-2"></i> All Bookings</a>
            <a href="./forms/allapointment.php" target="fr" class="nav-link sub-link"><i class="bi bi-calendar-event me-2"></i> All Appointments</a> 
            <a href="./forms/allemengency.php" target="fr" class="nav-link sub-link"><i class="bi bi-exclamation-triangle me-2"></i> Emergency Requests</a>    
            <a href="./forms/allbills.php" target="fr" class="nav-link sub-link"><i class="bi bi-receipt-cutoff me-2"></i> All Bills</a> 
            <a href="./forms/allpayments.php" target="fr" class="nav-link sub-link"><i class="bi bi-credit-card me-2"></i> All Payments</a> 
        </div>
    </div>
</div>

        

        <!-- Sign Out -->
        <div class="mt-3 border-top mx-4" style="border-color: var(--border-color) !important; opacity: 0.4;"></div>
        <a href="./sign_in.php" class="nav-link d-flex align-items-center text-danger">
            <i class="bi bi-box-arrow-right me-3"></i> Sign Out
        </a>
    </div>

    <!-- Bottom Storage Card -->
    <div class="mt-auto p-4">
        <div class="storage-card p-3 rounded-4">
            <div class="d-flex align-items-center mb-2">
                <div class="p-2 rounded-3 shadow-sm me-2 d-flex align-items-center justify-content-center" 
                     style="width: 32px; height: 32px; background-color: var(--storage-icon-bg);">
                    <i class="bi bi-cloud-check-fill" style="color: var(--icon-color);"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-bold" style="color: var(--text-dark); font-size: 0.75rem;">Cloud Storage</span>
                    <span style="font-size: 0.6rem; color: var(--text-muted);">85% full capacity</span>
                </div>
            </div>
            <div class="progress mb-2" style="height: 5px; background-color: rgba(249, 115, 22, 0.15); border-radius: 10px;">
                <div class="progress-bar" style="width: 85%; background: var(--brand-gradient); border-radius: 10px;"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small style="font-size: 0.65rem; color: var(--text-muted);">1.2TB / 2TB</small>
                <a href="#" class="text-decoration-none fw-bold" style="font-size: 0.65rem; color: #f97316;">Upgrade</a>
            </div>
        </div>
    </div>
</aside>

<!-- DHEGEYSTAYHA ISBEDDELKA DARK/LIGHT MODE -->
<script>
    window.addEventListener('storage', function (event) {
        if (event.key === 'app-theme') {
            document.documentElement.setAttribute('data-bs-theme', event.newValue || 'light');
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.querySelector('.sidebar-wrapper');
        if (!sidebar || typeof bootstrap === 'undefined') return;

        sidebar.querySelectorAll('.collapse').forEach(function (menu) {
            menu.addEventListener('show.bs.collapse', function () {
                sidebar.querySelectorAll('.collapse.show').forEach(function (openMenu) {
                    if (openMenu !== menu) {
                        bootstrap.Collapse.getOrCreateInstance(openMenu, {
                            toggle: false
                        }).hide();
                    }
                });
            });
        });
    });
</script>
