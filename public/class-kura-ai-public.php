<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/public
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
 */
class Kura_AI_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/kura-ai-public.js',
            array('jquery'),
            $this->version,
            false,
        );

        // Localize the script with PHP data for JS
        wp_localize_script(
            $this->plugin_name, // Same handle as used in wp_enqueue_script()
            'kura_ai_public',  // JS object name
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kura_ai_public_nonce')
            ],
        );
    }
}