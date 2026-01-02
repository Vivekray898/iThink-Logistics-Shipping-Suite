<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_Checkout {

    public function __construct() {
        add_action('wp_footer', array($this, 'render_checkout_script'));
        add_action('wp_head', array($this, 'render_checkout_styles'));
    }

    public function render_checkout_styles() {
        if ( ! is_checkout() ) return;
        ?>
        <style>
            .ithink-geo-suggestions {
                position: absolute; background: white; border: 1px solid #ccc; border-top: none;
                z-index: 9999; width: 100%; max-height: 200px; overflow-y: auto;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .ithink-geo-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
            .ithink-geo-item:hover { background-color: #f0f0f0; }
        </style>
        <?php
    }

    public function render_checkout_script() {
        if ( ! is_checkout() ) return;
        $geo_key = get_option('ithink_geoapify_key', '');
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var geoApiKey = "<?php echo esc_js($geo_key); ?>";
            var fields = { address: '#billing_address_1', city: '#billing_city', state: '#billing_state', postcode: '#billing_postcode', country: '#billing_country' };
            var continueBtn = '.cfw-continue-to-shipping-btn, .cfw-continue-to-payment-btn'; 
            var msgContainerId = 'ithink-checkout-status';

            // --- GEOAPIFY LOGIC ---
            if(geoApiKey) {
                var $addrInput = $(fields.address);
                $addrInput.wrap('<div style="position:relative;"></div>');
                var $suggestions = $('<div class="ithink-geo-suggestions" style="display:none;"></div>');
                $addrInput.after($suggestions);
                var debounceTimer;

                $addrInput.on('input', function() {
                    var query = $(this).val();
                    clearTimeout(debounceTimer);
                    if(query.length < 4) { $suggestions.hide(); return; }

                    debounceTimer = setTimeout(function() {
                        fetch(`https://api.geoapify.com/v1/geocode/autocomplete?text=${encodeURIComponent(query)}&apiKey=${geoApiKey}&limit=5`)
                        .then(response => response.json())
                        .then(result => {
                            $suggestions.empty();
                            if(result.features && result.features.length > 0) {
                                result.features.forEach(feature => {
                                    var props = feature.properties;
                                    var label = `<strong>${props.address_line1}</strong><small>${props.city || ''}, ${props.state || ''}</small>`;
                                    var $item = $(`<div class="ithink-geo-item" data-json='${JSON.stringify(props)}'>${label}</div>`);
                                    $suggestions.append($item);
                                });
                                $suggestions.show();
                            } else { $suggestions.hide(); }
                        });
                    }, 300);
                });

                $(document).on('click', '.ithink-geo-item', function() {
                    var data = $(this).data('json');
                    if(data.address_line1) $(fields.address).val(data.address_line1);
                    if(data.city) $(fields.city).val(data.city).trigger('change');
                    if(data.postcode) { $(fields.postcode).val(data.postcode).trigger('change'); $(fields.postcode).trigger('keyup'); }
                    if(data.state) {
                        var stateText = data.state.toLowerCase();
                        $(fields.state + ' option').each(function() {
                            if($(this).text().toLowerCase() === stateText) $(fields.state).val($(this).val()).trigger('change');
                        });
                    }
                    $suggestions.hide();
                });
                
                $(document).on('click', function(e) {
                     if (!$(e.target).closest(fields.address).length && !$(e.target).closest('.ithink-geo-suggestions').length) $suggestions.hide();
                });
            }

            // --- PINCODE LOGIC ---
            function showStatus(msg, type) {
                $('#' + msgContainerId).remove(); 
                if(!msg) return;
                var color = (type === 'success') ? '#0f8a1e' : '#e01616';
                $(fields.postcode).closest('.woocommerce-input-wrapper').after('<div id="'+msgContainerId+'" style="color:'+color+'; font-size:12px; margin-top:5px; font-weight:600;">'+msg+'</div>');
            }

            function checkPincode(pincode) {
                $(continueBtn).addClass('disabled').css({'pointer-events': 'none', 'opacity': '0.5'});
                showStatus('Checking serviceability...', 'info');
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>', type: 'POST',
                    data: { action: 'ithink_v3_check', pincode: pincode },
                    success: function(response) {
                        if (response.success) {
                            showStatus('✅ ' + response.data, 'success');
                            $(continueBtn).removeClass('disabled').css({'pointer-events': 'auto', 'opacity': '1'});
                            $('body').trigger('update_checkout'); 
                        } else {
                            showStatus('❌ ' + response.data, 'error');
                        }
                    },
                    error: function() { showStatus('System error.', 'error'); $(continueBtn).removeClass('disabled').css({'pointer-events': 'auto', 'opacity': '1'}); }
                });
            }

            $(document).on('change blur keyup', fields.postcode, function() {
                var pin = $(this).val();
                if(pin && pin.length === 6 && /^\d+$/.test(pin)) { checkPincode(pin); } 
                else {
                    $('#' + msgContainerId).remove();
                    if(pin.length > 6) { showStatus('Pincode must be 6 digits', 'error'); $(continueBtn).addClass('disabled').css({'pointer-events': 'none', 'opacity': '0.5'}); }
                }
            });

            setTimeout(function(){
                var prePin = $(fields.postcode).val();
                if(prePin && prePin.length === 6) checkPincode(prePin);
            }, 2000);
        });
        </script>
        <?php
    }
}