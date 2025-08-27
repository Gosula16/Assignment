<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $userId = $_SESSION['user_id'];
    
    // Get user statistics
    $stats = [];
    
    // Total resumes
    $resumeCount = $db->fetch("SELECT COUNT(*) as count FROM resumes WHERE user_id = ?", [$userId]);
    $stats['resumes'] = $resumeCount['count'];
    
    // Average ATS score
    $avgScore = $db->fetch("SELECT AVG(ats_score) as avg_score FROM resumes WHERE user_id = ? AND ats_score > 0", [$userId]);
    $stats['ats_score'] = round($avgScore['avg_score'] ?? 0);
    
    // Job descriptions parsed
    $jdCount = $db->fetch("SELECT COUNT(*) as count FROM job_descriptions WHERE user_id = ?", [$userId]);
    $stats['job_descriptions'] = $jdCount['count'];
    
    // Downloads (placeholder - would need to track in database)
    $stats['downloads'] = 0;
    
    // Recent activity
    $activity = [];
    
    // Recent resumes
    $recentResumes = $db->fetchAll(
        "SELECT title, created_at FROM resumes WHERE user_id = ? ORDER BY created_at DESC LIMIT 3",
        [$userId]
    );
    
    foreach ($recentResumes as $resume) {
        $activity[] = [
            'icon' => 'file-alt',
            'message' => 'Created resume: ' . $resume['title'],
            'timestamp' => $resume['created_at']
        ];
    }
    
    // Recent job descriptions
    $recentJDs = $db->fetchAll(
        "SELECT created_at FROM job_descriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 2",
        [$userId]
    );
    
    foreach ($recentJDs as $jd) {
        $activity[] = [
            'icon' => 'search',
            'message' => 'Parsed job description',
            'timestamp' => $jd['created_at']
        ];
    }
    
    // Sort activity by timestamp
    usort($activity, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Limit to 5 most recent activities
    $activity = array_slice($activity, 0, 5);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'activity' => $activity
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data'
    ]);
}
?>