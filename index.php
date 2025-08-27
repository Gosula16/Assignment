<?php
require_once 'includes/auth.php';
$auth = new Auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-Powered Resume Builder - ATS Optimized</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container flex justify-between items-center">
            <h1>AI Resume Builder</h1>
            <nav>
                <ul class="nav-links">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 0; text-align: center;">
            <div class="container">
                <h1 style="font-size: 3rem; font-weight: 700; margin-bottom: 1rem;">
                    Build ATS-Optimized Resumes with AI
                </h1>
                <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9;">
                    Create professional resumes that pass Applicant Tracking Systems and land you more interviews
                </p>
                <?php if (!$auth->isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        Get Started Free
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Section -->
        <section style="padding: 4rem 0; background: white;">
            <div class="container">
                <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #1f2937;">
                    Why Choose Our AI Resume Builder?
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div class="stat-card">
                        <h3 style="color: #10b981;">🤖 AI-Powered</h3>
                        <h4 style="margin-bottom: 1rem;">Smart Content Generation</h4>
                        <p>Our AI analyzes job descriptions and tailors your resume content to match exactly what employers are looking for.</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3 style="color: #3b82f6;">📊 ATS-Optimized</h3>
                        <h4 style="margin-bottom: 1rem;">Beat the Bots</h4>
                        <p>90%+ of our resumes pass ATS systems with optimized formatting, keywords, and structure that recruiters love.</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3 style="color: #8b5cf6;">⚡ Real-time Scoring</h3>
                        <h4 style="margin-bottom: 1rem;">Instant Feedback</h4>
                        <p>Get immediate ATS scores and suggestions to improve your resume before applying to jobs.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section style="padding: 4rem 0; background: #f8fafc;">
            <div class="container">
                <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #1f2937;">
                    How It Works
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div style="text-align: center; padding: 2rem;">
                        <div style="width: 60px; height: 60px; background: #667eea; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;">1</div>
                        <h4 style="margin-bottom: 1rem;">Paste Job Description</h4>
                        <p style="color: #6b7280;">Copy and paste the job description you're applying for</p>
                    </div>
                    
                    <div style="text-align: center; padding: 2rem;">
                        <div style="width: 60px; height: 60px; background: #667eea; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;">2</div>
                        <h4 style="margin-bottom: 1rem;">AI Tailors Content</h4>
                        <p style="color: #6b7280;">Our AI analyzes keywords and optimizes your resume content</p>
                    </div>
                    
                    <div style="text-align: center; padding: 2rem;">
                        <div style="width: 60px; height: 60px; background: #667eea; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold;">3</div>
                        <h4 style="margin-bottom: 1rem;">Export & Apply</h4>
                        <p style="color: #6b7280;">Download your ATS-optimized resume and start applying</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 0; text-align: center;">
            <div class="container">
                <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Ready to Land Your Dream Job?</h2>
                <p style="font-size: 1.1rem; margin-bottom: 2rem; opacity: 0.9;">
                    Join thousands of job seekers who've improved their interview rates with our AI-powered resume builder
                </p>
                <?php if (!$auth->isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem; background: white; color: #667eea;">
                        Start Building Your Resume
                    </a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer style="background: #1f2937; color: white; padding: 2rem 0; text-align: center;">
        <div class="container">
            <p>&copy; 2024 AI Resume Builder. Built for job seekers who want to win.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>