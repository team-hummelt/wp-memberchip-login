<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Memberchip_Login
 * @subpackage Wp_Memberchip_Login/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wp_Memberchip_Login_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-memberchip-login',
			false,
			dirname(plugin_basename(__FILE__), 2) . '/languages/'
		);

	}



}
