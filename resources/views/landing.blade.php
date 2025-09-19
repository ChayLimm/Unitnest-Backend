<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnitNest - Automated POS for Landlords</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --blue-dark: #1d4ed8;
            --blue-light: #3b82f6;
            --blue-bg: #dbeafe;
            --gray-light: #f3f4f6;
            --gray-dark: #4b5563;
            --text-dark: #1f2937;
            --accent-teal: #0ea5e9;
            --accent-purple: #6366f1;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #f8fafc;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 24px;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--gray-dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary-blue);
        }
        
        /* Hero Section */
        .hero {
            padding: 160px 0 80px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            max-width: 600px;
            z-index: 2;
            position: relative;
        }
        
        .hero h1 {
            font-size: 3.2rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-teal) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.2rem;
            color: var(--gray-dark);
            margin-bottom: 40px;
            max-width: 500px;
        }
        
        .development-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--accent-teal) 0%, var(--accent-purple) 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 30px;
            font-size: 0.9rem;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }
        
        .development-badge i {
            margin-right: 8px;
        }
        
        .hero-image {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 50%;
            max-width: 600px;
            z-index: 1;
        }
        
        /* Features */
        .features {
            padding: 100px 0;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            color: var(--primary-blue);
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .section-subtitle {
            text-align: center;
            color: var(--gray-dark);
            max-width: 700px;
            margin: 0 auto 60px;
            font-size: 1.2rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-blue), var(--accent-teal));
        }
        
        .feature-icon {
            background: var(--blue-light);
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 24px;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .feature-card p {
            color: var(--gray-dark);
        }
        
        /* Coming Soon */
        .coming-soon {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-teal) 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .coming-soon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ffffff' fill-opacity='0.1' d='M0,128L48,117.3C96,107,192,85,288,112C384,139,480,213,576,218.7C672,224,768,160,864,138.7C960,117,1056,139,1152,149.3C1248,160,1344,160,1392,160L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-position: center;
        }
        
        .coming-soon-content {
            position: relative;
            z-index: 2;
        }
        
        .coming-soon h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .countdown {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 40px 0;
        }
        
        .countdown-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 25px;
            border-radius: 16px;
            min-width: 120px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .countdown-number {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        /* CTA */
        .cta {
            padding: 100px 0;
            background-color: white;
            text-align: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-teal) 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        
        .btn i {
            margin-right: 10px;
        }
        
        /* Footer */
        footer {
            background-color: var(--text-dark);
            color: white;
            padding: 60px 0 30px;
            text-align: center;
        }
        
        .footer-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }
        
        .footer-links a {
            color: var(--blue-light);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .copyright {
            margin-top: 30px;
            color: var(--gray-light);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .hero-image {
                position: relative;
                transform: none;
                width: 100%;
                max-width: 500px;
                margin: 40px auto 0;
                display: block;
            }
            
            .hero {
                padding: 140px 0 60px;
                text-align: center;
            }
            
            .hero-content {
                max-width: 100%;
            }
            
            .hero p {
                margin: 0 auto 40px;
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .countdown {
                flex-wrap: wrap;
            }
            
            .nav-links {
                display: none;
            }
            
            .feature-card {
                padding: 25px;
            }
        }
        
        /* Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: float 5s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <i class="fas fa-home"></i>
                    <span>UnitNest</span>
                </div>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#coming-soon">Coming Soon</a>
                    <a href="#waitlist">Join Waitlist</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="development-badge">
                    <i class="fas fa-tools"></i> In Development - Coming Soon!
                </div>
                <h1>Automate Property Management with Telegram</h1>
                <p>UnitNest is a revolutionary POS system designed for landlords to track payments, manage bills, and communicate with tenants seamlessly through Telegram.</p>
                <a href="#waitlist" class="btn">
                    <i class="fas fa-plus"></i> Join Waitlist
                </a>
            </div>
        </div>
        <div class="hero-image floating">
            <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
                <rect x="50" y="50" width="500" height="300" rx="20" fill="#2563eb" opacity="0.1" />
                <rect x="80" y="80" width="440" height="180" rx="10" fill="white" />
                <circle cx="110" cy="110" r="15" fill="#3b82f6" />
                <rect x="135" y="100" width="120" height="10" rx="5" fill="#dbeafe" />
                <rect x="270" y="100" width="80" height="10" rx="5" fill="#dbeafe" />
                <rect x="80" y="140" width="440" height="2" fill="#f3f4f6" />
                
                <circle cx="100" cy="180" r="8" fill="#0ea5e9" />
                <rect x="115" y="175" width="150" height="6" rx="3" fill="#e6f0ff" />
                <circle cx="100" cy="200" r="8" fill="#6366f1" />
                <rect x="115" y="195" width="120" height="6" rx="3" fill="#e6f0ff" />
                <circle cx="100" cy="220" r="8" fill="#10b981" />
                <rect x="115" y="215" width="180" height="6" rx="3" fill="#e6f0ff" />
                
                <rect x="300" y="175" width="120" height="40" rx="10" fill="#3b82f6" />
                <text x="360" y="200" text-anchor="middle" fill="white" font-family="Inter" font-size="12">Pay Now</text>
                
                <circle cx="500" cy="270" r="50" fill="#0ea5e9" />
                <path d="M480 270 L495 285 L520 255" stroke="white" stroke-width="8" fill="none" stroke-linecap="round" />
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">How UnitNest Works</h2>
            <p class="section-subtitle">Our automated POS system integrates with Telegram to simplify property management and payment tracking</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <h3>Telegram Bot Integration</h3>
                    <p>Manage your properties, send invoices, and receive payments directly through Telegram with our intelligent bot.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Automated Billing</h3>
                    <p>Automatically generate and send invoices to tenants, track payments, and send reminders for overdue bills.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Financial Reports</h3>
                    <p>Get detailed financial reports and analytics for each property to help you make informed decisions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Coming Soon Section -->
    <section class="coming-soon" id="coming-soon">
        <div class="container">
            <div class="coming-soon-content">
                <h2>We're Working Hard to Launch UnitNest</h2>
                <p>Our team is currently developing the platform with cutting-edge technology to ensure the best experience.</p>
                
                <div class="countdown">
                    <div class="countdown-item">
                        <div class="countdown-number">45</div>
                        <div>Days</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-number">12</div>
                        <div>Hours</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-number">30</div>
                        <div>Minutes</div>
                    </div>
                    <div class="countdown-item">
                        <div class="countdown-number">22</div>
                        <div>Seconds</div>
                    </div>
                </div>
                
                <p>Be among the first to know when we launch!</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="waitlist">
        <div class="container">
            <h2 class="section-title">Get Notified at Launch</h2>
            <p class="section-subtitle">Join our waiting list to get early access and exclusive benefits</p>
            
            <form style="max-width: 500px; margin: 40px auto;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <input type="email" placeholder="Your email address" style="flex: 1; min-width: 250px; padding: 16px; border: 1px solid #ddd; border-radius: 12px; font-size: 1rem;">
                    <button class="btn">Notify Me</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-home"></i> UnitNest
                </div>
                <p>Automated POS for landlords with Telegram integration</p>
                
                <div class="footer-links">
                    <a href="#features">Features</a>
                    <a href="#coming-soon">Coming Soon</a>
                    <a href="#waitlist">Waitlist</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                
                <p class="copyright">© 2023 UnitNest. All rights reserved. Property management reimagined.</p>
                <p style="color: #9ca3af; margin-top: 10px; font-size: 0.9rem;">
                    UnitNest is currently in development. Features and specifications may change prior to launch.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Simple countdown animation (for demonstration only)
        function updateCountdown() {
            const days = document.querySelector('.countdown-item:nth-child(1) .countdown-number');
            const hours = document.querySelector('.countdown-item:nth-child(2) .countdown-number');
            const minutes = document.querySelector('.countdown-item:nth-child(3) .countdown-number');
            const seconds = document.querySelector('.countdown-item:nth-child(4) .countdown-number');
            
            let sec = parseInt(seconds.innerText);
            let min = parseInt(minutes.innerText);
            let hr = parseInt(hours.innerText);
            let d = parseInt(days.innerText);
            
            sec--;
            
            if (sec < 0) {
                sec = 59;
                min--;
                
                if (min < 0) {
                    min = 59;
                    hr--;
                    
                    if (hr < 0) {
                        hr = 23;
                        d--;
                        
                        if (d < 0) {
                            d = 0;
                            hr = 0;
                            min = 0;
                            sec = 0;
                        }
                    }
                }
            }
            
            seconds.innerText = sec.toString().padStart(2, '0');
            minutes.innerText = min.toString().padStart(2, '0');
            hours.innerText = hr.toString().padStart(2, '0');
            days.innerText = d.toString().padStart(2, '0');
        }
        
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>