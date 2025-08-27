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
    <title>Preview - <?php echo htmlspecialchars($resume['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .preview-container {
            max-width: 8.5in;
            margin: 2rem auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            min-height: 11in;
            padding: 0.75in;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .resume-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #333;
        }
        
        .resume-header h1 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .resume-header h2 {
            font-size: 1.2rem;
            color: #6b7280;
            font-weight: normal;
        }
        
        .preview-section {
            margin-bottom: 1.5rem;
        }
        
        .preview-section h3 {
            font-size: 1.1rem;
            font-weight: bold;
            color: #1f2937;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 0.25rem;
            margin-bottom: 1rem;
        }
        
        .experience-item, .education-item, .project-item {
            margin-bottom: 1.25rem;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.5rem;
        }
        
        .item-title {
            font-weight: bold;
            font-size: 1rem;
        }
        
        .company-date {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-style: italic;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .description {
            margin-left: 0;
        }
        
        .description ul {
            margin: 0.5rem 0;
            padding-left: 1.25rem;
        }
        
        .skills-category {
            margin-bottom: 0.75rem;
        }
        
        .skills-category strong {
            font-weight: bold;
            color: #1f2937;
        }
        
        .action-bar {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 1rem;
            z-index: 1000;
        }
        
        @media print {
            .action-bar {
                display: none;
            }
            
            .preview-container {
                box-shadow: none;
                margin: 0;
                padding: 0.5in;
            }
        }
        
        @media (max-width: 768px) {
            .preview-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .action-bar {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 1rem;
                justify-content: center;
            }
        }
    </style>
</head>
<body style="background: #f3f4f6;">
    <div class="action-bar">
        <a href="editor.php?id=<?php echo $resumeId; ?>" class="btn btn-secondary">Edit Resume</a>
        <button onclick="window.print()" class="btn btn-primary">Print</button>
        <button onclick="exportResume('pdf')" class="btn btn-success">Export PDF</button>
    </div>

    <div class="preview-container">
        <!-- Resume Header -->
        <div class="resume-header">
            <h1><?php echo htmlspecialchars($resume['title']); ?></h1>
            <?php if (!empty($resume['target_role'])): ?>
                <h2><?php echo htmlspecialchars($resume['target_role']); ?></h2>
            <?php endif; ?>
        </div>

        <!-- Resume Sections -->
        <?php if (isset($resume['sections'])): ?>
            <?php foreach ($resume['sections'] as $section): ?>
                <?php echo renderSection($section); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

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

    <script>
        const resumeId = <?php echo json_encode($resumeId); ?>;
        
        function exportResume(format) {
            fetch('api/export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resume_id: resumeId,
                    format: format
                })
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Export failed');
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `resume.${format}`;
                a.click();
                window.URL.revokeObjectURL(url);
                closeModal();
            })
            .catch(error => {
                alert('Export failed: ' + error.message);
            });
        }
        
        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.classList.remove('active');
            });
        }
        
        // Modal handling
        document.addEventListener('click', (e) => {
            if (e.target.matches('.modal-close') || e.target.matches('.modal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>

<?php
function renderSection($section) {
    $sectionTitles = [
        'summary' => 'Professional Summary',
        'experience' => 'Work Experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'projects' => 'Projects'
    ];
    
    $content = $section['content'];
    $html = '<div class="preview-section">';
    $html .= '<h3>' . ($sectionTitles[$section['type']] ?? ucfirst($section['type'])) . '</h3>';
    
    switch ($section['type']) {
        case 'summary':
            if (!empty($content['text'])) {
                $html .= '<p>' . nl2br(htmlspecialchars($content['text'])) . '</p>';
            }
            break;
            
        case 'experience':
            if (!empty($content['items'])) {
                foreach ($content['items'] as $item) {
                    $html .= '<div class="experience-item">';
                    if (!empty($item['title'])) {
                        $html .= '<div class="item-title">' . htmlspecialchars($item['title']) . '</div>';
                    }
                    $html .= '<div class="company-date">';
                    if (!empty($item['company'])) {
                        $html .= '<span>' . htmlspecialchars($item['company']) . '</span>';
                    }
                    if (!empty($item['start_date']) || !empty($item['end_date'])) {
                        $html .= '<span>' . htmlspecialchars(($item['start_date'] ?? '') . ' - ' . ($item['end_date'] ?? '')) . '</span>';
                    }
                    $html .= '</div>';
                    if (!empty($item['description'])) {
                        $html .= '<div class="description">' . formatDescription($item['description']) . '</div>';
                    }
                    $html .= '</div>';
                }
            }
            break;
            
        case 'education':
            if (!empty($content['items'])) {
                foreach ($content['items'] as $item) {
                    $html .= '<div class="education-item">';
                    if (!empty($item['degree'])) {
                        $html .= '<div class="item-title">' . htmlspecialchars($item['degree']) . '</div>';
                    }
                    $html .= '<div class="company-date">';
                    if (!empty($item['school'])) {
                        $html .= '<span>' . htmlspecialchars($item['school']) . '</span>';
                    }
                    if (!empty($item['year'])) {
                        $html .= '<span>' . htmlspecialchars($item['year']) . '</span>';
                    }
                    $html .= '</div>';
                    if (!empty($item['gpa'])) {
                        $html .= '<div>GPA: ' . htmlspecialchars($item['gpa']) . '</div>';
                    }
                    $html .= '</div>';
                }
            }
            break;
            
        case 'skills':
            if (!empty($content['categories'])) {
                foreach ($content['categories'] as $category) {
                    if (!empty($category['name']) && !empty($category['skills'])) {
                        $html .= '<div class="skills-category">';
                        $html .= '<strong>' . htmlspecialchars($category['name']) . ':</strong> ';
                        $html .= htmlspecialchars(implode(', ', $category['skills']));
                        $html .= '</div>';
                    }
                }
            }
            break;
            
        case 'projects':
            if (!empty($content['items'])) {
                foreach ($content['items'] as $item) {
                    $html .= '<div class="project-item">';
                    if (!empty($item['name'])) {
                        $html .= '<div class="item-title">' . htmlspecialchars($item['name']) . '</div>';
                    }
                    if (!empty($item['technologies'])) {
                        $html .= '<div class="technologies"><strong>Technologies:</strong> ' . htmlspecialchars($item['technologies']) . '</div>';
                    }
                    if (!empty($item['description'])) {
                        $html .= '<div class="description">' . nl2br(htmlspecialchars($item['description'])) . '</div>';
                    }
                    if (!empty($item['link'])) {
                        $html .= '<div><strong>Link:</strong> <a href="' . htmlspecialchars($item['link']) . '" target="_blank">' . htmlspecialchars($item['link']) . '</a></div>';
                    }
                    $html .= '</div>';
                }
            }
            break;
    }
    
    $html .= '</div>';
    return $html;
}

function formatDescription($description) {
    // Convert bullet points to HTML list
    $lines = explode("\n", $description);
    $formatted = '';
    $inList = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (strpos($line, '•') === 0 || strpos($line, '*') === 0 || strpos($line, '-') === 0) {
            if (!$inList) {
                $formatted .= '<ul>';
                $inList = true;
            }
            $formatted .= '<li>' . htmlspecialchars(substr($line, 1)) . '</li>';
        } else {
            if ($inList) {
                $formatted .= '</ul>';
                $inList = false;
            }
            $formatted .= '<p>' . htmlspecialchars($line) . '</p>';
        }
    }
    
    if ($inList) {
        $formatted .= '</ul>';
    }
    
    return $formatted;
}
?>