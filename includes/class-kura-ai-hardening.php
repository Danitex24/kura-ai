<?php
/**
 * The security hardening functionality of the plugin.
 *
 * @link       https://yourdomain.com
 * @since      1.0.0
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 */

namespace Kura_AI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Import WordPress constants
if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// Import WordPress functions
use function get_option;
use function update_option;
use function esc_html__;
use function wp_kses_post;

/**
 * The security hardening class.
 *
 * This class handles automated security hardening through .htaccess rules
 * and database optimizations.
 *
 * @since      1.0.0
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Your Name <email@example.com>
 */
class Kura_AI_Hardening {

    /**
     * The path to the .htaccess file.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $htaccess_path    The path to the .htaccess file.
     */
    private $htaccess_path;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->htaccess_path = ABSPATH . '.htaccess';
    }

    /**
     * Apply security hardening rules to .htaccess.
     *
     * @since    1.0.0
     * @return   array    The result of the operation.
     */
    public function apply_htaccess_rules() {
        if (!is_writable($this->htaccess_path)) {
            return array(
                'success' => false,
                'message' => __('The .htaccess file is not writable.', 'kura-ai')
            );
        }

        $rules = $this->get_security_rules();
        $current_content = file_get_contents($this->htaccess_path);

        // Remove any existing Kura AI security rules
        $pattern = '/# BEGIN Kura AI Security.*# END Kura AI Security\n/s';
        $current_content = preg_replace($pattern, '', $current_content);

        // Add new rules at the beginning of the file
        $new_content = "# BEGIN Kura AI Security\n";
        $new_content .= $rules;
        $new_content .= "# END Kura AI Security\n\n";
        $new_content .= $current_content;

        if (file_put_contents($this->htaccess_path, $new_content)) {
            return array(
                'success' => true,
                'message' => __('Security rules have been applied successfully.', 'kura-ai')
            );
        }

        return array(
            'success' => false,
            'message' => __('Failed to apply security rules.', 'kura-ai')
        );
    }

    /**
     * Get the security rules for .htaccess.
     *
     * @since    1.0.0
     * @return   string    The security rules.
     */
    private function get_security_rules() {
        $rules = "# Disable directory browsing\n";
        $rules .= "Options -Indexes\n\n";

        $rules .= "# Protect .htaccess file\n";
        $rules .= "<Files .htaccess>\n";
        $rules .= "    Order allow,deny\n";
        $rules .= "    Deny from all\n";
        $rules .= "</Files>\n\n";

        $rules .= "# Protect wp-config.php\n";
        $rules .= "<Files wp-config.php>\n";
        $rules .= "    Order allow,deny\n";
        $rules .= "    Deny from all\n";
        $rules .= "</Files>\n\n";

        $rules .= "# Block access to sensitive files\n";
        $rules .= "<FilesMatch \"^.*\\.(log|txt|md|json|zip|tar|gz|sql)$\">\n";
        $rules .= "    Order allow,deny\n";
        $rules .= "    Deny from all\n";
        $rules .= "</FilesMatch>\n\n";

        $rules .= "# Prevent script injection\n";
        $rules .= "<FilesMatch \"\\.ph(p[3-5]?|t|tml)\\$\">\n";
        $rules .= "    SetHandler application/x-httpd-php\n";
        $rules .= "</FilesMatch>\n\n";

        $rules .= "# Block bad bots\n";
        $rules .= "RewriteEngine On\n";
        $rules .= "RewriteCond %{HTTP_USER_AGENT} ^$ [NC,OR]\n";
        $rules .= "RewriteCond %{HTTP_USER_AGENT} ^(java|curl|wget).* [NC,OR]\n";
        $rules .= "RewriteCond %{HTTP_USER_AGENT} ^.*(winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner).* [NC]\n";
        $rules .= "RewriteRule .* - [F,L]\n\n";

        $rules .= "# Prevent hotlinking\n";
        $rules .= "RewriteCond %{HTTP_REFERER} !^$\n";
        $rules .= "RewriteCond %{HTTP_REFERER} !^http(s)?://(www\\.)?" . $_SERVER['HTTP_HOST'] . " [NC]\n";
        $rules .= "RewriteRule \\.(jpg|jpeg|png|gif|svg)$ - [NC,F,L]\n";

        return $rules;
    }

    /**
     * Optimize the WordPress database.
     *
     * @since    1.0.0
     * @return   array    The result of the operation.
     */
    public function optimize_database() {
        global $wpdb;

        $optimizations = array(
            'post_revisions' => "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'",
            'auto_drafts' => "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'",
            'trashed_posts' => "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'",
            'spam_comments' => "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'",
            'trashed_comments' => "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'",
            'expired_transients' => "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%' AND option_value < UNIX_TIMESTAMP()"
        );

        $results = array();
        $total_cleaned = 0;

        foreach ($optimizations as $type => $query) {
            $cleaned = $wpdb->query($query);
            $results[$type] = $cleaned;
            $total_cleaned += $cleaned;
        }

        // Optimize tables
        $tables = $wpdb->get_results('SHOW TABLES', \ARRAY_N);
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table[0]}");
        }

        return array(
            'success' => true,
            'message' => sprintf(
                __('Database optimized successfully. Cleaned %d items.', 'kura-ai'),
                $total_cleaned
            ),
            'details' => $results
        );
    }

    /**
     * Get the current security status.
     *
     * @since    1.0.0
     * @return   array    The security status.
     */
    public function get_security_status() {
        $status = array();

        // Check .htaccess rules
        $htaccess_content = @file_get_contents($this->htaccess_path);
        $status['htaccess'] = array(
            'exists' => file_exists($this->htaccess_path),
            'writable' => is_writable($this->htaccess_path),
            'has_security_rules' => (strpos($htaccess_content, '# BEGIN Kura AI Security') !== false)
        );

        // Check database status
        global $wpdb;
        $status['database'] = array(
            'post_revisions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"),
            'auto_drafts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"),
            'trashed_posts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"),
            'spam_comments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"),
            'trashed_comments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'")
        );

        return $status;
    }
}