<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_Order_Box {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_shipping_meta_box' ) );
        add_action( 'admin_footer', array( $this, 'render_order_script' ) );
    }

    public function add_shipping_meta_box() {
        add_meta_box(
            'ithink_shipping_box',
            'iThink Logistics',
            array( $this, 'render_meta_box_content' ),
            'shop_order',
            'side',
            'high'
        );
    }

    public function render_meta_box_content( $post ) {
        $order_id = $post->ID;
        $awb = get_post_meta( $order_id, 'ithink_awb', true );
        $courier = get_post_meta( $order_id, 'ithink_courier', true );
        $track_url = get_post_meta( $order_id, 'ithink_tracking_url', true );

        echo '<div style="text-align:center; padding:10px;">';

        if ( $awb ) {
            echo '<div class="notice notice-success inline" style="margin:0 0 10px 0;"><p><strong>Order Booked!</strong></p></div>';
            echo '<p><strong>Courier:</strong> ' . esc_html( $courier ) . '</p>';
            echo '<p><strong>AWB:</strong> ' . esc_html( $awb ) . '</p>';
            if($track_url) {
                echo '<a href="' . esc_url( $track_url ) . '" target="_blank" class="button">Track Shipment</a>';
            }
        } else {
            echo '<p>Dimensions (cm) & Weight (kg):</p>';
            echo '<input type="text" id="ithink_l" placeholder="L" style="width:30px;" value="10"> x ';
            echo '<input type="text" id="ithink_w" placeholder="W" style="width:30px;" value="10"> x ';
            echo '<input type="text" id="ithink_h" placeholder="H" style="width:30px;" value="10"> ';
            echo '<br><br>';
            echo '<input type="text" id="ithink_wt" placeholder="Weight" style="width:60px;" value="0.5"> kg';
            
            echo '<hr>';
            echo '<button type="button" id="ithink_book_order" class="button button-primary button-large" style="width:100%;">Book Shipment</button>';
            echo '<div id="ithink_book_msg" style="margin-top:10px;"></div>';
        }
        echo '</div>';
    }

    public function render_order_script() {
        global $post_type;
        if ( 'shop_order' !== $post_type ) return;
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#ithink_book_order').click(function() {
                var btn = $(this);
                var msg = $('#ithink_book_msg');
                var order_id = <?php echo get_the_ID(); ?>;

                if( !confirm('Are you sure you want to book this shipment?') ) return;

                btn.prop('disabled', true).text('Booking...');
                msg.html('Connecting to iThink API...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ithink_create_order',
                        order_id: order_id,
                        length: $('#ithink_l').val(),
                        width:  $('#ithink_w').val(),
                        height: $('#ithink_h').val(),
                        weight: $('#ithink_wt').val()
                    },
                    success: function(response) {
                        if(response.success) {
                            msg.html('<span style="color:green;">' + response.data + '</span>');
                            setTimeout(function(){ location.reload(); }, 1500);
                        } else {
                            btn.prop('disabled', false).text('Book Shipment');
                            msg.html('<span style="color:red;">Error: ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).text('Book Shipment');
                        msg.html('<span style="color:red;">System Error.</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}