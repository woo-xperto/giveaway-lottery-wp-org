<?php 
/*
Plugin Name: Giveaway Lottery
Requires Plugins: woocommerce
Plugin URI: http://wooxperto.com/plugins/giveaway-lottery
Description: A comprehensive Giveaway management system based on WooCommerce WordPress. # Designed, Developed, Maintained & Supported by wooXperto.
Version: 1.0.0
Author: Team WooXperto
Author URI: http://wooxperto.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: giveaway-lottery

*/
// gswc
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// plugin constants
define("WXGIVEAWAY_VERSION", "1.0.1");
define( 'WXGIVEAWAY_ACC_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' );
define( 'WXGIVEAWAY_ACC_PATH', plugin_dir_path( __FILE__ ) );

define('WXGIVEAWAY_TICKET_MIN_VALUE', 1);
define('WXGIVEAWAY_TICKET_MAX_VALUE', 99999999);

function wxgiveaway_giveaway_plugin_activate(){
    global $wpdb;
    $table_name = $wpdb->prefix.'wx_giveaway'; 
    $sql = "CREATE TABLE {$table_name} (
        id BIGINT NOT NULL AUTO_INCREMENT,
        order_id BIGINT,
        giveaways_id BIGINT,
        variation_id BIGINT,
        order_item_id BIGINT,
        ticket_no VARCHAR(250),
        PRIMARY KEY (id),
        INDEX (giveaways_id),
        INDEX idx_giveaways_variation (giveaways_id, variation_id),
        INDEX idx_order_giveaways_variation (order_id, giveaways_id, variation_id),
        INDEX idx_order_giveaways_variation_order_item (order_id, giveaways_id, variation_id, order_item_id)
    );";

    require_once (ABSPATH."wp-admin/includes/upgrade.php");
    dbDelta($sql);
    
    // create_generate_giveaway_tickets_procedure 
    $procedure_sql = "
        CREATE PROCEDURE GenerateWXGiveawayTickets(
            IN p_giveaway_id BIGINT,
            IN p_order_id BIGINT,
            IN p_ticket_count INT,
            IN p_min_ticket_number INT,
            IN p_max_ticket_number INT,
            IN p_order_item_id INT,
            IN p_variation_id INT
        )
        BEGIN
            DECLARE ticket_counter INT DEFAULT 0;
            DECLARE new_ticket_number INT;
            DECLARE available_tickets INT;

            -- Get the number of remaining available tickets for the given giveaway
            SELECT (p_max_ticket_number - p_min_ticket_number + 1) - COUNT(*) INTO available_tickets
            FROM {$table_name}
            WHERE giveaways_id = p_giveaway_id;

            -- If the requested number of tickets exceeds the available tickets, set it to the available tickets
            IF p_ticket_count > available_tickets THEN
                SET p_ticket_count = available_tickets;
            END IF;

            -- Loop to generate unique tickets
            WHILE ticket_counter < p_ticket_count DO
                SET new_ticket_number = FLOOR(RAND() * (p_max_ticket_number - p_min_ticket_number + 1) + p_min_ticket_number);

                -- Check if the generated ticket number is not already used for the given giveaway
                IF NOT EXISTS (
                    SELECT id
                    FROM {$table_name}
                    WHERE giveaways_id = p_giveaway_id AND ticket_no = new_ticket_number
                ) THEN
                    -- Insert the new ticket
                    INSERT INTO {$table_name} (giveaways_id, ticket_no, order_id, order_item_id, variation_id)
                    VALUES (p_giveaway_id, new_ticket_number, p_order_id, p_order_item_id, p_variation_id);
                    SET ticket_counter = ticket_counter + 1;
                END IF;
            END WHILE;
        END;
    ";

    // Drop the procedure if it already exists
    $wpdb->query("DROP PROCEDURE IF EXISTS GenerateWXGiveawayTickets");

    // Create the procedure
    $wpdb->query($procedure_sql);

    // add default settings data
    $wxgiveaway_default_settings = array(
        'ticket_generate'=>'processing',
        'ticket_delete_at'=>'cancelled',
        'ticket_send'=>'',
        'ticket_style'=>'style1',
        'logo_url'=>'',
  
    );

    if (get_option('wxgiveaway_settings') === false) {
        add_option('wxgiveaway_settings', $wxgiveaway_default_settings);
    } 
}
register_activation_hook(__FILE__, "wxgiveaway_giveaway_plugin_activate");

// ======= Registering wxGiveaway files =======
add_action('wp_enqueue_scripts', 'wxgiveaway_fontend_assets');
function wxgiveaway_fontend_assets() {

  wp_enqueue_style('wxgiveaway_custom_style', plugin_dir_url(__FILE__) . 'inc/frontend/assets/css/wxgiveaway_style.css');

  wp_enqueue_script('wxgiveaway_custom_script', plugin_dir_url(__FILE__ ) . 'inc/frontend/assets/js/js.js', array('jquery'), true);

  wp_localize_script('wxgiveaway_custom_script', 'gswcAjax', array(
      'ajaxurl'=> admin_url('admin-ajax.php'),
      'cartUrl'=> wc_get_cart_url()
  ));

}

add_action('admin_enqueue_scripts', 'wxgiveaway_backend_assets');
function wxgiveaway_backend_assets() {
    
  wp_enqueue_style('gift-card', plugin_dir_url(__FILE__) . 'inc/admin/assets/css/wxgiveaway_backend_style.css');

  // Ensure jQuery is enqueued first
  wp_enqueue_script('jquery'); 
  wp_enqueue_script('wp_admin_script', plugin_dir_url(__FILE__ ) . 'inc/admin/assets/js/wp_admin.js', array('jquery'), '5.0.55', true );

  wp_localize_script('wp_admin_script', 'gswcBkAjax', array(
    'url'=> admin_url('admin-ajax.php'),
    'cartUrl'=> wc_get_cart_url()
  ));

}


require_once 'inc/admin/giveaway-settings.php';
require_once 'inc/frontend/giveaway-tickets.php';
require_once 'inc/frontend/wxgiveaway-function.php';




