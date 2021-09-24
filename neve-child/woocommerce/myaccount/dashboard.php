<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<?php
        global $current_user;
        get_currentuserinfo();
        $user_email = (string) $current_user->user_email;
        //initialize OneSignal
        /*echo "<script>window.addEventListener('load', function() {
        window._oneSignalInitOptions.promptOptions = {
              slidedown: {
                  enabled: true,
                  autoPrompt: true,
                  actionMessage: 'Recibe notificaciones sobre tu alquiler',
                  acceptButtonText: 'PERMITIR',
                  cancelButtonText: 'CANCELAR',
                  displayPredicate: false,
                  timeDelay: 0
              }};


          window.OneSignal = window.OneSignal || [];
          // Why use .push? See: http://stackoverflow.com/a/38466780/555547 
          window.OneSignal.push(function() {
           // Never call init() more than once. An error will occur. 
            window.OneSignal.init(window._oneSignalInitOptions);
          });
          })

          let myCustomUniqueUserId = '".$user_email."';

          OneSignal.push(function() {
                OneSignal.setExternalUserId(myCustomUniqueUserId);
          });

          </script>";*/

        //echo "<h3 id='cul-verify-id'>Verifica tu identidad</h3>";
        //echo "<p>Es obligatorio que sigas el proceso de verificación para poder despachar tu pedido:</p>";
        //echo '<center>';
        /*echo '<div class="wp-block-buttons">
<div class="wp-block-button is-style-secondary"><a class="wp-block-button__link has-background has-neve-link-color-background-color" href="#tot_get_verified">Verifica tu identidad</a></div>
</div>';*/
        //echo do_shortcode( '[tot-reputation-status auto-launch-when-not-verified="false"]' );
        //echo do_shortcode( '[tot-wp-embed tot-widget="accountConnector" verification-model="person"][/tot-wp-embed]');
        //echo "</center><hr>";
        echo "<br><h3 id='questions-form'>Responde las siguientes preguntas para poder despachar tu alquiler</h3>";
        echo "<br><p>Por favor responde con sinceridad, en algunos casos solicitaremos corroborar las respuestas con solicitudes posteriores, por ejemplo un certificado laboral.</p>";
        echo do_shortcode( '[user-meta-profile form="User Extra Info"]' );
        echo "<br><br>";


        
        ?>

<p>
	<?php
	printf(
		/* translators: 1: user display name 2: logout url */
		wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ), $allowed_html ),
		'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
		esc_url( wc_logout_url() )
	);
	?>
</p>

<p>
	<?php
	/* translators: 1: Orders URL 2: Address URL 3: Account URL. */
	$dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">billing address</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' );
	if ( wc_shipping_enabled() ) {
		/* translators: 1: Orders URL 2: Addresses URL 3: Account URL. */
		$dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping and billing addresses</a>, and <a href="%3$s">edit your password and account details</a>.', 'woocommerce' );
	}
	printf(
		wp_kses( $dashboard_desc, $allowed_html ),
		esc_url( wc_get_endpoint_url( 'orders' ) ),
		esc_url( wc_get_endpoint_url( 'edit-address' ) ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) )
	);
	?>
</p>

<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */