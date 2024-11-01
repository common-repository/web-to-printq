<?php
    defined( 'ABSPATH' ) or die( 'Are you trying to trick me?' );

    /*
    * Plugin Name: Web To PrintQ - Product Designer
    * Plugin URI: http://en.web-to-printq.com/wp-designer/
    * Description: Plugin for integrating printQ Designer into WooCommerce
    * Author: CloudLab AG
    * Version: 1.3.2
    * Author URI: http://www.printq.eu
    * Requires at least: 4.6
    * Tested up to: 4.9.6
    *
    * @package printQ
    * @category WebToPrint
    * @author CloudLab AG
    */

    define( 'PRINTQ_DESIGNER_VERSION', '1.3.2' );

    define( 'PRINTQ_ROOT', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

    define( 'PRINTQ_URL', plugin_dir_url( __FILE__ ) );

    require_once PRINTQ_ROOT . 'includes/config.php';

    function pqd_autoload( $class ) {
        if ( strpos( $class, 'Printq_' ) !== false ) {
            $path = strtolower( $class );
            $path = str_replace( 'printq_', '', $path );
            $path = str_replace( '_', DIRECTORY_SEPARATOR, $path );

            $file_name = PRINTQ_INCLUDES_DIR . $path . '.class.php';
            if ( file_exists( $file_name ) ) {
                require_once $file_name;
            }
        }
    }

    spl_autoload_register( 'pqd_autoload' );

    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

        $designer = new Printq_Designer();

        register_activation_hook( __FILE__, array( $designer, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $designer, 'deactivate' ) );

        $designer->run();

    }
