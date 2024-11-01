<?php
    /**
     * Created by PhpStorm.
     * User: th
     * Date: 12 Iun 2017
     * Time: 18:53
     */

    class Printq_Controller_Projects extends Printq_Controller_Abstract {


        public function _construct() {
            $this->addAllowDirect( array( 'save', 'load', 'delete' ) );
        }

        public function indexAction() {

        }

        public function saveAction() {
            $result    = array();
            $deleteIds = array();
            try {
                if ( ! is_user_logged_in() ) {
                    throw new Exception( esc_html__( 'Please log in!', PQD_DOMAIN ) );
                }

                $addToCartData = $this->getData( 'addToCartData', array() );
                if(isset($addToCartData['add-to-cart'])) {
                    unset( $addToCartData['add-to-cart']);
                }
                $parent_data = array(
                    'post_author'    => get_current_user_id(),
                    'post_content'   => wp_kses_post( $this->getData( 'project_description', 0 ) ),
                    'post_title'     => sanitize_text_field( $this->getData( 'project_name', 0 ) ),
                    'post_status'    => 'publish',
                    'post_type'      => 'pqd_project',
                    'comment_status' => 'closed',
                    'meta_input'     => array(
                        'product_id'       => intval( $this->getData( 'product_id', 0 ) ),
                        'add_to_cart_data' =>$this->getData( 'addToCartData', 0 )
                    )
                );
                if ( $project_id = intval( $this->getData( 'project_id', 0 ) ) ) {
                    $parent_data['ID'] = $project_id;
                }
                $project_id = wp_insert_post( $parent_data, true );
                if ( is_wp_error( $project_id ) ) {
                    $msg = esc_html__( 'Cannot save project. Errors: ', PQD_DOMAIN );
                    $msg .= implode( ' ', $project_id->get_error_messages() );
                    throw new Exception( $msg );
                }
                $deleteIds[]   = $project_id;
                $theme_content = $this->getData( 'content', null );
                if ( $theme_content && is_array( $theme_content ) ) {
                    global $wpdb;
                    $wpdb->delete( $wpdb->posts, array( 'post_parent' => $project_id ) );
                    foreach ( $theme_content as $value ) {
                        $content_data = array(
                            'post_author'    => get_current_user_id(),
                            'post_content'   => $value['content'],
                            'post_title'     => $project_id . ' content ' . $value['page_number'],
                            'post_parent'    => $project_id,
                            'post_status'    => 'publish',
                            'post_type'      => 'pqd_pcontent',
                            'comment_status' => 'closed',
                            'meta_input'     => array(
                                'width'       => $value['width'],
                                'height'      => $value['height'],
                                'page_number' => $value['page_number']
                            )
                        );
                        $content      = wp_insert_post( $content_data, true );
                        if ( is_wp_error( $content ) ) {
                            $msg = esc_html__( 'Cannot save project. Errors: ', PQD_DOMAIN );
                            $msg .= implode( ' ', $content->get_error_messages() );
                            throw new Exception( $msg );
                        }
                        $deleteIds[] = $content;
                    }
                }

                $saved_project                 = get_post( $project_id );
                $result['success']             = true;
                $result['id']                  = $project_id;
                $result['project_name']        = $saved_project->post_title;
                $result['project_description'] = $saved_project->post_content;
            } catch ( Exception $e ) {
                //since wp does not support transactions, try to simulate them
                foreach ( $deleteIds as $delete_id ) {
                    wp_delete_post( $delete_id, true );
                }
                $result = array( 'success' => false, 'message' => $e->getMessage() );
            }

            echo json_encode( $result );
        }

        public function loadAction() {
            try {
                if ( ! is_user_logged_in() ) {
                    throw new Exception( esc_html__( 'Please log in!', PQD_DOMAIN ) );
                }

                $project_id = $this->getData( 'project_id', 0 );
                if ( ! $project_id ) {
                    throw new Exception( esc_html__( 'Please specify a project to load', PQD_DOMAIN ) );
                }
                $result            = Printq_Helper_Design::getProject( $project_id );
                $result['success'] = true;
            } catch ( Exception $e ) {
                $result = array( 'success' => false, 'message' => $e->getMessage() );
            }
            echo wp_json_encode( $result );
        }

        public function deleteAction() {
            try {
                if ( ! is_user_logged_in() ) {
                    throw new Exception( esc_html__( 'Please log in!', PQD_DOMAIN ) );
                }

                $project_id = $this->getData( 'project_id', 0 );
                if ( ! $project_id ) {
                    throw new Exception( esc_html__( 'Please specify a project to delete', PQD_DOMAIN ) );
                }
                $post = get_post( $project_id );

                //allow loading only current user's projects
                if ( empty( $post->ID ) || $post->post_author != get_current_user_id() ) {
                    throw new Exception( esc_html__( 'You can only delete your own projects', PQD_DOMAIN ) );
                }

                $deleted = wp_delete_post( $post->ID, true );
                if ( false === $deleted ) {
                    throw new Exception( esc_html__( 'Cannot delete project', PQD_DOMAIN ) );
                }

                global $wpdb;
                $wpdb->delete( $wpdb->posts, array( 'post_parent' => $post->ID ) );

                if ( ! $this->isAjax ) {
                    wc_add_notice( esc_html__( 'Project successfully deleted', PQD_DOMAIN ) );
                    wp_safe_redirect( wp_get_referer() );
                }
                $result['success'] = true;
            } catch ( Exception $e ) {
                if ( ! $this->isAjax ) {
                    wc_add_notice( sprintf( esc_html__( 'An error has occured while deleting the project: %s', PQD_DOMAIN ), $e->getMessage() ) );
                    wp_safe_redirect( wp_get_referer() );
                }
                $result = array( 'error' => true, 'message' => $e->getMessage() );
            }
            echo wp_json_encode( $result );
        }
    }
