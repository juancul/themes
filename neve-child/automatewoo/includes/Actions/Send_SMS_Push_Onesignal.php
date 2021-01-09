<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Action_Send_SMS_Push_ OneSignal
 */
class Action_Send_SMS_Push_Onesignal extends Action_Send_SMS_Twilio {


	function load_admin_details() {
		parent::load_admin_details();

		$this->title = __( 'Send Push (OneSignal)', 'automatewoo' );
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

                        function sendMessage() {
                            $content      = array(
                                "en" => 'English Message'
                            );
                            $hashes_array = array();
                            array_push($hashes_array, array(
                                "id" => "like-button",
                                "text" => $message ,
                                "icon" => "https://cdn.vivecul.com.co/wp-content/uploads/2020/02/25230918/1.AndroidIconLogoFinalDGDIG.png",
                                "url" => "https://vivecul.com.co/mi-cuenta/pagos"
                            ));
                            array_push($hashes_array, array(
                                "id" => "like-button-2",
                                "text" => "Like2",
                                "icon" => "http://i.imgur.com/N8SN8ZS.png",
                                "url" => "https://yoursite.com"
                            ));
                            $fields = array(
                                'app_id' => "e630bc30-e4ab-4746-8743-07669af2d8d9",
                                'included_segments' => array(
                                    'All'
                                ),
                                'data' => array(
                                    "foo" => "bar"
                                ),
                                'contents' => $content,
                                'web_buttons' => $hashes_array
                            );
                            
                            $fields = json_encode($fields);
                            print("\nJSON sent:\n");
                            print($fields);
                            
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json; charset=utf-8',
                                'Authorization: Basic YzQ2NTQ3NTYtMWFiMS00YzM2LTg4MTYtODAwNjI5MzlhMmYz'
                            ));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                            curl_setopt($ch, CURLOPT_HEADER, FALSE);
                            curl_setopt($ch, CURLOPT_POST, TRUE);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			    $response = curl_exec($ch);
                            curl_close($ch);
                            
                            return $response;
                        }

                        $response = sendMessage();
                        $return["allresponses"] = $response;
                        $return = json_encode($return);

                        $data = json_decode($response, true);
                        /*print_r($data);
                        $id = $data['id'];
                        print_r($id);

                        print("\n\nJSON received:\n");
                        print($return);
                        print("\n");*/

                        $this->workflow->log_action_note( $this, __( $data, 'automatewoo' ) );

                /*if ( $request->is_successful() ) {
                        $this->workflow->log_action_note( $this, __( 'SMS successfully sent.', 'automatewoo' ) );
                }
                else {
                        // don't throw exception since the error is only for one recipient
                        $this->workflow->log_action_error( $this, $twilio->get_request_error_message( $rsult ) );
                }*/
        }

}
