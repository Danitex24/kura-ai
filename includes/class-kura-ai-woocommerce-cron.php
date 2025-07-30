<?php
/**
 * Handles the scheduled WooCommerce checkups.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Your Name <your-email@example.com>
 */
class Kura_AI_WooCommerce_Cron {

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
    }

    /**
     * Schedule the cron job.
     *
     * @since    1.0.0
     */
    public function schedule_cron() {
        $options = get_option( 'kura_ai_settings' );
        $schedule = isset( $options['woocommerce_schedule'] ) ? $options['woocommerce_schedule'] : 'disabled';

        if ( 'disabled' !== $schedule && ! wp_next_scheduled( 'kura_ai_woocommerce_checkup' ) ) {
            wp_schedule_event( time(), $schedule, 'kura_ai_woocommerce_checkup' );
        } elseif ( 'disabled' === $schedule && wp_next_scheduled( 'kura_ai_woocommerce_checkup' ) ) {
            wp_clear_scheduled_hook( 'kura_ai_woocommerce_checkup' );
        }
    }

    /**
     * Run the WooCommerce checkup.
     *
     * @since    1.0.0
     */
    public function run_checkup() {
        // The same logic as the manual audit can be used here.
        // In a real application, you would probably want to refactor this into a shared function.
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
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

        // Save the audit to the database
        // In a real application, you would save this to a custom table.
        update_option( 'kura_ai_last_audit', array(
            'date' => current_time( 'mysql' ),
            'suggestion' => $suggestion,
        ) );
    }
}
