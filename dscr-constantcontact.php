<?php
/**
 * Constant Contact Integration for DSCR Calculator
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- PART 1: SETTINGS PAGE ---

add_action('admin_menu', 'dscr_cc_setup_menu');
function dscr_cc_setup_menu() {
    add_submenu_page(
        'dscr-calculator',
        'DSCR Constant Contact',
        'Constant Contact',
        'manage_options',
        'dscr-cc-settings',
        'dscr_cc_settings_page_html'
    );
}

add_action('admin_init', 'dscr_cc_register_settings');
function dscr_cc_register_settings() {
    register_setting('dscr_cc_settings_group', 'dscr_cc_client_id');
    register_setting('dscr_cc_settings_group', 'dscr_cc_client_secret');
    register_setting('dscr_cc_settings_group', 'dscr_cc_list_id');
}

function dscr_cc_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle Manual Settings Save
    if (isset($_POST['dscr_cc_save_settings']) && check_admin_referer('dscr_cc_settings_action', 'dscr_cc_nonce')) {
        update_option('dscr_cc_client_id', sanitize_text_field($_POST['dscr_cc_client_id']));
        update_option('dscr_cc_client_secret', sanitize_text_field($_POST['dscr_cc_client_secret']));
        update_option('dscr_cc_list_id', sanitize_text_field($_POST['dscr_cc_list_id']));
        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    $client_id     = get_option('dscr_cc_client_id');
    $client_secret = get_option('dscr_cc_client_secret');
    $list_id       = get_option('dscr_cc_list_id');
    $access_token  = get_option('dscr_cc_access_token');
    
    $redirect_uri = admin_url('admin.php?page=dscr-cc-settings');

    // Handle Disconnect
    if (isset($_POST['dscr_cc_disconnect'])) {
        delete_option('dscr_cc_access_token');
        delete_option('dscr_cc_refresh_token');
        delete_option('dscr_cc_token_expires');
        echo '<div class="updated"><p>Disconnected from Constant Contact. Tokens cleared.</p></div>';
        $access_token = '';
    }

    // Handle Connect Redirect
    if (isset($_POST['dscr_cc_connect']) && !empty($client_id)) {
        $state = wp_generate_password(12, false);
        update_option('dscr_cc_oauth_state', $state);
        
        $auth_url = 'https://authz.constantcontact.com/oauth2/default/v1/authorize?' . http_build_query(array(
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => 'contact_data',
            'state'         => $state,
        ));
        
        error_log('DSCR CC: Redirecting to Authorization URL');
        wp_redirect($auth_url);
        exit;
    }

    // Handle OAuth Callback
    if (isset($_GET['code']) && isset($_GET['state'])) {
        $saved_state = get_option('dscr_cc_oauth_state');
        if ($_GET['state'] === $saved_state) {
            $code = sanitize_text_field($_GET['code']);
            error_log('DSCR CC: Received authorization code');
            
            $token_url = 'https://authz.constantcontact.com/oauth2/default/v1/token';
            $auth_header = 'Basic ' . base64_encode($client_id . ':' . $client_secret);
            
            $response = wp_remote_post($token_url, array(
                'headers' => array(
                    'Authorization' => $auth_header,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
                'body' => array(
                    'grant_type'   => 'authorization_code',
                    'code'         => $code,
                    'redirect_uri' => $redirect_uri,
                ),
            ));

            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['access_token'])) {
                    update_option('dscr_cc_access_token', $body['access_token']);
                    update_option('dscr_cc_refresh_token', isset($body['refresh_token']) ? $body['refresh_token'] : '');
                    update_option('dscr_cc_token_expires', time() + $body['expires_in']);
                    error_log('DSCR CC: Tokens saved successfully');
                    wp_redirect($redirect_uri);
                    exit;
                } else {
                    error_log('DSCR CC Token Error: ' . wp_remote_retrieve_body($response));
                    echo '<div class="error"><p>Failed to get access token. Check error log.</p></div>';
                }
            } else {
                error_log('DSCR CC WP Error: ' . $response->get_error_message());
                echo '<div class="error"><p>Connection error: ' . $response->get_error_message() . '</p></div>';
            }
        }
    }

    // Handle Test Connection
    if (isset($_POST['dscr_cc_test']) && $access_token) {
        $test_response = wp_remote_get('https://api.cc.email/v3/contact_lists', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
        ));
        $test_body = wp_remote_retrieve_body($test_response);
        echo '<div class="updated"><h4>Test API Response (Contact Lists):</h4><pre>' . esc_html($test_body) . '</pre></div>';
    }

    // Handle Send Test Contact
    if (isset($_POST['dscr_cc_send_test']) && $access_token) {
        $test_email = 'test@test.com';
        $test_first = 'Test';
        $test_last  = 'User';
        
        echo '<h3>Test Contact Results:</h3>';
        $result = dscr_add_to_constant_contact($test_email, $test_first, $test_last);
        
        if ($result) {
            echo '<div class="updated"><p>Test contact sent successfully! ✅</p></div>';
        } else {
            echo '<div class="error"><p>Failed to send test contact. Check debug.log for details. ❌</p></div>';
        }
    }

    $status = $access_token ? '<span style="color: green;">Connected ✅</span>' : '<span style="color: red;">Not Connected ❌</span>';

    ?>
    <div class="wrap">
        <h2>DSCR Constant Contact Settings</h2>
        <form method="post" action="">
            <?php wp_nonce_field('dscr_cc_settings_action', 'dscr_cc_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Client ID (API Key)</th>
                    <td><input type="text" name="dscr_cc_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Client Secret</th>
                    <td><input type="password" name="dscr_cc_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>List ID</th>
                    <td><input type="text" name="dscr_cc_list_id" value="<?php echo esc_attr($list_id); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Redirect URI</th>
                    <td><code><?php echo esc_url($redirect_uri); ?></code><br><small>Add this to your Constant Contact App settings.</small></td>
                </tr>
                <tr>
                    <th>Connection Status</th>
                    <td><?php echo $status; ?></td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'dscr_cc_save_settings'); ?>
        </form>

        <hr>

        <h3>Connection Tools & Debugging</h3>
        <form method="post" action="">
            <input type="submit" name="dscr_cc_connect" class="button button-primary" value="Connect to Constant Contact" <?php disabled(empty($client_id) || empty($client_secret)); ?>>
            <input type="submit" name="dscr_cc_test" class="button" value="Verify Credentials (GET Lists)" <?php disabled(empty($access_token)); ?>>
            <input type="submit" name="dscr_cc_send_test" class="button button-secondary" value="Send Test Contact (Sign Up Form)" <?php disabled(empty($access_token)); ?>>
            <input type="submit" name="dscr_cc_disconnect" class="button" value="Disconnect / Clear Tokens" <?php disabled(empty($access_token)); ?>>
        </form>
    </div>
    <?php
}

// --- PART 3: ADD CONTACT FUNCTION ---

function dscr_add_to_constant_contact($email, $first_name, $last_name) {
    error_log('[DSCR-CC] Form submitted - Email: ' . $email);
    
    $access_token = get_option('dscr_cc_access_token');
    $expires      = get_option('dscr_cc_token_expires');
    $client_id    = get_option('dscr_cc_client_id');
    $client_secret = get_option('dscr_cc_client_secret');
    $list_id      = get_option('dscr_cc_list_id', 'bac74420-3dd4-11f1-b7ab-02420a320002');

    error_log('[DSCR-CC] Token value: ' . $access_token);
    error_log('[DSCR-CC] List ID: ' . $list_id);

    if (empty($access_token)) {
        error_log('[DSCR-CC] Error: No access token found');
        return false;
    }

    // Refresh token if expired
    if (time() >= $expires) {
        error_log('[DSCR-CC] Token expired - refreshing');
        $refresh_token = get_option('dscr_cc_refresh_token');
        $token_url = 'https://authz.constantcontact.com/oauth2/default/v1/token';
        $auth_header = 'Basic ' . base64_encode($client_id . ':' . $client_secret);
        
        $refresh_response = wp_remote_post($token_url, array(
            'headers' => array(
                'Authorization' => $auth_header,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refresh_token,
            ),
        ));

        if (!is_wp_error($refresh_response)) {
            $refresh_body = json_decode(wp_remote_retrieve_body($refresh_response), true);
            if (isset($refresh_body['access_token'])) {
                $access_token = $refresh_body['access_token'];
                update_option('dscr_cc_access_token', $access_token);
                update_option('dscr_cc_refresh_token', isset($refresh_body['refresh_token']) ? $refresh_body['refresh_token'] : $refresh_token);
                update_option('dscr_cc_token_expires', time() + $refresh_body['expires_in']);
                error_log('[DSCR-CC] Token refreshed successfully');
            } else {
                error_log('[DSCR-CC] Refresh Error Body: ' . wp_remote_retrieve_body($refresh_response));
                return false;
            }
        } else {
            error_log('[DSCR-CC] Refresh WP Error: ' . $refresh_response->get_error_message());
            return false;
        }
    }

    $url = 'https://api.cc.email/v3/contacts/sign_up_form';
    $body = array(
        'email_address' => array(
            'address'            => $email,
            'permission_to_send' => 'implicit'
        ),
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'list_memberships' => array($list_id)
    );

    error_log('[DSCR-CC] Payload: ' . json_encode($body));

    $response = wp_remote_post($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
        'body' => json_encode($body),
        'timeout' => 15
    ));

    if (is_wp_error($response)) {
        error_log('[DSCR-CC] API Error: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    error_log('[DSCR-CC] API Response Code: ' . $response_code);
    error_log('[DSCR-CC] API Response Body: ' . $response_body);

    if ($response_code >= 200 && $response_code < 300) {
        error_log('[DSCR-CC] Contact added successfully');
        return true;
    } else {
        switch ($response_code) {
            case 401:
                error_log('[DSCR-CC] Token expired - refreshing (unauthorized)');
                break;
            case 404:
                error_log('[DSCR-CC] List ID not found (' . $list_id . ')');
                break;
            case 400:
                error_log('[DSCR-CC] Bad request - show full error');
                break;
            default:
                error_log('[DSCR-CC] Unexpected response code: ' . $response_code);
        }
        return false;
    }
}
