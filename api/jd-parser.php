<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$jobDescription = $input['job_description'] ?? '';
$resumeId = $input['resume_id'] ?? null;

if (empty($jobDescription)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Job description required']);
    exit;
}

try {
    $parser = new JobDescriptionParser();
    $keywords = $parser->extractKeywords($jobDescription);
    
    // Save to database if resume ID provided
    if ($resumeId) {
        $parser->saveJobDescription($auth->getCurrentUser()['id'], $resumeId, $jobDescription, $keywords);
    }
    
    echo json_encode([
        'success' => true,
        'keywords' => $keywords,
        'message' => 'Job description parsed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Parsing failed']);
}

class JobDescriptionParser {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function extractKeywords($jobDescription) {
        // Common technical skills and keywords
        $techSkills = [
            // Programming Languages
            'JavaScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go', 'Rust', 'Swift',
            'TypeScript', 'Kotlin', 'Scala', 'R', 'MATLAB', 'SQL', 'HTML', 'CSS',
            
            // Frameworks & Libraries
            'React', 'Angular', 'Vue.js', 'Node.js', 'Express', 'Django', 'Flask', 'Spring',
            'Laravel', 'Rails', 'jQuery', 'Bootstrap', 'Tailwind', 'Next.js', 'Nuxt.js',
            
            // Databases
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'SQLite', 'Oracle', 'Cassandra',
            'DynamoDB', 'Elasticsearch', 'Neo4j',
            
            // Cloud & DevOps
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Jenkins', 'GitLab', 'GitHub',
            'Terraform', 'Ansible', 'Chef', 'Puppet', 'CI/CD', 'DevOps',
            
            // Tools & Technologies
            'Git', 'Linux', 'Unix', 'Nginx', 'Apache', 'REST', 'GraphQL', 'API', 'Microservices',
            'Machine Learning', 'AI', 'Data Science', 'Big Data', 'Analytics',
            
            // Soft Skills
            'Leadership', 'Communication', 'Problem Solving', 'Team Work', 'Project Management',
            'Agile', 'Scrum', 'Kanban', 'Mentoring', 'Training'
        ];
        
        // Action verbs commonly found in job descriptions
        $actionVerbs = [
            'develop', 'build', 'create', 'design', 'implement', 'maintain', 'optimize',
            'manage', 'lead', 'coordinate', 'collaborate', 'analyze', 'troubleshoot',
            'deploy', 'configure', 'integrate', 'test', 'debug', 'monitor', 'support'
        ];
        
        // Experience levels
        $experienceLevels = [
            'Junior', 'Senior', 'Lead', 'Principal', 'Staff', 'Entry Level', 'Mid Level',
            '1+ years', '2+ years', '3+ years', '5+ years', '10+ years'
        ];
        
        $text = strtolower($jobDescription);
        $foundKeywords = [];
        
        // Extract technical skills
        foreach ($techSkills as $skill) {
            if (stripos($text, strtolower($skill)) !== false) {
                $foundKeywords[] = [
                    'text' => $skill,
                    'type' => 'technical_skill',
                    'importance' => $this->calculateImportance($text, $skill),
                    'missing' => false // Will be updated when comparing with resume
                ];
            }
        }
        
        // Extract action verbs
        foreach ($actionVerbs as $verb) {
            if (stripos($text, $verb) !== false) {
                $foundKeywords[] = [
                    'text' => $verb,
                    'type' => 'action_verb',
                    'importance' => $this->calculateImportance($text, $verb),
                    'missing' => false
                ];
            }
        }
        
        // Extract experience requirements
        foreach ($experienceLevels as $level) {
            if (stripos($text, strtolower($level)) !== false) {
                $foundKeywords[] = [
                    'text' => $level,
                    'type' => 'experience',
                    'importance' => 3,
                    'missing' => false
                ];
            }
        }
        
        // Extract degree requirements
        $degrees = ['Bachelor', 'Master', 'PhD', 'Doctorate', 'Associate', 'Degree'];
        foreach ($degrees as $degree) {
            if (stripos($text, strtolower($degree)) !== false) {
                $foundKeywords[] = [
                    'text' => $degree,
                    'type' => 'education',
                    'importance' => 2,
                    'missing' => false
                ];
            }
        }
        
        // Extract industry-specific terms using regex patterns
        $patterns = [
            '/\b(full[- ]?stack|frontend|backend|devops|qa|quality assurance)\b/i',
            '/\b(fintech|healthcare|e-commerce|saas|b2b|b2c)\b/i',
            '/\b(startup|enterprise|fortune 500|remote|hybrid)\b/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $jobDescription, $matches)) {
                foreach ($matches[0] as $match) {
                    $foundKeywords[] = [
                        'text' => trim($match),
                        'type' => 'industry_term',
                        'importance' => 2,
                        'missing' => false
                    ];
                }
            }
        }
        
        // Remove duplicates and sort by importance
        $foundKeywords = $this->removeDuplicates($foundKeywords);
        usort($foundKeywords, function($a, $b) {
            return $b['importance'] - $a['importance'];
        });
        
        // Limit to top 50 keywords
        return array_slice($foundKeywords, 0, 50);
    }
    
    private function calculateImportance($text, $keyword) {
        $count = substr_count(strtolower($text), strtolower($keyword));
        
        // Higher importance for keywords that appear multiple times
        if ($count >= 3) return 5;
        if ($count >= 2) return 4;
        if ($count >= 1) return 3;
        
        return 1;
    }
    
    private function removeDuplicates($keywords) {
        $unique = [];
        $seen = [];
        
        foreach ($keywords as $keyword) {
            $key = strtolower(trim($keyword['text']));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $keyword;
            }
        }
        
        return $unique;
    }
    
    public function saveJobDescription($userId, $resumeId, $jobDescription, $keywords) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO job_descriptions (user_id, resume_id, raw_text, extracted_keywords) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                raw_text = VALUES(raw_text), 
                extracted_keywords = VALUES(extracted_keywords)
            ");
            
            $stmt->execute([
                $userId,
                $resumeId,
                $jobDescription,
                json_encode($keywords)
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Failed to save job description: " . $e->getMessage());
            return false;
        }
    }
}
?>