<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$resumeId = $_GET['id'] ?? null;
$format = $_GET['format'] ?? 'pdf';

if (!$resumeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resume ID is required']);
    exit;
}

try {
    $db = new Database();
    $userId = $_SESSION['user_id'];
    
    // Get resume data
    $resume = $db->fetch(
        "SELECT * FROM resumes WHERE id = ? AND user_id = ?",
        [$resumeId, $userId]
    );
    
    if (!$resume) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resume not found']);
        exit;
    }
    
    // Get resume sections
    $sections = $db->fetchAll(
        "SELECT * FROM sections WHERE resume_id = ? ORDER BY position, id",
        [$resumeId]
    );
    
    // Process sections
    $resumeData = [
        'id' => $resume['id'],
        'title' => $resume['title'],
        'targetRole' => $resume['target_role'],
        'atsScore' => $resume['ats_score'],
        'personalInfo' => [],
        'sections' => []
    ];
    
    foreach ($sections as $section) {
        $content = json_decode($section['content'], true);
        if ($section['type'] === 'summary' && isset($content['fullname'])) {
            // This is personal info stored as summary
            $resumeData['personalInfo'] = $content;
        } else {
            if (!isset($resumeData['sections'][$section['type']])) {
                $resumeData['sections'][$section['type']] = [];
            }
            $resumeData['sections'][$section['type']][] = $content;
        }
    }
    
    // Export based on format
    switch ($format) {
        case 'pdf':
            exportToPDF($resumeData);
            break;
        case 'docx':
            exportToDOCX($resumeData);
            break;
        case 'json':
            exportToJSON($resumeData);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid format']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export failed']);
}

function exportToPDF($resumeData) {
    // For now, we'll create a simple HTML-to-PDF conversion
    // In production, you'd use a library like TCPDF, DOMPDF, or wkhtmltopdf
    
    $html = generateResumeHTML($resumeData);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="resume_' . $resumeData['id'] . '.pdf"');
    
    // Simple PDF generation (in production, use proper PDF library)
    // For now, we'll return HTML that can be printed as PDF
    header('Content-Type: text/html');
    echo $html;
}

function exportToDOCX($resumeData) {
    // For DOCX export, you'd use a library like PHPWord
    // For now, we'll create a simple HTML version
    
    $html = generateResumeHTML($resumeData);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="resume_' . $resumeData['id'] . '.docx"');
    
    // Simple DOCX-like HTML
    echo $html;
}

function exportToJSON($resumeData) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="resume_' . $resumeData['id'] . '.json"');
    
    echo json_encode($resumeData, JSON_PRETTY_PRINT);
}

