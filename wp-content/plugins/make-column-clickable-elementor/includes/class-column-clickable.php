<?php
/**
 * Main Class
 *
 * @author   Fernando_Acosta
 * @since    1.0.0
 * @package  make-column-clickable-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'Make_Column_Clickable_Setup' ) ) :

  /**
   * The main Make_Column_Clickable_Setup class
   */
  class Make_Column_Clickable_Setup {
    public function __construct() {
      add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

      add_action( 'elementor/element/column/layout/before_section_end', array( $this, 'widget_extensions' ), 10, 2 );
      add_action( 'elementor/frontend/column/before_render', array( $this, 'before_render_options' ), 10 );
    }


    /**
     * After layout callback
     *
     * @param  object $element
     * @param  array $args
     * @return void
     */
    public function widget_extensions( $element, $args ) {
      $element->add_control(
        'column_link',
        [
          'label'       => __( 'Column Link', 'make-column-clickable-elementor' ),
          'type'        => Elementor\Controls_Manager::URL,
          'dynamic'     => [
            'active' => true,
          ],
          'placeholder' => __( 'https://your-link.com', 'elementor' ),
          'selectors'   => [
        ],
        ]
      );
    }


    public function before_render_options( $element ) {
      $settings  = $element->get_settings_for_display();

      if ( isset( $settings['column_link'], $settings['column_link']['url'] ) && ! empty( $settings['column_link']['url'] ) ) {
        wp_enqueue_script( 'make-column-clickable-elementor' );

        // start of WPML
        do_action( 'wpml_register_single_string', 'Make Column Clickable Elementor', 'Link - ' . $settings['column_link']['url'], $settings['column_link']['url'] );
        $settings['column_link']['url'] = apply_filters('wpml_translate_single_string', $settings['column_link']['url'], 'Make Column Clickable Elementor', 'Link - ' . $settings['column_link']['url'] );
        // end of WPML

        $element->add_render_attribute( '_wrapper', 'class', 'make-column-clickable-elementor' );
        $element->add_render_attribute( '_wrapper', 'style', 'cursor: pointer;' );
        $element->add_render_attribute( '_wrapper', 'data-column-clickable', $settings['column_link']['url'] );
        $element->add_render_attribute( '_wrapper', 'data-column-clickable-blank', $settings['column_link']['is_external'] ? '_blank' : '_self' );
      }
    }


    public function frontend_scripts() {
      wp_register_script( 'make-column-clickable-elementor', plugins_url( 'assets/js/make-column-clickable.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), Make_Column_Clickable_Elementor::VERSION, true );
    }
  }

endif;

new Make_Column_Clickable_Setup();
