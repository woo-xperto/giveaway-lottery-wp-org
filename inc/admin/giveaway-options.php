<?php
/*Add ticket custom column Section at products data grid*/
if( !function_exists('wxgiveaway_add_tickets_columns')){
    function wxgiveaway_add_tickets_columns($columns){

        $columns["wxgiveaway_tickets"] = "Tickets";

        return $columns;
    }
}
add_filter("manage_product_posts_columns","wxgiveaway_add_tickets_columns",100,1);

/*Display content in the Tickets columns */

if( !function_exists("wxgiveaway_ticket_column_content")){
    function wxgiveaway_ticket_column_content($column, $post_id ){

        if("wxgiveaway_tickets" == $column ){
            $is_giveaway=wxgiveaway_is_giveaway($post_id);
           if($is_giveaway){
                
                $ticket_range=get_post_meta($post_id,'ticket_range',true);
                echo esc_html( $ticket_range );
                $allocated_entries=wxgiveaway_get_total_number_of_tickets($post_id);

                global $wpdb;
                $table_name = $wpdb->prefix.'wx_giveaway'; 
                $query = $wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE giveaways_id = $post_id");    
                $row_counts = $wpdb->get_var($query);
                
                echo esc_html__( 'Allocated:', 'giveaway-lottery' ) . ' <b>' . esc_html( number_format( $allocated_entries ) ) . '</b>,<br/>' .
                esc_html__( 'Sold:', 'giveaway-lottery' ) . ' <b>' . esc_html( number_format( $row_counts ) ) . '</b>,<br/>' .
                esc_html__( 'Left:', 'giveaway-lottery' ) . ' <b>' . esc_html( number_format( $allocated_entries - $row_counts ) ) . '</b>';

                global $wp;
                $current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
                $ext = "&giveaway_id={$post_id}&export_tickets=true&v=".time();
                
                $current_url = str_replace( $ext, '', $current_url );
                $current_url .= $ext;

                echo "<br/><a class='' href='" . esc_url( $current_url ) . "'><b>" . esc_html__( 'Export Tickets', 'giveaway-lottery' ) . "</b></a>";


                do_action('wxgiveaway_product_column_option',$post_id);
           }
        }


    }
    add_action("manage_product_posts_custom_column","wxgiveaway_ticket_column_content",10,2);
}


// export giveaway tickets
add_action('init',function(){
    if(is_admin()){
        if(isset($_GET['export_tickets'])){
            ini_set('max_execution_time', 600); 
            $giveaway_id=sanitize_text_field($_GET['giveaway_id']);
            global $wpdb;

            $table_name = $wpdb->prefix.'wx_giveaway';

            $sql="SELECT a.order_id,a.ticket_no
            FROM {$table_name} a 
            WHERE a.giveaways_id = {$giveaway_id} order by a.order_id";
            $rows = $wpdb->get_results($sql);

            if($rows){
                $filename = "export_tickets_".time()."-".$giveaway_id.".csv";
                $csv_file = fopen('php://output', 'w');
                header('Content-type: application/csv');
                header('Content-Disposition: attachment; filename="'.$filename.'"');
                $header_row = array("Order ID","Ticket no.");
                fputcsv($csv_file,$header_row,',','"');


                foreach($rows as $row){
                    $line = array(
                        $row->order_id,
                        $row->ticket_no
                    );
                    
                    fputcsv($csv_file,$line,',','"');
                }
                
                fclose($csv_file);
                exit();
            }
            
            
        }
    }
});

// end ticket export option

// Add a Tickets column to the orders list
//add_filter('manage_edit-shop_order_columns', 'wxgiveaway_add_tickets_order_column');
add_filter('manage_woocommerce_page_wc-orders_columns', 'wxgiveaway_add_tickets_order_column');

function wxgiveaway_add_tickets_order_column($columns) {
    $columns['order_tickets'] = __('Tickets', 'giveaway-lottery');
    return $columns;
}

// Populate the ticket column with data
add_action('manage_woocommerce_page_wc-orders_custom_column', 'wxgiveaway_tikets_order_column_content', 10, 2);

function wxgiveaway_tikets_order_column_content($column, $post_id) {
    if ($column == 'order_tickets') {
        // Retrieve and display your custom data here
        $order = wc_get_order($post_id);
        
        echo esc_html($order->get_meta('total_obtain_tickets'));
    }
}
