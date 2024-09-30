<?php
// Ticket Checker Short code from
add_shortcode('ticket-check','wxgiveaway_ticket_checker_short_code_fun');
function wxgiveaway_ticket_checker_short_code_fun($jekono){ 
    $result = shortcode_atts(array( 
    'title' =>'',
    ),$jekono);
    extract($result);
    ob_start();
    ?>
    <!-- Start html code here  -->
    <form>
        <label for="product_id">Giveaway No:</label><br>
        <input type="text" id="product_id" name="product_id"><br>
        <label for="ticket">Ticket No:</label><br>
        <input type="text" id="ticket" name="ticket">
        <button type="button">Check</button>
    </form>
    <!-- End html code here  -->
    <?php
    return ob_get_clean();
}

// giveaway get allocated max number of orders
function wxgiveaway_get_allocated_number_of_orders($giveaway_id){
    $max_number_of_order = (int) get_post_meta($giveaway_id,'_allow_max_number_of_order',true);
    return $max_number_of_order;
}

// giveaway get total allocated tickets
function wxgiveaway_get_total_number_of_tickets($giveaway_id){
    $ticket_range = get_post_meta($giveaway_id, '_ticket_range', true);
    $range = explode('-', $ticket_range);

    if (is_array($range)) {
        $min = isset($range[0]) ? intval(trim($range[0])) : WXGIVEAWAY_TICKET_MIN_VALUE;
        $max = isset($range[1]) ? intval(trim($range[1])) : WXGIVEAWAY_TICKET_MAX_VALUE;
    } else {
        $min = WXGIVEAWAY_TICKET_MIN_VALUE;
        $max = WXGIVEAWAY_TICKET_MAX_VALUE;
    }

    $totalTickets = $max - $min + 1;

    return (int) $totalTickets;
}

// get giveaway start date & time
function wxgiveaway_start_date_time($giveaway_id){    
    $start_date = get_post_meta($giveaway_id, '_start_date', true);
    $dateTime = new DateTime($start_date);
    return $dateTime->format('Y-m-d H:i:s');
}
// get giveaway close date & time
function wxgiveaway_close_date_time($giveaway_id){    
    $close_date = get_post_meta($giveaway_id, '_close_date', true);
    $dateTime = new DateTime($close_date);
    return $dateTime->format('Y-m-d H:i:s');
}

// is the product giveaway or not
function wxgiveaway_is_giveaway($giveaway_id){
    $_enable_giveaway = get_post_meta($giveaway_id,'_enable_giveaway',true);
    if($_enable_giveaway==='yes'){
        return true;
    }

    return false;
}

// add giveaway state class in body tag
function wxgiveaway_giveaways_body_class($classes) {
    if(is_singular('product')){
        global $post;
        $post_id=$post->ID;
        if(wxgiveaway_is_giveaway($post_id)){
            $c_date = wxgiveaway_close_date_time($post_id);
            $s_date = wxgiveaway_start_date_time($post_id);
            if($s_date && $c_date){
                if(strtotime(current_time('Y-m-d H:i:s'))>strtotime($c_date)){
                    $classes[] = 'giveaway_closed';
                }else{                    
                    if(strtotime(current_time('Y-m-d H:i:s'))>=strtotime($s_date) && strtotime(current_time('Y-m-d H:i:s'))<strtotime($c_date)){
                        $classes[] = 'giveaway_active';
                    }else{
                        $classes[] = 'giveaway_inactive';
                    }
                }
            }
        }
    }

    
    return $classes;
}

add_filter('body_class', 'wxgiveaway_giveaways_body_class',10,1);

// is giveaway active
function wxgiveaway_is_active($giveaway_id){
    if(wxgiveaway_is_giveaway($giveaway_id)){
        $c_date = wxgiveaway_close_date_time($giveaway_id);
        $s_date = wxgiveaway_start_date_time($giveaway_id);
        if($s_date && $c_date){
            if(strtotime(current_time('Y-m-d H:i:s'))>strtotime($c_date)){
                return false;
            }else{                    
                if(strtotime(current_time('Y-m-d H:i:s'))>=strtotime($s_date) && strtotime(current_time('Y-m-d H:i:s'))<strtotime($c_date)){
                    return true;
                }else{
                    return false;
                }
            }
        }
    }

    return false;
}

// cart & checkout validation
require_once('cart-checkout-validation.php');

// override woocommerce templates
function wxgiveaway_override_template($template, $template_name, $template_path) {
    $plugin_path = plugin_dir_path(__FILE__) . 'woocommerce/';

    // Check if the template exists in the custom theme directory
    if (file_exists($plugin_path . $template_name)) {
        $template = $plugin_path . $template_name;
    }

    return $template;
}
add_filter('woocommerce_locate_template', 'wxgiveaway_override_template', 10, 3);

