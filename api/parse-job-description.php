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

if (!$input || empty($input['jobDescription'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Job description is required']);
    exit;
}

try {
    $db = new Database();
    $userId = $_SESSION['user_id'];
    $jobDescription = $input['jobDescription'];
    $resumeId = $input['resumeId'] ?? null;
    
    // Parse job description and extract keywords
    $parsedData = parseJobDescription($jobDescription);
    
    // Save job description to database
    $db->query(
        "INSERT INTO job_descriptions (user_id, resume_id, raw_text, extracted_keywords) VALUES (?, ?, ?, ?)",
        [$userId, $resumeId, $jobDescription, json_encode($parsedData)]
    );
    
    // Calculate ATS score based on keywords
    $atsScore = calculateATSScore($parsedData);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'keywords' => $parsedData['keywords'],
            'skills' => $parsedData['skills'],
            'jobTitle' => $parsedData['jobTitle'],
            'company' => $parsedData['company'],
            'requirements' => $parsedData['requirements'],
            'atsScore' => $atsScore,
            'suggestions' => generateOptimizationSuggestions($parsedData)
        ],
        'message' => 'Job description parsed successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Parse JD error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to parse job description'
    ]);
}

function parseJobDescription($jobDescription) {
    // Clean and normalize text
    $text = strtolower(trim($jobDescription));
    
    // Extract job title (look for common patterns)
    $jobTitle = extractJobTitle($text);
    
    // Extract company name (basic pattern matching)
    $company = extractCompany($text);
    
    // Extract skills and keywords
    $skills = extractSkills($text);
    $keywords = extractKeywords($text);
    $requirements = extractRequirements($text);
    
    return [
        'jobTitle' => $jobTitle,
        'company' => $company,
        'skills' => $skills,
        'keywords' => $keywords,
        'requirements' => $requirements,
        'rawText' => $jobDescription
    ];
}

