<?php
require_once '../includes/auth.php';
require_once '../classes/ResumeManager.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$resumeManager = new ResumeManager();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            handleGet($resumeManager, $user['id']);
            break;
        case 'POST':
            handlePost($resumeManager, $user['id'], $input);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGet($resumeManager, $userId) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            $resumeId = $_GET['id'] ?? null;
            if (!$resumeId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Resume ID required']);
                return;
            }
            
            $resume = $resumeManager->getResume($resumeId, $userId);
            if ($resume) {
                echo json_encode(['success' => true, 'resume' => $resume]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Resume not found']);
            }
            break;
            
        case 'list':
            $resumes = $resumeManager->getUserResumes($userId);
            echo json_encode(['success' => true, 'resumes' => $resumes]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($resumeManager, $userId, $input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $title = $input['title'] ?? 'My Resume';
            $targetRole = $input['target_role'] ?? '';
            
            $result = $resumeManager->createResume($userId, $title, $targetRole);
            echo json_encode($result);
            break;
            
        case 'update':
            $resumeId = $input['resume_id'] ?? null;
            $data = $input['data'] ?? [];
            
            if (!$resumeId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Resume ID required']);
                return;
            }
            
            // Update basic resume info
            $resumeData = [];
            if (isset($data['title'])) $resumeData['title'] = $data['title'];
            if (isset($data['target_role'])) $resumeData['target_role'] = $data['target_role'];
            if (isset($data['ats_score'])) $resumeData['ats_score'] = $data['ats_score'];
            
            if (!empty($resumeData)) {
                $resumeManager->updateResume($resumeId, $resumeData, $userId);
            }
            
            // Update sections
            if (isset($data['sections'])) {
                $sections = $resumeManager->getResumeSections($resumeId);
                foreach ($sections as $section) {
                    $sectionType = $section['type'];
                    if (isset($data['sections'][$sectionType])) {
                        $resumeManager->updateSection($section['id'], $data['sections'][$sectionType], $userId);
                    }
                }
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            $resumeId = $input['resume_id'] ?? null;
            
            if (!$resumeId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Resume ID required']);
                return;
            }
            
            $result = $resumeManager->deleteResume($resumeId, $userId);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?>