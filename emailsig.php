<?php
/*
Plugin Name: Emailsig
Description: A plugin to manage email signatures.
Version: 1.1.1
Author: bodfather
*/

// Call the updater
require_once 'updater.php';
add_action('admin_init', 'your_plugin_updater');

function emailsig_admin_styles() {
    wp_enqueue_style('emailsig-admin', plugins_url('/admin/css/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'emailsig_admin_styles');

function emailsig_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'emailsig_signatures';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        website varchar(255) NOT NULL,
        logo_url varchar(255) NOT NULL,
        mobile varchar(50) NOT NULL,
        mobile_link varchar(255) NOT NULL,
        address varchar(255) NOT NULL,
        address_link varchar(255) NOT NULL,
        description varchar(255) NOT NULL,
        signature_text text NOT NULL,
        banner1 varchar(255) NOT NULL,
        banner1_link varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('plugins_loaded', 'emailsig_create_table');

// Add a menu item under the Tools menu
function emailsig_add_menu_page() {
    add_management_page(
        'Emailsig Settings',
        'Emailsig',
        'manage_options',
        'emailsig-settings',
        'emailsig_settings_page'
    );
}
add_action('admin_menu', 'emailsig_add_menu_page');

// Display the settings page
function emailsig_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'emailsig_signatures';
    $signature_to_edit = null;

    // Handle form submission for saving a new or edited signature
    if (isset($_POST['save_signature'])) {
        $full_name = sanitize_text_field($_POST['full_name']);
        $email_address = sanitize_email($_POST['email']);
        $website = !empty($_POST['website']) ? esc_url($_POST['website']) : '';
        $logo_url = esc_url($_POST['logo_url']);
        $mobile_number = sanitize_text_field($_POST['mobile']);
        $description = sanitize_text_field($_POST['description']);
        $signature_html = stripslashes($_POST['signature_template']); // Remove magic quotes if any
        $mobile_link = !empty($_POST['mobile_link']) ? esc_url($_POST['mobile_link']) : '';
        $banner1 = !empty($_POST['banner1']) ? esc_url($_POST['banner1']) : '';
        $banner1_link = !empty($_POST['banner1_link']) ? esc_url($_POST['banner1_link']) : '';
        $address = !empty($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $address_link = !empty($_POST['address_link']) ? esc_url($_POST['address_link']) : '';

        // Replace variables in the signature HTML
        $signature_text = str_replace(
            ['{full_name}', '{email}', '{website}', '{logo_url}', '{mobile}', '{mobile_link}', '{banner1}', '{banner1_link}', '{address}', '{address_link}'],
            [$full_name, $email_address, $website, $logo_url, $mobile_number, $mobile_link, $banner1, $banner1_link, $address, $address_link],
            $signature_html
        );

        if (isset($_POST['edit_signature_id']) && !empty($_POST['edit_signature_id'])) {
            // Editing an existing signature
            $edit_id = intval($_POST['edit_signature_id']);
            $wpdb->update(
                $table_name,
                array(
                    'full_name' => $full_name,
                    'email' => $email_address,
                    'website' => $website,
                    'logo_url' => $logo_url,
                    'mobile' => $mobile_number,
                    'description' => $description,
                    'mobile_link'=> $mobile_link,
                    'banner1' => $banner1,
                    'banner1_link' => $banner1_link,
                    'address' => $address,
                    'address_link' => $address_link,
                    'signature_text' => $signature_text
                ),
                array('id' => $edit_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s'),
                array('%d')
            );
            echo '<div class="updated"><p>Signature updated!</p></div>';
        } else {
            // Creating a new signature
            $wpdb->insert(
                $table_name,
                array(
                    'description' => $description,
                    'full_name' => $full_name,
                    'email' => $email_address,
                    'website' => $website,
                    'logo_url' => $logo_url,
                    'mobile' => $mobile_number,
                    'mobile_link'=> $mobile_link,
                    'banner1' => $banner1,
                    'banner1_link' => $banner1_link,
                    'address' => $address,
                    'address_link' => $address_link,
                    'signature_text' => $signature_text
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s')
            );
            echo '<div class="updated"><p>Signature saved!</p></div>';
        }
    }

    // Handle form submission for deleting a signature
    if (isset($_POST['delete_signature_id'])) {
        $delete_id = intval($_POST['delete_signature_id']);
        $wpdb->delete(
            $table_name,
            array('id' => $delete_id),
            array('%d')
        );
        echo '<div class="updated"><p>Signature deleted!</p></div>';
    }

    // Handle form submission for editing a signature (loading data into the form)
    if (isset($_POST['edit_signature_id'])) {
        $edit_id = intval($_POST['edit_signature_id']);
        $signature_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    }

    // Fetch saved signatures
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    // Render the form and saved signatures table
    ?>
    <div class="wrap">
        <h1>Emailsig Settings</h1>
        <form method="post" action="">
            <div style="display: flex;">
                <div style="flex: 1;">
                    <table class="form-table emailsig-settings">
                        <tr>
                            <th scope="row">Description</th>
                            <td colspan="2"><input type="text" name="description" id="description" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->description) : ''; ?>" class="regular-text"></td>
                            </tr>
                        <tr>
                            <th scope="row">Full Name</th>
                            <td colspan="2"><input type="text" name="full_name" id="full_name" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->full_name) : ''; ?>" class="regular-text" required></td>
                            </tr>
                        <tr>
                            <th scope="row">Email Address</th>
                            <td colspan="2"><input type="email" name="email" id="email" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->email) : ''; ?>" class="regular-text" required></td>
                            </tr>
                        <tr>
                            <th scope="row">Website</th>
                            <td colspan="2"><input type="url" name="website" id="website" value="<?php echo isset($signature_to_edit) ? esc_url($signature_to_edit->website) : ''; ?>" class="regular-text"></td>
                            </tr>
                        <tr>
                            <th scope="row">Logo URL</th>
                            <td colspan="2"><input type="url" name="logo_url" id="logo_url" value="<?php echo isset($signature_to_edit) ? esc_url($signature_to_edit->logo_url) : ''; ?>" class="regular-text"></td>
                            </tr>
                        <tr class="multi-input">
                            <th scope="row" rowspan="2">Mobile</th>
                            <td class="es_subhead">Number:</td>
                            <td><input type="text" name="mobile" id="mobile" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->mobile) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="multi-input-last">
                            <td class="es_subhead">Link:</td>
                            <td><input type="text" name="mobile_link" id="mobile_link" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->mobile_link) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="multi-input">
                            <th scope="row" rowspan="2">Address</th>
                            <td class="es_subhead">Physical:</td>
                            <td><input type="text" name="address" id="address" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->address) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="multi-input-last">
                            <td class="es_subhead">Link:</td>
                            <td><input type="text" name="address_link" id="address_link" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->address_link) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="multi-input">
                            <th scope="row" rowspan="2">Banner 1</th>
                            <td class="es_subhead">Image Location:</td>
                            <td><input type="text" name="banner1" id="banner1" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->banner1) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="multi-input-last">
                            <td class="es_subhead">Link:</td>
                            <td><input type="text" name="banner1_link" id="banner1_link" value="<?php echo isset($signature_to_edit) ? esc_attr($signature_to_edit->banner1_link) : ''; ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="save_signature" id="save_signature" class="button button-primary" value="<?php echo isset($signature_to_edit) ? 'Update Signature' : 'Generate and Save Signature'; ?>">
                        <?php if (isset($signature_to_edit)): ?>
                            <input type="hidden" name="edit_signature_id" value="<?php echo esc_attr($signature_to_edit->id); ?>" />
                        <?php endif; ?>
                    </p>
                </div>
                <?php $email_layout = file_get_contents(plugin_dir_path(__FILE__) . 'includes/email_layout_div.html');
                ?>
                <div style="flex: 1; margin-left: 20px;">
                    <h3>Signature Preview:</h3>
                    <p><strong>VARIABLES: </strong>
                    {full_name} , {email} , {website} , {logo_url} , {mobile} , {mobile_link} , {address} , {address_link} , {banner1} , {banner1_link}
                    </p>
                    <textarea id="signature_template" name="signature_template" style="width: 100%; height: 200px; margin-bottom: 10px;"><?php echo esc_textarea($email_layout); ?></textarea>
                    <div id="signature_preview" style="background-color: #ffffff;padding:5px;"><?php echo $email_layout; ?></div>
                </div>
                
            </div>
        </form>

        <h2>Saved Signatures</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Website</th>
                    <th>Logo URL</th>
                    <th>Mobile Number</th>
                    <th>Banner</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->description); ?></td>
                        <td><?php echo esc_html($row->full_name); ?></td>
                        <td><?php echo esc_html($row->email); ?></td>
                        <td><?php echo !empty($row->website) ? esc_url($row->website) : 'N/A'; ?></td>
                        <td><?php echo esc_url($row->logo_url); ?></td>
                        <td><?php echo esc_html($row->mobile); ?></td>
                        <td><img width="100px" src="<?php echo esc_html($row->banner1) ?>"></td>
                        <td>
                       
                            <input type="hidden" name="copy_html_button" value="<?php echo esc_attr($row->id); ?>" />
                            <button class="copy-html-button" style="width:80px;" data-signature="<?php echo esc_attr($row->signature_text); ?>">Copy HTML</button>
                        
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="edit_signature_id" value="<?php echo esc_attr($row->id); ?>" />
                                <input type="submit" value="Edit" class="button button-primary">
                            </form>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="delete_signature_id" value="<?php echo esc_attr($row->id); ?>">
                                <button type="submit" class="button-link-delete" onclick="return confirm('Are you sure you want to delete this signature?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    // Update the signature preview in real-time with default values if fields are empty
    function updateSignaturePreview() {
        const fullName = document.getElementById('full_name').value || "Display Name - Full Name";
        const email = document.getElementById('email').value || "email@address.com";
        const website = document.getElementById('website').value || "website.com";
        const logoUrl = document.getElementById('logo_url').value || "https://bodmerch.com/logo-placeholder";
        const mobile = document.getElementById('mobile').value || "0821234567";
        const mobile_link = document.getElementById('mobile_link').value || "https://wa.me/27821234567";
        const address = document.getElementById('address').value || "1 Second Street, Town, 1234";
        const address_link = document.getElementById('address_link').value || "https://www.google.com/maps/@-34.0721664,18.8547072,14z?entry=ttu";
        const banner1 = document.getElementById('banner1').value || "https://bodmerch.com/banner1-placeholder";
        const banner1_link = document.getElementById('banner1_link').value || "https://bodmerch.com/banner1link-placeholder";
        
        let signatureHtml = document.getElementById('signature_template').value;

        // Replace placeholders with user inputs or defaults
        signatureHtml = signatureHtml.replace(/{full_name}/g, fullName)
                                     .replace(/{email}/g, email)
                                     .replace(/{website}/g, website)
                                     .replace(/{logo_url}/g, logoUrl)
                                     .replace(/{mobile}/g, mobile)
                                     .replace(/{mobile_link}/g, mobile_link)
                                     .replace(/{address}/g, address)
                                     .replace(/{address_link}/g, address_link)
                                     .replace(/{banner1}/g, banner1)
                                     .replace(/{banner1_link}/g, banner1_link);

        // Update the preview area
        document.getElementById('signature_preview').innerHTML = signatureHtml;
    }

    // Listen to changes in the input fields and textarea
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="url"], #signature_template').forEach(function(input) {
        input.addEventListener('input', updateSignaturePreview);
    });

    // Initialize the preview on page load
    document.addEventListener('DOMContentLoaded', updateSignaturePreview);

    // Handle HTML copy
document.querySelectorAll('.copy-html-button').forEach(button => {
    button.addEventListener('click', function() {
        const signatureHtml = this.dataset.signature;
        const originalText = this.innerText; // Store the original button text
        const buttonElement = this; // Reference to the clicked button

        navigator.clipboard.writeText(signatureHtml).then(() => {
            // Change the button text to "Copied"
            buttonElement.innerText = 'Copied';
            buttonElement.disabled = true;

            // Revert the button text back to "Copy HTML" after 3 seconds
            setTimeout(() => {
                buttonElement.innerText = originalText;
                buttonElement.disabled = false;
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    });
});

 
    </script>

    <?php
}