function generateResumeHTML($resumeData) {
    $personalInfo = $resumeData['personalInfo'] ?? [];
    $sections = $resumeData['sections'] ?? [];
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($resumeData['title']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 11pt;
                line-height: 1.4;
                color: #000;
                margin: 0.5in;
                background: white;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #000;
            }
            .name {
                font-size: 24pt;
                font-weight: bold;
                margin-bottom: 5px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .title {
                font-size: 14pt;
                color: #666;
                margin-bottom: 10px;
            }
            .contact {
                font-size: 10pt;
                color: #666;
            }
            .section {
                margin-bottom: 20px;
            }
            .section-title {
                font-size: 14pt;
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 10px;
                padding-bottom: 3px;
                border-bottom: 1px solid #ccc;
            }
            .item {
                margin-bottom: 12px;
            }
            .item-header {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                margin-bottom: 3px;
            }
            .item-title {
                font-weight: bold;
                font-size: 12pt;
            }
            .item-date {
                font-size: 10pt;
                color: #666;
            }
            .item-subtitle {
                font-style: italic;
                font-size: 10pt;
                color: #666;
                margin-bottom: 5px;
            }
            .item-description {
                font-size: 11pt;
                line-height: 1.4;
                text-align: justify;
            }
            .skills-list {
                font-size: 11pt;
            }
            @media print {
                body { margin: 0.5in; }
                .section { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>';
    
    // Header section
    $html .= '<div class="header">';
    if (!empty($personalInfo['fullname'])) {
        $html .= '<div class="name">' . htmlspecialchars($personalInfo['fullname']) . '</div>';
    }
    if (!empty($personalInfo['professionaltitle'])) {
        $html .= '<div class="title">' . htmlspecialchars($personalInfo['professionaltitle']) . '</div>';
    }
    
    // Contact info
    $contactParts = [];
    if (!empty($personalInfo['email'])) $contactParts[] = $personalInfo['email'];
    if (!empty($personalInfo['phone'])) $contactParts[] = $personalInfo['phone'];
    if (!empty($personalInfo['location'])) $contactParts[] = $personalInfo['location'];
    if (!empty($personalInfo['linkedin'])) $contactParts[] = $personalInfo['linkedin'];
    
    if (!empty($contactParts)) {
        $html .= '<div class="contact">' . implode(' | ', array_map('htmlspecialchars', $contactParts)) . '</div>';
    }
    $html .= '</div>';
    
    // Sections
    $sectionOrder = ['summary', 'experience', 'skills', 'education', 'projects'];
    $sectionTitles = [
        'summary' => 'Professional Summary',
        'experience' => 'Professional Experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'projects' => 'Projects'
    ];
    
    foreach ($sectionOrder as $sectionType) {
        if (isset($sections[$sectionType]) && !empty($sections[$sectionType])) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">' . $sectionTitles[$sectionType] . '</div>';
            
            foreach ($sections[$sectionType] as $item) {
                $html .= generateSectionItemHTML($sectionType, $item);
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= '</body></html>';
    
    return $html;
}

function generateSectionItemHTML($type, $item) {
    $html = '<div class="item">';
    
    switch ($type) {
        case 'summary':
            if (!empty($item['content'])) {
                $html .= '<div class="item-description">' . nl2br(htmlspecialchars($item['content'])) . '</div>';
            }
            break;
            
        case 'experience':
            if (!empty($item['jobTitle']) || !empty($item['company'])) {
                $html .= '<div class="item-header">';
                $html .= '<div class="item-title">' . htmlspecialchars($item['jobTitle'] ?? '') . 
                         (!empty($item['company']) ? ' - ' . htmlspecialchars($item['company']) : '') . '</div>';
                $html .= '<div class="item-date">' . formatDateRange($item['startDate'] ?? '', $item['endDate'] ?? '', $item['current'] ?? false) . '</div>';
                $html .= '</div>';
                
                if (!empty($item['location'])) {
                    $html .= '<div class="item-subtitle">' . htmlspecialchars($item['location']) . '</div>';
                }
                
                if (!empty($item['description'])) {
                    $html .= '<div class="item-description">' . nl2br(htmlspecialchars($item['description'])) . '</div>';
                }
            }
            break;
            
        case 'education':
            if (!empty($item['degree']) || !empty($item['school'])) {
                $html .= '<div class="item-header">';
                $html .= '<div class="item-title">' . htmlspecialchars($item['degree'] ?? '') . '</div>';
                $html .= '<div class="item-date">' . formatDate($item['endDate'] ?? '') . '</div>';
                $html .= '</div>';
                
                if (!empty($item['school'])) {
                    $subtitle = htmlspecialchars($item['school']);
                    if (!empty($item['location'])) {
                        $subtitle .= ', ' . htmlspecialchars($item['location']);
                    }
                    $html .= '<div class="item-subtitle">' . $subtitle . '</div>';
                }
                
                if (!empty($item['gpa'])) {
                    $html .= '<div class="item-description">GPA: ' . htmlspecialchars($item['gpa']) . '</div>';
                }
                
                if (!empty($item['honors'])) {
                    $html .= '<div class="item-description">' . htmlspecialchars($item['honors']) . '</div>';
                }
            }
            break;
            
        case 'skills':
            if (!empty($item['skills'])) {
                if (!empty($item['category'])) {
                    $html .= '<div class="item-title">' . htmlspecialchars($item['category']) . '</div>';
                }
                $html .= '<div class="skills-list">' . implode(', ', array_map('htmlspecialchars', $item['skills'])) . '</div>';
            }
            break;
            
        case 'projects':
            if (!empty($item['title'])) {
                $html .= '<div class="item-header">';
                $html .= '<div class="item-title">' . htmlspecialchars($item['title']) . '</div>';
                $html .= '<div class="item-date">' . formatDateRange($item['startDate'] ?? '', $item['endDate'] ?? '') . '</div>';
                $html .= '</div>';
                
                if (!empty($item['technologies'])) {
                    $html .= '<div class="item-subtitle">' . htmlspecialchars($item['technologies']) . '</div>';
                }
                
                if (!empty($item['description'])) {
                    $html .= '<div class="item-description">' . nl2br(htmlspecialchars($item['description'])) . '</div>';
                }
                
                if (!empty($item['link'])) {
                    $html .= '<div class="item-description">Project Link: ' . htmlspecialchars($item['link']) . '</div>';
                }
            }
            break;
    }
    
    $html .= '</div>';
    return $html;
}

function formatDate($dateString) {
    if (!$dateString) return '';
    $date = DateTime::createFromFormat('Y-m', $dateString);
    if ($date) {
        return $date->format('M Y');
    }
    return $dateString;
}

function formatDateRange($startDate, $endDate, $current = false) {
    $start = formatDate($startDate);
    $end = $current ? 'Present' : formatDate($endDate);
    
    if (!$start && !$end) return '';
    if (!$start) return $end;
    if (!$end) return $start;
    
    return $start . ' - ' . $end;
}
?>