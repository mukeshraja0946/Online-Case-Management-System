<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCMS - Case Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --header-height: 80px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f3f4f6;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            height: var(--header-height);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #2d3748 !important;
            letter-spacing: -0.5px;
        }

        .nav-link {
            font-weight: 600;
            color: #4a5568 !important;
            margin: 0 10px;
        }

        .btn-auth {
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login {
            color: #667eea;
            background: transparent;
            border: 2px solid #667eea;
        }

        .btn-login:hover {
            background: #667eea;
            color: white;
        }

        .btn-register {
            background: var(--primary-gradient);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(118, 75, 162, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(118, 75, 162, 0.4);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 180px 0 140px;
            background: #1a202c;
            overflow: hidden;
        }

        .hero-bg-shape {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgb(90, 92, 106) 0%, rgb(32, 45, 58) 81.3%);
            z-index: 0;
        }
        
        /* Modern Abstract Circles */
        .shape-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            opacity: 0.4;
        }
        .shape-1 { background: #764ba2; width: 400px; height: 400px; top: -100px; right: 10%; animation: float 6s ease-in-out infinite; }
        .shape-2 { background: #667eea; width: 300px; height: 300px; bottom: -50px; left: 5%; animation: float 8s ease-in-out infinite reverse; }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .badge-pill {
            background: rgba(255, 255, 255, 0.1);
            color: #a3bffa;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            margin-bottom: 25px;
            display: inline-block;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            color: white;
            line-height: 1.1;
            margin-bottom: 25px;
            letter-spacing: -1px;
        }

        .hero-title span {
            background: linear-gradient(to right, #a3bffa, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text {
            color: #cbd5e0;
            font-size: 1.3rem;
            line-height: 1.6;
            margin-bottom: 40px;
            font-weight: 300;
        }

        .btn-cta {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            background: white;
            color: #764ba2;
            transition: all 0.3s;
            border: none;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: #667eea;
        }

        /* Stats Section */
        .stats-container {
            position: relative;
            z-index: 3;
            margin-top: -60px;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }
        
        .stat-icon.blue { background: #ebf4ff; color: #BAE6FD; }
        .stat-icon.purple { background: #faf5ff; color: #805ad5; }
        .stat-icon.green { background: #f0fff4; color: #48bb78; }

        .stat-title {
            font-weight: 700;
            font-size: 1.2rem;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .stat-desc {
            color: #718096;
            font-size: 0.95rem;
        }

        /* Footer */
        footer {
            background: white;
            padding: 40px 0;
            text-align: center;
            margin-top: 80px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
           <a class="navbar-brand" href="#" style="margin-left: 100px;"></a>
            <div class="d-flex ms-auto gap-2">
                <a href="auth/login.php" class="btn btn-auth btn-login">Log In</a>
                <a href="auth/register.php" class="btn btn-auth btn-register">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-bg-shape"></div>
        <div class="shape-circle shape-1"></div>
        <div class="shape-circle shape-2"></div>

        <div class="container">
            <div class="hero-content">
                <img src="assets/img/main.png" alt="Logo" style="height: 120px; margin-bottom: 30px;">
                <h1 class="hero-title">
                    Online Case <br>
                    <span>Management System</span>
                </h1>
                <p class="hero-text">
                    Experience the future of student services. Seamless case tracking, instant approvals, and transparent communication for modern educational institutions.
                </p>
                <a href="auth/register.php" class="btn btn-cta"> <i class="fas fa-rocket me-2"></i> Get Started Now</a>
            </div>
        </div>
    </section>

    <!-- Floating Cards -->
    <section class="stats-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
                <h4 class="stat-title">Instant Submission</h4>
                <p class="stat-desc">Submit academic, hostel, or disciplinary requests in seconds from any device.</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-user-shield"></i></div>
                <h4 class="stat-title">Secure & Private</h4>
                <p class="stat-desc">Role-based access control (RBAC) ensures your data is safe and seen only by authorized staff.</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <h4 class="stat-title">Real-Time Updates</h4>
                <p class="stat-desc">No more waiting. Get notified instantly when your request is approved or rejected.</p>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2026 OCMS College System. Built for Excellence.</p>
        </div>
    </footer>

</body>
</html>

