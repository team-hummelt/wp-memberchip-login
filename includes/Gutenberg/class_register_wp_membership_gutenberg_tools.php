<?php

class Register_WP_Membership_Gutenberg_Tools
{

    protected Wp_Memberchip_Login $main;

    /**
     * The ID of this theme.
     *
     * @since    2.0.0
     * @access   private
     * @var      string $basename The ID of this theme.
     */
    protected string $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    protected string $version;

    public function __construct(string $version,string $basename,Wp_Memberchip_Login $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
    }

    public function fn_register_gutenberg_object()
    {
        wp_register_script('wp-membership-gutenberg-localize', '', [], $this->version, true);
        wp_enqueue_script('wp-membership-gutenberg-localize');
        wp_localize_script('wp-membership-gutenberg-localize',
            'MBLEndpoint',
            array(
                'url' => esc_url_raw(rest_url('wp-membership/v1/')),
                'nonce' => wp_create_nonce('wp_rest')
            )
        );
    }

    public function rss_importer_gutenberg_register_sidebar(): void
    {
        //$plugin_asset = require WP_RSS_FEED_IMPORTER_PLUGIN_DIR . '/includes/Gutenberg/Sidebar/build/index.asset.php';
        /* wp_register_script(
             'rss-importer-sidebar',
             WP_RSS_FEED_IMPORTER_PLUGIN_URL . '/includes/Gutenberg/Sidebar/build/index.js',
             $plugin_asset['dependencies'], $plugin_asset['version'], true
         );*/


    }

    public function rss_importer_sidebar_script_enqueue()
    {
	    $plugin_asset = require WP_MEMBERSHIP_LOGIN_PLUGIN_DIR . '/includes/Gutenberg/MembershipWidget/build/index.asset.php';

       /// wp_enqueue_script('rss-importer-sidebar');
        wp_enqueue_style('rss-importer-block-style');
        wp_enqueue_style(
            'rss-importer-block-style',
            WP_MEMBERSHIP_LOGIN_PLUGIN_URL . '/includes/Gutenberg/MembershipWidget/build/index.css', array(), $plugin_asset['version']);


    }

    /**
     * Register TAM MEMBERS REGISTER GUTENBERG BLOCK TYPE
     *
     * @since    1.0.0
     */
    public function register_wp_membership_block_type()
    {
        global $registerWPMemberhipCallback;
        register_block_type('mbl/membership-block', array(
            'render_callback' => [$registerWPMemberhipCallback, 'callback_wp_membership_block_type'],
            'editor_script' => 'wp-membership-gutenberg-block',
        ));
        add_filter('gutenberg_block_wp_membership_callback', array($registerWPMemberhipCallback, 'gutenberg_block_wp_membership_filter'), 10, 4);

    }

    public function wp_membership_block_type_scripts(): void
    {
        $plugin_asset = require WP_MEMBERSHIP_LOGIN_PLUGIN_DIR . '/includes/Gutenberg/MembershipWidget/build/index.asset.php';

        wp_enqueue_script(
            'wp-membership-gutenberg-block',
            WP_MEMBERSHIP_LOGIN_PLUGIN_URL . '/includes/Gutenberg/MembershipWidget/build/index.js',
            $plugin_asset['dependencies'], $plugin_asset['version'], true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wp-membership-gutenberg-block', 'wp-memberchip-login', WP_MEMBERSHIP_LOGIN_PLUGIN_DIR . '/languages');
        }

        wp_localize_script('wp-membership-gutenberg-block',
            'MBLEndpoint',
            array(
                'url' => esc_url_raw(rest_url('wp-membership/v1/')),
                'nonce' => wp_create_nonce('wp_rest')
            )
        );

        wp_enqueue_style(
            'wp-membership-gutenberg-block',
            WP_MEMBERSHIP_LOGIN_PLUGIN_URL . '/includes/Gutenberg/MembershipWidget/build/index.css', array(), $plugin_asset['version']);
    }


	public function fn_rss_posts_meta_fields(): void
    {
        register_meta(
            'post',
            '_wp_membership',
            array(
                'type' => 'string',
                //'object_subtype' => 'immo',
                'single' => true,
                'show_in_rest' => true,
                'default' => '',
                'auth_callback' => array($this, 'wp_membership_post_permissions_check')
            )
        );
    }

	/**
	 * Check if a given request has access.
	 *
	 * @return bool
	 */
	public function wp_membership_post_permissions_check(): bool
	{
		return current_user_can('edit_posts');
	}


}
