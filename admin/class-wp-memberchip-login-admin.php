<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/admin
 */

use Membership\Login\WP_Membership_Admin_Ajax;
use Membership\Login\WP_Membership_Login_Settings;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/admin
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Memberchip_Login_Admin
{

    use WP_Membership_Login_Settings;

    /**
     * Store plugin main class to allow admin access.
     *
     * @since    2.0.0
     * @access   private
     * @var Wp_Memberchip_Login $main The main class.
     */
    protected Wp_Memberchip_Login $main;

    /**
     * TWIG autoload for PHP-Template-Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Environment $twig TWIG autoload for PHP-Template-Engine
     */
    protected Environment $twig;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * The default Settings
     *
     * @since    1.0.0
     * @access   private
     * @var      array $settings The current version of this plugin.
     */
    protected array $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $basename The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct(string $basename, string $version, Wp_Memberchip_Login $main, Environment $twig)
    {

        $this->basename = $basename;
        $this->version = $version;
        $this->main = $main;
        $this->twig = $twig;
        $this->settings = $this->get_wp_membership_defaults();
    }

    public function register_wp_imports_admin_menu(): void
    {
        //delete_option($this->basename . '_settings');
        if (!get_option($this->basename . '_settings')) {
            update_option($this->basename . '_settings', $this->settings['upload_settings']);
        }
        add_menu_page(
            __('Members', 'wp-memberchip-login'),
            __('Members', 'wp-memberchip-login'),
            get_option($this->basename . '_settings')['plugin_min_role'],
            'membership-login',
            '',
            $this->get_svg_icons('incognito')
            , 200
        );

        $hook_suffix = add_submenu_page(
            'membership-login',
            __('Settings', 'wp-memberchip-login'),
            __('Settings', 'wp-memberchip-login'),
            get_option($this->basename . '_settings')['plugin_min_role'],
            'membership-login',
            array($this, 'wp_membership_login_startseite'));

        add_action('load-' . $hook_suffix, array($this, 'wp_membership_login_load_ajax_admin_script'));

        $hook_suffix = add_submenu_page(
            'membership-login',
            __('Documents', 'wp-memberchip-login'),
            __('Documents', 'wp-memberchip-login'),
            get_option($this->basename . '_settings')['plugin_min_role'],
            'membership-login-documents',
            array($this, 'wp_membership_login_documents'));

        add_action('load-' . $hook_suffix, array($this, 'wp_membership_login_load_ajax_admin_script'));

        $hook_suffix = add_submenu_page(
            'membership-login',
            __('Document groups', 'wp-memberchip-login'),
            __('Document groups', 'wp-memberchip-login'),
            get_option($this->basename . '_settings')['plugin_min_role'],
            'membership-login-documents-groups',
            array($this, 'wp_membership_login_document_groups'));

        add_action('load-' . $hook_suffix, array($this, 'wp_membership_login_load_ajax_admin_script'));

        if(get_option($this->basename . '_settings')['show_dashboard_downloads']) {
            $hook_suffix = add_menu_page(
                __('Downloads', 'wp-memberchip-login'),
                __('Downloads', 'wp-memberchip-login'),
                get_option($this->basename . '_settings')['download_min_role'],
                'membership-login-downloads',
                array($this, 'wp_membership_login_documents_downloads'),
                $this->get_svg_icons('download')
                , 201
            );

            add_action('load-' . $hook_suffix, array($this, 'wp_membership_login_load_ajax_admin_script'));
        }
    }

    public function wp_membership_login_startseite()
    {
        $data = [
            'select' => $this->get_wp_membership_defaults('select_user_role'),
            'sdb' => get_option($this->basename . '_settings')
        ];
        try {
            $template = $this->twig->render('@templates/wp-membership-login-startseite.html.twig', $data);
            echo $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_membership_login_documents()
    {
        $data = [];
        try {
            $template = $this->twig->render('@templates/wp-membership-documents.html.twig', $data);
            echo $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_membership_login_document_groups()
    {
        $groupData = [];
        $groups = apply_filters($this->basename.'/get_document_groups', '');
        if($groups->status) {
            $groupData = (array) $groups->record;
        }
        $data = [
            'data' => $groupData
        ];
        try {
            $template = $this->twig->render('@templates/wp-membership-group.html.twig', $data);
            echo $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_membership_login_documents_downloads()
    {
        $data = [];
        try {
            $template = $this->twig->render('@templates/wp-membership-download.html.twig', $data);
            echo $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_membership_login_load_ajax_admin_script()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        $title_nonce = wp_create_nonce('wp_membership_login_admin_handle');
        wp_register_script($this->basename . '-admin-ajax-script', '', [], '', true);
        wp_enqueue_script($this->basename . '-admin-ajax-script');
        wp_localize_script($this->basename . '-admin-ajax-script', 'wml_ajax_obj',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => $title_nonce,
                'data_table' => plugin_dir_url(__FILE__) . 'assets/js/tools/DataTablesGerman.json',
                'js_lang' => $this->js_language()
            ));
    }

    /**
     * @throws Exception
     */
    public function admin_ajax_MembershipLogin(): void
    {
        check_ajax_referer('wp_membership_login_admin_handle');
        require 'Ajax/class_wp_membership_admin_ajax.php';
        $adminAjaxHandle = WP_Membership_Admin_Ajax::instance($this->basename, $this->main, $this->twig);
        wp_send_json($adminAjaxHandle->admin_ajax_handle());
    }

    public function wp_membership_login_add_query(): void
    {
        global $wp;
        $wp->add_query_var(SECURITY_QUERY_GET);
    }

    function wp_membership_login_callback_trigger(): void
    {
        if (get_query_var(SECURITY_QUERY_GET) == SECURITY_ERROR_QUERY_URI) {
         include_once (plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PageTemplates/security-page-cap-error.php');
         exit();
        }

        if (get_query_var(SECURITY_QUERY_GET) == SECURITY_DOCUMENT_QUERY_URI) {
            require(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PageTemplates/class_wp_membership_document_download.php');
            $download = WP_Membership_Document_Download::instance($this->basename, $this->main);
            $download->security_document_download();
            exit();
        }
        if (get_query_var(SECURITY_QUERY_GET) == SECURITY_DOCUMENT_ADMIN_QUERY_URI) {
            require(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PageTemplates/class_wp_membership_document_admin_download.php');
            $download = WP_Membership_Document_Admin_Download::instance($this->basename, $this->main);
            $download->security_document_admin_download();
            exit();
        }

    }

    /**
     * Register the Update-Checker for the Plugin.
     *
     * @since    1.0.0
     */
    public function set_wp_membership_login_update_checker()
    {
        if (get_option("{$this->basename}_server_api") && get_option($this->basename . '_server_api')->update->update_aktiv) {
            $membershipLoginUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                get_option("{$this->basename}_server_api")->update->update_url_git,
                WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR . $this->basename . '.php',
                $this->basename
            );

            if (get_option("{$this->basename}_server_api")->update->update_type == '1') {
                if (get_option("{$this->basename}_server_api")->update->update_branch == 'release') {
                    $membershipLoginUpdateChecker->getVcsApi()->enableReleaseAssets();
                } else {
                    $membershipLoginUpdateChecker->setBranch(get_option("{$this->basename}_server_api")->update->branch_name);
                }
            }
        }
    }

    public function membership_login_show_upgrade_notification($current_plugin_metadata, $new_plugin_metadata)
    {

        /**
         * Check "upgrade_notice" in readme.txt.
         *
         * Eg.:
         * == Upgrade Notice ==
         * = 20180624 = <- new version
         * Notice        <- message
         *
         */
        if (isset($new_plugin_metadata->upgrade_notice) && strlen(trim($new_plugin_metadata->upgrade_notice)) > 0) {

            // Display "upgrade_notice".
            echo sprintf('<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'wp-memberchip-login'), esc_html(rtrim($new_plugin_metadata->upgrade_notice)));

        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wp_Memberchip_Login_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wp_Memberchip_Login_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        $membership_current_screen = get_current_screen();

        wp_enqueue_script('jquery');
        wp_enqueue_style($this->basename . '-admin-bs-style', plugin_dir_url(__FILE__) . 'assets/css/bs/bootstrap.min.css', array(), $this->version, false);
        wp_enqueue_style($this->basename . '-animate', plugin_dir_url(__FILE__) . 'assets/css/tools/animate.min.css', array(), $this->version);
        wp_enqueue_style($this->basename . '-swal2', plugin_dir_url(__FILE__) . 'assets/css/tools/sweetalert2.min.css', array(), $this->version, false);

        wp_enqueue_style($this->basename . '-bootstrap-icons-style', WP_MEMBERSHIP_LOGIN_PLUGIN_URL . 'includes/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css', array(), $this->version);
        wp_enqueue_style($this->basename . '-font-awesome-icons-style', WP_MEMBERSHIP_LOGIN_PLUGIN_URL . 'includes/vendor/components/font-awesome/css/font-awesome.min.css', array(), $this->version);
        wp_enqueue_style($this->basename . '-admin-dashboard-style', plugin_dir_url(__FILE__) . 'assets/admin-dashboard-style.css', array(), $this->version, false);
        wp_enqueue_style($this->basename . '-admin-data-tables-bs5', plugin_dir_url(__FILE__) . 'assets/css/tools/dataTables.bootstrap5.min.css', array(), $this->version, false);
        //wp_enqueue_style($this->basename . '-admin-data-tables-bs5', plugin_dir_url(__FILE__) . 'assets/css/tools/datatables.min.css', array(), $this->version, false);

        wp_enqueue_script($this->basename . '-bs', plugin_dir_url(__FILE__) . 'assets/js/bs/bootstrap.bundle.min.js', array(), $this->version, true);
        wp_enqueue_script($this->basename . '-swal2', plugin_dir_url(__FILE__) . 'assets/js/tools/sweetalert2.all.min.js', array(), $this->version, true);
        wp_enqueue_script($this->basename . '-data-table', plugin_dir_url(__FILE__) . 'assets/js/tools/data-table/jquery.dataTables.min.js', array(), $this->version, true);
        wp_enqueue_script($this->basename . '-bs-data-table', plugin_dir_url(__FILE__) . 'assets/js/tools/data-table/dataTables.bootstrap5.min.js', array(), $this->version, true);
        //wp_enqueue_script($this->basename . '-bs-data-table', plugin_dir_url(__FILE__) . 'assets/js/tools/data-table/datatables.min.js', array(), $this->version, true);
        wp_enqueue_script($this->basename . '-tables', plugin_dir_url(__FILE__) . 'assets/js/wpMembershipTables.js', false, $this->version, true);

        if($membership_current_screen->base == 'mitglieder_page_membership-login-documents'){
            wp_enqueue_style($this->basename.'-admin-dropzone', plugin_dir_url(__FILE__) . '/assets/css/tools/dropzone.min.css', array(), $this->version, false);
            wp_enqueue_script($this->basename . '-dropzone', plugin_dir_url(__FILE__) . 'assets/js/tools/dropzone/dropzone.min.js', array(), $this->version, true);
            wp_enqueue_script($this->basename . '-dropzone-options', plugin_dir_url(__FILE__) . 'assets/js/tools/dropzone/dropzone-optionen.js', array(), $this->version, true);
        }

        wp_enqueue_script($this->basename.'_global', plugin_dir_url(__FILE__) . 'assets/js/wp-membership-global.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->basename, plugin_dir_url(__FILE__) . 'assets/js/wp-memberchip-login-admin.js', array('jquery'), $this->version, false);
    }

    /**
     * @param $name
     *
     * @return string
     */
    private static function get_svg_icons($name): string
    {
        $icon = '';
        switch ($name) {
            case'shield':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
                         <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                         <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                          </svg>';
                break;
            case 'incognito':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-incognito" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="m4.736 1.968-.892 3.269-.014.058C2.113 5.568 1 6.006 1 6.5 1 7.328 4.134 8 8 8s7-.672 7-1.5c0-.494-1.113-.932-2.83-1.205a1.032 1.032 0 0 0-.014-.058l-.892-3.27c-.146-.533-.698-.849-1.239-.734C9.411 1.363 8.62 1.5 8 1.5c-.62 0-1.411-.136-2.025-.267-.541-.115-1.093.2-1.239.735Zm.015 3.867a.25.25 0 0 1 .274-.224c.9.092 1.91.143 2.975.143a29.58 29.58 0 0 0 2.975-.143.25.25 0 0 1 .05.498c-.918.093-1.944.145-3.025.145s-2.107-.052-3.025-.145a.25.25 0 0 1-.224-.274ZM3.5 10h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5Zm-1.5.5c0-.175.03-.344.085-.5H2a.5.5 0 0 1 0-1h3.5a1.5 1.5 0 0 1 1.488 1.312 3.5 3.5 0 0 1 2.024 0A1.5 1.5 0 0 1 10.5 9H14a.5.5 0 0 1 0 1h-.085c.055.156.085.325.085.5v1a2.5 2.5 0 0 1-5 0v-.14l-.21-.07a2.5 2.5 0 0 0-1.58 0l-.21.07v.14a2.5 2.5 0 0 1-5 0v-1Zm8.5-.5h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5Z"/>
                          </svg>';
                break;
            case'download':
                 $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                          <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                          <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                          </svg>';
                break;

            default:
        }

        return 'data:image/svg+xml;base64,' . base64_encode($icon);

    }

    protected function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }

        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace([
            "\n",
            "\r",
            "\t"
        ], '', $string));
    }

}
