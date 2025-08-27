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

if (!$input || empty($input['resumeData'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resume data is required']);
    exit;
}

try {
    $resumeData = $input['resumeData'];
    $score = calculateATSScore($resumeData);
    $recommendations = generateATSRecommendations($resumeData, $score);
    
    // Update resume score in database if resume ID exists
    if (isset($resumeData['id'])) {
        $db = new Database();
        $db->query(
            "UPDATE resumes SET ats_score = ? WHERE id = ? AND user_id = ?",
            [$score, $resumeData['id'], $_SESSION['user_id']]
        );
    }
    
    echo json_encode([
        'success' => true,
        'score' => $score,
        'recommendations' => $recommendations,
        'breakdown' => getScoreBreakdown($resumeData),
        'message' => 'ATS score calculated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("ATS score calculation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to calculate ATS score'
    ]);
}

function calculateATSScore($resumeData) {
    $score = 0;
    $maxScore = 100;
    
    // 1. Contact Information (15 points)
    $contactScore = calculateContactScore($resumeData);
    $score += $contactScore;
    
    // 2. Professional Summary (10 points)
    $summaryScore = calculateSummaryScore($resumeData);
    $score += $summaryScore;
    
    // 3. Work Experience (25 points)
    $experienceScore = calculateExperienceScore($resumeData);
    $score += $experienceScore;
    
    // 4. Skills Section (20 points)
    $skillsScore = calculateSkillsScore($resumeData);
    $score += $skillsScore;
    
    // 5. Education (10 points)
    $educationScore = calculateEducationScore($resumeData);
    $score += $educationScore;
    
    // 6. Formatting & Structure (10 points)
    $formatScore = calculateFormatScore($resumeData);
    $score += $formatScore;
    
    // 7. Keywords & ATS Compatibility (10 points)
    $keywordScore = calculateKeywordScore($resumeData);
    $score += $keywordScore;
    
    return min($score, $maxScore);
}

function calculateContactScore($resumeData) {
    $score = 0;
    $personalInfo = $resumeData['personalInfo'] ?? [];
    
    // Essential contact info
    if (!empty($personalInfo['fullname'])) $score += 4;
    if (!empty($personalInfo['email'])) $score += 4;
    if (!empty($personalInfo['phone'])) $score += 3;
    if (!empty($personalInfo['location'])) $score += 2;
    if (!empty($personalInfo['linkedin'])) $score += 2;
    
    return min($score, 15);
}

function calculateSummaryScore($resumeData) {
    $score = 0;
    $sections = $resumeData['sections'] ?? [];
    
    if (isset($sections['summary']) && !empty($sections['summary'])) {
        $summary = $sections['summary'][0]['content'] ?? '';
        $wordCount = str_word_count($summary);
        
        if ($wordCount >= 20) $score += 5; // Has substantial content
        if ($wordCount >= 50 && $wordCount <= 150) $score += 3; // Optimal length
        if (containsActionWords($summary)) $score += 2; // Contains action words
    }
    
    return min($score, 10);
}

function calculateExperienceScore($resumeData) {
    $score = 0;
    $sections = $resumeData['sections'] ?? [];
    
    if (isset($sections['experience']) && !empty($sections['experience'])) {
        $experiences = $sections['experience'];
        
        // Points for having experience
        $score += min(count($experiences) * 3, 9); // Up to 9 points for multiple experiences
        
        foreach ($experiences as $exp) {
            // Check for required fields
            if (!empty($exp['jobTitle'])) $score += 1;
            if (!empty($exp['company'])) $score += 1;
            if (!empty($exp['startDate'])) $score += 1;
            if (!empty($exp['description'])) {
                $description = $exp['description'];
                if (containsActionWords($description)) $score += 2;
                if (containsMetrics($description)) $score += 2;
                if (str_word_count($description) >= 30) $score += 1;
            }
        }
    }
    
    return min($score, 25);
}

function calculateSkillsScore($resumeData) {
    $score = 0;
    $sections = $resumeData['sections'] ?? [];
    
    if (isset($sections['skills']) && !empty($sections['skills'])) {
        $skillsSections = $sections['skills'];
        
        foreach ($skillsSections as $skillSection) {
            $skills = $skillSection['skills'] ?? [];
            $skillCount = count($skills);
            
            if ($skillCount >= 5) $score += 5; // Has multiple skills
            if ($skillCount >= 10) $score += 5; // Has many skills
            if ($skillCount >= 15) $score += 5; // Comprehensive skills
            if (!empty($skillSection['category'])) $score += 3; // Categorized skills
            
            // Check for technical vs soft skills balance
            $technicalSkills = countTechnicalSkills($skills);
            $softSkills = countSoftSkills($skills);
            
            if ($technicalSkills >= 3 && $softSkills >= 2) $score += 2;
        }
    }
    
    return min($score, 20);
}

function calculateEducationScore($resumeData) {
    $score = 0;
    $sections = $resumeData['sections'] ?? [];
    
    if (isset($sections['education']) && !empty($sections['education'])) {
        $educations = $sections['education'];
        
        foreach ($educations as $edu) {
            if (!empty($edu['degree'])) $score += 3;
            if (!empty($edu['school'])) $score += 3;
            if (!empty($edu['endDate'])) $score += 2;
            if (!empty($edu['gpa']) && floatval($edu['gpa']) >= 3.5) $score += 2;
        }
    }
    
    return min($score, 10);
}

function calculateFormatScore($resumeData) {
    $score = 10; // Start with full score, deduct for issues
    
    // Check for ATS-unfriendly elements (this is a simplified check)
    // In a real implementation, you'd check the actual document format
    
    // Assume good formatting if structured data is present
    if (empty($resumeData['personalInfo'])) $score -= 2;
    if (empty($resumeData['sections'])) $score -= 3;
    
    // Check section organization
    $expectedSections = ['summary', 'experience', 'skills', 'education'];
    $presentSections = array_keys($resumeData['sections'] ?? []);
    $missingSections = array_diff($expectedSections, $presentSections);
    $score -= count($missingSections);
    
    return max($score, 0);
}

function calculateKeywordScore($resumeData) {
    $score = 0;
    
    // Check for industry-relevant keywords
    $allText = '';
    $sections = $resumeData['sections'] ?? [];
    
    foreach ($sections as $sectionType => $sectionItems) {
        foreach ($sectionItems as $item) {
            if (isset($item['content'])) {
                $allText .= ' ' . $item['content'];
            }
            if (isset($item['description'])) {
                $allText .= ' ' . $item['description'];
            }
        }
    }
    
    $keywordDensity = calculateKeywordDensity($allText);
    
    if ($keywordDensity >= 2) $score += 3; // Good keyword density
    if ($keywordDensity >= 4) $score += 3; // Excellent keyword density
    if (containsIndustryTerms($allText)) $score += 4; // Contains industry terms
    
    return min($score, 10);
}

function containsActionWords($text) {
    $actionWords = [
        'achieved', 'managed', 'led', 'developed', 'created', 'implemented', 'improved',
        'increased', 'decreased', 'optimized', 'designed', 'built', 'launched', 'delivered',
        'collaborated', 'coordinated', 'supervised', 'trained', 'mentored', 'analyzed',
        'researched', 'solved', 'streamlined', 'automated', 'innovated'
    ];
    
    $text = strtolower($text);
    foreach ($actionWords as $word) {
        if (strpos($text, $word) !== false) {
            return true;
        }
    }
    return false;
}

function containsMetrics($text) {
    // Check for numbers, percentages, dollar amounts, etc.
    $patterns = [
        '/\d+%/',           // Percentages
        '/\$\d+/',          // Dollar amounts
        '/\d+\s*(million|thousand|k|m)/i', // Large numbers
        '/\d+\s*(years?|months?|weeks?)/i', // Time periods
        '/\d+\s*(people|team|members)/i',   // Team sizes
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    return false;
}

function countTechnicalSkills($skills) {
    $technicalKeywords = [
        'javascript', 'python', 'java', 'php', 'react', 'angular', 'vue', 'node.js',
        'sql', 'mysql', 'postgresql', 'mongodb', 'aws', 'azure', 'docker', 'kubernetes',
        'git', 'html', 'css', 'typescript', 'c++', 'c#', 'ruby', 'go', 'swift'
    ];
    
    $count = 0;
    foreach ($skills as $skill) {
        $skill = strtolower($skill);
        foreach ($technicalKeywords as $keyword) {
            if (strpos($skill, $keyword) !== false) {
                $count++;
                break;
            }
        }
    }
    return $count;
}

function countSoftSkills($skills) {
    $softKeywords = [
        'leadership', 'communication', 'teamwork', 'problem solving', 'analytical',
        'project management', 'time management', 'adaptability', 'creativity',
        'critical thinking', 'collaboration', 'negotiation'
    ];
    
    $count = 0;
    foreach ($skills as $skill) {
        $skill = strtolower($skill);
        foreach ($softKeywords as $keyword) {
            if (strpos($skill, $keyword) !== false) {
                $count++;
                break;
            }
        }
    }
    return $count;
}

function calculateKeywordDensity($text) {
    $words = str_word_count($text);
    if ($words == 0) return 0;
    
    $keywords = countKeywords($text);
    return ($keywords / $words) * 100;
}

function countKeywords($text) {
    $keywords = [
        'experience', 'skills', 'management', 'development', 'project', 'team',
        'leadership', 'analysis', 'design', 'implementation', 'strategy'
    ];
    
    $count = 0;
    $text = strtolower($text);
    foreach ($keywords as $keyword) {
        $count += substr_count($text, $keyword);
    }
    return $count;
}

function containsIndustryTerms($text) {
    // This would be more sophisticated in a real implementation
    $industryTerms = [
        'agile', 'scrum', 'devops', 'ci/cd', 'api', 'microservices', 'cloud',
        'machine learning', 'artificial intelligence', 'data analysis', 'big data'
    ];
    
    $text = strtolower($text);
    foreach ($industryTerms as $term) {
        if (strpos($text, $term) !== false) {
            return true;
        }
    }
    return false;
}

function generateATSRecommendations($resumeData, $score) {
    $recommendations = [];
    
    if ($score < 70) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'overall',
            'message' => 'Your resume needs significant improvements to pass ATS systems effectively.'
        ];
    }
    
    // Contact info recommendations
    $personalInfo = $resumeData['personalInfo'] ?? [];
    if (empty($personalInfo['email'])) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'contact',
            'message' => 'Add a professional email address to your contact information.'
        ];
    }
    
    // Experience recommendations
    $sections = $resumeData['sections'] ?? [];
    if (empty($sections['experience'])) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'experience',
            'message' => 'Add work experience section with detailed job descriptions.'
        ];
    } else {
        $hasMetrics = false;
        foreach ($sections['experience'] as $exp) {
            if (!empty($exp['description']) && containsMetrics($exp['description'])) {
                $hasMetrics = true;
                break;
            }
        }
        
        if (!$hasMetrics) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'experience',
                'message' => 'Add quantifiable achievements with numbers, percentages, or metrics to your experience descriptions.'
            ];
        }
    }
    
    // Skills recommendations
    if (empty($sections['skills'])) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'skills',
            'message' => 'Add a skills section with relevant technical and soft skills.'
        ];
    }
    
    // Summary recommendations
    if (empty($sections['summary'])) {
        $recommendations[] = [
            'priority' => 'medium',
            'category' => 'summary',
            'message' => 'Add a professional summary to highlight your key qualifications.'
        ];
    }
    
    return $recommendations;
}

function getScoreBreakdown($resumeData) {
    return [
        'contact' => calculateContactScore($resumeData),
        'summary' => calculateSummaryScore($resumeData),
        'experience' => calculateExperienceScore($resumeData),
        'skills' => calculateSkillsScore($resumeData),
        'education' => calculateEducationScore($resumeData),
        'format' => calculateFormatScore($resumeData),
        'keywords' => calculateKeywordScore($resumeData)
    ];
}
?>