<?php
require_once __DIR__ . '/../config/database.php';

class ResumeManager {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createResume($userId, $title = 'My Resume', $targetRole = '') {
        try {
            $stmt = $this->db->prepare("INSERT INTO resumes (user_id, title, target_role) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $title, $targetRole]);
            
            $resumeId = $this->db->lastInsertId();
            
            // Create default sections
            $this->createDefaultSections($resumeId);
            
            return ['success' => true, 'resume_id' => $resumeId];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create resume'];
        }
    }

    private function createDefaultSections($resumeId) {
        $defaultSections = [
            ['type' => 'summary', 'position' => 1, 'content' => json_encode([
                'text' => 'Professional summary highlighting your key qualifications and career objectives.'
            ])],
            ['type' => 'experience', 'position' => 2, 'content' => json_encode([
                'items' => []
            ])],
            ['type' => 'education', 'position' => 3, 'content' => json_encode([
                'items' => []
            ])],
            ['type' => 'skills', 'position' => 4, 'content' => json_encode([
                'categories' => [
                    ['name' => 'Technical Skills', 'skills' => []],
                    ['name' => 'Soft Skills', 'skills' => []]
                ]
            ])],
            ['type' => 'projects', 'position' => 5, 'content' => json_encode([
                'items' => []
            ])]
        ];

        foreach ($defaultSections as $section) {
            $stmt = $this->db->prepare("INSERT INTO sections (resume_id, type, position, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$resumeId, $section['type'], $section['position'], $section['content']]);
        }
    }

    public function getUserResumes($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM resumes WHERE user_id = ? ORDER BY updated_at DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getResume($resumeId, $userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM resumes WHERE id = ? AND user_id = ?");
            $stmt->execute([$resumeId, $userId]);
            $resume = $stmt->fetch();
            
            if ($resume) {
                $resume['sections'] = $this->getResumeSections($resumeId);
            }
            
            return $resume;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getResumeSections($resumeId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM sections WHERE resume_id = ? ORDER BY position ASC");
            $stmt->execute([$resumeId]);
            $sections = $stmt->fetchAll();
            
            foreach ($sections as &$section) {
                $section['content'] = json_decode($section['content'], true);
            }
            
            return $sections;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateSection($sectionId, $content, $userId) {
        try {
            // Verify user owns this section
            $stmt = $this->db->prepare("
                SELECT s.id FROM sections s 
                JOIN resumes r ON s.resume_id = r.id 
                WHERE s.id = ? AND r.user_id = ?
            ");
            $stmt->execute([$sectionId, $userId]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Unauthorized'];
            }

            $stmt = $this->db->prepare("UPDATE sections SET content = ? WHERE id = ?");
            $stmt->execute([json_encode($content), $sectionId]);
            
            // Update resume timestamp
            $stmt = $this->db->prepare("
                UPDATE resumes SET updated_at = CURRENT_TIMESTAMP 
                WHERE id = (SELECT resume_id FROM sections WHERE id = ?)
            ");
            $stmt->execute([$sectionId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update section'];
        }
    }

    public function updateResume($resumeId, $data, $userId) {
        try {
            // Verify user owns this resume
            $stmt = $this->db->prepare("SELECT id FROM resumes WHERE id = ? AND user_id = ?");
            $stmt->execute([$resumeId, $userId]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Unauthorized'];
            }

            $updateFields = [];
            $params = [];
            
            if (isset($data['title'])) {
                $updateFields[] = 'title = ?';
                $params[] = $data['title'];
            }
            
            if (isset($data['target_role'])) {
                $updateFields[] = 'target_role = ?';
                $params[] = $data['target_role'];
            }
            
            if (isset($data['ats_score'])) {
                $updateFields[] = 'ats_score = ?';
                $params[] = $data['ats_score'];
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No data to update'];
            }

            $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
            $params[] = $resumeId;

            $sql = "UPDATE resumes SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update resume'];
        }
    }

    public function deleteResume($resumeId, $userId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM resumes WHERE id = ? AND user_id = ?");
            $stmt->execute([$resumeId, $userId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete resume'];
        }
    }

    public function getUserStats($userId) {
        try {
            $stats = [];
            
            // Total resumes
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM resumes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['total_resumes'] = $stmt->fetch()['count'];
            
            // Average ATS score
            $stmt = $this->db->prepare("SELECT AVG(ats_score) as avg_score FROM resumes WHERE user_id = ? AND ats_score > 0");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            $stats['avg_ats_score'] = $result['avg_score'] ? round($result['avg_score']) : 0;
            
            // Recent activity
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM resumes WHERE user_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute([$userId]);
            $stats['recent_updates'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (PDOException $e) {
            return ['total_resumes' => 0, 'avg_ats_score' => 0, 'recent_updates' => 0];
        }
    }
}
?>