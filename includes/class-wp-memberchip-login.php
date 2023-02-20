<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/includes
 */

use Membership\Login\Make_Remote_Exec;
use Membership\Login\Srv_Api_Endpoint;
use Membership\Login\WP_Membership_Login_DB_Handle;
use Membership\Login\WP_Membership_Login_Helper;
use Membership\Login\WP_Membership_Login_Rest_Endpoint;
use Membership\Login\WP_Membership_Login_Security_Handle;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use WPMembership\License\Register_Api_WP_Remote;
use WPMembership\License\Register_Product_License;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Memberchip_Login {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Memberchip_Login_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

    /**
     * The Public API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_rsa plugin API ID_RSA.
     */
    private string $id_rsa;


    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_plugin_rsa plugin API ID_RSA.
     */
    private string $id_plugin_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $plugin_api_config plugin API ID_RSA.
     */
    private object $plugin_api_config;

    /**
     * The Public API DIR.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $api_dir plugin API DIR.
     */
    private string $api_dir;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $srv_api_dir plugin Slug Path.
     */
    private string $srv_api_dir;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_slug plugin Slug Path.
     */
    private string $plugin_slug;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @var object The main class.
     */
    public object $main;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

    /**
     * The current database version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $db_version The current database version of the plugin.
     */
    protected string $db_version;

    /**
     * TWIG autoload for PHP-Template-Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Environment $twig TWIG autoload for PHP-Template-Engine
     */
    private Environment $twig;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @throws LoaderError
     * @since    1.0.0
     */
	public function __construct() {
        $this->plugin_name = WP_MEMBERSHIP_LOGIN_BASENAME;
        $this->plugin_slug = WWP_MEMBERSHIP_LOGIN_SLUG_PATH;
        $this->main        = $this;

        define("SECURITY_ERROR_QUERY_URI", 'err');
        define("SECURITY_QUERY_GET", 'security');
        define("SECURITY_DOCUMENT_QUERY_URI", '2QNBtN6MhJWTgum2GPh3');
        define("SECURITY_DOCUMENT_ADMIN_QUERY_URI", 'jfu@xmd7eqe1URC9tnh');

        $upload_dir = wp_get_upload_dir();
        define("DOCUMENT_WP_MEMBERSHIP_UPLOAD_DIR", $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wp-membership-documents' . DIRECTORY_SEPARATOR);
        define("DOCUMENT_WP_MEMBERSHIP_UPLOAD_URL", $upload_dir['baseurl'].'/wp-membership-documents/');


        /**
         * Currently plugin version.
         * Start at version 1.0.0 and use SemVer - https://semver.org
         * Rename this for your plugin and update it as you release new versions.
         */
        $plugin = get_file_data( plugin_dir_path( dirname( __FILE__ ) ) . $this->plugin_name . '.php', array( 'Version' => 'Version' ), false );
        if ( ! $this->version ) {
            $this->version = $plugin['Version'];
        }

        if ( defined( 'WP_MEMBERSHIP_LOGIN_DB_VERSION' ) ) {
            $this->db_version = WP_MEMBERSHIP_LOGIN_DB_VERSION;
        } else {
            $this->db_version = '1.0.0';
        }

        $this->check_dependencies();
        $this->load_dependencies();

        $twigAdminDir = plugin_dir_path( dirname( __FILE__ ) ) . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR;
        $twig_loader  = new FilesystemLoader( $twigAdminDir );
        $twig_loader->addPath( $twigAdminDir . 'Templates', 'templates' );
        $twig_loader->addPath( $twigAdminDir . 'Templates'.DIRECTORY_SEPARATOR.'Layout'.DIRECTORY_SEPARATOR, 'layout' );
        $twig_loader->addPath( $twigAdminDir . 'Templates'.DIRECTORY_SEPARATOR.'Loops'.DIRECTORY_SEPARATOR, 'loop' );
        $this->twig = new Environment( $twig_loader );
        //JOB Twig Filter
        $language   = new TwigFilter( '__', function ( $value ) {
            return __( $value, 'wp-memberchip-login' );
        } );
        $getVersion = new TwigFilter('version', function () {
            return $this->version;
        });
        $getDbVersion = new TwigFilter('dbVersion', function () {
            return $this->version;
        });
        $getOption = new TwigFilter('get_option', function ($option) {
            return get_option($option);
        });
        $this->twig->addFilter( $language );
        $this->twig->addFilter( $getVersion );
        $this->twig->addFilter( $getDbVersion );
        $this->twig->addFilter( $getOption );


        //JOB SRV API FreePlugin
        /*$this->srv_api_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'admin' . DIRECTORY_SEPARATOR . 'srv-api' . DIRECTORY_SEPARATOR;

        if ( is_file( $this->srv_api_dir . 'id_rsa' . DIRECTORY_SEPARATOR . $this->plugin_name . '_id_rsa' ) ) {
            $this->id_plugin_rsa = base64_encode( $this->srv_api_dir . DIRECTORY_SEPARATOR . 'id_rsa' . $this->plugin_name . '_id_rsa' );
        } else {
            $this->id_plugin_rsa = '';
        }
        if ( is_file( $this->srv_api_dir . 'config' . DIRECTORY_SEPARATOR . 'config.json' ) ) {
            $this->plugin_api_config = json_decode( file_get_contents( $this->srv_api_dir . 'config' . DIRECTORY_SEPARATOR . 'config.json' ) );
        } else {
            $this->plugin_api_config = (object) [];
        }*/

		$this->set_locale();
        $this->define_product_license_class();
        //JOB SRV API FreePlugin
        //$this->register_wp_remote_exec();
        //$this->register_wp_rss_importer_rest_endpoint();

        $this->register_wp_membership_helper_class();
        $this->register_wp_membership_login_database_handle();
        $this->register_wp_membership_security();

		$this->define_admin_hooks();
		$this->define_public_hooks();
        $this->register_wp_membership_gutenberg_tools();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Memberchip_Login_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Memberchip_Login_i18n. Defines internationalization functionality.
	 * - Wp_Memberchip_Login_Admin. Defines all hooks for the admin area.
	 * - Wp_Memberchip_Login_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-memberchip-login-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-memberchip-login-i18n.php';

        /**
         * The Settings Trait
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/trait_wp_membership_login_settings.php';

        /**
         * The Helper Class
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class_wp_membership_login_helper.php';


        /**
         * The  database for the WP-Membership Login Plugin
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/database/class_wp_membership_login_db_handle.php';


        /**
         * The  database for the WP-Membership Login Plugin
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Upload/class_wp_membership_login_document_upload.php';



        /**
         * WP Membership Login REST-Endpoint
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_wp_membership_login_rest_endpoint.php';

        /**
         * The  Gutenberg for the WP-Membership Login Plugin
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_register_wp_membership_gutenberg_tools.php';

        /**
         * The  Gutenberg Callback for the WP-Membership Login Plugin
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_wp_membership_gutenberg_block_callback.php';

        /**
         * The  Shortcodes for the WP-Membership Login Plugin
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/PageTemplates/class_wp_membership_shortcodes.php';


        /**
         * Composer-Autoload
         * Composer Vendor for Theme|Plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/vendor/autoload.php';

        /**
         * // JOB The class responsible for defining all actions that occur in the license area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/license/class_register_product_license.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/license/admin/class_register_api_wp_remote.php';

        /**
		 * The class responsible for defining all actions that occur in the admin area.
		 */

        if ( is_file( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-memberchip-login-admin.php' ) ) {

            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/PageTemplates/class_wp_membership_login_security_handle.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-memberchip-login-admin.php';
        }

        //JOB SRV API Endpoint FreePlugin
        /**
         * SRV WP-Remote Exec
         * core plugin.
         */
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/srv-api/config/class_make_remote_exec.php';

        /**
         * SRV WP-Remote API
         * core plugin.
         */
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/srv-api/class_srv_api_endpoint.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-memberchip-login-public.php';

		$this->loader = new Wp_Memberchip_Login_Loader();

	}

    /**
     * Check PHP and WordPress Version
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function check_dependencies(): void {
        global $wp_version;
        if ( version_compare( PHP_VERSION, WP_MEMBERSHIP_LOGIN_MIN_PHP_VERSION, '<' ) || $wp_version < WP_MEMBERSHIP_LOGIN_MIN_WP_VERSION ) {
            $this->maybe_self_deactivate();
        }
    }

    /**
     * Self-Deactivate
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function maybe_self_deactivate(): void {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins( $this->plugin_slug );
        add_action( 'admin_notices', array( $this, 'self_deactivate_notice' ) );
    }

    /**
     * Self-Deactivate Admin Notiz
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function self_deactivate_notice(): void {
        echo sprintf( '<div class="notice notice-error is-dismissible" style="margin-top:5rem"><p>' . __( 'This plugin has been disabled because it requires a PHP version greater than %s and a WordPress version greater than %s. Your PHP version can be updated by your hosting provider.', 'wp-memberchip-login' ) , WP_MEMBERSHIP_LOGIN_MIN_PHP_VERSION, WP_MEMBERSHIP_LOGIN_MIN_WP_VERSION ).'</p></div>';
        exit();
    }

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Memberchip_Login_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Memberchip_Login_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}
    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_product_license_class()
    {

        if(!get_option('hupa_server_url')){
            update_option('hupa_server_url', $this->get_license_config()->api_server_url);
        }

        global $wpRemoteLicense;
        $wpRemoteLicense = new Register_Api_WP_Remote($this->get_plugin_name(), $this->get_version(), $this->get_license_config(), $this->main);
        global $product_license;
        $product_license = new Register_Product_License( $this->get_plugin_name(), $this->get_version(), $this->get_license_config(), $this->main );
        $this->loader->add_action( 'init', $product_license, 'license_site_trigger_check' );
        $this->loader->add_action( 'template_redirect', $product_license, 'license_callback_site_trigger_check' );
    }

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

        if ( is_file( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-memberchip-login-admin.php' ) && get_option( "{$this->plugin_name}_product_install_authorize" ) ) {
            $plugin_admin = new Wp_Memberchip_Login_Admin($this->plugin_name, $this->get_version(), $this->main, $this->twig);

            $this->loader->add_action( 'init', $plugin_admin, 'set_wp_membership_login_update_checker' );
            $this->loader->add_action( 'in_plugin_update_message-' . $this->plugin_name . '/' . $this->plugin_name .'.php', $plugin_admin, 'membership_login_show_upgrade_notification',10,2 );

            $this->loader->add_action('admin_menu', $plugin_admin, 'register_wp_imports_admin_menu');
            $this->loader->add_action('wp_ajax_nopriv_MembershipLogin', $plugin_admin, 'admin_ajax_MembershipLogin');
            $this->loader->add_action('wp_ajax_MembershipLogin', $plugin_admin, 'admin_ajax_MembershipLogin');
            $this->loader->add_action('init', $plugin_admin, 'wp_membership_login_add_query');
            $this->loader->add_action('template_redirect', $plugin_admin, 'wp_membership_login_callback_trigger');

            $registerEndpoint = new WP_Membership_Login_Rest_Endpoint($this->plugin_name, $this->main);
            $this->loader->add_action('rest_api_init', $registerEndpoint, 'register_wp_membership_login_routes');

            global $registerWPMemberhipCallback;
            $registerWPMemberhipCallback = new WP_Membership_Gutenberg_Block_Callback();

            WP_Membership_Shortcodes::instance($this->plugin_name, $this->version, $this->main);

        }
	}

    /**
     * Register all the hooks related to the Gutenberg Plugins functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_membership_login_database_handle() {
        global $wpMembershipDb;
        $wpMembershipDb = WP_Membership_Login_DB_Handle::instance($this->plugin_name, $this->db_version, $this->main);
        $this->loader->add_action( 'init', $wpMembershipDb, 'wp_membership_login_check_jal_install');

        $this->loader->add_filter( $this->plugin_name.'/get_document_import', $wpMembershipDb, 'getMembershipsDocumentsImportsByArgs', 10, 2 );
        $this->loader->add_filter( $this->plugin_name.'/set_document_import', $wpMembershipDb, 'setMembershipDocumentsImport' );
        $this->loader->add_filter( $this->plugin_name.'/update_document_import', $wpMembershipDb, 'updateMembershipDocumentImport' );
        $this->loader->add_filter( $this->plugin_name.'/delete_document_import', $wpMembershipDb, 'deleteMemberDocumentImport' );
        $this->loader->add_filter( $this->plugin_name.'/update_document_download_count', $wpMembershipDb, 'updateMembershipDocumentCountDownload' );
        $this->loader->add_filter( $this->plugin_name.'/update_document_group', $wpMembershipDb, 'updateMembershipDocumentGroup',10,2);

        $this->loader->add_filter( $this->plugin_name.'/get_document_groups', $wpMembershipDb, 'getMembershipDocumentGroupsByArgs', 10, 2 );
        $this->loader->add_filter( $this->plugin_name.'/set_document_groups', $wpMembershipDb, 'setMembershipDocumentGroups' );
        $this->loader->add_filter( $this->plugin_name.'/update_document_groups', $wpMembershipDb, 'updateMembershipDocumentGroups' );
        $this->loader->add_filter( $this->plugin_name.'/delete_document_groups', $wpMembershipDb, 'deleteMembershipDocumentGroups' );

        $this->loader->add_filter( $this->plugin_name.'/get_membership_login', $wpMembershipDb, 'getWPMembershipLoginByArgs', 10, 2 );
        $this->loader->add_filter( $this->plugin_name.'/set_membership_login', $wpMembershipDb, 'setWPMembershipLogin' );
        $this->loader->add_filter( $this->plugin_name.'/update_membership_login', $wpMembershipDb, 'updatetWPMembershipLogin' );
        $this->loader->add_filter( $this->plugin_name.'/delete_membership_login', $wpMembershipDb, 'deleteWPMembershipLogin' );
        $this->loader->add_filter( $this->plugin_name.'/update_membership_group', $wpMembershipDb, 'updateMembershipGroup',10,2 );


    }

    /**
     * Register all the hooks related to the Gutenberg Plugins functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_membership_helper_class() {
        global $wbMembershipHelper;
        $wbMembershipHelper = WP_Membership_Login_Helper::instance($this->version, $this->plugin_name, $this->main);
        $this->loader->add_filter( $this->plugin_name.'/get_random_id', $wbMembershipHelper, 'getMembershipRandomString' );
        $this->loader->add_filter( $this->plugin_name.'/generate_random_id', $wbMembershipHelper, 'getMSLGenerateRandomId',10 ,4);
        $this->loader->add_filter( $this->plugin_name.'/fileSizeConvert', $wbMembershipHelper, 'membershipFileSizeConvert' );
        $this->loader->add_filter( $this->plugin_name.'/ArrayToObject', $wbMembershipHelper, 'membershipArrayToObject' );
        $this->loader->add_filter( $this->plugin_name.'/object2Array', $wbMembershipHelper, 'object2array_recursive' );
        $this->loader->add_filter( $this->plugin_name.'/date_format_language', $wbMembershipHelper, 'wp_membership_login_date_format_language', 10, 3 );
        $this->loader->add_filter( $this->plugin_name.'/current_theme_directory', $wbMembershipHelper, 'wp_membership_login_current_theme_directory' );
        $this->loader->add_filter( $this->plugin_name.'/get_theme_pages', $wbMembershipHelper, 'fn_wp_membership_login_get_theme_pages' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_membership_security() {
        if ( is_file( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-memberchip-login-admin.php' ) ) {
            $security = WP_Membership_Login_Security_Handle::instance($this->plugin_name, $this->version, $this->main);
            $security->fn_wp_membership_login_show_query();
            $this->loader->add_filter($this->plugin_name . '/check_user_capabilities', $security, 'fn_child_check_user_capabilities');
        }
    }




	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Memberchip_Login_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

    /**
     * Register RSS Gutenberg Tools
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_membership_gutenberg_tools() {
        $gbTools = new Register_WP_Membership_Gutenberg_Tools($this->version, $this->plugin_name, $this->main);
        $this->loader->add_action('init', $gbTools, 'register_wp_membership_block_type');
        $this->loader->add_action('enqueue_block_editor_assets', $gbTools, 'wp_membership_block_type_scripts');

    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string
    {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Memberchip_Login_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Wp_Memberchip_Login_Loader
    {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string
    {
		return $this->version;
	}

    /**
     * Register API SRV Rest-Api Endpoints
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_remote_exec() {
        global $wpRemoteExec;
        $wpRemoteExec = Make_Remote_Exec::instance( $this->plugin_name, $this->get_version(), $this->main );
    }

    /**
     * Register WP_REST ENDPOINT
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_rss_importer_rest_endpoint() {
        global $rss_importer_public_endpoint;
        $rss_importer_public_endpoint = new Srv_Api_Endpoint( $this->plugin_name, $this->version, $this->main );
        $this->loader->add_action( 'rest_api_init', $rss_importer_public_endpoint, 'register_routes' );

    }

    public function get_plugin_api_config(): object {
        return $this->plugin_api_config;
    }

    /**
     * License Config for the plugin.
     *
     * @return    object License Config.
     * @since     1.0.0
     */
    public function get_license_config():object {
        $config_file = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/license/config.json';
        return json_decode(file_get_contents($config_file));
    }

}
