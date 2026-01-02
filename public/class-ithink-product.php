<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_Product_Page {

    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'render_pincode_box'), 25);
    }

    public function render_pincode_box() {
        ?>
        <div class="ithink-wrapper" style="margin: 20px 0; padding: 15px; background: #f7f7f7; border: 1px solid #ddd; border-radius: 4px;">
            <p style="margin: 0 0 10px; font-weight: 600;">Check Delivery Availability</p>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="ithink_pincode" placeholder="Enter Pincode" maxlength="6" style="padding: 8px; border: 1px solid #ccc; width: 100%; max-width: 150px;">
                <button type="button" id="ithink_check_btn" class="button" style="padding: 0 15px;">Check</button>
            </div>
            <div id="ithink_message" style="margin-top: 10px; font-size: 14px;"></div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#ithink_pincode').on('keyup', function() {
                if ($(this).val().length !== 6) {
                    $('#ithink_message').html('');
                }
            });

            $('#ithink_check_btn').click(function(e) {
                e.preventDefault();
                var pin = $('#ithink_pincode').val();
                var $msg = $('#ithink_message');
                
                if (pin.length !== 6 || !/^\d+$/.test(pin)) {
                    $msg.html('<span style="color:red;">Please enter a valid 6-digit pincode.</span>');
                    return;
                }

                $msg.html('<span style="color:#666;">Checking...</span>');
                
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: { action: 'ithink_v3_check', pincode: pin },
                    success: function(res) {
                        if (res.success) {
                            $msg.html('<span style="color:green; font-weight:bold;">✅ ' + res.data + '</span>');
                        } else {
                            $msg.html('<span style="color:red; font-weight:bold;">❌ ' + res.data + '</span>');
                        }
                    },
                    error: function() {
                        $msg.html('<span style="color:red;">Error connecting.</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}