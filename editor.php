<?php
require_once 'includes/auth.php';
require_once 'classes/ResumeManager.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$resumeManager = new ResumeManager();

$resumeId = $_GET['id'] ?? null;
if (!$resumeId) {
    header('Location: dashboard.php');
    exit;
}

$resume = $resumeManager->getResume($resumeId, $user['id']);
if (!$resume) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume - <?php echo htmlspecialchars($resume['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container flex justify-between items-center">
            <h1>AI Resume Builder</h1>
            <nav>
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="preview.php?id=<?php echo $resumeId; ?>">Preview</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="resume-editor">
            <!-- Editor Panel -->
            <div class="editor-panel">
                <div class="panel-header">
                    <h3>Resume Editor</h3>
                </div>
                <div class="panel-content">
                    <!-- Basic Info -->
                    <div class="form-group">
                        <label for="resume-title">Resume Title</label>
                        <input type="text" id="resume-title" class="form-control resume-field" value="<?php echo htmlspecialchars($resume['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="target-role">Target Role</label>
                        <input type="text" id="target-role" class="form-control resume-field" value="<?php echo htmlspecialchars($resume['target_role'] ?? ''); ?>" placeholder="e.g., Senior Software Engineer">
                    </div>

                    <!-- Job Description Panel -->
                    <div class="jd-panel">
                        <h4>Job Description Analysis</h4>
                        <div class="form-group">
                            <label for="job-description">Paste Job Description</label>
                            <textarea id="job-description" class="form-control jd-textarea" placeholder="Paste the job description here to analyze keywords and tailor your resume..."></textarea>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                            <button type="button" class="btn btn-primary parse-jd">Parse Keywords</button>
                            <button type="button" class="btn btn-success tailor-resume">AI Tailor Resume</button>
                        </div>
                        <div class="keywords-list"></div>
                    </div>

                    <!-- Resume Sections -->
                    <div class="resume-sections">
                        <!-- Summary Section -->
                        <div class="resume-section" data-section-type="summary">
                            <div class="section-header">
                                <h4 class="section-title">Professional Summary</h4>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control resume-field" rows="4" placeholder="Write a compelling professional summary that highlights your key qualifications..."></textarea>
                            </div>
                        </div>

                        <!-- Experience Section -->
                        <div class="resume-section" data-section-type="experience">
                            <div class="section-header">
                                <h4 class="section-title">Work Experience</h4>
                                <button type="button" class="btn btn-secondary add-item" data-section-type="experience">Add Experience</button>
                            </div>
                            <div class="section-items"></div>
                        </div>

                        <!-- Education Section -->
                        <div class="resume-section" data-section-type="education">
                            <div class="section-header">
                                <h4 class="section-title">Education</h4>
                                <button type="button" class="btn btn-secondary add-item" data-section-type="education">Add Education</button>
                            </div>
                            <div class="section-items"></div>
                        </div>

                        <!-- Skills Section -->
                        <div class="resume-section" data-section-type="skills">
                            <div class="section-header">
                                <h4 class="section-title">Skills</h4>
                                <button type="button" class="btn btn-secondary" onclick="addSkillCategory()">Add Category</button>
                            </div>
                            <div class="skills-categories"></div>
                        </div>

                        <!-- Projects Section -->
                        <div class="resume-section" data-section-type="projects">
                            <div class="section-header">
                                <h4 class="section-title">Projects</h4>
                                <button type="button" class="btn btn-secondary add-item" data-section-type="projects">Add Project</button>
                            </div>
                            <div class="section-items"></div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-success export-resume">Export Resume</button>
                        <button type="button" class="btn btn-secondary" onclick="window.resumeBuilder.autoSave()">Save Changes</button>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- ATS Score -->
                <div class="ats-score">
                    <h3><?php echo $resume['ats_score']; ?>%</h3>
                    <p>ATS Compatibility Score</p>
                    <div class="score-breakdown">
                        <div>Keywords: 0%</div>
                        <div>Skills: 0%</div>
                        <div>Format: 0%</div>
                        <div>Content: 0%</div>
                    </div>
                </div>

                <!-- Resume Preview -->
                <div class="resume-preview">
                    <div class="resume-content">
                        <div class="resume-header">
                            <h1><?php echo htmlspecialchars($resume['title']); ?></h1>
                            <?php if ($resume['target_role']): ?>
                                <h2><?php echo htmlspecialchars($resume['target_role']); ?></h2>
                            <?php endif; ?>
                        </div>
                        <!-- Preview content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Export Modal -->
    <div id="export-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Export Resume</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div>
                <p style="margin-bottom: 1.5rem;">Choose your export format:</p>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <button class="btn btn-primary" onclick="exportResume('pdf')">Export as PDF</button>
                    <button class="btn btn-secondary" onclick="exportResume('docx')">Export as Word Document</button>
                    <button class="btn btn-secondary" onclick="exportResume('json')">Export as JSON</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>