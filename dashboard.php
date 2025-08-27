<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$db = new Database();
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

// Get user's resumes
$resumes = $db->fetchAll(
    "SELECT r.*, COUNT(s.id) as section_count 
     FROM resumes r 
     LEFT JOIN sections s ON r.id = s.resume_id 
     WHERE r.user_id = ? 
     GROUP BY r.id 
     ORDER BY r.updated_at DESC",
    [$_SESSION['user_id']]
);

// Get recent job descriptions
$jobDescriptions = $db->fetchAll(
    "SELECT * FROM job_descriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$_SESSION['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ResumeAI</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="resume-builder.php" class="nav-link">Create Resume</a>
                <a href="templates.php" class="nav-link">Templates</a>
                <div class="user-menu">
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Dashboard</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="#overview" class="sidebar-link active">
                    <i class="fas fa-chart-line"></i>
                    Overview
                </a>
                <a href="#resumes" class="sidebar-link">
                    <i class="fas fa-file-alt"></i>
                    My Resumes
                </a>
                <a href="#job-descriptions" class="sidebar-link">
                    <i class="fas fa-briefcase"></i>
                    Job Descriptions
                </a>
                <a href="#analytics" class="sidebar-link">
                    <i class="fas fa-analytics"></i>
                    Analytics
                </a>
                <a href="#settings" class="sidebar-link">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Header -->
            <div class="dashboard-header">
                <div class="header-content">
                    <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
                    <p>Here's what's happening with your resumes</p>
                </div>
                <div class="header-actions">
                    <a href="resume-builder.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        New Resume
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($resumes) ?></h3>
                        <p>Total Resumes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= !empty($resumes) ? round(array_sum(array_column($resumes, 'ats_score')) / count($resumes)) : 0 ?></h3>
                        <p>Avg ATS Score</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($jobDescriptions) ?></h3>
                        <p>Job Descriptions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Downloads</p>
                    </div>
                </div>
            </div>

            <!-- Recent Resumes -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Resumes</h2>
                    <a href="resume-builder.php" class="btn btn-outline">Create New</a>
                </div>
                <div class="resumes-grid">
                    <?php if (empty($resumes)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3>No resumes yet</h3>
                            <p>Create your first AI-powered resume to get started</p>
                            <a href="resume-builder.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Create Resume
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($resumes as $resume): ?>
                            <div class="resume-card">
                                <div class="resume-header">
                                    <h3><?= htmlspecialchars($resume['title']) ?></h3>
                                    <div class="resume-actions">
                                        <button class="btn-icon" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn-icon" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="resume-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-briefcase"></i>
                                        <?= htmlspecialchars($resume['target_role'] ?: 'No target role') ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <?= date('M j, Y', strtotime($resume['updated_at'])) ?>
                                    </span>
                                </div>
                                <div class="resume-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">ATS Score</span>
                                        <div class="score-badge score-<?= $resume['ats_score'] >= 80 ? 'high' : ($resume['ats_score'] >= 60 ? 'medium' : 'low') ?>">
                                            <?= $resume['ats_score'] ?>%
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Sections</span>
                                        <span class="stat-value"><?= $resume['section_count'] ?></span>
                                    </div>
                                </div>
                                <div class="resume-actions-bottom">
                                    <a href="resume-builder.php?id=<?= $resume['id'] ?>" class="btn btn-primary btn-sm">
                                        Edit Resume
                                    </a>
                                    <a href="resume-preview.php?id=<?= $resume['id'] ?>" class="btn btn-outline btn-sm">
                                        Preview
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="quick-actions">
                    <a href="resume-builder.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3>Create Resume</h3>
                        <p>Start building a new ATS-optimized resume</p>
                    </a>
                    <a href="job-description-parser.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Parse Job Description</h3>
                        <p>Extract keywords from job postings</p>
                    </a>
                    <a href="templates.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h3>Browse Templates</h3>
                        <p>Choose from professional resume templates</p>
                    </a>
                    <a href="ats-checker.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>ATS Checker</h3>
                        <p>Test your resume's ATS compatibility</p>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>