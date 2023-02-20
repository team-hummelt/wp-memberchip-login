<?php

namespace Membership\Login;

use stdClass;
use Wp_Memberchip_Login;
use WP_Query;

class WP_Membership_Login_DB_Handle
{

    private static $instance;

    use WP_Membership_Login_Settings;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The current version of the DB-Version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $db_version The current version of the database Version.
     */
    protected string $db_version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Memberchip_Login $main The main class.
     */
    private Wp_Memberchip_Login $main;

    /**
     * @return static
     */
    public static function instance(string $basename, string $db_version, Wp_Memberchip_Login $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $db_version, $main);
        }
        return self::$instance;
    }

    public function __construct(string $basename, string $db_version, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->db_version = $db_version;
        $this->basename = $basename;
    }

    /**
     * @param string $args
     * @param bool $fetchMethod
     * @return object
     */
    public function getMembershipsDocumentsImportsByArgs(string $args = '', bool $fetchMethod = true): object
    {
        global $wpdb;
        $return = new stdClass();
        $return->status = false;
        $return->count = 0;
        $fetchMethod ? $fetch = 'get_results' : $fetch = 'get_row';
        $table = $wpdb->prefix . $this->upload_table;
        $tableGroup = $wpdb->prefix . $this->document_group_table;
        $result = $wpdb->$fetch("SELECT d.*, g.designation as groupBezeichnung, g.description as groupBeschreibung, g.active as groupActive 
                                  FROM $table d 
                                  LEFT JOIN $tableGroup g ON d.group = g.id
                                  $args");
        if (!$result) {
            return $return;
        }
        $fetchMethod ? $count = count($result) : $count = 1;
        $return->count = $count;
        $return->status = true;
        $return->record = $result;
        return $return;
    }

    public function setMembershipDocumentsImport($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $table = $wpdb->prefix . $this->upload_table;
        $wpdb->insert(
            $table,
            array(
                'file_id' => $record->file_id,
                'group' => $record->group,
                'original' => $record->orginalName,
                'filename' => $record->filename,
                'size' => $record->size,
                'mime_type' => $record->type,
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
        if (!$wpdb->insert_id) {
            $return->status = false;
            $return->msg = __('Document could not be saved.', 'wp-memberchip-login');
            $return->title = __('Document not saved', 'wp-memberchip-login');
            return $return;
        }
        $return->status = true;
        $return->id = $wpdb->insert_id;
        $return->msg = __('Document was saved successfully.', 'wp-memberchip-login');
        $return->title = __('Document saved', 'wp-memberchip-login');

        return $return;
    }

    public function updateMembershipDocumentImport($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . $this->upload_table;
        $wpdb->update(
            $table,
            array(
                'group' => $record->group,
                'active' => $record->active,
                'beschreibung' => $record->beschreibung,
                'name' => $record->name,
            ),
            array('id' => $record->id),
            array('%d', '%d', '%s', '%s'),
            array('%d')
        );

        if ($wpdb->last_error !== '') {
            $return->status = false;
            $return->msg = __('Document could not be saved.', 'wp-memberchip-login');
            $return->title = __('Document not saved', 'wp-memberchip-login');
            return $return;
        }

        $return->status = true;
        $return->msg = __('Document was saved successfully.', 'wp-memberchip-login');
        $return->title = __('Document saved', 'wp-memberchip-login');

        return $return;
    }

    public function updateMembershipDocumentCountDownload($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . $this->upload_table;
        $wpdb->update(
            $table,
            array(
                'downloads' => $record->downloads
            ),
            array('id' => $record->id),
            array('%d'),
            array('%d')
        );

        if ($wpdb->last_error !== '') {
            $return->status = false;
            $return->msg = __('Document could not be saved.', 'wp-memberchip-login');
            $return->title = __('Document not saved', 'wp-memberchip-login');
            return $return;
        }

        $return->status = true;
        $return->msg = __('Document was saved successfully.', 'wp-memberchip-login');
        $return->title = __('Document saved', 'wp-memberchip-login');

        return $return;
    }

    public function updateMembershipDocumentGroup($group, $id): object
    {
        $return = new stdClass();
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . $this->upload_table;
        $wpdb->update(
            $table,
            array(
                'group' => $group
            ),
            array('id' => $id),
            array('%d'),
            array('%d')
        );

        if ($wpdb->last_error !== '') {
            $return->status = false;
            $return->msg = __('Group could not be saved.', 'wp-memberchip-login');
            $return->title = __('Error while saving!', 'wp-memberchip-login');
            return $return;
        }

        $return->status = true;
        $return->msg = __('Group was created successfully.', 'wp-memberchip-login');
        $return->title = __('Group created!', 'wp-memberchip-login');

        return $return;
    }

    public function deleteMemberDocumentImport($id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->upload_table;
        $wpdb->delete(
            $table,
            array(
                'id' => $id
            ),
            array('%d')
        );
    }

    /**
     * @param string $args
     * @param bool $fetchMethod
     * @return object
     */
    public function getMembershipDocumentGroupsByArgs(string $args = '', bool $fetchMethod = true): object
    {
        global $wpdb;
        $return = new stdClass();
        $return->status = false;
        $return->count = 0;
        $fetchMethod ? $fetch = 'get_results' : $fetch = 'get_row';
        $table = $wpdb->prefix . $this->document_group_table;
        $result = $wpdb->$fetch("SELECT g.* FROM $table g $args");
        if (!$result) {
            return $return;
        }
        $fetchMethod ? $count = count($result) : $count = 1;
        $return->count = $count;
        $return->status = true;
        $return->record = $result;
        return $return;
    }

    public function setMembershipDocumentGroups($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $table = $wpdb->prefix . $this->document_group_table;
        $wpdb->insert(
            $table,
            array(
                'designation' => $record->designation,
                'description' => $record->description,
                'active' => $record->active,
            ),
            array('%s', '%s', '%d')
        );
        if (!$wpdb->insert_id) {
            $return->status = false;
            $return->msg = __('Group could not be saved.', 'wp-memberchip-login');
            $return->title = __('Error while saving!', 'wp-memberchip-login');
            return $return;
        }
        $return->status = true;
        $return->id = $wpdb->insert_id;
        $return->msg = __('Group was created successfully.', 'wp-memberchip-login');
        $return->title = __('Group created!', 'wp-memberchip-login');

        return $return;
    }

    public function updateMembershipDocumentGroups($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . $this->document_group_table;
        $wpdb->update(
            $table,
            array(
                'designation' => $record->designation,
                'description' => $record->description,
                'active' => $record->active,
            ),
            array('id' => $record->id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        if ($wpdb->last_error !== '') {
            $return->status = false;
            $return->msg = __('Changes could not be saved.', 'wp-memberchip-login');
            $return->title = __('Changes not saved', 'wp-memberchip-login');
            return $return;
        }

        $return->status = true;
        $return->msg = __('Changes were saved successfully.', 'wp-memberchip-login');
        $return->title = __('Changes saved', 'wp-memberchip-login');

        return $return;
    }

    public function deleteMembershipDocumentGroups($id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->document_group_table;
        $wpdb->delete(
            $table,
            array(
                'id' => $id
            ),
            array('%d')
        );
    }

    /**
     * @param string $args
     * @param bool $fetchMethod
     * @return object
     */
    public function getWPMembershipLoginByArgs(string $args = '', bool $fetchMethod = true): object
    {
        global $wpdb;
        $return = new stdClass();
        $return->status = false;
        $return->count = 0;
        $fetchMethod ? $fetch = 'get_results' : $fetch = 'get_row';
        $table = $wpdb->prefix . $this->membership_table;
        $groupTable = $wpdb->prefix . $this->document_group_table;
        $result = $wpdb->$fetch("SELECT m.*, g.id as groupID, g.designation as groupDesignation, g.description as groupDescription,
                                 g.capabilities as groupCapabilities, g.active as groupActive   
        FROM $table m
        LEFT JOIN $groupTable g ON m.document_group = g.id
        $args");
        if (!$result) {
            return $return;
        }
        $fetchMethod ? $count = count($result) : $count = 1;
        $return->count = $count;
        $return->status = true;
        $return->record = $result;
        return $return;
    }

    public function setWPMembershipLogin($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $table = $wpdb->prefix . $this->membership_table;
        $wpdb->insert(
            $table,
            array(
                'document_group' => $record->document_group,
                'page_id' => $record->page_id,
                'designation' => $record->designation,
                'capabilities' => $record->capabilities,
                'active' => $record->active,
                'login_link' => $record->login_link,
                'redirect_link' => $record->redirect_link,
                'note' => $record->note,
                'self_active' => $record->self_active,
                'shortcode' => $record->shortcode
            ),
            array('%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s')
        );
        if (!$wpdb->insert_id) {
            $return->status = false;
            $return->msg = __('Settings could not be saved.', 'wp-memberchip-login');
            $return->title = __('Error while saving!', 'wp-memberchip-login');
            return $return;
        }
        $return->status = true;
        $return->id = $wpdb->insert_id;
        $return->msg = __('Settings were saved successfully.', 'wp-memberchip-login');
        $return->title = __('Settings saved', 'wp-memberchip-login');

        return $return;
    }

    public function updatetWPMembershipLogin($record): object
    {
        $return = new stdClass();
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . $this->membership_table;
        $wpdb->update(
            $table,
            array(
                'document_group' => $record->document_group,
                'page_id' => $record->page_id,
                'designation' => $record->designation,
                'capabilities' => $record->capabilities,
                'active' => $record->active,
                'login_link' => $record->login_link,
                'redirect_link' => $record->redirect_link,
                'note' => $record->note,
                'self_active' => $record->self_active,
            ),
            array('id' => $record->id),
            array('%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d'),
            array('%d')
        );

        if ($wpdb->last_error !== '') {
            $return->status = false;
            $return->msg = __('Changes could not be saved.', 'wp-memberchip-login');
            $return->title = __('Changes not saved', 'wp-memberchip-login');
            return $return;
        }

        $return->status = true;
        $return->msg = __('Changes were saved successfully.', 'wp-memberchip-login');
        $return->title = __('Changes saved', 'wp-memberchip-login');

        return $return;
    }

    public function updateMembershipGroup($group, $id): object
    {
        $return = new stdClass();
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . $this->membership_table;
        $wpdb->update(
            $table,
            array(
                'document_group' => $group
            ),
            array('id' => $id),
            array('%d'),
            array('%d')
        );

        if ($wpdb->last_error !== '') {
            $return->status = false;
            $return->msg = __('Changes could not be saved.', 'wp-memberchip-login');
            $return->title = __('Changes not saved', 'wp-memberchip-login');
            return $return;
        }

        $return->status = true;
        $return->msg = __('Changes were saved successfully.', 'wp-memberchip-login');
        $return->title = __('Changes saved', 'wp-memberchip-login');

        return $return;
    }

    public function deleteWPMembershipLogin($id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->membership_table;
        $wpdb->delete(
            $table,
            array(
                'id' => $id
            ),
            array('%d')
        );
    }

    public function wp_membership_login_check_jal_install()
    {
        if (get_option('jal_wp-memberchip-login_db_version') != $this->db_version) {
            update_option('jal_wp-memberchip-login_db_version', $this->db_version);
            $this->wp_membership_login_jal_install();
            $this->set_default_membership_data();
        }
    }

    protected function set_default_membership_data()
    {
        $t = apply_filters($this->basename . '/current_theme_directory', false);
        $theme_data = wp_get_theme($t);
        $themeDir = $theme_data->get_template_directory() . DIRECTORY_SEPARATOR;
        $ifMember = $this->getWPMembershipLoginByArgs('', true);
        if (!$ifMember->status) {
            $insertGroup = $this->setMembershipDocumentGroups((object)$this->get_wp_membership_defaults('defaults_document_groups'));
            if (!$insertGroup->status) {
                add_action('admin_notices', array($this, 'wp_membership_install_error_notice'));
            } else {
                $args = array(
                    'post_type' => 'page',
                    'post_status' => 'any',
                    'meta_query' => array(
                        array(
                            'key' => '_wp_page_template',
                            'value' => $this->membership_template_datei,
                        ),
                    ),
                );
                $query = new WP_Query($args);
                $templateDir = plugin_dir_path(dirname(__FILE__)) . 'PageTemplates' . DIRECTORY_SEPARATOR;
                if (!$query->post_count) {
                    $filename = $this->membership_template_datei;
                    $redirect = $this->default_redirect_link;
                } else {
                    $unId = uniqid();
                    $filename = 'wp-membership-' . $unId . '.php';
                    $redirect = $this->default_redirect_link . '-' . $unId;
                }

                if (is_file($templateDir . $this->membership_template_datei) && !is_file($themeDir . $filename)) {
                    $template = file_get_contents($templateDir . $this->membership_template_datei);
                    file_put_contents($themeDir . $filename, $template);
                }
                $ms = $this->get_wp_membership_defaults('default_membership');
                $title_of_the_page = $redirect;
                $pl = strtolower(str_replace(' ', '-', trim($title_of_the_page)));

                $insertArgs = [
                    'comment_status' => 'close',
                    'ping_status' => 'close',
                    'post_author' => 1,
                    'post_title' => ucwords($title_of_the_page),
                    'post_name' => $pl,
                    'post_status' => 'publish',
                    'post_content' => '',
                    'post_type' => 'page',
                    'page_template' => $filename
                ];
                $insertPageId = wp_insert_post($insertArgs, true);
                if (!is_wp_error($insertPageId)) {
                    $ms['redirect_link'] = $pl;
                    $ms['document_group'] = $insertGroup->id;
                    $ms['page_id'] = $insertPageId;
                    $ms['shortcode'] = apply_filters($this->basename.'/generate_random_id',16,0,8);
                    $insertMbs = $this->setWPMembershipLogin((object)$ms);
                }
            }
        }
    }

    public function wp_membership_install_error_notice()
    {
        echo '<div class="notice notice-error is-dismissible" style="margin-top:5rem"><p>' . __('An error occurred while creating the database.', 'wp-memberchip-login') . '</p></div>';
    }

    protected function wp_membership_login_jal_install()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        $table_name = $wpdb->prefix . $this->upload_table;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
    	`id` int(11) NOT NULL AUTO_INCREMENT,
    	`file_id` varchar(128) NOT NULL,
    	`group` int(11) NOT NULL DEFAULT 1,
    	`name` varchar(128) NULL,
		`original` varchar(64) NOT NULL,
		`beschreibung` text NULL,
        `filename` varchar(255) NOT NULL,
        `size` varchar(18) DEFAULT NULL,
        `mime_type` varchar(128) DEFAULT NULL,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `downloads` int(6) NOT NULL DEFAULT 0,
        `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id)
     ) $charset_collate;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . $this->document_group_table;
        $sql = "CREATE TABLE $table_name (
    	`id` int(11) NOT NULL AUTO_INCREMENT,
    	`designation` varchar(128) NOT NULL,
    	`description` text NULL,
		`capabilities` varchar(64) NOT NULL DEFAULT 'read',
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id)
     ) $charset_collate;";
        dbDelta($sql);

        $table_name = $wpdb->prefix . $this->membership_table;
        $sql = "CREATE TABLE $table_name (
    	`id` int(11) NOT NULL AUTO_INCREMENT,
       	`page_id` int(11) NOT NULL UNIQUE,
    	`document_group` int(11) NOT NULL DEFAULT 0,
    	`shortcode` varchar(64) NULL,
    	`designation` text NULL,
    	`self_active` tinyint(1) NOT NULL DEFAULT 1,
		`capabilities` varchar(64) NOT NULL DEFAULT 'read',
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `login_link` varchar(64) NOT NULL DEFAULT 'wp-login.php',
        `redirect_link` varchar(64) NOT NULL DEFAULT 'wp-admin/index.php',
        `note` text NULL, 
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id)
     ) $charset_collate;";
        dbDelta($sql);
    }
}
