<?php
namespace Membership\Login;
trait WP_Membership_Login_Settings {

    protected array $wp_membership_settings_defaults;
    protected string $upload_table = 'membership_login_security_uploads';
    protected string $membership_table = 'membership_login_security';
    protected string $document_group_table = 'membership_document_groups';
    protected string $upload_min_role = 'manage_options';
    protected string $download_min_role = 'read';
    protected string $settings_min_role = 'manage_options';
    protected string $plugin_min_role = 'manage_options';
    protected bool $bootstrap_css_aktiv = false;
    protected bool $bootstrap_js_aktiv = false;
    protected bool $show_dashboard_downloads = false;
    protected string $mimeTypes = 'pdf,svg,rar,zip,jpg,jpeg,png,gif,doc,docx';
    protected int $max_file_size = 10;
    protected int $error_page = 0;
    protected int $after_logout_page = 0;
    protected int $check_mime_type_active = 1;
    protected int $check_upload_size_active = 1;

    protected string $membership_template_datei = 'wp-membership.php';
    protected string $default_redirect_link = 'WP Member Security';

    protected function get_wp_membership_defaults($args = '', $cap = NULL):array
    {
        $this->wp_membership_settings_defaults = [
            'upload_settings' => [
                'upload_min_role' => $this->upload_min_role,
                'download_min_role' =>  $this->download_min_role,
                'settings_min_role' => $this->settings_min_role,
                'plugin_min_role' => $this->plugin_min_role,
                'bootstrap_css_aktiv' => $this->bootstrap_css_aktiv,
                'bootstrap_js_aktiv' => $this->bootstrap_js_aktiv,
                'show_dashboard_downloads' => $this->show_dashboard_downloads,
                'mime_types' => $this->mimeTypes,
                'max_file_size' => $this->max_file_size,
                'error_page' => $this->error_page,
                'after_logout_page' => $this->after_logout_page,
                'check_mime_type_active' => $this->check_mime_type_active,
                'check_upload_size_active' => $this->check_upload_size_active
            ],
            'select_user_role' => [
                "0" => [
                    'capabilities' => 'subscriber',
                    'value' => 'read',
                    'name' => __('Subscriber', 'wp-memberchip-login')
                ],
                "1" => [
                    'capabilities' => 'contributor',
                    'value' => 'edit_posts',
                    'name' => __('Contributor', 'wp-memberchip-login')
                ],
                "2" => [
                    'capabilities' => 'subscriber',
                    'value' => 'publish_posts',
                    'name' => __('Author', 'wp-memberchip-login')
                ],
                "3" => [
                    'capabilities' => 'editor',
                    'value' => 'publish_pages',
                    'name' => __('Editor', 'wp-memberchip-login')
                ],
                "4" => [
                    'capabilities' => 'administrator',
                    'value' => 'manage_options',
                    'name' => __('Administrator', 'wp-memberchip-login')
                ],
            ],
            'defaults_document_groups' => [
                'designation' => __('Documents' ,'wp-memberchip-login'),
                'description' => __('Default document group', 'wp-memberchip-login'),
                'capabilities' => 'read',
                'active' => 1
            ],
            'default_membership' => [
                'document_group' => 1,
                'designation' => __('default membership', 'wp-memberchip-login'),
                'capabilities' => 'read',
                'active' => 1,
                'self_active' => 1,
                'login_link' => 'wp-login.php',
                'redirect_link' => $this->default_redirect_link,
                'page_template' => $this->membership_template_datei,
                'note' => __('standard membership with page template', 'wp-memberchip-login')
            ]
        ];

        if($args) {
            if($cap){
                $return = [];
                foreach ($this->wp_membership_settings_defaults[$args] as $tmp) {
                    if($tmp['value'] == $cap) {
                        $return = $tmp;
                    }
                }
                return $return;
            }
            return  $this->wp_membership_settings_defaults[$args];
        }

        return $this->wp_membership_settings_defaults;
    }

