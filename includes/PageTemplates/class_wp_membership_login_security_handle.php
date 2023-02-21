<?php

namespace Membership\Login;

use stdClass;
use Wp_Memberchip_Login;
use WP_Query;

class WP_Membership_Login_Security_Handle
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
     * @var      string $version The current version of the database Version.
     */
    protected string $version;

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
    public static function instance(string $basename, string $version, Wp_Memberchip_Login $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $version, $main);
        }
        return self::$instance;
    }

    public function __construct(string $basename, string $version, Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
    }

    public function fn_wp_membership_login_show_query()
    {
        add_action('parse_request', array($this, 'fn_wp_membership_security_login_wp_parse_request'));
    }

    public function fn_wp_membership_security_login_wp_parse_request($wp)
    {
        global $wp;
        if(!is_admin() && isset($wp->query_vars['pagename'])) {
            $args = sprintf('WHERE m.active=1 and m.redirect_link="%s"', $wp->query_vars['pagename']);
            $pages = apply_filters($this->basename . '/get_membership_login', $args, false);
            if ($pages->status) {
                $pages = $pages->record;
                if (!is_admin()) {
                    if (!is_user_logged_in()) {
                        $settings = get_option($this->basename . '_settings');
                        if($settings['error_page']){
                            $page = get_post((int)$settings['error_page']);
                            $url = site_url().'/'.$page->post_name;
                        } else {
                            $url = wp_login_url();
                        }
                        @ob_flush();
                        @ob_end_flush();
                        @ob_end_clean();
                        wp_redirect($url);
                        exit();
                    }
                    $capabilities = $this->fn_child_check_user_capabilities($pages->capabilities);
                    if (!$capabilities) {
                        @ob_flush();
                        @ob_end_flush();
                        @ob_end_clean();
                        wp_redirect(site_url() . '?' . SECURITY_QUERY_GET . '=' . SECURITY_ERROR_QUERY_URI);
                        exit();
                    }
                    add_action('wp_enqueue_scripts', array($this, 'fn_wp_membership_login_public_enqueue_styles'));
                }
            }
        }
    }

    public function fn_wp_membership_login_public_enqueue_styles()
    {
        $settings = get_option($this->basename.'_settings');
        if($settings['bootstrap_css_aktiv']){
            wp_enqueue_style($this->basename . '-public-bs-style', plugin_dir_url(dirname(__FILE__, 2)) . 'admin/assets/css/bs/bootstrap.min.css', array(), $this->version, false);
        }
        if($settings['bootstrap_js_aktiv']){
            wp_enqueue_script($this->basename . '-public-bs', plugin_dir_url(dirname(__FILE__, 2)) . 'admin/assets/js/bs/bootstrap.bundle.min.js', array(), $this->version, true);
        }
    }

    public function fn_child_check_user_capabilities($capabilities): bool
    {
        global $current_user;
        if(user_can($current_user->ID, $capabilities) ) {
            return true;
        }
        return false;
    }

}