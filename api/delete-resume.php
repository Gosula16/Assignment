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

if (!$input || empty($input['resumeId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resume ID is required']);
    exit;
}

try {
    $db = new Database();
    $userId = $_SESSION['user_id'];
    $resumeId = $input['resumeId'];
    
    // Verify resume belongs to user
    $resume = $db->fetch(
        "SELECT id FROM resumes WHERE id = ? AND user_id = ?",
        [$resumeId, $userId]
    );
    
    if (!$resume) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resume not found']);
        exit;
    }
    
    // Delete resume and related sections (CASCADE should handle this)
    $db->query("DELETE FROM resumes WHERE id = ? AND user_id = ?", [$resumeId, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Resume deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete resume error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete resume'
    ]);
}
?>