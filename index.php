<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $db = new Database();
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-Powered Resume Builder | ATS Optimized</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-file-alt"></i>
                <span>ResumeAI</span>
            </div>
            <div class="nav-menu">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="resume-builder.php" class="nav-link">Create Resume</a>
                    <a href="templates.php" class="nav-link">Templates</a>
                    <div class="user-menu">
                        <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                        <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#templates" class="nav-link">Templates</a>
                    <a href="#pricing" class="nav-link">Pricing</a>
                    <a href="auth/login.php" class="btn btn-outline">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
            <div class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">
                    Create <span class="highlight">ATS-Optimized</span> Resumes with AI
                </h1>
                <p class="hero-subtitle">
                    Build professional resumes that pass ATS systems and land you interviews. 
                    AI-powered tailoring, real-time scoring, and expert templates.
                </p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">90%+</span>
                        <span class="stat-label">ATS Pass Rate</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">15min</span>
                        <span class="stat-label">Average Build Time</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">50k+</span>
                        <span class="stat-label">Resumes Created</span>
                    </div>
                </div>
                <div class="hero-actions">
                    <?php if (!$isLoggedIn): ?>
                        <a href="auth/register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-rocket"></i>
                            Start Building Free
                        </a>
                        <a href="#demo" class="btn btn-secondary btn-large">
                            <i class="fas fa-play"></i>
                            Watch Demo
                        </a>
                    <?php else: ?>
                        <a href="resume-builder.php" class="btn btn-primary btn-large">
                            <i class="fas fa-plus"></i>
                            Create New Resume
                        </a>
                        <a href="dashboard.php" class="btn btn-secondary btn-large">
                            <i class="fas fa-tachometer-alt"></i>
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="resume-preview">
                    <div class="resume-header">
                        <div class="resume-name">John Doe</div>
                        <div class="resume-title">Senior Software Engineer</div>
                    </div>
                    <div class="resume-sections">
                        <div class="resume-section">
                            <div class="section-title">Experience</div>
                            <div class="section-content"></div>
                        </div>
                        <div class="resume-section">
                            <div class="section-title">Skills</div>
                            <div class="section-content"></div>
                        </div>
                    </div>
                    <div class="ats-score">
                        <div class="score-circle">
                            <span class="score-number">92</span>
                            <span class="score-label">ATS Score</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Powerful Features for Job Success</h2>
                <p>Everything you need to create resumes that get noticed</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>AI-Powered Tailoring</h3>
                    <p>Automatically customize your resume for each job description with intelligent keyword optimization.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Real-time ATS Scoring</h3>
                    <p>Get instant feedback on your resume's ATS compatibility with detailed scoring and recommendations.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Job Description Parser</h3>
                    <p>Extract key requirements and skills from job postings to match your resume perfectly.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3>Multiple Export Formats</h3>
                    <p>Download your resume in PDF, DOCX, or JSON formats, all optimized for ATS systems.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Version Management</h3>
                    <p>Keep track of different resume versions and easily switch between them for different applications.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Collaboration Tools</h3>
                    <p>Share your resume with coaches and peers for feedback and inline comments.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Templates Section -->
    <section id="templates" class="templates">
        <div class="container">
            <div class="section-header">
                <h2>ATS-Optimized Templates</h2>
                <p>Choose from professionally designed templates that pass ATS systems</p>
            </div>
            <div class="templates-grid">
                <div class="template-card">
                    <div class="template-preview professional"></div>
                    <h3>Professional</h3>
                    <p>Clean and traditional design perfect for corporate roles</p>
                </div>
                <div class="template-card">
                    <div class="template-preview modern"></div>
                    <h3>Modern</h3>
                    <p>Contemporary layout ideal for tech and creative positions</p>
                </div>
                <div class="template-card">
                    <div class="template-preview minimal"></div>
                    <h3>Minimal</h3>
                    <p>Simple and elegant design that highlights your content</p>
                </div>
                <div class="template-card">
                    <div class="template-preview executive"></div>
                    <h3>Executive</h3>
                    <p>Sophisticated layout for senior-level positions</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Land Your Dream Job?</h2>
                <p>Join thousands of professionals who've improved their job search with our AI-powered resume builder.</p>
                <?php if (!$isLoggedIn): ?>
                    <a href="auth/register.php" class="btn btn-primary btn-large">
                        Get Started for Free
                    </a>
                <?php else: ?>
                    <a href="resume-builder.php" class="btn btn-primary btn-large">
                        Create Your Resume Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="nav-brand">
                        <i class="fas fa-file-alt"></i>
                        <span>ResumeAI</span>
                    </div>
                    <p>AI-powered resume builder for the modern job seeker</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Product</h4>
                        <a href="templates.php">Templates</a>
                        <a href="#features">Features</a>
                        <a href="#pricing">Pricing</a>
                    </div>
                    <div class="footer-column">
                        <h4>Support</h4>
                        <a href="#help">Help Center</a>
                        <a href="#contact">Contact</a>
                        <a href="#privacy">Privacy</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 ResumeAI. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>