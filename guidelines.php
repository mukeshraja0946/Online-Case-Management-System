<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Guidelines | OCMS</title>
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
            background-color: var(--bg-light);
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
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .navbar-brand img {
            height: 45px;
            transition: var(--transition);
        }

        /* Content Section */
        .content-section {
            padding: 60px 0;
        }

        .content-card {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            margin-bottom: 40px;
        }

        .content-card h2 {
            margin-top: 35px;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .content-card p {
            color: var(--text-muted);
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        .content-card ul {
            color: var(--text-muted);
            margin-bottom: 20px;
            font-size: 1.05rem;
        }
        
        .content-card li {
            margin-bottom: 8px;
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
    </style>
</head>
<body>

    <!-- Transparent Navbar -->
    <nav class="navbar sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="index.php">
                <h3 class="outfit mb-0" style="color: var(--primary); font-weight: bold;">OCMS</h3>
            </a>
            <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Back to Home</a>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="content-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="content-card">
                        <h1 class="outfit mb-4 text-center">User Guidelines</h1>
                        <hr class="mb-5" style="opacity: 0.1;">

                        <p>Welcome to the Online Case Management System (OCMS). These guidelines are designed to help you navigate and utilize the system effectively. By using OCMS, you agree to adhere to these rules and procedures.</p>

                        <h2 class="outfit">1. Account Security</h2>
                        <p>Your account is personal and highly confidential. Please ensure that you do not share your login credentials with anyone.</p>
                        <ul>
                            <li>Always log out when using public or shared computers.</li>
                            <li>Use a strong password and update it periodically.</li>
                            <li>If you suspect any unauthorized access, report it to the administration immediately.</li>
                        </ul>

                        <h2 class="outfit">2. Submitting Cases</h2>
                        <p>When submitting a case or request, provide as much accurate and detailed information as possible to facilitate a swift resolution.</p>
                        <ul>
                            <li>Select the correct category for your case (Academic, Hostel, Administrative, etc.).</li>
                            <li>Attach relevant documents or evidence if applicable.</li>
                            <li>Maintain a respectful and professional tone in all descriptions and communications.</li>
                        </ul>

                        <h2 class="outfit">3. Code of Conduct</h2>
                        <p>All users must maintain a professional and respectful demeanor when interacting with staff or submitting grievances.</p>
                        <ul>
                            <li>Do not submit false, misleading, or frivolous cases.</li>
                            <li>Abusive language or threats will not be tolerated and may lead to disciplinary action.</li>
                        </ul>

                        <h2 class="outfit">4. Response Times & Tracking</h2>
                        <p>Cases are reviewed in the order they are received and based on urgency.</p>
                        <ul>
                            <li>You can track the status of your case via your Student Dashboard.</li>
                            <li>Please allow the stipulated time frame for staff to review and respond before raising duplicate cases.</li>
                        </ul>

                        <h2 class="outfit">5. System Usage</h2>
                        <p>The OCMS is intended solely for institutional purposes.</p>
                        <ul>
                            <li>Unauthorized attempts to bypass security, access restricted areas, or manipulate system data are strictly prohibited.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern Footer -->
    <footer>
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <a href="index.php" class="footer-logo outfit">OCMS</a>
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
            <div class="copyright text-center">
                &copy; 2026 Online Case Management System. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
