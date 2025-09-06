<?php

namespace Kura_AI;

if (!defined('ABSPATH')) {
    exit;
}

// Import WordPress core files and functions
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-includes/pluggable.php';

// Import WordPress core classes
use \WP_Error;
use \Exception;

// Import WordPress functions
use function \get_option;
use function \update_option;
use function \wp_kses_post;
use function \sanitize_text_field;
use function \current_time;
use function \esc_html__;
use function \esc_html;
use function \__;
use function \wp_die;
use function \is_wp_error;
use function \wp_remote_get;
use function \wp_remote_post;
use function \wp_remote_retrieve_body;
use function \wp_remote_retrieve_response_code;
use function \wp_json_encode;
use function \sprintf;

// Import WordPress constants
use const \ABSPATH;
use const \WP_DEBUG;

class Kura_AI_File_Monitor {
    private $monitored_files;
    private $version_table;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->version_table = $wpdb->prefix . 'kura_ai_file_versions';
        $this->monitored_files = get_option('kura_ai_monitored_files', array());
    }

    public function add_monitored_file($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('invalid_file', esc_html__('File does not exist', 'kura-ai'));
        }

        if (!in_array($file_path, $this->monitored_files)) {
            $this->monitored_files[] = $file_path;
            update_option('kura_ai_monitored_files', $this->monitored_files);
            $this->create_version($file_path, 'Initial version');
        }

        return true;
    }

    public function remove_monitored_file($file_path) {
        $key = array_search($file_path, $this->monitored_files);
        if ($key !== false) {
            unset($this->monitored_files[$key]);
            $this->monitored_files = array_values($this->monitored_files);
            update_option('kura_ai_monitored_files', $this->monitored_files);
            return true;
        }
        return false;
    }

    public function create_version($file_path, $description = '') {
        if (!file_exists($file_path)) {
            return new WP_Error('invalid_file', esc_html__('File does not exist', 'kura-ai'));
        }

        $content = wp_kses_post(file_get_contents($file_path));
        $hash = md5($content);

        $this->wpdb->insert(
            $this->version_table,
            array(
                'file_path' => $file_path,
                'content' => $content,
                'hash' => $hash,
                'description' => sanitize_text_field($description),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        return $this->wpdb->insert_id;
    }

    public function rollback_version($version_id) {
        $version = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE id = %d",
                $version_id
            )
        );

        if (!$version) {
            return new WP_Error('invalid_version', esc_html__('Version not found', 'kura-ai'));
        }

        if (!file_exists($version->file_path)) {
            return new WP_Error('invalid_file', esc_html__('Original file no longer exists', 'kura-ai'));
        }

        $result = file_put_contents($version->file_path, $version->content);
        if ($result === false) {
            return new WP_Error('write_error', esc_html__('Failed to write file content', 'kura-ai'));
        }

        return true;
    }

    public function get_file_versions($file_path) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE file_path = %s ORDER BY created_at DESC",
                $file_path
            )
        );
    }

    public function compare_versions($version_id_1, $version_id_2) {
        $version1 = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE id = %d",
                $version_id_1
            )
        );

        $version2 = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->version_table} WHERE id = %d",
                $version_id_2
            )
        );

        if (!$version1 || !$version2) {
            return new WP_Error('invalid_version', esc_html__('One or both versions not found', 'kura-ai'));
        }

        return array(
            'version1' => array(
                'content' => $version1->content,
                'created_at' => $version1->created_at,
                'description' => $version1->description
            ),
            'version2' => array(
                'content' => $version2->content,
                'created_at' => $version2->created_at,
                'description' => $version2->description
            )
        );
    }

    public function get_monitored_files() {
        return $this->monitored_files;
    }
}