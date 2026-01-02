<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_footer', array($this, 'render_admin_scripts'));
    }

    public function create_admin_menu() {
        add_options_page(
            'iThink Logistics', 
            'iThink Logistics', 
            'manage_options', 
            'ithink-logistics-settings', 
            array($this, 'settings_page_html')
        );
    }

    public function register_settings() {
        register_setting('ithink_group', 'ithink_access_token');
        register_setting('ithink_group', 'ithink_secret_key');
        register_setting('ithink_group', 'ithink_geoapify_key');
        register_setting('ithink_group', 'ithink_pickup_id'); // NEW SETTING
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>iThink Logistics Configuration</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#settings" class="nav-tab nav-tab-active" onclick="ithinkSwitchTab(event, 'settings')">API Settings</a>
                <a href="#calculator" class="nav-tab" onclick="ithinkSwitchTab(event, 'calculator')">Rate Calculator</a>
                <a href="#remittance" class="nav-tab" onclick="ithinkSwitchTab(event, 'remittance')">Remittance</a>
            </h2>

            <div id="tab-settings" class="ithink-tab-content" style="display:block; margin-top:20px;">
                <form method="post" action="options.php">
                    <?php settings_fields('ithink_group'); ?>
                    <?php do_settings_sections('ithink_group'); ?>
                    
                    <h3>1. Logistics Credentials</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Access Token</th>
                            <td><input type="text" name="ithink_access_token" value="<?php echo esc_attr(get_option('ithink_access_token')); ?>" style="width: 400px;" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Secret Key</th>
                            <td><input type="text" name="ithink_secret_key" value="<?php echo esc_attr(get_option('ithink_secret_key')); ?>" style="width: 400px;" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Pickup Warehouse ID</th>
                            <td>
                                <input type="text" name="ithink_pickup_id" value="<?php echo esc_attr(get_option('ithink_pickup_id')); ?>" style="width: 100px;" />
                                <p class="description">Required for booking. Find this ID in your iThink Dashboard (e.g., 24).</p>
                            </td>
                        </tr>
                    </table>

                    <h3>2. Address Autocomplete (Geoapify)</h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Geoapify API Key</th>
                            <td>
                                <input type="text" name="ithink_geoapify_key" value="<?php echo esc_attr(get_option('ithink_geoapify_key')); ?>" style="width: 400px;" />
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>

            <div id="tab-calculator" class="ithink-tab-content" style="display:none; margin-top:20px;">
                <p>Please refer to previous steps for Calculator UI code.</p>
            </div>
            <div id="tab-remittance" class="ithink-tab-content" style="display:none; margin-top:20px;">
                 <p>Please refer to previous steps for Remittance UI code.</p>
            </div>

        </div>
        <?php
    }

    public function render_admin_scripts() {
        if ( ! isset($_GET['page']) || $_GET['page'] !== 'ithink-logistics-settings' ) return;
        ?>
        <script>
        function ithinkSwitchTab(evt, tabName) {
            evt.preventDefault();
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("ithink-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("nav-tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
            }
            document.getElementById("tab-" + tabName).style.display = "block";
            evt.currentTarget.className += " nav-tab-active";
        }
        // ... (Include previous JS for calculator and remittance) ...
        </script>
        <?php
    }
}