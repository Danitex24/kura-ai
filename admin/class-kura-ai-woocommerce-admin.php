<?php
/**
 * The WooCommerce-specific admin functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 * @author     Your Name <your-email@example.com>
 */
class Kura_AI_WooCommerce_Admin {

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
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_kura_ai_run_store_audit', array( $this, 'ajax_run_store_audit' ) );
        add_action( 'wp_ajax_kura_ai_run_competitor_audit', array( $this, 'ajax_run_competitor_audit' ) );
    }

    /**
     * Enqueue the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-woocommerce-admin',
            plugin_dir_url( __FILE__ ) . 'css/kura-ai-woocommerce-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-woocommerce-admin',
            plugin_dir_url( __FILE__ ) . 'js/kura-ai-woocommerce-admin.js',
            array( 'jquery' ),
            $this->version,
            false
        );

        wp_localize_script(
            $this->plugin_name . '-woocommerce-admin',
            'kura_ai_woocommerce_admin',
            array(
                'nonce' => wp_create_nonce( 'kura_ai_run_store_audit' ),
                'competitor_nonce' => wp_create_nonce( 'kura_ai_run_competitor_audit' ),
            )
        );
    }

    /**
     * Add the WooCommerce admin menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        if ( class_exists( 'WooCommerce' ) ) {
            add_submenu_page(
                'kura-ai',
                __( 'AI Store Audit', 'kura-ai' ),
                __( 'AI Store Audit', 'kura-ai' ),
                'manage_options',
                'kura-ai-store-audit',
                array( $this, 'display_store_audit_page' )
            );

            add_submenu_page(
                'kura-ai',
                __( 'Competitor Audit', 'kura-ai' ),
                __( 'Competitor Audit', 'kura-ai' ),
                'manage_options',
                'kura-ai-competitor-audit',
                array( $this, 'display_competitor_audit_page' )
            );
        }
    }

    /**
     * Display the AI Store Audit page.
     *
     * @since    1.0.0
     */
    public function display_store_audit_page() {
        include_once 'partials/kura-ai-woocommerce-audit-display.php';
    }

    /**
     * Display the Competitor Audit page.
     *
     * @since    1.0.0
     */
    public function display_competitor_audit_page() {
        include_once 'partials/kura-ai-competitor-audit-display.php';
    }

    /**
     * AJAX handler for running the store audit.
     *
     * @since    1.0.0
     */
    public function ajax_run_store_audit() {
        check_ajax_referer( 'kura_ai_run_store_audit', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have sufficient permissions to perform this action.', 'kura-ai' ) );
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( __( 'WooCommerce is not installed or activated.', 'kura-ai' ) );
        }

        // Fetch WooCommerce data
        $products = wc_get_products( array( 'limit' => -1 ) );
        $categories = get_terms( 'product_cat' );
        $orders = wc_get_orders( array( 'limit' => -1 ) );
        $total_revenue = 0;
        foreach ( $orders as $order ) {
            $total_revenue += $order->get_total();
        }

        // Prepare data for AI
        $data = array(
            'products' => array(),
            'categories' => array(),
            'total_revenue' => $total_revenue,
            'total_orders' => count( $orders ),
        );

        foreach ( $products as $product ) {
            $data['products'][] = array(
                'name' => $product->get_name(),
                'description' => $product->get_description(),
            );
        }

        foreach ( $categories as $category ) {
            $data['categories'][] = $category->name;
        }

        // Create the prompt for the AI
        $prompt = "Analyze the following WooCommerce store data and provide 10 suggestions for improvement. Focus on product descriptions, SEO, store performance, conversion optimization, and user experience.\n\n" . json_encode( $data, JSON_PRETTY_PRINT );

        // Send the prompt to the AI
        $suggestion = KuraAI_Helper::run_ai_prompt( $prompt );

        wp_send_json_success( array( 'suggestion' => $suggestion ) );
    }

    /**
     * AJAX handler for running the competitor audit.
     *
     * @since    1.0.0
     */
    public function ajax_run_competitor_audit() {
        check_ajax_referer( 'kura_ai_run_competitor_audit', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have sufficient permissions to perform this action.', 'kura-ai' ) );
        }

        if ( empty( $_POST['url'] ) ) {
            wp_send_json_error( __( 'Please enter a competitor URL.', 'kura-ai' ) );
        }

        $url = esc_url_raw( $_POST['url'] );

        // Create the prompt for the AI
        $prompt = "Analyze the following competitor's website and provide 2-3 key summary points about their strategy. Focus on their products, pricing, and marketing.\n\n" . $url;

        // Send the prompt to the AI
        $suggestion = KuraAI_Helper::run_ai_prompt( $prompt );

        wp_send_json_success( array( 'suggestion' => $suggestion ) );
    }
}
