<?php

use TOT\Reasons;
use TOT\Integrations\WooCommerce\Checkout;

add_shortcode('tot-reputation-status', 'tot_reputation_status_shortcode');

/**
 * Provides short contextual text snippets to describe the current state of the user.
 * @param $attrs
 * @param null $content
 * @return string
 */
function tot_reputation_status_shortcode($attrs, $content = null)
{
    global $tot_plugin_text_domain;
    global $post;

    // normalize attribute keys, lowercase
    if (!empty($attrs)) {
        $attrs = array_change_key_case($attrs, CASE_LOWER);
    }

    $settings = shortcode_atts(
        array(
            'wp-userid' => get_current_user_id(),
            'order-id' => '',
            'tot-transaction-id' => '',
            'auto-launch-when-not-verified' => '',
            'show-get-verified-button' => 'false'
        ),
        $attrs
    );

    // resolve the appUserid
    $wpUserid = $settings['wp-userid'];
    $appUserid = $settings['app-userid'] = tot_user_id($wpUserid, null, false);

    // resolve the appTransactionId
    $appTransactionId = null;
    $order_id = Checkout::get_current_wc_order_id($settings['order-id']);
    $order = null;
    if ( class_exists( 'WooCommerce' ) ) {
        $order = wc_get_order($order_id);
    }

    $reputation = null;
    if (!empty($order) && !is_wp_error($order)) {
        tot_log_as_html_comment('Saw order.', $order_id);
        // If we're within the context of an order then we ALWAYS use the transaction id to find the reputation.
        $appTransactionId = $settings['tot-transaction-id'] ? $settings['tot-transaction-id'] : get_post_meta( $order_id, 'tot_transaction_id', true );
        $settings['tot-transaction-id'] = $appTransactionId;
        if (!empty($appTransactionId)) {
            $reputation = tot_get_order_reputation($appTransactionId);
        }
    } else if (!empty($appUserid) && !is_wp_error($appUserid)) {
        tot_log_as_html_comment('Saw user.', $order_id);
        $reputation = tot_get_user_reputation($appUserid);
    }

    // Must either be an existing user or part of a TOT checkout.
    if ((empty($appTransactionId) || is_wp_error($appTransactionId)) && (empty($appUserid) || is_wp_error($appUserid))) {
        tot_log_as_html_comment('Neither user nor order found.', '');
        return apply_filters('tot_verification_gates_request_signin_block', "<p class='woocommerce-info'>Inicia sesión para verificar tu identidad</p>");
    }

    $reasons = is_wp_error($reputation) ? $reputation : $reputation->reasons;

    if (is_wp_error($reasons)) {
        return apply_filters('tot_verification_gates_error_block', "<p class='woocommerce-error'>Hubo un problema verificando tu estado de verificación de identidad. Por favor revisa en unos momentos</p>");
    } else {
        $reasons = new Reasons($reasons);
    }

    $hasReasons = isset($reasons);
    $approved = $hasReasons && $reasons->is_positive('govtIdPositiveReview');
    $pendingReview = $hasReasons && $reasons->is_positive('govtIdPendingReview');
    $rejected = $hasReasons && $reasons->is_negative('govtIdPositiveReview');

    if ($approved) {
        tot_log_as_html_comment('tot_verification_gates_approved_block', $reasons);
        return apply_filters('tot_verification_gates_approved_block', "<p class='woocommerce-message'>¡Gracias! Tu identidad ha sido verificada</p>");
    }

    if ($pendingReview) {
        tot_log_as_html_comment('tot_verification_gates_pending_block', $reasons);
        return apply_filters('tot_verification_gates_pending_block', "<p class='woocommerce-info'>Tu verificación de identidad está en proceso, debes estar pendiente de tu email.</p><p class='woocommerce-info'>Puedes revisar tu proceso acá: <a href='#tot_get_verified'>".do_shortcode( '[tot-wp-embed tot-widget="accountConnector" verification-model="person"][/tot-wp-embed]
' )." </a></p>");
    }

    if ($rejected) {
        tot_log_as_html_comment('tot_verification_gates_rejected_block', $reasons);
        return apply_filters('tot_verification_gates_rejected_block', "<p class='woocommerce-error'>Tu identidad <strong>NO FUE APROBADA </strong> por favor ponte en contacto acá: <a href='vivecul.com.co/ayuda'>vivecul.com.co/ayuda</a></p>");
    }

    $not_verified = !($pendingReview || $rejected || $approved);

    if ($not_verified) {
        tot_log_as_html_comment('tot_verification_gates_not_verified_block', $reasons);

        $verify_person_data = array();
        $verify = null;
        if (!empty($appUserid)) {
            $verify_person_data['appUserid'] = $appUserid;
            $appData = tot_get_user_app_data($wpUserid);
            $verify_person_data['person'] = $appData;
            $verify = new \TOT\API_Person($verify_person_data, 'POST');
        }
        if (!empty($appTransactionId)) {
            $verify = new \TOT\API_Person($verify_person_data);
            $verify->set_details_from_order($order);
        }
        $error_callback = array('\TOT\API_Person', 'handle_verify_person_api_error');
        $verify->verify_result = $verify->send($error_callback);

        $autolaunchwhennotverified = $settings['auto-launch-when-not-verified'];
        $autoLaunchModal = '' !== $autolaunchwhennotverified && $autolaunchwhennotverified !== 'false';
        $settingsData = '';
        if ($autoLaunchModal) {
            $settingsData .= ' data-tot-auto-open-modal=\'true\' ';
        }

        // Add a div with id = tot-auto-launch-modal to the page to automatically launch the modal.
        return apply_filters("tot_verification_gates_not_verified_block",
            "<div class='tot-wc-order-validation' $settingsData>"
            . '<a data-tot-verification-required="true" href="#tot_get_verified">'
            . __('Verification', $tot_plugin_text_domain)
            . '</a> '
            . __(' is required before you can proceed.', 'token-of-trust') . '</div>');
    }
}