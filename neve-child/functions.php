<?php
    
function ds_debug_filters( $custom_tags = array() ) {
    // $wp_filter Stores all of the filters
    global $wp_filter;

    if ( empty( $wp_filter ) )
        return false;

    // Check if custom tags are defined
    if ( ! empty( $custom_tags ) ) {
        // Check if custom tags are available
        $tags = array_intersect( array_keys( $wp_filter), (array) $custom_tags );

        if ( empty( $tags ) )
            return false;

        // Fill custom tags
        foreach ( $tags as $tag )
            $_wp_filter[$tag] = $wp_filter[$tag];
    } else {
        // Use default tags
        $_wp_filter = $wp_filter;
    }

    echo '<pre id="wp-debug-filters">';

    // Uncomment, if you want to sort by name of the filter hooks
    // ksort( $_wp_filter );

    foreach ( $_wp_filter as $tag => $data ) {
        // Print tag name
        printf(
            '<br /><strong>%s</strong><br />',
            esc_html( $tag )
        );

        // Sort by priority
        ksort( $data );

        foreach ( $data as $priority => $functions ) {
            // Print priority once
            printf(
                '%s',
                $priority
            );

            // Go through each function
            foreach ( $functions as $function ) {
                $_function = $function['function'];
                $_args = $function['accepted_args'];

                // Check function type
                if ( is_array( $_function ) ) {
                    // Object class calling
                    if ( is_object( $_function[0] ) )
                        $class = get_class( $_function[0] );
                    else
                        $class = $_function[0];

                    $name = $class . '::' . $_function[1];
                } else {
                    // Static calling
                    $name = $_function;
                }

                // Print function name and number of accepted arguments
                printf(
                    "\t%s() (%s)<br />",
                    esc_html( $name ),
                    sprintf(
                        _n(
                            '1 accepted argument',
                            '%s accepted arguments',
                            $_args
                        ),
                        $_args
                    )
                );
            }
        }
    }

    echo '</pre>';
}

//add_action( 'shutdown', 'ds_debug_filters', ['woocommerce_valid_order_statuses_for_payment']);


add_action('wf_refresh_after_product_import','refresh_after_product_import',1);
function refresh_after_product_import($product_object) { $product_object->save(); }

function my_get_wp_user_id($theOrder) {
    if (empty($theOrder)) {
        global $order;
        $theOrder = $order;
    }

    if (!empty($theOrder)) {
        if (method_exists($theOrder, 'get_customer_id')) {
            // WC 3.0+
            return $theOrder->get_customer_id();
        } else {
            // WC 2.5+
            if (!is_a($theOrder, 'WC_Order')) {
                global $post;
                $order_id = $post->ID;
            } else {
                $order_id = $theOrder->id;
            }
            if (!empty($order_id)) {
                return get_post_meta($order_id, '_customer_user', true);
            }
        }
    }
    return null;
}

/* Add user information to orders list*/
add_action( 'manage_shop_order_posts_custom_column' , 'custom_orders_list_column_content', 50, 2 );
function custom_orders_list_column_content( $column, $post_id ) {
    if ( $column == 'order_number' )
    {
        global $the_order;

        if( $phone = $the_order->get_billing_phone() ){
            $phone_wp_dashicon = '<span class="dashicons dashicons-phone"></span> ';
            echo '<br><a href="https://wa.me/57'.substr($phone,3).'" target="_blank">' . $phone_wp_dashicon . $phone.'</a></strong>';
        }
    
    if( $phone2 = $the_order->get_billing_phone() ){
            $phone_wp_dashicon = '<span class="dashicons dashicons-admin-site-alt"></span> ';
            echo '<br><a href="skype:'.$phone.'?call" target="_blank">'.$phone_wp_dashicon.'Skype ' . $phone.'</a></strong>';
        }

        if( $email = $the_order->get_billing_email() ){
            echo '<br><strong><a href="mailto:'.$email.'">' . $email . '</a></strong><br>';
            $order = new WC_Order($post_id);
            $wp_user_id = my_get_wp_user_id($order);
            echo '<a href="https://vivecul.com.co/wp-admin/edit.php?post_status=all&post_type=shop_order&_customer_user='.$wp_user_id.'">ID de Usuario: ' .$wp_user_id. '</a> <a href="https://vivecul.com.co/wp-admin/user-edit.php?user_id='.$wp_user_id.'">-</a><br>';
            $allSubscriptions = WC_Subscriptions_Manager::get_users_subscriptions($wp_user_id);
            $active_sub_quantity = 0;
            //$active_amount = 0;
            $onhold_sub_quantity = 0;
            foreach ($allSubscriptions as $subscription){
                if ($subscription['status'] == 'active') {
                    $active_sub_quantity += 1;
                    //$active_amount += $subscription['total'];
                }
                if ($subscription['status'] == 'on-hold' | $subscription['status'] == 'late-payment-60' | $subscription['status'] == 'late-payment-90' | $subscription['status'] == 'late-payment-120' | $subscription['status'] == 'late-payment-150' | $subscription['status'] == 'late-payment-1801' | $subscription['status'] == 'bad-payment') {
                    $onhold_sub_quantity += 1;
                }
            }

            if ($active_sub_quantity > 1 && $onhold_sub_quantity > 0){
                echo '<a style ="background-color: #f54b42; color: #ffffff; padding: 3px;border-radius: 2px;" href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-active&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Activos: ' . $active_sub_quantity . '<br></a>';
                echo '<a style ="background-color: #f54b42; color: #ffffff; padding: 3px;border-radius: 2px;" href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-on-hold&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Pago Demorado: ' . $onhold_sub_quantity . '<br></a>';
            }

            else if ($active_sub_quantity <= 1 && $onhold_sub_quantity > 0){
                echo '<a href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-active&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Activos: ' . $active_sub_quantity . '<br></a>';
                echo '<a style ="background-color: #f54b42; color: #ffffff; padding: 3px;border-radius: 2px;" href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-on-hold&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Pago Demorado: ' . $onhold_sub_quantity . '<br></a>';
            }
            else if ($active_sub_quantity > 1 && $onhold_sub_quantity <= 0){
                echo '<a style ="background-color: #f54b42; color: #ffffff; padding: 3px;border-radius: 2px;" href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-active&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Activos: ' . $active_sub_quantity . '<br></a>';
                echo '<a href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-on-hold&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Pago Demorado: ' . $onhold_sub_quantity . '<br></a>';
            }
            else {
                echo '<a href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-active&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Activos: ' . $active_sub_quantity . '<br></a>';
                echo '<a href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-on-hold&post_type=shop_subscription&_customer_user='.$wp_user_id.'"> Alquileres Pago Demorado: ' . $onhold_sub_quantity . '<br></a>';
            }
            
            // Get COMPLETED orders for customer
            $args = array(
                'customer_id' => $wp_user_id,
                'post_status' => 'completed',
                'post_type' => 'shop_order',
                'return' => 'ids',
            );
            $numorders_completed = 0;
            $numorders_completed = count( wc_get_orders( $args ) ); // count the array of orders

            echo '<a href="https://vivecul.com.co/wp-admin/edit.php?post_status=wc-completed&post_type=shop_order&_customer_user='.$wp_user_id.'"> Pagos Exitosos: ' . $numorders_completed . '<br></a>';
            

            if (!empty($wp_user_id)) {
                // Only use the shortcode if we have a userid - otherwise the verified indicator shows your own status.
                echo do_shortcode('[tot-wp-embed tot-widget="verifiedIndicator" show-admin-buttons="true" tot-show-when-not-verified="true" wp-userid="' . $wp_user_id . '"][/tot-wp-embed]');
            }            
        }
    
    }
}

/**
 * @snippet       Save "Terms and Conditions" Acceptance Upon Checkout - WooCommerce
 * @how-to        Get CustomizeWoo.com FREE
 * @source        https://businessbloomer.com/?p=74053
 * @author        Rodolfo Melogli
 * @compatible    Woo 3.3.4
 */
 
// 1. Save T&C as Order Meta
 
add_action( 'woocommerce_checkout_update_order_meta', 'bbloomer_save_terms_conditions_acceptance' );
 
function bbloomer_save_terms_conditions_acceptance( $order_id ) {
    if ( $_POST['terms'] ) update_post_meta( $order_id, 'terms', esc_attr( $_POST['terms'] ) );
}
 
// 2. Display T&C @ Single Order Page 
 
add_action( 'woocommerce_admin_order_data_after_billing_address', 'bbloomer_display_terms_conditions_acceptance' );
  
