<?php
    defined( 'ABSPATH' ) or die( 'Are you trying to trick me?' );

    class Printq_Designer {

        /**
         * @var string
         */
        static $query_var = 'pqd-projects';
        /**
         * @var string
         */

        static $_endpoint = 'pqd';
        /**
         * The unique identifier of this plugin.
         *
         * @var string
         * @since 1.0.0
         */
        protected $plugin_name;

        /**
         * The current version of the plugin.
         *
         * @var string
         * @since 1.0.0
         */
        protected $plugin_version;

        /**
         * The loader that's responsible for maintaining and registering all hooks that power
         * the plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      Printq_Loader $loader Maintains and registers all hooks for the plugin.
         */
        protected $loader;

        public function __construct() {
            $this->plugin_name    = 'printq_designer';
            $this->plugin_version = PRINTQ_DESIGNER_VERSION;

            $this->loadDependencies();
            $this->setLocale();

            $this->init_hooks();
        }

        protected function init_hooks() {
            $this->loader->add_action( 'plugins_loaded', $this, 'plugins_loaded' );
            $this->loader->add_action( 'init', $this, 'create_post_types' );
            $this->loader->add_action( 'template_redirect', $this, 'process_actions', 100 );

            //todo: delete files when session expires - harder to do due to woocommerce's session persistence
            //$this->loader->add_action( 'woocommerce_cleanup_sessions', $this, 'delete_cart_files', 1 );

            $this->loader->add_action( 'init', $this, 'add_endpoints' );
            $this->loader->add_action( 'woocommerce_account_' . self::$query_var . '_endpoint', $this, 'my_account_projects' );
            $this->loader->add_filter( 'query_vars', $this, 'my_account_query_vars', 10, 1 );
            $this->loader->add_filter( 'woocommerce_account_menu_items', $this, 'add_my_account_link', 10, 1 );
            $this->loader->add_filter( 'request', $this, 'set_query_var' );

            //this should be on designer_public but add to cart is made through ajax
            $this->loader->add_filter( 'woocommerce_add_cart_item_data', $this, 'add_item_meta', 10, 1 );
            $this->loader->add_filter( 'woocommerce_add_order_item_meta', $this, 'add_order_item_meta', 10, 3 );
            $this->loader->add_filter( 'woocommerce_add_cart_item', $this, 'change_item_folder', 10, 2 );

            $this->map_ajax_actions();
        }

        public function add_my_account_link( $items ) {
            $items[ self::$query_var ] = esc_html__( 'PrintQ Projects', PQD_DOMAIN );

            return $items;
        }

        public function my_account_projects() {
            @include PRINTQ_VIEWS_DIR . 'saved_projects.php';
        }

        public function add_endpoints() {
            add_rewrite_endpoint( self::$query_var, EP_ROOT | EP_PAGES );
            add_rewrite_endpoint( self::$_endpoint, EP_ROOT );
        }

        public function set_query_var( $vars ) {
            if ( ! empty( $vars[ self::$_endpoint ] ) ) {
                return $vars;
            }
            if ( isset ( $vars[ self::$_endpoint ] )
                 || ( isset ( $vars['pagename'] ) && self::$_endpoint === $vars['pagename'] )
                 || ( isset ( $vars['page'] ) && self::$_endpoint === $vars['page'] )
            ) {
                // In some cases WP misinterprets the request as a page request and
                // returns a 404.
                $vars['page']             = $vars['pagename'] = $vars['name'] = false;
                $vars[ self::$_endpoint ] = true;
            }

            return $vars;
        }

        public function my_account_query_vars( $vars ) {

            if ( ! empty( $vars[ self::$query_var ] ) ) {
                return $vars;
            }
            $vars[] = self::$query_var;

            return $vars;
        }

        public function activate() {
            //create upload dir
            if ( ! file_exists( PRINTQ_UPLOAD_DIR ) || ! is_dir( PRINTQ_UPLOAD_DIR ) ) {
                if ( ! wp_mkdir_p( PRINTQ_UPLOAD_DIR ) ) {
                    add_action( 'admin_init', array( $this, 'error_create_directory' ) );
                }
            }

            if ( is_multisite() ) {
                update_site_option( 'printq_designer_version', $this->plugin_version );
            } else {
                update_option( 'printq_designer_version', $this->plugin_version );
            }

            $this->create_post_types();
            $this->add_endpoints();

            flush_rewrite_rules();
        }

        public function delete_cart_files() {

            global $wpdb;
            if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {
                //try to use reflection to get table in which sessions are stored
                if ( class_exists( 'ReflectionProperty' ) ) {
                    $session     = WC()->session;
                    $class       = get_class( $session );
                    $refProperty = new ReflectionProperty( $class, '_table' );
                    $refProperty->setAccessible( true );
                    $sessions_table = $refProperty->getValue( $session );
                } else {
                    $sessions_table = $wpdb->prefix . 'woocommerce_sessions';
                }

                $expired_sessions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$sessions_table} WHERE session_expiry < %d;", time() ), ARRAY_A );
                foreach ( $expired_sessions as $expired_session ) {
                    $session_value = maybe_unserialize( $expired_session['session_value'] );
                }
            }
        }

        public function error_create_directory() {
            echo '<div id="message" class="error">';
            echo sprintf( __( 'Failed to create uploads directory. Please make sure you have write permissions on \'%s\'!', PQD_DOMAIN ), dirname( PRINTQ_UPLOAD_DIR ) );
            echo '</div>';
        }

        protected function map_ajax_actions() {
            if ( /*is_admin() &&*/
                defined( 'DOING_AJAX' ) && DOING_AJAX
            ) {
                $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

                $req = str_replace( 'pqd_', '', $action );
                if ( $req ) {
                    //might be grouped by directories
                    $req             = strtoupper( $req[0] ) . substr( $req, 1 );
                    $controller_name = 'Printq_Controller_' . $req;
                    if ( class_exists( $controller_name ) ) {
                        /** @var Printq_Controller_Abstract $controller */
                        $controller         = new $controller_name( $_REQUEST );
                        $controller->isAjax = true;
                        //by default isAjax should be true
                        //can be disabled by setting isAjax=false
                        if ( isset( $_REQUEST['isAjax'] ) && ! ( (bool) ( $_REQUEST['isAjax'] ) ) ) {
                            $controller->isAjax = false;
                        }
                        $this->loader->add_action( "wp_ajax_$action", $controller, 'dispatch' );

                        $subaction = isset( $_REQUEST['subaction'] ) ? sanitize_text_field( $_REQUEST['subaction'] ) : 'index';
                        if ( $subaction && $controller->hasNoPriv( $subaction ) ) {
                            $this->loader->add_action( "wp_ajax_nopriv_$action", $controller, 'dispatch' );
                        }
                        if ( method_exists( $controller, $subaction . 'Action' )
                             && ! $controller->hasNoPriv( $subaction )
                        ) {
                            $this->loader->add_action( "wp_ajax_nopriv_$action", $this, 'loginRequired' );
                        }
                    }
                }
            }
        }

        public function process_actions() {
            $api = trim( get_query_var( self::$_endpoint ), '/' );

            if ( '' === $api ) {
                return;
            }

            $parts               = explode( '/', $api );
            $controller_name     = array_shift( $parts );
            $action              = array_shift( $parts );
            $values              = $this->get_api_values( join( '/', $parts ) );
            $values              = array_merge( $values, $_REQUEST );
            $values['subaction'] = $action;
            $controller_name     = 'Printq_Controller_' . strtoupper( $controller_name[0] ) . substr( $controller_name, 1 );

            try {
                if ( ! class_exists( $controller_name ) ) {
                    throw new Exception( esc_html__( 'Controller cannot be found', PQD_DOMAIN ) );
                }

                /** @var Printq_Controller_Abstract $controller */
                $controller = new $controller_name( $values );
                if ( isset( $_REQUEST['isAjax'] ) && (bool) ( $_REQUEST['isAjax'] ) ) {
                    $controller->isAjax = true;
                }

                $action = ! empty( $action ) ? $action : 'index';
                if ( ! method_exists( $controller, $action . 'Action' ) ) {
                    throw new Exception( esc_html__( 'Requested action does not exist!', PQD_DOMAIN ) );
                }

                if ( ! $controller->hasNoPriv( $action ) && ! is_user_logged_in() ) {
                    throw new Exception( esc_html__( 'Please log in to use this endpoint' ) );
                }
                if ( ! $controller->hasAllowDirect( $action ) ) {
                    throw new Exception( esc_html__( 'No direct access allowed', PQD_DOMAIN ) );
                }

                $controller->dispatch();
            } catch ( Exception $e ) {
                wp_die( $e->getMessage() );
            }

        }

        /**
         * Parse request URI into associative array.
         *
         * @wp-hook template_redirect
         *
         * @param   string $request
         *
         * @return  array
         */
        protected function get_api_values( $request ) {
            $keys    = $values = array();
            $count   = 0;
            $request = trim( $request, '/' );
            $tok     = strtok( $request, '/' );

            while ( $tok !== false ) {
                0 === $count ++ % 2 ? $keys[] = $tok : $values[] = $tok;
                $tok = strtok( '/' );
            }

            // fix odd requests
            if ( count( $keys ) !== count( $values ) ) {
                $values[] = '';
            }

            return array_combine( $keys, $values );
        }

        public function loginRequired() {
            $json = array( 'error' => 1, 'success' => 0, 'message' => __( 'Please login to use this feature', PQD_DOMAIN ) );

            echo json_encode( $json );
            exit;
        }

        public function add_item_meta( $cart_item_data ) {
            if ( isset( $_POST['pqd_content'] ) ) {
                $name                           = 'wp_item_' . md5( implode( '', $cart_item_data ) ) . '_' . mt_rand( 0, 100 );
                $pqd_content                    = $_POST['pqd_content'];
                $cart_item_data['pqd_pdf_data'] = array(
                    'folder' => $name
                );
                if ( pqd_is_active() ) {
                    foreach ( $pqd_content as &$svg ) {
                        $svg = stripslashes( $svg );
                    }

                    try {
                        $pdf = Printq_Helper_Pdf::generate_pdf( $pqd_content, $name, $name );
                        if ( $pdf ) {
                            $cart_item_data['pqd_pdf_data']['pdf'] = sanitize_text_field( $pdf );
                        }
                    } catch ( Exception $e ) {
                        die( $e->getMessage() );
                    }
                }

                //save preview in cart item folder
                if ( isset( $_POST['pqd_image_preview'] ) ) {
                    $images                                   = Printq_Helper_Pdf::showPreview( $_POST['pqd_image_preview'], '.jpeg', $name );
                    $cart_item_data['pqd_pdf_data']['images'] = $images;
                }
            }

            return $cart_item_data;
        }

        protected static function delete_dir( $path ) {
            if ( ! is_dir( $path ) ) {
                return true;
            }
            if ( substr( $path, strlen( $path ) - 1, 1 ) != '/' ) {
                $path .= '/';
            }
            $files = glob( $path . '*', GLOB_MARK );
            foreach ( $files as $file ) {
                if ( is_dir( $file ) ) {
                    self::delete_dir( $file );
                } else {
                    unlink( $file );
                }
            }

            return rmdir( $path );

        }

        public function change_item_folder( $cart_item_data, $cart_item_key ) {
            if ( isset( $cart_item_data['pqd_pdf_data'], $cart_item_data['pqd_pdf_data']['folder'] ) ) {
                //rename directory
                $old_name = PRINTQ_UPLOAD_PREVIEWS_DIR . $cart_item_data['pqd_pdf_data']['folder'];
                $new_name = PRINTQ_UPLOAD_PREVIEWS_DIR . $cart_item_key;

                if ( ! defined( 'FS_CHMOD_FILE' ) ) {
                    define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
                }
                require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
                require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
                $wp_filesystem = new WP_Filesystem_Direct( new StdClass() );

                $result = $wp_filesystem->move( $old_name, $new_name );
                if ( $result ) {
                    $cart_item_data['pqd_pdf_data']['folder'] = $cart_item_key;
                }
            }

            return $cart_item_data;
        }

        public function add_order_item_meta( $item_id, $values, $cart_item_key ) {
            if ( isset( $values['pqd_pdf_data'] ) ) {
                wc_add_order_item_meta( $item_id, 'pqd_pdf_data', $values['pqd_pdf_data'], true );
            }
        }

        public function create_post_types() {
            do_action( 'woocommerce_cleanup_sessions' );
            register_post_type(
                'pqd_template',
                array(
                    'label'              => __( 'printQ Templates', PQD_DOMAIN ),
                    'labels'             => array(
                        'name'                  => __( 'printQ Templates', PQD_DOMAIN ),
                        'singular_name'         => __( 'printQ Template', PQD_DOMAIN ),
                        'add_new_item'          => __( 'Add new printQ Template', PQD_DOMAIN ),
                        'edit_item'             => __( 'Edit printQ Template', PQD_DOMAIN ),
                        'new_item'              => __( 'New printQ Template', PQD_DOMAIN ),
                        'view_item'             => __( 'View printQ Template', PQD_DOMAIN ),
                        'search_items'          => __( 'Search printQ Template', PQD_DOMAIN ),
                        'not_found'             => __( 'No printQ Template found', PQD_DOMAIN ),
                        'not_found_in_trash'    => __( 'No printQ Template found in trash', PQD_DOMAIN ),
                        'all_items'             => __( 'printQ Templates', PQD_DOMAIN ),
                        'insert_into_item'      => __( 'INSERT INTO printQ Template', PQD_DOMAIN ),
                        'uploaded_to_this_item' => __( 'Upload to printQ Template', PQD_DOMAIN ),
                    ),
                    'description'        => __( 'Create awesome templates to use with printQ Editor', PQD_DOMAIN ),
                    'public'             => true,
                    'show_ui'            => true,
                    'show_in_menu'       => true,
                    'show_in_nav_menus'  => true,
                    'map_meta_cap'       => true,
                    'publicly_queryable' => false,
                    'capability_type'    => array( 'pqd_template', 'pqd_templates' ),
                    'capabilities'       => array(
                        'edit_post'              => 'edit_pqd_template',
                        'read_post'              => 'read_pqd_template',
                        'delete_post'            => 'delete_pqd_template',
                        'edit_posts'             => 'edit_pqd_templates',
                        'edit_others_posts'      => 'edit_others_pqd_templates',
                        'publish_posts'          => 'publish_pqd_templates',
                        'read_private_posts'     => 'read_private_pqd_templates',
                        'create_posts'           => 'edit_pqd_templates',
                        'read'                   => 'read',
                        'delete_posts'           => 'delete_pqd_templates',
                        'delete_private_posts'   => 'delete_private_pqd_templates',
                        'delete_published_posts' => 'delete_published_pqd_templates',
                        'delete_others_posts'    => 'delete_others_pqd_templates',
                        'edit_private_posts'     => 'edit_private_pqd_templates',
                        'edit_published_posts'   => 'edit_published_pqd_templates',
                    ),
                    'hierarchical'       => false,
                    'supports'           => array( 'title' ),
                )
            );
            register_post_type(
                'pqd_project',
                array(
                    'label'              => __( 'printQ Saved Project', PQD_DOMAIN ),
                    'labels'             => array(
                        'name'          => __( 'printQ Projects', PQD_DOMAIN ),
                        'singular_name' => __( 'printQ Project', PQD_DOMAIN )
                    ),
                    'description'        => __( 'Save template configurations to use for later', PQD_DOMAIN ),
                    'public'             => false,
                    'show_ui'            => false,
                    'show_in_menu'       => false,
                    'show_in_nav_menus'  => false,
                    'map_meta_cap'       => true,
                    'publicly_queryable' => false,
                    'capability_type'    => array( 'pqd_project', 'pqd_projects' ),
                    'capabilities'       => array(
                        'edit_post'              => 'edit_pqd_project',
                        'read_post'              => 'read_pqd_project',
                        'delete_post'            => 'delete_pqd_project',
                        'edit_posts'             => 'edit_pqd_projects',
                        'edit_others_posts'      => 'edit_others_pqd_projects',
                        'publish_posts'          => 'publish_pqd_projects',
                        'read_private_posts'     => 'read_private_pqd_projects',
                        'create_posts'           => 'edit_pqd_projects',
                        'read'                   => 'read',
                        'delete_posts'           => 'delete_pqd_projects',
                        'delete_private_posts'   => 'delete_private_pqd_projects',
                        'delete_published_posts' => 'delete_published_pqd_projects',
                        'delete_others_posts'    => 'delete_others_pqd_projects',
                        'edit_private_posts'     => 'edit_private_pqd_projects',
                        'edit_published_posts'   => 'edit_published_pqd_projects',
                    ),
                    'supports'           => array(),
                )
            );
            register_post_type(
                'pqd_pcontent',
                array(
                    'label'              => __( 'printQ Saved Project content', PQD_DOMAIN ),
                    'labels'             => array(
                        'name'          => __( 'printQ Projects content', PQD_DOMAIN ),
                        'singular_name' => __( 'printQ Project content', PQD_DOMAIN )
                    ),
                    'description'        => __( 'saved project contents', PQD_DOMAIN ),
                    'public'             => false,
                    'show_ui'            => false,
                    'show_in_menu'       => false,
                    'show_in_nav_menus'  => false,
                    'map_meta_cap'       => true,
                    'publicly_queryable' => false,
                    'capability_type'    => array( 'pqd_pcontent', 'pqd_pcontents' ),
                    'capabilities'       => array(
                        'edit_post'              => 'edit_pqd_pcontent',
                        'read_post'              => 'read_pqd_pcontent',
                        'delete_post'            => 'delete_pqd_pcontent',
                        'edit_posts'             => 'edit_pqd_pcontents',
                        'edit_others_posts'      => 'edit_others_pqd_pcontents',
                        'publish_posts'          => 'publish_pqd_pcontents',
                        'read_private_posts'     => 'read_private_pqd_pcontents',
                        'create_posts'           => 'edit_pqd_pcontents',
                        'read'                   => 'read',
                        'delete_posts'           => 'delete_pqd_pcontents',
                        'delete_private_posts'   => 'delete_private_pqd_pcontents',
                        'delete_published_posts' => 'delete_published_pqd_pcontents',
                        'delete_others_posts'    => 'delete_others_pqd_pcontents',
                        'edit_private_posts'     => 'edit_private_pqd_pcontents',
                        'edit_published_posts'   => 'edit_published_pqd_pcontents',
                    ),
                    'supports'           => array(),
                )
            );
        }

        protected function _getPluginDbVersion() {

            if ( is_multisite() ) {
                $printq_designer_version = get_site_option( 'printq_designer_version' );
            } else {
                $printq_designer_version = get_option( 'printq_designer_version' );
            }

            return $printq_designer_version;
        }

        public function plugins_loaded() {
            $administrator_role      = get_role( 'administrator' );
            $printq_designer_version = $this->_getPluginDbVersion();
            if ( $printq_designer_version != $this->plugin_version ) {
                $printq_designer_manager = add_role( 'printq_designer_manager',
                                                     __( 'printQ Designer Manager', PQD_DOMAIN ),
                                                     array(
                                                         'edit_pqd_template'              => true,
                                                         'read_pqd_template'              => true,
                                                         'delete_pqd_template'            => true,
                                                         'edit_others_pqd_templates'      => true,
                                                         'publish_pqd_templates'          => true,
                                                         'read_private_pqd_templates'     => true,
                                                         'edit_pqd_templates'             => true,
                                                         'delete_pqd_templates'           => true,
                                                         'delete_private_pqd_templates'   => true,
                                                         'delete_published_pqd_templates' => true,
                                                         'delete_others_pqd_templates'    => true,
                                                         'edit_private_pqd_templates'     => true,
                                                         'edit_published_pqd_templates'   => true,
                                                     ) );

                if ( null === $printq_designer_manager ) {
                    // Role exists, just update capabilities.
                    $printq_designer_manager = get_role( 'printq_designer_manager' );
                    $printq_designer_manager->add_cap( 'publish_pqd_templates', true );
                    $printq_designer_manager->add_cap( 'read_pqd_templates', true );
                    $printq_designer_manager->add_cap( 'edit_pqd_templates', true );
                    $printq_designer_manager->add_cap( 'delete_pqd_templates', true );
                }

                $shop_manager_role = get_role( 'shop_manager' );
                if ( null != $shop_manager_role ) {
                    $shop_manager_role->add_cap( 'publish_pqd_templates', true );
                    $shop_manager_role->add_cap( 'read_pqd_templates', true );
                    $shop_manager_role->add_cap( 'edit_pqd_templates', true );
                    $shop_manager_role->add_cap( 'delete_pqd_templates', true );
                }
            }
            if ( null != $administrator_role ) {
                $administrator_role->add_cap( 'edit_pqd_template', true );
                $administrator_role->add_cap( 'read_pqd_template', true );
                $administrator_role->add_cap( 'delete_pqd_template', true );
                $administrator_role->add_cap( 'edit_others_pqd_templates', true );
                $administrator_role->add_cap( 'publish_pqd_templates', true );
                $administrator_role->add_cap( 'read_private_pqd_templates', true );
                $administrator_role->add_cap( 'edit_pqd_templates', true );
                $administrator_role->add_cap( 'delete_pqd_templates', true );
                $administrator_role->add_cap( 'delete_private_pqd_templates', true );
                $administrator_role->add_cap( 'delete_published_pqd_templates', true );
                $administrator_role->add_cap( 'delete_others_pqd_templates', true );
                $administrator_role->add_cap( 'edit_private_pqd_templates', true );
                $administrator_role->add_cap( 'edit_published_pqd_templates', true );
            }

            $this->maybeUpdate();
        }

        public function maybeUpdate() {
            if ( version_compare( $this->_getPluginDbVersion(), '1.3.1', '<' ) ) {
                $this->_updateTo131();
            }
            if ( is_multisite() ) {
                update_site_option( 'printq_designer_version', $this->plugin_version );
            } else {
                update_option( 'printq_designer_version', $this->plugin_version );
            }
        }

        protected function _updateTo131() {
            if ( is_multisite() ) {
                $options = get_blog_option( get_current_blog_id(), 'pqd', array() );
            } else {
                $options = get_option( 'pqd', array() );
            }
            if ( isset( $options['api_key'] ) ) {
                $options['api_key'] = '';
            }
            if ( is_multisite() ) {
                update_blog_option( get_current_blog_id(), 'pqd', $options );
            } else {
                update_option( 'pqd', $options );
            }
        }

        public function deactivate() {

        }

        protected function loadDependencies() {
            $this->loader = new Printq_Loader();
        }

        /**
         * Define the locale for this plugin for internationalization.
         *
         * @since    1.0.0
         * @access   private
         */
        protected function setLocale() {
            $this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
        }

        public function load_plugin_textdomain() {
            load_plugin_textdomain(
                PQD_DOMAIN,
                false,
                PRINTQ_LANG_DIR
            );
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         *
         * @since    1.0.0
         */
        public function run() {
            if ( is_admin() ) {
                $plugin = new Printq_Admin_Designer( $this->plugin_name, $this->plugin_version, $this->loader );
            } else {
                $plugin = new Printq_Public_Designer( $this->plugin_name, $this->plugin_version, $this->loader );
            }
            $plugin->run();
        }
    }
