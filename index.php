<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Case Management System | Official Portal</title>
    <link rel="icon" type="image/png" href="assets/img/OCMS_logo.png">
    
    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Montserrat & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            background-color: white;
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, .outfit {
            font-family: 'times new roman', sans-serif;
            color: var(--primary);
        }

        /* Navbar */
        .navbar {
            padding: 12px 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .navbar-brand img {
            height: 45px;
            transition: var(--transition);
        }

        /* Hero Section */
        .hero {
            padding: 90px 0 60px;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.05), transparent),
                        radial-gradient(circle at bottom left, rgba(15, 23, 42, 0.02), transparent);
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.3;
            z-index: -1;
        }

        .hero h1 {
            font-size: clamp(5.5rem, 5vw, 15rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.04em;
            margin-bottom: 25px;
            background: linear-gradient(to bottom, var(--primary) 0%, var(--accent) 50%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 650px;
            margin: 0 auto 45px;
            font-weight: 500;
        }

        /* Buttons */
        .btn-premium {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 10px;
            transition: var(--transition);
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .btn-primary-p {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            color: white;
            border: none;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.3);
        }

        .btn-primary-p:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.4);
        }

        .btn-secondary-p {
            background-color: white;
            color: var(--primary);
            border: 2px solid #e2e8f0;
        }

        .btn-secondary-p:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.05);
        }

        /* Feature Section */
        .section-padding { padding: 30px 0; }
        
        .section-header { margin-bottom: 20px; }
        .section-header h2 { font-size: 2.5rem; font-weight: 700; margin-bottom: 15px; }
        .section-header .divider { 
            width: 60px; height: 4px; background: var(--accent); margin: 0 auto; border-radius: 2px;
        }

        .f-card {
            background: white;
            padding: 35px 30px;
            border-radius: 24px;
            border: 1px solid #f1f5f9;
            transition: var(--transition);
            height: 100%;
            position: relative;
        }

        .f-card:hover {
            transform: translateY(-12px);
            border-color: white;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.08);
        }

        .f-icon-box {
            width: 64px;
            height: 64px;
            background: var(--bg-light);
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .f-card:hover .f-icon-box {
            background: var(--primary);
            color: white;
        }

        .f-card h4 { font-size: 1.4rem; font-weight: 700; margin-bottom: 15px; }
        .f-card p { color: var(--text-muted); font-size: 1rem; margin-bottom: 0; }

        /* About/Success Section */
        .about-section {
            background-color: var(--bg-light);
            border-radius: 30px;
            margin: 0 40px;
            padding: 30px 40px;
        }

        .about-content { max-width: 800px; margin: 0 auto; }
        .about-tag {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent);
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        /* Footer */
        footer {
            padding: 60px 0 30px;
            background: white;
            border-top: 1px solid #f1f5f9;
        }

        .footer-logo { font-size: 1.6rem; font-weight: 800; color: var(--primary); margin-bottom: 25px; display: block; text-decoration: none; }
        .footer-heading { font-weight: 700; font-size: 1.1rem; margin-bottom: 25px; color: var(--primary); }
        
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: var(--text-muted); text-decoration: none; transition: var(--transition); font-weight: 400; }
        .footer-links a:hover { color: var(--accent); padding-left: 5px; }

        .copyright {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.8rem; }
            .section-padding { padding: 80px 0; }
            .about-section { border-radius: 40px; margin: 0 10px; }
        }
    </style>
</head>
<body>

    <!-- Transparent Navbar -->
    <nav class="navbar sticky-top">
        <div class="container d-flex justify-content-center">
            <!-- Empty navbar for sticky blur effect -->
        </div>
    </nav>

    <!-- Main Hero -->
    <section class="hero text-center" style="padding-top: 50px;">
        <div class="container">
            <div class="mb-4">
                <img src="assets/img/ocmslogo.png" alt="OCMS Logo" style="height: 90px;">
            </div>
            <h1 class="outfit">Online Case<br>Management System</h1>
            <p>The institutional portal for students and staff to manage academic, hostel, and administrative cases with absolute transparency.</p>
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                <a href="#features" class="btn btn-premium btn-secondary-p">
                    <i class="fas fa-compass"></i> Explore More
                </a>
                <a href="auth/login.php" class="btn btn-premium btn-primary-p">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </section>

    <!-- Key Features -->
    <section id="features" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="outfit">Portal Features</h2>
                <div class="divider"></div>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="f-card">
                        <div class="f-icon-box"><i class="fas fa-file-signature"></i></div>
                        <h4 class="outfit">Unified Submission</h4>
                        <p>A single interface for all institutional requests, from academic inquiries to grievance reporting.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="f-card">
                        <div class="f-icon-box"><i class="fas fa-map-location-dot"></i></div>
                        <h4 class="outfit">Live Tracking</h4>
                        <p>Stay informed with step-by-step progress updates as your case moves through the review process.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="f-card">
                        <div class="f-icon-box"><i class="fas fa-user-shield"></i></div>
                        <h4 class="outfit">Secure Review</h4>
                        <p>Robust role-based access ensures that sensitive case data is only accessible to authorized personnel.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Section (Integrated About) -->
    <section class="pb-4">
        <div class="container">
            <div class="about-section text-center">
                <div class="about-content">
                    <span class="about-tag">Institutional Efficiency</span>
                    <h2 class="outfit mb-4" style="font-weight: 700;">About</h2>
                    <p class="mb-0 text-muted" style="font-size: 1.15rem;">
                        OCMS is designed to bridge the communication gap between students and the administration. By digitizing the case management process, we ensure faster resolutions, documented transparency, and a more responsive campus environment for everyone involved.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Footer -->
    <footer>
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <a href="#" class="footer-logo outfit">OCMS</a>
                    <p class="text-muted pe-lg-5">
                        Simplifying institutional case management through secure, transparent, and efficient digital workflows. Trusted by students and staff alike.
                    </p>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-heading outfit">Resources</h5>
                    <ul class="footer-links">
                        <li><a href="auth/login.php">Student Dashboard</a></li>
                        <li><a href="auth/login.php">Staff Portal</a></li>
                        <li><a href="guidelines.php">User Guidelines</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-heading outfit">Institutional Support</h5>
                    <ul class="footer-links">
                        <li><a href="mailto:institute@ocms.com"><i class="fas fa-envelope me-2"></i> institute@ocms.com</a></li>
                        <li><p class="text-muted mb-0"><i class="fas fa-clock me-2"></i> Mon - Sat, 9:00 AM - 5:00 PM</p></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