    protected function twig_language()
    {
        $lang = [
            __('Active', 'wp-memberchip-login'),
            __('active', 'wp-memberchip-login'),
            __('Edit', 'wp-memberchip-login'),
            __('edit', 'wp-memberchip-login'),
            __('Delete', 'wp-memberchip-login'),
            __('delete', 'wp-memberchip-login'),
            __('Settings', 'wp-memberchip-login'),
            __('settings', 'wp-memberchip-login'),
            __('Overview', 'wp-memberchip-login'),
            __('back', 'wp-memberchip-login'),
            __('no data', 'wp-memberchip-login'),
            __('from', 'wp-memberchip-login'),
            __('to', 'wp-memberchip-login'),
            __('Save', 'wp-memberchip-login'),
            __('Cancel', 'wp-memberchip-login'),
            __('at', 'wp-memberchip-login'),
            __('Minimum requirement for plugin usage', 'wp-memberchip-login'),
            __('Clock', 'wp-memberchip-login'),
            __('Help', 'wp-memberchip-login'),
            __('Plugin visibility', 'wp-memberchip-login'),
            __('and' ,'wp-memberchip-login'),
            __('Add membership' ,'wp-memberchip-login'),
            __('Plugin settings', 'wp-memberchip-login'),
            __('Members', 'wp-memberchip-login'),
            __('Bootstrap CSS active', 'wp-memberchip-login'),
            __('Bootstrap JS active', 'wp-memberchip-login'),
            __('Show downloads in dashboard', 'wp-memberchip-login'),
            __('User Role Downloads (Dashboard View)', 'wp-memberchip-login'),
            __('Separate MimeTypes with comma or semicolon. (e.g. pdf, jpg, png)', 'wp-memberchip-login'),
            __('Allowed documents', 'wp-memberchip-login'),
            __('Maximum file size (MB)', 'wp-memberchip-login'),
            __('File', 'wp-memberchip-login'),
            __('Size', 'wp-memberchip-login'),
            __('Type', 'wp-memberchip-login'),
            __('Downloads', 'wp-memberchip-login'),
            __('Created', 'wp-memberchip-login'),
            __('Download', 'wp-memberchip-login'),
            __('Designation', 'wp-memberchip-login'),
            __('designation', 'wp-memberchip-login'),
            __('Document group', 'wp-memberchip-login'),
            __('Authorization', 'wp-memberchip-login'),
            __('Redirection', 'wp-memberchip-login'),
            __('Page', 'wp-memberchip-login'),
            __('Edit Membership', 'wp-memberchip-login'),
            __('Create membership', 'wp-memberchip-login'),
            __('Create', 'wp-memberchip-login'),
            __('Group', 'wp-memberchip-login'),
            __('Title', 'wp-memberchip-login'),
            __('Upload', 'wp-memberchip-login'),
            __('Upload documents', 'wp-memberchip-login'),
            __('Cancel upload', 'wp-memberchip-login'),
            __('Uploaded documents', 'wp-memberchip-login'),
            __('Member Documents', 'wp-memberchip-login'),
            __('Groups', 'wp-memberchip-login'),
            __('Add group', 'wp-memberchip-login'),
            __('Error page (no authorization)', 'wp-memberchip-login'),
            __('Page after logout', 'wp-memberchip-login'),
            __('Check MimeTypes', 'wp-memberchip-login'),
            __('Check upload size', 'wp-memberchip-login'),
            __('Logout Url', 'wp-memberchip-login'),
            __('For the logout link there is a shortcode with different options.<br>Shortcode:<code>[mbl-logout text=logout class="your-css-class"]</code><br>text=link text', 'wp-memberchip-login'),

        ];
    }

    protected function js_language():array
    {
        return [
            'checkbox_delete_label' => __('Delete all imported posts?', 'wp-memberchip-login'),
            'Cancel' => __('Cancel', 'wp-memberchip-login'),
            'delete_title' => __('Really delete membership?', 'wp-memberchip-login'),
            'delete_subtitle' => __('The deletion cannot be undone.', 'wp-memberchip-login'),
            'delete_btn_txt' => __('Delete membership', 'wp-memberchip-login'),
            'delete_file_title' => __('Really delete file?', 'wp-memberchip-login'),
            'delete_file_btn' => __('Delete file' ,'wp-memberchip-login'),
            'delete' => __('Delete', 'wp-memberchip-login'),
            'clock' => __('Clock', 'wp-memberchip-login'),
            'delete_group_title' => __('Delete group really?', 'wp-memberchip-login'),
            'delete_group_btn' => __('Delete group', 'wp-memberchip-login'),
            'delete_group_subtitle' => __('All documents in this group will be moved to the default group.','wp-memberchip-login')
        ];

    }
}