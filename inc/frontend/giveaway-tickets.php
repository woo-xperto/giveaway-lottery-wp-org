<?php
/**
 * Utility method to create ticket number for each line item from order 
 */
function wxgiveaway_generate_giveaway_tickets($giveaway_id, $order_id, $tickets, $order_item_id, $min, $max, $variation_id=0) {
    global $wpdb;
    // Prepare the SQL to call the procedure
    $ticket_sql = $wpdb->prepare(
        "CALL GenerateWXGiveawayTickets(%d, %d, %d, %d, %d, %d, %d)",
        $giveaway_id, $order_id, $tickets, $min, $max, $order_item_id, $variation_id
    );

    // Execute the procedure
    $wpdb->query($ticket_sql);

    wc_add_order_item_meta($order_item_id,'no_of_tickets',$tickets);
}



/**
 * Call the function wxgive_woocommerce_new_order_action when a new order is created in woocommerce
 * This function will generate giveaway tickets for each order 
 */

add_action( 'woocommerce_order_status_processing', 'wxgiveaway_giveaway_ticket_generate', 10, 3);
add_action( 'woocommerce_order_status_completed', 'wxgiveaway_giveaway_ticket_generate', 10, 3);
function wxgiveaway_giveaway_ticket_generate($order_id,$order,$status_transition){
    $settings=get_option('wxgiveaway_settings');
    
    $ticket_generate_status=$settings['ticket_generate'];
    if(!$ticket_generate_status){
        $ticket_generate_status='processing';
    }
    

    $isTicketGenerated = (int) $order->get_meta('wxgiveaway_ticket_generated');
    // ticket generate
    if($ticket_generate_status===$status_transition['to'] && $isTicketGenerated!=1){
        // Get and Loop Over Order Items
        $totalTickets=0;
        foreach ( $order->get_items() as $item_id => $item ) {
            $product_id = $item->get_product_id();

            $isSingleGiveaway = wxgiveaway_is_giveaway($product_id);

            $variation_id = $item->get_variation_id();
            $giveaway_id = $product_id;

            $post_id = ($variation_id>0?$variation_id:$product_id);

            $_no_of_tickets = (int)get_post_meta($post_id, '_no_of_tickets', true);
            $_bonus_tickets = (int)get_post_meta($post_id, '_bonus_tickets', true);
            $quantity = $item->get_quantity();
            $tickets = ($_no_of_tickets + $_bonus_tickets)*$quantity;

            $tickets = apply_filters( 'obtain_tickets', $tickets, $giveaway_id, $post_id, $order_id, $item_id,$_no_of_tickets,$_bonus_tickets);

            if($tickets>0){
                // Retrieve the ticket range from post meta
                $ticket_range = get_post_meta($giveaway_id, '_ticket_range', true);
                $range = explode('-', $ticket_range);

                if (is_array($range)) {
                    $min = isset($range[0]) ? intval(trim($range[0])) : WXGIVEAWAY_TICKET_MIN_VALUE;
                    $max = isset($range[1]) ? intval(trim($range[1])) : WXGIVEAWAY_TICKET_MAX_VALUE;
                } else {
                    $min = WXGIVEAWAY_TICKET_MIN_VALUE;
                    $max = WXGIVEAWAY_TICKET_MAX_VALUE;
                }

                wxgiveaway_generate_giveaway_tickets($giveaway_id, $order_id, $tickets, $item_id, $min,$max,$variation_id);

                $totalTickets+=$tickets;
            }

        } // end foreach item

        $order->update_meta_data('total_obtain_tickets',$totalTickets);

        if($totalTickets>0){
            $order->update_meta_data('wxgiveaway_ticket_generated',1);
        }

        $order->save();
    }

}

// delete generated tickets
add_action( 'woocommerce_order_status_changed', 'wxgiveaway_giveaway_ticket_delete', 10, 4);
function wxgiveaway_giveaway_ticket_delete($order_id,$status_from,$status_to,$order){
    $settings=get_option('wxgiveaway_settings');
   
    $ticket_ticket_delete_at=$settings['ticket_delete_at'];
    if(!$ticket_ticket_delete_at){
        $ticket_ticket_delete_at='cancelled';
    }

    $isTicketGenerated = (int) $order->get_meta('wxgiveaway_ticket_generated');

    // ticket delete
    if($ticket_ticket_delete_at===$status_to && $isTicketGenerated==1){
        global $wpdb;
        $table_name = $wpdb->prefix.'wx_giveaway';
        $wpdb->query("delete from $table_name where order_id={$order_id}");
        $order->delete_meta_data('total_obtain_tickets');
        $order->delete_meta_data('wxgiveaway_ticket_generated');
        foreach ( $order->get_items() as $item_id => $item ) {
            $item->delete_meta_data( 'no_of_tickets' );
            $item->save();
        }

        $order->save();
    }
}


// send tickets through email
add_action('woocommerce_email_order_meta','wxgiveaway_giveaway_ticket_send',99,1);
function wxgiveaway_giveaway_ticket_send($order){

    $settings=get_option('wxgiveaway_settings');    
    $ticket_send=(isset($settings['ticket_send'])?$settings['ticket_send']:'');
    $ticket_style=$settings['ticket_style'];
    $logo=$settings['logo_url'];
    if(!$ticket_style){
        $ticket_style='style1';
    }

    
    $wxgiveaway_ticket_generated=(int)$order->get_meta('wxgiveaway_ticket_generated');
    $total_obtain_tickets=(int)$order->get_meta('total_obtain_tickets');

    if($ticket_send && $wxgiveaway_ticket_generated===1 && $total_obtain_tickets>0){
        global $wpdb;
        $sql="SELECT a.*, b.post_title, c.meta_value FROM {$wpdb->prefix}wx_giveaway a, {$wpdb->prefix}posts b, {$wpdb->prefix}postmeta c 
        where a.giveaways_id=b.ID and a.giveaways_id=c.post_id and c.meta_key='_draw_date' and a.order_id={$order->get_id()} and a.order_id";
        $results=$wpdb->get_results($sql);
        if($results){
            $ticketsHtml=array();
            foreach($results as $row){
                $ticketsHtml[]=wxgiveaway_get_ticket_for_selected_style($row,$ticket_style,$order,$logo);
            } // end foreach loop

            if(count($ticketsHtml)>0){
                if($ticket_style==='style1'){
                    wxgiveaway_print_ticket_style1($ticketsHtml);
                }
                if($ticket_style==='style2'){
                    wxgiveaway_print_ticket_style2($ticketsHtml);
                }
            }


        }

    }


}


function wxgiveaway_print_ticket_style2($ticketsHtml) {
    echo '<h2>' . esc_html__('Tickets:', 'giveaway-lottery') . '</h2><div style="margin-bottom:40px;"><table style="border-collapse: separate;
    border-spacing: 0 10px;width: 100%; margin:auto; background: #ffffff;">';
    
    foreach ($ticketsHtml as $ticket) {
        echo wp_kses_post($ticket);  // Use wp_kses_post to allow safe HTML content
    }
    
    echo '</table></div>';
}

function wxgiveaway_print_ticket_style1($ticketsHtml) {
    echo '<h2>' . esc_html__('Tickets:', 'giveaway-lottery') . '</h2><div style="margin-bottom:40px;">
    <table style="border-collapse: separate;border-spacing: 10px;width: 100%; margin:auto; background: #ffffff;">';
    
    for ($i = 0; $i < count($ticketsHtml); $i += 2) {
        echo '<tr>';  // Start a new row
        
        // Print the current td
        echo wp_kses_post($ticketsHtml[$i]); 
        
        // Check if there is a next td, if not add a blank td
        if (isset($ticketsHtml[$i + 1])) {
            echo wp_kses_post($ticketsHtml[$i + 1]);
        } else {
            echo '<td style=" margin: 0 auto; text-align: center; width: 50%;"></td>';
        }
        echo '</tr>';  // Close the row
    }
    echo '</table></div>';
}



