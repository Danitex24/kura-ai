<?php
/**
 * Handles the WooCommerce hooks.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Your Name <your-email@example.com>
 */
class Kura_AI_WooCommerce_Hooks {

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

        $options = get_option( 'kura_ai_settings' );
        if ( ! empty( $options['woocommerce_activity_tracking'] ) ) {
            add_action( 'wp_head', array( $this, 'track_product_page_view' ) );
            add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 6 );
        }
    }

    /**
     * Track product page views.
     *
     * @since    1.0.0
     */
    public function track_product_page_view() {
        if ( is_product() ) {
            $product_id = get_the_ID();
            $this->log_activity( 'product_view', $product_id );
        }
    }

    /**
     * Track add to cart events.
     *
     * @since    1.0.0
     */
    public function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        $this->log_activity( 'add_to_cart', $product_id, array( 'quantity' => $quantity ) );
    }

    /**
     * Log activity to the database.
     *
     * @since    1.0.0
     */
    private function log_activity( $type, $product_id, $data = array() ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kura_ai_activity_logs';
        $wpdb->insert(
            $table_name,
            array(
                'type' => $type,
                'product_id' => $product_id,
                'data' => json_encode( $data ),
                'date' => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Log a new order.
     *
     * @since    1.0.0
     * @param    int    $order_id    The ID of the order.
     */
    public function new_order( $order_id ) {
        // In a real application, you would save this to a custom table.
        update_option( 'kura_ai_last_order', array(
            'date' => current_time( 'mysql' ),
            'order_id' => $order_id,
        ) );
    }

    /**
     * Log a product update.
     *
     * @since    1.0.0
     * @param    int    $product_id    The ID of the product.
     */
    public function update_product( $product_id ) {
        // In a real application, you would save this to a custom table.
        update_option( 'kura_ai_last_product_update', array(
            'date' => current_time( 'mysql' ),
            'product_id' => $product_id,
        ) );
    }

    /**
     * Log an order status change.
     *
     * @since    1.0.0
     * @param    int      $order_id    The ID of the order.
     * @param    string   $old_status  The old status of the order.
     * @param    string   $new_status  The new status of the order.
     */
    public function order_status_changed( $order_id, $old_status, $new_status ) {
        // In a real application, you would save this to a custom table.
        update_option( 'kura_ai_last_order_status_change', array(
            'date' => current_time( 'mysql' ),
            'order_id' => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
        ) );
    }
}
