<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habeeb Psychiatric Hospital - Excellence in Mental Healthcare</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0a192f; /* Dark Navy Background */
            color: #fff;
        }

        /* ===== GRADIENTS & MIDABADA CUSUB ===== */
        .orange-gradient-text {
            background: linear-gradient(45deg, #ff6b00, #ff9e00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navy-gradient-bg {
            background: linear-gradient(135deg, #0a192f 0%, #172a45 100%);
            border-bottom: 1px solid rgba(255, 138, 0, 0.15);
        }

        .btn-orange {
            background: linear-gradient(45deg, #ff6b00, #ff9e00);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
        }

        .btn-orange:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 0, 0.5);
            color: white;
        }

        .btn-outline-orange {
            border: 2px solid #ff9e00;
            color: #ff9e00;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s ease;
        }

        .btn-outline-orange:hover {
            background: rgba(255, 158, 0, 0.1);
            color: #fff;
            border-color: #ff9e00;
            transform: translateY(-3px);
        }

        .text-orange-custom {
            color: #ff9e00 !important;
        }

        /* ===== HERO SECTION ===== */
        .hero-section {
            padding: 120px 0;
            position: relative;
            overflow: hidden;
        }

        .doctor-img-container {
            position: relative;
            z-index: 1;
        }

        .doctor-img {
            border-radius: 30px;
            width: 100%;
            max-width: 500px;
            border: 3px solid rgba(255, 158, 0, 0.2);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        /* FLOATING CARDS (NAVY THEME) */
        .floating-card {
            position: absolute;
            background: #172a45;
            border: 1px solid rgba(255, 138, 0, 0.2);
            padding: 18px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            z-index: 2;
        }

        /* BADGES */
        .custom-badge {
            background: rgba(255, 158, 0, 0.1) !important;
            color: #ff9e00 !important;
            border: 1px solid rgba(255, 158, 0, 0.25) !important;
        }
    </style>
</head>
<body>
    

<section class="hero-section navy-gradient-bg">
    <div class="container">
        <div class="row align-items-center">
            
            <!-- Bidix: Qoraalka iyo Badhamada -->
            <div class="col-lg-6">
                <div class="d-flex gap-2 mb-4">
                    <span class="badge custom-badge rounded-pill px-3 py-2"><i class="fas fa-ribbon me-1"></i> Mental Health Leader</span>
                    <span class="badge custom-badge rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i> 24/7 Care</span>
                </div>
                
                <h1 class="display-4 fw-bold mb-4">
                    Welcome to <br>
                    <span class="orange-gradient-text">Habeeb Psychiatric</span> <br>
                    Hospital
                </h1>
                
                <p class="text-white-50 fs-5 mb-5">
                    Muxuu daryeelka dhimirku muhiim u yahay? Waxaan bixinaa adeegyo caafimaad oo casri ah oo ku dhisan naxariis, kalsooni, iyo wehel joogto ah oo loo fidinayo bukaanka.
                </p>
                
                <!-- Statics / Tirokoobyo -->
                <div class="row mb-5">
                    <div class="col-4">
                        <h2 class="fw-bold text-orange-custom mb-1">20+</h2>
                        <p class="small text-white-50">Years Experience</p>
                    </div>
                    <div class="col-4">
                        <h2 class="fw-bold text-orange-custom mb-1">15k+</h2>
                        <p class="small text-white-50">Lives Restored</p>
                    </div>
                    <div class="col-4">
                        <h2 class="fw-bold text-orange-custom mb-1">40+</h2>
                        <p class="small text-white-50">Mental Experts</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <button class="btn btn-orange shadow">Book Appointment</button>
                    <button class="btn btn-outline-orange"><i class="fas fa-play-circle me-2"></i>Our Mission</button>
                </div>
            </div>
            
            <!-- Midig: Sawirka iyo Floating Cards -->
            <div class="col-lg-6 mt-5 mt-lg-0">
                <div class="doctor-img-container text-center">
                    
                    <!-- Floating Badge 1 (Kore) -->
                    <div class="floating-card" style="top: 8%; right: -10px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-orange-custom">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="text-start">
                                <p class="mb-0 small fw-bold text-white">Next Session</p>
                                <p class="mb-0 small text-white-50">Today 2:30 PM</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sawirka Dhaqtarka -->
                    <img src="https://img.freepik.com/free-photo/portrait-smiling-handsome-male-doctor-man_171337-5055.jpg" alt="Habeeb Specialist Doctor" class="doctor-img">
                    
                    <!-- Floating Badge 2 (Hoose) -->
                    <div class="floating-card" style="bottom: 8%; left: -10px;">
                        <div class="text-center">
                            <div class="text-warning mb-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <h5 class="mb-0 fw-bold text-white">4.9/5</h5>
                            <p class="mb-0 small text-white-50">Patient Satisfaction</p>
                        </div>
                    </div>
                    
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>