// generate ticket html markup
function wxgiveaway_get_ticket_for_selected_style($row,$ticket_style,$order,$logo){
    $ticket='';
    switch ($ticket_style) {
        case 'style1':
            $ticket = wxgiveaway_get_ticket_style1($row,$logo);
            break;
        case 'style2':
            $ticket = wxgiveaway_get_ticket_style2($row,$order,$logo);
            break;
    }

    return $ticket;
}

// ticket style 1 markup
function wxgiveaway_get_ticket_style1($row,$logo){
    $date=$row->meta_value;
    $dateTime = new DateTime($date);
    // Format the date-time to AM/PM format
    $formattedDate = $dateTime->format('d/m/Y h:i A');
    $ticket='<td style=" margin: 0 auto; text-align: center; border: 1px solid #ddd; width: 50%; border-radius: 10px;" >
                <table>        
                    <tr>
                        <td style=" margin: 0 auto; text-align: center;">';
                            if($logo){
                                $ticket.='<img style="max-width:50%; display: block; margin: 0px auto;" src="'.$logo.'" alt="'.__('site logo','giveaway-lottery').'">';
                            }
            $ticket.='</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 10px;text-align: center;">
                            <h3 style="text-align: center;font-size: 14px; color: #636363;margin: 0;font-weight: 600;" >'.__('Giveaway','giveaway-lottery').': '.esc_html($row->post_title).'</h3>
                        
                        </td>
                                                    
                    </tr>
                                
                    <tr>	
                        <td style="padding: 5px 10px; text-align: center;">
                            <h3 style="font-size: 14px; color: #636363;margin: 0;font-weight: 600;text-align: center;" >'.__('Ticket No.','giveaway-lottery').': '.esc_html($row->ticket_no).'</h3>
                        </td>
                    </tr>
        
                    <tr>
                        <td style="padding: 5px 10px;text-align: center;"> 
                        <h3 style="font-size: 14px; color: #636363;margin: 0;font-weight: 600;text-align: center;" >'.__('Draw','giveaway-lottery').': '.esc_html($formattedDate).'</h3>
                    </td>
                    </tr>
                
                </table>
            </td>';
    return $ticket;
}

// ticket style 2 markup
function wxgiveaway_get_ticket_style2($row,$order,$logo){
    $name=$order->get_billing_first_name().' '.$order->get_billing_last_name();
    $date=$row->meta_value;
    $dateTime = new DateTime($date);
    // Format the date-time to AM/PM format
    $formattedDate = $dateTime->format('d/m/Y h:i A');

    $ticket='<tr>
                <td  style="vertical-align:bottom;margin: 10px auto; text-align: left; width: 55%; border-width: 1px 0px 1px 1px; border-color:#ddd; border-style: solid; border-radius: 5px 0px 0px 5px;" >
                    <table>        
                        <tr>
                            <td style="padding:0px;">
                            <h3 style="font-size: 14px; color: #636363;margin: 0;font-weight: 600;" >'.__('Name','giveaway-lottery').': '.esc_html($name).'</h3>                          
                            
                            </td>
                                                        
                        </tr>       
                        
                        <tr>
                            <td style="padding:0px;">                           

                            <h3 style="font-size: 14px; color: #636363;margin: 0;font-weight: 600;" >'.__('Phone','giveaway-lottery').': '.esc_html($order->get_billing_phone()).'</h3>                          
                            
                            </td>
                                                        
                        </tr>

                        <tr>
                            <td style="padding:0px;">
                            

                            <h3 style="font-size: 14px; color: #636363;margin: 0;font-weight: 600;" >'.__('Draw','giveaway-lottery').': '.esc_html($formattedDate).'</h3> 
                            
                            </td>
                                                        
                        </tr>
                        
                </table>
                </td>

                <td  style="vertical-align:bottom;margin: 10px auto; width: 45%; border-width: 1px 1px 1px 0px; border-color:#ddd; border-style: solid; border-radius: 0px 5px 5px 0px;" >
                    <table>        
                        <tr>
                            <td style=" margin: 0 auto; text-align: center;">';
                            if($logo){
                                $ticket.='<img style="max-width:40%; display: block; margin: 0px auto;" src="'.$logo.'" alt="'.__('site logo','giveaway-lottery').'">';
                            }
                $ticket.='</td>            
                           
                        
                        </tr>
                        <tr>
                            <td style="padding:0px;text-align:center;">
                            <h3 style="font-size: 14px; color: #636363; margin: 0;font-weight: 600;text-align:center;" >'.__('Giveaway','giveaway-lottery').': '.esc_html($row->post_title).'</h3>
                            
                            </td>
                                                        
                        </tr>
                                    
                        <tr>	
                            <td style="padding:0px;text-align:center;">
                                <h3 style="font-size: 14px; color: #636363;margin: 0;font-weight: 600;text-align:center;" >'.__('Ticket No.','giveaway-lottery').': '.esc_html($row->ticket_no).'</h3>
                                </td>
                        </tr>           
                        
                    
                </table>
                </td>            
            </tr>';
    return $ticket;
}

