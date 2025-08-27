<?php
require_once '../includes/auth.php';
require_once '../classes/ResumeManager.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$resumeId = $input['resume_id'] ?? null;
$jobDescription = $input['job_description'] ?? null;

if (!$resumeId || !$jobDescription) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resume ID and job description required']);
    exit;
}

try {
    $tailor = new AITailor();
    $tailoredResume = $tailor->tailorResume($resumeId, $jobDescription, $auth->getCurrentUser()['id']);
    
    echo json_encode([
        'success' => true,
        'tailored_resume' => $tailoredResume,
        'message' => 'Resume tailored successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Tailoring failed']);
}

class AITailor {
    private $db;
    private $resumeManager;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->resumeManager = new ResumeManager();
    }
    
    public function tailorResume($resumeId, $jobDescription, $userId) {
        // Get current resume
        $resume = $this->resumeManager->getResume($resumeId, $userId);
        if (!$resume) {
            throw new Exception('Resume not found');
        }
        
        // Parse job description for keywords
        $jobKeywords = $this->extractJobKeywords($jobDescription);
        
        // Tailor each section
        $tailoredResume = $resume;
        
        // Tailor summary
        if (isset($resume['sections'])) {
            foreach ($resume['sections'] as $index => $section) {
                switch ($section['type']) {
                    case 'summary':
                        $tailoredResume['sections'][$index]['content'] = $this->tailorSummary($section['content'], $jobKeywords, $jobDescription);
                        break;
                    case 'experience':
                        $tailoredResume['sections'][$index]['content'] = $this->tailorExperience($section['content'], $jobKeywords);
                        break;
                    case 'skills':
                        $tailoredResume['sections'][$index]['content'] = $this->tailorSkills($section['content'], $jobKeywords);
                        break;
                }
            }
        }
        
        return $tailoredResume;
    }
    
    private function extractJobKeywords($jobDescription) {
        // Use the same logic as JD parser
        require_once 'jd-parser.php';
        $parser = new JobDescriptionParser();
        return $parser->extractKeywords($jobDescription);
    }
    
    private function tailorSummary($summaryContent, $jobKeywords, $jobDescription) {
        $currentSummary = $summaryContent['text'] ?? '';
        
        if (empty($currentSummary)) {
            // Generate new summary based on job description
            $tailoredSummary = $this->generateSummary($jobKeywords, $jobDescription);
        } else {
            // Enhance existing summary
            $tailoredSummary = $this->enhanceSummary($currentSummary, $jobKeywords);
        }
        
        return ['text' => $tailoredSummary];
    }
    
    private function generateSummary($jobKeywords, $jobDescription) {
        // Extract key requirements from job description
        $techSkills = [];
        $softSkills = [];
        $experienceLevel = 'experienced';
        
        foreach ($jobKeywords as $keyword) {
            if ($keyword['type'] === 'technical_skill') {
                $techSkills[] = $keyword['text'];
            } elseif ($keyword['type'] === 'action_verb') {
                $softSkills[] = $keyword['text'];
            } elseif ($keyword['type'] === 'experience' && strpos(strtolower($keyword['text']), 'senior') !== false) {
                $experienceLevel = 'senior';
            }
        }
        
        // Generate professional summary template
        $templates = [
            "Results-driven professional with expertise in " . implode(', ', array_slice($techSkills, 0, 5)) . ". Proven track record of delivering high-quality solutions and driving business growth through innovative technology implementations.",
            
            "Skilled " . $experienceLevel . " professional specializing in " . implode(', ', array_slice($techSkills, 0, 4)) . ". Strong background in problem-solving and collaborative development with a focus on scalable and maintainable solutions.",
            
            "Dynamic technology professional with comprehensive experience in " . implode(', ', array_slice($techSkills, 0, 6)) . ". Committed to excellence in software development and passionate about creating impactful solutions."
        ];
        
        // Select template based on available keywords
        $template = $templates[array_rand($templates)];
        
        return $template;
    }
    
    private function enhanceSummary($currentSummary, $jobKeywords) {
        $enhanced = $currentSummary;
        
        // Find high-importance technical keywords missing from summary
        $summaryLower = strtolower($currentSummary);
        $missingKeywords = [];
        
        foreach ($jobKeywords as $keyword) {
            if ($keyword['importance'] >= 4 && 
                $keyword['type'] === 'technical_skill' && 
                strpos($summaryLower, strtolower($keyword['text'])) === false) {
                $missingKeywords[] = $keyword['text'];
            }
        }
        
        // Add important missing keywords
        if (!empty($missingKeywords)) {
            $keywordsToAdd = array_slice($missingKeywords, 0, 3);
            $enhanced .= " Experienced with " . implode(', ', $keywordsToAdd) . ".";
        }
        
        return $enhanced;
    }
    
    private function tailorExperience($experienceContent, $jobKeywords) {
        if (!isset($experienceContent['items']) || empty($experienceContent['items'])) {
            return $experienceContent;
        }
        
        $tailoredItems = [];
        
        foreach ($experienceContent['items'] as $item) {
            $tailoredItem = $item;
            
            if (!empty($item['description'])) {
                $tailoredItem['description'] = $this->enhanceExperienceDescription($item['description'], $jobKeywords);
            }
            
            $tailoredItems[] = $tailoredItem;
        }
        
        return ['items' => $tailoredItems];
    }
    
    private function enhanceExperienceDescription($description, $jobKeywords) {
        $enhanced = $description;
        $descriptionLower = strtolower($description);
        
        // Find relevant keywords not mentioned in this experience
        $relevantKeywords = [];
        
        foreach ($jobKeywords as $keyword) {
            if ($keyword['importance'] >= 3 && 
                $keyword['type'] === 'technical_skill' && 
                strpos($descriptionLower, strtolower($keyword['text'])) === false) {
                $relevantKeywords[] = $keyword['text'];
            }
        }
        
        // Add relevant keywords naturally
        if (!empty($relevantKeywords)) {
            $keywordsToAdd = array_slice($relevantKeywords, 0, 2);
            
            // Check if description ends with a period
            $enhanced = rtrim($enhanced, '. ');
            $enhanced .= ", utilizing " . implode(' and ', $keywordsToAdd) . ".";
        }
        
        // Enhance action verbs
        $enhanced = $this->enhanceActionVerbs($enhanced, $jobKeywords);
        
        return $enhanced;
    }
    
    private function enhanceActionVerbs($text, $jobKeywords) {
        // Map of basic verbs to stronger alternatives
        $verbMap = [
            'worked on' => 'developed',
            'helped with' => 'contributed to',
            'was responsible for' => 'managed',
            'did' => 'executed',
            'made' => 'created',
            'used' => 'utilized',
            'worked with' => 'collaborated on'
        ];
        
        $enhanced = $text;
        
        foreach ($verbMap as $weak => $strong) {
            $enhanced = str_ireplace($weak, $strong, $enhanced);
        }
        
        return $enhanced;
    }
    
    private function tailorSkills($skillsContent, $jobKeywords) {
        if (!isset($skillsContent['categories']) || empty($skillsContent['categories'])) {
            // Create skills from job keywords if no skills exist
            return $this->generateSkillsFromKeywords($jobKeywords);
        }
        
        $tailoredCategories = [];
        
        foreach ($skillsContent['categories'] as $category) {
            $tailoredCategory = $category;
            
            // Add missing relevant skills to existing categories
            if (isset($category['skills']) && is_array($category['skills'])) {
                $categoryName = strtolower($category['name'] ?? '');
                $existingSkills = array_map('strtolower', $category['skills']);
                
                // Find relevant keywords for this category
                $relevantKeywords = [];
                foreach ($jobKeywords as $keyword) {
                    if ($keyword['type'] === 'technical_skill' && 
                        $keyword['importance'] >= 3 &&
                        !in_array(strtolower($keyword['text']), $existingSkills)) {
                        
                        // Add to technical skills category
                        if (strpos($categoryName, 'technical') !== false || 
                            strpos($categoryName, 'programming') !== false ||
                            strpos($categoryName, 'development') !== false) {
                            $relevantKeywords[] = $keyword['text'];
                        }
                    }
                }
                
                // Add up to 3 new relevant skills
                $skillsToAdd = array_slice($relevantKeywords, 0, 3);
                $tailoredCategory['skills'] = array_merge($category['skills'], $skillsToAdd);
            }
            
            $tailoredCategories[] = $tailoredCategory;
        }
        
        return ['categories' => $tailoredCategories];
    }
    
    private function generateSkillsFromKeywords($jobKeywords) {
        $technicalSkills = [];
        $softSkills = [];
        
        foreach ($jobKeywords as $keyword) {
            if ($keyword['type'] === 'technical_skill' && $keyword['importance'] >= 3) {
                $technicalSkills[] = $keyword['text'];
            } elseif ($keyword['type'] === 'action_verb' && $keyword['importance'] >= 3) {
                $softSkills[] = ucfirst($keyword['text']);
            }
        }
        
        $categories = [];
        
        if (!empty($technicalSkills)) {
            $categories[] = [
                'name' => 'Technical Skills',
                'skills' => array_slice($technicalSkills, 0, 10)
            ];
        }
        
        if (!empty($softSkills)) {
            $categories[] = [
                'name' => 'Core Competencies',
                'skills' => array_slice($softSkills, 0, 6)
            ];
        }
        
        return ['categories' => $categories];
    }
}
?>