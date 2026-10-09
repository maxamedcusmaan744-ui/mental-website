<?php 
session_start();
if(isset($_SESSION['email'])) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Habeeb Psychiatric Hospital</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #0a192f; /* Background-ka weyn oo laga dhigay Dark Navy */
        color: #fff;
    }
    
    /* IFRAME CONTAINER GRADIENT & GLOW EFFECT */
    .iframe-container {
        position: relative;
        width: 100%;
        height: 950px; 
        overflow: hidden;
        border-radius: 30px; 
        background: rgba(23, 42, 69, 0.8); /* Navy Blue sanduuq ah */
        backdrop-filter: blur(15px);
        /* Border yar oo ifaya oo Orange ah */
        border: 2px solid rgba(255, 138, 0, 0.25);
        /* Hoos ifaya (Glow Effect) oo la jaanqaadaya Orange-ka */
        box-shadow: 0 25px 50px rgba(255, 110, 0, 0.15);
        margin-top: 20px;
        margin-bottom: 50px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .iframe-container:hover {
        box-shadow: 0 30px 60px rgba(255, 110, 0, 0.25);
    }

    #mainFrame {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }

    .custom-table-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        overflow: hidden;
        background: #172a45;
        margin-bottom: 30px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
    }
    </style>
</head>
<body>

<!-- Navbar / Sidebar-ka Dhaqtarka -->
<?php include 'assets_doctor/sidebar_doctor.php' ?>

<!-- Main Content Container -->
<div class="container-fluid px-lg-5 mt-4"> 
    
    <!-- Hero / Iframe Section -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="iframe-container">
                <iframe 
                    src="home_doctor.php" 
                    name="fr" 
                    id="mainFrame" 
                    title="Doctor Home"
                    loading="lazy"
                    allowfullscreen>
                </iframe>
            </div>
        </div>s
    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } else {
    header('Location: sign_in.php');
    exit();
} ?>