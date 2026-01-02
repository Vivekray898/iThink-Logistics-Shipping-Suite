<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Shipping Calculator Class
 * Integrates directly with WooCommerce Shipping Method API
 */
if ( class_exists( 'WC_Shipping_Method' ) ) {
    class iThink_Shipping_Method extends WC_Shipping_Method {

        private $api_rate_url = 'https://my.ithinklogistics.com/api_v3/rate/check.json';

        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'ithink_shipping';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'iThink Logistics', 'ithink' );
            $this->method_description = __( 'Real-time shipping rates from iThink Logistics API V3.', 'ithink' );
            $this->supports           = array( 'shipping-zones', 'instance-settings' );

            $this->init();
        }

        public function init() {
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title   = $this->get_option( 'title' );
            $this->enabled = $this->get_option( 'enabled' );

            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable', 'ithink' ),
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'       => __( 'Method Title', 'ithink' ),
                    'type'        => 'text',
                    'default'     => __( 'iThink Logistics', 'ithink' ),
                ),
                'fallback_weight' => array(
                    'title'       => __( 'Fallback Weight (kg)', 'ithink' ),
                    'type'        => 'number',
                    'description' => __( 'Used if product weight is missing.', 'ithink' ),
                    'default'     => '0.5',
                    'custom_attributes' => array( 'step' => '0.1' )
                ),
            );
        }

        /**
         * The Core Logic: Calculates Shipping
         */
        public function calculate_shipping( $package = array() ) {
            
            // 1. Get Settings & Keys
            $access_token = get_option('ithink_access_token');
            $secret_key   = get_option('ithink_secret_key');
            $store_pincode = WC()->countries->get_base_postcode();

            if( empty($access_token) || empty($secret_key) ) return;
            if( empty($package['destination']['postcode']) ) return;

            // 2. Calculate Weight & Dimensions
            $weight = 0;
            $length = 10; $width = 10; $height = 10; // Default fallback dimensions (cm)
            $mrp = 0;

            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                $qty     = $item['quantity'];
                
                // Weight
                $w = (float) $product->get_weight();
                $weight += ( $w > 0 ? $w : (float) $this->get_option('fallback_weight') ) * $qty;

                // Price
                $mrp += $product->get_price() * $qty;

                // Simple Max Dimension Logic (Improvement over adding them)
                $l = (float) $product->get_length();
                $w = (float) $product->get_width();
                $h = (float) $product->get_height();
                if($l > $length) $length = $l;
                if($w > $width)  $width  = $w;
                if($h > $height) $height = $h;
            }

            // Ensure limits (API Max: 10kg usually, adjust as needed)
            if ($weight == 0) $weight = 0.5;

            // 3. Determine Payment Method (Prepaid vs COD)
            // Rates often differ. Defaulting to 'prepaid' is safer for quotes, 
            // but we can try to detect the chosen method.
            $payment_method = 'prepaid';
            if ( ! is_admin() && ! empty( WC()->session->get( 'chosen_payment_method' ) ) ) {
                $chosen = WC()->session->get( 'chosen_payment_method' );
                if ( strpos( $chosen, 'cod' ) !== false ) {
                    $payment_method = 'cod';
                }
            }

            // 4. Prepare API Payload
            $payload = array(
                'data' => array(
                    'from_pincode'        => $store_pincode,
                    'to_pincode'          => $package['destination']['postcode'],
                    'shipping_length_cms' => $length,
                    'shipping_width_cms'  => $width,
                    'shipping_height_cms' => $height,
                    'shipping_weight_kg'  => $weight,
                    'order_type'          => 'forward',
                    'payment_method'      => $payment_method,
                    'product_mrp'         => $mrp,
                    'access_token'        => $access_token,
                    'secret_key'          => $secret_key
                )
            );

            // 5. Call API
            $response = wp_remote_post( $this->api_rate_url, array(
                'headers' => array( 'Content-Type'  => 'application/json' ),
                'body'    => json_encode( $payload ),
                'timeout' => 25 
            ));

            if ( is_wp_error( $response ) ) return;

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            // 6. Process Rates
            if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
                
                $eta = isset($body['expected_delivery_date']) ? $body['expected_delivery_date'] : '';

                foreach ( $body['data'] as $rate ) {
                    // Check if service is available (pickup: Y)
                    if ( isset($rate['pickup']) && $rate['pickup'] === 'Y' ) {
                        
                        $label = $rate['logistic_name'];
                        if($eta) $label .= ' (' . $eta . ')';

                        $cost = floatval( $rate['rate'] );

                        // Add the rate to WooCommerce
                        $this->add_rate( array(
                            'id'    => $this->id . '_' . sanitize_title( $rate['logistic_name'] ),
                            'label' => $label,
                            'cost'  => $cost,
                            'meta_data' => array(
                                'courier_id' => $rate['logistic_name']
                            )
                        ));
                    }
                }
            }
        }
    }
}