<?php
    defined( 'ABSPATH' ) or die( 'Are you trying to trick me?' );

    class Printq_Helper_Design {

        static function getUserUploadDirectory() {
            $key = 'mySq47234#@dfasd';

            $customer = get_current_user_id();
            if ( $customer ) {
                $sid = $customer;
            } else {
                if ( ! ( $sid = session_id() ) ) {
                    session_start();
                    $sid = session_id();
                }
            }

            $sid = md5( $sid . $key );

            return $sid;
        }

        static function getUploadedImageUrl() {
            return PRINTQ_UPLOAD_URL;
        }

        static function getProjectSize( $type ) {
            $size = array();
            switch ( $type ) {
                case 'a4':
                    $size['width']  = 793;
                    $size['height'] = 1122;
                    break;
                case 'blog':
                    $size['width']  = 800;
                    $size['height'] = 1200;
                    break;
                case 'card':
                    $size['width']  = 560;
                    $size['height'] = 396;
                    break;
                case 'email':
                    $size['width']  = 600;
                    $size['height'] = 200;
                    break;
                case 'facebookCover':
                    $size['width']  = 851;
                    $size['height'] = 315;
                    break;
                case 'facebookPost':
                    $size['width']  = 940;
                    $size['height'] = 788;
                    break;
                case 'poster':
                    $size['width']  = 1587;
                    $size['height'] = 2245;
                    break;
                case 'social':
                    $size['width']  = 800;
                    $size['height'] = 800;
                    break;
                default:
                    $size['width']  = 800;
                    $size['height'] = 600;
                    break;
            }

            return $size;
        }

        public static function getUserSavedProjects( $userId = null ) {
            if ( ! $userId ) {
                $userId = get_current_user_id();
            }
            $post_query = array(
                'post_type' => 'pqd_project',
                'author'    => $userId
            );

            return get_posts( $post_query );
        }

        static function getProductSavedProjects( $userId, $productId ) {
            $savedProjects = array();
            //if( $userId != get_current_user_id() ){
            //    return $savedProjects;
            //}

            $wc_product = wc_get_product( $productId );
            if ( ! $wc_product ) {
                //product no longer exists
                return $savedProjects;
            }

            $post_query = array(
                'post_type'  => 'pqd_project',
                'author'     => $userId,
                'meta_key'   => 'product_id',
                'meta_value' => $productId
            );

            return get_posts( $post_query );
        }

        public static function getProject( $project_id ) {

            $post = get_post( $project_id );

            //allow loading only current user's projects
            if ( empty( $post->ID ) || $post->post_author != get_current_user_id() ) {
                throw new Exception( esc_html__( 'Project could not be found', PQD_DOMAIN ) );
            }

            $content_query = array(
                'post_type'   => 'pqd_pcontent',
                'post_parent' => $project_id,
                'orderby'     => 'meta_value_num',
                'order'     => 'ASC',
                'meta_key'    => 'page_number',

            );

            $result   = array(
                'id'                  => $post->ID,
                'project_name'        => $post->post_title,
                'project_description' => $post->post_content,
                'product_id'          => get_post_meta( $post->ID, 'product_id', true ),
                'items'               => array()
            );
            $contents = get_posts( $content_query );
            foreach ( $contents as $content ) {
                $result['items'][] = array(
                    'content_type' => 'json',
                    'content'      => $content->post_content,
                    'width'        => get_post_meta( $content->ID, 'width', true ),
                    'height'       => get_post_meta( $content->ID, 'height', true ),
                    'page_number'  => get_post_meta( $content->ID, 'page_number', true )
                );
            }

            $result['add_to_cart_data'] = get_post_meta( $post->ID, 'add_to_cart_data', true);
            if( isset( $result['add_to_cart_data']['add-to-cart']) ){
                unset( $result['add_to_cart_data']['add-to-cart']);
            }

            return $result;
        }
    }
