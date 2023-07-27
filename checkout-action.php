<?php

// Add custom field on Woo Checkout page
add_action( 'woocommerce_after_order_notes', 'woocustom_checkout_field' );

function woocustom_checkout_field( $checkout ) {
	
	echo '<div id="woocustom_checkout_field"><h3>'.__('My Field').'</h3>';

    if(get_custom_table_value(1) === "file"){
        woocustom_checkout_file_upload();
    }
				
	woocommerce_form_field( 'woo_field', array( 
		'type' 			=> get_custom_table_value(1), 
		'class' 		=> array('orm-row-wide'), 
		'label' 		=> __('Fill in this field'),
		'required'		=> true,
		'placeholder' 	=> __('Enter a value'),
        'options'     => array(
            'yes' => __('YES'),
            'no' => __('NO')
        )
		), $checkout->get_value( 'woo_field' ));

	echo '</div>';
}

// Get the value from the custom table
function get_custom_table_value($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'woo_custom';

    $sql = $wpdb->prepare("SELECT field_type FROM $table_name WHERE id = %d", $id);

    $result = $wpdb->get_var($sql);

    return $result;
}


// Validate custom field value during process the checkout
add_action('woocommerce_checkout_process', 'woocustom_checkout_field_process');

function woocustom_checkout_field_process() {
	global $woocommerce;

	if ( ! $_POST['woo_field'] && get_custom_table_value(1) !== "file" )  {
         wc_add_notice( __( 'Please fill My Field.' ), 'error' );
    }    
}

// Update the user meta with field value
add_action('woocommerce_checkout_update_user_meta', 'woocustom_checkout_field_update_user_meta');

function woocustom_checkout_field_update_user_meta( $user_id ) {
	if ($user_id && $_POST['woo_field']) {
        update_user_meta( $user_id, 'woo_field', esc_attr($_POST['woo_field']) );
    }
}

// Update the order meta with field value
add_action('woocommerce_checkout_update_order_meta', 'woocustom_checkout_field_update_order_meta');

function woocustom_checkout_field_update_order_meta( $order_id ) {
	if ($_POST['woo_field']) {
        update_post_meta( $order_id, 'Woo Field', esc_attr($_POST['woo_field']));
    }

    if ( ! empty( $_POST['appform_field'] ) ) {
        update_post_meta( $order_id, '_application', $_POST['appform_field'] );
     }
}

// Show custom value in orders table
add_filter( 'manage_edit-shop_order_columns', 'woocustom_show_custom_orders_table_column' );

function woocustom_show_custom_orders_table_column( $columns ) {
    $columns['woo_field'] = 'WooCustom Field';
    return $columns;
}

// Show row value in orders table
add_action('manage_shop_order_posts_custom_column', 'woocustom_populate_orders_custom_table_column', 99, 2);

function woocustom_populate_orders_custom_table_column( $column, $product_id ) {
    if ( $column === 'woo_field') {
        $field_value = get_post_meta($product_id, 'Woo Field', true);
        if( !$field_value ) {
            echo '<a href="' . get_post_meta( $product_id, '_application', true ) . '" target="_blank">' . get_post_meta($product_id , '_application', true ) . '</a>';
        }else {
            echo $field_value;
        }
        
    }
}

// Show custom value on order screen page
add_action( 'woocommerce_admin_order_data_after_order_details', 'display_url_address' );

function display_url_address( $order ){
    $field_value = get_post_meta( $order->get_id(), 'Woo Field', true );
    $file_value = get_post_meta( $order->get_id(), '_application', true );


    if ( $file_value ) {
        echo '<p class="form-field form-field-wide wc-customer-user" ><b>File:</b> <a href="' . get_post_meta( $order->get_id(), '_application', true ) . '" target="_blank">' . get_post_meta($order->get_id() , '_application', true ) . '</a></p>';
    } elseif ( $field_value ) {
        echo '<p class="form-field form-field-wide wc-customer-user">
        <b>Woo Custom Field Value:</b> <br>'. $field_value.'</p>';
    }
}

// File upload
function woocustom_checkout_file_upload() {
   echo '<p class="form-row"><label for="appform">Application Form (PDF)<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="file" id="appform" name="appform" accept="*" required><input type="hidden" name="appform_field" /></span></p>';
   wc_enqueue_js( "
      $( '#appform' ).change( function() {
         if ( this.files.length ) {
            const file = this.files[0];
            const formData = new FormData();
            formData.append( 'appform', file );
            $.ajax({
               url: wc_checkout_params.ajax_url + '?action=appformupload',
               type: 'POST',
               data: formData,
               contentType: false,
               enctype: 'multipart/form-data',
               processData: false,
               success: function ( response ) {
                  $( 'input[name=\"appform_field\"]' ).val( response );
               }
            });
         }
      });
   " );
}
 
add_action( 'wp_ajax_appformupload', 'woocustom_appformupload' );
add_action( 'wp_ajax_nopriv_appformupload', 'woocustom_appformupload' );
 
function woocustom_appformupload() {
   global $wpdb;
   $uploads_dir = wp_upload_dir();
   if ( isset( $_FILES['appform'] ) ) {
      if ( $upload = wp_upload_bits( $_FILES['appform']['name'], null, file_get_contents( $_FILES['appform']['tmp_name'] ) ) ) {
         echo $upload['url'];
      }
   }
   die;
}
