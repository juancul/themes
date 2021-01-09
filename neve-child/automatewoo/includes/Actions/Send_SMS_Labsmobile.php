<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Action_Send_SMS_Labsmobile
 */
class Action_Send_SMS_Labsmobile extends Action_Send_SMS_Twilio {

	function load_admin_details() {
		parent::load_admin_details();

		$this->title = __( 'Send SMS (LabsMobile)', 'automatewoo' );
		$this->group = __( 'SMS', 'automatewoo' );
	}

	/**
	 * Sends an SMS to one recipient.
	 *
	 * @since 4.3.2
	 *
	 * @param string $recipient_field The phone number of the SMS recipient.
	 * @param string $message         The body of the SMS
	 */
	public function send_sms( $recipient_field, $message ) {
		//$twilio = Integrations::get_twilio();

		/*if ( ! $twilio || ! $recipient_field ) {
			return;
		}*/

		$is_sms_to_customer = $this->is_recipient_the_primary_customer( $recipient_field );

		// process any variables in the recipient field
		$recipient_phone = $this->workflow->variable_processor()->process_field( $recipient_field );

		// check if this SMS is going to the workflow's primary customer
		if ( $is_sms_to_customer ) {
			$customer = $this->workflow->data_layer()->get_customer();

			// check if the customer is unsubscribed
			if ( $this->workflow->is_customer_unsubscribed( $customer ) ) {
				$error = new \WP_Error( 'unsubscribed', __( "The recipient is not opted-in to this workflow.", 'automatewoo' ) );
				$this->workflow->log_action_email_error( $error, $this );
				return;
			}

			// because the SMS is to the primary customer, use the customer's country to parse the phone number
			$recipient_phone = Phone_Numbers::parse( $recipient_phone, $customer->get_billing_country() );
		}
		else {
			$recipient_phone = Phone_Numbers::parse( $recipient_phone );
		}

		//$request = $twilio->send_sms( $recipient_phone, $message );

			$url = 'http://api.labsmobile.com/get/send.php?';
            $url .= 'username=hola@vivecul.com&';
            $url .= 'password=sTeOAeMCjAjEBHFUg1o1iZuvKM81tbNM&';
            $url .= 'msisdn='.$recipient_phone.'&';
            $url .= 'message='.urlencode($message).'&';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $result = curl_exec($ch);
            curl_close($ch);

            $this->workflow->log_action_note( $this, __( $result, 'automatewoo' ) );

		/*if ( $request->is_successful() ) {
			$this->workflow->log_action_note( $this, __( 'SMS successfully sent.', 'automatewoo' ) );
		}
		else {
			// don't throw exception since the error is only for one recipient
			$this->workflow->log_action_error( $this, $twilio->get_request_error_message( $rsult ) );
		}*/
	}

}
