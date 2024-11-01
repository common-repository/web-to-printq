<?php
    defined( 'ABSPATH' ) or die( 'Are you trying to trick me?' );

    abstract class Printq_Controller_Abstract {

        public $no_priv          = array( 'index' );
        public $allow_direct     = array();
        public $continue_actions = array();

        public $isAjax = false;

        protected $data = array();

        protected $_wp_scripts = null;
        protected $_wp_styles  = null;

        public function __construct( $data = array() ) {
            $this->_wp_scripts = new WP_Scripts();
            $this->_wp_styles  = new WP_Styles();
            $this->data        = (array) $data;
            $this->_construct();
        }

        public function getData( $key = null, $default = null ) {
            //todo: add __get and __set
            if ( ! is_null( $key ) ) {
                if ( isset( $this->data[ $key ] ) ) {
                    return $this->data[ $key ];
                } else {
                    return $default;
                }
            } else {
                return $this->data;
            }
        }


        protected function _construct() {

        }

        public function addNoPriv( $action ) {

            if ( is_array( $action ) ) {
                $this->no_priv = array_merge( $this->no_priv, $action );
            } else {
                if ( ! in_array( $action, $this->no_priv ) ) {
                    $this->no_priv[] = $action;
                }
            }

        }

        public function removeNoPriv( $action ) {

            if ( is_array( $action ) ) {
                $this->no_priv = array_diff( $this->no_priv, $action );
            } else {
                $position = array_search( $action, $this->no_priv );
                if ( $position !== false ) {
                    array_splice( $this->no_priv, $position, 1 );
                }
            }
        }

        public function hasNoPriv( $action ) {
            return in_array( $action, $this->no_priv );
        }

        public function addAllowDirect( $action ) {

            if ( is_array( $action ) ) {
                $this->allow_direct = array_merge( $this->allow_direct, $action );
            } else {
                if ( ! in_array( $action, $this->allow_direct ) ) {
                    $this->allow_direct[] = $action;
                }
            }

        }

        public function removeAllowDirect( $action ) {

            if ( is_array( $action ) ) {
                $this->allow_direct = array_diff( $this->allow_direct, $action );
            } else {
                $position = array_search( $action, $this->allow_direct );
                if ( $position !== false ) {
                    array_splice( $this->allow_direct, $position, 1 );
                }
            }

        }

        public function hasAllowDirect( $action ) {
            return in_array( $action, $this->allow_direct );
        }

        public function dispatch( $defaultAction = '' ) {
            $subaction = $this->getData( 'subaction', $defaultAction );
            if ( ! $this->getData( 'pqd_nonce' ) || ! wp_verify_nonce( $this->getData( 'pqd_nonce' ), 'pqd_nonce' ) ) {
                $this->nonceErrorAction();
                exit;
            }
            if ( $subaction ) {
                $subaction .= 'Action';
                if ( method_exists( $this, $subaction ) ) {
                    $this->$subaction();
                } else {
                    $this->errorAction();
                }
            } else {
                $this->indexAction();
            }
            if ( ! isset( $this->continue_actions[ $subaction ] ) ) {
                //not all actions are ajax ones
                exit;
            }
        }

        abstract public function indexAction();

        public function enqueue_style( $handle, $src, $deps = array(), $ver = PRINTQ_DESIGNER_VERSION, $args = null ) {
            $this->_wp_styles->add( $handle, $src, $deps, $ver, $args );
            $this->_wp_styles->enqueue( $handle );
        }

        public function enqueue_script( $handle, $src, $deps = array(), $ver = PRINTQ_DESIGNER_VERSION, $args = null ) {
            $this->_wp_scripts->add( $handle, $src, $deps, $ver, $args );
            $this->_wp_scripts->enqueue( $handle );
        }

        public function localize( $handle, $object_name, $l10n ) {
            $this->_wp_scripts->localize( $handle, $object_name, $l10n );
        }

        public function errorAction() {
            $json = array( 'error' => 1, 'success' => 0, 'message' => __( 'Action does not exist' ) );

            echo json_encode( $json );
        }

        public function nonceErrorAction() {
            $json = array( 'error' => 1, 'success' => 0, 'message' => __( 'Are you trying trick me?' ) );

            echo json_encode( $json );
        }

    }
