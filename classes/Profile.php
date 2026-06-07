<?php
/**
 * Profile Class
 * Handles user profile management and links
 */

class Profile {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get user profile
     */
    public function getProfile($userId) {
        $this->db->query("SELECT * FROM profiles WHERE user_id = ?");
        $this->db->bind("i", $userId);
        return $this->db->single();
    }
    
    /**
     * Get profile by slug
     */
    public function getProfileBySlug($slug) {
        $this->db->query("SELECT p.*, u.username, u.first_name, u.last_name, u.profile_photo, u.cover_photo FROM profiles p JOIN users u ON p.user_id = u.id WHERE p.slug = ? AND p.is_published = 1");
        $this->db->bind("s", $slug);
        return $this->db->single();
    }
    
    /**
     * Update profile
     */
    public function updateProfile($userId, $data) {
        $this->db->query("UPDATE profiles SET title = ?, description = ?, website = ?, location = ?, social_twitter = ?, social_facebook = ?, social_instagram = ?, social_linkedin = ?, social_youtube = ?, social_tiktok = ?, social_github = ?, theme = ? WHERE user_id = ?");
        $this->db->bind("s", $data['title']);
        $this->db->bind("s", $data['description']);
        $this->db->bind("s", $data['website']);
        $this->db->bind("s", $data['location']);
        $this->db->bind("s", $data['social_twitter']);
        $this->db->bind("s", $data['social_facebook']);
        $this->db->bind("s", $data['social_instagram']);
        $this->db->bind("s", $data['social_linkedin']);
        $this->db->bind("s", $data['social_youtube']);
        $this->db->bind("s", $data['social_tiktok']);
        $this->db->bind("s", $data['social_github']);
        $this->db->bind("s", $data['theme']);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Profile updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Publish profile
     */
    public function publishProfile($userId) {
        $this->db->query("UPDATE profiles SET is_published = 1 WHERE user_id = ?");
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Profile published'];
        }
        
        return ['success' => false, 'message' => 'Publish failed'];
    }
    
    /**
     * Unpublish profile
     */
    public function unpublishProfile($userId) {
        $this->db->query("UPDATE profiles SET is_published = 0 WHERE user_id = ?");
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Profile unpublished'];
        }
        
