<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_API {

    private $api_pincode_url = 'https://my.ithinklogistics.com/api_v3/pincode/check.json';

    public function __construct() {
        add_action('wp_ajax_ithink_v3_check', array($this, 'handle_pincode_check_ajax'));
        add_action('wp_ajax_nopriv_ithink_v3_check', array($this, 'handle_pincode_check_ajax'));
    }

    public function handle_pincode_check_ajax() {
        $pincode      = sanitize_text_field( $_POST['pincode'] ?? '' );
        $access_token = get_option('ithink_access_token', '');
        $secret_key   = get_option('ithink_secret_key', '');

        if ( empty( $pincode ) || strlen($pincode) !== 6 || !ctype_digit($pincode) ) {
            wp_send_json_error( 'Invalid Pincode' );
        }

        if ( empty( $access_token ) || empty( $secret_key ) ) {
            wp_send_json_error( 'API Keys missing' );
        }

        $payload = array(
            'data' => array(
                'pincode'      => $pincode,
                'access_token' => $access_token,
                'secret_key'   => $secret_key
            )
        );

        $response = wp_remote_post( $this->api_pincode_url, array(
            'headers' => array( 'Content-Type'  => 'application/json' ),
            'body'    => json_encode( $payload ),
            'timeout' => 20
        ));

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'Connection failed' );
        }

        $body_json = wp_remote_retrieve_body( $response );
        $result    = json_decode( $body_json, true );

        if ( isset( $result['status'] ) && ( $result['status'] === 'success' || $result['status'] === true ) ) {
            $data = $result['data'] ?? [];
            if ( ! empty( $data ) ) {
                wp_send_json_success( 'Delivery Available' );
            } else {
                wp_send_json_error( 'Service not available' );
            }
        } else {
            wp_send_json_error( $result['remarks'] ?? 'Service not available' );
        }
    }
}