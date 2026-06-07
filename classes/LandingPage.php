<?php
/**
 * LandingPage Class
 * Handles landing page creation and management
 */

class LandingPage {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create landing page
     */
    public function createLandingPage($userId, $data) {
        $slug = $this->generateSlug($data['title']);
        
        $this->db->query("INSERT INTO landing_pages (user_id, title, slug, description, template_id, is_published) VALUES (?, ?, ?, ?, ?, 0)");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $data['title']);
        $this->db->bind("s", $slug);
        $this->db->bind("s", $data['description'] ?? '');
        $this->db->bind("i", $data['template_id'] ?? 0);
        
        if ($this->db->execute()) {
            $pageId = $this->db->lastId();
            return ['success' => true, 'message' => 'Landing page created', 'id' => $pageId];
        }
        
        return ['success' => false, 'message' => 'Failed to create landing page'];
    }
    
    /**
     * Get landing pages
     */
    public function getLandingPages($userId) {
        $this->db->query("SELECT * FROM landing_pages WHERE user_id = ? AND deleted_at IS NULL ORDER BY updated_at DESC");
        $this->db->bind("i", $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get landing page
     */
    public function getLandingPage($pageId, $userId = null) {
        if ($userId) {
            $this->db->query("SELECT * FROM landing_pages WHERE id = ? AND user_id = ?");
            $this->db->bind("i", $pageId);
            $this->db->bind("i", $userId);
        } else {
            $this->db->query("SELECT * FROM landing_pages WHERE id = ? AND is_published = 1");
            $this->db->bind("i", $pageId);
        }
        
        return $this->db->single();
    }
    
    /**
     * Get landing page by slug
     */
    public function getLandingPageBySlug($slug, $userId) {
        $this->db->query("SELECT * FROM landing_pages WHERE slug = ? AND user_id = ?");
        $this->db->bind("s", $slug);
        $this->db->bind("i", $userId);
        return $this->db->single();
    }
    
    /**
     * Update landing page
     */
    public function updateLandingPage($pageId, $userId, $data) {
        $this->db->query("UPDATE landing_pages SET title = ?, description = ?, seo_title = ?, seo_description = ?, seo_keywords = ?, content = ? WHERE id = ? AND user_id = ?");
        $this->db->bind("s", $data['title']);
        $this->db->bind("s", $data['description']);
        $this->db->bind("s", $data['seo_title']);
        $this->db->bind("s", $data['seo_description']);
        $this->db->bind("s", $data['seo_keywords']);
        $this->db->bind("s", $data['content']);
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Landing page updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Publish landing page
     */
    public function publishLandingPage($pageId, $userId) {
        $this->db->query("UPDATE landing_pages SET is_published = 1 WHERE id = ? AND user_id = ?");
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Landing page published'];
        }
        
        return ['success' => false, 'message' => 'Publish failed'];
    }
    
    /**
     * Unpublish landing page
     */
    public function unpublishLandingPage($pageId, $userId) {
        $this->db->query("UPDATE landing_pages SET is_published = 0 WHERE id = ? AND user_id = ?");
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Landing page unpublished'];
        }
        
        return ['success' => false, 'message' => 'Unpublish failed'];
    }
    
    /**
     * Delete landing page
     */
    public function deleteLandingPage($pageId, $userId) {
        $this->db->query("UPDATE landing_pages SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Landing page deleted'];
        }
        
        return ['success' => false, 'message' => 'Delete failed'];
    }
    
    /**
     * Add section to landing page
     */
    public function addSection($pageId, $userId, $data) {
        // Verify ownership
        $this->db->query("SELECT id FROM landing_pages WHERE id = ? AND user_id = ?");
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if (!$this->db->single()) {
            return ['success' => false, 'message' => 'Page not found'];
        }
        
        // Get next position
        $this->db->query("SELECT MAX(position) as max_pos FROM landing_page_sections WHERE landing_page_id = ?");
        $this->db->bind("i", $pageId);
        $pos = $this->db->single();
        $position = ($pos['max_pos'] !== null) ? $pos['max_pos'] + 1 : 0;
        
        $this->db->query("INSERT INTO landing_page_sections (landing_page_id, type, position, data) VALUES (?, ?, ?, ?)");
        $this->db->bind("i", $pageId);
        $this->db->bind("s", $data['type']);
        $this->db->bind("i", $position);
        $this->db->bind("s", json_encode($data['content']));
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Section added', 'id' => $this->db->lastId()];
        }
        
        return ['success' => false, 'message' => 'Failed to add section'];
    }
    
    /**
     * Get landing page sections
     */
    public function getSections($pageId) {
        $this->db->query("SELECT * FROM landing_page_sections WHERE landing_page_id = ? AND is_visible = 1 ORDER BY position ASC");
        $this->db->bind("i", $pageId);
        $sections = $this->db->resultSet();
        
        foreach ($sections as &$section) {
            $section['data'] = json_decode($section['data'], true);
        }
        
        return $sections;
    }
    
    /**
     * Update section
     */
    public function updateSection($sectionId, $pageId, $userId, $data) {
        $this->db->query("UPDATE landing_page_sections SET data = ? WHERE id = ? AND landing_page_id = (SELECT id FROM landing_pages WHERE id = ? AND user_id = ?)");
        $this->db->bind("s", json_encode($data));
        $this->db->bind("i", $sectionId);
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Section updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Delete section
     */
    public function deleteSection($sectionId, $pageId, $userId) {
        $this->db->query("UPDATE landing_page_sections SET is_visible = 0 WHERE id = ? AND landing_page_id = (SELECT id FROM landing_pages WHERE id = ? AND user_id = ?)");
        $this->db->bind("i", $sectionId);
        $this->db->bind("i", $pageId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Section deleted'];
        }
        
        return ['success' => false, 'message' => 'Delete failed'];
    }
    
    /**
     * Reorder sections
     */
    public function reorderSections($pageId, $userId, $sectionIds) {
        foreach ($sectionIds as $position => $sectionId) {
            $this->db->query("UPDATE landing_page_sections SET position = ? WHERE id = ? AND landing_page_id = (SELECT id FROM landing_pages WHERE id = ? AND user_id = ?)");
            $this->db->bind("i", $position);
            $this->db->bind("i", $sectionId);
            $this->db->bind("i", $pageId);
            $this->db->bind("i", $userId);
            $this->db->execute();
        }
        
        return ['success' => true, 'message' => 'Sections reordered'];
    }
    
    /**
     * Generate slug from title
     */
    private function generateSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        
        // Check if slug exists
        $counter = 1;
        $originalSlug = $slug;
        
        while (true) {
            $this->db->query("SELECT id FROM landing_pages WHERE slug = ?");
            $this->db->bind("s", $slug);
            
            if (!$this->db->single()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Get template
     */
    public function getTemplate($templateId) {
        $this->db->query("SELECT * FROM templates WHERE id = ?");
        $this->db->bind("i", $templateId);
        return $this->db->single();
    }
    
    /**
     * Get templates by category
     */
    public function getTemplatesByCategory($category) {
        $this->db->query("SELECT * FROM templates WHERE category = ? AND is_active = 1 ORDER BY name ASC");
        $this->db->bind("s", $category);
        return $this->db->resultSet();
    }
}
?>
