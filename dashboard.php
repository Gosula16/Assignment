<?php
require_once 'includes/auth.php';
require_once 'classes/ResumeManager.php';

$auth = new Auth();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$resumeManager = new ResumeManager();
$stats = $resumeManager->getUserStats($user['id']);
$resumes = $resumeManager->getUserResumes($user['id']);

// Handle new resume creation
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_resume') {
    $title = trim($_POST['title'] ?? 'My Resume');
    $targetRole = trim($_POST['target_role'] ?? '');
    
    $result = $resumeManager->createResume($user['id'], $title, $targetRole);
    if ($result['success']) {
        header('Location: editor.php?id=' . $result['resume_id']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AI Resume Builder</title>
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
                    <li><span style="color: rgba(255,255,255,0.8);">Welcome, <?php echo htmlspecialchars($user['name']); ?></span></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h2>Your Resume Dashboard</h2>
                <p style="color: #6b7280; font-size: 1.1rem;">Manage your resumes and track your ATS optimization progress</p>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo $stats['total_resumes']; ?></h3>
                    <p>Total Resumes</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['avg_ats_score']; ?>%</h3>
                    <p>Average ATS Score</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['recent_updates']; ?></h3>
                    <p>Recent Updates (7 days)</p>
                </div>
            </div>

            <!-- Create New Resume -->
            <div class="editor-panel" style="margin-bottom: 2rem;">
                <div class="panel-header">
                    <h3>Create New Resume</h3>
                </div>
                <div class="panel-content">
                    <form method="POST" action="" class="flex" style="gap: 1rem; align-items: end;">
                        <input type="hidden" name="action" value="create_resume">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="title">Resume Title</label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Software Engineer Resume" required>
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="target_role">Target Role (Optional)</label>
                            <input type="text" id="target_role" name="target_role" class="form-control" placeholder="e.g., Senior Frontend Developer">
                        </div>
                        <button type="submit" class="btn btn-primary">Create Resume</button>
                    </form>
                </div>
            </div>

            <!-- Existing Resumes -->
            <div class="editor-panel">
                <div class="panel-header">
                    <h3>Your Resumes</h3>
                </div>
                <div class="panel-content">
                    <?php if (empty($resumes)): ?>
                        <div class="text-center" style="padding: 2rem; color: #6b7280;">
                            <p style="font-size: 1.1rem; margin-bottom: 1rem;">You haven't created any resumes yet.</p>
                            <p>Create your first resume above to get started!</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach ($resumes as $resume): ?>
                                <div class="section-item" style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="flex: 1;">
                                        <h4 style="margin-bottom: 0.5rem; color: #1f2937;"><?php echo htmlspecialchars($resume['title']); ?></h4>
                                        <div style="display: flex; gap: 2rem; color: #6b7280; font-size: 0.9rem;">
                                            <?php if ($resume['target_role']): ?>
                                                <span>Target: <?php echo htmlspecialchars($resume['target_role']); ?></span>
                                            <?php endif; ?>
                                            <span>ATS Score: <?php echo $resume['ats_score']; ?>%</span>
                                            <span>Updated: <?php echo date('M j, Y', strtotime($resume['updated_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="section-controls">
                                        <a href="editor.php?id=<?php echo $resume['id']; ?>" class="btn btn-primary">Edit</a>
                                        <a href="preview.php?id=<?php echo $resume['id']; ?>" class="btn btn-secondary">Preview</a>
                                        <button onclick="deleteResume(<?php echo $resume['id']; ?>)" class="btn btn-danger">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        function deleteResume(resumeId) {
            if (confirm('Are you sure you want to delete this resume? This action cannot be undone.')) {
                fetch('api/resume.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        resume_id: resumeId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete resume: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred while deleting the resume.');
                });
            }
        }
    </script>
</body>
</html>