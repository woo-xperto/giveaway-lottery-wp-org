<?php
require_once __DIR__.'/menu/wxgiveaway-dashboard-left-sid.php';
require_once('giveaway-options.php');
add_filter( 'product_type_options', 'wxgive_enable_giveaway');
function wxgive_enable_giveaway( $product_type_options ) {
    global $post;
    $product_type_options['giveaways'] = array(
        'id'            => '_enable_giveaway',
        'wrapper_class' => '',
        'label'         => __( 'Is it a single giveaway?', 'giveaway-lottery' ),
        'default'       => get_post_meta($post->ID,'_enable_giveaway',true),
        'description'=>__('By checking this, the product will be treated a giveaway item','giveaway-lottery')
    );

    return $product_type_options;

}
// add giveaway settings table under product data tab
add_filter('woocommerce_product_data_tabs', 'wxgiveaway_giveaway_settings_tab');
function wxgiveaway_giveaway_settings_tab($tabs) {
    $tabs['giveaway_settings'] = array(
        'label'    => __('Giveaway Settings', 'giveaway-lottery'),
        'target'   => 'giveaway_settings_product_data',
        'class'    => array(),  // Show for simple and variable products
    );

    return $tabs;
}

// giveaway settings tab content
add_action('woocommerce_product_data_panels', 'wxgiveaway_giveaway_settings_fields');
function wxgiveaway_giveaway_settings_fields() {
    global $woocommerce, $post;

    // Add nonce field
    wp_nonce_field( 'wxgiveaway_setting_save_meta_box', 'wxgiveaway_setting_meta_box_nonce' );

    echo '<div id="giveaway_settings_product_data" class="panel woocommerce_options_panel hidden">';

    // Number: No of Tickets
    woocommerce_wp_text_input(
        array(
            'id'            => '_no_of_tickets',
            'wrapper_class' => 'show_if_simple',
            'label'         => esc_html__( 'No of Tickets', 'giveaway-lottery' ),
            'type'          => 'number',
            'wx_giveaway_attributes' => array(
                'step' => '1',
                'min'  => '0'
            ),
            'value' => esc_attr( get_post_meta( $post->ID, '_no_of_tickets', true ) )
        )
    );

    // Text: Bonus Tickets
    woocommerce_wp_text_input(
        array(
            'id'            => '_bonus_tickets',
            'wrapper_class' => 'show_if_simple',
            'label'         => esc_html__( 'Bonus Tickets', 'giveaway-lottery' ),
            'type'          => 'number', 
            'value'         => esc_attr( get_post_meta( $post->ID, '_bonus_tickets', true ) )
        )
    );

    echo '<p class="show_if_simple"><small>(' . esc_html__( 'Total obtain tickets = No of tickets + Bonus tickets', 'giveaway-lottery' ) . ')</small><span></span></p>';

    // Giveaway Ticket Selling Start Date
    woocommerce_wp_text_input(
        array(
            'id'            => '_start_date',
            'wrapper_class' => '',
            'label'         => esc_html__( 'Ticket selling start date & time', 'giveaway-lottery' ),
            'type'          => 'datetime-local',
            'value'         => esc_attr( get_post_meta( $post->ID, '_start_date', true ) )
        )
    );

    // Ticket Selling Close Date & Time
    woocommerce_wp_text_input(
        array(
            'id'            => '_close_date',
            'wrapper_class' => '',
            'label'         => esc_html__( 'Ticket Selling close date & time', 'giveaway-lottery' ),
            'type'          => 'datetime-local',
            'value'         => esc_attr( get_post_meta( $post->ID, '_close_date', true ) )
        )
    );

    // Giveaway Draw Date & Time
    woocommerce_wp_text_input(
        array(
            'id'            => '_draw_date',
            'wrapper_class' => '',
            'label'         => esc_html__( 'Giveaway draw date & time', 'giveaway-lottery' ),
            'type'          => 'datetime-local',
            'value'         => esc_attr( get_post_meta( $post->ID, '_draw_date', true ) )
        )
    );

    woocommerce_wp_text_input(
        array(
            'id'            => '_ticket_range',
            'wrapper_class' => '',
            'label'         => esc_html__( 'Ticket range (' . WXGIVEAWAY_TICKET_MIN_VALUE . ' - ' . WXGIVEAWAY_TICKET_MAX_VALUE . ')', 'giveaway-lottery' ),
            'type'          => 'text',
            'value'         => esc_attr( get_post_meta( $post->ID, '_ticket_range', true ) )
        )
    );

    echo '<p><small>(' . esc_html__( 'Random ticket numbers will be from which range?', 'giveaway-lottery' ) . ')</small><span></span></p>';

    echo '</div>';

}


