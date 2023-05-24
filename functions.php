<?php
/**
 * @package BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */

/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since BuddyBoss Child 1.0.0
 */
function buddyboss_theme_child_languages()
{
    /**
     * Makes child theme available for translation.
     * Translations can be added into the /languages/ directory.
     */

    // Translate text from the PARENT theme.
    load_theme_textdomain('buddyboss-theme', get_stylesheet_directory() . '/languages');

    // Translate text from the CHILD theme only.
    // Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
    // load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );

}
add_action('after_setup_theme', 'buddyboss_theme_child_languages');

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since Boss Child Theme  1.0.0
 */
function buddyboss_theme_child_scripts_styles()
{
    /**
     * Scripts and Styles loaded by the parent theme can be unloaded if needed
     * using wp_deregister_script or wp_deregister_style.
     *
     * See the WordPress Codex for more information about those functions:
     * http://codex.wordpress.org/Function_Reference/wp_deregister_script
     * http://codex.wordpress.org/Function_Reference/wp_deregister_style
     **/

    // Styles
    wp_enqueue_style('buddyboss-child-css', get_stylesheet_directory_uri() . '/assets/css/custom.css', array(), time());
    wp_enqueue_style('buddyboss-child-jquery-datatable-css', get_stylesheet_directory_uri() . '/assets/css/jquery.dataTables.min.css');

    // Javascript
    //Add Amchart libraries
    wp_enqueue_script('amcharts5-index', get_stylesheet_directory_uri() . '/assets/js/amcharts5/index.js');
    wp_enqueue_script('amcharts5-xy', get_stylesheet_directory_uri() . '/assets/js/amcharts5/xy.js');
    wp_enqueue_script('amcharts5-theme-animated', get_stylesheet_directory_uri() . '/assets/js/amcharts5/themes/Animated.js');
    wp_enqueue_script('amcharts5-theme-dark', get_stylesheet_directory_uri() . '/assets/js/amcharts5/themes/Dark.js');

    wp_enqueue_script('moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js');
    wp_enqueue_script('buddyboss-child-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array('jquery', 'moment-js', 'amcharts5-index', 'amcharts5-xy', 'amcharts5-theme-animated', 'amcharts5-theme-dark'), time());
    wp_enqueue_script('buddyboss-child-jquery-datatable-js', get_stylesheet_directory_uri() . '/assets/js/dataTable/jquery.dataTables.min.js', array('jquery'));
    wp_enqueue_script('buddyboss-child-dataTables-buttons-js', get_stylesheet_directory_uri() . '/assets/js/dataTable/dataTables.buttons.min.js', array('jquery', 'buddyboss-child-jquery-datatable-js'));
    wp_enqueue_script('buddyboss-child-jszip-js', get_stylesheet_directory_uri() . '/assets/js/dataTable/jszip.min.js', array('jquery', 'buddyboss-child-jquery-datatable-js'));
    wp_enqueue_script('buddyboss-child-buttons-html5-js', get_stylesheet_directory_uri() . '/assets/js/dataTable/buttons.html5.min.js', array('jquery', 'buddyboss-child-jquery-datatable-js'));

    wp_localize_script(
        'buddyboss-child-js',
        'globalOtherlife',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'site_url' => site_url(),
        )
    );

    // wp_enqueue_script('buddyboss-child-bootstrap-js', get_stylesheet_directory_uri() . '/assets/js/bootstrap.bundle.min.js', array('jquery'));
}
add_action('wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999);

/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here

///////added by Juan///////////////////

// WordPress Core
// remove_action('admin_init', 'send_frame_options_header');
// remove_action('login_init', 'send_frame_options_header');

// Just in case
// remove_action('init', 'send_frame_options_header');


define('FirstPromoter_INCLUDES', trailingslashit(__DIR__ . '/firstpromoter'));
require_once FirstPromoter_INCLUDES . 'firstpromoter.php';

define('GHL_INCLUDES', trailingslashit(__DIR__ . '/GHL'));
require_once GHL_INCLUDES . 'main.php';
///////End here////////////////////////