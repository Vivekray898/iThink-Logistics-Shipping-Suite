<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_API {

    private $api_pincode_url          = 'https://my.ithinklogistics.com/api_v3/pincode/check.json';
    private $api_zone_rate_url        = 'https://my.ithinklogistics.com/api_v3/rate/zone_rate.json';
    private $api_remittance_url       = 'https://my.ithinklogistics.com/api_v3/remittance/get.json';
    private $api_remittance_details_url = 'https://my.ithinklogistics.com/api_v3/remittance/get_details.json';

    public function __construct() {
        // Pincode
        add_action('wp_ajax_ithink_v3_check', array($this, 'handle_pincode_check_ajax'));
        add_action('wp_ajax_nopriv_ithink_v3_check', array($this, 'handle_pincode_check_ajax'));
        
        // Admin Tools
        add_action('wp_ajax_ithink_zone_rate_check', array($this, 'handle_zone_rate_ajax'));
        add_action('wp_ajax_ithink_remittance_check', array($this, 'handle_remittance_ajax'));
        add_action('wp_ajax_ithink_remittance_details_check', array($this, 'handle_remittance_details_ajax'));
    }

    // 1. PINCODE CHECK
    public function handle_pincode_check_ajax() {
        $pincode = sanitize_text_field( $_POST['pincode'] ?? '' );
        $access_token = get_option('ithink_access_token', '');
        $secret_key   = get_option('ithink_secret_key', '');

        if ( empty($pincode) || strlen($pincode) !== 6 || !ctype_digit($pincode) ) wp_send_json_error( 'Invalid Pincode' );
        if ( empty($access_token) || empty($secret_key) ) wp_send_json_error( 'API Keys missing' );

        $payload = array('data' => array('pincode' => $pincode, 'access_token' => $access_token, 'secret_key' => $secret_key));

        $response = wp_remote_post( $this->api_pincode_url, array('headers' => array('Content-Type' => 'application/json'), 'body' => json_encode($payload), 'timeout' => 20));
        if ( is_wp_error( $response ) ) wp_send_json_error( 'Connection failed' );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset($body['status']) && ($body['status'] === 'success' || $body['status'] === true) ) {
            $data = $body['data'] ?? [];
            if ( ! empty($data) ) wp_send_json_success( 'Delivery Available' );
            else wp_send_json_error( 'Service not available' );
        } else {
            wp_send_json_error( $body['remarks'] ?? 'Service not available' );
        }
    }

    // 2. ZONE RATE CALCULATOR
    public function handle_zone_rate_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

        $access_token = get_option('ithink_access_token', '');
        $secret_key   = get_option('ithink_secret_key', '');
        $from_pincode = WC()->countries->get_base_postcode(); 

        $payload = array(
            'data' => array(
                'from_pincode'        => $from_pincode,
                'shipping_length_cms' => sanitize_text_field($_POST['length']),
                'shipping_width_cms'  => sanitize_text_field($_POST['width']),
                'shipping_height_cms' => sanitize_text_field($_POST['height']),
                'shipping_weight_kg'  => sanitize_text_field($_POST['weight']),
                'order_type'          => 'forward',
                'payment_method'      => sanitize_text_field($_POST['payment']),
                'service_type'        => sanitize_text_field($_POST['service_type']),
                'product_mrp'         => sanitize_text_field($_POST['mrp']),
                'access_token'        => $access_token,
                'secret_key'          => $secret_key
            )
        );

        $response = wp_remote_post( $this->api_zone_rate_url, array('headers' => array('Content-Type' => 'application/json'), 'body' => json_encode($payload), 'timeout' => 25));
        if ( is_wp_error( $response ) ) wp_send_json_error( $response->get_error_message() );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset($body['status']) && ($body['status'] === 'success' || $body['status'] === true) ) {
            wp_send_json_success( $body['data'] );
        } else {
            $msg = isset($body['remarks']) ? $body['remarks'] : 'No rates found.';
            if(isset($body['data']) && is_string($body['data'])) $msg = $body['data'];
            wp_send_json_error( $msg );
        }
    }

    // 3. REMITTANCE SUMMARY
    public function handle_remittance_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $this->process_remittance_request( $this->api_remittance_url );
    }

    // 4. REMITTANCE DETAILS (AWB)
    public function handle_remittance_details_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $this->process_remittance_request( $this->api_remittance_details_url );
    }

    // Helper for Remittance requests
    private function process_remittance_request( $url ) {
        $access_token = get_option('ithink_access_token', '');
        $secret_key   = get_option('ithink_secret_key', '');
        $date         = sanitize_text_field($_POST['remittance_date']);

        if ( empty($date) ) wp_send_json_error('Please select a date.');

        $payload = array(
            'data' => array(
                'remittance_date' => $date,
                'access_token'    => $access_token,
                'secret_key'      => $secret_key
            )
        );

        $response = wp_remote_post( $url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => json_encode($payload),
            'timeout' => 25
        ));

        if ( is_wp_error( $response ) ) wp_send_json_error( 'Connection failed' );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset($body['status']) && ($body['status'] === 'success' || $body['status'] === true) ) {
            wp_send_json_success( $body['data'] );
        } else {
            $msg = isset($body['message']) ? $body['message'] : 'No data found.';
            wp_send_json_error( $msg );
        }
    }
}