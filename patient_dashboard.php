<?php 
session_start();
if(isset($_SESSION['email'])) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habeeb Psychiatric Hospital - Excellence in Mental Healthcare</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Background ka weyn oo ah Navy Blue Gradient dhalaalaya */
            background: linear-gradient(135deg, #0a192f 0%, #172a45 100%);
            min-height: 100vh;
            color: #ffffff;
        }

        /* Qaybta koobaysa Iframe-ka oo leh nidaamka midabada cusub */
        .iframe-container {
            position: relative;
            width: 100%;
            height: 950px; 
            overflow: hidden;
            border-radius: 30px; 
            background: rgba(23, 42, 69, 0.8);
            backdrop-filter: blur(15px);
            /* Xuduud (Border) leh midabka Orange gradient oo khafiif ah */
            border: 2px solid rgba(255, 138, 0, 0.3);
            /* Hoos hadal weyn oo Orange ah oo deggen */
            box-shadow: 0 25px 50px rgba(255, 138, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 50px;
        }

        #mainFrame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Styling-ka Qaybaha qoraalka iyo badhamada orange-ka ah haddii aad dib u furato */
        .text-gradient-orange {
            background: linear-gradient(45deg, #ff6b00, #ff9e00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .custom-table-card {
            background: rgba(23, 42, 69, 0.9) !important;
            border: 1px solid rgba(255, 138, 0, 0.2) !important;
            color: #ffffff !important;
        }

        .custom-table-card .card-header {
            background: rgba(10, 25, 47, 0.5) !important;
            border-bottom: 1px solid rgba(255, 138, 0, 0.2) !important;
            color: #ffffff !important;
        }

        .table {
            color: #ffffff !important;
        }

        .btn-outline-orange {
            color: #ff8a00;
            border-color: #ff8a00;
        }

        .btn-outline-orange:hover {
            background: linear-gradient(45deg, #ff6b00, #ff9e00);
            color: #fff;
            border-color: transparent;
        }
    </style>
</head>
<body>

<!-- Navbar/Sidebar -->
<?php include 'assets_patient/sidebar_patient.php' ?>

<!-- Hero Section -->
<div class="container-fluid px-lg-5 pt-4"> 
    <div class="row justify-content-center">
        <div class="col-12 text-center mb-2">
            <!-- Magaca Cusub oo wata Orange Gradient -->
            <h2 class="fw-bold text-white">
                <span class="text-gradient-orange">Habeeb</span> Psychiatric Hospital
            </h2>
            <p class="text-muted small">Patient Portal Menu System</p>
        </div>
        <div class="col-12">
            <!-- Iframe Container oo leh wejiga cusub -->
            <div class="iframe-container shadow-lg">
                <iframe 
                    src="home_patient.php" 
                    name="fr" 
                    id="mainFrame" 
                    title="Patient Home"
                    loading="lazy"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Tables Section (Waa la faallaystay laakiin waa loo diyaariyey midabka cusub) -->
<!-- 
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5 text-white">Patient <span class="text-gradient-orange">Dashboard</span></h2>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card custom-table-card shadow">
                    <div class="card-header py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-check text-gradient-orange me-2"></i> Recent Appointments</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <th class="text-muted">ID</th>
                                <th class="text-muted">Doctor</th>
                                <th class="text-muted">Date</th>
                                <th class="text-muted">Status</th>
                                <th class="text-muted">Action</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#APT-102</td>
                                    <td>Dr. Sarah Johnson</td>
                                    <td>Oct 24, 2023</td>
                                    <td><span class="badge bg-success bg-opacity-20 text-success">Completed</span></td>
                                    <td><button class="btn btn-sm btn-outline-orange">View</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> 
-->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } else {
    header('Location: sign_in.php');
    exit();
} ?>