function bbloomer_display_terms_conditions_acceptance( $order ) {
   if ( get_post_meta( $order->get_id(), 'terms', true ) == 'on' ) {
       echo '<p><strong>Terms & Conditions: </strong>accepted</p>';
   } else echo '<p><strong>Terms & Conditions: </strong>N/A</p>';
}


/**
 * Plugin Name: Remove Subscription Action Buttons from My Account
 * Plugin URI: https://gist.github.com/thenbrent/8851287/
 * Description: Remove any given button from the <a href="http://docs.woothemes.com/document/subscriptions/customers-view/#section-2">My Subscriptions</a> table on the My Account page. By default, only the "Change Payment Method" button is removed, but you can uncomment additional actions to remove those buttons also.
 * Author: Brent Shepherd
 * Author URI:
 * Version: 2.0
 */

/**
 * Remove the "Change Payment Method" button from the My Subscriptions table.
 *
 * This isn't actually necessary because @see eg_subscription_payment_method_cannot_be_changed()
 * will prevent the button being displayed, however, it is included here as an example of how to
 * remove just the button but allow the change payment method process.
 */
function eg_remove_my_subscriptions_button( $actions, $subscription ) {

  foreach ( $actions as $action_key => $action ) {
    switch ( $action_key ) {
      case 'change_payment_method': // Hide "Change Payment Method" button?
      case 'change_address':    // Hide "Change Address" button?
      case 'switch':      // Hide "Switch Subscription" button?
//      case 'resubscribe':   // Hide "Resubscribe" button from an expired or cancelled subscription?
//      case 'pay':     // Hide "Pay" button on subscriptions that are "on-hold" as they require payment?
      case 'reactivate':    // Hide "Reactive" button on subscriptions that are "on-hold"?
      case 'cancel':      // Hide "Cancel" button on subscriptions that are "active" or "on-hold"?
        unset( $actions[ $action_key ] );
        break;
      default: 
        error_log( '-- $action = ' . print_r( $action, true ) );
        break;
    }
  }

  return $actions;
}
add_filter( 'wcs_view_subscription_actions', 'eg_remove_my_subscriptions_button', 100, 2 );



/**
 * Adds a new column to the "My Orders" table in the account.
 *
 * @param string[] $columns the columns in the orders table
 * @return string[] updated columns
 */
function sv_wc_add_my_account_orders_column( $columns ) {

  $new_columns = array();

  foreach ( $columns as $key => $name ) {

    $new_columns[ $key ] = $name;

    // add ship-to after order status column
    if ( 'order-status' === $key ) {
      $new_columns['order-items'] = __( 'Productos', 'textdomain' );
    }
  }

  return $new_columns;
}
add_filter( 'woocommerce_my_account_my_orders_columns', 'sv_wc_add_my_account_orders_column' );


/**
 * Adds data to the custom "items" column in "My Account > Orders".
 *
 * @param \WC_Order $order the order object for the row
 */
function sv_wc_my_orders_items_column( $order ) {

  foreach ( $order->get_items() as $item_id => $item ) {
     $formatted_items = $formatted_items."<br>- ".$item->get_name();    
  }
  
  echo ! empty( $formatted_items ) ? $formatted_items : '&ndash;';
}

add_action( 'woocommerce_my_account_my_orders_column_order-items', 'sv_wc_my_orders_items_column' );



//Remove cancelled orders from my account

add_filter( 'woocommerce_my_account_my_orders_query', 'custom_my_orders' );
function custom_my_orders($args ) {
    $args['status'] = array(
        'wc-pending',
        'wc-processing',
        'wc-on-hold',
        'wc-completed',
//      'wc-cancelled',
        'wc-refunded',
        'wc-failed',
        'wc-no-cumplido',
        'wc-no-pago',
        'wc-no-contactable',
        'wc-late-payment',
        'wc-late-payment-30',
        'wc-late-payment-60',
        'wc-late-payment-90',
        'wc-late-payment-120',
        'wc-late-payment-150',
        'wc-late-payment-180',
        'wc-bad-payment',
    );

    return $args;
}

/* 
* Check for pending payments and block screen 
*/

add_action('wp_footer', 'check_pending_orders');
 
