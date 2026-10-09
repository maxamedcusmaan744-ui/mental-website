<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habeeb Psychiatric Hospital - Excellence in Mental Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0a192f;
            color: #ffffff;
        }

        /* Dark Navy Blue Gradient Background */
        .navy-gradient-bg {
            background: linear-gradient(135deg, #0a192f 0%, #172a45 100%);
        }

        /* Orange Gradient Qoraalka ah */
        .orange-gradient-text {
            background: linear-gradient(90deg, #ff6b00, #ff9e00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        /* Badhanka Orange Gradient-ka ah */
        .btn-orange {
            background: linear-gradient(90deg, #ff6b00, #ff9e00);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-orange:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 0, 0.4);
            color: white;
        }

        /* Badhanka Outline Orange-ka ah */
        .btn-outline-orange {
            color: #ff9e00;
            border: 2px solid #ff9e00;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-outline-orange:hover {
            background: linear-gradient(90deg, #ff6b00, #ff9e00);
            color: white;
            border-color: transparent;
        }

        /* Hero Section */
        .hero-section {
            padding: 100px 0;
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 2px solid rgba(255, 158, 0, 0.2);
        }

        /* Kaadhadhka Sabaynaya (Floating Cards) */
        .floating-card {
            position: absolute;
            background: rgba(23, 42, 69, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255, 158, 0, 0.2);
            z-index: 2;
        }

        .text-orange-light {
            color: #ff9e00 !important;
        }

        .custom-badge {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #ff9e00 !important;
            border: 1px solid rgba(255, 158, 0, 0.3) !important;
        }
    </style>
</head>
<body>
    

<section class="hero-section navy-gradient-bg">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge custom-badge rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Certified Specialists</span>
                    <span class="badge custom-badge rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i> 24/7 Mental Health Care</span>
                </div>
                
                <h1 class="display-4 fw-bold mb-4 text-white">
                    <span class="orange-gradient-text">Habeeb Hospital</span> <br>Excellence in Psychiatric Care
                </h1>
                <p class="text-white-50 fs-5 mb-5">Providing advanced psychiatric services and compassionate mental healthcare with modern professional medical expert solutions.</p>
                
                <div class="row mb-5">
                    <div class="col-4">
                        <h3 class="fw-bold text-orange-light">20+</h3>
                        <p class="small text-white-50">Years Experience</p>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold text-orange-light">15k+</h3>
                        <p class="small text-white-50">Lives Transformed</p>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold text-orange-light">30+</h3>
                        <p class="small text-white-50">Psychiatric Experts</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <button class="btn btn-orange shadow">Book Appointment</button>
                    <button class="btn btn-outline-orange"><i class="fas fa-play-circle me-2"></i>Watch Our Story</button>
                </div>
            </div>
            
            <div class="col-lg-6 mt-5 mt-lg-0">
                <div class="doctor-img-container text-center">
                    <div class="floating-card" style="top: 10%; right: -10px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-orange-light">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="text-start">
                                <p class="mb-0 small fw-bold text-white">Next Consultant</p>
                                <p class="mb-0 x-small text-white-50">Today 2:30 PM</p>
                            </div>
                        </div>
                    </div>

                    <img src="https://img.freepik.com/free-photo/portrait-smiling-handsome-male-doctor-man_171337-5055.jpg" alt="Habeeb Clinic Doctor" class="doctor-img">
                    
                    <div class="floating-card" style="bottom: 10%; left: -10px;">
                        <div class="text-center">
                            <div class="text-warning mb-1">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <h5 class="mb-0 fw-bold text-white">4.9/5</h5>
                            <p class="mb-0 small text-white-50">Top Rated Facility</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>