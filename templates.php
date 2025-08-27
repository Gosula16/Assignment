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
    <title>Resume Templates - ResumeAI</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/templates.css">
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
                    <a href="templates.php" class="nav-link active">Templates</a>
                    <div class="user-menu">
                        <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                        <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="templates.php" class="nav-link active">Templates</a>
                    <a href="auth/login.php" class="btn btn-outline">Login</a>
                    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Templates Header -->
    <section class="templates-header">
        <div class="container">
            <div class="header-content">
                <h1>ATS-Optimized Resume Templates</h1>
                <p>Choose from professionally designed templates that pass ATS systems and get you interviews</p>
                <div class="header-stats">
                    <div class="stat">
                        <span class="stat-number">12</span>
                        <span class="stat-label">Templates</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">ATS Pass Rate</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">100k+</span>
                        <span class="stat-label">Downloads</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Template Filters -->
    <section class="template-filters">
        <div class="container">
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">Category:</label>
                    <div class="filter-options">
                        <button class="filter-btn active" data-category="all">All Templates</button>
                        <button class="filter-btn" data-category="professional">Professional</button>
                        <button class="filter-btn" data-category="creative">Creative</button>
                        <button class="filter-btn" data-category="executive">Executive</button>
                        <button class="filter-btn" data-category="technical">Technical</button>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Industry:</label>
                    <select class="filter-select" id="industry-filter">
                        <option value="all">All Industries</option>
                        <option value="technology">Technology</option>
                        <option value="finance">Finance</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="marketing">Marketing</option>
                        <option value="education">Education</option>
                        <option value="consulting">Consulting</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Experience Level:</label>
                    <select class="filter-select" id="level-filter">
                        <option value="all">All Levels</option>
                        <option value="entry">Entry Level</option>
                        <option value="mid">Mid Level</option>
                        <option value="senior">Senior Level</option>
                        <option value="executive">Executive</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Templates Grid -->
    <section class="templates-section">
        <div class="container">
            <div class="templates-grid" id="templates-grid">
                
                <!-- Professional Template -->
                <div class="template-card" data-category="professional" data-industry="technology finance" data-level="mid senior">
                    <div class="template-preview">
                        <div class="preview-container">
                            <div class="template-professional">
                                <div class="template-header">
                                    <div class="template-name">JOHN SMITH</div>
                                    <div class="template-title">Senior Software Engineer</div>
                                    <div class="template-contact">john@email.com | (555) 123-4567 | LinkedIn</div>
                                </div>
                                <div class="template-sections">
                                    <div class="template-section">
                                        <div class="section-title">PROFESSIONAL SUMMARY</div>
                                        <div class="section-content">
                                            <div class="content-line"></div>
                                            <div class="content-line"></div>
                                            <div class="content-line short"></div>
                                        </div>
                                    </div>
                                    <div class="template-section">
                                        <div class="section-title">EXPERIENCE</div>
                                        <div class="section-content">
                                            <div class="exp-item">
                                                <div class="exp-header">
                                                    <div class="exp-title">Senior Developer</div>
                                                    <div class="exp-date">2020-Present</div>
                                                </div>
                                                <div class="exp-company">Tech Company Inc.</div>
                                                <div class="content-line"></div>
                                                <div class="content-line short"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-overlay">
                            <div class="template-actions">
                                <button class="btn btn-primary use-template" data-template="professional">
                                    <i class="fas fa-plus"></i>
                                    Use Template
                                </button>
                                <button class="btn btn-outline preview-template" data-template="professional">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3>Professional</h3>
                        <p>Clean and traditional design perfect for corporate roles and established professionals.</p>
                        <div class="template-features">
                            <span class="feature-tag">ATS-Friendly</span>
                            <span class="feature-tag">Corporate</span>
                            <span class="feature-tag">Traditional</span>
                        </div>
                        <div class="template-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>25k+ downloads</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <span>4.9/5 rating</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modern Template -->
                <div class="template-card" data-category="creative" data-industry="technology marketing" data-level="entry mid">
                    <div class="template-preview">
                        <div class="preview-container">
                            <div class="template-modern">
                                <div class="template-sidebar">
                                    <div class="sidebar-header">
                                        <div class="template-name">JANE DOE</div>
                                        <div class="template-title">UX Designer</div>
                                    </div>
                                    <div class="sidebar-section">
                                        <div class="section-title">CONTACT</div>
                                        <div class="contact-item">jane@email.com</div>
                                        <div class="contact-item">(555) 987-6543</div>
                                    </div>
                                    <div class="sidebar-section">
                                        <div class="section-title">SKILLS</div>
                                        <div class="skill-item">UI/UX Design</div>
                                        <div class="skill-item">Figma</div>
                                        <div class="skill-item">Adobe Creative</div>
                                    </div>
                                </div>
                                <div class="template-main">
                                    <div class="template-section">
                                        <div class="section-title">EXPERIENCE</div>
                                        <div class="exp-item">
                                            <div class="exp-title">UX Designer</div>
                                            <div class="exp-company">Design Studio</div>
                                            <div class="content-line"></div>
                                            <div class="content-line short"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-overlay">
                            <div class="template-actions">
                                <button class="btn btn-primary use-template" data-template="modern">
                                    <i class="fas fa-plus"></i>
                                    Use Template
                                </button>
                                <button class="btn btn-outline preview-template" data-template="modern">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3>Modern</h3>
                        <p>Contemporary two-column layout ideal for creative and tech professionals.</p>
                        <div class="template-features">
                            <span class="feature-tag">Two-Column</span>
                            <span class="feature-tag">Creative</span>
                            <span class="feature-tag">Modern</span>
                        </div>
                        <div class="template-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>18k+ downloads</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <span>4.8/5 rating</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimal Template -->
                <div class="template-card" data-category="professional" data-industry="all" data-level="all">
                    <div class="template-preview">
                        <div class="preview-container">
                            <div class="template-minimal">
                                <div class="template-header">
                                    <div class="template-name">ALEX JOHNSON</div>
                                    <div class="template-contact">alex@email.com • (555) 456-7890 • linkedin.com/in/alex</div>
                                </div>
                                <div class="template-sections">
                                    <div class="template-section">
                                        <div class="section-title">SUMMARY</div>
                                        <div class="section-content">
                                            <div class="content-line"></div>
                                            <div class="content-line"></div>
                                        </div>
                                    </div>
                                    <div class="template-section">
                                        <div class="section-title">EXPERIENCE</div>
                                        <div class="section-content">
                                            <div class="exp-item">
                                                <div class="exp-header">
                                                    <div class="exp-left">
                                                        <div class="exp-title">Project Manager</div>
                                                        <div class="exp-company">Consulting Firm</div>
                                                    </div>
                                                    <div class="exp-date">2019-2024</div>
                                                </div>
                                                <div class="content-line"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-overlay">
                            <div class="template-actions">
                                <button class="btn btn-primary use-template" data-template="minimal">
                                    <i class="fas fa-plus"></i>
                                    Use Template
                                </button>
                                <button class="btn btn-outline preview-template" data-template="minimal">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3>Minimal</h3>
                        <p>Clean and simple design that focuses on content with maximum readability.</p>
                        <div class="template-features">
                            <span class="feature-tag">Clean</span>
                            <span class="feature-tag">Simple</span>
                            <span class="feature-tag">Versatile</span>
                        </div>
                        <div class="template-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>22k+ downloads</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <span>4.7/5 rating</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Executive Template -->
                <div class="template-card" data-category="executive" data-industry="finance consulting" data-level="senior executive">
                    <div class="template-preview">
                        <div class="preview-container">
                            <div class="template-executive">
                                <div class="template-header">
                                    <div class="header-left">
                                        <div class="template-name">MICHAEL BROWN</div>
                                        <div class="template-title">Chief Technology Officer</div>
                                    </div>
                                    <div class="header-right">
                                        <div class="contact-line">michael@email.com</div>
                                        <div class="contact-line">(555) 321-9876</div>
                                        <div class="contact-line">New York, NY</div>
                                    </div>
                                </div>
                                <div class="template-sections">
                                    <div class="template-section">
                                        <div class="section-title">EXECUTIVE SUMMARY</div>
                                        <div class="section-content">
                                            <div class="content-line"></div>
                                            <div class="content-line"></div>
                                            <div class="content-line short"></div>
                                        </div>
                                    </div>
                                    <div class="template-section">
                                        <div class="section-title">PROFESSIONAL EXPERIENCE</div>
                                        <div class="section-content">
                                            <div class="exp-item">
                                                <div class="exp-title">Chief Technology Officer</div>
                                                <div class="exp-meta">Fortune 500 Company • 2018-Present</div>
                                                <div class="content-line"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-overlay">
                            <div class="template-actions">
                                <button class="btn btn-primary use-template" data-template="executive">
                                    <i class="fas fa-plus"></i>
                                    Use Template
                                </button>
                                <button class="btn btn-outline preview-template" data-template="executive">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3>Executive</h3>
                        <p>Sophisticated layout designed for C-level executives and senior leadership roles.</p>
                        <div class="template-features">
                            <span class="feature-tag">Executive</span>
                            <span class="feature-tag">Leadership</span>
                            <span class="feature-tag">Premium</span>
                        </div>
                        <div class="template-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>12k+ downloads</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <span>4.9/5 rating</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Template -->
                <div class="template-card" data-category="technical" data-industry="technology" data-level="entry mid senior">
                    <div class="template-preview">
                        <div class="preview-container">
                            <div class="template-technical">
                                <div class="template-header">
                                    <div class="template-name">SARAH WILSON</div>
                                    <div class="template-title">Full Stack Developer</div>
                                    <div class="template-contact">sarah@email.com | github.com/sarah | portfolio.com</div>
                                </div>
                                <div class="template-sections">
                                    <div class="two-column">
                                        <div class="left-column">
                                            <div class="template-section">
                                                <div class="section-title">TECHNICAL SKILLS</div>
                                                <div class="skills-grid">
                                                    <div class="skill-category">
                                                        <div class="skill-cat-title">Languages</div>
                                                        <div class="skill-items">JavaScript, Python, Java</div>
                                                    </div>
                                                    <div class="skill-category">
                                                        <div class="skill-cat-title">Frameworks</div>
                                                        <div class="skill-items">React, Node.js, Django</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="right-column">
                                            <div class="template-section">
                                                <div class="section-title">EXPERIENCE</div>
                                                <div class="exp-item">
                                                    <div class="exp-title">Senior Developer</div>
                                                    <div class="exp-company">Tech Startup</div>
                                                    <div class="content-line"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-overlay">
                            <div class="template-actions">
                                <button class="btn btn-primary use-template" data-template="technical">
                                    <i class="fas fa-plus"></i>
                                    Use Template
                                </button>
                                <button class="btn btn-outline preview-template" data-template="technical">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3>Technical</h3>
                        <p>Specialized layout for developers and technical professionals with skill emphasis.</p>
                        <div class="template-features">
                            <span class="feature-tag">Developer</span>
                            <span class="feature-tag">Skills-Focused</span>
                            <span class="feature-tag">Technical</span>
                        </div>
                        <div class="template-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>15k+ downloads</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <span>4.8/5 rating</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Creative Template -->
                <div class="template-card" data-category="creative" data-industry="marketing education" data-level="entry mid">
                    <div class="template-preview">
                        <div class="preview-container">
                            <div class="template-creative">
                                <div class="template-header">
                                    <div class="header-design">
                                        <div class="template-name">EMMA DAVIS</div>
                                        <div class="template-title">Marketing Manager</div>
                                        <div class="header-line"></div>
                                    </div>
                                </div>
                                <div class="template-sections">
                                    <div class="template-section">
                                        <div class="section-title">PROFILE</div>
                                        <div class="section-content">
                                            <div class="content-line"></div>
                                            <div class="content-line short"></div>
                                        </div>
                                    </div>
                                    <div class="template-section">
                                        <div class="section-title">EXPERIENCE</div>
                                        <div class="section-content">
                                            <div class="exp-item">
                                                <div class="exp-badge">2022-2024</div>
                                                <div class="exp-content">
                                                    <div class="exp-title">Marketing Manager</div>
                                                    <div class="exp-company">Creative Agency</div>
                                                    <div class="content-line"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="template-overlay">
                            <div class="template-actions">
                                <button class="btn btn-primary use-template" data-template="creative">
                                    <i class="fas fa-plus"></i>
                                    Use Template
                                </button>
                                <button class="btn btn-outline preview-template" data-template="creative">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3>Creative</h3>
                        <p>Eye-catching design for creative professionals in marketing, design, and media.</p>
                        <div class="template-features">
                            <span class="feature-tag">Creative</span>
                            <span class="feature-tag">Colorful</span>
                            <span class="feature-tag">Unique</span>
                        </div>
                        <div class="template-stats">
                            <div class="stat-item">
                                <i class="fas fa-download"></i>
                                <span>14k+ downloads</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-star"></i>
                                <span>4.6/5 rating</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Create Your Perfect Resume?</h2>
                <p>Choose a template and start building your ATS-optimized resume in minutes</p>
                <?php if (!$isLoggedIn): ?>
                    <a href="auth/register.php" class="btn btn-primary btn-large">
                        Get Started for Free
                    </a>
                <?php else: ?>
                    <a href="resume-builder.php" class="btn btn-primary btn-large">
                        Create Resume Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Template Preview Modal -->
    <div class="modal" id="template-preview-modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Template Preview</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-container-large" id="template-preview-content">
                    <!-- Template preview will be loaded here -->
                </div>
                <div class="preview-actions">
                    <button class="btn btn-outline" id="close-preview">Close</button>
                    <button class="btn btn-primary" id="use-previewed-template">
                        <i class="fas fa-plus"></i>
                        Use This Template
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                        <h4>Templates</h4>
                        <a href="#professional">Professional</a>
                        <a href="#creative">Creative</a>
                        <a href="#executive">Executive</a>
                        <a href="#technical">Technical</a>
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
    <script src="assets/js/templates.js"></script>
</body>
</html>