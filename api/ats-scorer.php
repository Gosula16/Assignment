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
$resumeData = $input['resume_data'] ?? null;

if (!$resumeId || !$resumeData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resume ID and data required']);
    exit;
}

try {
    $scorer = new ATSScorer();
    $score = $scorer->calculateScore($resumeId, $resumeData);
    
    // Update resume with new score
    $resumeManager = new ResumeManager();
    $resumeManager->updateResume($resumeId, ['ats_score' => $score['total']], $auth->getCurrentUser()['id']);
    
    echo json_encode([
        'success' => true,
        'score' => $score['total'],
        'breakdown' => $score['breakdown'],
        'recommendations' => $score['recommendations']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Scoring failed']);
}

class ATSScorer {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function calculateScore($resumeId, $resumeData) {
        $scores = [
            'keywords' => $this->scoreKeywords($resumeId, $resumeData),
            'skills' => $this->scoreSkills($resumeData),
            'format' => $this->scoreFormat($resumeData),
            'content' => $this->scoreContent($resumeData)
        ];
        
        // Weighted average (Keywords 40%, Skills 30%, Format 20%, Content 10%)
        $totalScore = round(
            ($scores['keywords'] * 0.4) +
            ($scores['skills'] * 0.3) +
            ($scores['format'] * 0.2) +
            ($scores['content'] * 0.1)
        );
        
        $recommendations = $this->generateRecommendations($scores, $resumeData);
        
        return [
            'total' => $totalScore,
            'breakdown' => $scores,
            'recommendations' => $recommendations
        ];
    }
    
    private function scoreKeywords($resumeId, $resumeData) {
        try {
            // Get job description keywords for this resume
            $stmt = $this->db->prepare("
                SELECT extracted_keywords 
                FROM job_descriptions 
                WHERE resume_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$resumeId]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['extracted_keywords']) {
                return 50; // Default score if no job description
            }
            
            $jobKeywords = json_decode($result['extracted_keywords'], true);
            if (!$jobKeywords) return 50;
            
            // Extract text from resume
            $resumeText = $this->extractResumeText($resumeData);
            $resumeTextLower = strtolower($resumeText);
            
            $totalKeywords = count($jobKeywords);
            $matchedKeywords = 0;
            
            foreach ($jobKeywords as $keyword) {
                $keywordText = strtolower($keyword['text']);
                if (strpos($resumeTextLower, $keywordText) !== false) {
                    $matchedKeywords++;
                }
            }
            
            $score = $totalKeywords > 0 ? round(($matchedKeywords / $totalKeywords) * 100) : 50;
            return min(100, max(0, $score));
            
        } catch (Exception $e) {
            return 50;
        }
    }
    
    private function scoreSkills($resumeData) {
        $score = 0;
        $maxScore = 100;
        
        // Check if skills section exists and has content
        if (isset($resumeData['sections']['skills']['categories'])) {
            $skillsCategories = $resumeData['sections']['skills']['categories'];
            
            if (empty($skillsCategories)) {
                return 20; // Low score for no skills
            }
            
            $totalSkills = 0;
            foreach ($skillsCategories as $category) {
                if (isset($category['skills']) && is_array($category['skills'])) {
                    $totalSkills += count($category['skills']);
                }
            }
            
            // Score based on number of skills
            if ($totalSkills >= 15) $score = 100;
            elseif ($totalSkills >= 10) $score = 85;
            elseif ($totalSkills >= 5) $score = 70;
            elseif ($totalSkills >= 3) $score = 50;
            else $score = 30;
            
            // Bonus for having categorized skills
            if (count($skillsCategories) >= 2) {
                $score = min(100, $score + 10);
            }
        } else {
            $score = 20;
        }
        
        return $score;
    }
    
    private function scoreFormat($resumeData) {
        $score = 80; // Start with good base score for our ATS-optimized format
        $issues = [];
        
        // Check for required sections
        $requiredSections = ['summary', 'experience', 'education', 'skills'];
        $presentSections = 0;
        
        foreach ($requiredSections as $section) {
            if (isset($resumeData['sections'][$section])) {
                $sectionContent = $resumeData['sections'][$section];
                
                // Check if section has content
                switch ($section) {
                    case 'summary':
                        if (!empty($sectionContent['text'])) $presentSections++;
                        break;
                    case 'experience':
                        if (!empty($sectionContent['items'])) $presentSections++;
                        break;
                    case 'education':
                        if (!empty($sectionContent['items'])) $presentSections++;
                        break;
                    case 'skills':
                        if (!empty($sectionContent['categories'])) $presentSections++;
                        break;
                }
            }
        }
        
        // Deduct points for missing sections
        $missingSections = count($requiredSections) - $presentSections;
        $score -= $missingSections * 15;
        
        // Bonus for having projects section
        if (isset($resumeData['sections']['projects']) && !empty($resumeData['sections']['projects']['items'])) {
            $score += 5;
        }
        
        return min(100, max(0, $score));
    }
    
    private function scoreContent($resumeData) {
        $score = 0;
        $checks = 0;
        
        // Check summary quality
        if (isset($resumeData['sections']['summary']['text'])) {
            $summary = $resumeData['sections']['summary']['text'];
            $checks++;
            
            if (strlen($summary) >= 100 && strlen($summary) <= 300) {
                $score += 25;
            } elseif (strlen($summary) >= 50) {
                $score += 15;
            } elseif (!empty($summary)) {
                $score += 10;
            }
        }
        
        // Check experience descriptions
        if (isset($resumeData['sections']['experience']['items'])) {
            $checks++;
            $experienceScore = 0;
            $itemCount = 0;
            
            foreach ($resumeData['sections']['experience']['items'] as $exp) {
                if (!empty($exp['description'])) {
                    $itemCount++;
                    $desc = $exp['description'];
                    
                    // Check for bullet points
                    if (strpos($desc, '•') !== false || strpos($desc, '*') !== false) {
                        $experienceScore += 10;
                    }
                    
                    // Check for action verbs
                    $actionVerbs = ['achieved', 'developed', 'implemented', 'managed', 'led', 'created', 'improved', 'increased', 'reduced', 'optimized'];
                    foreach ($actionVerbs as $verb) {
                        if (stripos($desc, $verb) !== false) {
                            $experienceScore += 5;
                            break;
                        }
                    }
                    
                    // Check for quantifiable results (numbers/percentages)
                    if (preg_match('/\d+%|\$\d+|\d+\+/', $desc)) {
                        $experienceScore += 10;
                    }
                }
            }
            
            $score += min(25, $experienceScore);
        }
        
        // Check education completeness
        if (isset($resumeData['sections']['education']['items'])) {
            $checks++;
            $eduScore = 0;
            
            foreach ($resumeData['sections']['education']['items'] as $edu) {
                if (!empty($edu['degree']) && !empty($edu['school'])) {
                    $eduScore += 15;
                    if (!empty($edu['year'])) {
                        $eduScore += 5;
                    }
                }
            }
            
            $score += min(25, $eduScore);
        }
        
        // Check overall content length
        $totalText = $this->extractResumeText($resumeData);
        $wordCount = str_word_count($totalText);
        
        $checks++;
        if ($wordCount >= 300 && $wordCount <= 800) {
            $score += 25;
        } elseif ($wordCount >= 200) {
            $score += 15;
        } elseif ($wordCount >= 100) {
            $score += 10;
        }
        
        return min(100, max(0, $score));
    }
    
    private function extractResumeText($resumeData) {
        $text = '';
        
        // Add basic info
        if (isset($resumeData['title'])) $text .= $resumeData['title'] . ' ';
        if (isset($resumeData['target_role'])) $text .= $resumeData['target_role'] . ' ';
        
        // Add sections content
        foreach ($resumeData['sections'] ?? [] as $sectionType => $content) {
            switch ($sectionType) {
                case 'summary':
                    if (!empty($content['text'])) {
                        $text .= $content['text'] . ' ';
                    }
                    break;
                    
                case 'experience':
                case 'education':
                case 'projects':
                    if (!empty($content['items'])) {
                        foreach ($content['items'] as $item) {
                            foreach ($item as $field => $value) {
                                if (is_string($value)) {
                                    $text .= $value . ' ';
                                }
                            }
                        }
                    }
                    break;
                    
                case 'skills':
                    if (!empty($content['categories'])) {
                        foreach ($content['categories'] as $category) {
                            if (!empty($category['name'])) {
                                $text .= $category['name'] . ' ';
                            }
                            if (!empty($category['skills'])) {
                                $text .= implode(' ', $category['skills']) . ' ';
                            }
                        }
                    }
                    break;
            }
        }
        
        return $text;
    }
    
    private function generateRecommendations($scores, $resumeData) {
        $recommendations = [];
        
        // Keywords recommendations
        if ($scores['keywords'] < 70) {
            $recommendations[] = [
                'type' => 'keywords',
                'message' => 'Include more relevant keywords from the job description in your resume content.',
                'priority' => 'high'
            ];
        }
        
        // Skills recommendations
        if ($scores['skills'] < 60) {
            $recommendations[] = [
                'type' => 'skills',
                'message' => 'Add more relevant skills and organize them into categories (Technical, Soft Skills, etc.).',
                'priority' => 'high'
            ];
        }
        
        // Format recommendations
        if ($scores['format'] < 80) {
            $recommendations[] = [
                'type' => 'format',
                'message' => 'Ensure all major sections (Summary, Experience, Education, Skills) have content.',
                'priority' => 'medium'
            ];
        }
        
        // Content recommendations
        if ($scores['content'] < 70) {
            $recommendations[] = [
                'type' => 'content',
                'message' => 'Use action verbs and quantify your achievements with specific numbers and results.',
                'priority' => 'medium'
            ];
        }
        
        // Specific content checks
        $resumeText = $this->extractResumeText($resumeData);
        
        if (str_word_count($resumeText) < 200) {
            $recommendations[] = [
                'type' => 'content',
                'message' => 'Your resume content seems too brief. Add more details about your experience and achievements.',
                'priority' => 'high'
            ];
        }
        
        if (!preg_match('/\d+%|\$\d+|\d+\+/', $resumeText)) {
            $recommendations[] = [
                'type' => 'content',
                'message' => 'Include quantifiable achievements (percentages, dollar amounts, numbers) in your experience descriptions.',
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }
}
?>