<?php
/**
 * TrueVault VPN - PageBuilder Helper Class
 * 
 * Manages pages, sections, and content blocks
 * All data stored in themes.db
 * 
 * USAGE:
 * $page = PageBuilder::getPage('home');
 * $sections = PageBuilder::getSections($pageId);
 * PageBuilder::addSection($pageId, 'hero', $data);
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class PageBuilder {
    /**
     * Get page by slug
     * 
     * @param string $slug Page slug (e.g., 'home', 'pricing')
     * @return array|null Page data or null if not found
     */
    public static function getPage($slug) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT * FROM pages 
                WHERE slug = ? AND is_active = 1
            ");
            $stmt->execute([$slug]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("PageBuilder::getPage() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get page by ID
     * 
     * @param int $pageId Page ID
     * @return array|null Page data or null if not found
     */
    public static function getPageById($pageId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$pageId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("PageBuilder::getPageById() error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all sections for a page
     * 
     * @param int $pageId Page ID
     * @param bool $visibleOnly Only return visible sections
     * @return array Array of sections
     */
    public static function getSections($pageId, $visibleOnly = true) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $sql = "SELECT * FROM page_sections WHERE page_id = ?";
            
            if ($visibleOnly) {
                $sql .= " AND is_visible = 1";
            }
            
            $sql .= " ORDER BY sort_order ASC";
            
            $stmt = $themesConn->prepare($sql);
            $stmt->execute([$pageId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("PageBuilder::getSections() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add section to page
     * 
     * @param int $pageId Page ID
     * @param string $type Section type (hero, features, pricing, etc.)
     * @param array $data Section data as array (will be JSON encoded)
     * @param int|null $order Sort order (null = append to end)
     * @return int|false Section ID or false on failure
     */
    public static function addSection($pageId, $type, $data, $order = null) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            // If no order specified, append to end
            if ($order === null) {
                $stmt = $themesConn->prepare("
                    SELECT MAX(sort_order) as max_order FROM page_sections WHERE page_id = ?
                ");
                $stmt->execute([$pageId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $order = ($result['max_order'] ?? 0) + 1;
            }
            
            // Encode data as JSON
            $jsonData = json_encode($data);
            
            $stmt = $themesConn->prepare("
                INSERT INTO page_sections (page_id, section_type, section_data, sort_order, is_visible)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$pageId, $type, $jsonData, $order]);
            
            return $themesConn->lastInsertId();
            
        } catch (Exception $e) {
            error_log("PageBuilder::addSection() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update section data
     * 
     * @param int $sectionId Section ID
     * @param array $data New section data
     * @return bool Success status
     */
    public static function updateSection($sectionId, $data) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $jsonData = json_encode($data);
            
            $stmt = $themesConn->prepare("
                UPDATE page_sections 
                SET section_data = ?
                WHERE id = ?
            ");
            $stmt->execute([$jsonData, $sectionId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("PageBuilder::updateSection() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete section
     * 
     * @param int $sectionId Section ID
     * @return bool Success status
     */
    public static function deleteSection($sectionId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("DELETE FROM page_sections WHERE id = ?");
            $stmt->execute([$sectionId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("PageBuilder::deleteSection() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reorder sections
     * 
     * @param int $pageId Page ID
     * @param array $orderArray Array of section IDs in new order
     * @return bool Success status
     */
    public static function reorderSections($pageId, $orderArray) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $themesConn->beginTransaction();
            
            $stmt = $themesConn->prepare("
                UPDATE page_sections 
                SET sort_order = ?
                WHERE id = ? AND page_id = ?
            ");
            
            foreach ($orderArray as $index => $sectionId) {
                $stmt->execute([$index, $sectionId, $pageId]);
            }
            
            $themesConn->commit();
            
            return true;
            
        } catch (Exception $e) {
            error_log("PageBuilder::reorderSections() error: " . $e->getMessage());
            if ($themesConn->inTransaction()) {
                $themesConn->rollBack();
            }
            return false;
        }
    }
    
    /**
     * Toggle section visibility
     * 
     * @param int $sectionId Section ID
     * @param bool $visible Visibility status
     * @return bool Success status
     */
    public static function toggleVisibility($sectionId, $visible) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                UPDATE page_sections 
                SET is_visible = ?
                WHERE id = ?
            ");
            $stmt->execute([$visible ? 1 : 0, $sectionId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("PageBuilder::toggleVisibility() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Render complete page with all sections
     * 
     * @param string $slug Page slug
     * @return string|null Rendered HTML or null if page not found
     */
    public static function render($slug) {
        $page = self::getPage($slug);
        
        if (!$page) {
            return null;
        }
        
        $sections = self::getSections($page['id']);
        
        ob_start();
        
        foreach ($sections as $section) {
            self::renderSection($section);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render individual section
     * 
     * @param array $section Section data from database
     */
    public static function renderSection($section) {
        $type = $section['section_type'];
        $data = json_decode($section['section_data'], true);
        
        // Include section template
        $templatePath = __DIR__ . '/../templates/sections/' . $type . '.php';
        
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo "<!-- Section template not found: $type -->\n";
        }
    }
    
    /**
     * Save page revision
     * 
     * @param int $pageId Page ID
     * @param int $adminId Admin user ID who made changes
     * @return int|false Revision ID or false on failure
     */
    public static function saveRevision($pageId, $adminId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            // Get current page and sections
            $page = self::getPageById($pageId);
            $sections = self::getSections($pageId, false); // Get all sections including hidden
            
            $revisionData = [
                'page' => $page,
                'sections' => $sections
            ];
            
            $jsonData = json_encode($revisionData);
            
            $stmt = $themesConn->prepare("
                INSERT INTO page_revisions (page_id, revision_data, created_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pageId, $jsonData, $adminId]);
            
            // Keep only last 10 revisions
            $stmt = $themesConn->prepare("
                DELETE FROM page_revisions 
                WHERE page_id = ? 
                AND id NOT IN (
                    SELECT id FROM page_revisions 
                    WHERE page_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                )
            ");
            $stmt->execute([$pageId, $pageId]);
            
            return $themesConn->lastInsertId();
            
        } catch (Exception $e) {
            error_log("PageBuilder::saveRevision() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get page revisions
     * 
     * @param int $pageId Page ID
     * @param int $limit Number of revisions to retrieve
     * @return array Array of revisions
     */
    public static function getRevisions($pageId, $limit = 10) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT * FROM page_revisions 
                WHERE page_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$pageId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("PageBuilder::getRevisions() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Restore page from revision
     * 
     * @param int $revisionId Revision ID
     * @return bool Success status
     */
    public static function restoreRevision($revisionId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            // Get revision data
            $stmt = $themesConn->prepare("SELECT page_id, revision_data FROM page_revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revision) {
                return false;
            }
            
            $data = json_decode($revision['revision_data'], true);
            $pageId = $revision['page_id'];
            
            $themesConn->beginTransaction();
            
            // Delete current sections
            $stmt = $themesConn->prepare("DELETE FROM page_sections WHERE page_id = ?");
            $stmt->execute([$pageId]);
            
            // Restore sections from revision
            foreach ($data['sections'] as $section) {
                $stmt = $themesConn->prepare("
                    INSERT INTO page_sections (page_id, section_type, section_data, sort_order, is_visible)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pageId,
                    $section['section_type'],
                    $section['section_data'],
                    $section['sort_order'],
                    $section['is_visible']
                ]);
            }
            
            $themesConn->commit();
            
            return true;
            
        } catch (Exception $e) {
            error_log("PageBuilder::restoreRevision() error: " . $e->getMessage());
            if ($themesConn->inTransaction()) {
                $themesConn->rollBack();
            }
            return false;
        }
    }
    
    /**
     * List all pages
     * 
     * @param bool $publicOnly Only return public pages
     * @return array Array of pages
     */
    public static function listPages($publicOnly = false) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $sql = "SELECT * FROM pages WHERE is_active = 1";
            
            if ($publicOnly) {
                $sql .= " AND is_public = 1";
            }
            
            $sql .= " ORDER BY sort_order ASC, title ASC";
            
            $stmt = $themesConn->query($sql);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("PageBuilder::listPages() error: " . $e->getMessage());
            return [];
        }
    }
}
?>
