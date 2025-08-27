<?php
require_once '../includes/auth.php';
require_once '../classes/ResumeManager.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$resumeId = $input['resume_id'] ?? null;
$format = $input['format'] ?? 'pdf';

if (!$resumeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resume ID required']);
    exit;
}

$user = $auth->getCurrentUser();
$resumeManager = new ResumeManager();
$resume = $resumeManager->getResume($resumeId, $user['id']);

if (!$resume) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Resume not found']);
    exit;
}

try {
    $exporter = new ResumeExporter();
    
    switch ($format) {
        case 'pdf':
            $exporter->exportToPDF($resume);
            break;
        case 'docx':
            $exporter->exportToDocx($resume);
            break;
        case 'json':
            $exporter->exportToJson($resume);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid format']);
            exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export failed']);
}

class ResumeExporter {
    
    public function exportToPDF($resume) {
        // Generate HTML for PDF conversion
        $html = $this->generateHTML($resume);
        
        // For now, we'll use a simple HTML to PDF approach
        // In production, you'd use libraries like TCPDF, mPDF, or wkhtmltopdf
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename($resume['title']) . '.pdf"');
        
        // Simple PDF generation using HTML
        $this->htmlToPdf($html, $resume['title']);
    }
    
    public function exportToDocx($resume) {
        // Generate DOCX content
        $docxContent = $this->generateDocxContent($resume);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename($resume['title']) . '.docx"');
        
        echo $docxContent;
    }
    
    public function exportToJson($resume) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename($resume['title']) . '.json"');
        
        echo json_encode($resume, JSON_PRETTY_PRINT);
    }
    
    private function generateHTML($resume) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($resume['title']) . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 8.5in;
                    margin: 0 auto;
                    padding: 0.5in;
                    font-size: 11pt;
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                
                .header h1 {
                    margin: 0;
                    font-size: 24pt;
                    font-weight: bold;
                }
                
                .header h2 {
                    margin: 5px 0 0 0;
                    font-size: 14pt;
                    color: #666;
                    font-weight: normal;
                }
                
                .section {
                    margin-bottom: 20px;
                }
                
                .section-title {
                    font-size: 14pt;
                    font-weight: bold;
                    color: #333;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 2px;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                }
                
                .experience-item, .education-item, .project-item {
                    margin-bottom: 15px;
                }
                
                .item-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: baseline;
                    margin-bottom: 5px;
                }
                
                .item-title {
                    font-weight: bold;
                    font-size: 12pt;
                }
                
                .item-company {
                    font-style: italic;
                }
                
                .item-date {
                    font-size: 10pt;
                    color: #666;
                }
                
                .item-description {
                    margin-left: 0;
                }
                
                .item-description ul {
                    margin: 5px 0;
                    padding-left: 20px;
                }
                
                .skills-category {
                    margin-bottom: 10px;
                }
                
                .skills-category strong {
                    font-weight: bold;
                }
                
                .skills-list {
                    margin-left: 10px;
                }
                
                @media print {
                    body {
                        padding: 0;
                    }
                }
            </style>
        </head>
        <body>';
        
        // Header
        $html .= '<div class="header">';
        $html .= '<h1>' . htmlspecialchars($resume['title']) . '</h1>';
        if (!empty($resume['target_role'])) {
            $html .= '<h2>' . htmlspecialchars($resume['target_role']) . '</h2>';
        }
        $html .= '</div>';
        
        // Sections
        if (isset($resume['sections'])) {
            foreach ($resume['sections'] as $section) {
                $html .= $this->renderSectionHTML($section);
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    private function renderSectionHTML($section) {
        $sectionTitles = [
            'summary' => 'Professional Summary',
            'experience' => 'Work Experience',
            'education' => 'Education',
            'skills' => 'Skills',
            'projects' => 'Projects'
        ];
        
        $html = '<div class="section">';
        $html .= '<div class="section-title">' . ($sectionTitles[$section['type']] ?? ucfirst($section['type'])) . '</div>';
        
        $content = $section['content'];
        
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
                        $html .= '<div class="item-header">';
                        $html .= '<div>';
                        if (!empty($item['title'])) {
                            $html .= '<div class="item-title">' . htmlspecialchars($item['title']) . '</div>';
                        }
                        if (!empty($item['company'])) {
                            $html .= '<div class="item-company">' . htmlspecialchars($item['company']) . '</div>';
                        }
                        $html .= '</div>';
                        if (!empty($item['start_date']) || !empty($item['end_date'])) {
                            $html .= '<div class="item-date">';
                            $html .= htmlspecialchars(($item['start_date'] ?? '') . ' - ' . ($item['end_date'] ?? ''));
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        if (!empty($item['description'])) {
                            $html .= '<div class="item-description">';
                            $html .= $this->formatDescription($item['description']);
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                    }
                }
                break;
                
            case 'education':
                if (!empty($content['items'])) {
                    foreach ($content['items'] as $item) {
                        $html .= '<div class="education-item">';
                        $html .= '<div class="item-header">';
                        $html .= '<div>';
                        if (!empty($item['degree'])) {
                            $html .= '<div class="item-title">' . htmlspecialchars($item['degree']) . '</div>';
                        }
                        if (!empty($item['school'])) {
                            $html .= '<div class="item-company">' . htmlspecialchars($item['school']) . '</div>';
                        }
                        $html .= '</div>';
                        if (!empty($item['year'])) {
                            $html .= '<div class="item-date">' . htmlspecialchars($item['year']) . '</div>';
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
                            $html .= '<span class="skills-list">' . htmlspecialchars(implode(', ', $category['skills'])) . '</span>';
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
                            $html .= '<div><strong>Technologies:</strong> ' . htmlspecialchars($item['technologies']) . '</div>';
                        }
                        if (!empty($item['description'])) {
                            $html .= '<div>' . nl2br(htmlspecialchars($item['description'])) . '</div>';
                        }
                        if (!empty($item['link'])) {
                            $html .= '<div><strong>Link:</strong> ' . htmlspecialchars($item['link']) . '</div>';
                        }
                        $html .= '</div>';
                    }
                }
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function formatDescription($description) {
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
    
    private function htmlToPdf($html, $title) {
        // Simple PDF generation - in production, use proper PDF libraries
        // For now, we'll output HTML that can be printed to PDF
        echo $html;
    }
    
    private function generateDocxContent($resume) {
        // Simple DOCX generation - in production, use PHPWord or similar
        // For now, we'll create a basic RTF that Word can open
        
        $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Times New Roman;}}';
        $rtf .= '{\\f0\\fs24 ';
        
        // Title
        $rtf .= '\\qc\\b\\fs32 ' . $this->rtfEscape($resume['title']) . '\\b0\\fs24\\par\\par';
        
        if (!empty($resume['target_role'])) {
            $rtf .= '\\qc\\i ' . $this->rtfEscape($resume['target_role']) . '\\i0\\par\\par';
        }
        
        // Sections
        if (isset($resume['sections'])) {
            foreach ($resume['sections'] as $section) {
                $rtf .= $this->renderSectionRTF($section);
            }
        }
        
        $rtf .= '}}';
        
        return $rtf;
    }
    
    private function renderSectionRTF($section) {
        $sectionTitles = [
            'summary' => 'PROFESSIONAL SUMMARY',
            'experience' => 'WORK EXPERIENCE',
            'education' => 'EDUCATION',
            'skills' => 'SKILLS',
            'projects' => 'PROJECTS'
        ];
        
        $rtf = '\\b\\fs28 ' . ($sectionTitles[$section['type']] ?? strtoupper($section['type'])) . '\\b0\\fs24\\par';
        $rtf .= '\\par';
        
        $content = $section['content'];
        
        switch ($section['type']) {
            case 'summary':
                if (!empty($content['text'])) {
                    $rtf .= $this->rtfEscape($content['text']) . '\\par\\par';
                }
                break;
                
            case 'experience':
                if (!empty($content['items'])) {
                    foreach ($content['items'] as $item) {
                        if (!empty($item['title'])) {
                            $rtf .= '\\b ' . $this->rtfEscape($item['title']) . '\\b0\\par';
                        }
                        if (!empty($item['company'])) {
                            $rtf .= '\\i ' . $this->rtfEscape($item['company']) . '\\i0';
                        }
                        if (!empty($item['start_date']) || !empty($item['end_date'])) {
                            $rtf .= ' (' . $this->rtfEscape(($item['start_date'] ?? '') . ' - ' . ($item['end_date'] ?? '')) . ')';
                        }
                        $rtf .= '\\par';
                        if (!empty($item['description'])) {
                            $rtf .= $this->rtfEscape($item['description']) . '\\par';
                        }
                        $rtf .= '\\par';
                    }
                }
                break;
                
            // Add other sections as needed...
        }
        
        return $rtf;
    }
    
    private function rtfEscape($text) {
        return str_replace(['\\', '{', '}'], ['\\\\', '\\{', '\\}'], $text);
    }
    
    private function sanitizeFilename($filename) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    }
}
?>