add_filter( 'woocommerce_order_item_display_meta_key', function($display_key, $meta, $item){
    if($display_key=='no_of_tickets'){
        $display_key='Tickets';
    }
    return $display_key;
},10,3);

// Show tickets in thank you page
add_action('woocommerce_thankyou','wxgiveaway_show_ticket_numbers',10,1);

add_action('woocommerce_view_order','wxgiveaway_show_ticket_numbers',10,1);

function wxgiveaway_show_ticket_numbers($order_id){
    global $wpdb;
    $sql="SELECT a.ticket_no FROM {$wpdb->prefix}wx_giveaway a where a.order_id={$order_id}";
    $results=$wpdb->get_results($sql);
    if($results){
        $tickets=array();
        foreach($results as $row){
            $tickets[]=$row->ticket_no;
        }

        // if(count($tickets)>0){
        //     echo '<h4>'.__('Tickets:','giveaway-lottery').'</h4>';
        //     $wrappedArray = array_map(function($item) {
        //         return "<span class='ticket_no'>$item</span>";
        //     }, $tickets);
        //     echo '<div class="thank_you_ticket_wrap">'.implode("", $wrappedArray).'</div>';
        // }
        if (count($tickets) > 0) {
            echo '<h4>' . esc_html__('Tickets:', 'giveaway-lottery') . '</h4>';
            $wrappedArray = array_map(function($item) {
                return "<span class='ticket_no'>" . esc_html($item) . "</span>";
            }, $tickets);
            echo '<div class="thank_you_ticket_wrap">' . wp_kses_post(implode("", $wrappedArray)) . '</div>';
        }
        
    }
}

// add tickets column at my account orders page
function wxgiveaway_add_order_column( $columns ) {
    // Define the new column with its title
    $new_column = array(
        'order-tickets' => esc_html__( 'Tickets', 'woocommerce' )
    );

    // Reorder the columns by inserting the new column after 'order-total'
    $columns = array_slice( $columns, 0, array_search( 'order-actions', array_keys( $columns ) ), true ) +
               $new_column +
               array_slice( $columns, array_search( 'order-actions', array_keys( $columns ) ), NULL, true );

    return $columns;
}
add_filter( 'woocommerce_my_account_my_orders_columns', 'wxgiveaway_add_order_column',10,1 );

// Populate the new column with data
function wxgiveaway_my_orders_tickets_column_content( $order ) {
    // Get the order ID
    $total_obtain_tickets = $order->get_meta('total_obtain_tickets');
    echo esc_html( $total_obtain_tickets );
}
add_action( 'woocommerce_my_account_my_orders_column_order-tickets', 'wxgiveaway_my_orders_tickets_column_content' );