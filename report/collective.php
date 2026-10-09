<?php
// 1. XIRIIRKA DATABASE-KA
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mental_health_support"; 

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("<div class='alert alert-danger m-3'><strong>Database Connection Failed:</strong> " . mysqli_connect_error() . "</div>");
}

// 2. SQL QUERY-GA DHAKHAATIIRTA (Halkan ayaa la saxay)
$query_doctors = "SELECT d.doctor_id, d.specialization, d.experience_years, u.full_name, u.profile_pic, u.status 
                  FROM doctors d
                  JOIN users u ON d.user_id = u.user_id
                  ORDER BY d.doctor_id DESC LIMIT 3"; // Waxaa loo beddelay d.doctor_id DESC

$result_doctors = mysqli_query($conn, $query_doctors);

// Badbaadada SQL Error-ka
if (!$result_doctors) {
    die("<div class='alert alert-danger m-3'><strong>SQL Query Error:</strong> " . mysqli_error($conn) . "</div>");
}
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card glass-card border-0 h-100 p-2">
            <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Registered Specialists</h5>
                <button class="btn btn-outline-hospital btn-sm rounded-pill px-3">View All Doctors</button>
            </div>
            <div class="table-responsive px-3">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th class="px-3">Doctor / Therapist</th>
                            <th>Specialty</th>
                            <th>Experience</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_doctors) > 0): ?>
                            <?php while($doctor = mysqli_fetch_assoc($result_doctors)): ?>
                                <tr>
                                    <td class="px-3">
                                        <div class="d-flex align-items-center">
                                            <img src="forms/uploads/<?php echo htmlspecialchars($doctor['profile_pic']); ?>" 
                                                 alt="Profile" 
                                                 class="patient-avatar object-fit-cover"
                                                 onerror="this.src='forms/uploads/default.jpg';">
                                            
                                            <div>
                                                <div class="fw-semibold small">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                                <small class="text-muted" style="font-size: 0.75rem;">ID: #DOC-<?php echo $doctor['doctor_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge custom-badge fw-normal p-2 rounded-2">
                                            <?php echo htmlspecialchars($doctor['specialization']); ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo htmlspecialchars($doctor['experience_years']); ?> Years
                                    </td>
                                    <td>
                                        <?php if (strcasecmp($doctor['status'], 'Active') == 0): ?>
                                            <span class="status-pill bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="status-pill bg-warning">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-action-dots border rounded-circle">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted small py-4">Wax dhakhaatiir ah oo diwangashan laguma kalaso helin database-ka.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card glass-card border-0 p-4 mb-4">
            <h6 class="fw-bold mb-3">Collective Patient Mood</h6>
            <div class="d-flex align-items-end gap-3 mb-4" style="height: 100px;">
                <div class="mood-bar w-100" style="height: 80%; opacity: 0.5;"></div>
                <div class="mood-bar w-100" style="height: 100%;"></div>
                <div class="mood-bar w-100" style="height: 60%; opacity: 0.7;"></div>
                <div class="mood-bar w-100" style="height: 30%; opacity: 0.3;"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted fw-bold">
                <span>MON</span><span>TUE</span><span>WED</span><span>THU</span>
            </div>
        </div>

        <div class="card border-0 shadow p-4 emergency-card">
            <div class="d-flex align-items-center mb-3">
                <div class="p-2 bg-danger text-white rounded-3 me-3 d-flex align-items-center justify-content-center" style="width:42px; height:42px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);">
                    <i class="bi bi-lightning-charge-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-emergency-title">Emergency Protocol</h6>
                    <small class="text-danger opacity-75 fw-bold live-pulse">1 Request Pending</small>
                </div>
            </div>
            <p class="small text-emergency-desc mb-4 opacity-90">Patient <strong>#ER-991</strong> triggered a crisis keyword in chat monitoring. Immediate intervention suggested.</p>
            <button class="btn btn-danger w-100 rounded-pill shadow-sm py-2 fw-bold" style="background-color: #dc3545; border: none;">Open Crisis Center</button>
        </div>
    </div>
</div>