        return ['success' => false, 'message' => 'Unpublish failed'];
    }
    
    /**
     * Update profile photo
     */
    public function updateProfilePhoto($userId, $filename) {
        $this->db->query("UPDATE users SET profile_photo = ? WHERE id = ?");
        $this->db->bind("s", $filename);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Profile photo updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Update cover photo
     */
    public function updateCoverPhoto($userId, $filename) {
        $this->db->query("UPDATE users SET cover_photo = ? WHERE id = ?");
        $this->db->bind("s", $filename);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Cover photo updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Add link
     */
    public function addLink($userId, $data) {
        // Check link limit based on plan
        $user = new User();
        $userData = $user->getUserById($userId);
        
        $this->db->query("SELECT COUNT(*) as count FROM links WHERE user_id = ? AND deleted_at IS NULL");
        $this->db->bind("i", $userId);
        $result = $this->db->single();
        
        $linkLimit = ($userData['plan_type'] === 'premium') ? PREMIUM_PLAN_LINKS : FREE_PLAN_LINKS;
        
        if ($result['count'] >= $linkLimit) {
            return ['success' => false, 'message' => 'Link limit reached for your plan'];
        }
        
        // Get next position
        $this->db->query("SELECT MAX(position) as max_pos FROM links WHERE user_id = ?");
        $this->db->bind("i", $userId);
        $pos = $this->db->single();
        $position = ($pos['max_pos'] !== null) ? $pos['max_pos'] + 1 : 0;
        
        $this->db->query("INSERT INTO links (user_id, title, url, description, icon, color, position, type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $data['title']);
        $this->db->bind("s", $data['url']);
        $this->db->bind("s", $data['description']);
        $this->db->bind("s", $data['icon']);
        $this->db->bind("s", $data['color']);
        $this->db->bind("i", $position);
        $this->db->bind("s", $data['type']);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Link added', 'id' => $this->db->lastId()];
        }
        
        return ['success' => false, 'message' => 'Failed to add link'];
    }
    
    /**
     * Get user links
     */
    public function getUserLinks($userId) {
        $this->db->query("SELECT * FROM links WHERE user_id = ? AND deleted_at IS NULL ORDER BY position ASC");
        $this->db->bind("i", $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Update link
     */
    public function updateLink($linkId, $userId, $data) {
        $this->db->query("UPDATE links SET title = ?, url = ?, description = ?, icon = ?, color = ? WHERE id = ? AND user_id = ?");
        $this->db->bind("s", $data['title']);
        $this->db->bind("s", $data['url']);
        $this->db->bind("s", $data['description']);
        $this->db->bind("s", $data['icon']);
        $this->db->bind("s", $data['color']);
        $this->db->bind("i", $linkId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Link updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Delete link
     */
    public function deleteLink($linkId, $userId) {
        $this->db->query("UPDATE links SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
        $this->db->bind("i", $linkId);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Link deleted'];
        }
        
        return ['success' => false, 'message' => 'Delete failed'];
    }
    
    /**
     * Reorder links
     */
    public function reorderLinks($userId, $linkIds) {
        foreach ($linkIds as $position => $linkId) {
            $this->db->query("UPDATE links SET position = ? WHERE id = ? AND user_id = ?");
            $this->db->bind("i", $position);
            $this->db->bind("i", $linkId);
            $this->db->bind("i", $userId);
            $this->db->execute();
        }
        
        return ['success' => true, 'message' => 'Links reordered'];
    }
    
    /**
     * Get public profile with links
     */
    public function getPublicProfile($slug) {
        $profile = $this->getProfileBySlug($slug);
        
        if (!$profile) {
            return null;
        }
        
        // Track view
        $this->trackView($profile['user_id'], $profile['id']);
        
        // Get links
        $this->db->query("SELECT * FROM links WHERE user_id = ? AND deleted_at IS NULL AND is_active = 1 ORDER BY position ASC");
        $this->db->bind("i", $profile['user_id']);
        $links = $this->db->resultSet();
        
        $profile['links'] = $links;
        
        return $profile;
    }
    
    /**
     * Track profile view
     */
    private function trackView($userId, $profileId) {
        $eventType = 'view';
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $deviceType = $this->detectDevice();
        
        $this->db->query("INSERT INTO analytics (user_id, profile_id, event_type, ip_address, user_agent, referrer, device_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $this->db->bind("i", $userId);
        $this->db->bind("i", $profileId);
        $this->db->bind("s", $eventType);
        $this->db->bind("s", $ipAddress);
        $this->db->bind("s", $userAgent);
        $this->db->bind("s", $referrer);
        $this->db->bind("s", $deviceType);
        $this->db->execute();
        
        // Update view count
        $this->db->query("UPDATE profiles SET views = views + 1 WHERE id = ?");
        $this->db->bind("i", $profileId);
        $this->db->execute();
    }
    
    /**
     * Detect device type
     */
    private function detectDevice() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Click link tracking
     */
    public function trackLinkClick($linkId, $userId) {
        $eventType = 'click';
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $deviceType = $this->detectDevice();
        
        $this->db->query("INSERT INTO analytics (user_id, link_id, event_type, ip_address, user_agent, referrer, device_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $this->db->bind("i", $userId);
        $this->db->bind("i", $linkId);
        $this->db->bind("s", $eventType);
        $this->db->bind("s", $ipAddress);
        $this->db->bind("s", $userAgent);
        $this->db->bind("s", $referrer);
        $this->db->bind("s", $deviceType);
        $this->db->execute();
        
        // Update link click count
        $this->db->query("UPDATE links SET click_count = click_count + 1 WHERE id = ?");
        $this->db->bind("i", $linkId);
        $this->db->execute();
    }
}
?>
