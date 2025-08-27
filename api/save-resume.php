<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = new Database();
    $userId = $_SESSION['user_id'];
    
    // Extract resume data
    $resumeId = $input['id'] ?? null;
    $title = $input['title'] ?? 'My Resume';
    $targetRole = $input['targetRole'] ?? '';
    $personalInfo = $input['personalInfo'] ?? [];
    $sections = $input['sections'] ?? [];
    $atsScore = $input['atsScore'] ?? 0;
    
    if ($resumeId) {
        // Update existing resume
        $db->query(
            "UPDATE resumes SET title = ?, target_role = ?, ats_score = ?, updated_at = NOW() WHERE id = ? AND user_id = ?",
            [$title, $targetRole, $atsScore, $resumeId, $userId]
        );
        
        // Delete existing sections
        $db->query("DELETE FROM sections WHERE resume_id = ?", [$resumeId]);
    } else {
        // Create new resume
        $db->query(
            "INSERT INTO resumes (user_id, title, target_role, ats_score) VALUES (?, ?, ?, ?)",
            [$userId, $title, $targetRole, $atsScore]
        );
        $resumeId = $db->lastInsertId();
    }
    
    // Save personal info as a summary section if provided
    if (!empty($personalInfo)) {
        $personalInfoJson = json_encode($personalInfo);
        $db->query(
            "INSERT INTO sections (resume_id, type, position, content) VALUES (?, 'summary', 0, ?)",
            [$resumeId, $personalInfoJson]
        );
    }
    
    // Save all sections
    $position = 1;
    foreach ($sections as $sectionType => $sectionItems) {
        foreach ($sectionItems as $item) {
            $content = json_encode($item);
            $db->query(
                "INSERT INTO sections (resume_id, type, position, content) VALUES (?, ?, ?, ?)",
                [$resumeId, $sectionType, $position++, $content]
            );
        }
    }
    
    echo json_encode([
        'success' => true,
        'resumeId' => $resumeId,
        'message' => 'Resume saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Save resume error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save resume'
    ]);
}
?>