<?php
/**
 * Plugin Name:          Make Column Clickable Elementor
 * Plugin URI:           https://fernandoacosta.net/make-column-clickable-elementor
 * Description:          Simple: allow users to click in the whole column instead of individual elements
 * Author:               Fernando Acosta
 * Author URI:           https://fernandoacosta.net/?utm_source=wp-org&utm_medium=site&utm_campaign=make-column-clickable
 * Version:              1.4.0
 * License:              GPLv2 or later
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see
 * <https://www.gnu.org/licenses/gpl-2.0.txt>.
 *
 * @package Fernando_Acosta
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

class Make_Column_Clickable_Elementor {
  /**
   * Version.
   *
   * @var float
   */
  const VERSION = '1.4.0';

  /**
   * Instance of this class.
   *
   * @var object
   */
  protected static $instance = null;
  /**
   * Initialize the plugin public actions.
   */
  function __construct() {
    $this->includes();
  }

  public function includes() {
    // framework
    include_once 'includes/class-column-clickable.php';
  }

  /**
   * Return an instance of this class.
   *
   * @return object A single instance of this class.
   */
  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if ( null == self::$instance ) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   * Get main file.
   *
   * @return string
   */
  public static function get_main_file() {
    return __FILE__;
  }

  /**
   * Get plugin path.
   *
   * @return string
   */

  public static function get_plugin_path() {
    return plugin_dir_path( __FILE__ );
  }

  /**
   * Get the plugin url.
   * @return string
   */
  public static function plugin_url() {
    return untrailingslashit( plugins_url( '/', __FILE__ ) );
  }

  /**
   * Get the plugin dir url.
   * @return string
   */
  public static function plugin_dir_url() {
    return plugin_dir_url( __FILE__ );
  }
}

add_action( 'plugins_loaded', array( 'Make_Column_Clickable_Elementor', 'get_instance' ) );
