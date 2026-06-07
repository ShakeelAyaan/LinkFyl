<?php
/**
 * Analytics Class
 * Handles analytics and reporting
 */

class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get profile analytics overview
     */
    public function getProfileAnalytics($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT COUNT(*) as total_views FROM analytics WHERE user_id = ? AND event_type = 'view' AND DATE(timestamp) >= ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        $views = $this->db->single();
        
        $this->db->query("SELECT COUNT(*) as total_clicks FROM analytics WHERE user_id = ? AND event_type = 'click' AND DATE(timestamp) >= ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        $clicks = $this->db->single();
        
        $this->db->query("SELECT COUNT(DISTINCT DATE(timestamp)) as active_days FROM analytics WHERE user_id = ? AND DATE(timestamp) >= ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        $activeDays = $this->db->single();
        
        return [
            'total_views' => $views['total_views'],
            'total_clicks' => $clicks['total_clicks'],
            'active_days' => $activeDays['active_days'],
            'average_daily_views' => $activeDays['active_days'] > 0 ? round($views['total_views'] / $activeDays['active_days']) : 0
        ];
    }
    
    /**
     * Get daily analytics
     */
    public function getDailyAnalytics($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT DATE(timestamp) as date, event_type, COUNT(*) as count FROM analytics WHERE user_id = ? AND DATE(timestamp) >= ? GROUP BY DATE(timestamp), event_type ORDER BY date DESC");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        return $this->db->resultSet();
    }
    
    /**
     * Get device analytics
     */
    public function getDeviceAnalytics($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT device_type, COUNT(*) as count FROM analytics WHERE user_id = ? AND DATE(timestamp) >= ? GROUP BY device_type");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        return $this->db->resultSet();
    }
    
    /**
     * Get geographic analytics
     */
    public function getGeographicAnalytics($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT country, COUNT(*) as count FROM analytics WHERE user_id = ? AND country IS NOT NULL AND DATE(timestamp) >= ? GROUP BY country ORDER BY count DESC LIMIT 20");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        return $this->db->resultSet();
    }
    
    /**
     * Get link analytics
     */
    public function getLinkAnalytics($userId) {
        $this->db->query("SELECT l.id, l.title, l.url, l.click_count, COUNT(a.id) as recent_clicks FROM links l LEFT JOIN analytics a ON l.id = a.link_id AND a.event_type = 'click' AND DATE(a.timestamp) >= DATE_SUB(NOW(), INTERVAL 30 DAY) WHERE l.user_id = ? AND l.deleted_at IS NULL GROUP BY l.id ORDER BY recent_clicks DESC");
        $this->db->bind("i", $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get top referrers
     */
    public function getTopReferrers($userId, $days = 30, $limit = 10) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT referrer, COUNT(*) as count FROM analytics WHERE user_id = ? AND referrer IS NOT NULL AND DATE(timestamp) >= ? GROUP BY referrer ORDER BY count DESC LIMIT ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        $this->db->bind("i", $limit);
        return $this->db->resultSet();
    }
    
    /**
     * Get top browsers
     */
    public function getTopBrowsers($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT browser, COUNT(*) as count FROM analytics WHERE user_id = ? AND browser IS NOT NULL AND DATE(timestamp) >= ? GROUP BY browser ORDER BY count DESC");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        return $this->db->resultSet();
    }
    
    /**
     * Get conversion rate
     */
    public function getConversionRate($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT COUNT(DISTINCT CASE WHEN event_type = 'view' THEN 1 END) as views, COUNT(DISTINCT CASE WHEN event_type = 'click' THEN 1 END) as clicks FROM analytics WHERE user_id = ? AND DATE(timestamp) >= ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        $result = $this->db->single();
        
        if ($result['views'] == 0) {
            return 0;
        }
        
        return round(($result['clicks'] / $result['views']) * 100, 2);
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity($userId, $limit = 20) {
        $this->db->query("SELECT a.*, l.title as link_title FROM analytics a LEFT JOIN links l ON a.link_id = l.id WHERE a.user_id = ? ORDER BY a.timestamp DESC LIMIT ?");
        $this->db->bind("i", $userId);
        $this->db->bind("i", $limit);
        return $this->db->resultSet();
    }
    
    /**
     * Get hourly traffic
     */
    public function getHourlyTraffic($userId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $this->db->query("SELECT HOUR(timestamp) as hour, COUNT(*) as count FROM analytics WHERE user_id = ? AND DATE(timestamp) = ? GROUP BY HOUR(timestamp) ORDER BY hour ASC");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $date);
        return $this->db->resultSet();
    }
    
    /**
     * Get weekly traffic
     */
    public function getWeeklyTraffic($userId) {
        $this->db->query("SELECT WEEK(timestamp) as week, YEAR(timestamp) as year, COUNT(*) as count FROM analytics WHERE user_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 12 WEEK) GROUP BY WEEK(timestamp), YEAR(timestamp) ORDER BY year DESC, week DESC");
        $this->db->bind("i", $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get monthly traffic
     */
    public function getMonthlyTraffic($userId) {
        $this->db->query("SELECT MONTH(timestamp) as month, YEAR(timestamp) as year, COUNT(*) as count FROM analytics WHERE user_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY MONTH(timestamp), YEAR(timestamp) ORDER BY year DESC, month DESC");
        $this->db->bind("i", $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get comparison analytics
     */
    public function getComparisonAnalytics($userId, $days1 = 7, $days2 = 14) {
        $startDate1 = date('Y-m-d', strtotime("-$days1 days"));
        $endDate1 = date('Y-m-d', strtotime("-" . ($days1 - 1) . " days"));
        
        $startDate2 = date('Y-m-d', strtotime("-$days2 days"));
        $endDate2 = date('Y-m-d', strtotime("-" . ($days2 - 1) . " days"));
        
        $this->db->query("SELECT COUNT(*) as views FROM analytics WHERE user_id = ? AND event_type = 'view' AND DATE(timestamp) BETWEEN ? AND ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate1);
        $this->db->bind("s", $endDate1);
        $period1Views = $this->db->single();
        
        $this->db->query("SELECT COUNT(*) as views FROM analytics WHERE user_id = ? AND event_type = 'view' AND DATE(timestamp) BETWEEN ? AND ?");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate2);
        $this->db->bind("s", $endDate2);
        $period2Views = $this->db->single();
        
        $change = $period2Views['views'] > 0 ? (($period1Views['views'] - $period2Views['views']) / $period2Views['views']) * 100 : 0;
        
        return [
            'period1' => $period1Views['views'],
            'period2' => $period2Views['views'],
            'change' => round($change, 2),
            'positive' => $change > 0
        ];
    }
    
    /**
     * Export analytics to CSV
     */
    public function exportAnalyticsCSV($userId, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("SELECT DATE(timestamp) as date, event_type, device_type, country, COUNT(*) as count FROM analytics WHERE user_id = ? AND DATE(timestamp) >= ? GROUP BY DATE(timestamp), event_type, device_type, country ORDER BY date DESC");
        $this->db->bind("i", $userId);
        $this->db->bind("s", $startDate);
        $data = $this->db->resultSet();
        
        $csv = "Date,Event Type,Device Type,Country,Count\n";
        
        foreach ($data as $row) {
            $csv .= $row['date'] . "," . $row['event_type'] . "," . $row['device_type'] . "," . ($row['country'] ?? 'Unknown') . "," . $row['count'] . "\n";
        }
        
        return $csv;
    }
    
    /**
     * Clean old analytics data
     */
    public function cleanOldAnalytics($days = 90) {
        $cutoffDate = date('Y-m-d', strtotime("-$days days"));
        
        $this->db->query("DELETE FROM analytics WHERE DATE(timestamp) < ?");
        $this->db->bind("s", $cutoffDate);
        return $this->db->execute();
    }
}
?>