function extractJobTitle($text) {
    // Common job title patterns
    $patterns = [
        '/job title[:\s]+([^\n\r]+)/i',
        '/position[:\s]+([^\n\r]+)/i',
        '/role[:\s]+([^\n\r]+)/i',
        '/we are looking for[:\s]+(?:a\s+|an\s+)?([^\n\r]+)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return '';
}

function extractCompany($text) {
    // Basic company extraction patterns
    $patterns = [
        '/company[:\s]+([^\n\r]+)/i',
        '/at\s+([A-Z][a-zA-Z\s&.,]+)(?:\s+we|\s+is|\s+has)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
    }
    
    return '';
}

function extractSkills($text) {
    // Common technical skills and keywords
    $skillKeywords = [
        // Programming Languages
        'javascript', 'python', 'java', 'c++', 'c#', 'php', 'ruby', 'go', 'rust', 'swift',
        'typescript', 'kotlin', 'scala', 'r', 'matlab', 'sql',
        
        // Web Technologies
        'html', 'css', 'react', 'angular', 'vue', 'node.js', 'express', 'django', 'flask',
        'spring', 'laravel', 'rails', 'asp.net', 'jquery', 'bootstrap', 'sass', 'less',
        
        // Databases
        'mysql', 'postgresql', 'mongodb', 'redis', 'elasticsearch', 'oracle', 'sqlite',
        'dynamodb', 'cassandra', 'neo4j',
        
        // Cloud & DevOps
        'aws', 'azure', 'gcp', 'docker', 'kubernetes', 'jenkins', 'git', 'github', 'gitlab',
        'ci/cd', 'terraform', 'ansible', 'puppet', 'chef',
        
        // Frameworks & Libraries
        'spring boot', 'hibernate', 'junit', 'maven', 'gradle', 'webpack', 'babel',
        'redux', 'mobx', 'rxjs', 'lodash', 'moment.js',
        
        // Mobile
        'ios', 'android', 'react native', 'flutter', 'xamarin', 'cordova', 'ionic',
        
        // Data Science & ML
        'machine learning', 'deep learning', 'tensorflow', 'pytorch', 'scikit-learn',
        'pandas', 'numpy', 'matplotlib', 'jupyter', 'tableau', 'power bi',
        
        // Soft Skills
        'leadership', 'communication', 'teamwork', 'problem solving', 'analytical',
        'project management', 'agile', 'scrum', 'kanban'
    ];
    
    $foundSkills = [];
    foreach ($skillKeywords as $skill) {
        if (stripos($text, $skill) !== false) {
            $foundSkills[] = $skill;
        }
    }
    
    return array_unique($foundSkills);
}

function extractKeywords($text) {
    // Extract important keywords using frequency analysis
    $words = str_word_count($text, 1);
    $words = array_map('strtolower', $words);
    
    // Remove common stop words
    $stopWords = [
        'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are',
        'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
        'should', 'could', 'can', 'may', 'might', 'must', 'shall', 'this', 'that', 'these', 'those',
        'a', 'an', 'as', 'if', 'then', 'than', 'when', 'where', 'why', 'how', 'all', 'any', 'both',
        'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own',
        'same', 'so', 'than', 'too', 'very', 'you', 'your', 'we', 'our', 'they', 'their', 'them'
    ];
    
    $filteredWords = array_filter($words, function($word) use ($stopWords) {
        return strlen($word) > 3 && !in_array($word, $stopWords);
    });
    
    $wordCounts = array_count_values($filteredWords);
    arsort($wordCounts);
    
    // Return top 20 keywords
    return array_slice(array_keys($wordCounts), 0, 20);
}

function extractRequirements($text) {
    // Extract requirements sections
    $requirements = [];
    
    // Look for bullet points or numbered lists
    if (preg_match_all('/[•\-\*]\s*([^\n\r]+)/i', $text, $matches)) {
        $requirements = array_merge($requirements, $matches[1]);
    }
    
    // Look for numbered requirements
    if (preg_match_all('/\d+\.\s*([^\n\r]+)/i', $text, $matches)) {
        $requirements = array_merge($requirements, $matches[1]);
    }
    
    // Clean and filter requirements
    $requirements = array_map('trim', $requirements);
    $requirements = array_filter($requirements, function($req) {
        return strlen($req) > 10 && strlen($req) < 200;
    });
    
    return array_slice($requirements, 0, 10); // Return top 10 requirements
}

function calculateATSScore($parsedData) {
    // Simple ATS scoring based on keyword density and structure
    $score = 0;
    
    // Base score for having structured data
    if (!empty($parsedData['jobTitle'])) $score += 10;
    if (!empty($parsedData['company'])) $score += 5;
    
    // Score based on skills found
    $skillCount = count($parsedData['skills']);
    $score += min($skillCount * 3, 30); // Max 30 points for skills
    
    // Score based on keywords
    $keywordCount = count($parsedData['keywords']);
    $score += min($keywordCount * 2, 40); // Max 40 points for keywords
    
    // Score based on requirements
    $reqCount = count($parsedData['requirements']);
    $score += min($reqCount * 1.5, 15); // Max 15 points for requirements
    
    return min($score, 100); // Cap at 100
}

function generateOptimizationSuggestions($parsedData) {
    $suggestions = [];
    
    if (!empty($parsedData['skills'])) {
        $suggestions[] = [
            'type' => 'skills',
            'message' => 'Add these skills to your resume: ' . implode(', ', array_slice($parsedData['skills'], 0, 5)),
            'action' => 'add_skills',
            'data' => array_slice($parsedData['skills'], 0, 10)
        ];
    }
    
    if (!empty($parsedData['keywords'])) {
        $suggestions[] = [
            'type' => 'keywords',
            'message' => 'Include these keywords in your experience descriptions: ' . implode(', ', array_slice($parsedData['keywords'], 0, 5)),
            'action' => 'add_keywords',
            'data' => array_slice($parsedData['keywords'], 0, 10)
        ];
    }
    
    if (!empty($parsedData['jobTitle'])) {
        $suggestions[] = [
            'type' => 'title',
            'message' => 'Consider updating your professional title to match: ' . $parsedData['jobTitle'],
            'action' => 'update_title',
            'data' => $parsedData['jobTitle']
        ];
    }
    
    return $suggestions;
}
?>