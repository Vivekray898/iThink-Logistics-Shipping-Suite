<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class iThink_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
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
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>iThink Logistics Configuration</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ithink_group'); ?>
                <?php do_settings_sections('ithink_group'); ?>
                
                <h2 style="padding-top:20px; border-top:1px solid #ccc;">1. Logistics API Credentials</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Access Token</th>
                        <td><input type="text" name="ithink_access_token" value="<?php echo esc_attr(get_option('ithink_access_token')); ?>" style="width: 400px;" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Secret Key</th>
                        <td><input type="text" name="ithink_secret_key" value="<?php echo esc_attr(get_option('ithink_secret_key')); ?>" style="width: 400px;" /></td>
                    </tr>
                </table>

                <h2 style="padding-top:20px; border-top:1px solid #ccc;">2. Address Autocomplete (Geoapify)</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Geoapify API Key</th>
                        <td>
                            <input type="text" name="ithink_geoapify_key" value="<?php echo esc_attr(get_option('ithink_geoapify_key')); ?>" style="width: 400px;" />
                            <p class="description">Get your key from <a href="https://www.geoapify.com/" target="_blank">Geoapify Dashboard</a>.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}