function check_pending_orders( $posted ) {
    global $wp, $woocommerce, $current_user;

    if(strpos($wp->request, 'admin') !== false) return;
    if(strpos($wp->request, 'pagos') !== false) return;
    if(strpos($wp->request, 'checkout') !== false) return;
    if(strpos($wp->request, 'view-order') !== false) return;
    if(strpos($wp->request, 'view-subscription') !== false) return;
    if(strpos($wp->request, 'alquileres') !== false) return;

    if ( is_user_logged_in() ) { 
        $user = $current_user;

        if ( ! empty( $user ) && $current_user->user_email != "juan+a@vivecul.com") {
            $customer_orders = get_posts( array(
                    'numberposts' => -1,
                    'meta_key'    => '_customer_user',
                    'meta_value'  => $user->ID,
                    'post_type'   => 'shop_order', // WC orders post type
                    'post_status' => 'wc-pending' // Only orders with status "completed"
            ));

            foreach ( $customer_orders as $customer_order ) {
                $wc_order = wc_get_order($customer_order);
                if($wc_order->created_via === 'subscription' && $wc_order->status === 'pending') $count++;
                if($wc_order->created_via === 'subscription' && $wc_order->status === 'failed') $count++;
            }

            if ( $count > 0 ) {
                        $link = wc_get_account_endpoint_url( 'orders' );
                    echo '<div class="screen-block"></div>';
                $pago = 'pago';
                $pendiente = 'pendiente';
                if($count > 1) {
                    $pago .= 's';
                    $pendiente .= 's';
                }
                    echo "<div class=\"payment-reminder\"><div class=\"text\">Tienes $count $pago $pendiente</div><div class=\"action\"><a href=\"$link\">Pagar ahora</a></div></div>";
            }
        }
    }
}


/* Remove Cancel Order button for users*/
add_filter('woocommerce_my_account_my_orders_actions', 'remove_my_cancel_button', 10, 2);
function remove_my_cancel_button( $actions, $order ){
    
        unset($actions['cancel']);
    
    return $actions;
}


/* Add Automatewoo guest email capture */
add_filter( 'automatewoo/guest_capture_fields', 'my_guest_capture_fields' );

/**
 * @param array $selectors
 */
function my_guest_capture_fields( $selectors ) {
    $selectors[] = '#hustle-field-email-module-1';
    $selectors[] = '.hustle-input ';
    return $selectors;
}




/**
 * Display Invoice Link on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'alegra_invoice_field_display_admin_order_meta', 10, 1 );

function alegra_invoice_field_display_admin_order_meta($order){
    echo '<p><strong>Invoice:</strong> <br/> <a href="https://app.alegra.com/invoice/view/id/' . get_post_meta( $order->get_id(), 'alegra_invoice', true ) . '" target="_blank">' . get_post_meta( $order->get_id(), 'alegra_invoice', true ) . '</a></p>';
}


/**
*Trigger identity verification pop up after order only for users who have not filled out the user risk form
*/

add_action( 'woocommerce_thankyou', 'trigger_identity_verification_popup');

function trigger_identity_verification_popup( $order_id ){
    //$order = wc_get_order( $order_id );
    $user_id = wp_get_current_user()->id;
    $dob = get_user_meta( $user_id, 'uform_datebirth', true );
    if ( !$dob ) {
        echo '<a href="#" id="show-id-verification-popup"> </a>';
        echo '<script> window.onload=function(){
                        setTimeout(function(){ document.getElementById("show-id-verification-popup").click(); }, 500)
                        };
              </script>';
    }
}

/**
* This function is used for remove email field from the checkout when user is logged in
* 
* @name custom_checkout_fields
* @param array $address_fields  array of the address fields
*/
function custom_checkout_fields( $address_fields ) {
    $user = wp_get_current_user();
    if ( is_user_logged_in() ) {
        $address_fields['billing']['billing_email']['autocomplete'] = $user->data->user_email;
        unset( $address_fields['billing']['billing_email'] );
        return $address_fields;
    }
    else {
        return $address_fields;
    }
}
add_filter( 'woocommerce_checkout_fields', 'custom_checkout_fields' ,20, 1 );

register_meta('user', 'is_verified', array(
  "type" => "boolean",
  "show_in_rest" => true,
  "single" => true
));

