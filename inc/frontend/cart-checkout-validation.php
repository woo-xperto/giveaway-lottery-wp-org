<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
// giveaway add to cart validation
add_filter('woocommerce_add_to_cart_validation', 'wxgiveaway_giveaway_add_to_cart_validation', 10, 3);
function wxgiveaway_giveaway_add_to_cart_validation($passed, $product_id, $quantity) {
    if(wxgiveaway_is_giveaway($product_id)){
        if(!wxgiveaway_is_active($product_id)){
            $passed = false;
            wc_add_notice(__('Giveaway "'.get_the_title($product_id).'" is not active', 'giveaway-lottery'), 'error');
        }
    }
    
    return $passed;
}

// validate cart at checkout process
add_action( 'woocommerce_after_checkout_validation', 'wxgiveaway_validate_cart_giveaway_items_at_checkout', 10, 2 );
function wxgiveaway_validate_cart_giveaway_items_at_checkout($fields, $errors){
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = (int) $cart_item['product_id'];
        if(wxgiveaway_is_giveaway($product_id)){
            if(!wxgiveaway_is_active($product_id)){
                $errors->add( 'validation', 'Giveaway "'.get_the_title($product_id).'" is not active' );
            }
        }
        
    }
}