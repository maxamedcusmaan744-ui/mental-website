<?php 
// 1. START SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. CHECK IF USER IS LOGGED IN AND IS AN ADMIN
if (isset($_SESSION['email']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin') { 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare Pro | Clinical Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f8fafc;
            --primary-accent: #6366f1;
            --primary-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --sidebar-width: 280px;
            --glass-bg: rgba(255, 255, 255, 0.8);
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
        }

        /* --- Sidebar Enhancements --- */
        .sidebar-wrapper {
            width: var(--sidebar-width);
            background: #ffffff;
            min-height: 100vh;
            position: fixed;
            border-right: 1px solid #e2e8f0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .brand-logo {
            padding: 30px 24px;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }

        .nav-link {
            padding: 12px 20px;
            color: #64748b;
            font-weight: 600;
            border-radius: 12px;
            margin: 4px 18px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .nav-link i { font-size: 1.25rem; margin-right: 12px; }

        .nav-link:hover {
            background-color: #f1f5f9;
            color: var(--primary-accent);
        }

        .nav-link.active {
            background: var(--primary-grad);
            color: white !important;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        /* --- Content Area --- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            transition: all 0.3s ease;
        }

        /* --- Glass Cards & UI --- */
        .glass-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
        }

        .status-pill {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-container {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 8px 20px;
            width: 400px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .search-container input {
            border: none;
            outline: none;
            padding-left: 10px;
            width: 90%;
            font-size: 0.9rem;
        }

        /* --- Custom Scrollbar --- */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* Responsive Fixes */
        @media (max-width: 992px) {
            .sidebar-wrapper { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 20px; }
            .search-container { width: 100%; margin-bottom: 20px; }
        }
    </style>
</head>
<body>

<?php include 'assets/sidebar.php'?>

<main class="main-content">
    
    <?php include 'assets/headers.php' ?>

    <style>
    .iframe-container {
        width: 100%;
        height: calc(100vh - 100px);
        overflow: hidden;
        border-radius: 20px;
        background: white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-top: 10px;
    }

    #mainFrame {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }
    </style>

    <div class="iframe-container">
        <iframe src="home.php" name="fr" id="mainFrame" loading="lazy"></iframe>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
} else {
    // Haddii uusan Admin ahayn ama uusan login ahayn, u dhiib sign_in.php
    header('Location: sign_in.php');
    exit();
}
?>