// variation items

// Save wx_giveaway Fields for Simple product
add_action('woocommerce_process_product_meta', 'wxgiveaway_save_giveaway_settings_data');
function wxgiveaway_save_giveaway_settings_data($post_id) {

    // Verify nonce
    if ( ! isset( $_POST['wxgiveaway_setting_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wxgiveaway_setting_meta_box_nonce'] ) ), 'wxgiveaway_setting_save_meta_box' ) ) {
        return; // Exit if nonce is invalid
    }


    // Checkbox: Enable Giveaway
    $enable_giveaway = isset($_POST['_enable_giveaway']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_giveaway', $enable_giveaway);

    // Number: No of Tickets
    if (isset($_POST['_no_of_tickets'])) {
        update_post_meta($post_id, '_no_of_tickets', sanitize_text_field(wp_unslash($_POST['_no_of_ticket)s'])));
    }

    // Text: Bonus Tickets
    if (isset($_POST['_bonus_tickets'])) {
        update_post_meta($post_id, '_bonus_tickets', sanitize_text_field(wp_unslash($_POST['_bonus_ticket)s'])));
    }
    
    // Text: Ticket range
    if (isset($_POST['_ticket_range'])) {
        update_post_meta($post_id, '_ticket_range', sanitize_text_field(wp_unslash($_POST['_ticket_range'])));
    }

    // Date: Start Date
    if (isset($_POST['_start_date'])) {
        update_post_meta($post_id, '_start_date', sanitize_text_field(wp_unslash($_POST['_start_date'])));
    }

    // Date: Close Date
    if (isset($_POST['_close_date'])) {
        update_post_meta($post_id, '_close_date', sanitize_text_field(wp_unslash($_POST['_close_date'])));
    }

    // Date: Draw Date
    if (isset($_POST['_draw_date'])) {
        update_post_meta($post_id, '_draw_date', sanitize_text_field(wp_unslash($_POST['_draw_date'])));
    }

}

// Add custom fields to the variation settings
function wxgiveaway_variation_settings_fields( $loop, $variation_data, $variation ) {
    // Number: No of Tickets
    woocommerce_wp_text_input(
        array(
            'id'            => 'no_of_tickets_' . esc_attr( $variation->ID ),
            'label'         => esc_html__( 'No of Tickets', 'woocommerce' ),
            'type'          => 'number',
            'value'         => esc_attr( get_post_meta( $variation->ID, '_no_of_tickets', true ) ),
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '0'
            )
        )
    );

    // Text: Bonus Tickets
    woocommerce_wp_text_input(
        array(
            'id'            => 'bonus_tickets_' . esc_attr( $variation->ID ),
            'label'         => esc_html__( 'Bonus Tickets', 'woocommerce' ),
            'type'          => 'number',
            'value'         => esc_attr( get_post_meta( $variation->ID, '_bonus_tickets', true ) ),
        )
    );

    woocommerce_wp_checkbox(
        array(
            'id'            => '_one_time_bonus' . esc_attr( $variation->ID ),
            'wrapper_class' => '',
            'label'         => esc_html__( 'Allow bonus only for customer\'s 1st order ', 'giveaway-lottery' ),
            'value'         => esc_attr( get_post_meta( $variation->ID, '_one_time_bonus', true ) ),
        )
    );

    echo '<p><small>(' . esc_html__( 'Total obtain tickets = No of tickets + Bonus tickets', 'giveaway-lottery' ) . ')</small><span></span></p>';

}
add_action( 'woocommerce_product_after_variable_attributes', 'wxgiveaway_variation_settings_fields', 10, 3 );

function wxgiveaway_save_variation_settings_fields( $post_id ) {
 
    // Verify nonce
    if ( ! isset( $_POST['wxgiveaway_setting_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wxgiveaway_setting_meta_box_nonce'] ) ), 'wxgiveaway_setting_save_meta_box' ) ) {
        return; // Exit if nonce is invalid
    }

    // Number: No of Tickets
    if (isset($_POST['no_of_tickets_' . $post_id])) {
        update_post_meta($post_id, '_no_of_tickets', sanitize_text_field($_POST['no_of_tickets_' . $post_id]));
    }

    // Text: Bonus Tickets
    if (isset($_POST['bonus_tickets_' . $post_id])) {
        update_post_meta($post_id, '_bonus_tickets', sanitize_text_field($_POST['bonus_tickets_' . $post_id]));
    }

}
add_action( 'woocommerce_save_product_variation', 'wxgiveaway_save_variation_settings_fields', 10, 2 );
