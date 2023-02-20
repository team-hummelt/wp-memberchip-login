<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/public
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Memberchip_Login_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 *@since    1.0.0
	 */
	public function __construct(string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-memberchip-login-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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

        global $wp_query;
        $settings = get_option($this->plugin_name.'_settings');
        if( isset($wp_query->query_vars['security']) && $settings['bootstrap_css_aktiv'] ){
            wp_enqueue_style($this->plugin_name . '-public-bs-style', plugin_dir_url(dirname(__FILE__)) . 'admin/assets/css/bs/bootstrap.min.css', array(), $this->version, false);
       }
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-memberchip-login-public.js', array( 'jquery' ), $this->version, false );

	}

}
