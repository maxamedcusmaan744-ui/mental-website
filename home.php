<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habeeb Hospital Dashboard</title>
    
    <!-- Fonts & CSS Frameworks -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            /* Habeeb Hospital Colors & Light Defaults */
            --navy-main: #1b2a4a;
            --navy-gradient: linear-gradient(135deg, #1b2a4a 0%, #0d1527 100%);
            --orange-main: #ea580c;
            --orange-gradient: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #f8fafc 50%, #ffebd9 100%);
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --card-bg: rgba(255, 255, 255, 0.45);
            --card-border: rgba(255, 255, 255, 0.6);
            --tr-bg: rgba(255, 255, 255, 0.6);
            --tr-hover: rgba(255, 255, 255, 0.9);
            --badge-bg: #f1f5f9;
            --badge-text: #1e293b;
        }

        /* MAQAARKA MADOW (DARK MODE CONFIGURATION) */
        [data-bs-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0d1527 0%, #0f172a 50%, #24140b 100%);
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --card-bg: rgba(15, 23, 42, 0.45);
            --card-border: rgba(255, 255, 255, 0.06);
            --tr-bg: rgba(30, 41, 59, 0.5);
            --tr-hover: rgba(30, 41, 59, 0.8);
            --badge-bg: #1e293b;
            --badge-text: #f1f5f9;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-dark);
            min-height: 100vh;
            padding: 2rem 1rem;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Glassmorphism Card Style */
        .glass-card {
            background: var(--card-bg) !important;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--card-border) !important;
            border-radius: 24px;
            box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.01), 0 4px 6px -2px rgba(0, 0, 0, 0.01);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border 0.3s ease, background-color 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(234, 88, 12, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(249, 115, 22, 0.25) !important;
        }

        /* Stats Icon Style */
        .stats-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Custom Brand Colors for Icons & Subtles */
        .bg-hospital-navy { background-color: rgba(27, 42, 74, 0.1) !important; color: #3b5284 !important; border: 1px solid rgba(27, 42, 74, 0.15); }
        [data-bs-theme="dark"] .bg-hospital-navy { background-color: rgba(59, 82, 132, 0.2) !important; color: #93aade !important; }
        
        .bg-hospital-orange { background-color: rgba(249, 115, 22, 0.1) !important; color: #ea580c !important; border: 1px solid rgba(249, 115, 22, 0.15); }
        [data-bs-theme="dark"] .bg-hospital-orange { background-color: rgba(249, 115, 22, 0.2) !important; color: #ff985e !important; }

        .bg-warning-subtle { background-color: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; border: 1px solid rgba(245, 158, 11, 0.2); }
        .bg-success-subtle { background-color: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; border: 1px solid rgba(16, 185, 129, 0.2); }

        /* Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
            color: var(--text-muted);
            border: none;
            padding: 1rem;
        }

        .table tbody tr {
            background-color: var(--tr-bg) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
            transition: all 0.2s ease, background-color 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: var(--tr-hover) !important;
            transform: scale(1.005);
        }

        .table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
            color: var(--text-dark);
        }

        .table tbody tr td:first-child { border-top-left-radius: 14px; border-bottom-left-radius: 14px; }
        .table tbody tr td:last-child { border-top-right-radius: 14px; border-bottom-right-radius: 14px; }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--navy-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
            margin-right: 12px;
            box-shadow: 0 4px 10px rgba(27, 42, 74, 0.2);
        }

        .custom-badge {
            background-color: var(--badge-bg) !important;
            color: var(--badge-text) !important;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Status Pills */
        .status-pill {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pill.bg-success { background-color: rgba(16, 185, 129, 0.15) !important; color: #34d399 !important; }
        .status-pill.bg-warning { background-color: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }
        .status-pill.bg-info { background-color: rgba(14, 165, 233, 0.15) !important; color: #38bdf8 !important; }

        /* Buttons matching Brand Identity */
        .btn-outline-hospital {
            border: 1px solid var(--orange-main);
            color: var(--orange-main);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-outline-hospital:hover {
            background: var(--orange-gradient);
            border-color: transparent;
            color: white;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
        }
        
        .btn-action-dots {
            background-color: var(--badge-bg) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--card-border) !important;
        }

        /* Mood Chart Bars - Orange & Navy Adaptations */
        .mood-bar {
            background: var(--orange-gradient);
            border-radius: 8px 8px 0 0;
            transition: height 0.3s ease;
        }

        /* Emergency Protocol Card Adaptations */
        .emergency-card {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border: 1px solid rgba(239, 68, 68, 0.2) !important;
            border-radius: 24px;
        }
        [data-bs-theme="dark"] .emergency-card {
            background: linear-gradient(135deg, #1f1315 0%, #451a1d 100%);
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
        }
        .text-emergency-title { color: #dc3545; }
        [data-bs-theme="dark"] .text-emergency-title { color: #fca5a5; }
        .text-emergency-desc { color: #1e293b; }
        [data-bs-theme="dark"] .text-emergency-desc { color: #fecaca; }

        /* Custom Pulse for live data */
        @keyframes pulse {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }
        .live-pulse {
            animation: pulse 2s infinite;
        }
    </style>
    
    <!-- ANTI-FLICKER -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('app-theme') || 
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
</head>
<body>

<div class="container-fluid px-md-4">
    
    <!-- ROW-KA CARDS-TA SARE -->
    <div class="row g-4 mb-5">
        <!-- 1. TOTAL PATIENTS CARD -->
        <?php include 'report/pateints.php'; ?>
        
        <!-- 2. SESSIONS CARD -->
        <?php include 'report/staffs.php'; ?>

        <!-- 3. PENDING CASES CARD -->
        <?php include 'report/nursing.php'; ?>

        <!-- 4. REVENUE CARD -->
        <?php include 'report/doctors.php'; ?>
    </div>

    <?php include 'report/collective.php'; ?>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// DHAGAYSTAYAASHA MAQAARKA (THEME LISTENERS)
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