function wc_order_rest( $response, $object, $request ) {
	$order = wc_get_order( $response->data['id'] );
	$response->data['type'] = $order->get_type();
	if($response->data['type'] === 'shop_subscription') {
		$subscription = wcs_get_subscription( $response->data['id'] );
		$response->data['billing_period']   = $subscription->get_billing_period();
		$response->data['billing_interval'] = $subscription->get_billing_interval();

		$resubscribed_subscriptions                  = array_filter( $subscription->get_related_orders( 'ids', 'resubscribe' ), 'wcs_is_subscription' );
		$response->data['resubscribed_from']         = strval( wcs_get_objects_property( $subscription, 'subscription_resubscribe' ) );
		$response->data['resubscribed_subscription'] = strval( reset( $resubscribed_subscriptions ) ); // Subscriptions can only be resubscribed to once so return the first and only element.

		foreach ( array( 'start', 'trial_end', 'next_payment', 'end' ) as $date_type ) {
			$date = $subscription->get_date( $date_type );
			$response->data[ $date_type . '_date' ] = ( ! empty( $date ) ) ? wc_rest_prepare_date_response( $date ) : '';
		}
		$response->data['date_completed_gmt'] = wc_rest_prepare_date_response( $subscription->get_date_completed() );
		$response->data['date_paid_gmt']      = wc_rest_prepare_date_response( $subscription->get_date_paid() );

	}

        foreach ( array( 'parent', 'renewal', 'switch' ) as $order_type ) {
                if ( wcs_order_contains_subscription( $response->data['id'], $order_type ) ) {
                        $response->data['order_type'] = $order_type . '_order';
                        break;
                }
        }

	return $response;
};

$order_types = ['shop_order', 'shop_subsription'];
foreach($order_types as $ot) {
	add_filter( 'woocommerce_rest_prepare_' . $ot . '_object', 'wc_order_rest', 10, 3 ); 
}



function createOrderWithCulcheck($workflow) {
    $customer = $workflow->data_layer()->get_user();
    $order = $workflow->data_layer()->get_order();
    $subscription = $workflow->data_layer()->get_subscription();

    $workflow->log_action_note( $workflow , "customer: $customer->ID, order: $order->ID, subscription: $subscription->ID");

if($order->ID) culSync('syncOrder' , $order->ID, $workflow);
    if($subscription->ID) culSync('syncOrder', $subscription->ID, $workflow);
    if($customer->ID) culSync('syncCustomer', $customer->ID, $workflow);
}

function updateOrderStatusWithCulcheck($workflow) {
    $order = $workflow->data_layer()->get_order();
    $workflow->log_action_note( $workflow , "order: $order->ID");

    if($order) culSync('syncOrder' , $order->ID, $workflow);
}

function culSync($mutation, $id, $workflow) {
    consumeCulcheck('mutation { ' . $mutation . '(id: ' . $id . ') { id }}', [], $workflow);
}

function consumeCulcheck($query, $variables = [], $workflow) {
    // Login:
    $token = culcheckLogin($workflow);
    // Consume:
    if($token) {
        $workflow->log_action_note( $workflow , "performing query: $query");

        graphqlQuery(
            'http://ec2-100-25-39-83.compute-1.amazonaws.com:8000/graphql',
            $query,
            $variables,
            $token,
            $workflow
        );
    }
}

$tempToken = null;
function culcheckLogin($workflow) {
global $tempToken;
    $workflow->log_action_note( $workflow , "tempToken: $tempToken");

    if($tempToken) return $tempToken;

    $login = graphqlQuery(
        'http://ec2-100-25-39-83.compute-1.amazonaws.com:8000/graphql',
        'mutation { login(email: "CulChekcWpUser", password: "ThisIsAVerySecretPassword") { token }}',
        [],
        null,
        $workflow
    );

$tempToken = $login['data']['login']['token'];
    $workflow->log_action_note( $workflow , "after login: $tempToken");

    return $tempToken ? $tempToken : false;
}

function graphqlQuery(string $endpoint, string $query, array $variables = [], ?string $token = null, $workflow): array
{
    $headers = ['Content-Type: application/json'];
    if (null !== $token) {
        $headers[] = "Authorization: $token";
    }

    if (false === $data = @file_get_contents($endpoint, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode(['query' => $query, 'variables' => $variables]),
        ]
    ]))) {
        $error = error_get_last();
        throw new \ErrorException($error['message'], $error['type']);
    }

    return json_decode($data, true);
}

//Add select department shortcode
add_action( 'woocommerce_single_product_summary', 'add_select_country_state', 15 );
function add_select_country_state() {
    echo do_shortcode('[vcwccr_country_selector]');
    // End of content
}