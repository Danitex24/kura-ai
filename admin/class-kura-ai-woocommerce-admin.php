<?php
/**
 * The WooCommerce-specific admin functionality of the plugin.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/admin
 * @author     Daniel Abughdyer <daniel@danovatesolutions.org>
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
        add_action( 'wp_ajax_kura_ai_export_audit', array( $this, 'ajax_export_audit' ) );
        add_action( 'wp_ajax_kura_ai_export_audit_pdf', array( $this, 'ajax_export_audit_pdf' ) );
        add_action( 'wp_ajax_kura_ai_get_chart_data', array( $this, 'ajax_get_chart_data' ) );
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
        wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true );
        wp_enqueue_script(
            $this->plugin_name . '-woocommerce-admin',
            plugin_dir_url( __FILE__ ) . 'js/kura-ai-woocommerce-admin.js',
            array( 'jquery', 'chartjs' ),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name . '-charts',
            plugin_dir_url( __FILE__ ) . 'js/kura-ai-charts.js',
            array( 'jquery', 'chartjs' ),
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_name . '-woocommerce-admin',
            'kura_ai_woocommerce_admin',
            array(
                'nonce' => wp_create_nonce( 'kura_ai_run_store_audit' ),
                'competitor_nonce' => wp_create_nonce( 'kura_ai_run_competitor_audit' ),
                'export_nonce' => wp_create_nonce( 'kura_ai_export_audit' ),
                'export_pdf_nonce' => wp_create_nonce( 'kura_ai_export_audit_pdf' ),
                'chart_nonce' => wp_create_nonce( 'kura_ai_get_chart_data' ),
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
                __( 'WooCommerce', 'kura-ai' ),
                __( 'WooCommerce', 'kura-ai' ),
                'manage_options',
                'kura-ai-woocommerce',
                array( $this, 'display_woocommerce_page' )
            );

            add_submenu_page(
                'kura-ai-woocommerce',
                __( 'AI Store Audit', 'kura-ai' ),
                __( 'AI Store Audit', 'kura-ai' ),
                'manage_options',
                'kura-ai-store-audit',
                array( $this, 'display_store_audit_page' )
            );

            add_submenu_page(
                'kura-ai-woocommerce',
                __( 'Competitor Audit', 'kura-ai' ),
                __( 'Competitor Audit', 'kura-ai' ),
                'manage_options',
                'kura-ai-competitor-audit',
                array( $this, 'display_competitor_audit_page' )
            );
        }
    }

    /**
     * Display the WooCommerce page.
     *
     * @since    1.0.0
     */
    public function display_woocommerce_page() {
        // This page can be used as a dashboard for all WooCommerce features.
        include_once 'partials/kura-ai-woocommerce-display.php';
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

        // Limit the response to 10 suggestions
        $suggestions = explode( "\n", $suggestion );
        $suggestions = array_slice( $suggestions, 0, 10 );
        $suggestion = implode( "\n", $suggestions );

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

    /**
     * AJAX handler for exporting the audit summary.
     *
     * @since    1.0.0
     */
    public function ajax_export_audit() {
        check_ajax_referer( 'kura_ai_export_audit', 'nonce' );

        if ( ! apply_filters( 'kuraai_report_export_allowed', current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( __( 'You do not have sufficient permissions to perform this action.', 'kura-ai' ) );
        }

        $last_audit = get_option( 'kura_ai_last_audit' );

        if ( empty( $last_audit ) ) {
            wp_send_json_error( __( 'No audit summary found.', 'kura-ai' ) );
        }

        $data = array(
            array( 'Date', 'Suggestion' ),
            array( $last_audit['date'], $last_audit['suggestion'] ),
        );

        Kura_AI_Export::generate_csv( $data, 'kura-ai-audit-' . date( 'Y-m-d' ) . '.csv' );
    }

    /**
     * AJAX handler for exporting the audit summary to PDF.
     *
     * @since    1.0.0
     */
    public function ajax_export_audit_pdf() {
        check_ajax_referer( 'kura_ai_export_audit_pdf', 'nonce' );

        if ( ! apply_filters( 'kuraai_report_export_allowed', current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( __( 'You do not have sufficient permissions to perform this action.', 'kura-ai' ) );
        }

        $last_audit = get_option( 'kura_ai_last_audit' );

        if ( empty( $last_audit ) ) {
            wp_send_json_error( __( 'No audit summary found.', 'kura-ai' ) );
        }

        $data = array(
            array( 'Date', 'Suggestion' ),
            array( $last_audit['date'], $last_audit['suggestion'] ),
        );

        Kura_AI_Export::generate_pdf( $data, 'kura-ai-audit-' . date( 'Y-m-d' ) . '.pdf' );
    }

    /**
     * AJAX handler for getting chart data.
     *
     * @since    1.0.0
     */
    public function ajax_get_chart_data() {
        check_ajax_referer( 'kura_ai_get_chart_data', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have sufficient permissions to perform this action.', 'kura-ai' ) );
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( __( 'WooCommerce is not installed or activated.', 'kura-ai' ) );
        }

        // Fetch data for the charts
        $sales_trend = $this->get_sales_trend_data();
        $top_selling_products = $this->get_top_selling_products_data();
        $category_distribution = $this->get_category_distribution_data();
        $store_health_evolution = $this->get_store_health_evolution_data();
        $suggestion_frequency = $this->get_suggestion_frequency_data();

        wp_send_json_success( array(
            'sales_trend' => $sales_trend,
            'top_selling_products' => $top_selling_products,
            'category_distribution' => $category_distribution,
            'store_health_evolution' => $store_health_evolution,
            'suggestion_frequency' => $suggestion_frequency,
        ) );
    }

    /**
     * Get sales trend data.
     *
     * @since    1.0.0
     */
    private function get_sales_trend_data() {
        // This is a placeholder. In a real application, you would query the database for sales data.
        return array(
            'labels' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July' ),
            'data' => array( 65, 59, 80, 81, 56, 55, 40 ),
        );
    }

    /**
     * Get top selling products data.
     *
     * @since    1.0.0
     */
    private function get_top_selling_products_data() {
        // This is a placeholder. In a real application, you would query the database for top selling products.
        return array(
            'labels' => array( 'Product A', 'Product B', 'Product C', 'Product D', 'Product E' ),
            'data' => array( 100, 80, 60, 40, 20 ),
        );
    }

    /**
     * Get category distribution data.
     *
     * @since    1.0.0
     */
    private function get_category_distribution_data() {
        // This is a placeholder. In a real application, you would query the database for category distribution.
        return array(
            'labels' => array( 'Category A', 'Category B', 'Category C' ),
            'data' => array( 300, 50, 100 ),
        );
    }

    /**
     * Get store health evolution data.
     *
     * @since    1.0.0
     */
    private function get_store_health_evolution_data() {
        // This is a placeholder. In a real application, you would query the database for store health evolution.
        return array(
            'labels' => array( 'Week 1', 'Week 2', 'Week 3', 'Week 4' ),
            'data' => array( 70, 80, 85, 90 ),
        );
    }

    /**
     * Get suggestion frequency data.
     *
     * @since    1.0.0
     */
    private function get_suggestion_frequency_data() {
        // This is a placeholder. In a real application, you would query the database for suggestion frequency.
        return array(
            'labels' => array( 'SEO', 'CRO', 'UX' ),
            'data' => array( 10, 5, 3 ),
        );
    }
}
