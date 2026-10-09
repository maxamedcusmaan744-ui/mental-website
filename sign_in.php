<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Access | Habeeb Psychiatric Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* REFINED MEDICAL ENTERPRISE PALETTE */
            --navy-dark: #0a0f1d;
            --navy-main: #111c44;
            --navy-light: #1b2c66;
            --navy-accent: #253b80;
            
            --orange-main: #f97316;
            --orange-light: #fdba74;
            --orange-dark: #ea580c;
            --orange-glow: rgba(249, 115, 22, 0.25);

            /* LUXURY GRADIENTS */
            --bg-overlay: linear-gradient(135deg, rgba(10, 15, 29, 0.85) 0%, rgba(17, 28, 68, 0.9) 100%);
            --navy-grad: linear-gradient(145deg, var(--navy-main) 0%, var(--navy-dark) 100%);
            --orange-grad: linear-gradient(135deg, var(--orange-main) 0%, var(--orange-dark) 100%);
            --input-focus-grad: linear-gradient(90deg, var(--orange-main), var(--orange-light));
            
            --glass-card: rgba(255, 255, 255, 0.96);
            --form-bg: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--navy-dark);
            background-image: url('https://images.unsplash.com/photo-1629909613654-28e377c37b09?q=80&w=2070&auto=format&fit=crop'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding: 20px;
        }

        /* Ambient Glass Backdrop Overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: var(--bg-overlay);
            backdrop-filter: blur(10px);
            z-index: 0;
        }

        @keyframes cardLoad {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Luxury Split-Container */
        .master-container {
            position: relative;
            z-index: 1;
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: var(--glass-card);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.5),
                        0 0 50px 0 rgba(17, 28, 68, 0.2);
            animation: cardLoad 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* --- Left Welcoming Pane --- */
        .welcome-pane {
            flex: 1.2;
            background: var(--navy-grad);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            color: white;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Premium Minimalist Geometric Network Pattern */
        .welcome-pane::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('data:image/svg+xml,%3Csvg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23f97316" fill-opacity="0.04"%3E%3Cpath d="M40 0l40 40-40 40L0 40z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
            opacity: 0.7;
            z-index: 1;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
            margin-auto: 0;
            align-self: center;
            text-align: center;
            width: 100%;
        }

        .pane-logo {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.05);
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 35px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.4s ease;
        }
        
        .pane-logo:hover {
            transform: scale(1.02);
        }

        .pane-title {
            font-weight: 800;
            font-size: 2.6rem;
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: -1px;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pane-subtitle {
            font-size: 1.1rem;
            color: #94a3b8;
            font-weight: 400;
            max-width: 340px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* --- Right Form Pane --- */
        .form-pane {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            justify-content: center;
        }

        .form-header-title {
            color: var(--navy-main);
            font-weight: 800;
            font-size: 2rem;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .form-header-subtitle {
            color: var(--text-muted);
            margin-bottom: 40px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        /* Clean Modern Field Architecture */
        .custom-input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .custom-input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #a6b4c9;
            transition: color 0.3s ease, transform 0.3s ease;
            z-index: 10;
            pointer-events: none;
        }

        .form-control {
            border-radius: 14px;
            padding: 18px 20px 18px 56px;
            border: 1.5px solid #e2e8f0;
            background-color: var(--form-bg);
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--navy-main);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Elite Focus State Interaction */
        .form-control:focus {
            border-color: var(--orange-main);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px var(--orange-glow);
        }

        .form-control:focus + .custom-input-icon {
            color: var(--orange-main);
            transform: translateY(-50%) scale(1.05);
        }

        .form-check-input {
            width: 19px;
            height: 19px;
            border-radius: 6px !important;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background-color: var(--orange-main);
            border-color: var(--orange-main);
            box-shadow: 0 2px 6px var(--orange-glow);
        }

        .form-check-label {
            color: var(--navy-main);
            font-size: 0.95rem;
            font-weight: 600;
            padding-left: 4px;
            cursor: pointer;
        }

        /* High-End Modernized Action Controls */
        .forgot-link {
            color: var(--navy-main);
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .forgot-link:hover { 
            color: var(--orange-main); 
        }

        /* Premium Orange Action Button */
        .btn-login {
            background: var(--orange-grad);
            border: none;
            color: white;
            padding: 18px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.05rem;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px -4px rgba(249, 115, 22, 0.45);
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px -4px rgba(249, 115, 22, 0.6);
            filter: brightness(1.05);
        }

        .btn-login:active { 
            transform: translateY(1px); 
        }

        /* Registration Pathway block */
        .signup-container {
            border-top: 1px dashed #e2e8f0;
            padding-top: 25px;
            margin-top: 5px;
        }

        .signup-link {
            color: var(--orange-main);
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: color 0.3s ease;
        }

        .signup-link:hover {
            color: var(--orange-dark);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.5;
        }

        /* Big Logo Configuration */
        .custom-logo-img {
            width: 100%;
            max-width: 160px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.25));
        }
        
        /* --- High-Fidelity Responsiveness --- */
        @media (max-width: 920px) {
            .master-container { max-width: 480px; border-radius: 20px; }
            .welcome-pane { display: none; }
            .form-pane { padding: 45px 35px; }
        }
    </style>
</head>
<body>

<div class="master-container">
    <div class="welcome-pane">
        <div class="welcome-content">
            <div class="pane-logo">
                <img src="forms/uploads/images.jpg" alt="Habeeb Psychiatric Hospital Logo" class="custom-logo-img">
            </div>
            <h1 class="pane-title">Habeeb<br>Psychiatric Hospital</h1>
            <p class="pane-subtitle">Welcome to your secure clinical administration portal.</p>
        </div>
    </div>

    <div class="form-pane">
        <h2 class="form-header-title">Admin Sign In</h2>
        <p class="form-header-subtitle">Enter your secure credentials to proceed.</p>

        <form action="log.php" method="POST">
            <div class="custom-input-group">
                <input type="email" class="form-control" id="txt_email" name="txt_email" placeholder="Enter email" required>
                <svg class="custom-input-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </div>

            <div class="custom-input-group">
                <input type="password" class="form-control" id="txt_password" name="txt_password" placeholder="Enter Password" required>
                <svg class="custom-input-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check d-flex align-items-center">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-login" name="btnLogin">
                <span>Sign In Securely</span>
            </button>
        </form>

        <div class="signup-container d-flex justify-content-between align-items-center">
            <span class="text-muted small fw-semibold">New system administrator?</span>
            <a href="signup.php" class="signup-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-person-plus-fill me-2" viewBox="0 0 16 16">
                  <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm7.5-3a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h2z"/>
                  <path d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
                </svg>Sign Up
            </a>
        </div>

        <div class="login-footer">
            Habeeb Psychiatric Hospital &copy; 2026 <br>
            <span class="badge bg-dark mt-2 px-3 py-2" style="background-color: var(--navy-main) !important; font-weight: 600; letter-spacing: 0.3px;">
                <span style="color: var(--orange-main); margin-right: 4px;">●</span> Secure System Environment
            </span>
        </div>
    </div>
</div>

</body>
</html>