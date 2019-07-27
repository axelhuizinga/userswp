<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * UsersWP forgot password widget.
 *
 * @since 1.0.22
 */
class UWP_Forgot_Widget extends WP_Super_Duper {

    /**
     * Register the forgot password widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'userswp',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['userswp','forgot']",
            'class_name'     => __CLASS__,
            'base_id'       => 'uwp_forgot',
            'name'          => __('UWP > Forgot','userswp'),
            'widget_ops'    => array(
                'classname'   => 'uwp-forgot-class',
                'description' => esc_html__('Displays forgot password form.','userswp'),
            ),
            'arguments'     => array(
                'title'  => array(
                    'title'       => __( 'Widget title', 'userswp' ),
                    'desc'        => __( 'Enter widget title.', 'userswp' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'default'     => '',
                    'advanced'    => false
                ),
                'form_title'  => array(
                    'title'       => __( 'Form title', 'userswp' ),
                    'desc'        => __( 'Enter the form title', 'userswp' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'default'     => '',
                    'placeholder' => __('Forgot Password?', 'userswp'),
                    'advanced'    => true
                ),
	            'design_style'  => array(
		            'title' => __('Design Style', 'userswp'),
		            'desc' => __('The design style to use.', 'userswp'),
		            'type' => 'select',
		            'options'   =>  array(
			            ""        =>  __('default', 'userswp'),
			            "bs1"        =>  __('Style 1', 'userswp'),
			            "bs2"        =>  __('Style 2', 'userswp'),
			            "bs3"        =>  __('Style 3', 'userswp'),
			            "bs4"        =>  __('Style 4', 'userswp'),
		            ),
		            'default'  => '',
		            'desc_tip' => true,
		            'advanced' => true
	            )
            )

        );


        parent::__construct( $options );
    }

    public function output( $args = array(), $widget_args = array(), $content = '' ) {

        if (is_user_logged_in()) {
            return false;
        }

        $defaults = array(
            'form_title' => __('Forgot Password?','userswp'),
        );

        /**
         * Parse incoming $args into an array and merge it with $defaults
         */
        $args = wp_parse_args( $args, $defaults );

        ob_start();

        global $uwp_forgot_widget_args;
        $uwp_forgot_widget_args = $args;

        echo '<div class="uwp_widgets uwp_widget_forgot">';

	    $design_style = !empty($args['design_style']) ? esc_attr($args['design_style']) : uwp_get_option("design_style",'bootstrap');
	    $template = $design_style ? $design_style."/forgot" : "forgot";

        echo '<div class="uwp_page">';

        uwp_locate_template($template);

        echo '</div>';

        echo '</div>';

        $output = ob_get_contents();

        ob_end_clean();

        return trim($output);

    }

}