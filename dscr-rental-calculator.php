<?php
/**
 * Plugin Name: DSCR Rental Calculator Test
 * Description: A real-time Debt Service Coverage Ratio (DSCR) calculator for real estate investors.
 * Version: 1.3.5
 * Author: GLTS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include Constant Contact Integration
require_once plugin_dir_path(__FILE__) . 'dscr-constantcontact.php';

// --- DB Setup on Plugin Activation ---
register_activation_hook(__FILE__, 'dscr_create_leads_table');
function dscr_create_leads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dscr_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// --- Admin Menu and Settings ---
add_action('admin_menu', 'dscr_setup_admin_menu');
function dscr_setup_admin_menu() {
    add_menu_page('DSCR Settings', 'DSCR Calculator', 'manage_options', 'dscr-calculator', 'dscr_admin_page_callback', 'dashicons-calculator');
}


function dscr_admin_page_callback() {
    if (!current_user_can('manage_options')) return;

    // Handle settings save
    if (isset($_POST['dscr_save_settings']) && check_admin_referer('dscr_settings_action', 'dscr_settings_nonce')) {
        update_option('dscr_lead_email', sanitize_email($_POST['dscr_lead_email']));
        update_option('dscr_user_email_subject', sanitize_text_field($_POST['dscr_user_email_subject']));
        update_option('dscr_user_email_body', wp_kses_post($_POST['dscr_user_email_body']));
        update_option('dscr_lead_email_subject', sanitize_text_field($_POST['dscr_lead_email_subject']));
        update_option('dscr_lead_email_body', wp_kses_post($_POST['dscr_lead_email_body']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $default_user_subject = 'Your Rental Property Report Is Here!';
    $default_user_body = "Hi {name},\n\nCongratulations on taking the first step in maximizing the return on your rental property by using our DSCR Calculator.\n\nWe've put together a handy PDF summary of your report. It's packed with all the essential details you need - from estimated interest payments to projected ROI. Think of it as your project's financial roadmap, ready for you to explore!\n\nHave questions or want to chat about your report? Our expert team is here and eager to help. Just reply to this email or <a href=\"tel:3475886460\" class=\"highlight\">call us at (347)588-6460</a>.\n\nWe're thrilled to be part of your fix-and-flip journey and can't wait to celebrate your success!\n\nHappy flipping!\n\nYour Partners at Express Capital Financing";

    $lead_email = get_option('dscr_lead_email', get_option('admin_email'));
    $user_subject = get_option('dscr_user_email_subject', $default_user_subject);
    if ($user_subject === 'Your DSCR Calculator Results') {
        $user_subject = $default_user_subject;
    }
    
    $user_body = get_option('dscr_user_email_body', $default_user_body);
    if (trim($user_body) === "Hello,\n\nHere is the PDF report for your DSCR calculation.\n\nThank you!" || trim($user_body) === "Hello,

Here is the PDF report for your DSCR calculation.

Thank you!") {
        $user_body = $default_user_body;
    }

    $lead_subject = get_option('dscr_lead_email_subject', 'New DSCR Calculator Lead');
    $lead_body = get_option('dscr_lead_email_body', "A new lead has downloaded the DSCR PDF.\n\nName: {name}\nEmail: {email}");

    global $wpdb;
    $table_name = $wpdb->prefix . 'dscr_leads';
    // Suppress errors temporarily in case the table hasn't been created yet (activation hook issues)
    $suppress = $wpdb->suppress_errors();
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 50");
    $wpdb->suppress_errors($suppress);

    ?>
    <div class="wrap">
        <h2>DSCR Calculator Dashboard</h2>
        
        <h3 style="margin-top:30px;">Email Templates & Settings</h3>
        <form method="post" action="">
            <?php wp_nonce_field('dscr_settings_action', 'dscr_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="dscr_lead_email">Admin / Lead Receiver Email</label></th>
                    <td><input type="email" name="dscr_lead_email" id="dscr_lead_email" value="<?php echo esc_attr($lead_email); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="dscr_user_email_subject">User Email Subject</label></th>
                    <td><input type="text" name="dscr_user_email_subject" id="dscr_user_email_subject" value="<?php echo esc_attr($user_subject); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="dscr_user_email_body">User Email Body (Use {name} and {email})<br><small>This content will be wrapped in the structured Express Capital template with your logo.</small></label></th>
                    <td><textarea name="dscr_user_email_body" id="dscr_user_email_body" rows="10" class="large-text"><?php echo esc_textarea($user_body); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="dscr_lead_email_subject">Lead Email Subject</label></th>
                    <td><input type="text" name="dscr_lead_email_subject" id="dscr_lead_email_subject" value="<?php echo esc_attr($lead_subject); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="dscr_lead_email_body">Lead Email Body (Use {name} and {email})</label></th>
                    <td><textarea name="dscr_lead_email_body" id="dscr_lead_email_body" rows="5" class="large-text"><?php echo esc_textarea($lead_body); ?></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="dscr_save_settings" class="button button-primary" value="Save Settings">
            </p>
        </form>

        <h3 style="margin-top:40px;">Recent Leads Log</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date/Time</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->id); ?></td>
                            <td><?php echo esc_html($log->time); ?></td>
                            <td><?php echo esc_html($log->name); ?></td>
                            <td><?php echo esc_html($log->email); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No leads found or table not created yet. (Try deactivating/reactivating the plugin)</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


// --- AJAX Endpoint to Send Emails ---
add_action('wp_ajax_dscr_submit_lead', 'dscr_submit_lead_handler');
add_action('wp_ajax_nopriv_dscr_submit_lead', 'dscr_submit_lead_handler');

function dscr_submit_lead_handler() {
    check_ajax_referer('dscr_ajax_nonce', 'nonce');

    if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['pdf_data'])) {
        wp_send_json_error('Missing fields');
        wp_die();
    }

    file_put_contents(dirname(__FILE__) . '/debug_post.txt', print_r($_POST, true) . "\nRaw Form Data: " . (isset($_POST['form_data']) ? $_POST['form_data'] : 'Not set'));

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $pdf_b64 = isset($_POST['pdf_data']) ? $_POST['pdf_data'] : '';

    if (empty($name) || empty($email) || empty($pdf_b64)) {
        wp_send_json_error('Missing required fields.');
    }

    if (strpos($pdf_b64, 'base64,') !== false) {
        $parts = explode('base64,', $pdf_b64);
        $pdf_b64 = $parts[1];
    }
    
    $pdf_decoded = base64_decode($pdf_b64);
    if ($pdf_decoded === false) {
        wp_send_json_error('Invalid PDF data.');
    }

    $upload_dir = wp_upload_dir();
    $temp_filename = 'DSCR_Report_' . time() . '.pdf';
    $temp_path = trailingslashit($upload_dir['basedir']) . $temp_filename;
    file_put_contents($temp_path, $pdf_decoded);

    $default_user_subject = 'Your Rental Property Report Is Here!';
    $default_user_body = "Hi {name},\n\nCongratulations on taking the first step in maximizing the return on your rental property by using our DSCR Calculator.\n\nWe've put together a handy PDF summary of your report. It's packed with all the essential details you need - from estimated interest payments to projected ROI. Think of it as your project's financial roadmap, ready for you to explore!\n\nHave questions or want to chat about your report? Our expert team is here and eager to help. Just reply to this email or <a href=\"tel:3475886460\" class=\"highlight\">call us at (347)588-6460</a>.\n\nWe're thrilled to be part of your fix-and-flip journey and can't wait to celebrate your success!\n\nHappy flipping!\n\nYour Partners at Express Capital Financing";

    // Get settings
    $lead_email = get_option('dscr_lead_email', get_option('admin_email'));
    $user_subject = get_option('dscr_user_email_subject', $default_user_subject);
    if ($user_subject === 'Your DSCR Calculator Results') {
        $user_subject = $default_user_subject;
    }
    
    $user_body = get_option('dscr_user_email_body', $default_user_body);
    if (trim($user_body) === "Hello,\n\nHere is the PDF report for your DSCR calculation.\n\nThank you!" || trim($user_body) === "Hello,

Here is the PDF report for your DSCR calculation.

Thank you!") {
        $user_body = $default_user_body;
    }

    $lead_subject = get_option('dscr_lead_email_subject', 'New DSCR Calculation Lead: ' . $name);
    if (strpos($lead_subject, 'New DSCR Calculator Lead') !== false) {
        $lead_subject = 'New DSCR Calculation Lead: ' . $name;
    }

    $user_body_parsed = str_replace(array('{name}', '{email}'), array($name, $email), $user_body);

    $logo_url = plugin_dir_url(__FILE__) . 'logo.png';
    $fd = isset($_POST['form_data']) ? json_decode(stripslashes($_POST['form_data']), true) : array();
    
    // Always use the hardcoded template for the Lead Email (user requested light title, bolded values)
    $lead_body_html = '<!DOCTYPE html>
<html>
<head>
  <title>New Lead Summary</title>
  <style>
body { margin: 0; padding: 0; background-color: #F0EEF7; font-family: Arial, sans-serif; }
.logo { text-align: center; padding: 30px 0 10px; }
.logo img { max-width: 350px; }
.card { width: 600px; max-width: 90%; margin: 0 auto 40px; background: #ffffff; border-top: 6px solid #ff5a00; padding: 25px 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.title { text-align: center; color: #6c7aa0; font-size: 18px; font-weight: normal; margin-bottom: 20px; }
hr { border: none; border-top: 1px solid #ddd; margin-bottom: 20px; }
.row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f9f9f9; }
.label { color: #6c7aa0; font-weight: normal; width: 50%; font-size: 14px; }
.value { color: #1f2a44; font-weight: bold; width: 50%; text-align: right; font-size: 14px; }
  </style>
</head>
<body>
  <div class="logo">
    <img src="' . esc_url($logo_url) . '" alt="Express Capital Financing">
  </div>
  <div class="card">
    <h2 class="title">New Lead Submission</h2>
    <hr>
    <div class="row"><span class="label">Full Name</span><span class="value">' . esc_html(isset($fd["fullName"]) ? $fd["fullName"] : $name) . '</span></div>
    <div class="row"><span class="label">Email</span><span class="value">' . esc_html(isset($fd["email"]) ? $fd["email"] : $email) . '</span></div>
    <div class="row"><span class="label">Purchase or Refinance?</span><span class="value">' . esc_html(isset($fd["purchaseOr"]) ? $fd["purchaseOr"] : "Purchase") . '</span></div>
    <div class="row"><span class="label">Purchase Price</span><span class="value">' . esc_html(isset($fd["purchasePrice"]) ? $fd["purchasePrice"] : "0") . '</span></div>
    <div class="row"><span class="label">Number of Units</span><span class="value">' . esc_html(isset($fd["numberOf119"]) ? $fd["numberOf119"] : "1") . '</span></div>
    <div class="row"><span class="label">LTV (%)</span><span class="value">' . esc_html(isset($fd["ltv120"]) ? $fd["ltv120"] : "") . '</span></div>
    <div class="row"><span class="label">Interest Rate (%)</span><span class="value">' . esc_html(isset($fd["number121"]) ? $fd["number121"] : "") . '</span></div>
    <div class="row"><span class="label">Amortization (Years)</span><span class="value">' . esc_html(isset($fd["number122"]) ? $fd["number122"] : "") . '</span></div>
    <div class="row"><span class="label">Origination Points(%)</span><span class="value">' . esc_html(isset($fd["number123"]) ? $fd["number123"] : "") . '</span></div>
    <div class="row"><span class="label">Loan Closing Fees</span><span class="value">' . esc_html(isset($fd["loanClosing"]) ? $fd["loanClosing"] : "") . '</span></div>
    <div class="row"><span class="label">Total Rent</span><span class="value">' . esc_html(isset($fd["typeA153"]) ? $fd["typeA153"] : "") . '</span></div>
    <div class="row"><span class="label">Vacancy Rate (%)</span><span class="value">' . esc_html(isset($fd["vacancyRate152"]) ? $fd["vacancyRate152"] : "") . '</span></div>
    <div class="row"><span class="label">Property Taxes</span><span class="value">' . esc_html(isset($fd["propertyTaxes"]) ? $fd["propertyTaxes"] : "") . '</span></div>
    <div class="row"><span class="label">Insurance</span><span class="value">' . esc_html(isset($fd["insurance"]) ? $fd["insurance"] : "") . '</span></div>
    <div class="row"><span class="label">MONTHLY HOA</span><span class="value">' . esc_html(isset($fd["monthlyHoa"]) ? $fd["monthlyHoa"] : "") . '</span></div>
    <div class="row"><span class="label">Annual Repairs & Maint</span><span class="value">' . esc_html(isset($fd["annualRepairs"]) ? $fd["annualRepairs"] : "") . '</span></div>
    <div class="row"><span class="label">Annual Utilities</span><span class="value">' . esc_html(isset($fd["annualUtilities"]) ? $fd["annualUtilities"] : "") . '</span></div>
    <div class="row"><span class="label">3rd Party Closing Cost</span><span class="value">' . esc_html(isset($fd["thirdParty"]) ? $fd["thirdParty"] : "") . '</span></div>
    <div class="row"><span class="label">Price Per Unit</span><span class="value">' . esc_html(isset($fd["pricePer89"]) ? $fd["pricePer89"] : "") . '</span></div>
    <div class="row"><span class="label">Loan Amount</span><span class="value">' . esc_html(isset($fd["loanamount"]) ? $fd["loanamount"] : "") . '</span></div>
    <div class="row"><span class="label">Down Payment</span><span class="value">' . esc_html(isset($fd["downPayment"]) ? $fd["downPayment"] : "") . '</span></div>
    <div class="row"><span class="label">Monthly Payment (P&I)</span><span class="value">' . esc_html(isset($fd["monthlyPayment92"]) ? $fd["monthlyPayment92"] : "") . '</span></div>
    <div class="row"><span class="label">PITIA</span><span class="value">' . esc_html(isset($fd["typeA93"]) ? $fd["typeA93"] : "") . '</span></div>
    <div class="row"><span class="label">Annual Mortgage Payment</span><span class="value">' . esc_html(isset($fd["typeA94"]) ? $fd["typeA94"] : "") . '</span></div>
    <div class="row"><span class="label">Origination Fee Amount</span><span class="value">' . esc_html(isset($fd["typeA95"]) ? $fd["typeA95"] : "") . '</span></div>
    <div class="row"><span class="label">Gross Monthly Rental</span><span class="value">' . esc_html(isset($fd["grossMonthly96"]) ? $fd["grossMonthly96"] : "") . '</span></div>
    <div class="row"><span class="label">Vacancy Deduction</span><span class="value">' . esc_html(isset($fd["typeA99"]) ? $fd["typeA99"] : "") . '</span></div>
    <div class="row"><span class="label">Net Effective Rent</span><span class="value">' . esc_html(isset($fd["typeA99_2"]) ? $fd["typeA99_2"] : "") . '</span></div>
    <div class="row"><span class="label">Taxes & Insurance</span><span class="value">' . esc_html(isset($fd["taxesAnd100"]) ? $fd["taxesAnd100"] : "") . '</span></div>
    <div class="row"><span class="label">Annual HOA</span><span class="value">' . esc_html(isset($fd["typeA101"]) ? $fd["typeA101"] : "") . '</span></div>
    <div class="row"><span class="label">Operating Expenses</span><span class="value">' . esc_html(isset($fd["operatingExpenses104"]) ? $fd["operatingExpenses104"] : "") . '</span></div>
    <div class="row"><span class="label">Net Operating Income</span><span class="value">' . esc_html(isset($fd["typeA105"]) ? $fd["typeA105"] : "") . '</span></div>
    <div class="row"><span class="label">Net Monthly Cashflow</span><span class="value">' . esc_html(isset($fd["typeA106"]) ? $fd["typeA106"] : "") . '</span></div>
    <div class="row"><span class="label">Cap Rate</span><span class="value">' . esc_html(isset($fd["typeA107"]) ? $fd["typeA107"] : "") . '</span></div>
    <div class="row"><span class="label">Cash on Cash Return</span><span class="value">' . esc_html(isset($fd["typeA108"]) ? $fd["typeA108"] : "") . '</span></div>
    <div class="row"><span class="label">DSCR</span><span class="value">' . esc_html(isset($fd["typeA109"]) ? $fd["typeA109"] : "") . '</span></div>
    <div class="row"><span class="label">Total Closing Cost</span><span class="value">' . esc_html(isset($fd["typeA110"]) ? $fd["typeA110"] : "") . '</span></div>
    <div class="row"><span class="label">Cash Needed to Close</span><span class="value">' . esc_html(isset($fd["typeA111"]) ? $fd["typeA111"] : "") . '</span></div>
    <hr>
  </div>
</body>
</html>';
    $user_body_html = '<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>' . esc_html($user_subject) . '</title>
    <style>
body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif; }
.email-container { width: 600px; max-width: 90%; margin: 30px auto; background: #ffffff; padding: 30px; border: 1px solid #ddd; }
.logo { text-align: center; margin-bottom: 20px; }
.logo img { max-width: 220px; height: auto; }
.divider { border-top: 1px solid #ddd; margin: 20px 0; }
.highlight { color: #0073e6; text-decoration: underline; font-weight: bold; }
.content { color: #333; font-size: 14px; line-height: 1.6; }
.content p { margin-bottom: 15px; }
    </style>
  </head>
  <body>
   <div class="email-container">
        <div class="logo">
            <img src="' . esc_url($logo_url) . '" alt="Express Capital Financing">
        </div>
        <div class="divider"></div>
        <div class="content">
            ' . wpautop($user_body_parsed) . '
        </div>
    </div>
  </body>
</html>';

    $attachments = array($temp_path);
    wp_mail($email, $user_subject, $user_body_html, array('Content-Type: text/html; charset=UTF-8'), $attachments);
    wp_mail($lead_email, $lead_subject, $lead_body_html, array('Content-Type: text/html; charset=UTF-8'), $attachments);

    // Constant Contact Integration
    $name_parts = explode(' ', $name, 2);
    $first_name = $name_parts[0];
    $last_name  = isset($name_parts[1]) ? $name_parts[1] : '';
    
    try {
        dscr_add_to_constant_contact($email, $first_name, $last_name);
    } catch (Exception $e) {
        error_log('DSCR CC Exception: ' . $e->getMessage());
    }

    @unlink($temp_path);

    global $wpdb;
    $table_name = $wpdb->prefix . 'dscr_leads';
    $suppress = $wpdb->suppress_errors();
    $wpdb->insert(
        $table_name,
        array(
            'time' => current_time('mysql'),
            'name' => $name,
            'email' => $email
        )
    );
    $wpdb->suppress_errors($suppress);

    wp_send_json_success('Successfully sent emails.');
}

function dscr_calc_shortcode()
{
    ob_start();
    ?>
    <div id="dscr-calculator-app" class="dscr-wrapper">
        <style>
            :root {
                --green: #0b6e3d;
                --dark: #2f3b4f;
                --dark-2: #1f2937;
                --gray: #f4f5f7;
                --text: #1f2937;
                --muted: #6b7280;
                --gold: #c79a3b;
                --card: #3a465a;
            }

            body {
                margin: 0;
                padding: 20px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background-color: #f9fafb;
            }

            .dscr-wrapper {
                max-width: 1200px;
                margin: 40px auto;
                color: var(--text);
                line-height: 1.5;
            }

            .dscr-container {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 32px;
                align-items: flex-start;
            }

            .dscr-container h1 {
                font-size: 32px;
                margin-bottom: 8px;
                color: inherit;
            }

            .dscr-container .subtitle {
                color: var(--muted);
                margin-bottom: 32px;
            }

            .dscr-field-group {
                margin-bottom: 24px;
            }

            .dscr-field-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .dscr-control-row {
                display: flex;
                gap: 14px;
            }

            .slider-container {
                flex: 1;
                display: flex;
                align-items: center;
            }

            .slider-track {
                width: 100%;
                height: 48px;
                background-color: #ffffff;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                position: relative;
                padding: 2px;
                box-sizing: border-box;
                display: flex;
                align-items: center;
            }

            .slider-fill {
                height: calc(100% - 8px);
                background-color: #006b35;
                border-radius: 6px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                transition: width 0.3s ease;
                min-width: 48px;
            }

            .slider-thumb {
                width: 42px;
                height: calc(100% - 4px);
                background-color: #27a768;
                border-radius: 6px;
                margin-right: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.1);
                cursor: pointer;
                flex-shrink: 0;
            }

            .thumb-lines {
                display: flex;
                gap: 4px;
            }

            .thumb-lines span {
                width: 3px;
                height: 16px;
                background-color: #006b35;
                border-radius: 2px;
                opacity: 0.8;
            }

            .slider-track:hover .slider-fill {
                filter: brightness(1.05);
            }

            .dscr-range-input {
                position: absolute;
                inset: 0;
                opacity: 0;
                cursor: pointer;
                width: 100%;
                height: 100%;
                z-index: 3;
                margin: 0;
                padding: 0;
                -webkit-appearance: none;
                appearance: none;
            }

            .dscr-range-input::-webkit-slider-runnable-track {
                width: 100%;
                height: 100%;
                cursor: pointer;
            }

            .dscr-range-input::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 32px;
                height: 24px;
                cursor: pointer;
                margin-top: -12px;
            }

            .dscr-range-input::-moz-range-track {
                width: 100%;
                height: 100%;
                cursor: pointer;
            }

            .dscr-range-input::-moz-range-thumb {
                width: 32px;
                height: 24px;
                cursor: pointer;
                border: none;
                background: transparent;
            }

            .dscr-num-box {
                width: 180px;
                display: flex;
                align-items: center;
                background: #f1f1f1;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                height: 44px;
                overflow: hidden;
            }

            .dscr-num-box .prefix {
                background: #e5e7eb;
                color: #111827;
                font-weight: 700;
                padding: 0 6px;
                height: 100%;
                display: flex;
                align-items: center;
                border-right: 1px solid #d1d5db;
                font-size: 12px;
                min-width: fit-content;
                justify-content: center;
                flex-shrink: 0;
            }

            .dscr-num-box input {
                flex: 1;
                border: none !important;
                background: transparent !important;
                padding: 0 8px !important;
                text-align: right;
                font-weight: 700;
                font-size: 14px;
                color: #111827;
                outline: none;
                box-shadow: none !important;
                width: 100%;
                min-width: 0;
            }

            /* RESULTS PANEL */
            .dscr-results {
                background: var(--dark);
                border-radius: 20px;
                padding: 28px;
                color: #fff;
                margin-top: 150px;
                position: sticky;
                top: 20px;
            }

            .dscr-results h2 {
                text-align: center;
                margin-bottom: 24px;
                color: #fff;
                border: none;
                margin-top: 0;
            }

            .result-block {
                margin-bottom: 20px;
            }

            .result-label {
                font-size: 14px;
                color: #cbd5e1;
                margin-bottom: 6px;
            }

            .result-value {
                background: var(--dark-2);
                border-radius: 10px;
                padding: 16px;
                text-align: center;
                font-size: 28px;
                font-weight: 700;
            }

            .payment-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .payment-card {
                background: var(--card);
                border-radius: 12px;
                padding: 16px;
                min-height: 100px;
            }

            .payment-card .amount {
                font-size: 20px;
                font-weight: 700;
            }

            .payment-card .desc {
                font-size: 13px;
                color: #cbd5e1;
                margin-top: 6px;
            }

            .payment-info {
                margin-top: 8px;
                font-size: 11px;
                color: #9ca3af;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .payment-info::before {
                content: "ℹ";
                font-size: 12px;
            }

            .cta-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                margin-top: 24px;
            }

            .cta {
                background: var(--gold);
                color: #fff;
                border: none;
                border-radius: 12px;
                padding: 14px 12px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s;
            }

            .cta:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .cta.secondary {
                background: #374151;
            }

            /* Checkbox Styling */
            .custom-check-row {
                margin-top: 24px;
                display: flex;
                gap: 12px;
                align-items: flex-start;
                cursor: pointer;
            }

            #checkIcon {
                background: #16a34a;
                color: #fff;
                border-radius: 6px;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                flex-shrink: 0;
                transition: background 0.2s;
            }

            @media (max-width: 900px) {
                .dscr-container {
                    grid-template-columns: 1fr;
                }

                .dscr-results {
                    position: static;
                }
            }
        </style>

        <div class="dscr-container">
            <div class="dscr-inputs">
                <h1>DSCR Rental Calculator</h1>
                <p class="subtitle">Enter your property details below to see your ratio.</p>

                <!-- Fields -->
                <div class="dscr-field-group" data-id="price">
                    <label>Purchase Price/As-Is Value</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="5000000" step="5000" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="5000" value="0" />
                        </div>
                    </div>
                </div>



                <div class="dscr-field-group" data-id="ltv">
                    <label>LTV (%)</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="100" step="1" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="text" inputmode="decimal" data-step="1" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="rate">
                    <label>Interest Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="1" max="15" step="0.01" value="6.75" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="text" inputmode="decimal" data-step="0.01" value="6.75" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="term">
                    <label>Loan Term</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="5" max="40" step="1" value="30" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">Yrs</span>
                            <input type="text" inputmode="decimal" data-step="1" value="30" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="origination">
                    <label>Origination Points</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="5" step="0.25" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">pts</span>
                            <input type="text" inputmode="decimal" data-step="0.25" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="closing-fees">
                    <label>Loan Closing Fees</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="10000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="rent">
                    <label>Monthly Gross Rent</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="50000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="vacancy">
                    <label>Vacancy Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="20" step="0.5" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="text" inputmode="decimal" data-step="0.5" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="taxes">
                    <label>Property Taxes</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="50000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="insurance">
                    <label>Insurance</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="20000" step="50" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="hoa">
                    <label>Monthly HOA</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="2000" step="10" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="10" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="repair">
                    <label>Annual Repair and Maintenance</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="5000" step="50" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="utilities">
                    <label>Annual Utilities</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="10000" step="50" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="third-party">
                    <label>3rd Party Closing Cost</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="20000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESULTS -->
            <div class="dscr-results">
                <h2>Your Results</h2>

                <div class="result-block">
                    <div class="result-label">DSCR (Debt Service Coverage Ratio)</div>
                    <div class="result-value" id="val-dscr">0.00</div>
                </div>

                <div class="result-block">
                    <div class="result-label">Loan Amount</div>
                    <div class="result-value" id="val-loan">$0</div>
                </div>

                <div class="result-label" style="margin-top:24px;">Monthly Breakdown</div>

                <div class="payment-grid">
                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-pi">$0.00</div>
                            <div class="desc">Principal &amp; Interest</div>
                        </div>
                        <div class="payment-info">Based on term and rate</div>
                    </div>

                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-pitia">$0.00</div>
                            <div class="desc">Monthly PITIA</div>
                        </div>
                        <div class="payment-info">Principal, Interest, Taxes, Insurance, HOA</div>
                    </div>
                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-cashflow">$0.00</div>
                            <div class="desc">Net Monthly CashFlow</div>
                        </div>
                        <div class="payment-info">Net Monthly CashFlow</div>
                    </div>
                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-closing-cost">0.00%</div>
                            <div class="desc">ROI</div>
                        </div>
                        <div class="payment-info">ROI Divided by Purchase Price/Value</div>
                    </div>
                </div>

                <div class="custom-check-row" id="checkWrapper">
                    <input type="checkbox" id="readyCheck" checked style="display:none;" />
                    <div id="checkIcon">✓</div>
                    <div>
                        <strong>Looks good.</strong><br />
                        <span style="font-size: 13px; color: #cbd5e1;">Ready to proceed with your application?</span>
                    </div>
                </div>

                <div class="cta-group">
                    <button class="cta secondary" id="downloadPdfBtn">Send Email</button>
                    <button class="cta" id="applyBtn">Apply Now</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup Modal -->
    <div id="dscr-lead-modal" class="dscr-modal" style="display: none;">
        <div class="dscr-modal-content">
            <h3 style="margin-top:0;">Download Your PDF Report</h3>
            <p style="font-size:14px;color:#6b7280;margin-bottom:15px;">Please enter your details below to get your free DSCR report emailed to you.</p>
            <div class="dscr-field-group">
                <label>Name</label>
                <input type="text" id="dscr-lead-name" class="regular-text" style="width:100%; border:1px solid #ccc; padding:8px; border-radius:6px; box-sizing:border-box;" required/>
            </div>
            <div class="dscr-field-group" style="margin-top:10px;">
                <label>Email</label>
                <input type="email" id="dscr-lead-email" class="regular-text" style="width:100%; border:1px solid #ccc; padding:8px; border-radius:6px; box-sizing:border-box;" required/>
            </div>
            <div class="dscr-modal-actions" style="margin-top:20px; display:flex; justify-content:space-between; gap:10px;">
                <button type="button" id="dscr-lead-cancel" class="cta secondary" style="flex:1;">Cancel</button>
                <button type="button" id="dscr-lead-submit" class="cta" style="flex:1;">Download PDF</button>
            </div>
            <div id="dscr-lead-error" style="color:red; font-size:13px; margin-top:10px; display:none;"></div>
            <div id="dscr-lead-loading" style="color:#0b6e3d; font-size:13px; margin-top:10px; display:none;">Processing... Please wait. Connecting...</div>
        </div>
    </div>
    
    <style>
        .dscr-modal {
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dscr-modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #1f2937;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        var dscrAjax = {
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo wp_create_nonce('dscr_ajax_nonce'); ?>'
        };
        var dscrPdfLogo = "<?php echo esc_js(plugin_dir_url(__FILE__) . 'logo.png'); ?>";
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const app = document.getElementById('dscr-calculator-app');
            const groups = app.querySelectorAll('.dscr-field-group');
            const vals = {
                dscr: app.querySelector('#val-dscr'),
                loan: app.querySelector('#val-loan'),
                pi: app.querySelector('#val-pi'),
                pitia: app.querySelector('#val-pitia'),
                cashflow: app.querySelector('#val-cashflow'),
                closingCost: app.querySelector('#val-closing-cost')
            };

            // Store all calculated values for PDF
            let calculatedValues = {};
            const applyBtn = app.querySelector('#applyBtn');
            const readyCheck = app.querySelector('#readyCheck');
            const checkIcon = app.querySelector('#checkIcon');
            const checkWrapper = app.querySelector('#checkWrapper');

            function formatCurrency(num) {
                return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function formatNumberWithCommas(num) {
                if (num === '' || num === null || num === undefined) return '0';
                const n = parseFloat(String(num).replace(/,/g, ''));
                if (isNaN(n)) return '0';
                // Check if number has decimals
                const parts = String(n).split('.');
                const intPart = parseInt(parts[0], 10).toLocaleString('en-US');
                if (parts.length > 1) {
                    return intPart + '.' + parts[1];
                }
                return intPart;
            }

            function parseNumericValue(val) {
                return parseFloat(String(val).replace(/,/g, '')) || 0;
            }

            function updateUI(groupId, value, source) {
                const group = app.querySelector(`.dscr-field-group[data-id="${groupId}"]`);
                const range = group.querySelector('.dscr-range-input');
                const textInput = group.querySelector('input[type="text"]');
                const fill = group.querySelector('.slider-fill');

                // Sync the other input
                if (source === 'range') {
                    textInput.value = formatNumberWithCommas(value);
                } else if (source === 'number') {
                    const numVal = parseNumericValue(value);
                    const min = parseFloat(range.min);
                    const max = parseFloat(range.max);
                    const clampedNum = Math.max(min, Math.min(max, numVal));
                    range.value = clampedNum;
                    value = clampedNum;
                }

                // Calculate percentage for visual fill
                const min = parseFloat(range.min);
                const max = parseFloat(range.max);
                const clampedVal = Math.min(Math.max(parseFloat(value) || 0, min), max);

                // Calculate percentage (0 to 100)
                let percent = 0;
                if (max > min) {
                    percent = ((clampedVal - min) / (max - min)) * 100;
                }

                // Clamp percent to valid range (0-100)
                percent = Math.max(0, Math.min(100, percent));

                // Update fill width - the thumb is inside the fill, so it will be positioned correctly
                fill.style.width = percent + '%';

                calculate();
            }

            function calculate() {
                const getVal = (id) => parseNumericValue(app.querySelector(`[data-id="${id}"] input[type="text"]`).value);

                const price = getVal('price');
                const units = 1;
                const ltv = getVal('ltv');
                const rate = getVal('rate');
                const term = getVal('term') || 1;
                const origination = getVal('origination');
                const closingFees = getVal('closing-fees');
                const rent = getVal('rent');
                const vacancy = getVal('vacancy');
                const taxes = getVal('taxes');
                const insurance = getVal('insurance');
                const hoa = getVal('hoa');
                const repair = getVal('repair');
                const utilities = getVal('utilities');
                const thirdParty = getVal('third-party');

                // Calculate loan amount from purchase price and LTV
                // Formula: Loan Amount = Purchase Price * LTV
                const loanAmount = (price * ltv) / 100;
                vals.loan.textContent = '$' + loanAmount.toLocaleString();

                // Calculate origination fee (as percentage of loan amount)
                const originationFee = (loanAmount * origination) / 100;

                // Calculate monthly Principal & Interest
                // Using fixed 360 payments (30 years)
                let monthlyPI = 0;
                if (loanAmount > 0) {
                    const monthlyRate = (rate / 100) / 12;
                    const numberOfPayments = 360; // Fixed to 360 payments
                    if (monthlyRate === 0) {
                        monthlyPI = loanAmount / numberOfPayments;
                    } else {
                        monthlyPI = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numberOfPayments)) / (Math.pow(1 + monthlyRate, numberOfPayments) - 1);
                    }
                }
                vals.pi.textContent = formatCurrency(monthlyPI);

                // Calculate monthly expenses
                const monthlyTaxes = taxes / 12;
                const monthlyInsurance = insurance / 12;
                const monthlyHOA = hoa; // Already monthly

                // Total monthly debt service (PITIA)
                const pitia = monthlyPI + monthlyTaxes + monthlyInsurance + monthlyHOA;
                vals.pitia.textContent = formatCurrency(pitia);

                // Calculate Down Payment
                const downPayment = price - loanAmount;

                // Calculate Annual Mortgage Payment = PITIA * 12
                const annualMortgagePayment = pitia * 12;

                // Calculate Annual HOA
                const annualHOA = hoa * 12;

                // Calculate Annual Repairs and Maintenance
                const annualRepair = repair * units;

                // Annual Rental Income = Gross Monthly Rental Income * 12
                const annualRentalIncome = rent * 12;

                // Vacancy Deduction = Annual Rental Income * vacancy Rate
                const vacancyRate = vacancy / 100;
                const vacancyDeduction = annualRentalIncome * vacancyRate;

                // Net Effective Rent = Annual Rental Income - Vacancy Deduction
                const netEffectiveRent = annualRentalIncome - vacancyDeduction;

                // Operating Expenses = (Taxes and Insurance + Annual HOA + Annual Repairs and Maint + Annual Utilities) + (Monthly Payment (P&I) * 12)
                const taxesAndInsurance = taxes + insurance;
                const operatingExpenses = (taxesAndInsurance + annualHOA + annualRepair + utilities) + (monthlyPI * 12);

                // Net Operating Income = Net Effective Rent - Operating Expenses
                const netOperatingIncome = netEffectiveRent - operatingExpenses;

                // Net Monthly Cashflow = Net Operating Income / 12
                const netMonthlyCashflow = netOperatingIncome / 12;
                vals.cashflow.textContent = formatCurrency(netMonthlyCashflow);

                // Calculate ROI = Net Operating Income / Purchase Price
                const roi = price > 0 ? (netOperatingIncome / price) * 100 : 0;
                vals.closingCost.textContent = roi.toFixed(2) + '%';

                // Calculate Total Closing Cost (used internally for cash needed to close and PDF)
                const totalClosingCost = originationFee + closingFees + thirdParty;

                // Calculate Cash Needed to Close
                const cashNeededToClose = downPayment + totalClosingCost;

                // Cap Rate = Net Operating Income / Purchase Price (in %)
                const capRate = price > 0 ? (netOperatingIncome / price) * 100 : 0;

                // Cash on Cash Return = Net Operating Income / Cash Needed to Close
                const cashOnCashReturn = cashNeededToClose > 0 ? (netOperatingIncome / cashNeededToClose) * 100 : 0;

                // DSCR = (Net Effective Rent / 12) / PITIA
                // This is monthly NOI divided by monthly debt service
                const monthlyNetEffectiveRent = netEffectiveRent / 12;
                let dscr = pitia > 0 ? monthlyNetEffectiveRent / pitia : 0;
                vals.dscr.textContent = dscr.toFixed(2);

                // DSCR Status Color
                if (dscr >= 1.25) vals.dscr.style.color = '#22c55e';
                else if (dscr >= 1.0) vals.dscr.style.color = '#eab308';
                else vals.dscr.style.color = '#ef4444';

                // Store all calculated values for PDF
                calculatedValues = {
                    pricePerUnit: price / units,
                    loanAmount: loanAmount,
                    downPayment: downPayment,
                    monthlyPI: monthlyPI,
                    pitia: pitia,
                    annualMortgagePayment: annualMortgagePayment,
                    originationFeeAmount: originationFee,
                    grossMonthlyRentalIncome: rent,
                    annualRentalIncome: annualRentalIncome,
                    vacancyDeduction: vacancyDeduction,
                    netEffectiveRent: netEffectiveRent,
                    taxesAndInsurance: taxesAndInsurance,
                    annualHOA: annualHOA,
                    annualRepair: annualRepair,
                    annualUtilities: utilities,
                    operatingExpenses: operatingExpenses,
                    netOperatingIncome: netOperatingIncome,
                    netMonthlyCashflow: netMonthlyCashflow,
                    capRate: capRate,
                    cashOnCashReturn: cashOnCashReturn,
                    dscr: dscr,
                    totalClosingCost: totalClosingCost,
                    cashNeededToClose: cashNeededToClose
                };
            }

            groups.forEach(group => {
                const range = group.querySelector('.dscr-range-input');
                const textInput = group.querySelector('input[type="text"]');
                const fill = group.querySelector('.slider-fill');
                const id = group.dataset.id;

                // Disable transition while dragging for instant response
                range.addEventListener('mousedown', () => {
                    fill.style.transition = 'none';
                });

                range.addEventListener('mouseup', () => {
                    fill.style.transition = 'width 0.3s ease';
                });

                range.addEventListener('touchstart', () => {
                    fill.style.transition = 'none';
                });

                range.addEventListener('touchend', () => {
                    fill.style.transition = 'width 0.3s ease';
                });

                range.addEventListener('input', (e) => updateUI(id, e.target.value, 'range'));
                textInput.addEventListener('input', (e) => {
                    updateUI(id, e.target.value, 'number');
                });
                textInput.addEventListener('blur', (e) => {
                    e.target.value = formatNumberWithCommas(e.target.value);
                });

                // Initialize
                updateUI(id, range.value, 'range');
            });

            // Custom Checkbox Toggle
            function toggleCheck() {
                readyCheck.checked = !readyCheck.checked;
                if (readyCheck.checked) {
                    checkIcon.textContent = '✓';
                    checkIcon.style.background = '#16a34a';
                    applyBtn.disabled = false;
                } else {
                    checkIcon.textContent = '';
                    checkIcon.style.background = '#9ca3af';
                    applyBtn.disabled = true;
                }
            }

            checkWrapper.addEventListener('click', toggleCheck);

            function formatCurrencyPDF(value) {
                return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function buildPdfHtml() {
                const getInputVal = (id) => parseNumericValue(app.querySelector(`[data-id="${id}"] input[type="text"]`).value);
                const cv = calculatedValues;

                const logoSrc = (typeof dscrPdfLogo !== 'undefined' && dscrPdfLogo) ? dscrPdfLogo : 'logo.png';

                const inputPrice = getInputVal('price');
                const inputLTV = getInputVal('ltv');
                const inputRate = getInputVal('rate');
                const inputTerm = getInputVal('term') || 30;
                const inputOrigination = getInputVal('origination');
                const inputClosingFees = getInputVal('closing-fees');
                const inputRent = getInputVal('rent');
                const inputVacancy = getInputVal('vacancy');
                const inputTaxes = getInputVal('taxes');
                const inputInsurance = getInputVal('insurance');
                const inputHOA = getInputVal('hoa');
                const inputRepair = getInputVal('repair');
                const inputUtilities = getInputVal('utilities');
                const inputThirdParty = getInputVal('third-party');

                return `
<style>
  #pdf-report * { margin: 0; padding: 0; box-sizing: border-box; }
  #pdf-report { font-family: "Segoe UI", Arial, sans-serif; background: #fff; width: 1000px; color: #333; display: flex; flex-direction: column; height: 1410px; max-height: 1410px; overflow: hidden; }
  #pdf-report .pdf-container { max-width: 1000px; margin: 0; background: #ffffff; padding: 0; flex: 1; display: flex; flex-direction: column; }
  #pdf-report .pdf-header { background: #3c4a5d; height: 110px; position: relative; width: 100%; flex-shrink: 0; }
  #pdf-report .pdf-logo-box { position: absolute; bottom: 0; left: 0; background: #ffffff; width: 260px; height: 95px; border-radius: 0 14px 0 0; display: flex; align-items: center; justify-content: center; padding: 8px 15px; }
  #pdf-report .pdf-logo-box img { max-height: 100%; }
  #pdf-report .pdf-contact { position: absolute; top: 32px; right: 40px; color: #ffffff; font-size: 15px; text-align: right; line-height: 1.6; }
  #pdf-report .pdf-contact-row { display: flex; justify-content: flex-end; gap: 10px; }
  #pdf-report .pdf-label { font-weight: 600; }
  #pdf-report .pdf-title { position: absolute; bottom: -20px; left: 65%; transform: translateX(-50%); background: #1e7a52; color: white; padding: 8px 40px; border-radius: 8px; font-weight: bold; font-size: 20px; z-index: 10; white-space: nowrap; }
  #pdf-report .pdf-main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 8px 15px; margin-top: 28px; flex: 1; }
  #pdf-report .pdf-column { display: flex; flex-direction: column; gap: 10px; }
  #pdf-report .pdf-card { background: #fafafa; border: 1px solid #d9c7a3; padding: 10px 14px; display: flex; flex-direction: column; }
  #pdf-report .pdf-section-gap { height: 6px; }
  #pdf-report .pdf-card h3 { margin: 0 0 6px; font-size: 18px; border-bottom: 1px solid #d6c7b2; padding-bottom: 4px; color: #333; }
  #pdf-report .pdf-sub-title { font-weight: bold; margin: 4px 0 2px; font-size: 17px; }
  #pdf-report .pdf-row { display: flex; justify-content: space-between; font-size: 16px; margin: 2px 0; }
  #pdf-report .pdf-positive span:last-child { color: #1a8f3c; font-weight: bold; }
  #pdf-report .pdf-negative span:last-child { color: #b33939; }
  #pdf-report .pdf-divider { border-top: 1px solid #d6c7b2; margin: 0 0; }
  #pdf-report .pdf-footer { text-align: center; font-size: 13px; padding: 6px 15px; color: #777; font-style: italic; flex-shrink: 0; margin-bottom: 20px; }
</style>
<div id="pdf-report">
<div class="pdf-container">
  <div class="pdf-header">
    <div class="pdf-logo-box">
      <img src="${logoSrc}" alt="Express Capital Financing" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.outerHTML='<h2 style=\\'color:#1e7a52; margin:0; font-size: 18px;\\'>Express Capital Financing</h2>'" />
    </div>
    <div class="pdf-contact">
      <div class="pdf-contact-row">
        <span class="pdf-label">Call:</span>
        <span>(718) 285-0806</span>
      </div>
      <div class="pdf-contact-row">
        <span class="pdf-label">Email:</span>
        <span>info@expresscapitalfinancing.com</span>
      </div>
    </div>
    <div class="pdf-title">DSCR Calculator Report</div>
  </div>

  <div class="pdf-main-grid">
    <div class="pdf-column">
      <div class="pdf-card">
        <div class="pdf-sub-title">Property Info</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Property Address</span><span></span></div>
        <div class="pdf-row"><span>Purchase or Refinance?</span><span>Purchase</span></div>
        <div class="pdf-row"><span>Purchase Price</span><span>${formatCurrencyPDF(inputPrice)}</span></div>
        <div class="pdf-row"><span>Number of Units</span><span>1</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Loan Information</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>LTV</span><span>${inputLTV}%</span></div>
        <div class="pdf-row"><span>Interest Rate</span><span>${inputRate}%</span></div>
        <div class="pdf-row"><span>Amortization (years)</span><span>${inputTerm}</span></div>
        <div class="pdf-row"><span>Origination Points</span><span>${inputOrigination}%</span></div>
        <div class="pdf-row"><span>Loan Closing Fees</span><span>${formatCurrencyPDF(inputClosingFees)}</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Property Income</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Total Rent</span><span>${formatCurrencyPDF(inputRent)}</span></div>
        <div class="pdf-row"><span>Vacancy Rate</span><span>${inputVacancy}%</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Property Expenses</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Property Taxes</span><span>${formatCurrencyPDF(inputTaxes)}</span></div>
        <div class="pdf-row"><span>Insurance</span><span>${formatCurrencyPDF(inputInsurance)}</span></div>
        <div class="pdf-row"><span>Monthly HOA</span><span>${formatCurrencyPDF(inputHOA)}</span></div>
        <div class="pdf-row"><span>Annual Repair and Maint (Per Unit)</span><span>${formatCurrencyPDF(inputRepair)}</span></div>
        <div class="pdf-row"><span>Annual Utilities</span><span>${formatCurrencyPDF(inputUtilities)}</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-row"><span>3rd Party Closing Cost (Title, Insurance)</span><span>${formatCurrencyPDF(inputThirdParty)}</span></div>
      </div>
    </div>

    <div class="pdf-column">
      <div class="pdf-card">
        <h3>Basic Info</h3>
        <div class="pdf-row"><span>Price Per Unit</span><span>${formatCurrencyPDF(cv.pricePerUnit)}</span></div>
        <div class="pdf-row"><span>Loan Amount</span><span>${formatCurrencyPDF(cv.loanAmount)}</span></div>
        <div class="pdf-row"><span>Down Payment</span><span>${formatCurrencyPDF(cv.downPayment)}</span></div>
      </div>

      <div class="pdf-card">
        <h3>Loan Information</h3>
        <div class="pdf-row pdf-positive"><span>Monthly Payments (P&I)</span><span>${formatCurrencyPDF(cv.monthlyPI)}</span></div>
        <div class="pdf-row"><span>PITIA</span><span>${formatCurrencyPDF(cv.pitia)}</span></div>
        <div class="pdf-row"><span>Annual Mortgage Payment</span><span>${formatCurrencyPDF(cv.annualMortgagePayment)}</span></div>
        <div class="pdf-row"><span>Origination Fee Amount</span><span>${formatCurrencyPDF(cv.originationFeeAmount)}</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Property Income</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Gross Monthly Rental Income</span><span>${formatCurrencyPDF(cv.grossMonthlyRentalIncome)}</span></div>
        <div class="pdf-row"><span>Annual Rental Income</span><span>${formatCurrencyPDF(cv.annualRentalIncome)}</span></div>
        <div class="pdf-row"><span>Vacancy Deduction</span><span>${formatCurrencyPDF(cv.vacancyDeduction)}</span></div>
        <div class="pdf-row"><span>Net Effective Rent</span><span>${formatCurrencyPDF(cv.netEffectiveRent)}</span></div>

        <div class="pdf-sub-title">Property Expenses</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Tax and Insurance</span><span>${formatCurrencyPDF(cv.taxesAndInsurance)}</span></div>
        <div class="pdf-row"><span>Annual HOA</span><span>${formatCurrencyPDF(cv.annualHOA)}</span></div>
        <div class="pdf-row"><span>Annual Repair and Maint</span><span>${formatCurrencyPDF(cv.annualRepair)}</span></div>
        <div class="pdf-row"><span>Annual Utilities</span><span>${formatCurrencyPDF(cv.annualUtilities)}</span></div>

        <div class="pdf-sub-title">Overview</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Operating Expenses</span><span>${formatCurrencyPDF(cv.operatingExpenses)}</span></div>
        <div class="pdf-row pdf-negative"><span>Net Operating Income</span><span>${formatCurrencyPDF(cv.netOperatingIncome)}</span></div>
        <div class="pdf-row"><span>Net Monthly Cashflow</span><span>${formatCurrencyPDF(cv.netMonthlyCashflow)}</span></div>
        <div class="pdf-row"><span>Cap Rate</span><span>${cv.capRate.toFixed(1)}%</span></div>
        <div class="pdf-row"><span>Cash On Cash Return</span><span>${cv.cashOnCashReturn.toFixed(1)}%</span></div>
        <div class="pdf-row"><span>DSCR*</span><span>${cv.dscr.toFixed(2)}</span></div>

        <div class="pdf-section-gap"></div>
        <div class="pdf-row"><span>Total Closing Cost</span><span>${formatCurrencyPDF(cv.totalClosingCost)}</span></div>
        <div class="pdf-row"><span>Cash Needed to Close</span><span>${formatCurrencyPDF(cv.cashNeededToClose)}</span></div>
      </div>
    </div>
  </div>

  <div class="pdf-footer">
    Disclaimer: This calculator provides estimates only. Consult professionals before making investment decisions.
  </div>
</div>
</div>`;
            }

    const leadModal = document.getElementById('dscr-lead-modal');
    const leadName = document.getElementById('dscr-lead-name');
    const leadEmail = document.getElementById('dscr-lead-email');
    const leadCancel = document.getElementById('dscr-lead-cancel');
    const leadSubmit = document.getElementById('dscr-lead-submit');
    const leadError = document.getElementById('dscr-lead-error');
    const leadLoading = document.getElementById('dscr-lead-loading');
    const pdfBtn = app.querySelector('#downloadPdfBtn') || app.querySelector('.cta.secondary');

    if (pdfBtn && leadModal) {
        pdfBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!calculatedValues || Object.keys(calculatedValues).length === 0) {
                alert('Please calculate values first before downloading PDF.');
                return;
            }
            leadModal.style.display = 'flex';
        });

        leadCancel.addEventListener('click', function() {
            leadModal.style.display = 'none';
            leadError.style.display = 'none';
            leadLoading.style.display = 'none';
            leadName.value = '';
            leadEmail.value = '';
        });

        leadSubmit.addEventListener('click', function(e) {
            e.preventDefault();
            const name = leadName.value.trim();
            const email = leadEmail.value.trim();
            
            if (!name || !email) {
                showError('Please provide both your name and email.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('Please enter a valid email address.');
                return;
            }

            leadError.style.display = 'none';
            leadLoading.style.display = 'block';
            leadSubmit.disabled = true;
            leadCancel.disabled = true;

            generateAndSendPdf(name, email);
        });

        function generateAndSendPdf(name, email) {
            try {
                const htmlContent = buildPdfHtml();
                const wrapper = document.createElement('div');
                wrapper.style.position = 'absolute';
                wrapper.style.left = '0';
                wrapper.style.top = '0';
                wrapper.style.width = '1000px';
                wrapper.style.zIndex = '-9999';
                wrapper.style.overflow = 'hidden';
                wrapper.style.background = '#ffffff';
                wrapper.innerHTML = htmlContent;
                document.body.appendChild(wrapper);

                const pdfContent = wrapper.querySelector('#pdf-report');
                const opt = {
                    margin: 0,
                    filename: 'DSCR_Calculator_Report.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, letterRendering: true, width: 1000, scrollX: 0, scrollY: 0 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(pdfContent).outputPdf('datauristring').then(function(pdfBase64) {
                    if (typeof dscrAjax !== 'undefined') {
                        const getInputValStr = (id) => {
                            const el = app.querySelector(`[data-id="${id}"] input[type="text"]`);
                            return el ? el.value : '0';
                        };
                        const formatCurrencyPDF = (value) => '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        
                        const cv = calculatedValues;
                        const fd = {
                            fullName: name,
                            email: email,
                            purchaseOr: 'Purchase',
                            purchasePrice: formatCurrencyPDF(getInputValStr('price')),
                            numberOf119: '1',
                            ltv120: getInputValStr('ltv') + '%',
                            number121: getInputValStr('rate') + '%',
                            number122: getInputValStr('term') || '30',
                            number123: getInputValStr('origination') + '%',
                            loanClosing: formatCurrencyPDF(getInputValStr('closing-fees')),
                            typeA153: formatCurrencyPDF(getInputValStr('rent')),
                            vacancyRate152: getInputValStr('vacancy') + '%',
                            propertyTaxes: formatCurrencyPDF(getInputValStr('taxes')),
                            insurance: formatCurrencyPDF(getInputValStr('insurance')),
                            monthlyHoa: formatCurrencyPDF(getInputValStr('hoa')),
                            annualRepairs: formatCurrencyPDF(getInputValStr('repair')),
                            annualUtilities: formatCurrencyPDF(getInputValStr('utilities')),
                            thirdParty: formatCurrencyPDF(getInputValStr('third-party')),
                            pricePer89: formatCurrencyPDF(cv.pricePerUnit),
                            loanamount: formatCurrencyPDF(cv.loanAmount),
                            downPayment: formatCurrencyPDF(cv.downPayment),
                            monthlyPayment92: formatCurrencyPDF(cv.monthlyPI),
                            typeA93: formatCurrencyPDF(cv.pitia),
                            typeA94: formatCurrencyPDF(cv.annualMortgagePayment),
                            typeA95: formatCurrencyPDF(cv.originationFeeAmount),
                            grossMonthly96: formatCurrencyPDF(cv.grossMonthlyRentalIncome),
                            typeA99: formatCurrencyPDF(cv.vacancyDeduction),
                            typeA99_2: formatCurrencyPDF(cv.netEffectiveRent),
                            taxesAnd100: formatCurrencyPDF(cv.taxesAndInsurance),
                            typeA101: formatCurrencyPDF(cv.annualHOA),
                            typeA102: formatCurrencyPDF(cv.annualRepair),
                            typeA103: formatCurrencyPDF(cv.annualUtilities),
                            operatingExpenses104: formatCurrencyPDF(cv.operatingExpenses),
                            typeA105: formatCurrencyPDF(cv.netOperatingIncome),
                            typeA106: formatCurrencyPDF(cv.netMonthlyCashflow),
                            typeA107: cv.capRate.toFixed(2) + '%',
                            typeA108: cv.cashOnCashReturn.toFixed(2) + '%',
                            typeA109: cv.dscr.toFixed(2),
                            typeA110: formatCurrencyPDF(cv.totalClosingCost),
                            typeA111: formatCurrencyPDF(cv.cashNeededToClose)
                        };

                        const formData = new URLSearchParams();
                        formData.append('action', 'dscr_submit_lead');
                        formData.append('nonce', dscrAjax.nonce);
                        formData.append('name', name);
                        formData.append('email', email);
                        formData.append('form_data', JSON.stringify(fd)); // Placed before PDF to dodge server truncation limits
                        formData.append('pdf_data', pdfBase64);

                        fetch(dscrAjax.url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        })
                        .then(res => res.json())
                        .then(data => {
                            console.log("Server response:", data);
                            finalizeDownload(opt, pdfContent, wrapper);
                        })
                        .catch(err => {
                            console.error("Ajax Error:", err);
                            finalizeDownload(opt, pdfContent, wrapper);
                        });
                    } else {
                        finalizeDownload(opt, pdfContent, wrapper);
                    }
                });
            } catch (err) {
                showError('Error preparing PDF: ' + err.message);
            }
        }

        function finalizeDownload(opt, pdfContent, wrapper) {
            html2pdf().set(opt).from(pdfContent).save().then(function () {
                document.body.removeChild(wrapper);
                hideModal();
            }).catch(function (err) {
                document.body.removeChild(wrapper);
                showError('Error downloading PDF: ' + err.message);
            });
        }

        function hideModal() {
            if(leadModal) {
                leadModal.style.display = 'none';
                leadLoading.style.display = 'none';
                leadSubmit.disabled = false;
                leadCancel.disabled = false;
                leadName.value = '';
                leadEmail.value = '';
            }
        }

        function showError(msg) {
            leadError.style.display = 'block';
            leadError.textContent = msg;
            leadSubmit.disabled = false;
            leadCancel.disabled = false;
            leadLoading.style.display = 'none';
        }

    } else if (pdfBtn) {
        pdfBtn.addEventListener('click', function (e) {
            e.preventDefault();
            try {
                // Inline default fallback if modal is miraculously missing
                const htmlContent = buildPdfHtml();
                const wrapper = document.createElement('div');
                wrapper.style.position = 'absolute';
                wrapper.style.left = '0'; wrapper.style.top = '0'; wrapper.style.width = '1000px';
                wrapper.style.zIndex = '-9999'; wrapper.style.overflow = 'hidden'; wrapper.style.background = '#ffffff';
                wrapper.innerHTML = htmlContent;
                document.body.appendChild(wrapper);
                const pdfContent = wrapper.querySelector('#pdf-report');
                const opt = { margin: 0, filename: 'DSCR_Calculator_Report.pdf', image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true, letterRendering: true, width: 1000, scrollX: 0, scrollY: 0 }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } };
                html2pdf().set(opt).from(pdfContent).save().then(() => document.body.removeChild(wrapper));
            } catch (err) { alert(err.message); }
        });
    }

            // Final init
            calculate();
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('dscr_calculator', 'dscr_calc_shortcode');
