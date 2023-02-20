<?php

namespace Membership\Login;
/**
 * The Ajax admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 */

defined('ABSPATH') or die();

use Exception;

use stdClass;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Wp_Memberchip_Login;
use WP_Term_Query;

class WP_Membership_Admin_Ajax
{
    use WP_Membership_Login_Settings;

    private static $instance;
    private string $method;
    private object $responseJson;

    /**
     * Store plugin main class to allow child access.
     *
     * @var Environment $twig TWIG autoload for PHP-Template-Engine
     */
    protected Environment $twig;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Memberchip_Login $main The main class.
     */
    private Wp_Memberchip_Login $main;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * @return static
     */
    public static function instance(string $basename, Wp_Memberchip_Login $main, Environment $twig): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $main, $twig);
        }
        return self::$instance;
    }

    public function __construct(string $basename, Wp_Memberchip_Login $main, Environment $twig)
    {
        $this->main = $main;
        $this->twig = $twig;
        $this->basename = $basename;
        $this->method = filter_input(INPUT_POST, 'method', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        $this->responseJson = (object)['status' => false, 'msg' => date('H:i:s', current_time('timestamp')), 'type' => $this->method];
    }

    /**
     * @throws Exception
     */
    public function admin_ajax_handle()
    {
        if (!method_exists($this, $this->method)) {
            throw new Exception("Method not found!#Not Found");
        }
        return call_user_func_array(self::class . '::' . $this->method, []);
    }

    private function update_plugin_settings(): object
    {
        $plugin_min_role = filter_input(INPUT_POST, 'plugin_min_role', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        $download_min_role = filter_input(INPUT_POST, 'download_min_role', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        $mime_type = filter_input(INPUT_POST, 'mime_type', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        $max_file_size = filter_input(INPUT_POST, 'max_file_size', FILTER_VALIDATE_INT);
        filter_input(INPUT_POST, 'bootstrap_css_aktiv', FILTER_UNSAFE_RAW) ? $bootstrap_css_aktiv = 1 : $bootstrap_css_aktiv = 0;
        filter_input(INPUT_POST, 'bootstrap_js_aktiv', FILTER_UNSAFE_RAW) ? $bootstrap_js_aktiv = 1 : $bootstrap_js_aktiv = 0;
        filter_input(INPUT_POST, 'show_dashboard_downloads', FILTER_UNSAFE_RAW) ? $show_dashboard_downloads = 1 : $show_dashboard_downloads = 0;

        $settings = get_option($this->basename . '_settings');
        $settings['plugin_min_role'] = $plugin_min_role;
        $settings['download_min_role'] = $download_min_role;
        $settings['bootstrap_css_aktiv'] = $bootstrap_css_aktiv;
        $settings['bootstrap_js_aktiv'] = $bootstrap_js_aktiv;
        $settings['show_dashboard_downloads'] = $show_dashboard_downloads;
        $settings['mime_types'] = $mime_type;
        $settings['max_file_size'] = (int)$max_file_size;

        update_option($this->basename . '_settings', $settings);
        $this->responseJson->status = true;
        return $this->responseJson;
    }

    private function wp_membership_template_handle(): object
    {
        $this->responseJson->target = filter_input(INPUT_POST, 'target', FILTER_UNSAFE_RAW);
        $this->responseJson->parent = filter_input(INPUT_POST, 'parent', FILTER_UNSAFE_RAW);
        $handle = filter_input(INPUT_POST, 'handle', FILTER_UNSAFE_RAW);
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$handle) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }

        if ($handle == 'update' && !$id) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }

        $tmpData = [];
        if ($handle == 'update') {
            $args = sprintf('WHERE m.id=%d', $id);
            $mbsData = apply_filters($this->basename . '/get_membership_login', $args, false);
            if (!$mbsData->status) {
                $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
                return $this->responseJson;
            }
            $tmpData = (array)$mbsData->record;
        }
        $groupSelect = [];
        $groups = apply_filters($this->basename . '/get_document_groups', 'WHERE g.active=1');
        if ($groups->status) {
            $groupSelect = (array)$groups->record;
        }
        $data = [
            'handle' => $handle,
            'd' => $tmpData,
            'pages' => apply_filters($this->basename . '/get_theme_pages', ''),
            'groups' => $groupSelect,
            'cap' => $this->get_wp_membership_defaults('select_user_role')
        ];

        try {
            $template = $this->twig->render('@loop/wp-membership-formular.html.twig', $data);
            $this->responseJson->template = $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        } catch (Throwable $e) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        }

        $this->responseJson->status = true;
        return $this->responseJson;
    }

    private function membership_handle(): object
    {
        $record = new stdClass();
        $handle = filter_input(INPUT_POST, 'handle', FILTER_UNSAFE_RAW);
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $designation = filter_input(INPUT_POST, 'designation', FILTER_UNSAFE_RAW);
        $page_id = filter_input(INPUT_POST, 'page_id', FILTER_VALIDATE_INT);
        $document_group = filter_input(INPUT_POST, 'document_group', FILTER_VALIDATE_INT);
        $capabilities = filter_input(INPUT_POST, 'capabilities', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        $login_link = filter_input(INPUT_POST, 'login_link', FILTER_UNSAFE_RAW);
        $note = filter_input(INPUT_POST, 'note', FILTER_UNSAFE_RAW);
        filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH) ? $record->active = 1 : $record->active = 0;
        filter_input(INPUT_POST, 'self_active', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH) ? $record->self_active = 1 : $record->self_active = 0;
        if (!$handle) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }

        if ($handle == 'update' && !$id) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }

        $this->responseJson->handle = $handle;

        if (!$designation) {
            $record->designation = date('dmY-His', current_time('timestamp'));
        }
        if (!$page_id) {
            $this->responseJson->msg = __('no page selected', 'wp-memberchip-login');
            return $this->responseJson;
        }

        if ($handle == 'insert') {
            $args = sprintf('WHERE m.page_id=%d', $page_id);
            $mbs = apply_filters($this->basename . '/get_membership_login', $args);
            if ($mbs->status) {
                $this->responseJson->msg = __('There is already a share for this page.', 'wp-memberchip-login');
                return $this->responseJson;
            }
        }

        if (!$capabilities) {
            $this->responseJson->msg = __('no permission selected', 'wp-memberchip-login');
            return $this->responseJson;
        }

        if (!$login_link) {
            $record->login_link = 'wp-login.php';
        } else {
            $record->login_link = $this->fnPregWhitespace(esc_html($login_link));
        }

        $record->designation = $this->fnPregWhitespace(esc_html($designation));
        $record->page_id = (int)$page_id;
        $record->document_group = (int)$document_group;
        $record->capabilities = $this->fnPregWhitespace($capabilities);
        $record->note = $this->fnPregWhitespace(esc_html($note));

        if ($handle == 'update') {
            $args = sprintf('WHERE m.id=%d', (int)$id);
            $member = apply_filters($this->basename . '/get_membership_login', $args, false);
            if (!$member->status) {
                $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
                return $this->responseJson;
            }
            $member = $member->record;
            if ($member->page_id != (int)$page_id) {
                $args = sprintf('WHERE m.page_id=%d', $page_id);
                $mbs = apply_filters($this->basename . '/get_membership_login', $args);
                if ($mbs->status) {
                    $this->responseJson->msg = __('There is already a share for this page.', 'wp-memberchip-login');
                    return $this->responseJson;
                }
            }
        }
        $page = get_post((int)$page_id);
        if (!$page) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }
        $record->redirect_link = esc_html($page->post_name);

        if ($handle == 'insert') {
            $record->sortcode = apply_filters($this->basename . '/generate_random_id', 16, 0, 8);
            $insert = apply_filters($this->basename . '/set_membership_login', $record);
            $this->responseJson->status = $insert->status;
            if (!$insert->status) {
                $this->responseJson->msg = $insert->msg;
            }
            return $this->responseJson;
        }

        $record->id = (int)$id;
        if (!$document_group) {
            $record->shortcode = '';
        }
        $update = apply_filters($this->basename . '/update_membership_login', $record);
        $this->responseJson->status = $update->status;
        if (!$update->status) {
            $this->responseJson->msg = $update->msg;
        }

        return $this->responseJson;
    }

    private function delete_wp_membership(): object
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }
        apply_filters($this->basename . '/delete_membership_login', (int)$id);
        $this->responseJson->title = __('Entry deleted', 'wp-memberchip-login');
        $this->responseJson->msg = __('The entry was successfully deleted.', 'wp-memberchip-login');
        $this->responseJson->status = true;
        $this->responseJson->id = (int)$id;
        return $this->responseJson;
    }

    private function document_upload_template(): object
    {
        $this->responseJson->target = filter_input(INPUT_POST, 'target', FILTER_UNSAFE_RAW);
        $this->responseJson->parent = filter_input(INPUT_POST, 'parent', FILTER_UNSAFE_RAW);

        $title_nonce = wp_create_nonce('wp_membership_login_admin_handle');
        $group = [];
        $selectGroup = apply_filters($this->basename . '/get_document_groups', '');
        if ($selectGroup) {
            $group = (array)$selectGroup->record;
        }
        $data = [
            'nonce' => $title_nonce,
            'group' => $group
        ];

        try {
            $template = $this->twig->render('@loop/wp-membership-upload.html.twig', $data);
            $this->responseJson->template = $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        } catch (Throwable $e) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        }
        $this->responseJson->status = true;
        return $this->responseJson;
    }

    /**
     * @throws Exception
     */
    private function dokument_upload()
    {
        $group = filter_input(INPUT_POST, 'group', FILTER_VALIDATE_INT);
        $settings = get_option($this->basename . '_settings');
        $ifUserUpload = apply_filters($this->basename . '/check_user_capabilities', $settings['plugin_min_role']);
        if (!$ifUserUpload) {
            return $this->responseJson;
        }
        $uploadClass = WP_Membership_Login_Document_Upload::instance($this->basename, $this->main);
        $upload = $uploadClass->document_upload();
        if (!$upload->status) {
            $this->responseJson->msg = $upload->msg;
            exit(json_encode($upload->msg));
        }
        $upload->group = (int)$group;
        $insert = apply_filters($this->basename . '/set_document_import', $upload);
        if (!$insert->status) {
            if (is_file(DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR . $upload->filename)) {
                unlink(DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR . $upload->filename);
            }
            exit(json_encode($insert->msg));
            //return $this->responseJson;
        }
        $ext = preg_replace('/^.*\./', '', $upload->orginalName);
        $this->responseJson->ext = $ext;
        $this->responseJson->size = apply_filters($this->basename . '/FileSizeConvert', $upload->size);
        $this->responseJson->type = $upload->type;
        $this->responseJson->id = $insert->id;
        $this->responseJson->filename = $upload->orginalName;
        $this->responseJson->date = date('d.m.Y', current_time('timestamp'));
        $this->responseJson->time = date('H:i:s', current_time('timestamp'));
        $this->responseJson->status = $upload->status;
        $this->responseJson->msg = $upload->msg;
        $this->responseJson->handle = $this->method;
        return $this->responseJson;
    }

    private function edit_document_template(): object
    {
        $this->responseJson->target = filter_input(INPUT_POST, 'target', FILTER_UNSAFE_RAW);
        $this->responseJson->parent = filter_input(INPUT_POST, 'parent', FILTER_UNSAFE_RAW);
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->responseJson->msg = __('File not found.', 'wp-memberchip-login');
            return $this->responseJson;
        }

        $args = sprintf('WHERE d.id=%d', (int)$id);
        $fileDb = apply_filters($this->basename . '/get_document_import', $args, false);
        if (!$fileDb->status) {
            $this->responseJson->msg = __('File not found.', 'wp-memberchip-login');
            return $this->responseJson;
        }

        $group = [];
        $selectGroup = apply_filters($this->basename . '/get_document_groups', '');
        if ($selectGroup) {
            $group = (array)$selectGroup->record;
        }
        $data = [
            'd' => (array)$fileDb->record,
            'group' => $group
        ];

        try {
            $template = $this->twig->render('@loop/wp-membership-edit-document.html.twig', $data);
            $this->responseJson->template = $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        } catch (Throwable $e) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        }

        $this->responseJson->status = true;
        return $this->responseJson;
    }

    private function update_document(): object
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->responseJson->msg = __('File not found.', 'wp-memberchip-login');
            return $this->responseJson;
        }
        $group = filter_input(INPUT_POST, 'group', FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW);
        $beschreibung = filter_input(INPUT_POST, 'beschreibung', FILTER_UNSAFE_RAW);
        filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW) ? $active = 1 : $active = 0;
        $record = new stdClass();
        $record->id = (int)$id;
        $record->active = $active;
        $record->group = (int)$group;
        $record->name = $this->fnPregWhitespace(esc_html($name));
        $record->beschreibung = $this->fnPregWhitespace(esc_html($beschreibung));
        $update = apply_filters($this->basename . '/update_document_import', $record);
        $this->responseJson->title = $update->title;
        $this->responseJson->status = $update->status;
        $this->responseJson->msg = $update->msg;
        return $this->responseJson;
    }

    private function delete_document(): object
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $this->responseJson->handle = filter_input(INPUT_POST, 'handle', FILTER_UNSAFE_RAW);
        if (!$id) {
            $this->responseJson->msg = __('File not found.', 'wp-memberchip-login');
            return $this->responseJson;
        }
        $args = sprintf('WHERE d.id=%d', $id);
        $fileDb = apply_filters($this->basename . '/get_document_import', $args, false);
        if (!$fileDb->status) {
            $this->responseJson->msg = __('File not found.', 'wp-memberchip-login');
            return $this->responseJson;
        }

        $fileDb = $fileDb->record;
        if (is_file(DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR . $fileDb->filename)) {
            unlink(DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR . $fileDb->filename);
        }
        apply_filters($this->basename . '/delete_document_import', (int)$id);

        $this->responseJson->status = true;
        $this->responseJson->id = $id;
        $this->responseJson->title = __('Document deleted', 'wp-memberchip-login');
        $this->responseJson->msg = __('The document was successfully deleted.', 'wp-memberchip-login');
        return $this->responseJson;
    }

    private function update_document_group(): object
    {
        $record = new stdClass();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $bezeichnung = filter_input(INPUT_POST, 'bezeichnung', FILTER_UNSAFE_RAW);
        $beschreibung = filter_input(INPUT_POST, 'beschreibung', FILTER_UNSAFE_RAW);
        filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW) ? $record->active = 1 : $record->active = 0;
        if (!$id) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }

        if (!$bezeichnung) {
            $record->designation = __('Group', 'wp-memberchip-login') . '-' . uniqid();
        } else {
            $record->designation = $this->fnPregWhitespace(esc_html($bezeichnung));
        }
        $record->description = $this->fnPregWhitespace(esc_html($beschreibung));
        $record->id = (int)$id;
        $update = apply_filters($this->basename . '/update_document_groups', $record);
        $this->responseJson->status = $update->status;
        $this->responseJson->title = $update->title;
        $this->responseJson->msg = $update->msg;
        return $this->responseJson;
    }

    private function add_document_group(): object
    {
        $record = new stdClass();
        $bezeichnung = filter_input(INPUT_POST, 'bezeichnung', FILTER_UNSAFE_RAW);
        $beschreibung = filter_input(INPUT_POST, 'beschreibung', FILTER_UNSAFE_RAW);
        filter_input(INPUT_POST, 'active', FILTER_UNSAFE_RAW) ? $record->active = 1 : $record->active = 0;

        if (!$bezeichnung) {
            $record->designation = __('Group', 'wp-memberchip-login') . '-' . uniqid();
        } else {
            $record->designation = $this->fnPregWhitespace(esc_html($bezeichnung));
        }
        $record->description = $this->fnPregWhitespace(esc_html($beschreibung));
        $insert = apply_filters($this->basename . '/set_document_groups', $record);
        if ($insert->status) {
            $record->id = $insert->id;
            $data = [
                'd' => $record
            ];
            try {
                $template = $this->twig->render('@loop/wp-membership-document-group.html.twig', $data);
                $this->responseJson->template = $this->html_compress_template($template);
            } catch (LoaderError|SyntaxError|RuntimeError $e) {
                $this->responseJson->msg = $e->getMessage();
                return $this->responseJson;
            } catch (Throwable $e) {
                $this->responseJson->msg = $e->getMessage();
                return $this->responseJson;
            }
            $this->responseJson->status = true;
        }

        return $this->responseJson;
    }

    private function delete_group(): object
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->responseJson->msg = __('Ajax transmission error.', 'wp-memberchip-login') . ' (Ajx - ' . __LINE__ . ')';
            return $this->responseJson;
        }
        $args = sprintf('WHERE d.group=%d', (int)$id);
        $docs = apply_filters($this->basename . '/get_document_import', $args);
        if ($docs->status) {
            foreach ($docs->record as $tmp) {
                apply_filters($this->basename . '/update_document_group', 1, $tmp->id);
            }
        }
        $args = sprintf('WHERE m.document_group=%d', (int)$id);
        $members = apply_filters($this->basename . '/get_membership_login', $args);
        if ($members->status) {
            foreach ($members->record as $tmp) {
                apply_filters($this->basename . '/update_membership_group', 0, $tmp->id);
            }
        }

        apply_filters($this->basename . '/delete_document_groups', (int)$id);
        $this->responseJson->msg = __('The group was successfully deleted.', 'wp-memberchip-login');
        $this->responseJson->title = __('Group deleted', 'wp-memberchip-login');
        $this->responseJson->id = $id;
        $this->responseJson->status = true;
        return $this->responseJson;
    }


    private function wp_membership_table(): object
    {
        $query = '';
        $columns = array(
            "m.designation",
            "m.document_group",
            "m.capabilities",
            "m.page_id",
            "m.redirect_link",
            "m.login_link",
            "m.active",
            "m.created_at",
            "",
            ""
        );

        $search = (string)$_POST['search']['value'];
        if (isset($_POST['search']['value'])) {
            $query = ' WHERE m.created_at LIKE "%' . $_POST['search']['value'] . '%"
             OR m.capabilities LIKE "%' . $_POST['search']['value'] . '%"
             OR m.redirect_link LIKE "%' . $_POST['search']['value'] . '%"
             OR m.note LIKE "%' . $_POST['search']['value'] . '%"
             OR m.login_link LIKE "%' . $_POST['search']['value'] . '%"
            ';
        }

        if (isset($_POST['order'])) {
            $query .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
        } else {

            $query .= ' ORDER BY  m.designation ASC';
        }

        $limit = '';
        if ($_POST["length"] != -1) {
            $limit = ' LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
        }

        $table = apply_filters($this->basename . '/get_membership_login', $query . $limit);
        $data_arr = array();
        if (!$table->status) {
            $this->responseJson->draw = $_POST['draw'];
            $this->responseJson->recordsTotal = 0;
            $this->responseJson->recordsFiltered = 0;
            $this->responseJson->data = $data_arr;
            return $this->responseJson;
        }

        foreach ($table->record as $tmp) {
            if ($tmp->active) {
                $checkActive = '<i class="text-success bi bi-check-circle"></i><small class="d-block lh-1 small-xl">' . __('Yes', 'wp-memberchip-login') . '</small>';
            } else {
                $checkActive = '<i class="text-danger bi bi-x-circle"></i><small class="d-block lh-1 small-xl">' . __('No', 'wp-memberchip-login') . '</small>';
            }
            $page = apply_filters($this->basename . '/get_theme_pages', $tmp->page_id);
            $tmp->groupDesignation ? $groupDesignation = $tmp->groupDesignation : $groupDesignation = '<small>' . __('none selected', 'wp-memberchip-login') . '</small>';
            $role = $this->get_wp_membership_defaults('select_user_role', $tmp->capabilities);
            $group = $groupDesignation;
            $shortcode = '';
            if ($tmp->groupID) {
                if ($tmp->groupActive) {
                    $group = '<span title="' . __('active', 'wp-memberchip-login') . '" class="text-green">' . $groupDesignation . '</span>';
                    $shortcode = '<span class="d-block text-nowrap small-xl">[mbl-documents id="' . $tmp->shortcode . '"]</span>';
                } else {
                    $group = '<span title="' . __('not active', 'wp-memberchip-login') . '" class="text-danger">' . $groupDesignation . '</span>';
                }
            }

            $tmp->self_active ? $loginLink = 'self' : $loginLink = $tmp->login_link;
            $data_item = array();
            $data_item[] = '<small>' . $tmp->designation . '</small>';
            $data_item[] = '<span>' . $group . $shortcode . ' </small>';
            $data_item[] = '<small>' . $role['name'] . '</small>';
            $data_item[] = '<small class="text-truncate">' . $page['name'] . '</small>';
            $data_item[] = '<small class="text-nowrap">' . $tmp->redirect_link . '</small>';
            $data_item[] = '<small>' . $loginLink . '</small>';
            $data_item[] = $checkActive;
            $data_item[] = '<small class="lh-1 small">' . date('d.m.Y', strtotime($tmp->created_at)) . '<span class="small-lg mt-1 d-block">' . date('H:i:s', strtotime($tmp->created_at)) . ' ' . __('Clock', 'wp-memberchip-login') . '</span> </small>';
            $data_item[] = '<button data-target="#colMembershipHandle" data-parent="#collParent" data-handle="update" data-type="wp_membership_template_handle" data-id="' . $tmp->id . '" class="mbl-action btn btn-success btn-sm">' . __('Edit', 'wp-memberchip-login') . '</button>';
            $data_item[] = '<button data-type="delete_wp_membership" data-id="' . $tmp->id . '" class="mbl-action btn btn-outline-danger btn-sm text-nowrap"><i class="bi d-inline-block"></i> ' . __('Delete', 'wp-memberchip-login') . '</button>';
            $data_arr[] = $data_item;
        }

        $this->responseJson->draw = $_POST['draw'];
        $tbCount = apply_filters($this->basename . '/get_membership_login', false);
        $this->responseJson->recordsTotal = $tbCount->count;
        $this->responseJson->data = $data_arr;
        if ($search) {
            $this->responseJson->recordsFiltered = count($table);
        } else {
            $this->responseJson->recordsFiltered = $tbCount->count;
        }
        return $this->responseJson;
    }

    private function wp_membership_document_table(): object
    {
        $query = '';
        $columns = array(
            "",
            "",
            "d.name",
            "d.group",
            "d.original",
            "d.size",
            "d.mime_type",
            "d.active",
            "d.beschreibung",
            "d.downloads",
            "d.created_at",
            "",
            "",
            ""
        );

        $search = (string)$_POST['search']['value'];
        if (isset($_POST['search']['value'])) {
            $query = ' WHERE d.created_at LIKE "%' . $_POST['search']['value'] . '%"
             OR d.name LIKE "%' . $_POST['search']['value'] . '%"
             OR d.original LIKE "%' . $_POST['search']['value'] . '%"
             OR d.beschreibung LIKE "%' . $_POST['search']['value'] . '%"
             OR d.mime_type LIKE "%' . $_POST['search']['value'] . '%"
            ';
        }


        if (isset($_POST['order'])) {
            $query .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
        } else {

            $query .= ' ORDER BY  d.name ASC';
        }

        $limit = '';
        if ($_POST["length"] != -1) {
            $limit = ' LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
        }

        $table = apply_filters($this->basename . '/get_document_import', $query . $limit);

        $data_arr = array();
        if (!$table->status) {
            $this->responseJson->draw = $_POST['draw'];
            $this->responseJson->recordsTotal = 0;
            $this->responseJson->recordsFiltered = 0;
            $this->responseJson->data = $data_arr;
            return $this->responseJson;
        }

        foreach ($table->record as $tmp) {
            $ext = preg_replace('/^.*\./', '', $tmp->original);
            if ($tmp->active) {
                $checkActive = '<i title="' . __('Active', 'wp-memberchip-login') . '" class="text-success bi bi-check-circle"></i><small class="d-block lh-1 small-xl">' . __('Yes', 'wp-memberchip-login') . '</small>';
            } else {
                $checkActive = '<i title="' . __('Active', 'wp-memberchip-login') . '" class="text-danger bi bi-x-circle"></i><small class="d-block lh-1 small-xl">' . __('No', 'wp-memberchip-login') . '</small>';
            }
            if ($tmp->beschreibung) {
                $beschreibung = '<i title="' . __('Description', 'wp-memberchip-login') . '" class="text-success bi bi-check-circle"></i><small class="d-block lh-1 small-xl">' . __('Yes', 'wp-memberchip-login') . '</small>';
            } else {
                $beschreibung = '<i title="' . __('Description', 'wp-memberchip-login') . '" class="text-danger bi bi-x-circle"></i><small class="d-block lh-1 small-xl">' . __('No', 'wp-memberchip-login') . '</small>';
            }

            $tmp->groupActive ? $groupClass = 'text-green' : $groupClass = 'text-danger fw-semibold';
            $tmp->groupActive ? $groupTitle = __('active', 'wp-memberchip-login') : $groupTitle = __('Group', 'wp-memberchip-login') . ' ' . __('not active', 'wp-memberchip-login');

            strlen($tmp->mime_type) > 50 ? $dot = '...' : $dot = '';
            $mime_type = substr($tmp->mime_type, 0, 50);
            $tmp->name ? $name = $tmp->name : $name = substr($tmp->original, 0, strrpos($tmp->original, '.'));
            $data_item = array();
            $data_item[] = '<span id="tr-row' . $tmp->id . '" class="table-file file ext_' . $ext . '"></span>';
            $data_item[] = $name;
            $data_item[] = '<span title="' . $groupTitle . '" class="' . $groupClass . '">' . $tmp->groupBezeichnung . '</span>';
            $data_item[] = $tmp->original;
            $data_item[] = '<span class="text-nowrap">' . apply_filters($this->basename . '/fileSizeConvert', $tmp->size) . '</span>';
            $data_item[] = '<div title="' . $tmp->mime_type . '">' . $mime_type . $dot . '</div>';
            $data_item[] = $checkActive;
            $data_item[] = $beschreibung;
            $data_item[] = '<span title="' . __('Downloads', 'wp-memberchip-login') . '">' . $tmp->downloads . '</span>';
            $data_item[] = '<small title="' . __('Created', 'wp-memberchip-login') . '" class="lh-1 small">' . date('d.m.Y', strtotime($tmp->created_at)) . '<span class="small-lg mt-1 d-block">' . date('H:i:s', strtotime($tmp->created_at)) . ' ' . __('Clock', 'wp-memberchip-login') . '</span> </small>';
            $data_item[] = '<a title="' . __('Download', 'wp-memberchip-login') . '" href="' . site_url() . '?' . SECURITY_QUERY_GET . '=' . SECURITY_DOCUMENT_ADMIN_QUERY_URI . '&id=' . $tmp->id . '&type=member" class="btn btn-blue btn-sm"><i class="bi bi-download"></i></a>';
            $data_item[] = '<button title="' . __('Edit', 'wp-memberchip-login') . '" data-target="#colMembershipEditDocument" data-parent="#collParent" data-type="edit_document_template" data-id="' . $tmp->id . '" class="mbl-action btn btn-success btn-sm"><i class="bi bi-pencil-square"></i></button>';
            $data_item[] = '<button title="' . __('Delete', 'wp-memberchip-login') . '" data-type="delete_document" data-id="' . $tmp->id . '" data-handle="overview_table" class="mbl-action btn btn-danger btn-sm text-nowrap"><i class="bi bi-trash"></i></button>';
            $data_arr[] = $data_item;
        }

        $this->responseJson->draw = $_POST['draw'];
        $tbCount = apply_filters($this->basename . '/get_document_import', false);
        $this->responseJson->recordsTotal = $tbCount->count;
        $this->responseJson->data = $data_arr;
        if ($search) {
            $this->responseJson->recordsFiltered = count($table);
        } else {
            $this->responseJson->recordsFiltered = $tbCount->count;
        }
        return $this->responseJson;
    }

    private function wp_membership_download_table(): object
    {
        $query = '';
        $columns = array(
            "",
            "d.name",
            "d.original",
            "d.size",
            "d.mime_type",
            "d.beschreibung",
            ""
        );

        $search = (string)$_POST['search']['value'];
        if (isset($_POST['search']['value'])) {
            $query = ' WHERE d.active=1 AND ( d.created_at LIKE "%' . $_POST['search']['value'] . '%"
             OR d.original LIKE "%' . $_POST['search']['value'] . '%"
             OR d.mime_type LIKE "%' . $_POST['search']['value'] . '%"
             OR d.beschreibung LIKE "%' . $_POST['search']['value'] . '%"
             OR d.name LIKE "%' . $_POST['search']['value'] . '%"
            )';
        }

        if (isset($_POST['order'])) {
            $query .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
        } else {

            $query .= ' ORDER BY  d.original ASC';
        }

        $limit = '';
        if ($_POST["length"] != -1) {
            $limit = ' LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
        }

        $table = apply_filters($this->basename . '/get_document_import', $query . $limit);

        $settings = get_option($this->basename . '_settings');
        $isUser = apply_filters($this->basename.'/check_user_capabilities', $settings['download_min_role']);

        $data_arr = array();
        if (!$table->status || !$isUser) {
            $this->responseJson->draw = $_POST['draw'];
            $this->responseJson->recordsTotal = 0;
            $this->responseJson->recordsFiltered = 0;
            $this->responseJson->data = $data_arr;
            return $this->responseJson;
        }
        foreach ($table->record as $tmp) {
            $ext = preg_replace('/^.*\./', '', $tmp->original);
            strlen($tmp->mime_type) > 50 ? $dot = '...' : $dot = '';
            $mime_type = substr($tmp->mime_type, 0, 50);
            $tmp->name ? $name = $tmp->name : $name = substr($tmp->original, 0, strrpos($tmp->original, '.'));
            $data_item = array();
            $data_item[] = '<span id="tr-row' . $tmp->id . '" class="table-file file ext_' . $ext . '"></span>';
            $data_item[] = $name;
            $data_item[] = $tmp->original;
            $data_item[] = '<span class="text-nowrap">'.apply_filters($this->basename.'/fileSizeConvert', (float)$tmp->size).'</span>';
            $data_item[] = $mime_type . $dot;
            $data_item[] = '<small>' . $tmp->beschreibung . '</small>';
            $data_item[] = '<a href="' . site_url() . '?' . SECURITY_QUERY_GET . '=' . SECURITY_DOCUMENT_ADMIN_QUERY_URI . '&id=' . $tmp->id . '&type=download" class="btn btn-success text-nowrap btn-sm"><i class="bi bi-cloud-download me-1"></i> Download</a>';
            $data_arr[] = $data_item;
        }

        $this->responseJson->draw = $_POST['draw'];
        $tbCount = apply_filters($this->basename.'/get_document_import', 'WHERE d.active=1');
        $this->responseJson->recordsTotal = $tbCount->count;
        $this->responseJson->data = $data_arr;
        if ($search) {
            $this->responseJson->recordsFiltered = count($table);
        } else {
            $this->responseJson->recordsFiltered = $tbCount->count;
        }
        return $this->responseJson;
    }

    private function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }
        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace(["\n", "\r", "\t"], '', $string));
    }

    private function fnPregWhitespace($string): string
    {
        if (!$string) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}