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

// Check if editing existing resume
$resumeId = $_GET['id'] ?? null;
$resume = null;
$sections = [];

if ($resumeId) {
    $resume = $db->fetch("SELECT * FROM resumes WHERE id = ? AND user_id = ?", [$resumeId, $_SESSION['user_id']]);
    if ($resume) {
        $sections = $db->fetchAll("SELECT * FROM sections WHERE resume_id = ? ORDER BY position, id", [$resumeId]);
        
        // Group sections by type
        $groupedSections = [];
        foreach ($sections as $section) {
            $groupedSections[$section['type']][] = $section;
        }
        $sections = $groupedSections;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $resume ? 'Edit Resume' : 'Create Resume' ?> - ResumeAI</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/resume-builder.css">
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="resume-builder.php" class="nav-link active">Create Resume</a>
                <a href="templates.php" class="nav-link">Templates</a>
                <div class="user-menu">
                    <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="auth/logout.php" class="btn btn-outline">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="builder-container">
        <!-- Builder Sidebar -->
        <aside class="builder-sidebar">
            <div class="sidebar-header">
                <h3><?= $resume ? 'Edit Resume' : 'Create Resume' ?></h3>
                <div class="resume-actions">
                    <button class="btn btn-outline btn-sm" id="save-resume">
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                    <button class="btn btn-primary btn-sm" id="preview-resume">
                        <i class="fas fa-eye"></i>
                        Preview
                    </button>
                </div>
            </div>

            <!-- Resume Settings -->
            <div class="sidebar-section">
                <h4>Resume Settings</h4>
                <div class="form-group">
                    <label for="resume-title" class="form-label">Resume Title</label>
                    <input type="text" id="resume-title" class="form-input" 
                           value="<?= htmlspecialchars($resume['title'] ?? 'My Resume') ?>" 
                           placeholder="e.g., Software Engineer Resume">
                </div>
                <div class="form-group">
                    <label for="target-role" class="form-label">Target Role</label>
                    <input type="text" id="target-role" class="form-input" 
                           value="<?= htmlspecialchars($resume['target_role'] ?? '') ?>" 
                           placeholder="e.g., Senior Software Engineer">
                </div>
                <div class="form-group">
                    <label for="template-select" class="form-label">Template</label>
                    <select id="template-select" class="form-input">
                        <option value="professional">Professional</option>
                        <option value="modern">Modern</option>
                        <option value="minimal">Minimal</option>
                        <option value="executive">Executive</option>
                    </select>
                </div>
            </div>

            <!-- Section Controls -->
            <div class="sidebar-section">
                <h4>Resume Sections</h4>
                <div class="section-controls">
                    <button class="section-btn" data-section="summary">
                        <i class="fas fa-user"></i>
                        <span>Summary</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="section-btn" data-section="experience">
                        <i class="fas fa-briefcase"></i>
                        <span>Experience</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="section-btn" data-section="education">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Education</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="section-btn" data-section="skills">
                        <i class="fas fa-cogs"></i>
                        <span>Skills</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="section-btn" data-section="projects">
                        <i class="fas fa-project-diagram"></i>
                        <span>Projects</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- AI Tools -->
            <div class="sidebar-section">
                <h4>AI Tools</h4>
                <div class="ai-tools">
                    <button class="btn btn-secondary btn-sm" id="ai-optimize">
                        <i class="fas fa-magic"></i>
                        AI Optimize
                    </button>
                    <button class="btn btn-secondary btn-sm" id="parse-jd">
                        <i class="fas fa-search"></i>
                        Parse Job Description
                    </button>
                    <button class="btn btn-secondary btn-sm" id="ats-score">
                        <i class="fas fa-chart-line"></i>
                        ATS Score
                    </button>
                </div>
            </div>

            <!-- Export Options -->
            <div class="sidebar-section">
                <h4>Export</h4>
                <div class="export-options">
                    <button class="btn btn-outline btn-sm" id="export-pdf">
                        <i class="fas fa-file-pdf"></i>
                        PDF
                    </button>
                    <button class="btn btn-outline btn-sm" id="export-docx">
                        <i class="fas fa-file-word"></i>
                        DOCX
                    </button>
                    <button class="btn btn-outline btn-sm" id="export-json">
                        <i class="fas fa-file-code"></i>
                        JSON
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Editor -->
        <main class="builder-main">
            <!-- Editor Header -->
            <div class="editor-header">
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <span><?= $resume ? 'Edit Resume' : 'Create Resume' ?></span>
                </div>
                <div class="editor-actions">
                    <div class="ats-score-display" id="ats-score-display">
                        <span class="score-label">ATS Score:</span>
                        <span class="score-value" id="current-ats-score"><?= $resume['ats_score'] ?? 0 ?></span>
                    </div>
                </div>
            </div>

            <!-- Resume Editor -->
            <div class="resume-editor" id="resume-editor">
                <!-- Personal Information -->
                <section class="editor-section" id="personal-info-section">
                    <div class="section-header">
                        <h3>Personal Information</h3>
                    </div>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" id="full-name" class="form-input" placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Professional Title</label>
                                <input type="text" id="professional-title" class="form-input" placeholder="Software Engineer">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" id="email" class="form-input" placeholder="john@example.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" id="phone" class="form-input" placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" id="location" class="form-input" placeholder="New York, NY">
                            </div>
                            <div class="form-group">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" id="linkedin" class="form-input" placeholder="https://linkedin.com/in/johndoe">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Dynamic Sections Container -->
                <div id="dynamic-sections">
                    <!-- Sections will be dynamically added here -->
                </div>

                <!-- Add Section Button -->
                <div class="add-section-container">
                    <button class="btn btn-outline" id="add-section-btn">
                        <i class="fas fa-plus"></i>
                        Add Section
                    </button>
                </div>
            </div>
        </main>

        <!-- Preview Panel -->
        <aside class="preview-panel" id="preview-panel">
            <div class="preview-header">
                <h3>Live Preview</h3>
                <button class="btn btn-outline btn-sm" id="close-preview">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="preview-content" id="preview-content">
                <!-- Live preview will be rendered here -->
                <div class="preview-placeholder">
                    <i class="fas fa-eye"></i>
                    <p>Click "Preview" to see your resume</p>
                </div>
            </div>
        </aside>
    </div>

    <!-- Modals -->
    <!-- Job Description Parser Modal -->
    <div class="modal" id="jd-parser-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Parse Job Description</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Paste Job Description</label>
                    <textarea id="job-description-text" class="form-input" rows="10" 
                              placeholder="Paste the job description here..."></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn-outline" id="cancel-jd-parse">Cancel</button>
                    <button class="btn btn-primary" id="parse-jd-btn">
                        <i class="fas fa-search"></i>
                        Parse & Optimize
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Template Modal -->
    <div class="modal" id="section-template-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Section</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="section-types">
                    <button class="section-type-btn" data-type="summary">
                        <i class="fas fa-user"></i>
                        <span>Professional Summary</span>
                    </button>
                    <button class="section-type-btn" data-type="experience">
                        <i class="fas fa-briefcase"></i>
                        <span>Work Experience</span>
                    </button>
                    <button class="section-type-btn" data-type="education">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Education</span>
                    </button>
                    <button class="section-type-btn" data-type="skills">
                        <i class="fas fa-cogs"></i>
                        <span>Skills</span>
                    </button>
                    <button class="section-type-btn" data-type="projects">
                        <i class="fas fa-project-diagram"></i>
                        <span>Projects</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/resume-builder.js"></script>
    
    <script>
        // Initialize with existing data if editing
        <?php if ($resume): ?>
        window.resumeData = {
            id: <?= $resume['id'] ?>,
            title: <?= json_encode($resume['title']) ?>,
            targetRole: <?= json_encode($resume['target_role']) ?>,
            sections: <?= json_encode($sections) ?>,
            atsScore: <?= $resume['ats_score'] ?>
        };
        <?php endif; ?>
    </script>
</body>
</html>